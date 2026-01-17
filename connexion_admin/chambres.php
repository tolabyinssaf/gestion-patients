<?php
session_start();
include("../config/connexion.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { 
    header("Location: ../login.php"); exit; 
}

// Récupération des statistiques des chambres
$stats = $pdo->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN statut = 'Occupée' THEN 1 ELSE 0 END) as occupees,
    SUM(CASE WHEN statut = 'Libre' THEN 1 ELSE 0 END) as libres
    FROM chambres")->fetch(PDO::FETCH_ASSOC);

// Filtre par service si nécessaire
$service_filter = isset($_GET['service']) ? $_GET['service'] : '';
$query = "SELECT * FROM chambres";
if ($service_filter) {
    $query .= " WHERE service = :service";
}
$query .= " ORDER BY numero_chambre ASC";

$stmt = $pdo->prepare($query);
if ($service_filter) $stmt->bindParam(':service', $service_filter);
$stmt->execute();
$chambres = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Infos Admin pour le header
$stmt_admin = $pdo->prepare("SELECT nom, prenom FROM utilisateurs WHERE id_user = ?");
$stmt_admin->execute([$_SESSION['user_id']]);
$admin = $stmt_admin->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Chambres | Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        :root { 
            --primary: #0f766e; 
            --primary-soft: #f0fdf4;
            --secondary: #64748b;
            --sidebar-bg: #0f172a;
            --bg-body: #f8fafc;
            --border: #e2e8f0;
        }
        
        body { background: var(--bg-body); font-family: 'Plus Jakarta Sans', sans-serif; display: flex; min-height: 100vh; margin: 0; }

        /* HEADER & SIDEBAR */
        header { 
            background: #ffffff; padding: 0 40px; height: 75px; 
            display: flex; justify-content: space-between; align-items: center; 
            border-bottom: 1px solid var(--border); position: fixed; 
            width: 100%; top: 0; z-index: 1050; 
        }
        .sidebar { 
            width: 260px; background: var(--sidebar-bg); height: 100vh; 
            position: fixed; top: 0; padding: 100px 16px 24px; z-index: 1000; 
        }
        .sidebar a { 
            display: flex; align-items: center; gap: 12px; color: #94a3b8; 
            text-decoration: none; padding: 12px 16px; border-radius: 10px; margin-bottom: 5px; transition: 0.3s; 
        }
        .sidebar a:hover, .sidebar a.active { background: var(--primary); color: #ffffff; }

        .main-wrapper { flex: 1; margin-left: 260px; padding-top: 75px; }
        .content-container { padding: 40px; }

        /* CARDS STATS */
        .stat-card {
            background: white; border-radius: 16px; padding: 20px;
            border: 1px solid var(--border); display: flex; align-items: center; gap: 15px;
        }
        .stat-icon {
            width: 48px; height: 48px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center; font-size: 1.2rem;
        }

        /* CHAMBRE CARD */
        .room-card {
            background: white; border-radius: 16px; border: 1px solid var(--border);
            transition: all 0.3s ease; overflow: hidden;
        }
        .room-card:hover { transform: translateY(-5px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); }
        
        .room-header { padding: 15px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
        .room-body { padding: 15px; }
        
        .status-badge { padding: 5px 10px; border-radius: 8px; font-size: 0.75rem; font-weight: 700; }
        .status-libre { background: #dcfce7; color: #166534; }
        .status-occupe { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>

<header>
    <img src="../images/logo_app2.png" alt="Logo" style="height: 35px;">
    
    <div class="d-flex align-items-center gap-4">
        <div class="dropdown">
            <button class="btn border-0 position-relative" type="button" data-bs-toggle="dropdown">
                <i class="fa-solid fa-bell fs-5 text-secondary"></i>
                <span id="notif-badge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="display:none; font-size:0.6rem;">0</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow border-0 p-0" id="notif-list" style="width: 280px; border-radius: 12px;">
                <li class="p-2 text-center text-muted small">Aucune notification</li>
            </ul>
        </div>

        <div style="background: var(--primary-soft); color: var(--primary); padding: 8px 16px; border-radius: 12px; font-weight: 700;">
            <i class="fa-solid fa-user-shield me-2"></i>ADMIN : <?= strtoupper($admin['nom']) ?>
        </div>
    </div>
</header>

 <aside class="sidebar">
        <a href="dashboard_admin.php" ><i class="fa-solid fa-chart-pie"></i> Vue Générale</a>
        <a href="utilisateurs.php" ><i class="fa-solid fa-user-md"></i> Utilisateurs</a>
        <a href="prestations.php"  ><i class="fa-solid fa-list-check"></i> Actes & Tarifs</a>
        <a href="chambres.php" class="active"><i class="fa-solid fa-bed"></i> Gestion Chambres</a>
        <div style="height: 1px; background: rgba(255,255,255,0.1); margin: 20px 0;"></div>
        <a href="facturation_list.php"><i class="fa-solid fa-file-invoice-dollar"></i> Rapports Financiers</a>
        <a href="profil.php" class="<?= basename($_SERVER['PHP_SELF']) == 'profil.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-user-gear"></i> Mon Profil
        </a>
        <a href="../logout.php" style="color: #fda4af; margin-top: 20px;"><i class="fa-solid fa-power-off"></i> Déconnexion</a>
    </aside>

<div class="main-wrapper">
    <main class="content-container">
        
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <h2 class="fw-800 text-dark mb-1">Gestion des Chambres</h2>
                <p class="text-secondary mb-0">Suivi en temps réel de l'occupation des lits</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-primary px-4 py-2 rounded-3 fw-bold" style="background: var(--primary); border:none;">
                    <i class="fa-solid fa-plus me-2"></i>Nouvelle Chambre
                </button>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon bg-light text-secondary"><i class="fa-solid fa-door-open"></i></div>
                    <div><div class="small text-muted">Total</div><div class="fw-bold fs-5"><?= $stats['total'] ?></div></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon" style="background: #dcfce7; color: #166534;"><i class="fa-solid fa-check-circle"></i></div>
                    <div><div class="small text-muted">Libres</div><div class="fw-bold fs-5"><?= $stats['libres'] ?></div></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon" style="background: #fee2e2; color: #991b1b;"><i class="fa-solid fa-bed"></i></div>
                    <div><div class="small text-muted">Occupées</div><div class="fw-bold fs-5"><?= $stats['occupees'] ?></div></div>
                </div>
            </div>
        </div>

        <div class="glass-card mb-4 p-3">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <select name="service" class="form-select border-0 bg-light rounded-3 shadow-none">
                        <option value="">Tous les services</option>
                        <option value="Urgences" <?= $service_filter == 'Urgences' ? 'selected' : '' ?>>Urgences</option>
                        <option value="Cardiologie" <?= $service_filter == 'Cardiologie' ? 'selected' : '' ?>>Cardiologie</option>
                        <option value="Pédiatrie" <?= $service_filter == 'Pédiatrie' ? 'selected' : '' ?>>Pédiatrie</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-dark w-100 rounded-3"><i class="fa-solid fa-filter me-2"></i>Filtrer</button>
                </div>
            </form>
        </div>

        <div class="row g-4">
            <?php foreach($chambres as $c): ?>
            <div class="col-md-3">
                <div class="room-card">
                    <div class="room-header">
                        <span class="fw-800 fs-5 text-dark"># <?= $c['numero_chambre'] ?></span>
                        <span class="status-badge <?= $c['statut'] == 'Libre' ? 'status-libre' : 'status-occupe' ?>">
                            <i class="fa-solid fa-circle me-1" style="font-size: 0.5rem;"></i> <?= $c['statut'] ?>
                        </span>
                    </div>
                    <div class="room-body">
                        <div class="text-secondary small fw-bold mb-1 text-uppercase"><?= $c['service'] ?></div>
                        <div class="small text-muted"><i class="fa-solid fa-layer-group me-2"></i>Étage : <?= $c['etage'] ?></div>
                        <div class="mt-3">
                            <?php if($c['statut'] == 'Libre'): ?>
                                <button class="btn btn-outline-success btn-sm w-100 rounded-pill">Assigner Patient</button>
                            <?php else: ?>
                                <button class="btn btn-outline-secondary btn-sm w-100 rounded-pill">Détails Occupation</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>