<?php
session_start();
include("../config/connexion.php");

// 1. SÉCURITÉ : Vérifier que l'utilisateur est un infirmier
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'infirmier') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// 2. RÉCUPÉRATION INFOS INFIRMIER (NOM ET PRÉNOM)
$stmt = $pdo->prepare("SELECT nom, prenom FROM utilisateurs WHERE id_user = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// On prépare le nom complet pour l'affichage
$nom_complet = htmlspecialchars($user['prenom'] . " " . $user['nom']);

// 3. STATISTIQUES INFIRMIER
// Nombre de patients hospitalisés (admissions sans date de sortie)
$count_hospitalises = $pdo->query("SELECT COUNT(*) FROM admissions WHERE date_sortie IS NULL")->fetchColumn();

// Nombre de soins effectués par cet infirmier aujourd'hui
$stmt_soins = $pdo->prepare("SELECT COUNT(*) FROM soins_patients WHERE id_infirmier = ? AND DATE(date_soin) = CURDATE()");
$stmt_soins->execute([$user_id]);
$count_soins = $stmt_soins->fetchColumn();

// 4. LISTE DES PATIENTS ACTUELS
$sql_patients = "SELECT a.id_admission, p.nom, p.prenom, p.cin, a.date_admission 
                 FROM admissions a 
                 JOIN patients p ON a.id_patient = p.id_patient 
                 WHERE a.date_sortie IS NULL 
                 ORDER BY a.date_admission DESC";
$patients_actifs = $pdo->query($sql_patients)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>MedCare | Espace Infirmier</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #0f766e; 
            --primary-light: #f0fdfa;
            --sidebar-bg: #0f172a;
            --bg-body: #f8fafc;
            --border: #e2e8f0;
            --white: #ffffff;
            --header-height: 75px;
            --sidebar-width: 260px;
        }

        * { margin:0; padding:0; box-sizing:border-box; font-family: 'Inter', sans-serif; }
        body { background: var(--bg-body); color: #1e293b; overflow-x: hidden; }

        /* HEADER (STYLE SECRÉTAIRE) */
        header {
            background: var(--white);
            padding: 0 40px; 
            height: var(--header-height);
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid var(--border);
            position: fixed; top: 0; left: 0; right: 0; z-index: 1000;
        }
        .logo { height: 45px; }

        /* SIDEBAR (STYLE SECRÉTAIRE) */
        .sidebar { 
            width: var(--sidebar-width); 
            background: var(--sidebar-bg); 
            padding: 24px 16px; 
            position: fixed; top: var(--header-height); left: 0; bottom: 0; 
            z-index: 999;
            overflow-y: auto;
        }

        .content { 
            margin-left: var(--sidebar-width); 
            margin-top: var(--header-height); 
            padding: 40px; 
            min-height: calc(100vh - var(--header-height));
        }

        .user-pill { 
            background: var(--primary-light); 
            padding: 8px 18px; 
            border-radius: 12px; 
            display: flex; align-items: center; gap: 10px; 
            font-size: 14px; font-weight: 600; color: var(--primary); 
        }

        .sidebar a { display: flex; align-items: center; gap: 12px; color: #94a3b8; text-decoration: none; padding: 12px 16px; border-radius: 10px; margin-bottom: 5px; }
        .sidebar a.active { background: var(--primary); color: #fff; }
        
        /* CARDS & TABLES */
        .mega-card { background: var(--white); padding: 25px; border-radius: 20px; display: flex; align-items: center; gap: 20px; border: 1px solid var(--border); }
        .icon-circle { width: 60px; height: 60px; border-radius: 15px; display: flex; align-items: center; justify-content: center; font-size: 24px; }
        .bg-hosp { background: #fff7ed; color: #ea580c; }
        .bg-care { background: #f0fdf4; color: #10b981; }
        
        .section-box { background: var(--white); padding: 25px; border-radius: 20px; border: 1px solid var(--border); margin-bottom: 30px; }
        table { width:100%; border-collapse:collapse; }
        th { text-align:left; padding:12px; background:#f8fafc; color:#64748b; font-size:11px; text-transform:uppercase; }
        td { padding:15px 12px; border-bottom:1px solid #f1f5f9; font-size:14px; }
    </style>
</head>
<body>

<header>
    <img src="../images/logo_app2.png" alt="Logo" class="logo">
    <div class="user-pill">
        <i class="fa-solid fa-user-nurse"></i>
        <span>Inf. <?= $nom_complet ?></span>
    </div>
</header>

<div class="wrapper">
    <aside class="sidebar">
        <h3 style="color:rgba(255,255,255,0.3); font-size:11px; text-transform:uppercase; margin-bottom:20px; padding-left:12px;">Menu Infirmier</h3>
        <a href="dashboard_infirmier.php" class="active"><i class="fa-solid fa-chart-line"></i> Tableau de bord</a>
        <a href="liste_patients_inf.php"><i class="fa-solid fa-user-injured"></i> Liste des Patients</a>
        <a href="saisir_soins.php"><i class="fa-solid fa-notes-medical"></i> Saisir un Soin</a>
        <a href="planning.php"><i class="fa-solid fa-calendar-alt"></i> Planning</a>
        <div style="height: 1px; background: rgba(255,255,255,0.1); margin: 20px 0;"></div>
        <a href="../connexio_utilisateur/deconnexion.php" style="color: #fda4af;"><i class="fa-solid fa-power-off"></i> Déconnexion</a>
    </aside>

    <main class="content">
        <div class="mb-4">
            <h1 class="h2 fw-bold mb-1">Ravi de vous revoir 👋</h1>
            <p class="text-muted">Bonjour <strong><?= $nom_complet ?></strong>, voici l'état des hospitalisations.</p>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-md-6 col-lg-4">
                <div class="mega-card shadow-sm">
                    <div class="icon-circle bg-hosp"><i class="fa-solid fa-bed-pulse"></i></div>
                    <div>
                        <p class="text-muted small mb-0 fw-bold text-uppercase">Patients Hospitalisés</p>
                        <div class="h3 fw-bold mb-0"><?= $count_hospitalises ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="mega-card shadow-sm">
                    <div class="icon-circle bg-care"><i class="fa-solid fa-syringe"></i></div>
                    <div>
                        <p class="text-muted small mb-0 fw-bold text-uppercase">Soins (Aujourd'hui)</p>
                        <div class="h3 fw-bold mb-0"><?= $count_soins ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="section-box shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-bold mb-0"><i class="fa-solid fa-list-check me-2 text-primary"></i>Patients en attente de soins</h5>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Admission</th>
                            <th>Patient</th>
                            <th>CIN</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($patients_actifs): ?>
                            <?php foreach($patients_actifs as $p): ?>
                            <tr>
                                <td class="fw-bold text-primary"><?= date('d/m H:i', strtotime($p['date_admission'])) ?></td>
                                <td class="fw-semibold"><?= htmlspecialchars($p['nom'].' '.$p['prenom']) ?></td>
                                <td><span class="badge bg-light text-dark border"><?= $p['cin'] ?></span></td>
                                <td class="text-end">
                                    <a href="saisir_soins.php?id_adm=<?= $p['id_admission'] ?>" class="btn btn-sm btn-primary rounded-pill px-3">
                                        <i class="fa-solid fa-plus-circle me-1"></i> Saisir Soin
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center py-5 text-muted">Aucun patient hospitalisé actuellement.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

</body>
</html>