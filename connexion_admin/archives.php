<?php
session_start();
include("../config/connexion.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { 
    header("Location: ../login.php"); exit; 
}

$view = $_GET['view'] ?? 'menu';

// Récupération des données
$data = [];
if ($view === 'patients') {
    $data = $pdo->query("SELECT * FROM patients_archive ORDER BY date_supprimee DESC")->fetchAll();
} elseif ($view === 'utilisateurs') {
    $data = $pdo->query("SELECT * FROM archives_utilisateurs ORDER BY date_suppression DESC")->fetchAll();
}

$countPatients = $pdo->query("SELECT COUNT(*) FROM patients_archive")->fetchColumn();
$countUsers = $pdo->query("SELECT COUNT(*) FROM archives_utilisateurs")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Archives | MedCare Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0d9488; 
            --primary-dark: #0f172a;
            --patient-theme: #4f46e5;
            --staff-theme: #0d9488;
            --bg-soft: #f8fafc;
        }
        
        body { background-color: var(--bg-soft); font-family: 'Plus Jakarta Sans', sans-serif; color: #334155; margin: 0; }

        /* Navigation */
        header { 
            background: #ffffff; padding: 0 40px; height: 75px; 
            display: flex; justify-content: space-between; align-items: center; 
            border-bottom: 1px solid #e2e8f0; position: fixed; width: 100%; top: 0; z-index: 1000; 
        }
        .user-pill { background: var(--primary-dark); padding: 8px 18px; border-radius: 12px; color: #fff; font-weight: 700; font-size: 0.9rem; }
        
        .sidebar { width: 260px; background: var(--primary-dark); height: 100vh; position: fixed; padding: 100px 16px 24px; transition: 0.3s; }
        .sidebar a { display: flex; align-items: center; gap: 12px; color: #94a3b8; text-decoration: none; padding: 12px 16px; border-radius: 10px; margin-bottom: 4px; font-weight: 500; }
        .sidebar a:hover, .sidebar a.active { background: rgba(255,255,255,0.1); color: #fff; }
        .sidebar a.active { background: var(--primary); }

        .main-content { margin-left: 260px; padding: 110px 40px 40px; }

        /* Recherche */
        .search-container {
            background: white; border-radius: 15px; padding: 10px 20px;
            display: flex; align-items: center; border: 1px solid #e2e8f0;
            margin-bottom: 25px; box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }
        .search-container i { color: #94a3b8; margin-right: 15px; }
        .search-container input { border: none; outline: none; width: 100%; font-weight: 500; color: #1e293b; }

        /* ======================== */
        /* TABLEAUX AMÉLIORÉS - FONCÉS ET LISIBLES */
        /* ======================== */
        .table-card { 
            background: #ffffff; 
            border-radius: 15px; 
            overflow: hidden;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            border: 1px solid #e0e7ff;
        }
        
        /* En-tête du tableau - plus foncé */
        .table thead { 
            background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
        }
        
        .table thead th { 
            color: #e2e8f0 !important; 
            font-weight: 700 !important; 
            text-transform: uppercase; 
            font-size: 0.85rem !important; 
            letter-spacing: 0.05em;
            padding: 20px 25px !important; 
            border-bottom: 2px solid #4a5568 !important;
            border-top: none !important;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        
        /* Corps du tableau - lignes alternées */
        .table tbody tr { 
            border-bottom: 1px solid #edf2f7;
            transition: all 0.2s ease;
        }
        
        .table tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }
        
        .table tbody tr:nth-child(odd) {
            background-color: #ffffff;
        }
        
        .table tbody tr:hover {
            background-color: #f0f9ff !important;
            transform: scale(1.002);
            box-shadow: 0 2px 8px rgba(13, 148, 136, 0.1);
        }
        
        .table tbody td { 
            padding: 18px 25px !important; 
            vertical-align: middle !important; 
            border-bottom: 1px solid #e2e8f0 !important;
            color: #2d3748 !important;
            font-weight: 500 !important;
            font-size: 0.95rem !important;
        }
        
        /* Cellules avec texte en gras et foncé */
        .table tbody td .text-bold-dark { 
            color: #1a202c !important; 
            font-weight: 700 !important; 
            font-size: 1rem !important; 
            margin-bottom: 4px;
            display: block;
        }
        
        .table tbody td .text-sub { 
            color: #718096 !important; 
            font-size: 0.85rem !important;
            display: block;
        }
        
        /* Badges améliorés - plus contrastés */
        .badge-date { 
            background: #ebf8ff; 
            color: #2b6cb0 !important; 
            font-weight: 600; 
            padding: 8px 16px; 
            border-radius: 10px; 
            font-size: 0.85rem; 
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid #bee3f8;
        }
        
        .badge-role {
            background: #f0fff4;
            color: #276749 !important;
            padding: 8px 16px;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 600;
            border: 1px solid #c6f6d5;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        /* Bordures et séparateurs */
        .table {
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 0 !important;
        }
        
        .table tbody tr:last-child td {
            border-bottom: none !important;
        }
        
        /* En-tête de section tableau */
        .table-header {
            background: #f7fafc;
            padding: 20px 30px;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .table-header h3 {
            color: #2d3748;
            font-weight: 700;
            margin: 0;
            font-size: 1.3rem;
        }

        /* ======================== */
        /* FIN DES MODIFICATIONS TABLEAUX */
        /* ======================== */
        
        .text-bold-dark { color: #0f172a; font-weight: 700; font-size: 0.95rem; }
        .text-sub { color: #64748b; font-size: 0.85rem; }

        /* Bouton Retour */
        .btn-return {
            display: inline-flex; align-items: center; gap: 8px;
            color: #64748b; text-decoration: none; font-weight: 600;
            margin-bottom: 20px; transition: 0.2s;
        }
        .btn-return:hover { color: var(--primary); }

        /* Cards Menu */
        .menu-card {
            background: white; border-radius: 24px; padding: 30px; border: 1px solid #e2e8f0;
            transition: all 0.3s ease; text-decoration: none !important; color: inherit; height: 100%;
            display: flex; flex-direction: column; align-items: center; text-align: center;
        }
        .menu-card:hover { transform: translateY(-5px); border-color: var(--primary); box-shadow: 0 10px 20px rgba(0,0,0,0.05); }
        .icon-box { width: 70px; height: 70px; border-radius: 20px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; margin-bottom: 20px; }
        
        /* État vide */
        .empty-state {
            padding: 40px 20px;
            text-align: center;
            color: #718096;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.5;
        }
    </style>
</head>
<body>

<header>
    <img src="../images/logo_app2.png" alt="Logo" style="height: 45px;">
    <div class="user-pill">
        <i class="fa-solid fa-shield-halved me-2"></i>ESPACE ADMINISTRATEUR
    </div>
</header>

<div class="wrapper">
    <aside class="sidebar">
        <a href="dashboard_admin.php"><i class="fa-solid fa-chart-pie"></i> Vue Générale</a>
        <a href="utilisateurs.php"><i class="fa-solid fa-user-md"></i> Utilisateurs</a>
        <a href="prestations.php"><i class="fa-solid fa-list-check"></i> Actes & Tarifs</a>
        <a href="chambres.php"><i class="fa-solid fa-bed"></i> Gestion Chambres</a>
        <div style="height: 1px; background: rgba(255,255,255,0.1); margin: 20px 0;"></div>
        <a href="facturation_list.php"><i class="fa-solid fa-file-invoice-dollar"></i> Rapports Financiers</a>
        <a href="archives.php" class="active"><i class="fa-solid fa-box-archive"></i> Archives</a>
        <a href="profil.php" class="<?= basename($_SERVER['PHP_SELF']) == 'profil.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-user-gear"></i> Mon Profil
        </a>
        <a href="../connexio_utilisateur/login.php" style="color: #fda4af; margin-top: 20px;"><i class="fa-solid fa-power-off"></i> Déconnexion</a>
    </aside>

    <main class="main-content">
        
        <?php if ($view === 'menu'): ?>
            <h2 class="fw-bold mb-1">Centre d'Archivage</h2>
            <p class="text-muted mb-5">Consultez les données qui ne sont plus actives dans le système.</p>
            
            <div class="row g-4">
                <div class="col-md-6">
                    <a href="?view=patients" class="menu-card">
                        <div class="icon-box" style="background: #eef2ff; color: #4f46e5;"><i class="fa-solid fa-hospital-user"></i></div>
                        <h4 class="fw-bold">Archives Patients</h4>
                        <p class="text-muted small">Dossiers médicaux et historiques des patients supprimés.</p>
                        <span class="badge bg-primary rounded-pill px-3 py-2"><?= $countPatients ?> Enregistrements</span>
                    </a>
                </div>
                <div class="col-md-6">
                    <a href="?view=utilisateurs" class="menu-card">
                        <div class="icon-box" style="background: #f0fdfa; color: #0d9488;"><i class="fa-solid fa-user-tie"></i></div>
                        <h4 class="fw-bold">Archives Personnel</h4>
                        <p class="text-muted small">Historique des comptes utilisateurs et membres du staff.</p>
                        <span class="badge bg-success rounded-pill px-3 py-2"><?= $countUsers ?> Enregistrements</span>
                    </a>
                </div>
            </div>

        <?php else: ?>
            <a href="archives.php" class="btn-return"><i class="fa-solid fa-arrow-left"></i> Retour aux catégories</a>
            
            <h3 class="fw-bold mb-4">
                <?= $view === 'patients' ? 'Registre des Patients Archivés' : 'Registre du Personnel Archivé' ?>
            </h3>

            <div class="search-container">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="archiveSearch" placeholder="Rechercher par nom, téléphone, matricule ou n'importe quel détail...">
            </div>

            <div class="table-card">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="tableData">
                        <thead>
                            <?php if ($view === 'patients'): ?>
                                <tr>
                                    <th>Patient</th>
                                    <th>Détails Personnels</th>
                                    <th>Coordonnées</th>
                                    <th>Date d'Archivage</th>
                                    <th>ID Origine</th>
                                </tr>
                            <?php else: ?>
                                <tr>
                                    <th>Nom & Spécialité</th>
                                    <th>Identifiants</th>
                                    <th>Rôle</th>
                                    <th>Date d'Archivage</th>
                                    <th>Contact</th>
                                </tr>
                            <?php endif; ?>
                        </thead>
                        <tbody>
                            <?php if (empty($data)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5">
                                        <div class="empty-state">
                                            <i class="fa-solid fa-box-open"></i>
                                            <h4 class="text-muted">Aucune donnée trouvée dans les archives.</h4>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>

                            <?php foreach ($data as $item): ?>
                                <tr>
                                    <?php if ($view === 'patients'): ?>
                                        <td>
                                            <div class="text-bold-dark">
                                                <i class="fa-solid fa-user-circle me-2 text-primary"></i>
                                                <?= strtoupper($item['nom']) ?> <?= $item['prenom'] ?>
                                            </div>
                                            <div class="text-sub">
                                                <i class="fa-solid fa-folder me-1"></i> Patient archivé
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge-role">
                                                <i class="fa-solid fa-venus-mars me-1"></i>
                                                <?= $item['sexe'] ?>
                                            </span>
                                            <div class="text-sub mt-2">
                                                <i class="fa-solid fa-calendar-day me-1"></i>
                                                Né(e) le <?= $item['date_naissance'] ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="text-bold-dark">
                                                <i class="fa-solid fa-phone me-2 text-success"></i>
                                                <?= $item['telephone'] ?>
                                            </div>
                                            <div class="text-sub mt-2">
                                                <i class="fa-solid fa-envelope me-2"></i>
                                                <?= $item['email'] ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge-date">
                                                <i class="fa-regular fa-calendar-xmark me-2"></i>
                                                <?= date('d/m/Y', strtotime($item['date_supprimee'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="text-sub fw-bold">
                                                <i class="fa-solid fa-hashtag me-1"></i>
                                                #<?= $item['id_patient'] ?>
                                            </div>
                                        </td>
                                    <?php else: ?>
                                        <td>
                                            <div class="text-bold-dark">
                                                <i class="fa-solid fa-user-tie me-2 text-primary"></i>
                                                <?= strtoupper($item['nom']) ?> <?= $item['prenom'] ?>
                                            </div>
                                            <div class="text-sub">
                                                <i class="fa-solid fa-stethoscope me-1"></i>
                                                <?= $item['specialite'] ?: 'Service Administratif' ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="text-bold-dark">
                                                <i class="fa-solid fa-id-card me-2 text-info"></i>
                                                <?= $item['matricule'] ?>
                                            </div>
                                            <div class="text-sub mt-2">
                                                <i class="fa-solid fa-address-card me-2"></i>
                                                CIN: <?= $item['cin'] ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge-role">
                                                <i class="fa-solid fa-user-tag me-1"></i>
                                                <?= strtoupper($item['role']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge-date text-danger">
                                                <i class="fa-regular fa-calendar-minus me-2"></i>
                                                <?= date('d/m/Y', strtotime($item['date_suppression'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="text-bold-dark">
                                                <i class="fa-solid fa-phone me-2 text-success"></i>
                                                <?= $item['telephone'] ?>
                                            </div>
                                            <div class="text-sub mt-2">
                                                <i class="fa-solid fa-envelope me-2"></i>
                                                <?= $item['email'] ?>
                                            </div>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

    </main>
</div>

<script>
    document.getElementById('archiveSearch')?.addEventListener('keyup', function() {
        const value = this.value.toLowerCase();
        const rows = document.querySelectorAll('#tableData tbody tr');
        
        rows.forEach(row => {
            const text = row.innerText.toLowerCase();
            row.style.display = text.includes(value) ? '' : 'none';
        });
    });
</script>

</body>
</html>