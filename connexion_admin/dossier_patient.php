<?php
session_start();
include("../config/connexion.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { 
    header("Location: ../login.php"); exit; 
}

$id_patient = isset($_GET['id']) ? intval($_GET['id']) : 0;

// 1. Informations personnelles du patient + Médecin
$query = "SELECT p.*, u.nom as med_nom, u.prenom as med_prenom 
          FROM patients p 
          LEFT JOIN utilisateurs u ON p.id_medecin = u.id_user 
          WHERE p.id_patient = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$id_patient]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$patient) { die("Patient non trouvé."); }

// Requêtes pour les listes
$list_ante = $pdo->prepare("SELECT * FROM antecedents WHERE id_patient = ? ORDER BY date_evenement DESC");
$list_ante->execute([$id_patient]);
$list_ante = $list_ante->fetchAll(PDO::FETCH_ASSOC);

$list_suivi = $pdo->prepare("SELECT * FROM suivis WHERE id_patient = ? ORDER BY date_suivi DESC");
$list_suivi->execute([$id_patient]);
$list_suivi = $list_suivi->fetchAll(PDO::FETCH_ASSOC);

$list_trait = $pdo->prepare("SELECT * FROM traitements WHERE id_patient = ? ORDER BY date_traitement DESC");
$list_trait->execute([$id_patient]);
$list_trait = $list_trait->fetchAll(PDO::FETCH_ASSOC);

$list_adm = $pdo->prepare("SELECT * FROM admissions WHERE id_patient = ? ORDER BY date_admission DESC");
$list_adm->execute([$id_patient]);
$list_adm = $list_adm->fetchAll(PDO::FETCH_ASSOC);

$stmt_admin = $pdo->prepare("SELECT nom, prenom FROM utilisateurs WHERE id_user = ?");
$stmt_admin->execute([$_SESSION['user_id']]);
$admin = $stmt_admin->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dossier | <?= htmlspecialchars($patient['nom']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        :root { 
            --primary: #0f766e;        /* Vert Émeraude */
            --primary-soft: #f0fdf4;   /* Fond Vert très clair */
            --secondary: #64748b;      /* Gris */
            --sidebar-bg: #0f172a;     /* Gris sombre */
            --bg-body: #f8fafc;
            --border: #e2e8f0;
        }
        
        body { background: var(--bg-body); font-family: 'Plus Jakarta Sans', sans-serif; display: flex; min-height: 100vh; margin: 0; }

        /* INTERFACE */
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

        .glass-card {
            background: white; border-radius: 20px; padding: 25px; border: 1px solid var(--border);
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); margin-bottom: 25px;
        }

        /* CHANGEMENT COULEUR TEXTE BLEU -> VERT */
        .text-primary, .fw-bold.text-primary { color: var(--primary) !important; }
        .avatar-box {
            width: 70px; height: 70px; background: var(--primary);
            border-radius: 15px; color: white; display: flex; align-items: center; justify-content: center;
            font-size: 2rem; font-weight: 800;
        }

        .info-pill {
            background: var(--primary-soft); padding: 6px 14px; border-radius: 10px; 
            font-size: 0.8rem; font-weight: 700; color: var(--primary); border: 1px solid #dcfce7;
        }

        .nav-pills .nav-link { color: var(--secondary); font-weight: 700; border-radius: 10px; }
        .nav-pills .nav-link.active { background: var(--primary) !important; color: white !important; }

        .table-custom th { font-size: 0.75rem; text-transform: uppercase; color: var(--secondary); padding: 15px; }
        .table-custom td { padding: 15px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }

        /* CONFIGURATION IMPRESSION PDF */
        @media print {
            header, .sidebar, .nav-pills, .btn-print, hr { display: none !important; }
            .main-wrapper { margin-left: 0 !important; padding-top: 0 !important; }
            .content-container { padding: 0 !important; }
            .glass-card { border: none !important; box-shadow: none !important; padding: 10px !important; }
            body { background: white !important; }
            .tab-pane { display: block !important; opacity: 1 !important; margin-bottom: 30px; }
            .tab-content > .tab-pane { display: block !important; }
        }
    </style>
</head>
<body>

<header>
    <img src="../images/logo_app2.png" alt="Logo" style="height: 35px;">
    <div style="background: var(--primary-soft); color: var(--primary); padding: 8px 16px; border-radius: 10px; font-weight: 700;">
        <i class="fa-solid fa-circle-user me-2"></i>ADMIN : <?= strtoupper($admin['nom']) ?>
    </div>
</header>

<aside class="sidebar">
        <a href="dashboard_admin.php" ><i class="fa-solid fa-chart-pie"></i> Vue Générale</a>
        <a href="utilisateurs.php"><i class="fa-solid fa-user-md"></i> Utilisateurs</a>
        <a href="prestations.php"><i class="fa-solid fa-list-check"></i> Actes & Tarifs</a>
        <a href="../chambres/gestion_chambres.php"><i class="fa-solid fa-bed"></i> Gestion Chambres</a>
        <div style="height: 1px; background: rgba(255,255,255,0.1); margin: 20px 0;"></div>
        <a href="facturation_list.php"><i class="fa-solid fa-file-invoice-dollar"></i> Rapports Financiers</a>
         <a href="archives.php">
            <i class="fa-solid fa-box-archive"></i> Archives
        </a>
        <a href="profil.php" class="<?= basename($_SERVER['PHP_SELF']) == 'profil.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-user-gear"></i> Mon Profil
        </a>
        <a href="../connexio_utilisateur/login.php" style="color: #fda4af; margin-top: 20px;"><i class="fa-solid fa-power-off"></i> Déconnexion</a>
    </aside>

