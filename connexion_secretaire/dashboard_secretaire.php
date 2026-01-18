<?php
session_start();
include("../config/connexion.php");

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'secretaire') {
    header("Location: login.php");
    exit;
}

// 1. GESTION DE LA DATE ET DU FILTRE MÉDECIN (AVEC MÉMOIRE)
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Si on reçoit un nouveau médecin par l'URL, on l'enregistre en session
if (isset($_GET['medecin_id'])) {
    $_SESSION['filter_medecin'] = $_GET['medecin_id'];
}

// On utilise la session s'il n'y a pas de GET, ou rien du tout par défaut
$filter_medecin = $_SESSION['filter_medecin'] ?? '';
$user_id = $_SESSION['user_id'];


// Infos secrétaire
$stmt = $pdo->prepare("SELECT nom, prenom FROM utilisateurs WHERE id_user = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// --- LISTE DES MÉDECINS POUR LE FILTRE ---
$sql_medecins = "SELECT id_user, nom, prenom,specialite, role FROM utilisateurs WHERE LOWER(role) = 'medecin' ORDER BY nom ASC";
$medecins_list = $pdo->query($sql_medecins)->fetchAll(PDO::FETCH_ASSOC);

// --- STATISTIQUES ---
// Compte uniquement les nouveaux patients qui ont été admis chez ce médecin aujourd'hui
$sql_new_pat = "SELECT COUNT(DISTINCT p.id_patient) FROM patients p 
                JOIN admissions a ON p.id_patient = a.id_patient 
                WHERE DATE(p.date_inscription) = '$selected_date'";
if($filter_medecin) $sql_new_pat .= " AND a.id_medecin = " . intval($filter_medecin);
$count_patients = $pdo->query($sql_new_pat)->fetchColumn();



// Statistiques admissions filtrées
$sql_count_adm = "SELECT COUNT(*) FROM admissions WHERE DATE(date_admission) = '$selected_date'";
if($filter_medecin) $sql_count_adm .= " AND id_medecin = " . intval($filter_medecin);
$count_admissions = $pdo->query($sql_count_adm)->fetchColumn();

// --- RECHERCHE PATIENT ---

// --- RECHERCHE PATIENT ---
// --- RECHERCHE PATIENT FILTRÉE ---
$search = $_GET['q'] ?? '';
$search_results = [];

if (!empty($search)) {
    // On cherche les patients. Si un médecin est filtré, on ne montre que ceux qui ont une admission chez lui.
    $sql_search = "SELECT DISTINCT p.* FROM patients p ";
    if(!empty($filter_medecin)) {
        $sql_search .= " JOIN admissions a ON p.id_patient = a.id_patient ";
    }
    
    $sql_search .= " WHERE (p.cin LIKE :q OR p.nom LIKE :q OR p.telephone LIKE :q)";
    
    if(!empty($filter_medecin)) {
        $sql_search .= " AND a.id_medecin = " . intval($filter_medecin);
    }
    $sql_search .= " LIMIT 5";
    
    $stmt = $pdo->prepare($sql_search);
    $stmt->execute(['q' => "%$search%"]);
    $search_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
}



if (!empty($search)) {
    $stmt = $pdo->prepare("SELECT * FROM patients WHERE cin LIKE ? OR nom LIKE ? OR telephone LIKE ? LIMIT 5");
    $stmt->execute(["%$search%", "%$search%", "%$search%"]);
    $search_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
}


// --- ADMISSIONS FILTRÉES ---
$sql_adm = "SELECT a.*, p.nom, p.prenom, f.id_facture, u.nom as nom_medecin 
    FROM admissions a 
    JOIN patients p ON a.id_patient = p.id_patient 
    JOIN utilisateurs u ON a.id_medecin = u.id_user
    LEFT JOIN factures f ON a.id_admission = f.id_admission
    WHERE DATE(a.date_admission) = '$selected_date'";
if($filter_medecin) $sql_adm .= " AND a.id_medecin = " . intval($filter_medecin);
$sql_adm .= " ORDER BY a.date_admission DESC";
$admissions = $pdo->query($sql_adm)->fetchAll(PDO::FETCH_ASSOC);

// --- SUIVIS FILTRÉS ---
$sql_s = "SELECT s.*, p.nom, p.prenom 
    FROM suivis s 
    JOIN patients p ON s.id_patient = p.id_patient 
    WHERE DATE(s.date_suivi) = '$selected_date'";
if($filter_medecin) $sql_s .= " AND s.id_medecin = " . intval($filter_medecin);
$suivis_du_jour = $pdo->query($sql_s)->fetchAll(PDO::FETCH_ASSOC);
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
            --primary: #0f766e; 
            --primary-light: #f0fdfa;
            --sidebar-bg: #0f172a;
            --bg-body: #f8fafc;
            --border: #e2e8f0;
            --white: #ffffff;
            --header-height: 75px;
            --sidebar-width: 260px;
        }

        * { margin:0; padding:0; box-sizing:border-box; font-family: 'Inter', sans-serif; }
        body { background: var(--bg-body); color: #1e293b; overflow-x: hidden; }

        header {
            background: var(--white);
            padding: 0 40px; 
            height: var(--header-height);
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid var(--border);
            position: fixed; top: 0; left: 0; right: 0; z-index: 1000;
        }
        .logo { height: 45px; }

        .sidebar { 
            width: var(--sidebar-width); 
            background: var(--sidebar-bg); 
            padding: 24px 16px; 
            position: fixed; top: var(--header-height); left: 0; bottom: 0; 
            z-index: 999;
            overflow-y: auto;
        }

        .content { 
            margin-left: var(--sidebar-width); 
            margin-top: var(--header-height); 
            padding: 40px; 
            min-height: calc(100vh - var(--header-height));
        }

        .date-badge-picker {
            background: var(--primary-light);
            color: var(--primary);
            padding: 10px 20px;
            border-radius: 50px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            border: 1px solid rgba(15, 118, 110, 0.1);
            position: relative;
            transition: 0.3s ease;
        }
        .date-badge-picker:hover { background: #ccfbf1; transform: translateY(-1px); }
        .date-badge-picker input[type="date"] {
            position: absolute; opacity: 0; width: 100%; height: 100%; left: 0; cursor: pointer;
        }

        /* --- STYLE RECHERCHE ET RÉSULTATS --- */
        .search-wrapper {
            background: var(--white);
            padding: 20px;
            border-radius: 20px;
            border: 1px solid var(--border);
            box-shadow: 0 4px 15px rgba(0,0,0,0.02);
            margin-bottom: 30px;
        }
        .modern-search-group {
            display: flex;
            background: #f1f5f9;
            border-radius: 12px;
            padding: 6px 12px;
            align-items: center;
            transition: 0.3s;
            border: 2px solid transparent;
        }
        .modern-search-group:focus-within {
            background: white;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px var(--primary-light);
        }
        .modern-search-group input, .modern-search-group select {
            flex: 1; border: none; background: transparent; outline: none; padding: 10px; font-size: 15px; font-weight: 500;
        }
        .search-btn { background: var(--primary); color: white; border: none; padding: 8px 20px; border-radius: 8px; font-weight: 600; transition: 0.2s; }

        .result-card {
            background: white;
            border: 1px solid var(--border);
            border-radius: 15px;
            padding: 12px 20px;
            margin-top: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: 0.3s;
        }
        .result-card:hover { border-color: var(--primary); transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.05); }

        .patient-avatar {
            width: 48px; height: 48px; background: var(--primary-light); color: var(--primary);
            display: flex; align-items: center; justify-content: center;
            border-radius: 12px; font-weight: 800; font-size: 20px; border: 1px solid rgba(15,118,110,0.1);
        }
        .patient-name-link {
            color: var(--primary); font-weight: 700; text-decoration: none; font-size: 16px; transition: 0.2s;
        }
        .patient-name-link:hover { color: #0d9488; text-decoration: underline; }

        .action-icon {
            width: 38px; height: 38px; display: inline-flex; align-items: center; justify-content: center;
            border-radius: 10px; text-decoration: none; transition: 0.3s; font-size: 16px;
        }
        .btn-admission { background: #e0f2fe; color: #0369a1; }
        .btn-admission:hover { background: #0369a1; color: white; }
        .btn-dossier { background: #f0fdf4; color: #166534; }
        .btn-dossier:hover { background: #166534; color: white; }

        .stats-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 40px; }
        .mega-card { background: var(--white); padding: 25px; border-radius: 20px; display: flex; align-items: center; gap: 20px; border: 1px solid var(--border); }
        .icon-circle { width: 60px; height: 60px; border-radius: 15px; display: flex; align-items: center; justify-content: center; font-size: 24px; }
        .bg-new { background: #eff6ff; color: #3b82f6; }
        .bg-adm { background: #f0fdf4; color: #10b981; }
        .section-box { background: var(--white); padding: 25px; border-radius: 20px; border: 1px solid var(--border); margin-bottom: 30px; }
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .status-badge { padding: 5px 12px; border-radius: 8px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .status-encours { background: #fff7ed; color: #c2410c; }
        .status-termine { background: #f0fdf4; color: #166534; }
        .user-pill { background: var(--primary-light); padding: 8px 18px; border-radius: 12px; display: flex; align-items: center; gap: 10px; font-size: 14px; font-weight: 600; color: var(--primary); }
        .sidebar a { display: flex; align-items: center; gap: 12px; color: #94a3b8; text-decoration: none; padding: 12px 16px; border-radius: 10px; margin-bottom: 5px; }
        .sidebar a.active { background: var(--primary); color: #fff; }
        table { width:100%; border-collapse:collapse; }
        th { text-align:left; padding:12px; background:#f8fafc; color:#64748b; font-size:11px; text-transform:uppercase; }
        td { padding:15px 12px; border-bottom:1px solid #f1f5f9; font-size:14px; }
    </style>
</head>
<body>

<header>
    <img src="../images/logo_app2.png" alt="Logo" class="logo">
    <div class="user-pill">
        <i class="fa-solid fa-user-tie"></i>
        <span>Séc. <?= htmlspecialchars($user['prenom']." ".$user['nom']) ?></span>
    </div>
</header>

<div class="wrapper">
    <aside class="sidebar">
        <h3 style="color:rgba(255,255,255,0.3); font-size:11px; text-transform:uppercase; margin-bottom:20px; padding-left:12px;">Menu Gestion</h3>
        <a href="dashboard_secretaire.php" class="active"><i class="fa-solid fa-chart-line"></i> Tableau de bord</a>
        <a href="patients_secr.php"><i class="fa-solid fa-user-group"></i> Patients</a>
        <a href="admissions.php"><i class="fa-solid fa-door-open"></i> Salle d'attente</a>
        <a href="suivis.php"><i class="fa-solid fa-calendar-check"></i> Suivis du jour</a>
        <a href="caisse.php"><i class="fa-solid fa-wallet"></i> Caisse & Factures</a>
        <div style="height: 1px; background: rgba(255,255,255,0.1); margin: 20px 0;"></div>
        <a href="../connexio_utilisateur/deconnexion.php" style="color: #fda4af;"><i class="fa-solid fa-power-off"></i> Déconnexion</a>
    </aside>

    <main class="content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h2 fw-bold mb-1">Espace Secrétariat 👋</h1>
                <p class="text-muted mb-0">Gestion pour le : <strong><?= date('d/m/Y', strtotime($selected_date)) ?></strong></p>
            </div>
            <div class="date-badge-picker">
                <i class="fa-solid fa-calendar-day"></i>
                <span><?= date('d F Y', strtotime($selected_date)) ?></span>
                <input type="date" value="<?= $selected_date ?>" onchange="updateFilter('date', this.value)">
            </div>
        </div>

        <div class="search-wrapper mb-4">
            <h5 class="fw-bold mb-3" style="font-size: 14px; color: #475569; text-transform: uppercase;">Filtrer par Médecin</h5>
            <div class="modern-search-group">
                <i class="fa-solid fa-user-doctor text-muted ms-2"></i>
                <select onchange="updateFilter('medecin_id', this.value)">
                    <option value="">Tous les médecins</option>
                    <?php foreach($medecins_list as $m): ?>
                        <option value="<?= $m['id_user'] ?>" <?= $filter_medecin == $m['id_user'] ? 'selected' : '' ?>>
                            Dr. <?= htmlspecialchars($m['nom']) ." ". htmlspecialchars($m['prenom']) ?> (<?= htmlspecialchars($m['specialite']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="stats-grid">
            <div class="mega-card shadow-sm">
                <div class="icon-circle bg-new"><i class="fa-solid fa-user-plus"></i></div>
                <div>
                    <p class="text-muted small mb-0 fw-bold">NOUVEAUX PATIENTS</p>
                    <div class="h3 fw-bold mb-0"><?= $count_patients ?></div>
                </div>
            </div>
            <div class="mega-card shadow-sm">
                <div class="icon-circle bg-adm"><i class="fa-solid fa-hospital-user"></i></div>
                <div>
                    <p class="text-muted small mb-0 fw-bold">ADMISSIONS</p>
                    <div class="h3 fw-bold mb-0"><?= $count_admissions ?></div>
                </div>
            </div>
        </div>

        <div class="search-wrapper">
            <h5 class="fw-bold mb-3" style="font-size: 16px; color: #475569;">Rechercher un patient</h5>
            <form action="" method="GET" class="mb-0">
                <input type="hidden" name="date" value="<?= $selected_date ?>">
                <input type="hidden" name="medecin_id" value="<?= $filter_medecin ?>">
                <div class="modern-search-group">
                    <i class="fa-solid fa-magnifying-glass text-muted ms-2"></i>
                    <input type="text" name="q" placeholder="Nom, CIN ou Téléphone..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="search-btn">Rechercher</button>
                </div>
            </form>
            
            <?php if ($search_results): ?>
            <div class="mt-3">
                <?php foreach($search_results as $p): 
                    $initiale = strtoupper(substr($p['nom'], 0, 1));
                ?>
                <div class="result-card shadow-sm">
                    <div class="d-flex align-items-center gap-3">
                        <div class="patient-avatar"><?= $initiale ?></div>
                        <div>
                            <a href="dossier_patient.php?id=<?= $p['id_patient'] ?>" class="patient-name-link">
                                <?= strtoupper($p['nom']) ?> <?= strtoupper($p['prenom']) ?>
                            </a>
                            <div class="text-muted small">
                                CIN: <strong><?= $p['CIN'] ?></strong> | Tél: <?= $p['telephone'] ?>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="ajouter_admission.php?id=<?= $p['id_patient'] ?>" class="action-icon btn-admission" title="Admission">
                            <i class="fa-solid fa-plus-circle"></i>
                        </a>
                        <a href="dossier_patient.php?id=<?= $p['id_patient'] ?>" class="action-icon btn-dossier" title="Dossier">
                            <i class="fa-solid fa-folder-open"></i>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="section-box shadow-sm h-100">
                    <div class="section-header">
                        <h5 class="fw-bold mb-0">Flux du jour</h5>
                        <a href="ajouter_patient.php?medecin_id=<?= $filter_medecin ?>" class="btn btn-sm btn-success rounded-pill px-3">+ Nouveau Patient</a>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Heure</th>
                                <th>Patient</th>
                                <th>Médecin</th>
                                <th>Statut</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($admissions): ?>
                                <?php foreach($admissions as $a): ?>
                                <tr>
                                    <td class="fw-bold text-primary"><?= date('H:i', strtotime($a['date_admission'])) ?></td>
                                    <td class="fw-semibold"><?= htmlspecialchars($a['nom'].' '.$a['prenom']) ?></td>
                                    <td class="text-muted small">Dr. <?= htmlspecialchars($a['nom_medecin']) ?></td>
                                    <td>
                                        <span class="status-badge <?= $a['id_facture'] ? 'status-termine' : 'status-encours' ?>">
                                            <?= $a['id_facture'] ? 'Payé' : 'En attente' ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="dossier_patient.php?id=<?= $a['id_patient'] ?>" class="text-primary action-icon btn-dossier" style="width:30px; height:30px;">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>
                                        <a href="facturer_admission.php?id_admission=<?= $a['id_admission'] ?>" 
                   class="btn btn-sm btn-success rounded-pill px-3">
                   <i class="fa-solid fa-file-invoice-dollar me-1"></i> Facturer Sortie
                </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="text-center py-5 text-muted">Aucune activité.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="section-box shadow-sm h-100">
                    <h5 class="fw-bold mb-4">Agenda Suivis</h5>
                    <?php if($suivis_du_jour): ?>
                        <?php foreach($suivis_du_jour as $s): ?>
                        <div class="p-3 bg-light rounded-3 border-start border-4 border-primary mb-3 d-flex justify-content-between align-items-center">
                            <div>
                                <p class="mb-0 fw-bold small"><?= htmlspecialchars($s['nom'].' '.$s['prenom']) ?></p>
                                <span class="text-muted small"><?= htmlspecialchars($s['motif'] ?? 'Contrôle') ?></span>
                            </div>
                            <a href="dossier_patient.php?id=<?= $s['id_patient'] ?>" class="text-muted"><i class="fa-solid fa-chevron-right"></i></a>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fa-solid fa-calendar-xmark opacity-25 mb-2" style="font-size: 2rem;"></i>
                            <p class="small">Aucun suivi prévu.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
    function updateFilter(param, val) {
        if(!val && param === 'date') return;
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.set(param, val);
        window.location.href = "dashboard_secretaire.php?" + urlParams.toString();
    }
</script>
</body>
</html>