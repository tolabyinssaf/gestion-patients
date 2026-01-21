<?php

session_start();
include("../config/connexion.php");

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'secretaire') {
    header("Location: dashboard_secretaire.php");
    exit;
}

// 1. GESTION DE LA DATE ET DU FILTRE MÉDECIN
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

if (isset($_GET['medecin_id'])) {
    $_SESSION['filter_medecin'] = $_GET['medecin_id'];
}
$filter_medecin = $_SESSION['filter_medecin'] ?? '';
$user_id = $_SESSION['user_id'];

// Infos secrétaire
$stmt = $pdo->prepare("SELECT nom, prenom FROM utilisateurs WHERE id_user = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// --- LISTE DES MÉDECINS ---
$sql_medecins = "SELECT id_user, nom, prenom, specialite, role FROM utilisateurs WHERE LOWER(role) = 'medecin' ORDER BY nom ASC";
$medecins_list = $pdo->query($sql_medecins)->fetchAll(PDO::FETCH_ASSOC);

// --- STATISTIQUES ---
$sql_new_pat = "SELECT COUNT(DISTINCT p.id_patient) FROM patients p 
                JOIN admissions a ON p.id_patient = a.id_patient 
                WHERE DATE(p.date_inscription) = '$selected_date'";
if($filter_medecin) $sql_new_pat .= " AND a.id_medecin = " . intval($filter_medecin);
$count_patients = $pdo->query($sql_new_pat)->fetchColumn();

$sql_count_adm = "SELECT COUNT(*) FROM admissions WHERE DATE(date_admission) = '$selected_date'";
if($filter_medecin) $sql_count_adm .= " AND id_medecin = " . intval($filter_medecin);
$count_admissions = $pdo->query($sql_count_adm)->fetchColumn();

// --- RECHERCHE PATIENT ---
$search = $_GET['q'] ?? '';
$search_results = [];
if (!empty($search)) {
    $sql_search = "SELECT * FROM patients 
                   WHERE (cin LIKE :q OR nom LIKE :q OR telephone LIKE :q) 
                   LIMIT 5";
    $stmt = $pdo->prepare($sql_search);
    $stmt->execute(['q' => "%$search%"]);
    $search_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// --- ADMISSIONS ---
$sql_adm = "SELECT a.*, p.nom, p.prenom, f.id_facture, u.nom as nom_medecin 
    FROM admissions a 
    JOIN patients p ON a.id_patient = p.id_patient 
    JOIN utilisateurs u ON a.id_medecin = u.id_user
    LEFT JOIN factures f ON a.id_admission = f.id_admission
    WHERE DATE(a.date_admission) = '$selected_date'";
if($filter_medecin) $sql_adm .= " AND a.id_medecin = " . intval($filter_medecin);
$sql_adm .= " ORDER BY a.date_admission DESC";
$admissions = $pdo->query($sql_adm)->fetchAll(PDO::FETCH_ASSOC);

// --- SUIVIS ---
$sql_s = "SELECT s.*, p.nom, p.prenom 
    FROM suivis s 
    JOIN patients p ON s.id_patient = p.id_patient 
    WHERE DATE(s.date_suivi) = '$selected_date'";
if($filter_medecin) $sql_s .= " AND s.id_medecin = " . intval($filter_medecin);
$suivis_du_jour = $pdo->query($sql_s)->fetchAll(PDO::FETCH_ASSOC);

// --- SORTIES ---
$sql_sorties = "SELECT a.*, p.nom, p.prenom, p.cin, u.nom as nom_medecin 
    FROM admissions a 
    JOIN patients p ON a.id_patient = p.id_patient 
    JOIN utilisateurs u ON a.id_medecin = u.id_user
    LEFT JOIN factures f ON a.id_admission = f.id_admission
    WHERE a.date_sortie IS NOT NULL AND f.id_facture IS NULL";
if($filter_medecin) $sql_sorties .= " AND a.id_medecin = " . intval($filter_medecin);
$sorties_a_traiter = $pdo->query($sql_sorties)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>MedCare | Secrétariat</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #0d9488; /* Vert Émeraude */
            --primary-hover: #0f766e;
            --secondary: #253a5d; /* Bleu doux */
            --sidebar-bg: #0f172a; /* Bleu Nuit / Indigo menu */
            --bg-body: #f8fafc;
            --white: #ffffff;
            --border: #e2e8f0;
            --text-main: #1e293b;
            --header-height: 75px;
            --sidebar-width: 260px;
        }

        * { margin:0; padding:0; box-sizing:border-box; font-family: 'Inter', sans-serif; }
        body { background: var(--bg-body); color: var(--text-main); }

        header {
            background: var(--white);
            padding: 0 40px; height: var(--header-height);
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid var(--border);
            position: fixed; top: 0; left: 0; right: 0; z-index: 1000;
        }
        .sidebar { 
            width: var(--sidebar-width); background: var(--sidebar-bg); 
            padding: 24px 16px; position: fixed; top: var(--header-height); left: 0; bottom: 0; z-index: 999;
        }
        .content { margin-left: var(--sidebar-width); margin-top: var(--header-height); padding: 40px; }

        /* RECHERCHE */
        .search-wrapper {
            background: var(--white); padding: 25px; border-radius: 16px;
            border: 1px solid var(--border); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        }
        .modern-search-group {
            display: flex; background: #f1f5f9; border-radius: 12px;
            padding: 5px; align-items: center; border: 2px solid transparent; transition: 0.3s;
        }
        .modern-search-group:focus-within {
            background: white; border-color: var(--primary); box-shadow: 0 0 0 4px rgba(13, 148, 136, 0.1);
        }
        .modern-search-group input {
            flex: 1; border: none; background: transparent; outline: none; padding: 10px 15px; font-weight: 500;
        }
        .search-btn { 
            background: var(--primary); color: white; border: none; 
            padding: 10px 24px; border-radius: 10px; font-weight: 600;
        }

        /* CARTES & STATS */
        .mega-card { 
            background: var(--white); padding: 25px; border-radius: 16px; 
            border: 1px solid var(--border); transition: 0.3s;
        }
        .icon-circle { 
            width: 56px; height: 56px; border-radius: 12px; 
            display: flex; align-items: center; justify-content: center; font-size: 22px; 
        }
        .bg-blue-light { background: #eff6ff; color: #3b82f6; }
        .bg-green-light { background: #f0fdf4; color: #16a34a; }

        /* BADGES & TABLES */
        .status-badge { padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; }
        .status-encours { background: #eff6ff; color: #1e40af; }
        .status-termine { background: #dcfce7; color: #166534; }
        
        .section-box { 
            background: var(--white); padding: 25px; border-radius: 16px; 
            border: 1px solid var(--border); margin-bottom: 24px;
        }
        .border-left-primary { border-left: 5px solid var(--primary); }

        .btn-primary { background: var(--primary); border: none; }
        .btn-primary:hover { background: var(--primary-hover); }
        .btn-secondary { background: var(--sidebar-bg); border: none; color: white; }
        
        .user-pill { background: #f0fdfa; color: var(--primary); border: 1px solid #ccfbf1; padding: 8px 16px; border-radius: 10px; font-weight: 600; }

        .sidebar a { display: flex; align-items: center; gap: 12px; color: #94a3b8; text-decoration: none; padding: 12px 16px; border-radius: 10px; transition: 0.2s; }
        .sidebar a.active { background: var(--primary); color: white; }
        .sidebar a:hover:not(.active) { background: rgba(255,255,255,0.05); color: white; }

        th { color: #64748b; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; padding: 15px 12px; border-bottom: 2px solid #f1f5f9; }
        td { padding: 15px 12px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
    </style>
</head>
<body>

<header>
    <img src="../images/logo_app2.png" alt="Logo" style="height: 45px;">
    <div class="user-pill">
        <i class="fa-solid fa-circle-user me-2"></i>
        <span>Séc. <?= htmlspecialchars($user['prenom']." ".$user['nom']) ?></span>
    </div>
</header>

<div class="wrapper">
    <aside class="sidebar">
        <h3 style="color:rgba(255,255,255,0.3); font-size:11px; text-transform:uppercase; margin-bottom:20px; padding-left:12px;">Menu Gestion</h3>
        <a href="dashboard_secretaire.php" class="active"><i class="fa-solid fa-chart-line"></i> Vue Générale</a>
        <a href="patients_secr.php"><i class="fa-solid fa-user-group"></i> Patients</a>
        <a href="../admission/admissions_list.php"><i class="fa-solid fa-hospital-user"></i> Admissions</a>
        <a href="suivis.php"><i class="fa-solid fa-calendar-check"></i> Suivis</a>
        <a href="caisse.php"><i class="fa-solid fa-wallet"></i> Caisse & Factures</a>
        <a href="profil_secretaire.php"><i class="fa-solid fa-user"></i> Profil</a>
        <div style="height: 1px; background: rgba(255,255,255,0.1); margin: 20px 0;"></div>
        <a href="../connexio_utilisateur/deconnexion.php" style="color: #fda4af;"><i class="fa-solid fa-power-off"></i> Déconnexion</a>
    </aside>

    <main class="content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
               
                <h4 class="text-muted small">Aujourd'hui, <?= date('d M Y', strtotime($selected_date)) ?></h4>
            </div>
            <div class="bg-white p-2 rounded-3 border shadow-sm d-flex align-items-center gap-3">
                <i class="fa-solid fa-calendar text-primary ms-2"></i>
                <input type="date" class="form-control form-control-sm border-0" value="<?= $selected_date ?>" onchange="updateFilter('date', this.value)">
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="mega-card d-flex align-items-center gap-3 border-start border-4" style="border-left-color: var(--secondary) !important;">
                    <div class="icon-circle bg-blue-light"><i class="fa-solid fa-user-plus"></i></div>
                    <div>
                        <span class="text-muted small fw-bold">NOUVEAUX PATIENTS</span>
                        <h2 class="fw-bold mb-0"><?= $count_patients ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mega-card d-flex align-items-center gap-3 border-start border-4" style="border-left-color: var(--primary) !important;">
                    <div class="icon-circle bg-green-light"><i class="fa-solid fa-stethoscope"></i></div>
                    <div>
                        <span class="text-muted small fw-bold">ADMISSIONS DU JOUR</span>
                        <h2 class="fw-bold mb-0"><?= $count_admissions ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="search-wrapper mb-4">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="small fw-bold text-muted mb-2 d-block">FILTRER PAR MÉDECIN</label>
                    <div class="modern-search-group" style="background: white; border: 1px solid var(--border);">
                        <i class="fa-solid fa-user-doctor text-primary ms-3"></i>
                        <select class="form-select border-0 bg-transparent shadow-none" onchange="updateFilter('medecin_id', this.value)">
                            <option value="">Tous les médecins</option>
                            <?php foreach($medecins_list as $m): ?>
                                <option value="<?= $m['id_user'] ?>" <?= $filter_medecin == $m['id_user'] ? 'selected' : '' ?>>
                                    Dr. <?= htmlspecialchars($m['nom']) ?> (<?= htmlspecialchars($m['specialite']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-8">
                    <label class="small fw-bold text-muted mb-2 d-block">RECHERCHER UN DOSSIER PATIENT</label>
                    <form action="dashboard_secretaire.php" method="GET" class="mb-0">
                        <input type="hidden" name="date" value="<?= $selected_date ?>">
                        <input type="hidden" name="medecin_id" value="<?= $filter_medecin ?>">
                        <div class="modern-search-group">
                            <i class="fa-solid fa-magnifying-glass text-muted ms-3"></i>
                            <input type="text" name="q" placeholder="Nom, CIN ou Téléphone..." value="<?= htmlspecialchars($search) ?>" autocomplete="off">
                            
                        </div>
                    </form>
                </div>
            </div>

            <?php if ($search_results): ?>
            <div class="mt-4 border-top pt-3">
                <?php foreach($search_results as $p): ?>
                <div class="p-3 border rounded-3 mb-2 d-flex justify-content-between align-items-center bg-light">
                    <div class="d-flex align-items-center gap-3">
                        <div style="width:40px; height:40px; background:white; border-radius:50%; display:flex; align-items:center; justify-content:center; color:var(--primary); font-weight:700; border: 1px solid var(--border);">
                            <?= strtoupper(substr($p['nom'], 0, 1)) ?>
                        </div>
                        <div>
                            <div class="fw-bold"><?= strtoupper($p['nom']) ?> <?= $p['prenom'] ?></div>
                            <div class="text-muted small">CIN: <?= $p['CIN'] ?> | Tél: <?= $p['telephone'] ?></div>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="../admission/ajouter_admission.php?id_patient=<?= $p['id_patient'] ?>"class="btn btn-sm btn-primary rounded-pill px-3"><i class="fa-solid fa-plus me-1"></i> Admettre</a>
                        <a href="dossier_patient.php?id=<?= $p['id_patient'] ?>" class="btn btn-sm btn-light border rounded-pill"><i class="fa-solid fa-folder-open"></i></a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="section-box border-left-primary shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-bold mb-0" style="color: var(--sidebar-bg);"><i class="fa-solid fa-circle-info text-primary me-2"></i> Sorties à régulariser</h5>
                <span class="badge bg-primary rounded-pill px-3"><?= count($sorties_a_traiter) ?> Dossiers</span>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Médecin</th>
                            <th>Date Sortie</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($sorties_a_traiter): ?>
                            <?php foreach($sorties_a_traiter as $s): ?>
                            <tr>
                                <td class="fw-bold"><?= htmlspecialchars($s['nom'].' '.$s['prenom']) ?></td>
                                <td class="text-muted">Dr. <?= htmlspecialchars($s['nom_medecin']) ?></td>
                                <td class="text-primary fw-600"><?= date('d/m/Y', strtotime($s['date_sortie'])) ?></td>
                                <td class="text-end">
                                    <a href="facturer_admission.php?id_admission=<?= $s['id_admission'] ?>" class="btn btn-sm btn-primary px-3 rounded-pill">
                                        Facturer & Libérer
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center py-4 text-muted small">Aucun patient en attente de facturation.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="row g-4 mt-1">
            <div class="col-lg-8">
                <div class="section-box h-100 shadow-sm">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-bold mb-0">Activité récente</h5>
                        <a href="ajouter_patient.php?medecin_id=<?= $filter_medecin ?>" 
   class="btn btn-sm btn-secondary px-3">
    + Nouveau Patient
</a>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Heure</th>
                                    <th>Patient</th>
                                    <th>Statut</th>
                                    <th class="text-end">Détails</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($admissions as $a): ?>
                                <tr>
                                    <td class="fw-bold text-primary"><?= date('H:i', strtotime($a['date_admission'])) ?></td>
                                    <td><?= htmlspecialchars($a['nom'].' '.$a['prenom']) ?></td>
                                    <td>
                                        <span class="status-badge <?= $a['id_facture'] ? 'status-termine' : 'status-encours' ?>">
                                            <?= $a['id_facture'] ? 'Réglé' : 'En attente' ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="dossier_patient.php?id=<?= $a['id_patient'] ?>" class="btn btn-sm btn-light border"><i class="fa-solid fa-eye"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="section-box h-100 shadow-sm">
                    <h5 class="fw-bold mb-4">Suivis du jour</h5>
                    <?php if($suivis_du_jour): ?>
                        <?php foreach($suivis_du_jour as $s): ?>
                        <div class="p-3 border rounded-3 mb-3" style="background: #f8fafc; border-left: 4px solid var(--primary) !important;">
                            <div class="fw-bold small"><?= htmlspecialchars($s['nom'].' '.$s['prenom']) ?></div>
                            <div class="text-muted x-small"><?= htmlspecialchars($s['motif'] ?? 'Contrôle périodique') ?></div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fa-solid fa-calendar-xmark text-muted mb-2" style="font-size: 1.5rem;"></i>
                            <p class="text-muted small">Aucun suivi aujourd'hui</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
    function updateFilter(param, val) {
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.set(param, val);
        if(param !== 'q') urlParams.delete('q'); 
        window.location.href = "dashboard_secretaire.php?" + urlParams.toString();
    }
</script>

</body>
</html>