<?php
session_start();
include("../config/connexion.php");

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'secretaire') {
    header("Location: ../login.php"); exit;
}

// 1. RÉCUPÉRATION DES FILTRES
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

if (isset($_GET['medecin_id'])) {
    $_SESSION['filter_medecin'] = $_GET['medecin_id'];
}
$filter_medecin = $_SESSION['filter_medecin'] ?? '';

$user_id = $_SESSION['user_id'];
$stmt_u = $pdo->prepare("SELECT nom, prenom FROM utilisateurs WHERE id_user = ?");
$stmt_u->execute([$user_id]);
$user_info = $stmt_u->fetch();

// 2. LISTE DES MÉDECINS
$medecins_list = $pdo->query("SELECT id_user, nom, prenom, specialite FROM utilisateurs WHERE LOWER(role) = 'medecin' ORDER BY nom ASC")->fetchAll();

// 3. REQUÊTE SQL
$sql = "SELECT 
            s.id_suivi, 
            s.date_suivi, 
            s.commentaire, 
            s.status,
            s.id_patient,
            p.nom AS pat_nom, 
            p.prenom AS pat_prenom, 
            u.nom AS med_nom 
        FROM suivis s
        JOIN patients p ON s.id_patient = p.id_patient
        JOIN utilisateurs u ON s.id_medecin = u.id_user
        WHERE DATE(s.date_suivi) = :date_sel";

