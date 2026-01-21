<?php
session_start();
include("../config/connexion.php");

// --- LOGIQUE SECRETAIRE CONSERVÉE ---
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'secretaire') {
    header("Location: ../login.php"); exit;
}

$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

if (isset($_GET['medecin_id'])) {
    $_SESSION['filter_medecin'] = $_GET['medecin_id'];
}
$filter_medecin = $_SESSION['filter_medecin'] ?? '';

$user_id = $_SESSION['user_id'];
$stmt_u = $pdo->prepare("SELECT nom, prenom FROM utilisateurs WHERE id_user = ?");
$stmt_u->execute([$user_id]);
$user_info = $stmt_u->fetch();

$medecins_list = $pdo->query("SELECT id_user, nom, prenom, specialite FROM utilisateurs WHERE LOWER(role) = 'medecin' ORDER BY nom ASC")->fetchAll();

$sql = "SELECT 
            s.id_suivi, s.date_suivi, s.commentaire, s.status, s.id_patient,
            p.nom AS pat_nom, p.prenom AS pat_prenom, 
            u.nom AS med_nom, u.prenom AS med_prenom
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
    <title>MedCare | Journal des Suivis</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --primary: #0d9488; /* Teal un peu plus vif */
            --primary-light: #f0fdfa;
            --primary-hover: #0f766e;
            --sidebar-bg: #0f172a; /* Bleu ardoise plus doux que le noir pur */
            --bg-body: #f1f5f9; /* Fond légèrement bleuté clair */
            --text-main: #0f172a;
            --text-muted: #121315;
            --white: #ffffff;
            --border: #e2e8f0;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        * { margin:0; padding:0; box-sizing:border-box; font-family: 'Inter', sans-serif; }
        body { background: var(--bg-body); color: var(--text-main); }

        header {
            background: var(--white);
            padding: 0 40px; height: 75px;
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid var(--border);
            position: fixed; top: 0; left: 0; right: 0; z-index: 1000;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }
        .user-pill {
            background: var(--primary-light); padding: 8px 18px; border-radius: 12px;
            display: flex; align-items: center; gap: 10px;
            font-size: 14px; font-weight: 600; color: var(--primary);
            border: 1px solid rgba(13, 148, 136, 0.2);
        }

        .sidebar { 
            width: 260px; background: var(--sidebar-bg); padding: 24px 16px; 
            position: fixed; top: 75px; left: 0; bottom: 0; z-index: 900;
        }
        .sidebar h3 { color: rgba(255,255,255,0.4); font-size: 11px; text-transform: uppercase; margin-bottom: 20px; padding-left: 12px; }
        .sidebar a {
            display: flex; align-items: center; gap: 12px; color: #cbd5e1; text-decoration: none;
            padding: 12px 16px; border-radius: 10px; margin-bottom: 5px; transition: 0.2s;
        }
        .sidebar a:hover { background: rgba(255,255,255,0.1); color: #fff; }
        .sidebar a.active { background: var(--primary); color: #fff; box-shadow: 0 4px 12px rgba(13, 148, 136, 0.3); }

        .content { flex: 1; padding: 40px; margin-left: 260px; margin-top: 75px; }
        
        .page-header { 
            margin-bottom: 35px; padding-bottom: 20px; border-bottom: 1px solid var(--border);
            display: flex; align-items: center; gap: 20px;
        }
        .page-icon {
            width: 60px; height: 60px; background: var(--primary); color: white;
            border-radius: 16px; display: flex; align-items: center; justify-content: center;
            font-size: 24px; box-shadow: 0 10px 15px -3px rgba(13, 148, 136, 0.3);
        }

        .search-wrapper {
            background: var(--white); padding: 20px; border-radius: 16px;
            border: 1px solid var(--border); margin-bottom: 30px;
            box-shadow: var(--shadow);
        }

        .suivi-card {
            background: var(--white); border-radius: 16px; border: 1px solid var(--border);
            padding: 25px; margin-bottom: 20px; display: flex; gap: 25px;
            transition: transform 0.2s ease;
            box-shadow: var(--shadow);
        }
        .suivi-card:hover { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); }

        .suivi-date-box {
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            background: #f8fafc; min-width: 90px; height: 90px; border-radius: 12px;
            border: 1px solid var(--border);
        }
        .suivi-date-box .day { font-size: 26px; font-weight: 800; color: var(--primary); line-height: 1; }
        .suivi-date-box .month { font-size: 12px; text-transform: uppercase; font-weight: 700; color: var(--text-muted); margin-top: 4px; }

        .commentaire-text {
            color: var(--text-main); font-size: 14px; background: #fdfdfd;
            padding: 15px; border-radius: 12px; margin: 12px 0; border: 1px solid #f1f5f9; border-left: 5px solid var(--primary);
        }

        .badge-status { font-size: 11px; padding: 6px 14px; border-radius: 50px; font-weight: 700; text-transform: uppercase; }
        .status-termine { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .status-encours { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }

        .btn-action {
            padding: 8px 16px; border-radius: 10px; text-decoration: none; font-size: 13px; font-weight: 600;
            display: inline-flex; align-items: center; gap: 8px; border: 1px solid var(--border);
            background: var(--white); color: var(--text-main); transition: 0.2s;
        }
        .btn-action:hover { background: var(--primary-light); border-color: var(--primary); color: var(--primary); }
        .btn-folder { background: var(--primary); color: white; border: none; box-shadow: 0 4px 6px rgba(13, 148, 136, 0.2); }
        .btn-folder:hover { background: var(--primary-hover); color: white; }

        @media(max-width:900px){ .sidebar { display:none; } .content { margin-left: 0; } }
    </style>
</head>
<body>

<header>
    <img src="../images/logo_app2.png" alt="Logo" style="height: 45px;">
    <div class="user-pill">
        <i class="fa-solid fa-circle-user"></i>
        <span>Séc. <?= htmlspecialchars($user_info['prenom']." ".$user_info['nom']) ?></span>
    </div>
</header>

<div class="d-flex">
    <aside class="sidebar">
        <h3>Menu Gestion</h3>
        <a href="dashboard_secretaire.php"><i class="fa-solid fa-chart-line"></i> Vue Générale</a>
        <a href="patients_secr.php"><i class="fa-solid fa-user-group"></i> Patients</a>
        <a href="../admission/admissions_list.php"><i class="fa-solid fa-hospital-user"></i> Admissions</a>
        <a href="suivis.php" class="active"><i class="fa-solid fa-calendar-check"></i> Suivis</a>
        <a href="caisse.php"><i class="fa-solid fa-wallet"></i> Caisse & Factures</a>
        <a href="profil_secretaire.php"><i class="fa-solid fa-user"></i> Profil</a>
        <div style="height: 1px; background: rgba(255,255,255,0.1); margin: 20px 0;"></div>
        <a href="../connexio_utilisateur/deconnexion.php" style="color: #fda4af;"><i class="fa-solid fa-power-off"></i> Déconnexion</a>
    </aside>

    <main class="content">
        <div class="page-header">
            <div class="page-icon"><i class="fa-solid fa-clipboard-list"></i></div>
            <div>
                
                <h3 class="subtitle fw-bold" style="color: var(--text-muted);">Journal des Suivis </strong></h3>
            </div>
        </div>

        <div class="search-wrapper">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted">DATE</label>
                    <input type="date" name="date" class="form-control" value="<?= $selected_date ?>" onchange="this.form.submit()">
                </div>
                <div class="col-md-5">
                    <label class="form-label small fw-bold text-muted">MÉDECIN</label>
                    <select name="medecin_id" class="form-select" onchange="this.form.submit()">
                        <option value="">Tous les médecins</option>
                        <?php foreach($medecins_list as $m): ?>
                            <option value="<?= $m['id_user'] ?>" <?= $filter_medecin == $m['id_user'] ? 'selected' : '' ?>>
                                Dr. <?= htmlspecialchars($m['nom']." ".$m['prenom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <a href="suivis.php?date=<?= date('Y-m-d') ?>" class="btn btn-outline-secondary w-100" style="border-radius: 10px; font-weight: 500;">Aujourd'hui</a>
                </div>
            </form>
        </div>

        <div class="timeline-container">
            <?php if(!empty($suivis)): ?>
                <?php foreach($suivis as $s): ?>
                <div class="suivi-card">
                    <div class="suivi-date-box">
                        <span class="day"><?= date('d', strtotime($s['date_suivi'])) ?></span>
                        <span class="month"><?= date('M', strtotime($s['date_suivi'])) ?></span>
                    </div>

                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h4 class="mb-0 fw-bold" style="font-size: 19px; color: #1e293b;">
                                    <?= strtoupper($s['pat_nom']) ?> <?= $s['pat_prenom'] ?>
                                </h4>
                                <span class="text-muted small">
                                    <i class="fa-solid fa-user-doctor me-1" style="color: var(--primary);"></i> Dr. <?= $s['med_nom'] ?>
                                    | <i class="fa-regular fa-clock ms-1"></i> <?= date('H:i', strtotime($s['date_suivi'])) ?>
                                </span>
                            </div>
                            <span class="badge-status <?= strtolower($s['status']) == 'terminé' ? 'status-termine' : 'status-encours' ?>">
                                <?= $s['status'] ?: 'En attente' ?>
                            </span>
                        </div>

                        <div class="commentaire-text">
                            <strong><i class="fa-solid fa-comment-medical me-2"></i>Note :</strong><br>
                            <?= nl2br(htmlspecialchars($s['commentaire'] ?: 'Aucune observation saisie.')) ?>
                        </div>

                        <div class="d-flex gap-2">
                            <a href="dossier_patient.php?id=<?= $s['id_patient'] ?>" class="btn-action btn-folder">
                                <i class="fa-solid fa-eye"></i> Voir Dossier
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="suivi-card justify-content-center py-5" style="border-style: dashed; background: #f8fafc; opacity: 0.8;">
                    <div class="text-center">
                        <i class="fa-regular fa-calendar-xmark fa-3x mb-3" style="color: #cbd5e1;"></i>
                        <p class="text-muted">Aucun suivi trouvé pour ces critères.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

</body>
</html>