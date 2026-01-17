<?php
session_start();
include("../config/connexion.php");

if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit; }

$id_admission = $_GET['id_adm'] ?? null;

if (!$id_admission) { echo "Admission non trouvée"; exit; }

// 1. RÉCUPÉRATION DES INFOS (Jointure Admissions + Patients pour avoir les antécédents)
$stmt = $pdo->prepare("SELECT a.*, p.*, 
                        u.nom as med_nom, u.prenom as med_prenom 
                        FROM admissions a 
                        JOIN patients p ON a.id_patient = p.id_patient
                        LEFT JOIN utilisateurs u ON a.id_medecin = u.id_user 
                        WHERE a.id_admission = ?");
$stmt->execute([$id_admission]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

// 2. RÉCUPÉRATION DE L'HISTORIQUE DES SOINS
$stmt_soins = $pdo->prepare("SELECT s.*, u.nom as inf_nom, u.prenom as inf_prenom 
                              FROM soins_patients s
                              JOIN utilisateurs u ON s.id_infirmier = u.id_user
                              WHERE s.id_admission = ? 
                              ORDER BY s.date_soin DESC");
$stmt_soins->execute([$id_admission]);
$historique_soins = $stmt_soins->fetchAll(PDO::FETCH_ASSOC);

$nom_complet = strtoupper($data['nom'] ?? '') . ' ' . ($data['prenom'] ?? '');
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>MedCare | Dossier Patient #<?= $id_admission ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #0f766e; 
            --sidebar-bg: #0f172a;
            --bg-body: #f8fafc;
            --border-color: #e2e8f0;
        }

        body { background: var(--bg-body); font-family: 'Plus Jakarta Sans', sans-serif; color: #334155; }
        
        header { background: #fff; height: 70px; position: fixed; top: 0; width: 100%; display: flex; align-items: center; padding: 0 30px; justify-content: space-between; border-bottom: 1px solid var(--border-color); z-index: 1000; }
        .sidebar { width: 260px; background: var(--sidebar-bg); position: fixed; top: 70px; left: 0; bottom: 0; padding: 20px; z-index: 999; }
        .content { margin-left: 260px; margin-top: 70px; padding: 40px; }
        
        .sidebar a { display: flex; align-items: center; gap: 12px; color: #94a3b8; text-decoration: none; padding: 12px 15px; border-radius: 10px; margin-bottom: 5px; }
        .sidebar a:hover { background: rgba(255,255,255,0.05); color: #fff; }

        .card-custom {
            background: white; border-radius: 16px; border: 1px solid var(--border-color);
            padding: 20px; margin-bottom: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        }

        .pdf-header-only { display: none; }

        @media print {
            header, .sidebar, .btn, .d-flex.gap-2, .btn-primary, .no-print { display: none !important; }
            .content { margin-left: 0 !important; margin-top: 0 !important; padding: 0px !important; }
            body { background: #fff !important; }
            .bg-white { border: 1px solid #eee !important; box-shadow: none !important; }
            .pdf-header-only { 
                display: block !important; 
                text-align: center; 
                border-bottom: 2px solid #000; 
                margin-bottom: 30px; 
                padding-bottom: 10px;
            }
            .pdf-app-name { font-size: 12px; text-transform: uppercase; color: #555; font-weight: bold; }
            .pdf-patient-name { font-size: 28px; font-weight: 800; text-transform: uppercase; margin: 10px 0; }
            .pdf-doctor-info { font-size: 14px; margin-bottom: 10px; border: 1px solid #ddd; padding: 10px; border-radius: 8px; }
        }
    </style>
</head>
<body>

<header>
    <img src="../images/logo_app2.png" style="height: 38px;">
    <div class="d-flex align-items-center gap-3">
        <span class="badge bg-light text-dark border">Session : <?= ucfirst($_SESSION['role'] ?? 'Utilisateur') ?></span>
    </div>
</header>

<aside class="sidebar">
    <h3 style="color:rgba(255,255,255,0.3); font-size:11px; text-transform:uppercase; margin-bottom:20px; padding-left:12px;">Menu Infirmier</h3>
    <a href="dashboard_infirmier.php"><i class="fa-solid fa-chart-line"></i> Tableau de bord</a>
    <a href="liste_patients_inf.php" class="active"><i class="fa-solid fa-user-injured"></i> Liste des Patients</a>
    <a href="saisir_soins.php"><i class="fa-solid fa-notes-medical"></i> Saisir un Soin</a>
    <a href="planning.php"><i class="fa-solid fa-calendar-alt"></i> Planning</a>
    <div style="height: 1px; background: rgba(255,255,255,0.1); margin: 20px 0;"></div>
    <a href="../connexio_utilisateur/deconnexion.php" style="color: #fda4af;"><i class="fa-solid fa-power-off"></i> Déconnexion</a>
</aside>

<main class="content">

    <div class="pdf-header-only">
        <div class="pdf-app-name">Medical Services - Dossier de Surveillance Clinique</div>
        <div class="pdf-patient-name"><?= $nom_complet ?></div>
        <div class="pdf-doctor-info">
            <strong>Médecin :</strong> Dr. <?= strtoupper($data['med_nom'] ?? '') ?> <?= $data['med_prenom'] ?? '' ?> | 
            <strong>Groupe :</strong> <?= $data['groupe_sanguin'] ?? 'N/A' ?>
        </div>
    </div>

    <div class="bg-white rounded-4 border-0 shadow-sm p-3 mb-4 d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-3">
            <div class="d-flex align-items-center justify-content-center fw-bold text-white shadow-sm" 
                  style="width: 50px; height: 50px; background: linear-gradient(135deg, var(--primary), #14b8a6); border-radius: 14px; font-size: 1.2rem;">
                <?= substr($data['nom'] ?? 'P', 0, 1) ?>
            </div>
            <div>
                <div class="d-flex align-items-center gap-2">
                    <h4 class="mb-0 fw-bold text-dark"><?= $nom_complet ?></h4>
                    <span class="badge rounded-pill bg-success-subtle text-success px-2 py-1" style="font-size: 0.65rem;">ADMIS</span>
                    <?php if(!empty($data['groupe_sanguin'])): ?>
                        <span class="badge bg-danger text-white rounded-pill px-2 py-1" style="font-size: 0.65rem;">
                            <i class="fa-solid fa-droplet"></i> <?= $data['groupe_sanguin'] ?>
                        </span>
                    <?php endif; ?>
                </div>
                <div class="d-flex gap-3 mt-1" style="font-size: 0.75rem; color: #64748b;">
                    <span><b class="text-dark">CIN :</b> <?= $data['CIN'] ?? 'N/A' ?></span>
                    <span><b class="text-dark">ÂGE :</b> <?= isset($data['date_naissance']) ? date_diff(date_create($data['date_naissance']), date_create('today'))->y : '?' ?> ans</span>
                    <span><b class="text-dark">ID :</b> #<?= $id_admission ?></span>
                </div>
            </div>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-light btn-sm text-secondary fw-bold rounded-3 border no-print">
                <i class="fa-solid fa-print"></i>
            </button>
            <a href="saisir_soins.php?id_adm=<?= $id_admission ?>" class="btn btn-primary btn-sm px-4 fw-bold rounded-3 shadow-sm border-0 no-print" style="background: var(--primary);">
                <i class="fa-solid fa-plus-circle me-2"></i> AJOUTER UN SOIN
            </a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="bg-white rounded-4 shadow-sm p-4 border-0 mb-4">
                <h6 class="fw-bold mb-3 d-flex align-items-center gap-2" style="font-size: 0.85rem; color: #1e293b;">
                    <i class="fa-solid fa-user-shield text-primary"></i> INFORMATIONS MÉDICALES (DOC)
                </h6>
                
                <div class="mb-3">
                    <label class="d-block text-muted fw-bold mb-1" style="font-size: 0.65rem; text-transform: uppercase;">Médecin Traitant</label>
                    <div class="d-flex align-items-center gap-2 p-2 rounded-3 bg-light border">
                        <i class="fa-solid fa-user-md text-primary ms-1"></i>
                        <span class="fw-bold text-dark" style="font-size: 0.85rem;">Dr. <?= $data['med_prenom'] ?? '' ?> <?= $data['med_nom'] ?? '' ?></span>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="d-block text-muted fw-bold mb-1" style="font-size: 0.65rem; text-transform: uppercase;">Antécédents du Patient</label>
                    <div class="p-3 rounded-3 bg-light border" style="font-size: 0.8rem; line-height: 1.5;">
                        <?= !empty($data['antecedents']) ? nl2br(htmlspecialchars($data['antecedents'])) : '<span class="text-muted">Aucun antécédent enregistré</span>' ?>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="d-block text-muted fw-bold mb-1" style="font-size: 0.65rem; text-transform: uppercase;">Interventions Chirurgicales</label>
                    <div class="p-3 rounded-3 bg-light border" style="font-size: 0.8rem; line-height: 1.5;">
                        <?= !empty($data['operations']) ? nl2br(htmlspecialchars($data['operations'])) : '<span class="text-muted">Aucune opération notée</span>' ?>
                    </div>
                </div>

                <div class="p-3 rounded-4" style="background: #fff1f2; border: 1px dashed #fda4af;">
                    <label class="d-block text-danger fw-bold mb-1" style="font-size: 0.65rem; text-transform: uppercase;">
                        <i class="fa-solid fa-circle-exclamation"></i> Allergies Signalées
                    </label>
                    <span class="text-danger fw-bold" style="font-size: 0.85rem;">
                        <?= !empty($data['allergies']) ? htmlspecialchars($data['allergies']) : 'Aucune allergie connue' ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="bg-white rounded-4 shadow-sm p-4 border-0">
                <h6 class="fw-bold mb-4" style="font-size: 0.85rem; color: #1e293b;">
                    <i class="fa-solid fa-clock-rotate-left text-primary me-2"></i> HISTORIQUE DE SURVEILLANCE INFIRMIÈRE
                </h6>

                <div class="timeline-container">
                    <?php if (empty($historique_soins)): ?>
                        <div class="text-center py-5 opacity-50">
                            <i class="fa-solid fa-folder-open fa-2x mb-2"></i>
                            <p class="small">Aucun acte de soin enregistré pour le moment.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($historique_soins as $soin): ?>
                        <div class="d-flex gap-3 mb-4">
                            <div class="text-end" style="min-width: 60px;">
                                <div class="fw-bold text-dark" style="font-size: 0.85rem;"><?= date('H:i', strtotime($soin['date_soin'])) ?></div>
                                <div class="text-muted" style="font-size: 0.65rem;"><?= date('d/m', strtotime($soin['date_soin'])) ?></div>
                            </div>
                            <div class="d-flex flex-column align-items-center">
                                <div class="rounded-circle bg-primary" style="width: 10px; height: 10px; margin-top: 5px;"></div>
                                <div class="bg-light flex-grow-1" style="width: 2px; margin: 5px 0;"></div>
                            </div>
                            <div class="bg-light rounded-4 p-3 flex-grow-1 border-0">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <span class="fw-bold text-primary" style="font-size: 0.9rem;"><?= htmlspecialchars($soin['type_acte']) ?></span>
                                    <span class="badge bg-white text-muted border fw-normal" style="font-size: 0.6rem;">Inf. <?= htmlspecialchars($soin['inf_prenom']) ?></span>
                                </div>
                                <div class="d-flex gap-2 mb-2">
                                    <?php if($soin['temperature']): ?>
                                        <span class="bg-white px-2 py-1 rounded-2 border small fw-bold text-danger">
                                            <i class="fa-solid fa-temperature-high me-1"></i><?= $soin['temperature'] ?>°C
                                        </span>
                                    <?php endif; ?>
                                    <?php if($soin['tension']): ?>
                                        <span class="bg-white px-2 py-1 rounded-2 border small fw-bold text-primary">
                                            <i class="fa-solid fa-heart-pulse me-1"></i><?= $soin['tension'] ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <?php if($soin['observations']): ?>
                                    <p class="mb-0 text-secondary" style="font-size: 0.8rem; font-style: italic;">
                                        "<?= htmlspecialchars($soin['observations']) ?>"
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>
</body>
</html>