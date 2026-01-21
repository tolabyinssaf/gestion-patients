<?php
session_start();
include("../config/connexion.php");

// Vérification de l'authentification Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { 
    header("Location: ../login.php"); 
    exit; 
}

$view = $_GET['view'] ?? 'menu';

// Récupération des données selon la vue
$data = [];
if ($view === 'patients') {
    $data = $pdo->query("SELECT * FROM patients_archive ORDER BY date_supprimee DESC")->fetchAll();
} elseif ($view === 'utilisateurs') {
    $data = $pdo->query("SELECT * FROM archives_utilisateurs ORDER BY date_suppression DESC")->fetchAll();
}

// Comptages pour les cartes du menu
$countPatients = $pdo->query("SELECT COUNT(*) FROM patients_archive")->fetchColumn();
$countUsers = $pdo->query("SELECT COUNT(*) FROM archives_utilisateurs")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Archives Admin | MedCare Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0d9488; 
            --primary-dark: #0f172a;
            --sidebar-color: #1e293b;
            --table-head: #111827;
            --accent-patients: #10b981; 
            --accent-staff: #6366f1; 
        }
        
        body { background-color: #f8fafc; font-family: 'Plus Jakarta Sans', sans-serif; margin: 0; }

        /* HEADER */
        header { 
            background: #ffffff; padding: 0 40px; height: 75px; 
            display: flex; justify-content: space-between; align-items: center; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.05); position: fixed; width: 100%; top: 0; z-index: 1000; 
        }
        .user-profile { display: flex; align-items: center; gap: 12px; background: var(--primary-dark); padding: 6px 15px; border-radius: 50px; color: white; }

        /* SIDEBAR */
        .sidebar { width: 260px; background: var(--primary-dark); height: 100vh; position: fixed; padding: 100px 16px; }
        .sidebar a { display: flex; align-items: center; gap: 12px; color: #94a3b8; text-decoration: none; padding: 12px 16px; border-radius: 12px; margin-bottom: 5px; transition: 0.3s; }
        .sidebar a:hover, .sidebar a.active { background: var(--primary); color: white; }
        .sidebar p { color: #475569; font-size: 0.7rem; text-transform: uppercase; font-weight: 800; padding-left: 15px; margin-top: 25px; }

        .main-content { margin-left: 260px; padding: 110px 40px; }

        /* CARTES MENU (STYLE SOMBRE) */
        .menu-card {
            background: var(--sidebar-color); border-radius: 28px; padding: 40px 30px;
            border: 1px solid rgba(255,255,255,0.05); text-decoration: none !important;
            height: 100%; display: flex; flex-direction: column; align-items: center; text-align: center;
            transition: all 0.4s; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2);
        }
        .menu-card:hover { transform: translateY(-10px); background: #0f172a; }
        .icon-box { width: 70px; height: 70px; border-radius: 20px; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin-bottom: 20px; }
        
        .card-patients .icon-box { background: rgba(16, 185, 129, 0.2); color: var(--accent-patients); }
        .card-staff .icon-box { background: rgba(99, 102, 241, 0.2); color: var(--accent-staff); }
        
        .menu-card h4 { color: white; font-weight: 700; }
        .menu-card p { color: #94a3b8; font-size: 0.85rem; }

        /* TABLEAU MODERNE */
        .table-wrapper {
            background: white; border-radius: 24px; padding: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;
        }
        .custom-table { width: 100%; border-collapse: separate; border-spacing: 0 10px; }
        .custom-table thead th {
            background: var(--table-head); color: #cbd5e1;
            padding: 20px; font-size: 0.75rem; text-transform: uppercase;
            letter-spacing: 1px; border: none; font-weight: 700;
        }
        .custom-table thead th:first-child { border-radius: 12px 0 0 12px; }
        .custom-table thead th:last-child { border-radius: 0 12px 12px 0; }

        .custom-table tbody tr { background: #ffffff; transition: 0.3s; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
        .custom-table tbody td { padding: 18px 20px; border-top: 1px solid #f1f5f9; border-bottom: 1px solid #f1f5f9; color: #1e293b; font-weight: 500; }
        
        .custom-table tbody td:first-child { border-radius: 12px 0 0 12px; }
        .custom-table tbody td:last-child { border-radius: 0 12px 12px 0; }

        /* Liseré de couleur sur le côté */
        .row-patient { border-left: 4px solid var(--accent-patients) !important; }
        .row-staff { border-left: 4px solid var(--accent-staff) !important; }

        .custom-table tbody tr:hover { background: #f8fafc; transform: scale(1.005); }

        /* RECHERCHE */
        .search-bar {
            background: #f1f5f9; border-radius: 15px; border: 1px solid #e2e8f0;
            padding: 12px 20px; display: flex; align-items: center; margin-bottom: 25px;
        }
        .search-bar input { background: transparent; border: none; outline: none; width: 100%; margin-left: 10px; font-weight: 600; color: var(--primary-dark); }

        /* BADGES */
        .tag { padding: 6px 12px; border-radius: 8px; font-size: 0.75rem; font-weight: 700; }
        .tag-date { background: var(--primary-dark); color: #fff; }
        .tag-role { background: #e0e7ff; color: #4338ca; }
    </style>
</head>
<body>

<header>
    <img src="../images/logo_app2.png" alt="Logo" style="height: 40px;">
    <div class="user-profile">
        <i class="fa-solid fa-shield-halved"></i>
        <span style="font-weight: 700; font-size: 0.85rem;">ADMINISTRATEUR</span>
    </div>
</header>

<aside class="sidebar">
    <p>Gestion</p>
    <a href="dashboard_admin.php"><i class="fa-solid fa-chart-pie"></i> Vue Générale</a>
    <a href="utilisateurs.php"><i class="fa-solid fa-user-md"></i> Utilisateurs</a>
    <a href="prestations.php"><i class="fa-solid fa-list-check"></i> Actes & Tarifs</a>
    
    <p>Système</p>
    <a href="archives.php" class="active"><i class="fa-solid fa-box-archive"></i> Archives</a>
    <a href="profil.php"><i class="fa-solid fa-user-gear"></i> Mon Profil</a>
    <a href="../connexio_utilisateur/login.php" style="margin-top: 50px; color: #fb7185;"><i class="fa-solid fa-power-off"></i> Déconnexion</a>
</aside>

<main class="main-content">
    <?php if ($view === 'menu'): ?>
        <div class="mb-5">
            <h2 style="font-weight: 800; color: var(--primary-dark);">Centre d'Archivage</h2>
            <p class="text-muted">Gestion des données historiques du système.</p>
        </div>

        <div class="row g-4">
            <div class="col-md-6">
                <a href="?view=patients" class="menu-card card-patients">
                    <div class="icon-box"><i class="fa-solid fa-hospital-user"></i></div>
                    <h4>Archives Patients</h4>
                    <p>Historique des dossiers médicaux supprimés.</p>
                    <div class="mt-3 tag tag-date"><?= $countPatients ?> Enregistrements</div>
                </a>
            </div>
            <div class="col-md-6">
                <a href="?view=utilisateurs" class="menu-card card-staff">
                    <div class="icon-box"><i class="fa-solid fa-user-tie"></i></div>
                    <h4>Archives Personnel</h4>
                    <p>Anciens comptes utilisateurs et staff.</p>
                    <div class="mt-3 tag tag-date"><?= $countUsers ?> Enregistrements</div>
                </a>
            </div>
        </div>

    <?php else: ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 style="font-weight: 800;"><?= $view === 'patients' ? 'Registre Patients Archivés' : 'Registre Personnel Archivé' ?></h3>
            <a href="archives.php" class="btn btn-dark btn-sm rounded-pill px-4">Retour</a>
        </div>

        <div class="search-bar">
            <i class="fa-solid fa-search text-muted"></i>
            <input type="text" id="archiveSearch" placeholder="Recherche rapide dans les archives...">
        </div>

        <div class="table-wrapper">
            <div class="table-responsive">
                <table class="custom-table" id="tableData">
                    <thead>
                        <?php if ($view === 'patients'): ?>
                            <tr>
                                <th>Identité du Patient</th>
                                <th>Sexe / Naissance</th>
                                <th>Coordonnées</th>
                                <th>Date d'Archivage</th>
                                <th>ID Dossier</th>
                            </tr>
                        <?php else: ?>
                            <tr>
                                <th>Nom & Spécialité</th>
                                <th>Identifiants</th>
                                <th>Rôle</th>
                                <th>Date d'Archivage</th>
                                <th>Téléphone</th>
                            </tr>
                        <?php endif; ?>
                    </thead>
                    <tbody>
                        <?php foreach ($data as $item): ?>
                            <tr class="<?= $view === 'patients' ? 'row-patient' : 'row-staff' ?>">
                                <?php if ($view === 'patients'): ?>
                                    <td><strong style="color: var(--primary-dark);"><?= strtoupper($item['nom']) ?></strong> <?= $item['prenom'] ?></td>
                                    <td><span class="tag" style="background: #e2e8f0;"><?= $item['sexe'] ?></span> <small class="ms-2"><?= $item['date_naissance'] ?></small></td>
                                    <td><i class="fa-solid fa-envelope me-2 text-muted"></i><?= $item['email'] ?></td>
                                    <td><span class="tag tag-date"><?= date('d/m/Y', strtotime($item['date_supprimee'])) ?></span></td>
                                    <td><code>#<?= $item['id_patient'] ?></code></td>
                                <?php else: ?>
                                    <td>
                                        <div class="fw-bold" style="color: var(--primary-dark);"><?= strtoupper($item['nom']) ?> <?= $item['prenom'] ?></div>
                                        <small class="text-muted"><?= $item['specialite'] ?: 'Service Administratif' ?></small>
                                    </td>
                                    <td>
                                        <div class="small">Matricule: <strong><?= $item['matricule'] ?></strong></div>
                                        <div class="small text-muted">CIN: <?= $item['cin'] ?></div>
                                    </td>
                                    <td><span class="tag tag-role"><?= strtoupper($item['role']) ?></span></td>
                                    <td><span class="tag tag-date bg-danger"><?= date('d/m/Y', strtotime($item['date_suppression'])) ?></span></td>
                                    <td><?= $item['telephone'] ?></td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</main>

<script>
    document.getElementById('archiveSearch')?.addEventListener('keyup', function() {
        let value = this.value.toLowerCase();
        let rows = document.querySelectorAll('#tableData tbody tr');
        rows.forEach(row => {
            row.style.display = row.innerText.toLowerCase().includes(value) ? '' : 'none';
        });
    });
</script>

</body>
</html>