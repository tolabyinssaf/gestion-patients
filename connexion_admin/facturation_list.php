<?php
session_start();
include "../config/connexion.php";

// Sécurité Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { 
    header("Location: ../login.php"); 
    exit; 
}

// Requête SQL optimisée avec vos colonnes exactes
$query = "SELECT f.*, p.nom, p.prenom, p.CIN, p.telephone, p.email
          FROM factures f 
          Left JOIN patients p ON f.id_patient = p.id_patient 
          ORDER BY f.date_facture DESC";
$stmt = $pdo->prepare($query);
$stmt->execute();
$factures = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Statistiques pour le bandeau
$total_recettes = 0;
$total_impayes = 0;
foreach($factures as $f) {
    if($f['statut_paiement'] == 'Payé') $total_recettes += $f['montant_total'];
    else $total_impayes += $f['montant_total'];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Facturation Hospitalière | MedCare</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { 
            --primary: #0f766e; 
            --dark-blue: #1e293b; 
            --amber: #f59e0b; 
            --bg-body: #f8fafc; 
            --sidebar-bg: #0f172a;
            --border: #e2e8f0; 
        }
        
        body { background: var(--bg-body); font-family: 'Plus Jakarta Sans', sans-serif; color: #1e293b; }

        header { 
            background: white; padding: 0 40px; height: 75px; 
            display: flex; justify-content: space-between; align-items: center; 
            border-bottom: 1px solid var(--border); position: fixed; width: 100%; top: 0; z-index: 1000;
        }
        .wrapper { display: flex; padding-top: 75px; }
        .sidebar { width: 260px; background: var(--sidebar-bg); height: calc(100vh - 75px); position: fixed; padding: 24px 16px; }
        .sidebar a { display: flex; align-items: center; gap: 12px; color: #94a3b8; text-decoration: none; padding: 12px 16px; border-radius: 10px; margin-bottom: 5px; transition: 0.3s; }
        .sidebar a.active { background: var(--primary); color: white; }
        .main-content { margin-left: 260px; padding: 30px; width: calc(100% - 260px); }

        .header-unit {
            background: var(--dark-blue);
            color: white; border-radius: 16px; 
            padding: 20px 25px; margin-bottom: 25px;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
        }
        
        .medical-card { 
            background: white; border-radius: 16px; border: 1px solid var(--border); 
            padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }

        .user-pill { background: #f0fdfa; padding: 8px 18px; border-radius: 12px; color: var(--primary); font-weight: 600; display: flex; align-items: center; gap: 10px; border: 1px solid var(--border); }
        
        /* Table Styles */
        .table thead th { background: #f8fafc; color: #64748b; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em; padding: 15px; border: none; }
        .table tbody td { padding: 15px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; font-size: 0.85rem; }
        
        .badge-status { padding: 6px 12px; border-radius: 8px; font-weight: 700; font-size: 0.7rem; }
        .bg-paid { background: #dcfce7; color: #166534; }
        .bg-pending { background: #fef3c7; color: #92400e; }
        
        .couverture-tag { font-size: 0.7rem; background: #f1f5f9; padding: 2px 8px; border-radius: 4px; color: #475569; }
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
        <a href="dashboard_admin.php" ><i class="fa-solid fa-chart-pie"></i> Vue Générale</a>
        <a href="utilisateurs.php"><i class="fa-solid fa-user-md"></i> Utilisateurs</a>
        <a href="prestations.php"><i class="fa-solid fa-list-check"></i> Actes & Tarifs</a>
        <a href="../chambres/gestion_chambres.php"><i class="fa-solid fa-bed"></i> Gestion Chambres</a>
        <div style="height: 1px; background: rgba(255,255,255,0.1); margin: 20px 0;"></div>
        <a href="facturation_list.php" class="active"><i class="fa-solid fa-file-invoice-dollar"></i> Rapports Financiers</a>
         <a href="archives.php">
            <i class="fa-solid fa-box-archive"></i> Archives
        </a>
        <a href="profil.php" class="<?= basename($_SERVER['PHP_SELF']) == 'profil.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-user-gear"></i> Mon Profil
        </a>
        <a href="../connexio_utilisateur/login.php" style="color: #fda4af; margin-top: 20px;"><i class="fa-solid fa-power-off"></i> Déconnexion</a>
    </aside>

    <main class="main-content">
        <div class="header-unit">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h3 class="fw-800 mb-1">Gestion Financière</h3>
                    <p class="mb-0 text-white-50">Suivi des encaissements et factures hospitalières</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="d-inline-flex gap-3 text-start">
                        <div class="bg-white bg-opacity-10 p-2 px-3 rounded-3">
                            <small class="d-block text-white-50" style="font-size: 0.6rem;">CA ENCAISSÉ</small>
                            <span class="fw-bold"><?= number_format($total_recettes, 2) ?> DH</span>
                        </div>
                        <div class="bg-white bg-opacity-10 p-2 px-3 rounded-3">
                            <small class="d-block text-white-50" style="font-size: 0.6rem;">RESTE À RECOUVRER</small>
                            <span class="fw-bold text-warning"><?= number_format($total_impayes, 2) ?> DH</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="medical-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-800 m-0">Journal des Factures</h5>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-secondary rounded-pill px-3"><i class="fa-solid fa-filter me-2"></i>Filtrer</button>
                    
                </div>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>N° Facture / Date</th>
                            <th>Patient (CIN)</th>
                            <th>Détails Séjour</th>
                            <th>Frais Médicaux</th>
                            <th>Total</th>
                            <th>Couverture</th>
                            <th>Statut</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($factures as $f): ?>
                        <tr>
                            <td>
                                <div class="fw-800 text-primary"><?= $f['numero_facture'] ?></div>
                                <div class="text-muted small"><?= date('d/m/Y', strtotime($f['date_facture'])) ?></div>
                            </td>
                            <td>
                                <div class="fw-bold"><?= strtoupper($f['nom']) ?> <?= $f['prenom'] ?></div>
                                <div class="text-muted small">CIN: <?= $f['CIN'] ?></div>
                            </td>
                            <td>
                                <div class="small">
                                    <strong><?= $f['nb_jours'] ?></strong> Jours x <?= $f['prix_unitaire_jour'] ?> DH
                                </div>
                                <div class="text-muted small">Admission #<?= $f['id_admission'] ?></div>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= number_format($f['frais_actes_medicaux'], 2) ?> DH</div>
                            </td>
                            <td>
                                <div class="fw-800 text-dark"><?= number_format($f['montant_total'], 2) ?> DH</div>
                                <small class="text-muted" style="font-size: 0.65rem;"><?= $f['mode_paiement'] ?></small>
                            </td>
                            <td>
                                <span class="couverture-tag"><?= $f['type_couverture'] ?></span>
                            </td>
                            <td>
                                <span class="badge-status <?= $f['statut_paiement'] == 'Payé' ? 'bg-paid' : 'bg-pending' ?>">
                                    <?= $f['statut_paiement'] == 'Payé' ? 'ENCAISSÉ' : 'EN ATTENTE' ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="dropdown">
                                    <button class="btn btn-light btn-sm rounded-circle" data-bs-toggle="dropdown">
                                        <i class="fa-solid fa-ellipsis-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                                        <li><a class="dropdown-item small" href="print_invoice.php?id=<?= $f['id_facture'] ?>"><i class="fa-solid fa-print me-2 text-primary"></i>Imprimer</a></li>
                                        <li><a class="dropdown-item small" href="edit_facture.php?id=<?= $f['id_facture'] ?>"><i class="fa-solid fa-pen me-2 text-warning"></i>Modifier</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item small text-danger" href="#"><i class="fa-solid fa-trash me-2"></i>Annuler</a></li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>