<?php
session_start();
include("../config/connexion.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'medecin') {
    header("Location: login.php");
    exit;
}

$id_medecin = $_SESSION['user_id'];
$id_patient = $_GET['id'] ?? null;

$id_patient = $_POST['id_patient'] ?? $_GET['id'] ?? $_GET['id_patient'] ?? null;

if (!$id_patient) die("Patient non spécifié");

/* Infos patient */
$stmt = $pdo->prepare("SELECT * FROM patients WHERE id_patient = ? AND id_medecin = ?");
$stmt->execute([$id_patient, $id_medecin]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$patient) die("Patient introuvable");

/* --- NOUVEAU : Récupérer l'historique des statuts pour le Modal --- */
$stmt_hist = $pdo->prepare("SELECT * FROM historique_statuts WHERE id_patient = ? ORDER BY date_changement DESC");
$stmt_hist->execute([$id_patient]);
$historique_statuts = $stmt_hist->fetchAll(PDO::FETCH_ASSOC);

/* Vérifier si le patient est actuellement hospitalisé */
$stmt_adm = $pdo->prepare("
    SELECT a.id_chambre, a.service, c.numero_chambre 
    FROM admissions a
    LEFT JOIN chambres c ON a.id_chambre = c.id_chambre
    WHERE a.id_patient = ? AND a.date_sortie IS NULL 
    LIMIT 1
");
$stmt_adm->execute([$id_patient]);
$current_adm = $stmt_adm->fetch();

/* Suivis */
$stmt = $pdo->prepare("SELECT id_suivi, date_suivi, commentaire, status FROM suivis WHERE id_patient = ? AND id_medecin = ? ORDER BY date_suivi DESC");
$stmt->execute([$id_patient, $id_medecin]);
$suivis = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Traitements */
$stmt = $pdo->prepare("SELECT * FROM traitements WHERE id_patient = ? ORDER BY date_traitement DESC");
$stmt->execute([$id_patient]);
$traitements = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Infos médecin */
$stmt = $pdo->prepare("SELECT prenom, nom FROM utilisateurs WHERE id_user = ?");
$stmt->execute([$id_medecin]);
$medecin = $stmt->fetch(PDO::FETCH_ASSOC);

/* Récupérer les antécédents enregistrés */
$stmt = $pdo->prepare("SELECT * FROM antecedents WHERE id_patient = ? ORDER BY date_enregistrement DESC");
$stmt->execute([$id_patient]);
$all_antecedents = $stmt->fetchAll(PDO::FETCH_ASSOC);

$today = date('Y-m-d');
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Dossier Patient | MedCare</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
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
            --accent-dark: #334155; 
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

        .wrapper { display: flex; min-height: calc(100vh - 75px); }

        .sidebar { width: 260px; background: var(--sidebar-bg); padding: 24px 16px; flex-shrink: 0;position: sticky;
    top: 75px; 
    height: calc(100vh - 75px);
    overflow-y: auto; }
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

        .content { flex: 1; padding: 40px; }

        .patient-card { 
            background: var(--white); 
            border-radius: 20px; 
            padding: 30px; 
            border: 1px solid var(--border); 
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); 
            margin-bottom: 30px;
            border-left: 5px solid var(--primary);
        }
        .avatar-box { width: 80px; height: 80px; border-radius: 15px; background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 35px; }
        
        .nav-tabs { border: none; background: #e2e8f0; padding: 5px; border-radius: 12px; display: inline-flex; margin-bottom: 25px; }
        .nav-tabs .nav-link { border: none; color: var(--text-muted); border-radius: 8px; padding: 10px 24px; font-weight: 600; font-size: 14px; transition: 0.3s; }
        .nav-tabs .nav-link.active { background: var(--white); color: var(--primary); box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        
        .content-card { background: var(--white); border-radius: 20px; padding: 25px; border: 1px solid var(--border); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
        .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .bg-termine { background: #fee2e2; color: #dc2626; }
        .bg-encours { background: #dcfce7; color: #16a34a; }
        
        .info-label { color: var(--text-muted); font-size: 11px; text-transform: uppercase; letter-spacing: 0.8px; font-weight: 700; }
        .info-value { font-weight: 600; color: var(--text-main); margin-top: 2px; }
        
        .btn-medical { background: var(--primary); color: var(--white); border: none; padding: 12px 25px; border-radius: 12px; font-weight: 600; transition: 0.3s; text-decoration: none; display: inline-block;}
        .btn-medical:hover { background: var(--primary-hover); color: var(--white); transform: translateY(-2px); box-shadow: 0 5px 15px rgba(15, 118, 110, 0.3); }

        .btn-add-action {
            background: var(--white); color: var(--primary); border: 2px solid var(--primary);
            padding: 8px 20px; border-radius: 10px; font-weight: 600; font-size: 13px;
            transition: 0.3s; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;
        }
        .btn-add-action:hover { background: var(--primary); color: white; }

        .icon-circle {
            width: 32px; height: 32px; background: var(--primary-light); color: var(--primary);
            border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-right: 10px;
        }

        .btn-check:checked + .btn-outline-oui { background-color: var(--accent-dark); color: white; border-color: var(--accent-dark); }
        .btn-check:checked + .btn-outline-non { background-color: var(--text-muted); color: white; border-color: var(--text-muted); }
        .ante-item { border-bottom: 1px solid #f1f5f9; padding: 12px 0; }
        .ante-item:last-child { border: none; }

        /* Styles Timeline Modal */
       /* Timeline Moderne */
.timeline-modern {
    position: relative;
    padding: 10px 0;
}

.timeline-modern::before {
    content: '';
    position: absolute;
    left: 19px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e2e8f0;
    border-radius: 2px;
}

.timeline-item-modern {
    position: relative;
    padding-left: 45px;
    margin-bottom: 25px;
}

.timeline-item-modern:last-child {
    margin-bottom: 0;
}

.timeline-point {
    position: absolute;
    left: 12px;
    top: 4px;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: var(--white);
    border: 3px solid var(--primary);
    z-index: 1;
}

.timeline-content {
    background: #f8fafc;
    padding: 15px;
    border-radius: 12px;
    border: 1px solid var(--border);
    transition: all 0.2s ease;
}

.timeline-content:hover {
 
    background: var(--white);
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

/* Couleurs dynamiques selon le statut */
.status-stable { border-left: 4px solid #10b981; }
.status-critique { border-left: 4px solid #ef4444; }
.status-observation { border-left: 4px solid #f59e0b; }
.status-urgent { border-left: 4px solid #7c3aed; }

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

<div class="wrapper">
       <aside class="sidebar">
        <h3 style="font-weight: 800;">Unité de Soins</h3>
        <a href="dashboard_medecin.php" ><i class="fa-solid fa-chart-line"></i> Vue Générale</a>
        <a href="hospitalisation.php"><i class="fa-solid fa-bed-pulse"></i> Patients Admis</a>
        <a href="patients.php" class="active"><i class="fa-solid fa-hospital-user"></i> Patients</a>
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
        <div class="patient-card">
            <div class="row align-items-center">
                <div class="col-auto">
                    <div class="avatar-box shadow-sm">
                        <i class="fa-solid fa-user"></i>
                    </div>
                </div>
                <div class="col">
                    <?php if ($current_adm): ?>
                        <span class="badge mb-2 shadow-sm" style="background: #fffbeb; color: #92400e; border: 1px solid #fde68a;">
                            <i class="fa-solid fa-bed-pulse me-2"></i> Hospitalisé : Ch. <?= $current_adm['numero_chambre'] ?> (<?= $current_adm['service'] ?>)
                        </span>
                    <?php endif; ?>
                    <h2 class="fw-bold mb-1" style="color: var(--primary);"><?= htmlspecialchars($patient['prenom'].' '.$patient['nom']) ?></h2>
                    <div class="d-flex gap-4">
                        <span class="small text-muted"><i class="fa-solid fa-cake-candles me-2 text-primary"></i><?= htmlspecialchars($patient['date_naissance']) ?></span>
                        <span class="small text-muted"><i class="fa-solid fa-id-card me-2 text-primary"></i>CIN: <?= htmlspecialchars($patient['CIN'] ?? $patient['CIN'] ?? 'N/A') ?></span>
                    </div>
                </div>
                <div class="col-auto">
                    <a href="modifier_patient.php?id=<?= $id_patient ?>" class="btn btn-outline-secondary rounded-pill btn-sm">
                        <i class="fa-solid fa-pen-to-square me-2"></i>Modifier Profil
                    </a>
                </div>
            </div>

            <hr class="my-4" style="border-top: 1px dashed var(--border);">

            <div class="row g-3">
                <div class="col-md-3">
                    <div class="info-label">Téléphone</div>
                    <div class="info-value"><i class="fa-solid fa-phone me-2 text-muted"></i><?= htmlspecialchars($patient['telephone']) ?></div>
                </div>
                <div class="col-md-3">
                    <div class="info-label">Email</div>
                    <div class="info-value"><i class="fa-solid fa-envelope me-2 text-muted"></i><?= htmlspecialchars($patient['email']) ?></div>
                </div>
                <div class="col-md-3">
                    <div class="info-label">Adresse</div>
                    <div class="info-value"><i class="fa-solid fa-location-dot me-2 text-muted"></i><?= htmlspecialchars($patient['adresse']) ?></div>
                </div>
                <div class="col-md-3">
                    <div class="info-label">Inscription</div>
                    <div class="info-value"><i class="fa-solid fa-calendar-check me-2 text-muted"></i><?= htmlspecialchars($patient['date_inscription']) ?></div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="p-3 rounded-4 border bg-white shadow-sm d-flex align-items-center h-100">
                    <div class="icon-circle shadow-sm"><i class="fa-solid fa-droplet text-danger"></i></div>
                    <div class="flex-grow-1">
                        <div class="info-label">Groupe Sanguin</div>
                        <div class="info-value text-danger fw-bold">
                            <?= !empty($patient['groupe_sanguin']) ? htmlspecialchars($patient['groupe_sanguin']) : "Non défini" ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="p-3 rounded-4 border bg-white shadow-sm d-flex align-items-center h-100" 
                     style="cursor: pointer;" data-bs-toggle="modal" data-bs-target="#modalHistorique">
                    <div class="icon-circle shadow-sm"><i class="fa-solid fa-heart-pulse text-primary"></i></div>
                    <div class="flex-grow-1">
                        <div class="info-label">Statut Actuel <i class="fa-solid fa-clock-rotate-left ms-1" style="font-size: 10px;"></i></div>
                        <div class="info-value text-primary fw-bold">
                            <?= !empty($patient['statut']) ? htmlspecialchars($patient['statut']) : "Stable" ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="p-3 rounded-4 border bg-white shadow-sm d-flex align-items-center h-100" style="border-left: 4px solid #f87171 !important;">
                    <i class="fa-solid fa-triangle-exclamation text-danger me-3 fs-4"></i>
                    <div class="flex-grow-1">
                        <div class="info-label">Allergies & Vigilance</div>
                        <div class="info-value text-danger" style="font-size: 13px;">
                            <?= !empty($patient['allergies']) ? htmlspecialchars($patient['allergies']) : "Aucune allergie signalée." ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <ul class="nav nav-tabs" id="patientTab" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="tab" href="#suivis-tab"><i class="fa-solid fa-clipboard-list me-2"></i>Consultations</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#anamnese-tab"><i class="fa-solid fa-history me-2"></i>Antécédents</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#traitements-tab"><i class="fa-solid fa-pills me-2"></i>Traitements</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#documents-tab"><i class="fa-solid fa-file-pdf me-2"></i>Rapport PDF</a>
            </li>
        </ul>

        <div class="tab-content">
            <div id="suivis-tab" class="tab-pane fade show active">
                <div class="content-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-bold mb-0">Journal des Consultations</h5>
                        <a href="ajouter_suivi.php?id_patient=<?= $id_patient ?>" class="btn-add-action">
                            <i class="fa-solid fa-plus-circle"></i> Nouveau Suivi
                        </a>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr class="text-muted small">
                                    <th>DATE</th>
                                    <th>OBSERVATIONS MÉDICALES</th>
                                    <th>STATUT</th>
                                    <th class="text-end">ACTION</th>
                                </tr>
                            </thead>
                              <tbody>
                                <?php if($suivis): foreach($suivis as $s): 
                                    // Récupération du statut depuis la base de données
                                    $statut = !empty($s['status']) ? trim($s['status']) : 'En cours';
                                    $est_termine = (strtolower($statut) === 'terminé' || strtolower($statut) === 'termine');
                                ?>
                                <tr>
                                    <td class="fw-bold text-dark">
                                        <?= date('d/m/Y', strtotime($s['date_suivi'])) ?>
                                    </td>
                                    
                                    <td>
                                        <div class="text-muted small">
                                            <?= nl2br(htmlspecialchars($s['commentaire'])) ?>
                                        </div>
                                    </td>
                                    
                                    <td>
                                        <?php if ($est_termine): ?>
                                            <span class="status-badge bg-termine">
                                                <i class="fa-solid fa-check-circle me-1"></i> Terminé
                                            </span>
                                        <?php else: ?>
                                            <span class="status-badge bg-encours">
                                                <i class="fa-solid fa-clock me-1"></i> En cours
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td class="text-end">
                                        <div class="btn-group shadow-sm rounded">
                                            <?php if (!$est_termine): ?>
                                                <!-- Crayon pour modifier si statut "En cours" -->
                                                <a href="modifier_suivi.php?id=<?= $s['id_suivi'] ?>" 
                                                   class="btn btn-sm btn-light border-0" 
                                                   title="Modifier le suivi">
                                                    <i class="fa-solid fa-pen text-primary"></i>
                                                </a>
                                            <?php else: ?>
                                                <!-- Corbeille pour supprimer si statut "Terminé" -->
                                               <a href="supprimer_suivi.php?id_suivi=<?= $s['id_suivi'] ?>&id_patient=<?= $id_patient ?>" 
   class="btn btn-sm btn-light border-0" 
   onclick="return confirm('Voulez-vous vraiment supprimer ce suivi terminé ?');"
   title="Supprimer le suivi">
    <i class="fa-solid fa-trash text-danger"></i>
</a>

                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted small">
                                            Aucun suivi enregistré.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div id="anamnese-tab" class="tab-pane fade">
                <div class="content-card">
                    <form action="sauvegarder_antecedents.php" method="POST">
                        <input type="hidden" name="id_patient" value="<?= $id_patient ?>">
                        <div class="row">
                            <div class="col-md-6 border-end">
                                <h6 class="fw-bold mb-4" style="color: var(--primary);"><i class="fa-solid fa-microscope me-2"></i>Pathologies Chroniques</h6>
                                <?php 
                                $pathologies_fixes = ['Diabète', 'Hypertension (HTA)', 'Asthme / Respiratoire', 'Maladie Cardiaque', 'Antécédents Néoplasiques'];
                                $deja_presents = [];
                                foreach($all_antecedents as $a) {
                                    if($a['categorie'] == 'Médical') $deja_presents[$a['nom_pathologie']] = $a['description'];
                                }

                                foreach($pathologies_fixes as $label): 
                                    $is_checked = isset($deja_presents[$label]);
                                ?>
                                <div class="ante-item py-3 border-bottom">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="fw-medium <?= $is_checked ? 'text-primary' : 'text-dark' ?>">
                                            <?= $label ?>
                                            <?php if($is_checked): ?> <i class="fa-solid fa-circle-check ms-1 small"></i> <?php endif; ?>
                                        </span>
                                        <div class="btn-group" role="group">
                                            <input type="radio" class="btn-check" name="patho[<?= $label ?>][active]" id="<?= $label ?>_no" value="0" <?= !$is_checked ? 'checked' : '' ?> onchange="toggleNote('<?= $label ?>', false)">
                                            <label class="btn btn-outline-non btn-sm px-3" for="<?= $label ?>_no">Non</label>

                                            <input type="radio" class="btn-check" name="patho[<?= $label ?>][active]" id="<?= $label ?>_yes" value="1" <?= $is_checked ? 'checked' : '' ?> onchange="toggleNote('<?= $label ?>', true)">
                                            <label class="btn btn-outline-oui btn-sm px-3" for="<?= $label ?>_yes">Oui</label>
                                        </div>
                                    </div>
                                    <div id="note_<?= $label ?>" class="mt-2" style="display: <?= $is_checked ? 'block' : 'none' ?>;">
                                        <textarea name="patho[<?= $label ?>][note]" class="form-control form-control-sm" rows="1"><?= $is_checked ? htmlspecialchars($deja_presents[$label]) : '' ?></textarea>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="col-md-6 ps-md-4">
                                <h6 class="fw-bold mb-4" style="color: var(--primary);"><i class="fa-solid fa-scissors me-2"></i>Antécédents Chirurgicaux</h6>
                                <div class="bg-light p-3 rounded-3 mb-4 border">
                                    <p class="small text-muted mb-2">Ajouter une opération :</p>
                                    <div class="input-group input-group-sm">
                                        <input type="text" name="new_chir_nom" class="form-control" placeholder="Ex: Appendicectomie">
                                        <input type="text" name="new_chir_annee" class="form-control" placeholder="Année" style="max-width: 80px;">
                                    </div>
                                </div>
                                
                                <div class="chirurgie-liste">
                                    <?php 
                                    $has_chir = false;
                                    foreach($all_antecedents as $ant): 
                                        if($ant['categorie'] == 'Chirurgical'): 
                                            $has_chir = true;
                                    ?>
                                        <div class="d-flex align-items-center mb-2 p-2 bg-white border rounded-3 shadow-sm">
                                            <div class="icon-circle bg-light me-3"><i class="fa-solid fa-stethoscope text-primary"></i></div>
                                            <div class="flex-grow-1">
                                                <div class="fw-bold small"><?= htmlspecialchars($ant['nom_pathologie']) ?></div>
                                                <div class="text-muted" style="font-size: 11px;">Année : <?= htmlspecialchars($ant['date_evenement']) ?></div>
                                            </div>
                                            <a href="supprimer_antecedent.php?id=<?= $ant['id_ante'] ?>&id_p=<?= $id_patient ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Supprimer ?')">
                                                <i class="fa-solid fa-trash"></i>
                                            </a>
                                        </div>
                                    <?php endif; endforeach; ?>
                                    <?php if(!$has_chir): ?>
                                        <div class="text-center py-4 text-muted small opacity-75">Aucune chirurgie répertoriée.</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="text-end mt-4 pt-3 border-top">
                            <button type="submit" class="btn-medical shadow-sm"><i class="fa-solid fa-check-double me-2"></i>Enregistrer</button>
                        </div>
                    </form>
                </div>
            </div>

            <div id="traitements-tab" class="tab-pane fade">
                <div class="content-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-bold mb-0">Ordonnances Actives</h5>
                        <a href="../traitement/ajouter_traitement.php?patient_id=<?php echo $patient['id_patient']; ?>" class="btn-add-action">
                            <i class="fa-solid fa-plus-circle"></i> Prescrire
                        </a>
                      
                    </div>
                    <table class="table align-middle">
                        <thead>
                            <tr class="text-muted small"><th>DATE</th><th>MÉDICAMENT</th><th>POSOLOGIE & DURÉE</th></tr>
                        </thead>
                        <tbody>
                            <?php if($traitements): foreach($traitements as $t): ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($t['date_traitement'])) ?></td>
                                <td><span class="badge bg-light text-primary border border-primary px-3"><?= htmlspecialchars($t['medicament']) ?></span></td>
                                <td><small class="text-muted"><?= nl2br(htmlspecialchars($t['description'])) ?></small></td>
                            </tr>
                            <?php endforeach; else: ?>
                                <tr><td colspan="3" class="text-center py-4 text-muted small">Aucun traitement.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="documents-tab" class="tab-pane fade">
                <div class="content-card text-center py-5">
                    <div class="mb-4"><i class="fa-solid fa-file-pdf fa-4x text-danger opacity-25"></i></div>
                    <h5 class="fw-bold">Rapport Médical Complet</h5>
                    <button class="btn-medical mt-3" id="btnPdf"><i class="fa-solid fa-download me-2"></i>Générer PDF</button>
                </div>
            </div>
        </div>
    </main>
</div>

<div class="modal fade" id="modalHistorique" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 shadow-lg border-0">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold"><i class="fa-solid fa-clock-rotate-left me-2 text-primary"></i>Historique du Statut</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
    <?php if($historique_statuts): ?>
        <div class="timeline-modern">
            <?php foreach($historique_statuts as $h): 
                // Logique de couleur dynamique
                $statut_clean = strtolower(htmlspecialchars($h['nouveau_statut']));
                $border_class = 'status-observation';
                if(str_contains($statut_clean, 'stable')) $border_class = 'status-stable';
                if(str_contains($statut_clean, 'critique')) $border_class = 'status-critique';
                if(str_contains($statut_clean, 'urgent')) $border_class = 'status-urgent';
            ?>
                <div class="timeline-item-modern">
                    <div class="timeline-point"></div>
                    <div class="timeline-content <?= $border_class ?>">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold text-dark"><?= htmlspecialchars($h['nouveau_statut']) ?></span>
                            <span class="text-muted" style="font-size: 0.75rem;">
                                <i class="fa-regular fa-calendar-days me-1"></i>
                                <?= date('d M Y', strtotime($h['date_changement'])) ?> 
                                <b class="ms-1"><?= date('H:i', strtotime($h['date_changement'])) ?></b>
                            </span>
                        </div>
                        
                        <div class="d-flex align-items-center">
                            <div class="avatar-sm me-2" style="width: 24px; height: 24px; background: #e2e8f0; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 10px;">
                                <i class="fa-solid fa-user-doctor"></i>
                            </div>
                            <span class="text-muted small">Mise à jour par l'équipe médicale</span>
                        </div>
                        
                        <?php if(!empty($h['note'])): ?>
                            <p class="mt-2 mb-0 small text-secondary italic">
                                <i class="fa-solid fa-quote-left me-1 opacity-50"></i>
                                <?= htmlspecialchars($h['note']) ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="text-center py-5">
            <img src="https://cdn-icons-png.flaticon.com/512/5058/5058315.png" style="width: 60px; opacity: 0.3;" class="mb-3">
            <p class="text-muted">Aucun historique de changement disponible.</p>
        </div>
    <?php endif; ?>
</div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
function toggleNote(id, show) {
    const noteDiv = document.getElementById('note_' + id);
    noteDiv.style.display = show ? 'block' : 'none';
}

// Génération PDF
document.getElementById('btnPdf').addEventListener('click', function() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    
    doc.setFillColor(15, 118, 110);
    doc.rect(0, 0, 210, 40, 'F');
    doc.setTextColor(255, 255, 255);
    doc.setFontSize(22);
    doc.text("MedicalServices - DOSSIER MEDICAL", 105, 25, null, null, "center");
    
    doc.setTextColor(0, 0, 0);
    doc.setFontSize(14);
    doc.text("IDENTITE DU PATIENT", 20, 55);
    doc.setFontSize(11);
    doc.text("Nom complet : <?= addslashes($patient['prenom'].' '.$patient['nom']) ?>", 20, 65);
    doc.text("Né(e) le : <?= addslashes($patient['date_naissance']) ?>", 20, 72);
    doc.text("Sexe : <?= addslashes($patient['sexe']) ?>", 20, 79);
    doc.text("Téléphone : <?= addslashes($patient['telephone']) ?>", 20, 86);
    
    doc.line(20, 100, 190, 100);
    
    let y = 110;
    doc.setFontSize(14);
    doc.text("HISTORIQUE MEDICAL", 20, y);
    y += 10;
    doc.setFontSize(10);
    <?php foreach($suivis as $s): ?>
        if(y > 270) { doc.addPage(); y = 20; }
        doc.text("- <?= addslashes($s['date_suivi']) ?> : <?= addslashes(str_replace(["\r", "\n"], ' ', $s['commentaire'])) ?>", 25, y);
        y += 7;
    <?php endforeach; ?>
    
    doc.save("Dossier_<?= $patient['nom'] ?>.pdf");
});
</script>
</body>
</html>
