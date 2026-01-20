<?php
session_start();
include("../config/connexion.php");

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'secretaire') {
    header("Location: login.php");
    exit;
}

$id_patient = $_GET['id'] ?? null;
if (!$id_patient) { header("Location: dashboard_secretaire.php"); exit; }

// 1. INFOS PATIENT
$stmt = $pdo->prepare("SELECT * FROM patients WHERE id_patient = ?");
$stmt->execute([$id_patient]);
$p = $stmt->fetch(PDO::FETCH_ASSOC);

// 2. SUIVIS
$suivis = $pdo->prepare("SELECT * FROM suivis WHERE id_patient = ? ORDER BY date_suivi DESC");
$suivis->execute([$id_patient]);
$liste_suivis = $suivis->fetchAll(PDO::FETCH_ASSOC);

// 3. TRAITEMENTS
$traitements = $pdo->prepare("SELECT * FROM traitements WHERE id_patient = ? ORDER BY date_traitement DESC");
$traitements->execute([$id_patient]);
$liste_traitements = $traitements->fetchAll(PDO::FETCH_ASSOC);

// 4. ADMISSION ACTUELLE (AJOUTÉ)
$stmt_adm = $pdo->prepare("
    SELECT a.*, u.nom as nom_medecin, u.prenom as prenom_medecin 
    FROM admissions a 
    LEFT JOIN utilisateurs u ON a.id_medecin = u.id_user 
    WHERE a.id_patient = ? 
    ORDER BY a.date_admission DESC LIMIT 1
");
$stmt_adm->execute([$id_patient]);
$last_adm = $stmt_adm->fetch(PDO::FETCH_ASSOC);

// Infos secrétaire
$user_id = $_SESSION['user_id'];
$stmt_u = $pdo->prepare("SELECT nom, prenom FROM utilisateurs WHERE id_user = ?");
$stmt_u->execute([$user_id]);
$user = $stmt_u->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Dossier Médical | <?= strtoupper($p['nom']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #0f766e; 
            --primary-soft: #f0fdfa;
            --sidebar-bg: #0f172a;
            --bg-body: #f8fafc;
            --border: #e2e8f0;
            --white: #ffffff;
            --header-height: 75px;
            --sidebar-width: 260px;
            --accent-blue: #3b82f6;
        }

        * { margin:0; padding:0; box-sizing:border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: var(--bg-body); color: #1e293b; }

        header { background: var(--white); padding: 0 40px; height: var(--header-height); display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); position: fixed; top: 0; left: 0; right: 0; z-index: 1000; }
        .sidebar { width: var(--sidebar-width); background: var(--sidebar-bg); padding: 24px 16px; position: fixed; top: var(--header-height); left: 0; bottom: 0; z-index: 999; }
        .sidebar a { display: flex; align-items: center; gap: 12px; color: #94a3b8; text-decoration: none; padding: 12px 16px; border-radius: 10px; margin-bottom: 5px; transition: 0.2s; }
        .sidebar a:hover, .sidebar a.active { background: var(--primary); color: #fff; }
        .content { margin-left: var(--sidebar-width); margin-top: var(--header-height); padding: 40px; }

        .patient-banner {
            background: var(--white);
            border-radius: 24px;
            padding: 30px;
            border: 1px solid var(--border);
            margin-bottom: 30px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .avatar-circle {
            width: 90px; height: 90px;
            background: linear-gradient(135deg, var(--primary) 0%, #134e4a 100%);
            color: white; border-radius: 22px;
            display: flex; align-items: center; justify-content: center;
            font-size: 35px; font-weight: 700;
            box-shadow: 0 10px 15px -3px rgba(15, 118, 110, 0.2);
        }

        .stat-badge {
            background: var(--primary-soft);
            color: var(--primary);
            padding: 8px 16px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .medical-card {
            background: var(--white);
            border-radius: 24px;
            border: 1px solid var(--border);
            padding: 24px;
            height: 100%;
            transition: 0.3s;
        }

        .card-header-ui {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .icon-shape {
            width: 45px; height: 45px;
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px;
        }

        .timeline-ui {
            position: relative;
            padding-left: 20px;
        }
        .timeline-ui::before {
            content: ''; position: absolute; left: 0; top: 0; bottom: 0;
            width: 2px; background: #f1f5f9;
        }
        .timeline-item {
            position: relative;
            padding-bottom: 25px;
            padding-left: 20px;
        }
        .timeline-item::after {
            content: ''; position: absolute; left: -25px; top: 5px;
            width: 12px; height: 12px; border-radius: 50%;
            background: white; border: 3px solid var(--primary);
        }

        .info-row { margin-bottom: 18px; }
        .info-label { font-size: 12px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; }
        .info-value { font-size: 15px; font-weight: 600; color: #1e293b; }

        .btn-action-ui {
            border-radius: 14px;
            padding: 10px 20px;
            font-weight: 600;
            transition: 0.2s;
            border: none;
        }
        .btn-edit { background: #f1f5f9; color: #475569; }
        .btn-facture { background: #e2b84eff; color: #92400e; }
        .btn-edit:hover { background: #e2e8f0; }

        .critical-note {
            background: #fff1f2;
            border-radius: 16px;
            padding: 15px;
            border: 1px solid #fecdd3;
            color: #9f1239;
        }
    </style>
</head>
<body>

<header>
    <img src="../images/logo_app2.png" alt="Logo" style="height:45px;">
    <div style="background:var(--primary-soft); padding:8px 18px; border-radius:12px; color:var(--primary); font-weight:600;">
        <i class="fa-solid fa-circle-user me-2"></i> Séc. <?= htmlspecialchars($user['prenom']) ?>
    </div>
</header>

<div class="wrapper">
    <aside class="sidebar">
        <h3 style="color:rgba(255,255,255,0.3); font-size:11px; text-transform:uppercase; margin-bottom:20px; padding-left:12px;">Menu Gestion</h3>
        <a href="dashboard_secretaire.php"><i class="fa-solid fa-chart-line"></i> Vue Générale</a>
        <a href="patients_secr.php" class="active"><i class="fa-solid fa-user-group"></i> Patients</a>
         <a href="../admission/admissions_list.php"><i class="fa-solid fa-hospital-user"></i> Admissions</a>
        <a href="suivis.php"><i class="fa-solid fa-calendar-check"></i> Suivis</a>
        <a href="caisse.php"><i class="fa-solid fa-wallet"></i> Caisse & Factures</a>
         <a href="profil_secretaire.php"><i class="fa-solid fa-user"></i> Profil</a>
        <div style="height: 1px; background: rgba(255,255,255,0.1); margin: 20px 0;"></div>
        <a href="../connexio_utilisateur/deconnexion.php" style="color: #fda4af;"><i class="fa-solid fa-power-off"></i> Déconnexion</a>
    </aside>

    <main class="content">
        <div class="patient-banner d-flex justify-content-between align-items-center shadow-sm">
            <div class="d-flex align-items-center gap-4">
                <div class="avatar-circle">
                    <?= strtoupper(substr($p['nom'], 0, 1)) ?>
                </div>
                <div>
                    <div class="d-flex align-items-center gap-3 mb-1">
                        <h2 class="fw-bold m-0"><?= strtoupper($p['nom']) ?> <?= $p['prenom'] ?></h2>
                        <span class="stat-badge"><i class="fa-solid fa-shield-check"></i> Dossier Actif</span>
                    </div>
                    <p class="text-muted m-0">
                        <span class="me-3"><i class="fa-solid fa-id-card me-1"></i> CIN : <strong><?= $p['CIN'] ?></strong></span>
                        <span><i class="fa-solid fa-calendar-day me-1"></i> Inscrit le <?= date('d/m/Y', strtotime($p['date_inscription'])) ?></span>
                    </p>
                </div>
            </div>
            <div class="d-flex gap-2">
                <?php if($last_adm): ?>
    <a href="details_facture.php?id_adm=<?= $last_adm['id_admission'] ?>" class="btn-action-ui btn-facture text-decoration-none">
        <i class="fa-solid fa-file-invoice-dollar me-2"></i>Voir la Facture
    </a>
<?php else: ?>
    <button class="btn-action-ui btn-facture opacity-50" disabled>
        <i class="fa-solid fa-ban me-2"></i>Pas d'admission active
    </button>
<?php endif; ?>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="medical-card shadow-sm">
                    <div class="card-header-ui">
                        <h5 class="fw-bold m-0">Admission Actuelle</h5>
                        <div class="icon-shape" style="background: #fff7ed; color: #bf7c4cff;"><i class="fa-solid fa-hospital-user"></i></div>
                    </div>

                    <?php if($last_adm): ?>
                        <div class="info-row">
                            <div class="info-label">Médecin / Service</div>
                            <div class="info-value">Dr. <?= htmlspecialchars($last_adm['nom_medecin']) ?> (<?= htmlspecialchars($last_adm['service']) ?>)</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Motif Admission</div>
                            <div class="info-value"><?= htmlspecialchars($last_adm['motif']) ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Date Admission</div>
                            <div class="info-value"><?= date('d/m/Y à H:i', strtotime($last_adm['date_admission'])) ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Chambre</div>
                            <div class="info-value"><?= $last_adm['id_chambre'] ? "Chambre ".$last_adm['id_chambre'] : 'Non assignée' ?></div>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fa-solid fa-door-open text-muted opacity-25 fa-3x mb-3"></i>
                            <p class="text-muted small">Aucune admission trouvée</p>
                            <a href="../admission/ajouter_admission.php?id_patient=<?= $p['id_patient'] ?>" 
   class="btn-action-ui text-decoration-none shadow-sm" 
   style="background: #bf7c4cff; color: white; display: inline-flex; align-items: center; gap: 8px;">
    <i class="fa-solid fa-plus-circle"></i>
    Nouvelle Admission
</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="medical-card shadow-sm">
                    <div class="card-header-ui">
                        <h5 class="fw-bold m-0">Profil Patient</h5>
                        <div class="icon-shape" style="background: #eff6ff; color: #3b82f6;"><i class="fa-solid fa-address-book"></i></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Téléphone Mobile</div>
                        <div class="info-value text-primary"><?= $p['telephone'] ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Genre / Sexe</div>
                        <div class="info-value"><?= $p['sexe'] ?? 'Non défini' ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Adresse Résidentielle</div>
                        <div class="info-value"><?= $p['adresse'] ?: 'N/A' ?></div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="medical-card shadow-sm">
                    <div class="card-header-ui">
                        <h5 class="fw-bold m-0">Historique des visites</h5>
                        <div class="icon-shape" style="background: #f0fdf4; color: #22c55e;"><i class="fa-solid fa-stethoscope"></i></div>
                    </div>
                    <div class="timeline-ui">
                        <?php if($liste_suivis): ?>
                            <?php foreach($liste_suivis as $s): ?>
                                <div class="timeline-item">
                                    <div class="info-value" style="font-size: 14px;"><?= $s['commentaire'] ?: 'Examen clinique' ?></div>
                                    <div class="info-label" style="font-size: 11px;"><?= date('d F Y', strtotime($s['date_suivi'])) ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fa-solid fa-calendar-xmark text-muted opacity-25 fa-3x mb-3"></i>
                                <p class="text-muted small">Aucun rendez-vous passé</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mt-2">
            <div class="col-12">
                <div class="medical-card shadow-sm">
                    <div class="card-header-ui">
                        <h5 class="fw-bold m-0">Prescriptions & Traitements</h5>
                        <div class="icon-shape" style="background: #faf5ff; color: #a855f7;"><i class="fa-solid fa-pills"></i></div>
                    </div>
                    <div class="row">
                        <?php if($liste_traitements): ?>
                            <?php foreach($liste_traitements as $t): ?>
    <div class="col-md-4">
        <div class="p-3 rounded-4 border mb-3 bg-white shadow-sm h-100" style="border-left: 4px solid #a855f7 !important;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="badge rounded-pill px-3 py-2" style="background: #faf5ff; color: #a855f7; font-size: 11px; border: 1px solid #f3e8ff;">
                    <i class="fa-regular fa-calendar-check me-1"></i> <?= date('d/m/Y', strtotime($t['date_traitement'])) ?>
                </span>
                <i class="fa-solid fa-pills text-muted opacity-50"></i>
            </div>

            <div class="mb-2">
                <div class="info-label" style="font-size: 10px; color: #a855f7;">Médicament</div>
                <div class="fw-bold text-dark" style="font-size: 15px; letter-spacing: -0.3px;">
                    <?= htmlspecialchars($t['medicament']) ?>
                </div>
            </div>

            <?php if(!empty($t['description'])): ?>
            <div class="pt-2 border-top mt-2">
                <div class="info-label" style="font-size: 10px;">Description & Posologie</div>
                <div class="text-muted small italic" style="line-height: 1.4;">
                    <i class="fa-solid fa-quote-left me-1 opacity-25"></i>
                    <?= htmlspecialchars($t['description']) ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-12 text-center py-5">
                                <i class="fa-solid fa-prescription text-muted opacity-25 fa-3x mb-3"></i>
                                <p class="text-muted small">Aucun traitement actif</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

</body>
</html>