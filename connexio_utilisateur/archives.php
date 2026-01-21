<?php
session_start();
include("../config/connexion.php");

// Vérification de l'authentification
if (!isset($_SESSION['user_id']) || 
    (!isset($_SESSION['role']) && !isset($_SESSION['user_role'])) || 
    ($_SESSION['role'] !== 'medecin' && $_SESSION['user_role'] !== 'medecin')) { 
    header("Location: ../connexio_utilisateur/login.php"); 
    exit; 
}

$nom_medecin = $_SESSION['user_nom'] ?? 'Médecin';
$view = $_GET['view'] ?? 'menu';

// Récupération des données
$data = [];
if ($view === 'patients') {
    $data = $pdo->query("SELECT * FROM patients_archive ORDER BY date_supprimee DESC")->fetchAll();
} elseif ($view === 'traitements') {
    $data = $pdo->query("SELECT * FROM historique_suppressions ORDER BY date_suppression DESC")->fetchAll();
}

$countPatients = $pdo->query("SELECT COUNT(*) FROM patients_archive")->fetchColumn();
$countTraitements = $pdo->query("SELECT COUNT(*) FROM historique_suppressions")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Archives Premium | MedCare Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0d9488; 
            --primary-dark: #0f172a;
            --sidebar-color: #1e293b;
            --table-head: #111827;
            --table-row-even: #f1f5f9;
            --accent-patients: #10b981; 
            --accent-traitements: #6366f1; 
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

        /* CARTES MENU (STYLE DEMANDÉ) */
        .menu-card {
            background: var(--sidebar-color); border-radius: 28px; padding: 40px 30px;
            border: 1px solid rgba(255,255,255,0.05); text-decoration: none !important;
            height: 100%; display: flex; flex-direction: column; align-items: center; text-align: center;
            transition: all 0.4s; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2);
        }
        .menu-card:hover { transform: translateY(-10px); background: #0f172a; }
        .icon-box { width: 70px; height: 70px; border-radius: 20px; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin-bottom: 20px; }
        .card-patients .icon-box { background: rgba(16, 185, 129, 0.2); color: var(--accent-patients); }
        .card-traitements .icon-box { background: rgba(99, 102, 241, 0.2); color: var(--accent-traitements); }
        .menu-card h4 { color: white; font-weight: 700; }
        .menu-card p { color: #94a3b8; font-size: 0.85rem; }

        /* TABLEAU MODERNE FONCÉ & COLORÉ */
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

        .custom-table tbody tr { 
            background: #ffffff; 
            transition: all 0.3s;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
        }
        
        .custom-table tbody td { 
            padding: 18px 20px; border-top: 1px solid #f1f5f9; border-bottom: 1px solid #f1f5f9;
            color: #1e293b; font-weight: 500;
        }
        .custom-table tbody td:first-child { border-left: 1px solid #f1f5f9; border-radius: 12px 0 0 12px; }
        .custom-table tbody td:last-child { border-right: 1px solid #f1f5f9; border-radius: 0 12px 12px 0; }

        /* Couleurs pour les lignes Patients */
        .row-patient { border-left: 4px solid var(--accent-patients) !important; }
        /* Couleurs pour les lignes Traitements */
        .row-traitement { border-left: 4px solid var(--accent-traitements) !important; }

        .custom-table tbody tr:hover { 
            background: #f8fafc; transform: scale(1.01);
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05);
        }

        /* RECHERCHE */
        .search-bar {
            background: #f1f5f9; border-radius: 15px; border: 1px solid #e2e8f0;
            padding: 12px 20px; display: flex; align-items: center; margin-bottom: 25px;
        }
        .search-bar input { background: transparent; border: none; outline: none; width: 100%; margin-left: 10px; font-weight: 600; color: var(--primary-dark); }

        /* BADGES */
        .tag { padding: 6px 12px; border-radius: 8px; font-size: 0.75rem; font-weight: 700; }
        .tag-date { background: var(--primary-dark); color: #fff; }
        .info-box-table { background: #f8fafc; padding: 10px; border-radius: 10px; border: 1px solid #e2e8f0; }
    </style>
</head>
<body>

<header>
    <img src="../images/logo_app2.png" alt="Logo" style="height: 40px;">
    <div class="user-profile">
        <i class="fa-solid fa-user-doctor"></i>
        <span style="font-weight: 700; font-size: 0.85rem;">Dr. <?= htmlspecialchars($nom_medecin) ?></span>
    </div>
</header>

   <aside class="sidebar">
        <p style="font-weight: 800;">Unité de Soins</p>
        <a href="dashboard_medecin.php"><i class="fa-solid fa-chart-line"></i> Vue Générale</a>
        <a href="hospitalisation.php"><i class="fa-solid fa-bed-pulse"></i> Patients Admis</a>
        <a href="patients.php"><i class="fa-solid fa-hospital-user"></i> Patients</a>
        <a href="../traitement/list.php"><i class="fa-solid fa-file-prescription"></i> Traitements</a>
        <a href="suivis.php"><i class="fa-solid fa-calendar-check"></i> Consultations</a>
        <p style="font-weight: 800;">Analyse & Gestion</p>
        <a href="../admission/statistique.php"><i class="fa-solid fa-chart-pie"></i> Statistiques</a>
        <a href="archives.php" class="active"><i class="fa-solid fa-box-archive"></i> Archives</a>
        <a href="profil_medcin.php"><i class="fa-solid fa-user-gear"></i> Profil</a>
        <div style="margin-top: 40px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 10px;">
            <a href="deconnexion.php" style="color: #fda4af;"><i class="fa-solid fa-power-off"></i> Déconnexion</a>
        </div>
    </aside>

<main class="main-content">
    <?php if ($view === 'menu'): ?>
        <div class="mb-5">
            <h2 style="font-weight: 800; color: var(--primary-dark);">Espace Archivage</h2>
            <p class="text-muted">Sélectionnez une catégorie pour explorer les données historiques.</p>
        </div>

        <div class="row g-4">
            <div class="col-md-6">
                <a href="?view=patients" class="menu-card card-patients">
                    <div class="icon-box"><i class="fa-solid fa-hospital-user"></i></div>
                    <h4>Base Patients</h4>
                    <p>Historique des dossiers médicaux clôturés.</p>
                    <div class="mt-3 tag tag-date"><?= $countPatients ?> Dossiers</div>
                </a>
            </div>
            <div class="col-md-6">
                <a href="?view=traitements" class="menu-card card-traitements">
                    <div class="icon-box"><i class="fa-solid fa-clock-rotate-left"></i></div>
                    <h4>Traitements & Soins</h4>
                    <p>Journal des modifications et suppressions de soins.</p>
                    <div class="mt-3 tag tag-date"><?= $countTraitements ?> Actions</div>
                </a>
            </div>
        </div>

    <?php else: ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 style="font-weight: 800;"><?= $view === 'patients' ? 'Registre Patients' : 'Registre Traitements' ?></h3>
            <a href="archives.php" class="btn btn-dark btn-sm rounded-pill px-3">Retour</a>
        </div>

        <div class="search-bar">
            <i class="fa-solid fa-search text-muted"></i>
            <input type="text" id="archiveSearch" placeholder="Filtrage intelligent...">
        </div>

        <div class="table-wrapper">
            <div class="table-responsive">
                <table class="custom-table" id="tableData">
                    <thead>
                        <?php if ($view === 'patients'): ?>
                            <tr>
                                <th>Patient</th>
                                <th>Sexe / Naissance</th>
                                <th>Téléphone</th>
                                <th>Archivé le</th>
                                <th>Code ID</th>
                            </tr>
                        <?php else: ?>
                            <tr>
                                <th>ID Soin</th>
                                <th>Motif de Retrait</th>
                                <th>Détails Historiques</th>
                                <th>Date d'action</th>
                            </tr>
                        <?php endif; ?>
                    </thead>
                    <tbody>
                        <?php foreach ($data as $item): ?>
                            <tr class="<?= $view === 'patients' ? 'row-patient' : 'row-traitement' ?>">
                                <?php if ($view === 'patients'): ?>
                                    <td><strong style="color: var(--primary-dark);"><?= strtoupper($item['nom']) ?></strong> <?= $item['prenom'] ?></td>
                                    <td><span class="tag" style="background: #e2e8f0;"><?= $item['sexe'] ?></span> <small class="ms-2"><?= $item['date_naissance'] ?></small></td>
                                    <td><i class="fa-solid fa-phone-alt me-2 text-muted"></i><?= $item['telephone'] ?></td>
                                    <td><span class="tag tag-date"><?= date('d/m/Y', strtotime($item['date_supprimee'])) ?></span></td>
                                    <td><code>#<?= $item['id_patient'] ?></code></td>
                                <?php else: ?>
                                    <td><span class="fw-bold">#TRT-<?= $item['id_traitement'] ?></span></td>
                                    <td><span class="badge bg-danger-subtle text-danger p-2 rounded-3"><?= htmlspecialchars($item['raison_suppression']) ?></span></td>
                                    <td>
                                        <?php $dt = json_decode($item['donnees_traitement'], true); ?>
                                        <div class="info-box-table">
                                            <div style="font-weight: 700; color: var(--accent-traitements);"><?= $dt['medicament'] ?? 'N/A' ?></div>
                                            <div class="small text-muted">Patient: <?= $dt['nom_patient'] ?? 'Inconnu' ?></div>
                                        </div>
                                    </td>
                                    <td><span class="tag tag-date"><?= date('d/m/Y H:i', strtotime($item['date_suppression'])) ?></span></td>
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