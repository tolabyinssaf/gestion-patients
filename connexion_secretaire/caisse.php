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
$query = "SELECT f.*, p.nom, p.prenom, a.date_admission, a.service 
          FROM factures f
          JOIN admissions a ON f.id_admission = a.id_admission
          JOIN patients p ON a.id_patient = p.id_patient
          WHERE a.statut = 'En cours' 
          ORDER BY f.date_facture DESC";
$factures = $pdo->query($query)->fetchAll();

// Calcul de quelques stats pour le look "Moderne"
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #0d9488; 
            --primary-light: #f0fdfa;
            --sidebar-bg: #0f172a;
            --bg-body: radial-gradient(circle at center, #f0fdfa 0%, #f8fafc 100%);
            --header-height: 75px;
            --sidebar-width: 260px;
            --card-shadow: 0 10px 25px -5px rgba(13, 148, 136, 0.1);
        }

        * { margin:0; padding:0; box-sizing:border-box; font-family: 'Inter', sans-serif; }
        body { background: var(--bg-body); min-height: 100vh; color: #1e293b; }

        /* HEADER & SIDEBAR (Identique aux autres pages) */
        header {
            background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px);
            padding: 0 40px; height: var(--header-height);
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid #e2e8f0; position: fixed; top: 0; left: 0; right: 0; z-index: 1000;
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

        /* STAT CARDS */
        .stat-card {
            background: white; border-radius: 16px; padding: 20px;
            border: 1px solid rgba(13, 148, 136, 0.1);
            box-shadow: var(--card-shadow);
            display: flex; align-items: center; gap: 15px;
        }
        .stat-icon {
            width: 50px; height: 50px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px;
        }
        .icon-green { background: #dcfce7; color: #166534; }
        .icon-orange { background: #fffbeb; color: #92400e; }

        /* TABLE MODERN STYLING */
        .main-card {
            background: white; border-radius: 20px; overflow: hidden;
            border: 1px solid #e2e8f0; box-shadow: 0 4px 20px rgba(0,0,0,0.03);
        }
        .table { margin-bottom: 0; }
        .table thead th {
            background: #f8fafc; color: #64748b; font-size: 11px;
            text-transform: uppercase; letter-spacing: 0.05em;
            padding: 18px 20px; border-bottom: 1px solid #f1f5f9;
        }
        .table tbody td { padding: 18px 20px; border-bottom: 1px solid #f8fafc; vertical-align: middle; }
        .table tbody tr:hover { background-color: var(--primary-light); }

        .amount-tag {
            background: #f1f5f9; padding: 4px 10px; border-radius: 8px;
            font-weight: 600; font-size: 0.9em; color: #475569;
        }
        .total-tag {
            background: var(--primary-light); color: var(--primary);
            padding: 6px 12px; border-radius: 8px; font-weight: 700;
        }

        .btn-pay {
            background: var(--primary); color: white; border: none;
            padding: 8px 16px; border-radius: 10px; font-weight: 600;
            transition: 0.3s; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;
        }
        .btn-pay:hover { background: #0f766e; color: white; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(13, 148, 136, 0.3); }

        .user-pill { background: var(--primary-light); color: var(--primary); border: 1px solid #ccfbf1; padding: 8px 16px; border-radius: 10px; font-weight: 600; }
    </style>
</head>
<body>

<header>
    <div class="d-flex align-items-center">
        <img src="../images/logo_app2.png" alt="Logo" style="height: 40px; margin-right: 15px;">
        <h5 class="mb-0 fw-bold" style="color:var(--sidebar-bg)">MedCare</h5>
    </div>
    <div class="user-pill">
        <i class="fa-solid fa-wallet me-2"></i>
        <span>Session Caisse : <?= htmlspecialchars($user_info['prenom']) ?></span>
    </div>
</header>

<div class="wrapper">
    <aside class="sidebar">
        <h3 style="color:rgba(255,255,255,0.3); font-size:11px; text-transform:uppercase; margin-bottom:20px; padding-left:12px;">Menu Gestion</h3>
        <a href="dashboard_secretaire.php"><i class="fa-solid fa-chart-line"></i> Vue Générale</a>
        <a href="patients_secr.php" ><i class="fa-solid fa-user-group"></i> Patients</a>
         <a href="../admission/admissions_list.php"><i class="fa-solid fa-hospital-user"></i> Admissions</a>
        <a href="suivis.php"><i class="fa-solid fa-calendar-check"></i> Suivis</a>
        <a href="caisse.php" class="active"><i class="fa-solid fa-wallet"></i> Caisse & Factures</a>
         <a href="profil_secretaire.php"><i class="fa-solid fa-user"></i> Profil</a>
        <div style="height: 1px; background: rgba(255,255,255,0.1); margin: 20px 0;"></div>
        <a href="../connexio_utilisateur/deconnexion.php" style="color: #fda4af;"><i class="fa-solid fa-power-off"></i> Déconnexion</a>
    </aside>

    <main class="content">
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <h1 class="h3 fw-bold mb-1">Gestion de la Caisse</h1>
                <p class="text-muted small mb-0">Suivi des facturations et encaissements en temps réel</p>
            </div>
            <div class="text-end">
                <span class="badge bg-white border text-dark p-2 rounded-3 shadow-sm">
                    <i class="fa-solid fa-circle text-success me-1" style="font-size: 8px;"></i> Serveur de Paiement Actif
                </span>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon icon-orange"><i class="fa-solid fa-clock-rotate-left"></i></div>
                    <div>
                        <div class="text-muted small fw-bold">EN ATTENTE</div>
                        <div class="h4 mb-0 fw-bold"><?= $total_attente ?> Dossiers</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon icon-green"><i class="fa-solid fa-hand-holding-dollar"></i></div>
                    <div>
                        <div class="text-muted small fw-bold">MONTANT TOTAL ESTIMÉ</div>
                        <div class="h4 mb-0 fw-bold"><?= number_format($montant_global, 2) ?> DH</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="main-card">
            <div class="p-3 border-bottom bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="fa-solid fa-list me-2 text-primary"></i>Factures Actives</h6>
                <div class="input-group input-group-sm" style="width: 250px;">
                    <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                    <input type="text" class="form-control bg-light border-start-0" placeholder="Rechercher un patient...">
                </div>
            </div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Service & Entrée</th>
                            <th>Frais Séjour</th>
                            <th>Frais Soins (Trigger)</th>
                            <th>Total Actuel</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($factures as $f): ?>
                        <tr>
                            <td>
                                <div class="fw-bold text-dark"><?= strtoupper($f['nom']) ?> <?= $f['prenom'] ?></div>
                                <div class="text-muted small">ID Admission: #ADM-<?= $f['id_admission'] ?></div>
                            </td>
                            <td>
                                <div class="small"><span class="badge bg-light text-dark border-0"><?= $f['service'] ?></span></div>
                                <div class="text-muted small mt-1"><?= date('d/m/Y', strtotime($f['date_admission'])) ?></div>
                            </td>
                            <td><span class="amount-tag"><?= number_format($f['nb_jours'] * $f['prix_unitaire_jour'], 2) ?> DH</span></td>
                            <td><span class="amount-tag text-primary"><?= number_format($f['frais_actes_medicaux'], 2) ?> DH</span></td>
                            <td><span class="total-tag"><?= number_format($f['montant_total'], 2) ?> DH</span></td>
                            <td class="text-end">
                                <a href="details_facture.php?id_adm=<?= $f['id_admission'] ?>" class="btn-pay">
                                    <i class="fa-solid fa-receipt"></i> Détails / Encaisser
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($factures)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <i class="fa-solid fa-check-double fa-3x mb-3 text-muted opacity-20"></i>
                                <p class="text-muted">Aucune admission en cours à facturer.</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

</body>
</html>