if (!empty($filter_medecin)) {
    $sql .= " AND s.id_medecin = :id_med";
}
$sql .= " ORDER BY s.date_suivi ASC";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':date_sel', $selected_date);
if (!empty($filter_medecin)) {
    $stmt->bindValue(':id_med', $filter_medecin, PDO::PARAM_INT);
}
$stmt->execute();
$suivis = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>MedCare | Suivis</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #0d9488; /* Le vert du menu */
            --primary-light: #f0fdfa;
            --primary-soft: rgba(13, 148, 136, 0.08);
            --secondary: #6366f1;
            /* Fond de page : Dégradé vert menthe très très clair */
            --bg-body: radial-gradient(circle at center, #f0fdfa 0%, #f8fafc 100%);
            --sidebar-bg: #0f172a;
            --white: #ffffff;
            --border: #e2e8f0;
            --text-main: #334155;
            --header-height: 75px;
            --sidebar-width: 260px;
            /* Box Shadow teinté en VERT */
            --card-shadow: 0 10px 25px -5px rgba(13, 148, 136, 0.15), 0 8px 10px -6px rgba(13, 148, 136, 0.1);
        }

        * { margin:0; padding:0; box-sizing:border-box; font-family: 'Inter', sans-serif; }
        body { background: var(--bg-body); color: var(--text-main); min-height: 100vh; }

        header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 0 40px; height: var(--header-height);
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid var(--border);
            position: fixed; top: 0; left: 0; right: 0; z-index: 1000;
        }

        .sidebar { 
            width: var(--sidebar-width); background: var(--sidebar-bg); 
            padding: 24px 16px; position: fixed; top: var(--header-height); left: 0; bottom: 0; z-index: 999;
        }
        .sidebar a { 
            display: flex; align-items: center; gap: 12px; color: #94a3b8; 
            text-decoration: none; padding: 12px 16px; border-radius: 10px; transition: 0.2s; margin-bottom: 5px;
        }
        .sidebar a.active { background: var(--primary); color: white; box-shadow: 0 4px 15px rgba(13, 148, 136, 0.4); }
        .sidebar a:hover:not(.active) { background: rgba(255,255,255,0.05); color: white; }

        .content { margin-left: var(--sidebar-width); margin-top: var(--header-height); padding: 40px; }

        /* CARTES AVEC OMBRE VERTE */
        .search-wrapper {
            background: var(--white); padding: 25px; border-radius: 16px;
            border: 1px solid rgba(13, 148, 136, 0.1); 
            box-shadow: var(--card-shadow); 
            margin-bottom: 30px;
        }

        .section-box { 
            background: var(--white); padding: 25px; border-radius: 16px; 
            border: 1px solid var(--border); 
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
        }

        .user-pill { background: var(--primary-light); color: var(--primary); border: 1px solid #ccfbf1; padding: 8px 16px; border-radius: 10px; font-weight: 600; }

        /* TABLE */
        table { width: 100%; border-collapse: separate; border-spacing: 0; }
        th { color: #64748b; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; padding: 15px 12px; border-bottom: 2px solid #f1f5f9; }
        td { padding: 15px 12px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        tr:hover td { background-color: var(--primary-light); } /* Hover Vert très clair */

        /* BADGES */
        .status-badge { padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; display: inline-block; }
        .status-encours { background: #fffbeb; color: #92400e; border: 1px solid #fef3c7; }
        .status-termine { background: #f0fdf4; color: #166534; border: 1px solid #dcfce7; }

        .btn-action { background: #ffffff; border: 1px solid var(--border); color: var(--primary); border-radius: 8px; width: 35px; height: 35px; display: inline-flex; align-items: center; justify-content: center; transition: 0.3s; }
        .btn-action:hover { background: var(--primary); color: white; transform: translateY(-2px); box-shadow: 0 4px 8px rgba(13, 148, 136, 0.2); }
        
        .form-control, .form-select {
            border-radius: 10px; border: 1px solid #e2e8f0; padding: 10px 15px;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--primary); box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.1);
        }
    </style>
</head>
<body>

<header>
    <img src="../images/logo_app2.png" alt="Logo" style="height: 45px;">
    <div class="user-pill">
        <i class="fa-solid fa-circle-user me-2"></i>
        <span>Séc. <?= htmlspecialchars($user_info['prenom']." ".$user_info['nom']) ?></span>
    </div>
</header>

<div class="wrapper">
    <aside class="sidebar">
        <h3 style="color:rgba(255,255,255,0.3); font-size:11px; text-transform:uppercase; margin-bottom:20px; padding-left:12px;">Menu Gestion</h3>
        <a href="dashboard_secretaire.php"><i class="fa-solid fa-chart-line"></i> Vue Générale</a>
        <a href="patients_secr.php" ><i class="fa-solid fa-user-group"></i> Patients</a>
         <a href="../admission/admissions_list.php"><i class="fa-solid fa-hospital-user"></i> Admissions</a>
        <a href="suivis.php" class="active"><i class="fa-solid fa-calendar-check"></i> Suivis</a>
        <a href="caisse.php"><i class="fa-solid fa-wallet"></i> Caisse & Factures</a>
         <a href="profil_secretaire.php"><i class="fa-solid fa-user"></i> Profil</a>
        <div style="height: 1px; background: rgba(255,255,255,0.1); margin: 20px 0;"></div>
        <a href="../connexio_utilisateur/deconnexion.php" style="color: #fda4af;"><i class="fa-solid fa-power-off"></i> Déconnexion</a>
    </aside>

    <main class="content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 fw-bold mb-1" style="color: #0f172a;">Suivis Patients</h1>
                <p class="text-muted small">Consultations pour le <span class="fw-bold text-dark"><?= date('d M Y', strtotime($selected_date)) ?></span></p>
            </div>
            <a href="suivis.php?date=<?= date('Y-m-d') ?>" class="btn btn-sm btn-white border shadow-sm rounded-pill px-3 fw-bold" style="background:white; color: var(--primary);">
                <i class="fa-solid fa-calendar-day me-1"></i> Aujourd'hui
            </a>
        </div>

        <div class="search-wrapper">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted">DATE DE CONSULTATION</label>
                    <input type="date" name="date" class="form-control" value="<?= $selected_date ?>" onchange="this.form.submit()">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-muted">FILTRER PAR MÉDECIN</label>
                    <select name="medecin_id" class="form-select" onchange="this.form.submit()">
                        <option value="">Tous les médecins de la clinique</option>
                        <?php foreach($medecins_list as $m): ?>
                            <option value="<?= $m['id_user'] ?>" <?= $filter_medecin == $m['id_user'] ? 'selected' : '' ?>>
                                Dr. <?= htmlspecialchars($m['nom']." ".$m['prenom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>

        <div class="section-box">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th class="ps-3">Heure</th>
                            <th>Patient</th>
                            <th>Médecin</th>
                            <th>Commentaire</th>
                            <th>Statut</th>
                            <th class="text-end pe-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($suivis)): ?>
                            <?php foreach($suivis as $s): ?>
                            <tr>
                                <td class="ps-3 fw-bold" style="color: var(--primary);"><?= date('H:i', strtotime($s['date_suivi'])) ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= strtoupper($s['pat_nom']) ?> <?= $s['pat_prenom'] ?></div>
                                </td>
                                <td>
                                    <div class="small fw-medium"><i class="fa-solid fa-stethoscope me-1 text-primary"></i> Dr. <?= $s['med_nom'] ?></div>
                                </td>
                                <td>
                                    <span class="text-muted small"><?= htmlspecialchars($s['commentaire'] ?: '---') ?></span>
                                </td>
                                <td>
                                    <span class="status-badge <?= strtolower($s['status']) == 'terminé' ? 'status-termine' : 'status-encours' ?>">
                                        <?= $s['status'] ?: 'En attente' ?>
                                    </span>
                                </td>
                                <td class="text-end pe-3">
                                    <a href="dossier_patient.php?id=<?= $s['id_patient'] ?>" class="btn-action">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="text-muted opacity-50">
                                        <i class="fa-regular fa-calendar-xmark fa-3x mb-3"></i>
                                        <p>Aucun suivi pour ce jour.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
</body>
</html>