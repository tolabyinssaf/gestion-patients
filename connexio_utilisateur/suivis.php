<?php
session_start();
include("../config/connexion.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'medecin') {
    header("Location: login.php");
    exit;
}

$id_medecin = $_SESSION['user_id'];

/* ===== RÉCUPÉRER LES SUIVIS ===== */
$stmt = $pdo->prepare("
    SELECT 
        s.id_suivi,
        s.date_suivi,
        s.commentaire,
        s.status,
        p.id_patient,
        p.nom,
        p.prenom
    FROM suivis s
    JOIN patients p ON s.id_patient = p.id_patient
    WHERE p.id_medecin = ?
    ORDER BY s.date_suivi DESC
");
$stmt->execute([$id_medecin]);
$suivis = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ===== STATUT AUTOMATIQUE ===== */
$today = date('Y-m-d');
foreach ($suivis as $key => $s) {
    if ($s['date_suivi'] <= $today && $s['status'] !== 'Terminé') {
        $upd = $pdo->prepare("UPDATE suivis SET status='Terminé' WHERE id_suivi=?");
        $upd->execute([$s['id_suivi']]);
        $suivis[$key]['status'] = 'Terminé';
    }
}

/* ===== INFOS MÉDECIN ===== */
$stmtMed = $pdo->prepare("SELECT nom, prenom FROM utilisateurs WHERE id_user=?");
$stmtMed->execute([$id_medecin]);
$medecin = $stmtMed->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Suivis | MedCare Pro</title>
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

    /* ===== HEADER (CONSISTANT) ===== */
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

    /* ===== SIDEBAR (CONSISTANT) ===== */
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
    
    /* TITRE AMELIORE */
    .page-header { 
        margin-bottom: 35px; 
        padding-bottom: 20px;
        border-bottom: 2px solid var(--primary-light);
        display: flex;
        align-items: center;
        gap: 20px;
    }
    .page-icon {
        width: 60px; height: 60px;
        background: var(--primary);
        color: white;
        border-radius: 16px;
        display: flex; align-items: center; justify-content: center;
        font-size: 24px;
        box-shadow: 0 4px 12px rgba(15, 118, 110, 0.2);
    }
    .page-header h1 { font-size: 30px; font-weight: 800; color: var(--text-main); letter-spacing: -0.5px; }
    .subtitle { color: var(--text-muted); font-size: 15px; margin-top: 2px; }

    /* ===== SUIVIS DESIGN (SANS ANIMATION) ===== */
    .timeline { position: relative; max-width: 950px; }

    .suivi-card {
        background: var(--white);
        border-radius: 16px;
        border: 1px solid var(--border);
        padding: 25px;
        margin-bottom: 15px;
        display: flex;
        gap: 25px;
    }

    /* Date Box */
    .suivi-date-box {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: #f1f5f9;
        min-width: 90px;
        height: 90px;
        border-radius: 12px;
        color: var(--text-main);
        border: 1px solid var(--border);
    }
    .suivi-date-box .day { font-size: 24px; font-weight: 800; color: var(--primary); }
    .suivi-date-box .month { font-size: 12px; text-transform: uppercase; font-weight: 700; color: var(--text-muted); }

    .suivi-body { flex: 1; }

    .patient-name {
        font-size: 19px;
        font-weight: 700;
        color: var(--text-main);
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .commentaire-text {
        color: var(--text-main);
        line-height: 1.6;
        font-size: 14px;
        background: var(--primary-light);
        padding: 18px;
        border-radius: 12px;
        margin: 15px 0;
        border-left: 5px solid var(--primary);
    }

    /* Badges */
    .badge-status {
        font-size: 11px;
        padding: 6px 14px;
        border-radius: 50px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .status-termine { background: #fee2e2; color: #dc2626; }
    .status-encours { background: #dcfce7; color: #16a34a; }

    /* Buttons */
    .actions-group { display: flex; gap: 12px; margin-top: 10px; }
    .btn-action {
        padding: 10px 18px;
        border-radius: 10px;
        text-decoration: none;
        font-size: 13px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border: 1px solid var(--border);
        background: var(--white);
        color: var(--text-main);
        transition: 0.2s;
    }
    .btn-action:hover { background: #f8fafc; border-color: var(--primary); color: var(--primary); }
    
    .btn-folder { background: var(--primary); color: white; border: none; }
    .btn-folder:hover { background: var(--primary-hover); color: white; }

    .btn-del { color: #e11d48; }
    .btn-del:hover { background: #fff1f2; border-color: #e11d48; color: #e11d48; }

    @media(max-width:900px){ .sidebar { display:none; } }
</style>
</head>

<body>

<header>
    <img src="../images/logo_app2.png" alt="Logo" class="logo">
    <div class="user-pill">
        <i class="fa-solid fa-user-doctor"></i>
        <span>Dr. <?= htmlspecialchars($medecin['prenom']." ".$medecin['nom']) ?></span>
    </div>
</header>

<div class="container">
    <aside class="sidebar">
        <h3>Menu Médical</h3>
        <a href="dashboard_medecin.php"><i class="fa-solid fa-chart-pie"></i> Tableau de bord</a>
        <a href="patients.php"><i class="fa-solid fa-user-group"></i> Mes Patients</a>
        <a href="suivis.php" class="active"><i class="fa-solid fa-file-medical"></i> Consultations</a>
        <a href="../traitement/list.php"><i class="fa-solid fa-pills"></i> Traitements</a>
        <a href="rendezvous.php"><i class="fa-solid fa-calendar-check"></i> Rendez-vous</a>
        <div style="height: 1px; background: rgba(255,255,255,0.1); margin: 20px 0;"></div>
        <a href="profil_medcin.php"><i class="fa-solid fa-user"></i> Profil</a>
        <a href="deconnexion.php" style="color: #fda4af;"><i class="fa-solid fa-power-off"></i> Déconnexion</a>
    </aside>

    <main class="content">
        <div class="page-header">
            <div class="page-icon">
                <i class="fa-solid fa-clipboard-list"></i>
            </div>
            <div>
                <h1>Journal des Suivis</h1>
                <p class="subtitle">Historique médical consolidé et statuts des consultations.</p>
            </div>
        </div>

        <div class="timeline">
            <?php if($suivis): ?>
                <?php foreach($suivis as $s): 
                    $dateObj = new DateTime($s['date_suivi']);
                    $jour = $dateObj->format('d');
                    $mois = $dateObj->format('M');
                ?>
                <div class="suivi-card">
                    <div class="suivi-date-box">
                        <span class="day"><?= $jour ?></span>
                        <span class="month"><?= $mois ?></span>
                    </div>

                    <div class="suivi-body">
                        <div style="display: flex; justify-content: space-between; align-items: start;">
                            <div class="patient-name">
                                <?= htmlspecialchars($s['prenom'].' '.$s['nom']) ?>
                            </div>
                            <?php if($s['status'] === 'Terminé'): ?>
                                <span class="badge-status status-termine">Suivi Terminé</span>
                            <?php else: ?>
                                <span class="badge-status status-encours">Suivi Actif</span>
                            <?php endif; ?>
                        </div>

                        <div class="commentaire-text">
                            <strong><i class="fa-solid fa-quote-left" style="opacity: 0.3; margin-right: 8px;"></i> Observations :</strong><br>
                            <?= nl2br(htmlspecialchars($s['commentaire'])) ?>
                        </div>

                        <div class="actions-group">
                            <a href="modifier_suivi.php?id=<?= $s['id_suivi'] ?>" class="btn-action">
                                <i class="fa-solid fa-pen"></i> Modifier
                            </a>
                            <a href="dossier_patient.php?id=<?= $s['id_patient'] ?>" class="btn-action btn-folder">
                                <i class="fa-solid fa-eye"></i> Ouvrir le Dossier
                            </a>
                            <?php if($s['status'] === 'Terminé'): ?>
                                <a href="supprimer_suivi.php?id=<?= $s['id_suivi'] ?>" 
                                   class="btn-action btn-del"
                                   onclick="return confirm('Supprimer ce compte-rendu définitivement ?')">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="suivi-card" style="justify-content: center; padding: 50px; border-style: dashed; background: transparent;">
                    <div style="text-align: center;">
                        <i class="fa-solid fa-notes-medical" style="font-size: 40px; color: var(--border); margin-bottom: 15px;"></i>
                        <p style="color: var(--text-muted);">Aucun suivi n'a été enregistré dans la base.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

</body>
</html>