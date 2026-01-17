<?php
session_start();
include("../config/connexion.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'medecin') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// --- GESTION DE LA DATE (AGENDA) ---
$date_selectionnee = isset($_GET['date_filtre']) ? $_GET['date_filtre'] : date('Y-m-d');

// 1. Infos médecin
$stmt = $pdo->prepare("SELECT nom, prenom FROM utilisateurs WHERE id_user = ?");
$stmt->execute([$user_id]);
$medecin = $stmt->fetch(PDO::FETCH_ASSOC);

// 2. STATS CLINIQUES : Patients hospitalisés
$stmt_hosp = $pdo->prepare("
    SELECT COUNT(*) as total 
    FROM admissions 
    WHERE id_medecin = ? 
    AND DATE(date_admission) <= ? 
    AND (date_sortie IS NULL OR DATE(date_sortie) >= ?)
");
$stmt_hosp->execute([$user_id, $date_selectionnee, $date_selectionnee]);
$patients_admis = $stmt_hosp->fetch(PDO::FETCH_ASSOC)['total'];

// 3. STATS ACTIVITÉ : Suivis faits
$stmt_today = $pdo->prepare("
    SELECT COUNT(*) as total 
    FROM suivis s 
    JOIN patients p ON s.id_patient = p.id_patient 
    WHERE s.id_medecin = ? AND DATE(s.date_suivi) = ?
");
$stmt_today->execute([$user_id, $date_selectionnee]);
$suivis_jour = $stmt_today->fetch(PDO::FETCH_ASSOC)['total'];

// 4. LISTE DES ACTIVITÉS SELON LA DATE (Correction de la colonne id_chambre)
$stmt_act = $pdo->prepare("
    SELECT p.nom, p.prenom, s.date_suivi, s.commentaire, a.id_chambre, a.statut as etat_patient, p.id_patient
    FROM suivis s
    JOIN patients p ON s.id_patient = p.id_patient
    LEFT JOIN admissions a ON p.id_patient = a.id_patient 
        AND DATE(a.date_admission) <= DATE(s.date_suivi) 
        AND (a.date_sortie IS NULL OR DATE(a.date_sortie) >= DATE(s.date_suivi))
    WHERE s.id_medecin = ? AND DATE(s.date_suivi) = ?
    ORDER BY s.date_suivi DESC
");
$stmt_act->execute([$user_id, $date_selectionnee]);
$activites = $stmt_act->fetchAll(PDO::FETCH_ASSOC);
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
        }

        * { margin:0; padding:0; box-sizing:border-box; font-family: 'Inter', sans-serif; }
        body { background: var(--bg-body); color: #1e293b; }

        header { 
            background: #fff; padding: 0 40px; height: 70px; 
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid #e2e8f0; position: sticky; top: 0; z-index: 100;
        }
        .user-pill { background: var(--primary-light); padding: 8px 15px; border-radius: 10px; color: var(--primary); font-weight: 700; font-size: 14px; }
        .container { display: flex; min-height: calc(100vh - 70px); }

        .sidebar { width: 260px; background: var(--sidebar-bg); padding: 25px 15px; }
        .sidebar h3 { color: #475569; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; margin: 20px 0 10px 10px; }
        .sidebar a { display: flex; align-items: center; gap: 12px; color: #94a3b8; text-decoration: none; padding: 12px; border-radius: 8px; margin-bottom: 4px; transition: 0.3s; }
        .sidebar a:hover, .sidebar a.active { background: var(--primary); color: #fff; }

        .content { flex: 1; padding: 35px; }

        .agenda-box {
            background: #fff; padding: 15px 25px; border-radius: 15px; margin-bottom: 30px;
            display: flex; justify-content: space-between; align-items: center;
            border: 1px solid #e2e8f0; box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }
        .date-input {
            padding: 8px 12px; border: 2px solid #e2e8f0; border-radius: 8px;
            font-weight: 600; color: var(--primary); outline: none; cursor: pointer;
        }

        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 25px; margin-bottom: 35px; }
        .stat-card { 
            background: #fff; padding: 25px; border-radius: 20px; border: 1px solid #e2e8f0;
            display: flex; align-items: center; gap: 20px;
        }
        .icon-circle { width: 55px; height: 55px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 22px; }

        .data-card { background: #fff; border-radius: 20px; padding: 25px; border: 1px solid #e2e8f0; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 12px; color: #64748b; font-size: 11px; text-transform: uppercase; border-bottom: 2px solid #f1f5f9; }
        td { padding: 16px 12px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        .badge-hosp { background: #eff6ff; color: #1d4ed8; padding: 4px 10px; border-radius: 6px; font-weight: 700; font-size: 11px; }
        .status-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 8px; }
        .bg-stable { background: var(--success); }
        .bg-critique { background: var(--danger); }
        .text-truncate { max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    </style>
</head>
<body>

<header>
    <img src="../images/logo_app2.png" style="height: 40px;">
    <div class="user-pill"><i class="fa-solid fa-user-doctor me-2"></i> Dr. <?= strtoupper($medecin['nom']) ?></div>
</header>

<div class="container">
    <aside class="sidebar">
        <h3>Unité de Soins</h3>
        <a href="dashboard_medecin.php" class="active"><i class="fa-solid fa-house-pulse"></i> Dashboard</a>
        <a href="hospitalisation.php"><i class="fa-solid fa-bed-pulse"></i> Patients Admis</a>
        <h3>Dossiers</h3>
        <a href="patients.php"><i class="fa-solid fa-address-book"></i> Base Patients</a>
        <a href="suivis.php"><i class="fa-solid fa-file-medical"></i> Journal des Soins</a>
        <a href="rendezvous.php"><i class="fa-solid fa-clock"></i> Consultations</a>
        <div style="margin-top: 50px;">
            <a href="deconnexion.php" style="color: #f87171;"><i class="fa-solid fa-arrow-right-from-bracket"></i> Déconnexion</a>
        </div>
    </aside>

    <main class="content">
        <div class="agenda-box">
            <div>
                <h1 style="font-size: 20px; font-weight: 800; color: #0f172a;">
                    <?= $date_selectionnee == date('Y-m-d') ? "Aujourd'hui" : "Archives du " . date('d/m/Y', strtotime($date_selectionnee)) ?>
                </h1>
                <p style="color: #64748b; font-size: 13px;">Consulter les données par date</p>
            </div>
            <form action="" method="GET" id="dateForm">
                <input type="date" name="date_filtre" class="date-input" 
                       value="<?= $date_selectionnee ?>" 
                       onchange="document.getElementById('dateForm').submit()">
            </form>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon-circle" style="background: #fff7ed; color: #ea580c;"><i class="fa-solid fa-bed"></i></div>
                <div>
                    <div style="font-size: 24px; font-weight: 800;"><?= $patients_admis ?></div>
                    <div style="color: #64748b; font-size: 13px;">Lits occupés ce jour</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="icon-circle" style="background: #f0fdf4; color: #16a34a;"><i class="fa-solid fa-stethoscope"></i></div>
                <div>
                    <div style="font-size: 24px; font-weight: 800;"><?= $suivis_jour ?></div>
                    <div style="color: #64748b; font-size: 13px;">Examens effectués</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="icon-circle" style="background: #eff6ff; color: #2563eb;"><i class="fa-solid fa-calendar-check"></i></div>
                <div>
                    <div style="font-size: 24px; font-weight: 800;">4</div>
                    <div style="color: #64748b; font-size: 13px;">RDV programmés</div>
                </div>
            </div>
        </div>

        <div class="data-card">
            <h2 style="font-size: 15px; margin-bottom: 20px; color: #475569;">
                <i class="fa-solid fa-clipboard-list me-2"></i> Activités du <?= date('d/m/Y', strtotime($date_selectionnee)) ?>
            </h2>
            <table>
                <thead>
                    <tr>
                        <th>Patient</th>
                        <th>Localisation</th>
                        <th>Dernière Observation</th>
                        <th>Heure</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($activites)): ?>
                        <tr><td colspan="5" style="text-align:center; color:#94a3b8; padding:30px;">Aucune activité enregistrée à cette date.</td></tr>
                    <?php endif; ?>
                    
                    <?php foreach ($activites as $a): ?>
                    <tr>
                        <td style="font-weight: 700; color: #1e293b;">
                            <span class="status-dot <?= (isset($a['etat_patient']) && $a['etat_patient'] == 'Urgence') ? 'bg-critique' : 'bg-stable' ?>"></span>
                            <?= htmlspecialchars($a['nom'].' '.$a['prenom']) ?>
                        </td>
                        <td>
                            <?php if(!empty($a['id_chambre'])): ?>
                                <span class="badge-hosp"><i class="fa-solid fa-door-open me-1"></i> Ch. <?= $a['id_chambre'] ?></span>
                            <?php else: ?>
                                <span style="color: #94a3b8; font-size: 12px;">Externe</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-truncate" style="color: #64748b;"><?= htmlspecialchars($a['commentaire']) ?></td>
                        <td style="font-weight: 500;"><?= date('H:i', strtotime($a['date_suivi'])) ?></td>
                        <td>
                            <a href="dossier_patient.php?id=<?= $a['id_patient'] ?>" style="color: var(--primary); text-decoration: none; font-weight: 700;">Dossier</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

</body>
</html>