<?php
session_start();
include("../config/connexion.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'medecin') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT nom, prenom, email FROM utilisateurs WHERE id_user = ?");
$stmt->execute([$user_id]);
$medecin = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt2 = $pdo->prepare("SELECT COUNT(*) AS total_patients FROM patients WHERE id_medecin = ?");
$stmt2->execute([$user_id]);
$total_patients = $stmt2->fetch(PDO::FETCH_ASSOC)['total_patients'];

$search = $_GET['search'] ?? '';
if ($search) {
   $stmt = $pdo->prepare("SELECT * FROM patients WHERE id_medecin = ? AND (cin LIKE ? OR nom LIKE ? OR prenom LIKE ?) ORDER BY nom ASC");
   $stmt->execute([$user_id, "%$search%", "%$search%", "%$search%"]);
} else {
    $stmt = $pdo->prepare("SELECT * FROM patients WHERE id_medecin = ? ORDER BY nom ASC");
    $stmt->execute([$user_id]);
}
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>MedCare | Gestion des Patients</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
    :root {
        --primary: #0f766e; 
        --primary-light: #f0fdfa;
        --primary-hover: #115e59;
        --sidebar-bg: #0f172a;
        --bg-body: #f8fafc;
        --text-main: #1e293b;
        --text-muted: #64748b;
        --white: #ffffff;
        --border: #e2e8f0;
    }

    * { margin:0; padding:0; box-sizing:border-box; font-family: 'Inter', sans-serif; }
    body { background: var(--bg-body); color: var(--text-main); }

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
    .content { flex: 1; padding: 40px; }
    .page-header { margin-bottom: 30px; }
    .page-header h1 { font-size: 28px; font-weight: 700; color: var(--text-main); }
    .subtitle { color: var(--text-muted); font-size: 15px; }

    /* STATS */
    .stat-pill {
        display: inline-flex; align-items: center; gap: 8px;
        background: var(--white); padding: 10px 20px;
        border-radius: 50px; border: 1px solid var(--border);
        margin-bottom: 30px; font-weight: 500; font-size: 14px;
    }
    .stat-pill b { color: var(--primary); font-size: 16px; }

    /* ===== BARRE DE RECHERCHE ===== */
    .top-actions {
        display: flex; justify-content: space-between; align-items: center;
        margin-bottom: 25px; gap: 20px;
    }
    
    .search-wrapper { flex: 1; max-width: 500px; position: relative; }
    .search-wrapper form {
        display: flex; align-items: center;
        background: var(--white);
        border-radius: 14px;
        border: 1px solid var(--border);
        overflow: hidden;
        transition: all 0.3s ease;
    }
    .search-wrapper i { margin-left: 18px; color: var(--text-muted); }
    .search-wrapper input {
        border: none; padding: 14px 15px; width: 100%; outline: none;
        font-size: 14px; color: var(--text-main);
    }
    .search-wrapper button {
        background: var(--primary); color: white; border: none;
        padding: 10px 20px; margin-right: 5px; border-radius: 10px;
        cursor: pointer; font-weight: 600; font-size: 13px;
    }

    .add-btn {
        background: var(--primary); color: #fff; padding: 14px 24px;
        border-radius: 14px; text-decoration: none; font-weight: 600;
        font-size: 14px; display: flex; align-items: center; gap: 10px;
        box-shadow: 0 4px 12px rgba(15, 118, 110, 0.2); transition: 0.3s;
    }

    /* TABLE SECTION */
    .section { background: var(--white); border-radius: 20px; border: 1px solid var(--border); overflow: hidden; }
    table { width: 100%; border-collapse: collapse; }
    th { background: #fcfcfd; padding: 18px 24px; text-align: left; font-size: 12px; color: var(--text-muted); text-transform: uppercase; border-bottom: 1px solid var(--border); }
    td { padding: 18px 24px; border-bottom: 1px solid var(--border); font-size: 14px; color: var(--text-main); }
    tr:hover { background: #fafafa; }

    /* BADGES & STATUS */
    .badge-f { background: #fdf2f8; color: #be185d; padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; }
    .badge-m { background: #eff6ff; color: #1d4ed8; padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; }
    
    .blood-pill { background: #fee2e2; color: #dc2626; padding: 4px 10px; border-radius: 6px; font-weight: 800; font-size: 12px; border: 1px solid #fecaca; }
    
    .status-pill { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
    .st-stable { background: #dcfce7; color: #15803d; }
    .st-urgent { background: #fef2f2; color: #dc2626; }
    .st-obs { background: #fef9c3; color: #a16207; }

    /* ACTIONS */
    .btn-group { display: flex; gap: 10px; }
    .btn-action { height: 38px; padding: 0 15px; border-radius: 10px; text-decoration: none; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; }
    .btn-dossier { background: var(--primary-light); color: var(--primary); }
    .btn-delete { background: #fff1f2; color: #e11d48; }

    @media(max-width:900px){ .sidebar { display:none; } }
</style>
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
        <a href="patients.php" class="active"><i class="fa-solid fa-user-group"></i> Mes Patients</a>
        <a href="suivis.php"><i class="fa-solid fa-file-medical"></i> Consultations</a>
        <a href="../traitement/list.php"><i class="fa-solid fa-pills"></i> Traitements</a>
        <a href="rendezvous.php"><i class="fa-solid fa-calendar-check"></i> Rendez-vous</a>
        <div style="height: 1px; background: rgba(255,255,255,0.1); margin: 20px 0;"></div>
        <a href="profil_medcin.php"><i class="fa-solid fa-user"></i> Profil</a>
        <a href="deconnexion.php" style="color: #fda4af;"><i class="fa-solid fa-power-off"></i> Déconnexion</a>
    </aside>

    <main class="content">
        <div class="page-header">
            <h1>Répertoire des Patients</h1>
            <p class="subtitle">Consultez et gérez les informations médicales de vos patients.</p>
        </div>

        <div class="stat-pill">
            <i class="fa-solid fa-users-viewfinder"></i>
            Total : <b><?= $total_patients ?></b> patients enregistrés
        </div>

        <div class="top-actions">
            <div class="search-wrapper">
                <form method="GET">
                    <i class="fa-solid fa-search"></i>
                    <input type="text" name="search" placeholder="Rechercher par nom, prénom ou CIN..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit">Rechercher</button>
                </form>
            </div>
            <a href="ajouter_patient.php" class="add-btn">
                <i class="fa-solid fa-plus-circle"></i> Nouveau Patient
            </a>
        </div>

        <div class="section">
            <table>
                <thead>
                    <tr>
                        <th>Identité du Patient</th>
                        <th>Sexe</th>
                        <th>Groupe</th>
                        <th>Statut</th>
                        <th>Email / Contact</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($patients): ?>
                        <?php foreach($patients as $p): ?>
                        <tr>
                            <td>
                                <div style="font-weight: 700;"><?= htmlspecialchars($p['nom']." ".$p['prenom']) ?></div>
                                <div style="font-size: 11px; color: var(--text-muted);">CIN : <?= htmlspecialchars($p['cin'] ?? 'N/A') ?></div>
                            </td>
                            <td>
                                <?php if(strtoupper($p['sexe']) == 'F'): ?>
                                    <span class="badge-f"><i class="fa-solid fa-venus"></i> Femme</span>
                                <?php else: ?>
                                    <span class="badge-m"><i class="fa-solid fa-mars"></i> Homme</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="blood-pill"><?= htmlspecialchars($p['groupe_sanguin'] ?: 'N/A') ?></span>
                            </td>
                            <td>
                                <?php 
                                    $st_class = 'st-stable';
                                    $st_text = $p['statut'] ?: 'Stable';
                                    if(strpos(strtolower($st_text), 'urgent') !== false) $st_class = 'st-urgent';
                                    if(strpos(strtolower($st_text), 'obs') !== false) $st_class = 'st-obs';
                                ?>
                                <span class="status-pill <?= $st_class ?>"><?= htmlspecialchars($st_text) ?></span>
                            </td>
                            <td style="color: var(--text-muted); font-size: 13px;">
                                <i class="fa-regular fa-envelope" style="margin-right: 5px;"></i><?= htmlspecialchars($p['email']) ?>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="dossier_patient.php?id=<?= $p['id_patient'] ?>" class="btn-action btn-dossier">
                                        <i class="fa-solid fa-file-waveform"></i> Dossier
                                    </a>
                                    <a href="supprimer_patient.php?id=<?= $p['id_patient'] ?>" class="btn-action btn-delete" 
                                       onclick="return confirm('Confirmez-vous la suppression de ce patient ?')">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align:center; padding: 60px; color: var(--text-muted);">
                                <i class="fa-solid fa-folder-open" style="font-size: 30px; display: block; margin-bottom: 10px; opacity: 0.3;"></i>
                                Aucun patient trouvé.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

</body>
</html>