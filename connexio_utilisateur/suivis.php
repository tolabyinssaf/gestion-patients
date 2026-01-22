<?php
session_start();
include("../config/connexion.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'medecin') {
    header("Location: login.php");
    exit;
}

$id_medecin = $_SESSION['user_id'];

// --- 1. LOGIQUE DE MISE À JOUR EN TEMPS RÉEL (AJAX) ---
if (isset($_GET['action']) && $_GET['action'] === 'fetch_status') {
    $today = date('Y-m-d');
    $pdo->query("UPDATE suivis SET status='Terminé' WHERE DATE(date_suivi) <= '$today' AND status != 'Terminé'");

    $stmt = $pdo->prepare("SELECT id_suivi, status FROM suivis s JOIN patients p ON s.id_patient = p.id_patient WHERE p.id_medecin = ?");
    $stmt->execute([$id_medecin]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($results);
    exit;
}

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
    ORDER BY CASE WHEN s.status = 'Terminé' THEN 1 ELSE 0 END ASC, 
        s.date_suivi DESC
");
$stmt->execute([$id_medecin]);
$suivis = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ===== STATUT AUTOMATIQUE ===== */
$today = date('Y-m-d');
foreach ($suivis as $key => $s) {
    $dateOnly = date('Y-m-d', strtotime($s['date_suivi']));
    if ($dateOnly <= $today && $s['status'] !== 'Terminé') {
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

    header {
        background: var(--white);
        padding: 0 40px;
        height: 75px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid var(--border);
        position: fixed;
        top: 0; left: 0; right: 0; 
        z-index: 1000;
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

    .container { display: flex; padding-top: 75px; }

    .sidebar { 
        width: 260px; 
        background: var(--sidebar-bg); 
        padding: 24px 16px; 
        position: fixed; 
        top: 75px; 
        left: 0; 
        bottom: 0; 
        overflow-y: auto; 
        z-index: 900;
    }
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

    .content { 
        flex: 1; 
        padding: 40px; 
        margin-left: 260px;
        margin-right: 320px; 
    }
    
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

    @media(max-width:1200px){ 
        .content { margin-right: 0; }
        .timeline-right { display: none; }
    }
    @media(max-width:900px){ 
        .sidebar { display:none; } 
        .content { margin-left: 0; }
    }
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
        <h3 style="font-weight: 800;">Unité de Soins</h3>
        <a href="dashboard_medecin.php"><i class="fa-solid fa-chart-line"></i> Vue Générale</a>
        <a href="hospitalisation.php"><i class="fa-solid fa-bed-pulse"></i> Patients Admis</a>
        <a href="patients.php"><i class="fa-solid fa-hospital-user"></i> Patients</a>
        <a href="../traitement/list.php"><i class="fa-solid fa-file-prescription"></i> Traitements</a>
        <a href="suivis.php" class="active"><i class="fa-solid fa-calendar-check"></i> Consultations</a>
        <h3 style="font-weight: 800;">Analyse & Gestion</h3>
        <a href="../admission/statistique.php"><i class="fa-solid fa-chart-pie"></i> Statistiques</a>
        <a href="archives.php"><i class="fa-solid fa-box-archive"></i> Archives</a>
        <a href="profil_medcin.php"><i class="fa-solid fa-user-gear"></i> Profil</a>
        <div style="margin-top: 40px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 10px;">
            <a href="deconnexion.php" style="color: #fda4af;"><i class="fa-solid fa-power-off"></i> Déconnexion</a>
        </div>
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
        <div style="margin-bottom: 25px;">
    <input type="text" id="searchInput" placeholder="Rechercher par nom ou prénom..." 
        style="width: 100%; padding: 12px 18px; border-radius: 12px; border: 1px solid #e2e8f0; font-size: 14px;">
</div>

        <?php if($suivis): ?>
            <?php foreach($suivis as $s): 
                $dateObj = new DateTime($s['date_suivi']);
                $jour = $dateObj->format('d');
                $mois = $dateObj->format('M');
            ?>
            <div class="suivi-card" data-id="<?= $s['id_suivi'] ?>">
                <div class="suivi-date-box">
                    <span class="day"><?= $jour ?></span>
                    <span class="month"><?= $mois ?></span>
                </div>

                <div class="suivi-body">
                    <div style="display: flex; justify-content: space-between; align-items: start;">
                        <div class="patient-name">
                            <?= htmlspecialchars($s['prenom'].' '.$s['nom']) ?>
                        </div>
                        <div class="status-container">
                            <?php if($s['status'] === 'Terminé'): ?>
                                <span class="badge-status status-termine">Suivi Terminé</span>
                            <?php else: ?>
                                <span class="badge-status status-encours">Suivi Actif</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="commentaire-text">
                        <strong><i class="fa-solid fa-quote-left" style="opacity: 0.3; margin-right: 8px;"></i> Observations :</strong><br>
                        <?= nl2br(htmlspecialchars($s['commentaire'])) ?>
                    </div>

                    <div class="actions-group">
                        <div class="edit-container">
                            <?php if($s['status'] !== 'Terminé'): ?>
                                <a href="modifier_suivi.php?id=<?= $s['id_suivi'] ?>" class="btn-action">
                                    <i class="fa-solid fa-pen"></i> Modifier
                                </a>
                                <a href="#" class="btn-action btn-print" onclick="printSuivi(<?= $s['id_suivi'] ?>); return false;">
                <i class="fa-solid fa-print"></i> Imprimer
            </a>
                            <?php endif; ?>
                        </div>

                        <a href="dossier_patient.php?id=<?= $s['id_patient'] ?>" class="btn-action btn-folder">
                            <i class="fa-solid fa-eye"></i> Ouvrir le Dossier
                        </a>
                        <div class="delete-container">
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

<div class="timeline-right" style="
    position: fixed;
    right: 0;
    top: 75px;
    bottom: 0;
    width: 320px;
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    border-left: 1px solid #e2e8f0;
    padding: 25px;
    overflow-y: auto;
    z-index: 800;
">
    <div style="margin-bottom: 30px;">
        <h3 style="color: var(--primary); font-size: 18px; margin-bottom: 5px; display: flex; align-items: center; gap: 10px;">
            <i class="fa-solid fa-timeline"></i> Chronologie
        </h3>
        <p style="color: var(--text-muted); font-size: 13px;">Dernières activités</p>
    </div>
    
    <div style="position: relative; padding-left: 20px;">
        <div style="position: absolute; left: 9px; top: 0; bottom: 0; width: 2px; background: #e2e8f0;"></div>
        
        <?php 
        $recentSuivis = array_slice($suivis, 0, 8);
        foreach($recentSuivis as $s): 
            $dateObj = new DateTime($s['date_suivi']);
        ?>
        <div style="position: relative; margin-bottom: 20px;" class="side-item" data-id="<?= $s['id_suivi'] ?>">
            <div class="side-dot" style="
                position: absolute;
                left: -23px;
                top: 5px;
                width: 16px;
                height: 16px;
                border-radius: 50%;
                background: <?= $s['status'] === 'Terminé' ? '#10b981' : 'var(--primary)' ?>;
                border: 3px solid white;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            "></div>
            
            <div style="background: white; padding: 15px; border-radius: 10px; border: 1px solid #e2e8f0;">
                <div style="font-weight: 600; font-size: 14px; color: var(--text-main); margin-bottom: 5px;">
                    <?= $s['prenom'] . ' ' . $s['nom'] ?>
                </div>
                <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 8px;">
                    <?= $dateObj->format('d M, H:i') ?>
                </div>
                <div style="font-size: 13px; color: #475569; line-height: 1.4;">
                    <?= mb_strimwidth($s['commentaire'], 0, 80, "...") ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <div style="margin-top: 30px; padding: 15px; background: #f0fdfa; border-radius: 10px; border-left: 4px solid var(--primary);">
        <div style="font-size: 13px; color: var(--text-main);">
            <i class="fa-solid fa-lightbulb"></i> <strong>Astuce :</strong> Les statuts se mettent à jour automatiquement.
        </div>
    </div>
</div>

</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function updateStatuses() {
    $.ajax({
        url: 'suivis.php?action=fetch_status',
        method: 'GET',
        dataType: 'json',
        success: function(data) {
            data.forEach(function(item) {
                const card = $(`.suivi-card[data-id="${item.id_suivi}"]`);
                if (card.length) {
                    const statusContainer = card.find('.status-container');
                    const editContainer = card.find('.edit-container');
                    const deleteContainer = card.find('.delete-container');
                    
                    if (item.status === 'Terminé') {
                        statusContainer.html('<span class="badge-status status-termine">Suivi Terminé</span>');
                        
                        // ON VIDE LE CONTENEUR DU BOUTON MODIFIER
                        editContainer.empty();

                        if (deleteContainer.is(':empty')) {
                            deleteContainer.html(`
                                <a href="supprimer_suivi.php?id=${item.id_suivi}" 
                                   class="btn-action btn-del"
                                   onclick="return confirm('Supprimer ce compte-rendu définitivement ?')">
                                     <i class="fa-solid fa-trash"></i>
                                </a>
                            `);
                        }
                    } else {
                        statusContainer.html('<span class="badge-status status-encours">Suivi Actif</span>');
                    }
                }

                const sideItem = $(`.side-item[data-id="${item.id_suivi}"]`);
                if (sideItem.length) {
                    const dot = sideItem.find('.side-dot');
                    dot.css('background', item.status === 'Terminé' ? '#10b981' : '#0f766e');
                }
            });
        }
    });
}

// FILTRAGE DES CARTES PAR NOM OU PRÉNOM
$('#searchInput').on('keyup', function() {
    const query = $(this).val().toLowerCase();

    $('.suivi-card').each(function() {
        const fullName = $(this).find('.patient-name').text().toLowerCase();
        if (fullName.includes(query)) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });

    // Optionnel : filtrer aussi la chronologie côté droit
    $('.side-item').each(function() {
        const sideName = $(this).find('div:first').text().toLowerCase();
        if (sideName.includes(query)) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });
});


function printSuivi(id) {
    const card = document.querySelector(`.suivi-card[data-id="${id}"]`);
    if (!card) return;

    // Infos patient
    const patientName = card.querySelector('.patient-name').innerText;
    const commentaire = card.querySelector('.commentaire-text').innerHTML;

    // Infos médecin depuis PHP
    const medecinNom = "Dr. <?= htmlspecialchars($medecin['prenom']." ".$medecin['nom']) ?>";
    const medecinSpecialite = "<?= htmlspecialchars($medecin['specialite'] ?? 'Médecin généraliste') ?>";

    // Date complète du suivi depuis la carte
    const dateSuivi = card.querySelector('.suivi-date-box .day').innerText + " " +
                      card.querySelector('.suivi-date-box .month').innerText + ", " +
                      new Date().getFullYear();

    // Fenêtre d'impression
    const printWindow = window.open('', '', 'width=800,height=600');
    printWindow.document.write(`
        <html>
        <head>
            <title>Imprimer Suivi</title>
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
            <style>
                body { 
                    font-family: 'Inter', sans-serif; 
                    padding: 40px; 
                    background: #fff; 
                    color: #111; 
                    text-align: center; /* Tout centré */
                }
                .print-header { 
                    margin-bottom: 30px; 
                    border-bottom: 2px solid #0f766e; 
                    padding-bottom: 20px; 
                }
                .print-header h2 { 
                    margin: 0 0 10px 0; 
                    font-size: 24px; 
                    color: #0f766e;
                }
                .info { 
                    margin: 5px 0; 
                    font-size: 16px; 
                    font-weight: 500;
                }
                .suivi-card { 
                    display: inline-block; /* centrage horizontal */
                    text-align: left; /* texte à l'intérieur aligné à gauche */
                    border: 1px solid #e2e8f0; 
                    border-radius: 16px; 
                    padding: 25px; 
                    margin-top: 20px;
                    max-width: 600px;
                    width: 100%;
                    background: #f9fafb;
                }
                .suivi-date-box { 
                    display: inline-block; 
                    text-align: center; 
                    padding: 10px; 
                    background: #f1f5f9; 
                    border-radius: 12px; 
                    margin-bottom: 15px;
                }
                .patient-name { 
                    font-weight: 700; 
                    font-size: 18px; 
                    margin-bottom: 10px; 
                    text-align: center;
                }
                .commentaire-text { 
                    background: #f0fdfa; 
                    padding: 15px; 
                    border-radius: 12px; 
                    border-left: 5px solid #0f766e; 
                    font-size: 14px;
                    line-height: 1.6;
                }
            </style>
        </head>
        <body>
            <div class="print-header">
                <h2>Suivi Patient</h2>
                <div class="info"><strong>Médecin :</strong> ${medecinNom} (${medecinSpecialite})</div>
                <div class="info"><strong>Date du suivi :</strong> ${dateSuivi}</div>
                <div class="info"><strong>Patient :</strong> ${patientName}</div>
            </div>

            <div class="suivi-card">
                ${commentaire}
            </div>
        </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.focus();
    printWindow.print();
    printWindow.close();
}



</script>

</body>
</html>