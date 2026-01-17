<?php
session_start();
include("../config/connexion.php");

// Vérifier que l'utilisateur est connecté et est médecin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'medecin') {
    header("Location: login.php");
    exit;
}

// Infos médecin
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT nom, prenom, email, telephone FROM utilisateurs WHERE id_user = ?");
$stmt->execute([$user_id]);
$medecin = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Profil Médecin | MedCare</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
       :root {
        --primary: #0f766e; /* Le vert que vous avez choisi */
        --primary-light: #f0fdfa;
        --primary-hover: #115e59;
        --sidebar-bg: #0f172a;
        --bg-body: #f8fafc;
        --text-main: #1e293b;
        --text-muted: #64748b;
        --white: #ffffff;
        --border: #e2e8f0;
    }

    *{margin:0;padding:0;box-sizing:border-box;font-family:"Inter", "Segoe UI", sans-serif;}
    body{background: var(--bg-body); color: var(--text-dark);}

    /* ===== HEADER ===== */
    header {
        background: var(--white);
        padding: 0 40px;
        height: 75px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid var(--border);
        position: sticky; top: 0; z-index: 100;
    }
    .logo { height: 45px; }
    .user-pill {
        background: var(--primary-light);
        padding: 8px 18px;
        border-radius: 12px;
        display: flex; align-items: center; gap: 10px;
        font-size: 14px; font-weight: 600; color: var(--primary);
        border: 1px solid rgba(15, 118, 110, 0.1);
    }

    /* ===== LAYOUT ===== */
    .container { display: flex; min-height: calc(100vh - 75px); }

    /* ===== SIDEBAR ===== */
    .sidebar { width: 260px; background: var(--sidebar-bg); padding: 24px 16px; flex-shrink: 0; }
    .sidebar h3 {
        color: rgba(255,255,255,0.3); font-size: 11px; 
        text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 20px; padding-left: 12px;
    }
    .sidebar a {
        display: flex; align-items: center; gap: 12px;
        color: #94a3b8; text-decoration: none;
        padding: 12px 16px; border-radius: 10px;
        margin-bottom: 5px; transition: 0.2s;
    }
    .sidebar a:hover { background: rgba(255,255,255,0.05); color: #fff; }
    .sidebar a.active { background: var(--primary); color: #fff; }


    /* ===== CONTENT ===== */
    .content{ flex:1; padding: 40px; max-width: 1200px; margin: 0 auto; }
    
    .page-header { margin-bottom: 30px; }
    .page-header h1 { font-size: 24px; color: var(--text-dark); }

    /* ===== PROFILE DASHBOARD ===== */
    .profile-grid {
        display: grid;
        grid-template-columns: 350px 1fr;
        gap: 30px;
    }

    .card {
        background: white;
        border-radius: 16px;
        padding: 30px;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        border: 1px solid #e2e8f0;
    }

    /* Left Card: Summary */
    .summary-card { text-align: center; }
    .avatar-wrapper {
        position: relative;
        width: 130px;
        height: 130px;
        margin: 0 auto 20px;
    }
    .avatar-wrapper img {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
        border: 5px solid #f0fdfa;
    }
    .status-badge {
        position: absolute;
        bottom: 5px;
        right: 5px;
        width: 18px;
        height: 18px;
        background: #22c55e;
        border: 3px solid white;
        border-radius: 50%;
    }

    .summary-card h2 { font-size: 22px; margin-bottom: 5px; }
    .summary-card .role { color: var(--text-light); font-size: 14px; margin-bottom: 20px; }

    .btn-edit {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: var(--primary);
        color: white;
        padding: 10px 24px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        transition: 0.2s;
        width: 100%;
        justify-content: center;
    }
    .btn-edit:hover { background: var(--primary-dark); }

    /* Right Card: Details */
    .details-card h3 {
        font-size: 18px;
        margin-bottom: 25px;
        padding-bottom: 10px;
        border-bottom: 1px solid #f1f5f9;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 20px;
    }

    .info-item {
        background: #f1f6fbff;
        padding: 20px;
        border-radius: 12px;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .info-item .label {
        font-size: 12px;
        text-transform: uppercase;
        color: var(--text-light);
        letter-spacing: 0.5px;
        font-weight: 600;
    }

    .info-item .value {
        font-size: 16px;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .info-item i { color: var(--primary); font-size: 18px; }

    /* ===== RESPONSIVE ===== */
    @media(max-width: 1024px){
        .profile-grid { grid-template-columns: 1fr; }
        .sidebar { width: 80px; }
        .sidebar h3, .sidebar span { display: none; }
        .sidebar a { justify-content: center; padding: 15px; }
    }
</style>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

</head>
<body>

<header>
    <img src="../images/logo_app2.png" alt="Logo" class="logo">
    <div class="user-pill">
        <i class="fa-solid fa-user-md"></i>
        <span>Dr. <?= htmlspecialchars($medecin['prenom']." ".$medecin['nom']) ?></span>
    </div>
</header>

<div class="container">
    <aside class="sidebar">
        <h3>Menu Médical</h3>
        <a href="dashboard_medecin.php"><i class="fa-solid fa-chart-pie"></i> Tableau de bord</a>
        <a href="patients.php" ><i class="fa-solid fa-user-group"></i> Mes Patients</a>
        <a href="suivis.php"><i class="fa-solid fa-file-medical"></i> Consultations</a>
        <a href="../traitement/list.php"><i class="fa-solid fa-pills"></i> Traitements</a>
        <a href="rendezvous.php"><i class="fa-solid fa-calendar-check"></i> Rendez-vous</a>
        <div style="height: 1px; background: rgba(255,255,255,0.1); margin: 20px 0;"></div>
        <a href="profil_medcin.php" class="active"><i class="fa-solid fa-user"></i> Profil</a>
        <a href="deconnexion.php" style="color: #fda4af;"><i class="fa-solid fa-power-off"></i> Déconnexion</a>
    </aside>

    <main class="content">
        <div class="page-header">
            <h1>Paramètres du compte</h1>
        </div>

        <div class="profile-grid">
            <div class="card summary-card">
                <div class="avatar-wrapper">
                    <img src="https://cdn-icons-png.flaticon.com/512/194/194938.png" alt="Avatar">
                    <div class="status-badge"></div>
                </div>
                <h2>Dr. <?= htmlspecialchars($medecin['prenom']." ".$medecin['nom']) ?></h2>
                <p class="role">Médecin Spécialiste certifié</p>
                <a href="modifier_profil.php" class="btn-edit">
                    <i class="bi bi-pencil-square"></i> Modifier mon profil
                </a>
            </div>

            <div class="card details-card">
                <h3>Informations personnelles</h3>
                
                <div class="info-grid">
                    <div class="info-item">
                        <span class="label">Nom de famille</span>
                        <div class="value"><i class="bi bi-person"></i> <?= htmlspecialchars($medecin['nom']) ?></div>
                    </div>

                    <div class="info-item">
                        <span class="label">Prénom</span>
                        <div class="value"><i class="bi bi-person"></i> <?= htmlspecialchars($medecin['prenom']) ?></div>
                    </div>

                    <div class="info-item">
                        <span class="label">Adresse Email</span>
                        <div class="value"><i class="bi bi-envelope-at"></i> <?= htmlspecialchars($medecin['email']) ?></div>
                    </div>

                    <div class="info-item">
                        <span class="label">Téléphone</span>
                        <div class="value"><i class="bi bi-telephone-plus"></i> <?= htmlspecialchars($medecin['telephone']) ?></div>
                    </div>
                </div>

                <div style="margin-top: 40px; padding: 20px; background: #fffbeb; border-radius: 12px; border: 1px solid #fef3c7;">
                    <p style="font-size: 13px; color: #92400e;">
                        <i class="bi bi-shield-lock-fill"></i> Vos données sont sécurisées et ne sont visibles que par l'administration de l'établissement.
                    </p>
                </div>
            </div>
        </div>
    </main>
</div>

</body>
</html>