<div class="main-wrapper">
    <main class="content-container">
        
        <div class="glass-card d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-4">
                <div class="avatar-box"><?= strtoupper(substr($patient['nom'], 0, 1)) ?></div>
                <div>
                    <h2 class="fw-800 mb-1" style="color: #0f172a;"><?= htmlspecialchars($patient['nom'].' '.$patient['prenom']) ?></h2>
                    <div class="d-flex gap-2">
                        <span class="info-pill"><i class="fa-solid fa-fingerprint me-2"></i><?= $patient['CIN'] ?></span>
                        <span class="info-pill text-danger" style="background:#fff1f2; border-color:#fecdd3;"><i class="fa-solid fa-droplet me-2"></i><?= $patient['groupe_sanguin'] ?></span>
                        <span class="info-pill"><i class="fa-solid fa-user-doctor me-2"></i>Dr. <?= $patient['med_nom'] ?></span>
                    </div>
                </div>
            </div>
            <button class="btn btn-dark px-4 py-2 rounded-4 fw-bold btn-print" onclick="window.print()">
                <i class="fa-solid fa-file-pdf me-2"></i>Exporter PDF
            </button>
        </div>

        <ul class="nav nav-pills mb-4 gap-2" id="pills-tab" role="tablist">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tab-infos">Vue Générale</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-traitements">Traitements</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-admissions">Admissions</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-suivi">Suivis</button></li>
        </ul>

        <div class="tab-content">
            
            <div class="tab-pane fade show active" id="tab-infos">
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="glass-card">
                            <h6 class="fw-800 text-uppercase small text-muted mb-4">Coordonnées</h6>
                            <p class="mb-2"><strong>Tél:</strong> <?= $patient['telephone'] ?></p>
                            <p class="mb-2"><strong>Email:</strong> <?= $patient['email'] ?></p>
                            <p class="mb-4"><strong>Adresse:</strong> <?= $patient['adresse'] ?></p>
                            <div class="p-3 rounded-3" style="background:#fff1f2; border:1px solid #fecdd3;">
                                <label class="text-danger small fw-800 d-block">ALLERGIES</label>
                                <span class="text-danger fw-bold small"><?= $patient['allergies'] ?: 'Aucune' ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="glass-card">
                            <h6 class="fw-800 text-uppercase small text-muted mb-4">Antécédents</h6>
                            <table class="table table-custom">
                                <thead><tr><th>Catégorie</th><th>Pathologie</th><th>Date</th></tr></thead>
                                <tbody>
                                    <?php foreach($list_ante as $a): ?>
                                    <tr>
                                        <td><span class="badge bg-light text-dark"><?= $a['categorie'] ?></span></td>
                                        <td class="fw-bold text-primary"><?= $a['nom_pathologie'] ?></td>
                                        <td><?= date('d/m/Y', strtotime($a['date_evenement'])) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-traitements">
                <div class="glass-card">
                    <h5 class="fw-800 mb-4 text-primary">Traitements en cours</h5>
                    <table class="table table-custom">
                        <thead><tr><th>Date</th><th>Médicaments</th><th>Description</th><th>Suivi</th></tr></thead>
                        <tbody>
                            <?php foreach($list_trait as $t): ?>
                            <tr>
                                <td class="fw-bold"><?= date('d/m/Y', strtotime($t['date_traitement'])) ?></td>
                                <td><span class="info-pill"><?= $t['medicament'] ?></span></td>
                                <td><?= $t['description'] ?></td>
                                <td class="text-muted small"><?= $t['suivi'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-admissions">
                <div class="glass-card">
                    <h5 class="fw-800 mb-4 text-primary">Admissions Hospitalières</h5>
                    <table class="table table-custom">
                        <thead><tr><th>Dates</th><th>Service</th><th>Chambre</th><th>Motif</th><th>Statut</th></tr></thead>
                        <tbody>
                            <?php foreach($list_adm as $adm): ?>
                            <tr>
                                <td><div class="small fw-bold">Du <?= date('d/m/Y', strtotime($adm['date_admission'])) ?></div></td>
                                <td class="fw-bold text-uppercase"><?= $adm['service'] ?></td>
                                <td><span class="badge bg-dark">N° <?= $adm['chambre'] ?></span></td>
                                <td><?= $adm['motif'] ?></td>
                                <td><span class="badge bg-success px-2 py-1 small"><?= $adm['status'] ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-suivi">
                <div class="glass-card">
                    <h5 class="fw-800 mb-4 text-primary">Journal de Suivi</h5>
                    <table class="table table-custom">
                        <thead><tr><th>Date</th><th>Observation</th><th>Statut</th></tr></thead>
                        <tbody>
                            <?php foreach($list_suivi as $s): ?>
                            <tr>
                                <td class="fw-bold"><?= date('d/m/Y', strtotime($s['date_suivi'])) ?></td>
                                <td><?= $s['commentaire'] ?></td>
                                <td><span class="badge bg-light text-dark"><?= $s['status'] ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>