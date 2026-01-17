<?php
session_start();
include("../config/connexion.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'medecin') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// 1. Infos médecin pour le Header
$stmt_med = $pdo->prepare("SELECT nom, prenom FROM utilisateurs WHERE id_user = ?");
$stmt_med->execute([$user_id]);
$medecin = $stmt_med->fetch(PDO::FETCH_ASSOC);

// 2. Récupérer les patients hospitalisés (Adapté à votre DB)
$stmt = $pdo->prepare("
    SELECT 
        a.id_admission,
        a.chambre, 
        a.service, 
        a.date_admission, 
        a.status,
        p.id_patient,
        p.nom, 
        p.prenom,
        DATEDIFF(NOW(), a.date_admission) as jours
    FROM admissions a
    JOIN patients p ON a.id_patient = p.id_patient
    WHERE a.id_medecin = ? AND a.date_sortie IS NULL
    ORDER BY a.chambre ASC
");
$stmt->execute([$user_id]);
$admis = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Hospitalisations | MedCare Pro</title>
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
        }

        * { margin:0; padding:0; box-sizing:border-box; font-family: 'Inter', sans-serif; }
        body { background: var(--bg-body); color: #1e293b; }

        /* HEADER IDENTIQUE AU DASHBOARD */
        header { 
            background: #fff; padding: 0 40px; height: 70px; 
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid #e2e8f0; position: sticky; top: 0; z-index: 100;
        }
        .user-pill { background: var(--primary-light); padding: 8px 15px; border-radius: 10px; color: var(--primary); font-weight: 700; font-size: 14px; }

        .container { display: flex; min-height: calc(100vh - 70px); }

        /* SIDEBAR IDENTIQUE AU DASHBOARD */
        .sidebar { width: 260px; background: var(--sidebar-bg); padding: 25px 15px; }
        .sidebar h3 { color: #475569; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; margin: 20px 0 10px 10px; }
        .sidebar a { 
            display: flex; align-items: center; gap: 12px; color: #94a3b8; 
            text-decoration: none; padding: 12px; border-radius: 8px; margin-bottom: 4px; transition: 0.3s;
        }
        .sidebar a:hover, .sidebar a.active { background: var(--primary); color: #fff; }

        /* CONTENT */
        .content { flex: 1; padding: 35px; }
        
        .page-header { margin-bottom: 30px; display: flex; justify-content: space-between; align-items: flex-end; }
        
        /* GRILLE DE CHAMBRES */
        .rooms-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; }
        
        .card-patient {
            background: #fff; border-radius: 18px; padding: 20px;
            border: 1px solid #e2e8f0; position: relative;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        .card-patient:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        
        .chambre-tag {
            position: absolute; top: 20px; right: 20px;
            background: var(--sidebar-bg); color: #fff;
            padding: 4px 12px; border-radius: 8px; font-weight: 800; font-size: 12px;
        }

        .patient-info h2 { font-size: 18px; font-weight: 800; color: #0f172a; margin-bottom: 15px; }
        
        .detail-row { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; font-size: 13px; color: #64748b; }
        .detail-row i { width: 16px; color: var(--primary); }

        .status-badge {
            display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; margin-top: 10px;
        }
        .status-urgence { background: #fff1f2; color: var(--danger); }
        .status-stable { background: #f0fdf4; color: var(--success); }
        .status-observation { background: #fffbeb; color: var(--warning); }

        .jours-count {
            margin-top: 15px; padding-top: 15px; border-top: 1px dashed #e2e8f0;
            font-size: 12px; font-weight: 600; color: #1e293b;
        }

        /* BOUTONS */
        .card-actions { display: flex; gap: 10px; margin-top: 20px; }
        .btn-action { 
            flex: 1; padding: 10px; border-radius: 10px; text-align: center; 
            text-decoration: none; font-size: 12px; font-weight: 700; transition: 0.2s;
        }
        .btn-visit { background: var(--primary); color: #fff; border: 1px solid var(--primary); }
        .btn-visit:hover { background: #0d615a; }
        
        .btn-exit { background: #fff; color: var(--danger); border: 1px solid #fee2e2; }
        .btn-exit:hover { background: #fff1f2; }

        .empty-state { text-align: center; padding: 60px; background: #fff; border-radius: 20px; border: 2px dashed #e2e8f0; }
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
        <a href="dashboard_medecin.php"><i class="fa-solid fa-house-pulse"></i> Dashboard</a>
        <a href="hospitalisation.php" class="active"><i class="fa-solid fa-bed-pulse"></i> Patients Admis</a>
        
        <h3>Dossiers</h3>
        <a href="patients.php"><i class="fa-solid fa-address-book"></i> Base Patients</a>
        <a href="suivis.php"><i class="fa-solid fa-file-medical"></i> Journal des Soins</a>
        <a href="rendezvous.php"><i class="fa-solid fa-clock"></i> Consultations</a>
        
        <div style="margin-top: 50px;">
            <a href="deconnexion.php" style="color: #f87171;"><i class="fa-solid fa-arrow-right-from-bracket"></i> Déconnexion</a>
        </div>
    </aside>

    <main class="content">
        <div class="page-header">
            <div>
                <h1 style="font-size: 24px; font-weight: 800;">Hospitalisations en cours</h1>
                <p style="color: #64748b; font-size: 14px;">Surveillance de votre unité de soins.</p>
            </div>
            <div style="text-align: right;">
                <span style="font-size: 12px; font-weight: 700; color: #94a3b8; text-transform: uppercase;">Capacité occupée</span>
                <div style="font-size: 20px; font-weight: 800; color: var(--primary);"><?= count($admis) ?> Patients</div>
            </div>
        </div>

        <?php if (empty($admis)): ?>
            <div class="empty-state">
                <i class="fa-solid fa-bed-pulse" style="font-size: 40px; color: #cbd5e1; margin-bottom: 15px;"></i>
                <p style="color: #64748b; font-weight: 600;">Aucune hospitalisation active sous votre responsabilité.</p>
            </div>
        <?php else: ?>
            <div class="rooms-grid">
                <?php foreach ($admis as $p): ?>
                    <div class="card-patient">
                        <div class="chambre-tag">Chambre <?= htmlspecialchars($p['chambre']) ?></div>
                        
                        <div class="patient-info">
                            <h2><?= htmlspecialchars($p['prenom'] . ' ' . $p['nom']) ?></h2>
                            
                            <div class="detail-row">
                                <i class="fa-solid fa-stethoscope"></i>
                                Service : <?= htmlspecialchars($p['service']) ?>
                            </div>
                            <div class="detail-row">
                                <i class="fa-solid fa-calendar-day"></i>
                                Admis le : <?= date('d/m/Y', strtotime($p['date_admission'])) ?>
                            </div>

                            <?php 
                                $statusClass = 'status-stable';
                                if($p['status'] == 'Urgence') $statusClass = 'status-urgence';
                                if($p['status'] == 'Observation') $statusClass = 'status-observation';
                            ?>
                            <span class="status-badge <?= $statusClass ?>">
                                <i class="fa-solid fa-circle-info me-1"></i> <?= htmlspecialchars($p['status']) ?>
                            </span>
                        </div>

                        <div class="jours-count">
                            <i class="fa-solid fa-clock-rotate-left me-2" style="color: var(--primary);"></i>
                            Hospitalisé depuis <?= $p['jours'] ?> jour(s)
                        </div>

                        <div class="card-actions">
                            <a href="ajouter_suivi.php?id=<?= $p['id_patient'] ?>" class="btn-action btn-visit">
                                <i class="fa-solid fa-file-medical me-1"></i> Visite
                            </a>
                            <a href="traiter_sortie.php?id_adm=<?= $p['id_admission'] ?>" 
                               class="btn-action btn-exit" 
                               onclick="return confirm('Voulez-vous valider la sortie médicale ?')">
                                <i class="fa-solid fa-right-from-bracket me-1"></i> Sortie
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</div>

</body>
</html>