<?php
session_start();
include("../config/connexion.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'medecin') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$date_selectionnee = isset($_GET['date_filtre']) ? $_GET['date_filtre'] : date('Y-m-d');

// --- LOGIQUE DE GESTION DES ACTIONS (TERMINER / SUPPRIMER) ---
if (isset($_GET['action']) && isset($_GET['id_suivi'])) {
    $id_suivi = $_GET['id_suivi'];
    
    if ($_GET['action'] === 'terminer') {
        $stmt = $pdo->prepare("UPDATE suivis SET status = 'Terminé' WHERE id_suivi = ? AND id_medecin = ?");
        $stmt->execute([$id_suivi, $user_id]);
    } 
    elseif ($_GET['action'] === 'supprimer') {
        $stmt = $pdo->prepare("DELETE FROM suivis WHERE id_suivi = ? AND id_medecin = ?");
        $stmt->execute([$id_suivi, $user_id]);
    }
    
    // Redirection pour éviter de répéter l'action au rafraîchissement
    header("Location: dashboard_medecin.php?date_filtre=" . $date_selectionnee);
    exit;
}

// 1. Infos médecin
$stmt = $pdo->prepare("SELECT nom, prenom FROM utilisateurs WHERE id_user = ?");
$stmt->execute([$user_id]);
$medecin = $stmt->fetch(PDO::FETCH_ASSOC);

// 2. STATS : Patients hospitalisés
$stmt_hosp = $pdo->prepare("SELECT COUNT(*) as total FROM admissions WHERE id_medecin = ? AND date_sortie IS NULL");
$stmt_hosp->execute([$user_id]);
$patients_hospitalises = $stmt_hosp->fetch(PDO::FETCH_ASSOC)['total'];

// Détails hospitalisés
$stmt_hosp_details = $pdo->prepare("
    SELECT p.id_patient, p.nom, p.prenom, a.id_admission, a.date_admission, a.service, c.numero_chambre
    FROM admissions a
    JOIN patients p ON a.id_patient = p.id_patient
    LEFT JOIN chambres c ON a.id_chambre = c.id_chambre
    WHERE a.id_medecin = ? AND a.date_sortie IS NULL
    GROUP BY a.id_admission ORDER BY a.date_admission DESC
");
$stmt_hosp_details->execute([$user_id]);
$patients_hospitalises_details = $stmt_hosp_details->fetchAll(PDO::FETCH_ASSOC);

// 3. STATS : Nouveaux admis
$stmt_nouveaux = $pdo->prepare("SELECT COUNT(*) as total FROM admissions WHERE id_medecin = ? AND DATE(date_admission) = ?");
$stmt_nouveaux->execute([$user_id, $date_selectionnee]);
$nouveaux_admis = $stmt_nouveaux->fetch(PDO::FETCH_ASSOC)['total'];

$stmt_nouveaux_details = $pdo->prepare("
    SELECT p.id_patient, p.nom, p.prenom, a.date_admission, a.service, c.numero_chambre
    FROM admissions a
    JOIN patients p ON a.id_patient = p.id_patient
    LEFT JOIN chambres c ON a.id_chambre = c.id_chambre
    WHERE a.id_medecin = ? AND DATE(a.date_admission) = ?
    GROUP BY a.id_admission ORDER BY a.date_admission DESC
");
$stmt_nouveaux_details->execute([$user_id, $date_selectionnee]);
$nouveaux_admis_details = $stmt_nouveaux_details->fetchAll(PDO::FETCH_ASSOC);

// 5. LISTE DES SUIVIS (Optimisé)
$stmt_suivis_details = $pdo->prepare("
    SELECT s.id_suivi, s.id_patient, s.status, s.date_suivi, s.commentaire, p.nom, p.prenom
    FROM suivis s
    JOIN patients p ON s.id_patient = p.id_patient
    WHERE s.id_medecin = ? AND DATE(s.date_suivi) = ? 
    ORDER BY s.date_suivi DESC
");
$stmt_suivis_details->execute([$user_id, $date_selectionnee]);
$suivis_details = $stmt_suivis_details->fetchAll(PDO::FETCH_ASSOC);
$suivis_jour = count($suivis_details);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>MedCare Pro | Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #0f766e;
            --primary-light: #f0fdfa;
            --sidebar-bg: #0f172a;
            --bg-body: #f1f5f9;
            --danger: #e11d48;
            --success: #10b981;
            --warning: #f59e0b;
            --info: #3b82f6;
        }

        * { margin:0; padding:0; box-sizing:border-box; font-family: 'Inter', sans-serif; }
        body { background: var(--bg-body); color: #1e293b; }

        header { 
            background: #fff; padding: 0 40px; height: 70px; 
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid #e2e8f0; position: sticky; top: 0; z-index: 100;
        }
        .user-pill { 
            background: var(--primary-light); 
            padding: 8px 15px; 
            border-radius: 10px; 
            color: var(--primary); 
            font-weight: 700; 
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .container { display: flex; min-height: calc(100vh - 70px); }

        .sidebar { 
            width: 260px; 
            background: var(--sidebar-bg); 
            padding: 25px 15px;
        }
        .sidebar h3 { 
            color: #475569; 
            font-size: 11px; 
            text-transform: uppercase; 
            letter-spacing: 1px; 
            margin: 20px 0 10px 10px; 
        }
        .sidebar a { 
            display: flex; 
            align-items: center; 
            gap: 12px; 
            color: #94a3b8; 
            text-decoration: none; 
            padding: 12px; 
            border-radius: 8px; 
            margin-bottom: 4px; 
            transition: 0.3s; 
        }
        .sidebar a:hover, .sidebar a.active { 
            background: var(--primary); 
            color: #fff; 
        }

        .content { flex: 1; padding: 40px; }

        .agenda-box {
            background: #fff; 
            padding: 25px 35px; 
            border-radius: 15px; 
            margin-bottom: 35px;
            display: flex; 
            justify-content: space-between; 
            align-items: center;
            border: 1px solid #e2e8f0; 
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        }

        .date-input {
            padding: 12px 18px; 
            border: 2px solid #cbd5e1; 
            border-radius: 10px;
            font-weight: 700; 
            font-size: 16px;
            color: var(--primary); 
            outline: none; 
            cursor: pointer;
            transition: all 0.3s;
        }

        .stats-grid { 
            display: grid; 
            grid-template-columns: repeat(3, 1fr); 
            gap: 30px; 
            margin-bottom: 40px; 
        }
        .stat-card { 
            background: #fff; 
            padding: 30px 25px; 
            border-radius: 20px; 
            border: 1px solid #e2e8f0;
            display: flex; 
            align-items: center; 
            gap: 25px;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            border-color: var(--primary);
        }
        .icon-circle { 
            width: 65px; height: 65px; 
            border-radius: 15px; 
            display: flex; align-items: center; justify-content: center; 
            font-size: 28px; 
        }

        .data-card { 
            background: #fff; 
            border-radius: 20px; 
            padding: 30px; 
            border: 1px solid #e2e8f0;
        }
        
        table { width: 100%; border-collapse: collapse; }
        th { 
            text-align: left; padding: 15px; 
            color: #64748b; font-size: 13px; 
            text-transform: uppercase; 
            border-bottom: 2px solid #f1f5f9; 
        }
        td { 
            padding: 15px; 
            border-bottom: 1px solid #f1f5f9; 
            font-size: 15px; 
        }

        /* Status Pills */
        .status-pill {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
        }
        .status-attente { background: #fef3c7; color: #92400e; }
        .status-termine { background: #dcfce7; color: #166534; }

        /* Action Buttons */
        .btn-done {
            background: var(--success);
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
            border: none;
            cursor: pointer;
        }
        .btn-delete {
            color: var(--danger);
            font-size: 18px;
            margin-left: 15px;
            text-decoration: none;
            transition: 0.2s;
        }
        .btn-delete:hover { transform: scale(1.2); }

        .action-link {
            color: var(--primary);
            text-decoration: none;
            font-weight: 800;
            padding: 8px 16px;
            border-radius: 8px;
            background: var(--primary-light);
        }

        /* Modal Styles */
        .modal-overlay {
            display: none; position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(4px); z-index: 1000;
            justify-content: center; align-items: center; padding: 20px;
        }
        .modal-content {
            background: white; border-radius: 20px;
            width: 650px; max-height: 80vh; overflow-y: auto;
        }
        .modal-header {
            padding: 25px; border-bottom: 1px solid #e2e8f0;
            display: flex; justify-content: space-between; align-items: center;
        }
        .modal-body { padding: 10px 25px 25px; }
    </style>
</head>
<body>

<header>
    <img src="../images/logo_app2.png" style="height: 40px;" alt="MedCare Logo">
    <div class="user-pill">
        <i class="fa-solid fa-user-doctor"></i>
        <span>Dr. <?= strtoupper($medecin['nom']) ?></span>
    </div>
</header>

<div class="container">
    <aside class="sidebar">
        <h3 style="font-weight: 800;">Unité de Soins</h3>
        <a href="dashboard_medecin.php" class="active"><i class="fa-solid fa-chart-line"></i> Vue Générale</a>
        <a href="hospitalisation.php"><i class="fa-solid fa-bed-pulse"></i> Patients Admis</a>
        <a href="patients.php"><i class="fa-solid fa-hospital-user"></i> Patients</a>
        <a href="../traitement/list.php"><i class="fa-solid fa-file-prescription"></i> Traitements</a>
        <a href="suivis.php"><i class="fa-solid fa-calendar-check"></i> Consultations</a>
        <h3 style="font-weight: 800;">Analyse & Gestion</h3>
        <a href="../admission/statistique.php"><i class="fa-solid fa-chart-pie"></i> Statistiques</a>
        <a href="archives.php"><i class="fa-solid fa-box-archive"></i> Archives</a>
        <a href="profil_medcin.php"><i class="fa-solid fa-user-gear"></i> Profil</a>
        <div style="margin-top: 40px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 10px;">
            <a href="deconnexion.php" style="color: #fda4af;"><i class="fa-solid fa-power-off"></i> Déconnexion</a>
        </div>
    </aside>

    <main class="content">
        <div class="agenda-box">
            <div>
                <h1 style="font-size: 26px; font-weight: 900; color: #0f172a;">
                    <?= $date_selectionnee == date('Y-m-d') ? "Aujourd'hui" : date('d/m/Y', strtotime($date_selectionnee)) ?>
                </h1>
                <p style="color: #64748b;">Consulter les données par date</p>
            </div>
            <form action="" method="GET" id="dateForm">
                <input type="date" name="date_filtre" class="date-input" value="<?= htmlspecialchars($date_selectionnee) ?>" onchange="document.getElementById('dateForm').submit()">
            </form>
        </div>

        <div class="stats-grid">
            <div class="stat-card" onclick="openModal('hospitalises')">
                <div class="icon-circle" style="background: #f0fdf4; color: var(--success);"><i class="fa-solid fa-hospital-user"></i></div>
                <div>
                    <div style="font-size: 32px; font-weight: 900;"><?= $patients_hospitalises ?></div>
                    <div style="color: #64748b; font-size: 14px;">Patients hospitalisés</div>
                </div>
            </div>

            <div class="stat-card" onclick="openModal('nouveaux')">
                <div class="icon-circle" style="background: #fff7ed; color: var(--warning);"><i class="fa-solid fa-bed"></i></div>
                <div>
                    <div style="font-size: 32px; font-weight: 900;"><?= $nouveaux_admis ?></div>
                    <div style="color: #64748b; font-size: 14px;">Nouveaux admis</div>
                </div>
            </div>

            <div class="stat-card" onclick="openModal('suivis')">
                <div class="icon-circle" style="background: #eff6ff; color: var(--info);"><i class="fa-solid fa-stethoscope"></i></div>
                <div>
                    <div style="font-size: 32px; font-weight: 900;"><?= $suivis_jour ?></div>
                    <div style="color: #64748b; font-size: 14px;">Consultations</div>
                </div>
            </div>
        </div>

        <div class="data-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                <h2 style="font-size: 18px; font-weight: 800;">Suivis médicaux du <?= date('d/m/Y', strtotime($date_selectionnee)) ?></h2>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Patient</th>
                        <th>Statut</th>
                        <th>Note / Observation</th>
                        
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if(empty($suivis_details)): ?>
                    <tr>
                        <td colspan="5" style="text-align:center; color:#94a3b8; padding:60px 20px;">Aucun suivi pour cette date.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach($suivis_details as $s): ?>
                    <tr>
                        <td style="font-weight:800;"><?= htmlspecialchars($s['prenom'] . ' ' . strtoupper($s['nom'])) ?></td>
                        <td>
                            <?php 
                                $is_done = (strtolower($s['status']) == 'terminé' || strtolower($s['status']) == 'termine');
                                $status_class = $is_done ? 'status-termine' : 'status-attente';
                                $status_label = $is_done ? 'Terminé' : 'En attente';
                            ?>
                            <span class="status-pill <?= $status_class ?>"><?= $status_label ?></span>
                        </td>
                        <td style="color:#475569; max-width:250px;">
                            <?= !empty($s['commentaire']) ? htmlspecialchars($s['commentaire']) : '<span class="text-muted">Aucune note</span>' ?>
                        </td>
                        
                        <td style="text-align:right; display: flex; align-items: center; justify-content: flex-end; gap: 10px;">
                            <a href="dossier_patient.php?id=<?= $s['id_patient'] ?>" class="action-link" title="Dossier"><i class="fa-solid fa-folder-open"></i></a>
                            
                            <?php if(!$is_done): ?>
                                <a href="?date_filtre=<?= $date_selectionnee ?>&action=terminer&id_suivi=<?= $s['id_suivi'] ?>" class="btn-done">Terminer</a>
                            <?php endif; ?>

                            <a href="?date_filtre=<?= $date_selectionnee ?>&action=supprimer&id_suivi=<?= $s['id_suivi'] ?>" 
                               class="btn-delete" 
                               onclick="return confirm('Supprimer ce suivi ?')" title="Supprimer">
                               <i class="fa-solid fa-trash-can"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<div id="modal-hospitalises" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Patients Hospitalisés (<?= $patients_hospitalises ?>)</h3>
            <button onclick="closeModal('hospitalises')" style="border:none; background:none; cursor:pointer;"><i class="fa-solid fa-times"></i></button>
        </div>
        <div class="modal-body">
            <?php foreach($patients_hospitalises_details as $patient): ?>
            <div style="padding:15px; border-bottom:1px solid #f1f5f9; display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <h4 style="margin:0;"><?= htmlspecialchars($patient['prenom'] . ' ' . strtoupper($patient['nom'])) ?></h4>
                    <p style="margin:0; font-size:12px; color:#64748b;">Ch. <?= htmlspecialchars($patient['numero_chambre'] ?? 'N/A') ?></p>
                </div>
                <a href="dossier_patient.php?id=<?= $patient['id_patient'] ?>" class="action-link">Dossier</a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div id="modal-nouveaux" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Nouveaux Admis (<?= $nouveaux_admis ?>)</h3>
            <button onclick="closeModal('nouveaux')" style="border:none; background:none; cursor:pointer;"><i class="fa-solid fa-times"></i></button>
        </div>
        <div class="modal-body">
            <?php foreach($nouveaux_admis_details as $patient): ?>
            <div style="padding:15px; border-bottom:1px solid #f1f5f9; display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <h4 style="margin:0;"><?= htmlspecialchars($patient['prenom'] . ' ' . strtoupper($patient['nom'])) ?></h4>
                    <p style="margin:0; font-size:12px; color:#64748b;">Service: <?= htmlspecialchars($patient['service']) ?></p>
                </div>
                <a href="dossier_patient.php?id=<?= $patient['id_patient'] ?>" class="action-link">Dossier</a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div id="modal-suivis" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Consultations du jour (<?= $suivis_jour ?>)</h3>
            <button onclick="closeModal('suivis')" style="border:none; background:none; cursor:pointer;"><i class="fa-solid fa-times"></i></button>
        </div>
        <div class="modal-body">
            <?php foreach($suivis_details as $suivi): ?>
            <div style="padding:15px; border-bottom:1px solid #f1f5f9; display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <h4 style="margin:0;"><?= htmlspecialchars($suivi['prenom'] . ' ' . strtoupper($suivi['nom'])) ?></h4>
                    <p style="margin:0; font-size:12px; color:#64748b;"><?= date('H:i', strtotime($suivi['date_suivi'])) ?></p>
                </div>
                <a href="dossier_patient.php?id=<?= $suivi['id_patient'] ?>" class="action-link">Dossier</a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
    function openModal(type) { document.getElementById('modal-' + type).style.display = 'flex'; }
    function closeModal(type) { document.getElementById('modal-' + type).style.display = 'none'; }
    
    // Fermeture automatique au clic extérieur
    window.onclick = function(event) {
        if (event.target.className === 'modal-overlay') {
            event.target.style.display = 'none';
        }
    }
</script>

</body>
</html>