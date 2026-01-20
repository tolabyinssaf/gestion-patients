<?php
session_start();
include "../config/connexion.php";

// Sécurité Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { 
    header("Location: ../login.php"); 
    exit; 
}

$id_chambre = $_GET['id'] ?? null;
if (!$id_chambre) { header("Location: gestion_chambres.php"); exit(); }

// 1. Infos Techniques de la chambre
$stmt = $pdo->prepare("SELECT * FROM chambres WHERE id_chambre = ?");
$stmt->execute([$id_chambre]);
$chambre = $stmt->fetch(PDO::FETCH_ASSOC);

// 2. Monitoring Patients
$stmt_patients = $pdo->prepare("
    SELECT a.*, p.nom, p.prenom, p.sexe, p.date_naissance, p.groupe_sanguin,
    (SELECT description FROM admission_logs WHERE id_admission = a.id_admission ORDER BY created_at DESC LIMIT 1) as dernier_log
    FROM admissions a
    JOIN patients p ON a.id_patient = p.id_patient
    WHERE a.id_chambre = ? AND a.statut = 'En cours'
");
$stmt_patients->execute([$id_chambre]);
$patients = $stmt_patients->fetchAll(PDO::FETCH_ASSOC);

// 3. Activity Feed
$stmt_logs = $pdo->prepare("
    SELECT al.*, p.nom as p_nom
    FROM admission_logs al
    LEFT JOIN admissions a ON al.id_admission = a.id_admission
    LEFT JOIN patients p ON a.id_patient = p.id_patient
    WHERE a.id_chambre = ? OR al.description LIKE ?
    ORDER BY al.created_at DESC LIMIT 8
");
$stmt_logs->execute([$id_chambre, "%Unité ".$chambre['numero_chambre']."%"]);
$logs = $stmt_logs->fetchAll(PDO::FETCH_ASSOC);

$occ = count($patients);
$taux = ($chambre['capacite'] > 0) ? round(($occ / $chambre['capacite']) * 100) : 0;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Monitoring Unité <?= $chambre['numero_chambre'] ?> | MedCare</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { 
            --primary: #0f766e; 
            --primary-light: #f0fdfa;
            --sidebar-bg: #0f172a; 
            --bg-body: #f1f5f9; 
            --border: #e2e8f0; 
        }
        
        body { 
            background: var(--bg-body); 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            color: #1e293b; 
        }

        /* Structure */
        header { 
            background: white; 
            padding: 0 40px; 
            height: 75px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            border-bottom: 1px solid var(--border); 
            position: fixed; 
            width: 100%; 
            top: 0; 
            z-index: 1000;
        }
        .wrapper { 
            display: flex; 
            padding-top: 75px; 
        }
        .sidebar { 
            width: 260px; 
            background: var(--sidebar-bg); 
            height: calc(100vh - 75px); 
            position: fixed; 
            padding: 24px 16px; 
        }
        .sidebar a { 
            display: flex; 
            align-items: center; 
            gap: 12px; 
            color: #94a3b8; 
            text-decoration: none; 
            padding: 12px 16px; 
            border-radius: 10px; 
            margin-bottom: 5px; 
            transition: 0.3s; 
        }
        .sidebar a.active { 
            background: var(--primary); 
            color: white; 
        }
        .main-content { 
            margin-left: 260px; 
            padding: 30px; 
            width: calc(100% - 260px); 
        }

        /* Medical UI Components */
        .medical-card { 
            background: white; 
            border-radius: 16px; 
            border: none; 
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); 
            padding: 20px; 
            height: 100%; 
        }
        
        .header-unit {
            background: linear-gradient(135deg, #0f766e 0%, #14b8a6 100%);
            color: white; 
            border-radius: 16px; 
            padding: 20px 25px; 
            margin-bottom: 25px;
            box-shadow: 0 4px 12px rgba(15, 118, 110, 0.15);
        }

        .stat-icon { 
            width: 40px; 
            height: 40px; 
            border-radius: 10px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            background: var(--primary-light); 
            color: var(--primary); 
            font-size: 1rem; 
        }
        
        .patient-row { 
            border-radius: 12px; 
            transition: 0.2s; 
            border: 1px solid transparent; 
            background: #f8fafc; 
            margin-bottom: 10px; 
            padding: 15px; 
        }
        .patient-row:hover { 
            border-color: var(--primary); 
            background: white; 
            box-shadow: 0 2px 8px rgba(0,0,0,0.05); 
        }

        .blood-badge { 
            background: #fee2e2; 
            color: #b91c1c; 
            font-weight: 800; 
            padding: 4px 10px; 
            border-radius: 8px; 
            font-size: 11px; 
        }
        
        .timeline-item { 
            border-left: 2px solid var(--border); 
            padding-left: 16px; 
            position: relative; 
            padding-bottom: 12px; 
            margin-bottom: 12px;
        }
        .timeline-item::before { 
            content: ''; 
            position: absolute; 
            left: -7px; 
            top: 0; 
            width: 12px; 
            height: 12px; 
            background: var(--primary); 
            border-radius: 50%; 
            border: 2px solid white; 
        }

        .progress { 
            background: rgba(255,255,255,0.1); 
            height: 6px; 
            border-radius: 10px; 
        }
        .user-pill { 
            background: #f0fdfa; 
            padding: 8px 18px; 
            border-radius: 12px; 
            color: var(--primary); 
            font-weight: 600; 
            display: flex; 
            align-items: center; 
            gap: 10px; 
        }
        
        /* Nouveaux styles pour la nouvelle structure */
        .unit-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .maintenance-card {
            border-left: 4px solid #f59e0b;
            margin-top: 20px;
        }
        
        .occupation-badge {
            font-size: 14px;
            padding: 6px 12px;
        }
    </style>
</head>
<body>

<header>
    <img src="../images/logo_app2.png" alt="Logo" style="height: 45px;">
    <div class="user-pill">
        <i class="fa-solid fa-user-shield"></i>
        <span>ADMIN : <?= strtoupper($_SESSION['role']) ?></span>
    </div>
</header>

<div class="wrapper">
    <aside class="sidebar">
        <a href="../connexion_admin/dashboard_admin.php"><i class="fa-solid fa-chart-pie"></i> Vue Générale</a>
        <a href="../connexion_admin/utilisateurs.php"><i class="fa-solid fa-user-md"></i> Utilisateurs</a>
        <a href="../connexion_admin/prestations.php"><i class="fa-solid fa-list-check"></i> Actes & Tarifs</a>
        <a href="gestion_chambres.php" class="active"><i class="fa-solid fa-bed"></i> Gestion Chambres</a>
        <div style="height: 1px; background: rgba(255,255,255,0.1); margin: 20px 0;"></div>
        <a href="../connexion_admin/facturation_list.php"><i class="fa-solid fa-file-invoice-dollar"></i> Rapports Financiers</a>
        <a href="../connexion_admin/archives.php"><i class="fa-solid fa-box-archive"></i> Archives</a>
          <a href="../connexion_admin/profil.php" class="<?= basename($_SERVER['PHP_SELF']) == 'profil.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-user-gear"></i> Mon Profil
        </a>
        <a href="../connexio_utilisateur/login.php" style="color: #fda4af; margin-top: 20px;"><i class="fa-solid fa-power-off"></i> Déconnexion</a>
    </aside>


    <main class="main-content">
        <!-- En-tête compact -->
        <div class="header-unit">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <span class="badge bg-white text-primary mb-2 text-uppercase"><?= $chambre['service'] ?></span>
                    <h3 class="fw-700 mb-1">Unité <?= $chambre['numero_chambre'] ?></h3>
                    <p class="mb-0 text-white-80" style="opacity: 0.9;">
                        <i class="fa-solid fa-layer-group me-2"></i> Bloc <?= $chambre['bloc'] ?> • Étage <?= $chambre['etage'] ?>
                    </p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="d-inline-block text-start bg-white bg-opacity-15 p-3 rounded-3" style="min-width: 180px; backdrop-filter: blur(10px);">
                        <div class="d-flex justify-content-between mb-2">
                            <small class="fw-bold text-white-80">OCCUPATION</small>
                            <small class="fw-bold text-white"><?= $taux ?>%</small>
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg-white" style="width: <?= $taux ?>%"></div>
                        </div>
                        <small class="text-white-80 mt-1 d-block">
                            <?= $occ ?> patient<?= $occ > 1 ? 's' : '' ?> sur <?= $chambre['capacite'] ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grille de statistiques -->
        <div class="unit-stats-grid">
            <div class="medical-card">
                <h6 class="fw-700 mb-3 text-uppercase small text-muted">Configuration Unité</h6>
                
                <div class="d-flex align-items-center mb-3">
                    <div class="stat-icon me-3"><i class="fa-solid fa-bed"></i></div>
                    <div>
                        <small class="text-muted d-block">Capacité</small>
                        <span class="fw-bold"><?= $chambre['capacite'] ?> Lits</span>
                    </div>
                </div>

                <div class="d-flex align-items-center mb-3">
                    <div class="stat-icon me-3 text-info"><i class="fa-solid fa-mask-ventilator"></i></div>
                    <div>
                        <small class="text-muted d-block">Oxygène Central</small>
                        <span class="fw-bold <?= $chambre['oxigene'] ? 'text-success' : 'text-danger' ?>">
                            <?= $chambre['oxigene'] ? 'Disponible' : 'Non équipé' ?>
                        </span>
                    </div>
                </div>

                <div class="d-flex align-items-center">
                    <div class="stat-icon me-3 text-warning"><i class="fa-solid fa-screwdriver-wrench"></i></div>
                    <div>
                        <small class="text-muted d-block">État Technique</small>
                        <span class="badge bg-<?= $chambre['etat'] == 'libre' ? 'success' : ($chambre['etat'] == 'maintenance' ? 'warning' : 'danger') ?> rounded-pill px-3">
                            <?= strtoupper($chambre['etat']) ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="medical-card maintenance-card">
                <h6 class="fw-700 mb-3 small text-uppercase text-muted">MAINTENANCE</h6>
                <p class="small text-muted mb-2">Dernier passage hygiène :</p>
                <p class="fw-bold mb-3">Aujourd'hui, 08:30</p>
                <a href="action_maintenance.php?id=<?= $id_chambre ?>&etat=<?= $chambre['etat'] == 'maintenance' ? 'libre' : 'maintenance' ?>" 
                   class="btn btn-<?= $chambre['etat'] == 'maintenance' ? 'success' : 'warning' ?> w-100 fw-bold rounded-pill">
                    <i class="fa-solid fa-<?= $chambre['etat'] == 'maintenance' ? 'check-circle' : 'triangle-exclamation' ?> me-2"></i>
                    <?= $chambre['etat'] == 'maintenance' ? 'Remettre en service' : 'Signaler une panne' ?>
                </a>
            </div>
        </div>

        <!-- Section principale -->
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="medical-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-700 m-0">
                            <i class="fa-solid fa-hospital-user me-2 text-primary"></i>
                            Patients Actuels
                            <span class="badge bg-primary rounded-pill ms-2"><?= $occ ?></span>
                        </h5>
                        <span class="badge bg-light text-dark rounded-pill border px-3 occupation-badge">
                            <?= $occ ?> / <?= $chambre['capacite'] ?> lits
                        </span>
                    </div>

                    <?php if(empty($patients)): ?>
                        <div class="text-center py-5">
                            <div class="mb-3">
                                <i class="fa-solid fa-bed fa-3x text-muted" style="opacity: 0.3;"></i>
                            </div>
                            <p class="text-muted mb-2">Aucun patient n'occupe cette unité actuellement.</p>
                            <small class="text-muted">Tous les lits sont disponibles</small>
                        </div>
                    <?php else: ?>
                        <div class="row g-3">
                            <?php foreach($patients as $p): ?>
                                <div class="col-12">
                                    <div class="patient-row">
                                        <div class="row align-items-center">
                                            <div class="col-md-5">
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" 
                                                         style="width: 38px; height: 38px; font-weight: 700; font-size: 14px;">
                                                        <?= substr($p['nom'], 0, 1) ?>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold"><?= strtoupper($p['nom']) ?> <?= $p['prenom'] ?></div>
                                                        <small class="text-muted">ID: #<?= $p['id_patient'] ?> • <?= $p['sexe'] ?></small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3 text-center">
                                                <span class="blood-badge"><?= $p['groupe_sanguin'] ?></span>
                                                <small class="d-block text-muted mt-1">Groupe sanguin</small>
                                            </div>
                                            <div class="col-md-4 text-end">
                                                <a href="dossier_patient.php?id=<?= $p['id_patient'] ?>" 
                                                   class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                                    Voir dossier <i class="fa-solid fa-arrow-right ms-1"></i>
                                                </a>
                                            </div>
                                            <?php if($p['dernier_log']): ?>
                                            <div class="col-12 mt-3">
                                                <div class="p-2 bg-light rounded border-start border-3 border-info">
                                                    <i class="fa-solid fa-clipboard-check me-2 text-info"></i>
                                                    <small><?= $p['dernier_log'] ?></small>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="medical-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h6 class="fw-700 m-0 text-uppercase small">Flux d'activité</h6>
                        <span class="badge bg-primary rounded-pill px-2"><?= count($logs) ?></span>
                    </div>
                    
                    <div class="clinical-timeline" style="max-height: 400px; overflow-y: auto;">
                        <?php foreach($logs as $log): ?>
                            <div class="timeline-item">
                                <div class="d-flex justify-content-between small mb-1">
                                    <span class="fw-700 text-primary"><?= $log['action'] ?></span>
                                    <span class="text-muted"><?= date('H:i', strtotime($log['created_at'])) ?></span>
                                </div>
                                <p class="small mb-1 fw-600" style="line-height: 1.4;"><?= $log['description'] ?></p>
                                <?php if($log['p_nom']): ?>
                                    <small class="text-muted">
                                        <i class="fa-solid fa-user-tag me-1"></i>
                                        <?= $log['p_nom'] ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <button class="btn btn-outline-primary w-100 rounded-pill btn-sm mt-3 fw-bold" 
                            data-bs-toggle="modal" 
                            data-bs-target="#modalHistorique">
                        <i class="fa-solid fa-history me-2"></i>Voir l'historique complet
                    </button>
                </div>
            </div>
        </div>
    </main>
</div>

<div class="modal fade" id="modalHistorique" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-0 px-4 pt-4">
                <h5 class="fw-700 mb-0">
                    <i class="fa-solid fa-clock-rotate-left me-2 text-primary"></i>
                    Historique de l'Unité <?= $chambre['numero_chambre'] ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-muted small mb-4">
                    <i class="fa-solid fa-info-circle me-1"></i>
                    Les événements sont conservés pendant 90 jours.
                </p>
                <!-- Contenu de l'historique complet ici -->
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>