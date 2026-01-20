<?php
session_start();
include "../config/connexion.php";

// Sécurité : Vérification Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { 
    header("Location: ../login.php"); 
    exit; 
}

// 1. Stats pour le Header (Dynamiques)
$stats = $pdo->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN etat = 'libre' THEN 1 ELSE 0 END) as libres,
    SUM(CASE WHEN etat = 'maintenance' THEN 1 ELSE 0 END) as maint,
    SUM(capacite) as total_lits
    FROM chambres")->fetch();

// 2. Récupération des données groupées par Bloc
$query = "SELECT * FROM chambres ORDER BY bloc ASC, numero_chambre ASC";
$chambres = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);

$blocs = [];
foreach($chambres as $c) {
    // Calcul de l'occupation actuelle
    $stmt_occ = $pdo->prepare("SELECT COUNT(*) FROM admissions WHERE id_chambre = ? AND statut = 'En cours'");
    $stmt_occ->execute([$c['id_chambre']]);
    $c['occupe_actuel'] = $stmt_occ->fetchColumn();

    // Activité récente
    $stmt_logs = $pdo->prepare("SELECT COUNT(*) FROM admission_logs al 
                                JOIN admissions a ON al.id_admission = a.id_admission 
                                WHERE a.id_chambre = ? AND DATE(al.created_at) = CURDATE()");
    $stmt_logs->execute([$c['id_chambre']]);
    $c['has_recent_activity'] = $stmt_logs->fetchColumn() > 0;

    $blocs[$c['bloc']][] = $c;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion Infrastructure | MedCare</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
   <style>
    :root { 
        --primary: #0f766e; 
        --primary-hover: #115e59; 
        --sidebar-bg: #0f172a; 
        --bg-body: #f1f5f9; 
        --white: #ffffff; 
        --border: #e2e8f0; 
        --card-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        /* Couleurs pour les cartes foncées */
        --card-dark-bg: #1e293b; 
        --card-text-main: #f8fafc;
        --card-text-muted: #94a3b8;
    }
    
    body { background: var(--bg-body); font-family: 'Plus Jakarta Sans', sans-serif; margin: 0; color: #1e293b; }
    
    header { 
        background: var(--white); padding: 0 40px; height: 75px; 
        display: flex; justify-content: space-between; align-items: center; 
        border-bottom: 1px solid var(--border); position: fixed; width: 100%; top: 0; z-index: 1000;
    }
    .user-pill { background: #f0fdfa; padding: 8px 18px; border-radius: 12px; color: var(--primary); font-weight: 600; display: flex; align-items: center; gap: 10px; }

    .wrapper { display: flex; padding-top: 75px; }
    .sidebar { 
        width: 260px; background: var(--sidebar-bg); height: calc(100vh - 75px); 
        position: fixed; padding: 24px 16px; flex-shrink: 0; 
    }
    .sidebar a { 
        display: flex; align-items: center; gap: 12px; color: #94a3b8; 
        text-decoration: none; padding: 12px 16px; border-radius: 10px; margin-bottom: 5px; transition: 0.3s; 
    }
    .sidebar a:hover, .sidebar a.active { background: var(--primary); color: white; box-shadow: 0 4px 12px rgba(15, 118, 110, 0.3); }

    .main-content { margin-left: 260px; padding: 40px; width: calc(100% - 260px); }

    .stat-card { 
        background: white; border-radius: 20px; padding: 25px; 
        box-shadow: var(--card-shadow); border: none; border-bottom: 4px solid var(--primary);
        height: 100%;
    }
    
    /* MODIFICATION : CARTES FONCÉES */
    .room-card {
        background: var(--card-dark-bg); /* Fond foncé */
        border-radius: 24px; 
        border: 1px solid #334155; 
        padding: 24px; 
        transition: 0.4s; 
        position: relative; 
        box-shadow: var(--card-shadow);
    }
    .room-card:hover { transform: translateY(-8px); box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2); border-color: var(--primary); }

    /* Ajustement du texte dans les cartes */
    .room-card h4 { color: var(--card-text-main); }
    .room-card p.text-muted { color: var(--card-text-muted) !important; }
    .room-card .text-muted.fw-bold { color: var(--card-text-muted) !important; }
    .room-card .fw-800 { color: var(--card-text-main); }

    .status-pill { font-size: 10px; font-weight: 800; padding: 6px 14px; border-radius: 50px; text-transform: uppercase; }
    .status-libre { background: rgba(21, 128, 61, 0.2); color: #4ade80; border: 1px solid rgba(74, 222, 128, 0.3); }
    .status-complet { background: rgba(185, 28, 28, 0.2); color: #f87171; border: 1px solid rgba(248, 113, 113, 0.3); }
    .status-maintenance { background: rgba(154, 52, 18, 0.2); color: #fbbf24; border: 1px solid rgba(251, 191, 36, 0.3); }

    .progress { background: #0f172a; border-radius: 10px; height: 8px; overflow: hidden; margin: 15px 0; }
    .feature-tag { font-size: 11px; font-weight: 700; color: #cbd5e1; background: #334155; padding: 5px 12px; border-radius: 8px; }
    .has-oxy { background: rgba(3, 105, 161, 0.3); color: #7dd3fc; border: 1px solid rgba(125, 211, 252, 0.2); }

    .bloc-title { 
        font-size: 1.1rem; font-weight: 800; color: #0f172a; 
        margin: 40px 0 20px; display: flex; align-items: center; gap: 15px; 
    }
    .bloc-title::after { content: ''; flex: 1; height: 2px; background: var(--border); }

    .btn-add { background: var(--primary); border: none; border-radius: 12px; padding: 10px 20px; font-weight: 600; color: white; }
    .btn-add:hover { background: var(--primary-hover); }
    
    /* Boutons dans les cartes foncées */
    .room-card .btn-light {
        background: #334155;
        border: 1px solid #475569 !important;
        color: white !important;
    }
    .room-card .btn-light:hover {
        background: var(--primary);
        border-color: var(--primary) !important;
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
        <div class="d-flex justify-content-between align-items-end mb-5">
            <div>
                <h2 class="fw-800 mb-1">Infrastructure Hospitalière</h2>
                <p class="text-muted mb-0">Disponibilité des lits et gestion des unités</p>
            </div>
            <a href="formulaire_chambre.php" class="btn btn-add">
                <i class="fa-solid fa-plus me-2"></i> Ajouter une Unité
            </a>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="stat-card">
                    <small class="text-muted fw-bold text-uppercase" style="font-size: 11px;">Capacité Totale</small>
                    <h3 class="fw-800 mt-2 mb-0"><?= $stats['total'] ?> <span class="fs-6 opacity-50">UNITÉS</span></h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card" style="border-bottom-color: #10b981;">
                    <small class="text-muted fw-bold text-uppercase" style="font-size: 11px;">Unités Libres</small>
                    <h3 class="fw-800 mt-2 mb-0" style="color: #10b981;"><?= $stats['libres'] ?> <span class="fs-6 opacity-50 text-dark">DISPO</span></h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card" style="border-bottom-color: #f59e0b;">
                    <small class="text-muted fw-bold text-uppercase" style="font-size: 11px;">En Maintenance</small>
                    <h3 class="fw-800 mt-2 mb-0" style="color: #f59e0b;"><?= $stats['maint'] ?> <span class="fs-6 opacity-50 text-dark">CHAMBRES</span></h3>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 mb-5 p-3" style="background: white;">
            <div class="row g-3 align-items-center">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                        <input type="text" id="searchInput" class="form-control border-0 bg-light" placeholder="Chercher une chambre, un service...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select id="statusFilter" class="form-select border-0 bg-light">
                        <option value="">Tous les états</option>
                        <option value="libre">Libre</option>
                        <option value="complet">Complet</option>
                        <option value="maintenance">Maintenance</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-center justify-content-end gap-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="oxyFilter">
                        <label class="form-check-label fw-bold small">Oxygène</label>
                    </div>
                    <span id="resultCount" class="badge bg-dark rounded-pill px-3"></span>
                </div>
            </div>
        </div>

        <?php foreach($blocs as $bloc_name => $rooms): ?>
            <div class="bloc-title"><?= strtoupper($bloc_name) ?></div>
            <div class="row g-4 mb-4">
                <?php foreach($rooms as $c): 
                    $pourcentage = ($c['occupe_actuel'] / $c['capacite']) * 100;
                    $bar_color = ($pourcentage >= 100) ? '#ef4444' : (($pourcentage >= 50) ? '#f59e0b' : '#10b981');
                ?>
                <div class="col-xl-3 col-lg-4 col-md-6 room-wrapper">
                    <div class="room-card" 
                         data-num="<?= $c['numero_chambre'] ?>" 
                         data-service="<?= $c['service'] ?>" 
                         data-status="<?= $c['etat'] ?>" 
                         data-oxy="<?= $c['oxigene'] ?>">
                        
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="status-pill status-<?= $c['etat'] ?>"><?= $c['etat'] ?></span>
                            <?php if($c['has_recent_activity']): ?>
                                <i class="fa-solid fa-bolt-lightning text-primary" title="Activité aujourd'hui"></i>
                            <?php endif; ?>
                        </div>
                        
                        <h4 class="fw-800 mb-0">Chambre <?= $c['numero_chambre'] ?></h4>
                        <p class="text-muted small fw-600"><?= $c['service'] ?></p>

                        <div class="my-3">
                            <div class="d-flex justify-content-between small mb-1">
                                <span class="text-muted fw-bold">Occupation</span>
                                <span class="fw-800"><?= $c['occupe_actuel'] ?> / <?= $c['capacite'] ?></span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar" style="width: <?= $pourcentage ?>%; background-color: <?= $bar_color ?>;"></div>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2 mb-4">
                            <span class="feature-tag"><i class="fa-solid fa-bed me-1"></i> <?= $c['type_lit'] ?></span>
                            <?php if($c['oxigene']): ?>
                                <span class="feature-tag has-oxy"><i class="fa-solid fa-wind me-1"></i> O₂ Central</span>
                            <?php endif; ?>
                        </div>

                        <div class="pt-3 border-top d-flex gap-2">
                            <a href="room_view.php?id=<?= $c['id_chambre'] ?>" class="btn btn-light btn-sm flex-grow-1 fw-bold border rounded-pill">
                                <i class="fa-solid fa-eye me-1"></i> Gérer
                            </a>
                            <a href="formulaire_chambre.php?id=<?= $c['id_chambre'] ?>" class="btn btn-light btn-sm border rounded-pill">
                                <i class="fa-solid fa-pen"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </main>
</div>

<script>
// Le script de filtrage reste identique, il fonctionne parfaitement avec cette structure
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const oxyFilter = document.getElementById('oxyFilter');
    const wrappers = document.querySelectorAll('.room-wrapper');
    const resultCount = document.getElementById('resultCount');

    function filterRooms() {
        const searchTerm = searchInput.value.toLowerCase();
        const statusTerm = statusFilter.value.toLowerCase();
        const oxyOnly = oxyFilter.checked;
        let visibleCount = 0;

        wrappers.forEach(wrapper => {
            const card = wrapper.querySelector('.room-card');
            const num = card.dataset.num.toLowerCase();
            const service = card.dataset.service.toLowerCase();
            const status = card.dataset.status.toLowerCase();
            const hasOxy = card.dataset.oxy === "1";

            const matchesSearch = num.includes(searchTerm) || service.includes(searchTerm);
            const matchesStatus = statusTerm === "" || status === statusTerm;
            const matchesOxy = !oxyOnly || hasOxy;

            if (matchesSearch && matchesStatus && matchesOxy) {
                wrapper.style.display = 'block';
                visibleCount++;
            } else {
                wrapper.style.display = 'none';
            }
        });
        resultCount.textContent = `${visibleCount} Unités`;
    }

    searchInput.addEventListener('input', filterRooms);
    statusFilter.addEventListener('change', filterRooms);
    oxyFilter.addEventListener('change', filterRooms);
    filterRooms();
});
</script>
</body>
</html>