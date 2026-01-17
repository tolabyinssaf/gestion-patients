<?php
session_start();
include("../config/connexion.php");

// 1. SÉCURITÉ & ACCÈS
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'infirmier') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$selected_date = $_GET['date'] ?? date('Y-m-d');

// 2. RÉCUPÉRATION INFOS INFIRMIER
$stmt = $pdo->prepare("SELECT nom, prenom FROM utilisateurs WHERE id_user = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$nom_complet = htmlspecialchars(($user['prenom'] ?? '') . " " . ($user['nom'] ?? ''));

// 3. RÉCUPÉRATION DU PLANNING
$sql_planning = "
    SELECT 
        pl.id_planning as id_cle,
        pl.heure_prevue as heure, 
        pl.soin_a_faire as acte, 
        pl.description_detaillee as description,
        pl.statut, 
        pl.priorite,
        p.id_patient,
        a.id_admission,
        p.nom as pat_nom, p.prenom as pat_prenom, a.chambre,
        'prevu' as source
    FROM planning_soins pl
    JOIN patients p ON pl.id_patient = p.id_patient
    JOIN admissions a ON pl.id_admission = a.id_admission
    WHERE pl.date_prevue = ? AND pl.statut != 'fait'

    UNION ALL

    SELECT 
        s.id_soin_patient as id_cle,
        TIME(s.date_soin) as heure, 
        s.type_acte as acte, 
        s.observations as description,
        'fait' as statut, 
        'normale' as priorite,
        p.id_patient,
        a.id_admission,
        p.nom as pat_nom, p.prenom as pat_prenom, a.chambre,
        'realise' as source
    FROM soins_patients s
    JOIN admissions a ON s.id_admission = a.id_admission
    JOIN patients p ON a.id_patient = p.id_patient
    WHERE DATE(s.date_soin) = ?

    ORDER BY heure ASC";

$stmt_plan = $pdo->prepare($sql_planning);
$stmt_plan->execute([$selected_date, $selected_date]);
$soins_prevus = $stmt_plan->fetchAll(PDO::FETCH_ASSOC);

// 4. GÉNÉRATION DES JOURS
$agenda_days = [];
for ($i = -3; $i <= 3; $i++) {
    $timestamp = strtotime("$i days", strtotime($selected_date));
    $agenda_days[] = [
        'full' => date('Y-m-d', $timestamp),
        'day_num' => date('d', $timestamp),
        'day_name' => date('D', $timestamp), 
        'active' => (date('Y-m-d', $timestamp) == $selected_date)
    ];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>MedCare | Planning Médical</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0d9488;
            --primary-soft: #f0fdfa;
            --secondary: #6366f1;
            --accent-red: #f43f5e;
            --bg-body: #f8fafc;
            --sidebar-bg: #0f172a;
            --header-height: 75px;
            --sidebar-width: 260px;
            --border-color: #e2e8f0;
        }

        body { 
            background: var(--bg-body); 
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #1e293b;
        }

        header { background: white; padding: 0 40px; height: var(--header-height); display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); position: fixed; top: 0; left: 0; right: 0; z-index: 1000; }
        .sidebar { width: var(--sidebar-width); background: var(--sidebar-bg); padding: 24px 16px; position: fixed; top: var(--header-height); left: 0; bottom: 0; z-index: 999; }
        .sidebar a { display: flex; align-items: center; gap: 12px; color: #94a3b8; text-decoration: none; padding: 12px 16px; border-radius: 12px; margin-bottom: 5px; }
        .sidebar a.active { background: var(--primary); color: white; }
        .content { margin-left: var(--sidebar-width); margin-top: var(--header-height); padding: 40px; }

        .agenda-container { display: flex; gap: 12px; justify-content: center; margin-bottom: 40px; }
        .agenda-day {
            background: white; border: 1px solid var(--border-color); border-radius: 18px;
            width: 70px; height: 90px; display: flex; flex-direction: column;
            align-items: center; justify-content: center; text-decoration: none;
            color: #64748b;
        }
        .agenda-day.active { background: var(--primary); color: white; border-color: var(--primary); }

        .soin-item { display: grid; grid-template-columns: 80px 1fr; gap: 25px; margin-bottom: 20px; }
        .soin-time { font-weight: 800; font-size: 1.1rem; color: #475569; padding-top: 20px; text-align: right; }

        .modern-card {
            background: white; border-radius: 16px; padding: 20px 24px;
            display: flex; justify-content: space-between; align-items: center;
            border: 1px solid #e2e8f0;
            position: relative;
        }

        .room-badge { background: #1e293b; color: white; padding: 4px 12px; border-radius: 8px; font-weight: 700; font-size: 0.8rem; }
        .patient-name { font-weight: 700; color: var(--primary); font-size: 0.95rem; margin-left: 10px; }
        .acte-title { font-size: 1.2rem; font-weight: 800; color: #0f172a; margin: 8px 0 4px 0; }
        .acte-desc { font-size: 0.9rem; color: #64748b; display: flex; align-items: center; gap: 6px; }

        .priority-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; }

        .btn-action-group { display: flex; gap: 10px; }
        .btn-dossier { background: white; border: 1px solid #cbd5e1; color: #475569; padding: 8px 16px; border-radius: 10px; font-weight: 600; text-decoration: none; font-size: 0.85rem; }
        .btn-valider { background: #0f172a; color: white; border: none; padding: 8px 20px; border-radius: 10px; font-weight: 700; text-decoration: none; font-size: 0.85rem; }

        .done-status { background: #f8fafc; border-left: 5px solid var(--primary); opacity: 1; }
    </style>
</head>
<body>

<header>
    <img src="../images/logo_app2.png" style="height: 45px;">
    <div style="background: var(--primary-soft); padding: 10px 18px; border-radius: 12px; display: flex; align-items: center; gap: 10px; font-weight: 700; color: var(--primary); border: 1px solid #ccfbf1;">
        <i class="fa-solid fa-user-nurse"></i> <span>Inf. <?= $nom_complet ?></span>
    </div>
</header>

<aside class="sidebar">
    <h3 style="color:rgba(255,255,255,0.3); font-size:11px; text-transform:uppercase; margin-bottom:20px; padding-left:12px;">Menu Infirmier</h3>
    <a href="dashboard_infirmier.php" ><i class="fa-solid fa-chart-line"></i> Tableau de bord</a>
    <a href="liste_patients_inf.php"><i class="fa-solid fa-user-injured"></i> Liste des Patients</a>
    <a href="saisir_soins.php"><i class="fa-solid fa-notes-medical"></i> Saisir un Soin</a>
    <a href="planning.php" class="active"><i class="fa-solid fa-calendar-alt"></i> Planning</a>
    <div style="height: 1px; background: rgba(255,255,255,0.1); margin: 20px 0;"></div>
    <a href="../connexio_utilisateur/deconnexion.php" style="color: #fda4af;"><i class="fa-solid fa-power-off"></i> Déconnexion</a>
</aside>

<main class="content">
    <div class="d-flex justify-content-between align-items-end mb-4">
        <div>
            <p class="text-muted"><?= date('l d F Y', strtotime($selected_date)) ?></p>
        </div>
        <div class="bg-white p-2 rounded-3 border d-flex align-items-center gap-2">
            <input type="date" class="form-control form-control-sm border-0 bg-light" value="<?= $selected_date ?>" onchange="location.href='?date='+this.value">
        </div>
    </div>

    <div class="agenda-container">
        <?php foreach($agenda_days as $day): ?>
            <a href="?date=<?= $day['full'] ?>" class="agenda-day <?= $day['active'] ? 'active' : '' ?>">
                <span class="day-t" style="font-size: 10px; text-transform: uppercase;"><?= $day['day_name'] ?></span>
                <span class="day-n" style="font-weight: 800; font-size: 1.4rem;"><?= $day['day_num'] ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="mt-5">
        <?php if($soins_prevus): ?>
            <?php foreach($soins_prevus as $s): 
                $is_done = ($s['statut'] == 'fait');
                $p_color = ($s['priorite'] == 'haute') ? 'var(--accent-red)' : 'var(--secondary)';
                $acte_text = strtolower($s['acte']); // Pour la détection
            ?>
            <div class="soin-item">
                <div class="soin-time"><?= date('H:i', strtotime($s['heure'])) ?></div>
                
                <div class="modern-card <?= $is_done ? 'done-status' : '' ?>">
                    <div style="flex-grow: 1;">
                        <div class="d-flex align-items-center">
                            <span class="room-badge">CH. <?= $s['chambre'] ?></span>
                            <span class="patient-name"><?= strtoupper($s['pat_nom']) ?> <?= $s['pat_prenom'] ?></span>
                        </div>
                        <h3 class="acte-title"><?= htmlspecialchars($s['acte']) ?></h3>
                        <div class="acte-desc">
                            <i class="fa-solid fa-comment-medical text-primary"></i>
                            <?= htmlspecialchars($s['description'] ?: 'Aucune consigne') ?>
                        </div>
                    </div>

                    <div class="text-end ms-4">
                        <div class="mb-3 d-flex align-items-center justify-content-end gap-2">
                            <span class="small fw-bold text-muted" style="font-size: 10px;"><?= strtoupper($s['priorite']) ?></span>
                            <span class="priority-dot" style="background: <?= $p_color ?>;"></span>
                        </div>

                        <div class="btn-action-group">
                            <a href="dossier_patient.php?id=<?= $s['id_patient'] ?>&id_adm=<?= $s['id_admission'] ?>" class="btn-dossier">Dossier</a>

                            <?php if(!$is_done): ?>
                                <?php if (strpos($acte_text, 'pansement') !== false || strpos($acte_text, 'plaie') !== false): ?>
                                    <a href="suivi_plaies.php?id_pat=<?= $s['id_patient'] ?>&id_adm=<?= $s['id_admission'] ?>" class="btn-valider" style="background: #6366f1;">
                                        <i class="fa-solid fa-camera me-1"></i> Photo
                                    </a>
                                <?php elseif (strpos($acte_text, 'constante') !== false || strpos($acte_text, 'tension') !== false): ?>
                                    <a href="saisir_constantes.php?id_pat=<?= $s['id_patient'] ?>&id_adm=<?= $s['id_admission'] ?>" class="btn-valider" style="background: var(--primary);">
                                        <i class="fa-solid fa-stethoscope me-1"></i> Saisir
                                    </a>
                                <?php else: ?>
                                    <a href="valider_soin.php?id=<?= $s['id_cle'] ?>" class="btn-valider">Valider</a>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="badge bg-success py-2 px-3 rounded-2">
                                    <i class="fa-solid fa-check me-1"></i> Terminé
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="bg-white rounded-4 p-5 text-center border mt-4" style="border-style: dashed !important; border-width: 2px !important; background: rgba(248, 250, 252, 0.5) !important;">
                <div class="mb-4">
                    <div class="icon-box d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px; background: var(--primary-soft); color: var(--primary); border-radius: 50%; font-size: 2rem;">
                        <i class="fa-solid fa-clipboard-check"></i>
                    </div>
                </div>
                <h4 class="fw-bold" style="color: #0f172a;">Tout est à jour !</h4>
                <p class="text-muted mx-auto" style="max-width: 400px; font-size: 0.95rem;">
                    Aucun soin n'est planifié pour cette journée. Profitez-en pour mettre à jour vos dossiers patients ou vérifier les constantes.
                </p>
                <a href="planning.php" class="btn btn-sm mt-3" style="background: var(--primary-soft); color: var(--primary); font-weight: 700; border-radius: 8px; padding: 8px 20px;">
                    <i class="fa-solid fa-rotate-right me-2"></i> Actualiser la liste
                </a>
            </div>
        <?php endif; ?>
    </div>
</main>
</body>
</html>