<?php
session_start();
include("../config/connexion.php");

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'secretaire') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$selected_date = $_GET['date_filter'] ?? '';
$search = $_GET['q'] ?? '';

$stmt = $pdo->prepare("SELECT nom, prenom FROM utilisateurs WHERE id_user = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$query = "SELECT * FROM patients WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (cin LIKE ? OR nom LIKE ? OR telephone LIKE ?)";
    $params = ["%$search%", "%$search%", "%$search%"];
}

if (!empty($selected_date)) {
    $query .= " AND DATE(date_inscription) = ?";
    $params[] = $selected_date;
}

$query .= " ORDER BY date_inscription DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>MedCare | Annuaire Patients</title>
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
        body { background: var(--bg-body); color: #1e293b; }

        header {
            background: var(--white); padding: 0 40px; height: var(--header-height);
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid var(--border); position: fixed; top: 0; left: 0; right: 0; z-index: 1000;
        }

        .sidebar { 
            width: var(--sidebar-width); background: var(--sidebar-bg); padding: 24px 16px; 
            position: fixed; top: var(--header-height); left: 0; bottom: 0; z-index: 999;
        }
        
        .content { 
            margin-left: var(--sidebar-width); 
            padding: 30px 40px; 
            margin-top: var(--header-height);
        }

        .user-pill { background: var(--primary-light); padding: 8px 18px; border-radius: 12px; display: flex; align-items: center; gap: 10px; font-size: 14px; font-weight: 600; color: var(--primary); }
        
        .sidebar h3 { color: rgba(255,255,255,0.3); font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 20px; padding-left: 12px; }
        .sidebar a { display: flex; align-items: center; gap: 12px; color: #94a3b8; text-decoration: none; padding: 12px 16px; border-radius: 10px; margin-bottom: 5px; transition: 0.3s; }
        .sidebar a:hover { background: rgba(255,255,255,0.05); color: #fff; }
        .sidebar a.active { background: var(--primary); color: #fff; box-shadow: 0 4px 12px rgba(15, 118, 110, 0.3); }

        /* --- STYLISATION TABLEAU --- */
        .table-container {
            background: var(--white);
            border-radius: 16px;
            border: 1px solid var(--border);
            overflow: hidden;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
        }

        .custom-table { width: 100%; border-collapse: collapse; }
        .custom-table th {
            background: #fcfdfe;
            padding: 20px 25px;
            color: #64748b;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            font-weight: 700;
            border-bottom: 2px solid #f1f5f9;
        }

        .custom-table tr { transition: all 0.2s ease; }
        .custom-table tr:hover { background-color: #f8fafc; }
        
        .custom-table td {
            padding: 16px 25px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
            color: #334155;
        }

        /* --- ELEMENTS DESIGN --- */
        .avatar-green {
            background: linear-gradient(135deg, #ccfbf1 0%, #99f6e4 100%);
            color: #0f766e; 
            width:44px; height:44px; 
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 16px;
        }

        .name-wrapper .name { font-weight: 700; color: #0f172a; font-size: 15px; margin-bottom: 2px; }
        .name-wrapper .sub { font-size: 12px; color: #94a3b8; }

        .badge-cin { 
            background: #f1f5f9; color: #475569; 
            padding: 6px 12px; border-radius: 8px; 
            font-weight: 600; font-size: 12px; border: 1px solid #e2e8f0;
        }

        .phone-tag {
            display: inline-flex; align-items: center; gap: 8px;
            color: #0f766e; font-weight: 600; font-size: 13px;
            background: #f0fdfa; padding: 4px 10px; border-radius: 6px;
        }

        /* --- BOUTONS --- */
        .btn-action {
            width: 40px; height: 40px; border-radius: 12px;
            display: inline-flex; align-items: center; justify-content: center;
            transition: 0.3s; border: 1px solid var(--border);
            background: white; color: #64748b; text-decoration: none;
        }
        .btn-edit:hover { background: #fffbeb; color: #d97706; border-color: #fef3c7; transform: translateY(-2px); }
        .btn-folder:hover { background: #eff6ff; color: #2563eb; border-color: #dbeafe; transform: translateY(-2px); }

        .sticky-search {
            position: sticky; top: var(--header-height);
            background: var(--bg-body); z-index: 900; padding: 20px 0;
        }
        .search-box {
            background: var(--white); padding: 12px 20px; border-radius: 16px;
            border: 1px solid var(--border); display: flex; gap: 15px; align-items: center;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        
        .date-trigger {
            background: #fff; border: 1px solid var(--border);
            color: #475569; padding: 10px 15px; border-radius: 12px;
            font-weight: 600; font-size: 14px; cursor: pointer;
            position: relative; transition: 0.2s;
        }
        .date-trigger:hover { border-color: var(--primary); color: var(--primary); }
        .date-trigger input[type="date"] { position: absolute; opacity: 0; inset: 0; cursor: pointer; }

    </style>
</head>
<body>

<header>
    <img src="../images/logo_app2.png" alt="Logo" style="height: 45px;">
    <div class="user-pill">
        <i class="fa-solid fa-circle-user fa-lg"></i>
        <span>Séc. <?= htmlspecialchars($user['prenom']." ".$user['nom']) ?></span>
    </div>
</header>

<div class="wrapper">
    <aside class="sidebar">
        <h3>Menu Gestion</h3>
        <a href="dashboard_secretaire.php"><i class="fa-solid fa-chart-line"></i> Tableau de bord</a>
        <a href="patients_secr.php" class="active"><i class="fa-solid fa-user-group"></i> Patients</a>
        <a href="admissions.php"><i class="fa-solid fa-door-open"></i> Salle d'attente</a>
        <a href="suivis.php"><i class="fa-solid fa-calendar-check"></i> Suivis du jour</a>
        <a href="caisse.php"><i class="fa-solid fa-wallet"></i> Caisse & Factures</a>
        <div style="height: 1px; background: rgba(255,255,255,0.1); margin: 20px 0;"></div>
        <a href="../connexio_utilisateur/deconnexion.php" style="color: #fda4af;"><i class="fa-solid fa-power-off"></i> Déconnexion</a>
    </aside>

    <main class="content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 fw-bold mb-1" style="letter-spacing: -0.5px;">Annuaire Patients</h1>
                <p class="text-muted small mb-0">Base de données centralisée du cabinet</p>
            </div>
            <a href="ajouter_patient.php" class="btn btn-primary px-4 py-2 rounded-pill shadow-sm fw-bold" style="background: var(--primary); border:none;">
                <i class="fa-solid fa-plus me-2"></i>Nouveau Patient
            </a>
        </div>

        <div class="sticky-search">
            <form action="" method="GET" id="filterForm" class="search-box">
                <i class="fa-solid fa-magnifying-glass text-muted ms-2"></i>
                <input type="text" name="q" class="form-control border-0 shadow-none p-0" placeholder="Rechercher un nom, un CIN ou un numéro..." value="<?= htmlspecialchars($search) ?>">
                
                <div class="date-trigger">
                    <i class="fa-solid fa-calendar-alt me-2 text-muted"></i>
                    <span><?= $selected_date ? date('d/m/Y', strtotime($selected_date)) : "Filtrer par date" ?></span>
                    <input type="date" name="date_filter" id="dateInput" value="<?= $selected_date ?>" onchange="this.form.submit()">
                </div>

                <?php if($search || $selected_date): ?>
                    <a href="patients_secr.php" class="btn btn-link text-danger text-decoration-none fw-bold small p-0 px-2">Réinitialiser</a>
                <?php endif; ?>

                <button type="submit" class="btn btn-dark rounded-pill px-4 fw-bold">Rechercher</button>
            </form>
        </div>

        <div class="table-container">
            <?php if($patients): ?>
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Identité (CIN)</th>
                            <th>Coordonnées</th>
                            <th>Inscription</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($patients as $p): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="avatar-green">
                                        <?= strtoupper(substr($p['nom'], 0, 1)) ?>
                                    </div>
                                    <div class="name-wrapper">
                                        <div class="name"><?= strtoupper($p['nom']) ?> <?= $p['prenom'] ?></div>
                                        <div class="sub">Patient régulier</div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge-cin"><?= $p['CIN'] ?></span></td>
                            <td>
                                <div class="phone-tag">
                                    <i class="fa-solid fa-phone"></i>
                                    <?= $p['telephone'] ?>
                                </div>
                            </td>
                            <td>
                                <div class="text-muted small fw-bold">
                                    <i class="fa-regular fa-calendar-check me-2 opacity-50"></i>
                                    <?= date('d/m/Y', strtotime($p['date_inscription'])) ?>
                                </div>
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-2">
                                    <a href="modifier_patient.php?id=<?= $p['id_patient'] ?>" class="btn-action btn-edit" title="Modifier la fiche">
                                        <i class="fa-solid fa-user-pen"></i>
                                    </a>
                                    <a href="dossier_patient.php?id=<?= $p['id_patient'] ?>" class="btn-action btn-folder" title="Voir le dossier médical">
                                        <i class="fa-solid fa-folder-open"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="p-5 text-center">
                    <img src="https://cdn-icons-png.flaticon.com/512/6134/6134065.png" alt="Empty" style="width: 80px; opacity: 0.3;" class="mb-3">
                    <h5 class="fw-bold text-muted">Aucun patient trouvé</h5>
                    <p class="text-muted small">Modifiez vos critères pour affiner la recherche.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<script>
    // Déclencheur automatique du picker de date sur l'icône/texte
    document.querySelector('.date-trigger').addEventListener('click', function() {
        document.getElementById('dateInput').showPicker();
    });
</script>

</body>
</html>