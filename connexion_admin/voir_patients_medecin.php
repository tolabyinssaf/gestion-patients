<?php
session_start();
include("../config/connexion.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { 
    header("Location: ../login.php"); exit; 
}

$id_medecin = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Infos du médecin
$stmt_med = $pdo->prepare("SELECT nom, prenom, specialite FROM utilisateurs WHERE id_user = ?");
$stmt_med->execute([$id_medecin]);
$medecin = $stmt_med->fetch(PDO::FETCH_ASSOC);

if (!$medecin) { die("Médecin non trouvé."); }

// Liste des patients
$query = "SELECT * FROM patients WHERE id_medecin = ? ORDER BY nom ASC";
$stmt_p = $pdo->prepare($query);
$stmt_p->execute([$id_medecin]);
$patients = $stmt_p->fetchAll(PDO::FETCH_ASSOC);

// --- CALCUL DES STATISTIQUES ---
$total_patients = count($patients);
$femmes = 0; 
$hommes = 0;

foreach($patients as $p) { 
    $sexe = strtoupper(trim($p['sexe'])); 
    if($sexe == 'F' || $sexe == 'FEMME') { $femmes++; } 
    else if($sexe == 'M' || $sexe == 'H' || $sexe == 'HOMME') { $hommes++; }
}

