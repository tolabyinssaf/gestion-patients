<?php
session_start();
include("../config/connexion.php");

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'secretaire') {
    header("Location: ../login.php"); exit;
}

// Récupération des infos utilisateur
$user_id = $_SESSION['user_id'];
$stmt_u = $pdo->prepare("SELECT nom, prenom FROM utilisateurs WHERE id_user = ?");
$stmt_u->execute([$user_id]);
$user_info = $stmt_u->fetch();

// Récupérer toutes les admissions en cours avec les montants
$query = "SELECT f.*, p.nom, p.prenom, a.service 
          FROM factures f
          JOIN admissions a ON f.id_admission = a.id_admission
          JOIN patients p ON a.id_patient = p.id_patient
          WHERE f.statut_paiement = 'Payé' 
          ORDER BY f.date_facture DESC";
$factures = $pdo->query($query)->fetchAll();

// Calcul de quelques stats
$total_attente = count($factures);
$montant_global = array_sum(array_column($factures, 'montant_total'));
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>MedCare | Caisse & Facturation</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #0d9488; 
            --sidebar-bg: #0f172a;
            --bg-body: #f1f5f9;
            --header-height: 75px;
            --sidebar-width: 260px;
            --card-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }

        * { margin:0; padding:0; box-sizing:border-box; font-family: 'Inter', sans-serif; }
        body { background: var(--bg-body); min-height: 100vh; color: #1e293b; }

        header {
            background: #ffffff; padding: 0 40px; height: var(--header-height);
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 2px solid #e2e8f0; position: fixed; top: 0; left: 0; right: 0; z-index: 1000;
        }
        .sidebar { 
            width: var(--sidebar-width); background: var(--sidebar-bg); 
            padding: 24px 16px; position: fixed; top: var(--header-height); left: 0; bottom: 0; z-index: 999;
        }
        .sidebar a { 
            display: flex; align-items: center; gap: 12px; color: #94a3b8; 
            text-decoration: none; padding: 12px 16px; border-radius: 10px; transition: 0.2s; margin-bottom: 5px;
        }
        .sidebar a.active { background: var(--primary); color: white; box-shadow: 0 4px 15px rgba(13, 148, 136, 0.4); }
        .sidebar a:hover:not(.active) { background: rgba(255,255,255,0.05); color: white; }

        .content { margin-left: var(--sidebar-width); margin-top: var(--header-height); padding: 40px; }

        .stat-card {
            background: white; border-radius: 16px; padding: 25px;
            box-shadow: var(--card-shadow); display: flex; align-items: center; gap: 20px;
        }
        .stat-icon { width: 60px; height: 60px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 24px; color: white; }
        .icon-green { background: #059669; }
        .icon-orange { background: #d97706; }

        .main-card { background: white; border-radius: 20px; overflow: hidden; box-shadow: var(--card-shadow); }
        
        .table thead th { background: #1e293b; color: #ffffff; font-size: 12px; padding: 20px; text-transform: uppercase; }
        .table tbody td { padding: 20px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }

        .total-tag { background: #0d9488; color: white; padding: 8px 15px; border-radius: 8px; font-weight: 800; }
        .btn-pay { background: #1e293b; color: white; padding: 10px 20px; border-radius: 12px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: 0.3s; }
        .btn-pay:hover { background: var(--primary); color: white; }
        .user-pill { background: #f0fdfa; color: var(--primary); border: 1px solid #ccfbf1; padding: 8px 16px; border-radius: 10px; font-weight: 700; }
    </style>
</head>
<body>

<header>
    <div class="d-flex align-items-center">
        <img src="../images/logo_app2.png" alt="Logo" style="height: 45px; margin-right: 15px;">
      
    </div>
    <div class="user-pill">
        
        <span>Secr: <?= htmlspecialchars($user_info['prenom']) ?></span>
    </div>
</header>

<div class="wrapper">
    <aside class="sidebar">
        <h3 style="color:rgba(255,255,255,0.3); font-size:11px; text-transform:uppercase; margin-bottom:20px; padding-left:12px;">Menu Gestion</h3>
        <a href="dashboard_secretaire.php"><i class="fa-solid fa-chart-line"></i> Vue Générale</a>
        <a href="patients_secr.php"><i class="fa-solid fa-user-group"></i> Patients</a>
        <a href="../admission/admissions_list.php"><i class="fa-solid fa-hospital-user"></i> Admissions</a>
        <a href="suivis.php"><i class="fa-solid fa-calendar-check"></i> Suivis</a>
        <a href="caisse.php" class="active"><i class="fa-solid fa-wallet"></i> Caisse & Factures</a>
        <a href="profil_secretaire.php"><i class="fa-solid fa-user"></i> Profil</a>
        <div style="height: 1px; background: rgba(255,255,255,0.1); margin: 20px 0;"></div>
        <a href="../connexio_utilisateur/deconnexion.php" style="color: #fda4af;"><i class="fa-solid fa-power-off"></i> Déconnexion</a>
    </aside>

    <main class="content">
        <div class="d-flex justify-content-between align-items-end mb-5">
            <div>
                <h1 class="h3 fw-800 mb-1" style="font-weight:800;">Gestion de la Caisse</h1>
                <p class="text-muted mb-0">Recherchez un patient pour encaisser sa facture</p>
            </div>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon icon-orange"><i class="fa-solid fa-clock"></i></div>
                    <div>
                        <div class="text-muted small fw-bold">EN ATTENTE</div>
                        <div class="h3 mb-0 fw-bold"><?= $total_attente ?> Dossiers</div>
                    </div>
                </div>
            </div>
            <div class="col-md-5">
                <div class="stat-card">
                    <div class="stat-icon icon-green"><i class="fa-solid fa-money-bill-wave"></i></div>
                    <div>
                        <div class="text-muted small fw-bold">TOTAL ESTIMÉ</div>
                        <div class="h3 mb-0 fw-bold"><?= number_format($montant_global, 2) ?> DH</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="main-card">
            <div class="p-4 border-bottom bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-800">Factures Actives</h5>
                <div class="input-group" style="width: 350px;">
                    <span class="input-group-text bg-light border-0"><i class="fa-solid fa-magnifying-glass text-primary"></i></span>
                    <input type="text" id="searchInput" class="form-control bg-light border-0" placeholder="Chercher par nom ou prénom...">
                </div>
            </div>
            <div class="table-responsive">
                <table class="table align-middle" id="facturesTable">
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Service</th>
                            <th>Frais Séjour</th>
                            <th>Soins</th>
                            <th>Total</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($factures as $f): ?>
                        <tr class="patient-row">
                            <td>
                                <div class="fw-800 text-dark patient-name" style="font-size: 16px;">
                                    <?= strtoupper($f['nom']) ?> <?= $f['prenom'] ?>
                                </div>
                                <div class="text-muted small">#ADM-<?= $f['id_admission'] ?></div>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?= $f['service'] ?></span></td>
                            <td><?= number_format($f['nb_jours'] * $f['prix_unitaire_jour'], 2) ?> DH</td>
                            <td><?= number_format($f['frais_actes_medicaux'], 2) ?> DH</td>
                            <td><span class="total-tag"><?= number_format($f['montant_total'], 2) ?> DH</span></td>
                            <td class="text-end">
                                <a href="details_facture.php?id_adm=<?= $f['id_admission'] ?>" class="btn-pay">
                                    <i class="fa-solid fa-cash-register"></i> Encaisser
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<script>
document.getElementById('searchInput').addEventListener('keyup', function() {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll('.patient-row');

    rows.forEach(row => {
        let name = row.querySelector('.patient-name').textContent.toLowerCase();
        if (name.includes(filter)) {
            row.style.display = "";
        } else {
            row.style.display = "none";
        }
    });
});
</script>

</body>
</html>