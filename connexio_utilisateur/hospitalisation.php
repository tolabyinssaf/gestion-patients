<?php
// ... Garder votre logique PHP identique ici ...
session_start();
include("../config/connexion.php"); 
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'medecin') {
    header("Location: login.php");
    exit;
}
$user_id = (int)$_SESSION['user_id'];
$stmt_med = $pdo->prepare("SELECT nom, prenom FROM utilisateurs WHERE id_user = ?");
$stmt_med->execute([$user_id]);
$medecin = $stmt_med->fetch(PDO::FETCH_ASSOC);

$stmt_hosp = $pdo->prepare("
    SELECT 
        a.id_admission, a.service, a.date_admission, a.type_admission AS statut,
        p.id_patient, p.nom, p.prenom,
        IFNULL(c.numero_chambre, 'N/A') as chambre,
        DATEDIFF(NOW(), a.date_admission) as jours
    FROM admissions a
    INNER JOIN patients p ON a.id_patient = p.id_patient
    LEFT JOIN chambres c ON a.id_chambre = c.id_chambre 
    WHERE a.id_medecin = :id_med 
    AND (a.date_sortie IS NULL OR a.date_sortie = '0000-00-00' OR a.date_sortie = '')
    GROUP BY a.id_admission 
    ORDER BY a.date_admission DESC
");
$stmt_hosp->execute(['id_med' => $user_id]);
$admis = $stmt_hosp->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Hospitalisations | MedCare Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0d9488;
            --primary-hover: #0f766e;
            --sidebar-bg: #0f172a;
            --bg-body: #f8fafc; /* Plus clair pour le contraste */
            --card-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.04), 0 4px 6px -2px rgba(0, 0, 0, 0.02);
            --danger: #ef4444;
            --success: #10b981;
            --warning: #f59e0b;
        }

        * { margin:0; padding:0; box-sizing:border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: var(--bg-body); color: #334155; }

        /* HEADER */
        header { 
            background: #ffffff; padding: 0 40px; height: 75px; 
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid #f1f5f9; position: sticky; top: 0; z-index: 1000;
            box-shadow: 0 1px 2px rgba(0,0,0,0.03);
        }
        .user-pill { 
            background: #f0fdfa; padding: 10px 20px; border-radius: 12px; 
            color: var(--primary); font-weight: 700; font-size: 14px;
            border: 1px solid #ccfbf1; display: flex; align-items: center; gap: 8px;
        }

        .container { display: flex; min-height: calc(100vh - 75px); }

        /* SIDEBAR */
        .sidebar { width: 280px; background: var(--sidebar-bg); padding: 30px 20px; transition: all 0.3s; }
        .sidebar a { 
            display: flex; align-items: center; gap: 14px; color: #94a3b8; 
            text-decoration: none; padding: 14px 18px; border-radius: 12px; 
            margin-bottom: 6px; font-weight: 500; transition: all 0.2s; font-size: 14.5px;
        }
        .sidebar a:hover { color: #fff; background: rgba(255,255,255,0.05); }
        .sidebar a.active { background: var(--primary); color: #fff; box-shadow: 0 10px 15px -3px rgba(13, 148, 136, 0.3); }

        /* CONTENT AREA */
        .content { flex: 1; padding: 40px; max-width: 1600px; margin: 0 auto; }
        
        .page-header { margin-bottom: 40px; display: flex; justify-content: space-between; align-items: center; }
        .page-title h1 { font-size: 28px; font-weight: 800; color: #0f172a; letter-spacing: -0.5px; }
        .page-title p { color: #64748b; font-weight: 500; margin-top: 4px; }

        /* ROOMS GRID */
        .rooms-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 25px; }
        
        .card-patient {
            background: #fff; border-radius: 24px; padding: 25px;
            border: 1px solid #f1f5f9; position: relative;
            box-shadow: var(--card-shadow);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
        }
        .card-patient:hover { box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.08); }
        
        /* CHAMBRE TAG UPGRADED */
        .chambre-tag {
            position: absolute; top: 0; right: 0;
            background: #f1f5f9; color: #475569;
            padding: 10px 20px; border-bottom-left-radius: 20px; 
            font-weight: 800; font-size: 12px; letter-spacing: 0.5px;
        }

        .patient-info h2 { font-size: 20px; font-weight: 800; color: #0f172a; margin-bottom: 18px; padding-right: 60px; }
        
        .detail-row { display: flex; align-items: center; gap: 12px; margin-bottom: 10px; font-size: 14px; color: #475569; font-weight: 500; }
        .detail-row i { font-size: 16px; color: var(--primary); opacity: 0.8; }

        /* BADGES */
        .status-badge {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 6px 14px; border-radius: 10px; font-size: 11px; 
            font-weight: 800; text-transform: uppercase; margin-top: 15px;
        }
        .status-urgence { background: #fee2e2; color: #b91c1c; }
        .status-stable { background: #dcfce7; color: #15803d; }
        .status-observation { background: #fef3c7; color: #b45309; }

        .jours-count {
            margin-top: 20px; padding-top: 15px; border-top: 1px solid #f8fafc;
            font-size: 13px; font-weight: 700; color: #64748b; display: flex; align-items: center;
        }

        /* ACTIONS */
        .card-actions { display: flex; gap: 12px; margin-top: 25px; }
        .btn-action { 
            flex: 1; padding: 12px; border-radius: 12px; text-align: center; 
            text-decoration: none; font-size: 13px; font-weight: 700; transition: all 0.2s;
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-visit { background: var(--primary); color: #fff; box-shadow: 0 4px 6px rgba(13, 148, 136, 0.2); }
        .btn-visit:hover { background: var(--primary-hover); transform: scale(1.02); }
        
        .btn-exit { background: #fff; color: var(--danger); border: 1.5px solid #fee2e2; }
        .btn-exit:hover { background: #fef2f2; border-color: #fecaca; }

        /* EMPTY STATE */
        .empty-state { 
            grid-column: 1 / -1; text-align: center; padding: 80px; 
            background: #fff; border-radius: 30px; border: 2px dashed #e2e8f0; 
        }

        /* STATS PILL */
        .stat-pill {
            background: #fff; padding: 15px 25px; border-radius: 20px;
            box-shadow: var(--card-shadow); border: 1px solid #f1f5f9;
        }
    </style>
</head>
<body>

<header>
    <img src="../images/logo_app2.png" style="height: 45px; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.05));">
    <div class="user-pill">
        <div style="width: 32px; height: 32px; background: var(--primary); color: white; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
            <i class="fa-solid fa-user-doctor"></i>
        </div>
        <span>Dr. <?= strtoupper($medecin['nom']) ?></span>
    </div>
</header>

<div class="container">
       <aside class="sidebar">
        <p style="font-weight: 800;">Unité de Soins</p>
        <a href="dashboard_medecin.php" ><i class="fa-solid fa-chart-line"></i> Vue Générale</a>
        <a href="hospitalisation.php" class="active"><i class="fa-solid fa-bed-pulse"></i> Patients Admis</a>
        <a href="patients.php"><i class="fa-solid fa-hospital-user"></i> Patients</a>
        <a href="../traitement/list.php"><i class="fa-solid fa-file-prescription"></i> Traitements</a>
        <a href="suivis.php"><i class="fa-solid fa-calendar-check"></i> Consultations</a>
        <p style="font-weight: 800;">Analyse & Gestion</p>
        <a href="../admission/statistique.php"><i class="fa-solid fa-chart-pie"></i> Statistiques</a>
        <a href="archives.php"><i class="fa-solid fa-box-archive"></i> Archives</a>
        <a href="profil_medcin.php"><i class="fa-solid fa-user-gear"></i> Profil</a>
        <div style="margin-top: 40px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 10px;">
            <a href="deconnexion.php" style="color: #fda4af;"><i class="fa-solid fa-power-off"></i> Déconnexion</a>
        </div>
    </aside>

    <main class="content">
        <div class="page-header">
            <div class="page-title">
                <h1>Hospitalisations actives</h1>
                <p>Liste des patients actuellement hospitalisés.</p>
            </div>
            <div class="stat-pill">
                <span style="font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase; display: block; margin-bottom: 2px;">Capacité Unité</span>
                <div style="font-size: 24px; font-weight: 900; color: var(--primary);"><?= count($admis) ?> <small style="font-size: 14px; font-weight: 600; color: #64748b;">Patients</small></div>
            </div>
        </div>

        <div class="rooms-grid">
            <?php if (empty($admis)): ?>
                <div class="empty-state">
                    <div style="width: 80px; height: 80px; background: #f8fafc; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                        <i class="fa-solid fa-bed-pulse" style="font-size: 32px; color: #cbd5e1;"></i>
                    </div>
                    <h3 style="color: #1e293b; font-weight: 800; font-size: 20px;">Tout est calme</h3>
                    <p style="color: #64748b; font-weight: 500; margin-top: 8px;">Aucune hospitalisation active n'est enregistrée pour le moment.</p>
                </div>
            <?php else: ?>
                <?php foreach ($admis as $p): ?>
                <div class="card-patient">
                    <div class="chambre-tag"># <?= htmlspecialchars($p['chambre'] ?? 'S.A') ?></div>
                    
                    <div class="patient-info">
                        <h2><?= htmlspecialchars(strtoupper($p['nom']) . ' ' . $p['prenom']) ?></h2>
                        
                        <div class="detail-row">
                            <i class="fa-solid fa-layer-group"></i>
                            <span>Unité : <?= htmlspecialchars($p['service']) ?></span>
                        </div>
                        <div class="detail-row">
                            <i class="fa-solid fa-calendar-check"></i>
                            <span>Admis le : <?= date('d M Y', strtotime($p['date_admission'])) ?></span>
                        </div>

                        <?php 
                            $statusClass = 'status-stable';
                            $currentStatut = $p['statut'];
                            if($currentStatut == 'Urgent') $statusClass = 'status-urgence';
                            elseif($currentStatut == 'Programme') $statusClass = 'status-observation';
                        ?>
                        <div class="status-badge <?= $statusClass ?>">
                            <i class="fa-solid fa-shield-halved"></i> <?= htmlspecialchars($currentStatut) ?>
                        </div>
                    </div>

                    <div class="jours-count">
                        <i class="fa-regular fa-clock" style="margin-right: 8px; color: var(--primary);"></i>
                        Prise en charge : <span style="color: #0f172a; margin-left: 5px;"> <?= $p['jours'] ?> jour(s)</span>
                    </div>

                    <div class="card-actions">
                        <a href="dossier_patient.php?id_patient=<?= $p['id_patient'] ?>" class="btn-action btn-visit">
                            <i class="fa-solid fa-id-card"></i> Dossier
                        </a>
                        <a href="traiter_sortie.php?id_adm=<?= $p['id_admission'] ?>" 
                           class="btn-action btn-exit" 
                           onclick="return confirm('Confirmer la sortie de ce patient ?')">
                            <i class="fa-solid fa-door-open"></i> Sortie
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</div>

</body>
</html>