// Infos Admin pour le Header
$stmt_admin = $pdo->prepare("SELECT nom, prenom FROM utilisateurs WHERE id_user = ?");
$stmt_admin->execute([$_SESSION['user_id']]);
$admin = $stmt_admin->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patients de Dr. <?= $medecin['nom'] ?> | MedCare Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        :root { 
            --primary: #0f766e; 
            --sidebar-bg: #0f172a;
            --bg-body: #f8fafc;
            --border-color: #e2e8f0;
        }
        
        body { background: var(--bg-body); font-family: 'Plus Jakarta Sans', sans-serif; display: flex; min-height: 100vh; margin: 0; }

        /* --- HEADER --- */
        header { 
            background: #ffffff; padding: 0 40px; height: 75px; 
            display: flex; justify-content: space-between; align-items: center; 
            border-bottom: 1px solid var(--border-color); position: fixed; 
            width: 100%; top: 0; z-index: 1050; 
        }
        
        /* --- SIDEBAR --- */
        .sidebar { 
            width: 260px; background: var(--sidebar-bg); height: 100vh; 
            position: fixed; top: 0; padding: 100px 16px 24px; z-index: 1000; 
        }
        .sidebar a { 
            display: flex; align-items: center; gap: 12px; color: #94a3b8; 
            text-decoration: none; padding: 12px 16px; border-radius: 10px; 
            margin-bottom: 5px; transition: 0.3s; 
        }
        .sidebar a:hover, .sidebar a.active { background: var(--primary); color: #ffffff; }

        /* --- LAYOUT --- */
        .main-wrapper { flex: 1; margin-left: 260px; padding-top: 75px; }
        .content-container { padding: 40px; }

        /* --- DOCTOR HERO CARD (AVEC SHADOW ÉLÉGANT) --- */
        .doctor-hero {
            background: white; 
            border-radius: 24px; 
            padding: 35px; 
            margin-bottom: 40px;
            display: flex; 
            justify-content: space-between; 
            align-items: center;
            border: 1px solid rgba(226, 232, 240, 0.8);
            /* Double Shadow élégant */
            box-shadow: 
                0 20px 25px -5px rgba(0, 0, 0, 0.05), 
                0 10px 10px -5px rgba(0, 0, 0, 0.02);
            transition: transform 0.3s ease;
        }
        .doctor-hero:hover {
            transform: translateY(-2px);
        }

        .stats-group { display: flex; gap: 15px; }
        .stat-pill {
            padding: 10px 20px; border-radius: 14px; font-weight: 700; font-size: 0.85rem;
            display: flex; align-items: center; gap: 8px;
        }
        .pill-total { background: #f0fdfa; color: var(--primary); border: 1px solid #ccfbf1; }
        .pill-women { background: #fff1f2; color: #e11d48; border: 1px solid #ffe4e6; }
        .pill-men { background: #eff6ff; color: #2563eb; border: 1px solid #dbeafe; }

        /* --- RECHERCHE --- */
        .search-container { position: relative; max-width: 400px; margin-bottom: 30px; }
        .search-container i { position: absolute; left: 15px; top: 13px; color: #94a3b8; }
        .search-input {
            width: 100%; padding: 11px 11px 11px 45px; border-radius: 12px;
            border: 1px solid var(--border-color); outline: none; transition: 0.3s;
            background: white;
        }
        .search-input:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(15, 118, 110, 0.1); }

        /* --- PATIENT CARDS --- */
        .patient-card {
            background: #fff; border: 1px solid var(--border-color); border-radius: 20px;
            padding: 25px; transition: 0.3s; height: 100%; position: relative;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
        }
        .patient-card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(0,0,0,0.05); border-color: var(--primary); }

        .avatar {
            width: 50px; height: 50px; border-radius: 12px;
            background: linear-gradient(135deg, var(--primary), #2dd4bf); color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem; font-weight: 700; margin-bottom: 15px;
        }

        .blood-type {
            position: absolute; top: 25px; right: 25px;
            background: #fff1f2; color: #e11d48; padding: 4px 10px;
            border-radius: 8px; font-size: 0.7rem; font-weight: 800;
        }

        .patient-name { font-size: 1.1rem; font-weight: 700; color: #1e293b; margin-bottom: 2px; }
        .patient-cin { font-size: 0.85rem; color: var(--primary); font-weight: 600; margin-bottom: 15px; display: block; }

        .info-row { display: flex; align-items: center; gap: 10px; color: #64748b; font-size: 0.85rem; margin-bottom: 8px; }
        .info-row i { color: #94a3b8; width: 15px; }

        .btn-action {
            display: block; width: 100%; text-align: center; background: #f8fafc;
            color: #1e293b; border: 1px solid var(--border-color); padding: 10px;
            border-radius: 10px; text-decoration: none; font-weight: 600; 
            margin-top: 15px; transition: 0.2s; font-size: 0.9rem;
        }
        .btn-action:hover { background: var(--primary); color: #fff; border-color: var(--primary); }
    </style>
</head>
<body>

<header>
    <img src="../images/logo_app2.png" alt="Logo" style="height: 40px;">
    <div style="background: #f0fdfa; color: var(--primary); padding: 8px 16px; border-radius: 12px; font-weight: 700;">
        <i class="fa-solid fa-user-shield me-2"></i>ADMIN : <?= strtoupper($admin['nom']) ?>
    </div>
</header>

 <aside class="sidebar">
        <a href="dashboard_admin.php" ><i class="fa-solid fa-chart-pie"></i> Vue Générale</a>
        <a href="utilisateurs.php"><i class="fa-solid fa-user-md"></i> Utilisateurs</a>
        <a href="prestations.php"  ><i class="fa-solid fa-list-check"></i> Actes & Tarifs</a>
        <a href="chambres.php"><i class="fa-solid fa-bed"></i> Gestion Chambres</a>
        <div style="height: 1px; background: rgba(255,255,255,0.1); margin: 20px 0;"></div>
        <a href="facturation_list.php"><i class="fa-solid fa-file-invoice-dollar"></i> Rapports Financiers</a>
        <a href="profil.php" class="<?= basename($_SERVER['PHP_SELF']) == 'profil.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-user-gear"></i> Mon Profil
        </a>
        <a href="../logout.php" style="color: #fda4af; margin-top: 20px;"><i class="fa-solid fa-power-off"></i> Déconnexion</a>
    </aside>

<div class="main-wrapper">
    <main class="content-container">
        
        <div class="doctor-hero">
            <div>
                <h2 style="font-weight: 800; color: #0f172a; margin: 0; font-size: 1.75rem;">Dr. <?= strtoupper($medecin['nom']) ?> <?= $medecin['prenom'] ?></h2>
                <p class="text-muted mb-0 mt-1"><i class="fa-solid fa-stethoscope me-2 text-primary"></i>Spécialité : <strong><?= $medecin['specialite'] ?></strong></p>
            </div>
            
            <div class="stats-group">
                <div class="stat-pill pill-total">
                    <i class="fa-solid fa-users"></i> <?= $total_patients ?> Patients
                </div>
                <div class="stat-pill pill-women">
                    <i class="fa-solid fa-venus"></i> <?= $femmes ?> Femmes
                </div>
                <div class="stat-pill pill-men">
                    <i class="fa-solid fa-mars"></i> <?= $hommes ?> Hommes
                </div>
            </div>
        </div>

        <div class="search-container">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="searchInput" class="search-input" placeholder="Rechercher par nom ou CIN...">
        </div>

        <div class="row g-4" id="patientGrid">
            <?php if($patients): foreach($patients as $p): ?>
            <div class="col-md-6 col-lg-4 col-xl-3 patient-item">
                <div class="patient-card">
                    <span class="blood-type"><?= $p['groupe_sanguin'] ?: 'N/A' ?></span>
                    <div class="avatar"><?= strtoupper(substr($p['nom'], 0, 1)) ?></div>
                    
                    <div class="patient-name"><?= htmlspecialchars($p['nom'] . ' ' . $p['prenom']) ?></div>
                    <span class="patient-cin">CIN: <?= $p['CIN'] ?></span>

                    <div class="info-row"><i class="fa-solid fa-phone"></i> <?= $p['telephone'] ?></div>
                    <div class="info-row"><i class="fa-solid fa-location-dot"></i> <?= $p['adresse'] ?></div>
                    <div class="info-row">
                        <i class="fa-solid <?= (strtoupper($p['sexe']) == 'F' || strtoupper($p['sexe']) == 'FEMME') ? 'fa-venus text-danger' : 'fa-mars text-primary' ?>"></i> 
                        Sexe: <?= (strtoupper($p['sexe']) == 'F' || strtoupper($p['sexe']) == 'FEMME') ? 'Femme' : 'Homme' ?>
                    </div>

                    <a href="dossier_patient.php?id=<?= $p['id_patient'] ?>" class="btn-action">
                        Ouvrir le dossier <i class="fa-solid fa-arrow-right ms-1" style="font-size: 0.7rem;"></i>
                    </a>
                </div>
            </div>
            <?php endforeach; else: ?>
            <div class="col-12 text-center py-5">
                <div class="p-5 bg-white rounded-4 border shadow-sm">
                    <i class="fa-solid fa-user-slash fa-3x text-light mb-3"></i>
                    <p class="text-muted">Aucun patient n'est encore rattaché à ce médecin.</p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<script>
    document.getElementById('searchInput').addEventListener('input', function() {
        let filter = this.value.toLowerCase();
        document.querySelectorAll('.patient-item').forEach(item => {
            let text = item.innerText.toLowerCase();
            item.style.display = text.includes(filter) ? 'block' : 'none';
        });
    });
</script>

</body>
</html>