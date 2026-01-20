<?php
session_start();
include("../config/connexion.php");

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'secretaire') {
    header("Location: login.php");
    exit;
}

$id_admission = isset($_GET['id_adm']) ? intval($_GET['id_adm']) : (isset($_GET['id_admission']) ? intval($_GET['id_admission']) : null);
$user_id = $_SESSION['user_id'];

$stmt_u = $pdo->prepare("SELECT nom, prenom FROM utilisateurs WHERE id_user = ?");
$stmt_u->execute([$user_id]);
$user_info = $stmt_u->fetch(PDO::FETCH_ASSOC);

$facture = null;
if ($id_admission) {
    $sql = "SELECT f.*, p.nom as pat_nom, p.prenom as pat_prenom, p.CIN, a.service, a.date_admission 
            FROM factures f 
            INNER JOIN admissions a ON f.id_admission = a.id_admission
            INNER JOIN patients p ON a.id_patient = p.id_patient
            WHERE f.id_admission = :id_adm";
    
    $stmt_f = $pdo->prepare($sql);
    $stmt_f->execute(['id_adm' => $id_admission]);
    $facture = $stmt_f->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Facturation Détails</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #0d9488; 
            --gold: #b45309;
            --gold-light: #fef3c7;
            --sidebar-bg: #0f172a;
            --bg-body: radial-gradient(circle at top right, #f8fafc 0%, #f1f5f9 100%);
            --header-height: 75px;
            --sidebar-width: 260px;
        }

        * { margin:0; padding:0; box-sizing:border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: var(--bg-body); min-height: 100vh; color: #1e293b; }

        /* HEADER & SIDEBAR */
        header {
            background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(12px);
            padding: 0 40px; height: var(--header-height);
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid rgba(226, 232, 240, 0.8); position: fixed; top: 0; left: 0; right: 0; z-index: 1000;
        }
        .sidebar { 
            width: var(--sidebar-width); background: var(--sidebar-bg); 
            padding: 24px 16px; position: fixed; top: var(--header-height); left: 0; bottom: 0; z-index: 999;
        }
        .sidebar a { 
            display: flex; align-items: center; gap: 12px; color: #94a3b8; 
            text-decoration: none; padding: 12px 16px; border-radius: 12px; transition: 0.3s; margin-bottom: 5px;
        }
        .sidebar a.active { background: var(--primary); color: white; box-shadow: 0 10px 15px -3px rgba(13, 148, 136, 0.3); }
        .sidebar a:hover:not(.active) { background: rgba(255,255,255,0.05); color: white; }

        .content { margin-left: var(--sidebar-width); margin-top: var(--header-height); padding: 40px; }

        .user-pill { 
            background: white; color: var(--sidebar-bg); border: 1px solid #e2e8f0; 
            padding: 8px 16px; border-radius: 12px; font-weight: 600; font-size: 14px;
        }

        /* INVOICE STYLING */
        .invoice-card {
            background: white; border-radius: 24px; overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.08);
            max-width: 900px; margin: 0 auto; border: 1px solid #ffffff;
        }
        .invoice-header {
            background: linear-gradient(to right, #1e293b, #334155);
            padding: 40px; color: white; position: relative;
        }
        .invoice-header::after {
            content: ""; position: absolute; bottom: 0; left: 0; right: 0;
            height: 8px; background: var(--gold);
        }
        .invoice-body { padding: 50px; }
        
        .info-label { color: #64748b; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px; }
        .info-value { font-weight: 700; color: #1e293b; font-size: 16px; }

        .item-table { width: 100%; margin: 30px 0; border-collapse: separate; border-spacing: 0 10px; }
        .item-table th { color: #64748b; font-size: 12px; text-transform: uppercase; padding: 10px 20px; }
        .item-table td { background: #f8fafc; padding: 20px; border: 1px solid #f1f5f9; }
        .item-table td:first-child { border-radius: 12px 0 0 12px; border-right: none; }
        .item-table td:last-child { border-radius: 0 12px 12px 0; border-left: none; text-align: right; font-weight: 700; color: var(--gold); }

        .total-section {
            background: #fffbeb; border: 2px solid #fef3c7; border-radius: 16px;
            padding: 25px; display: flex; justify-content: space-between; align-items: center;
        }
        .total-amount { font-size: 36px; font-weight: 800; color: var(--gold); letter-spacing: -1px; }

        /* BUTTONS */
        .btn-action-group { display: flex; gap: 15px; margin-top: 30px; }
        .btn-print { background: #f1f5f9; color: #475569; border: none; padding: 15px 30px; border-radius: 14px; font-weight: 700; transition: 0.2s; }
        .btn-print:hover { background: #e2e8f0; }
        .btn-confirm { background: var(--sidebar-bg); color: white; border: none; padding: 15px 30px; border-radius: 14px; font-weight: 700; flex-grow: 1; transition: 0.3s; text-decoration: none; text-align: center; }
        .btn-confirm:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(13, 148, 136, 0.2); color: white; }

        @media print {
            .sidebar, header, .btn-action-group { display: none !important; }
            .content { margin: 0 !important; padding: 0 !important; }
            .invoice-card { box-shadow: none !important; border: none !important; }
        }
    </style>
</head>
<body>

<header>
    <div class="d-flex align-items-center">
        <img src="../images/logo_app2.png" alt="Logo" style="height: 42px;">
    </div>
    <div class="user-pill">
        <i class="fa-solid fa-file-invoice-dollar me-2 text-warning"></i>
        <span>Séc. <?= htmlspecialchars($user_info['prenom']) ?></span>
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
        <?php if (!$facture): ?>
            <div class="text-center py-5">
                <div class="mb-4" style="font-size: 80px; color: #e2e8f0;"><i class="fa-solid fa-file-circle-exclamation"></i></div>
                <h2 class="fw-bold">Facture Non Disponible</h2>
                <p class="text-muted">L'admission n° <strong><?= $id_admission ?></strong> n'a pas encore de facture.<br>Vérifiez si la sortie a été validée par le médecin.</p>
                <a href="caisse.php" class="btn btn-dark rounded-pill px-4 mt-3">Retour à la caisse</a>
            </div>
        <?php else: ?>

            <div class="invoice-card">
                <div class="invoice-header d-flex justify-content-between align-items-start">
                    <div>
                        <h1 class="fw-800 mb-1" style="font-weight: 800; font-size: 28px;">FACTURE DE SORTIE</h1>
                        <p class="opacity-75 mb-0">Réf: <?= $facture['numero_facture'] ?></p>
                    </div>
                    <div class="text-end">
                        <div class="info-label text-white-50">Émis le</div>
                        <div class="info-value text-white"><?= date('d M Y', strtotime($facture['date_facture'])) ?></div>
                    </div>
                </div>

                <div class="invoice-body">
                    <div class="row mb-5">
                        <div class="col-md-6">
                            <div class="info-label">Destinataire</div>
                            <div class="info-value"><?= strtoupper($facture['pat_nom']) ?> <?= $facture['pat_prenom'] ?></div>
                            <div class="text-muted small">CIN : <?= $facture['CIN'] ?></div>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <div class="info-label">Service Médical</div>
                            <div class="info-value text-danger"><?= $facture['service'] ?></div>
                            <div class="text-muted small">Admis le : <?= date('d/m/Y', strtotime($facture['date_admission'])) ?></div>
                        </div>
                    </div>

                    <table class="item-table">
                        <thead>
                            <tr>
                                <th>Description des prestations</th>
                                <th class="text-end">Sous-total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark">Frais de séjour</div>
                                    <div class="small text-muted">Hospitalisation (<?= $facture['nb_jours'] ?> jours × <?= number_format($facture['prix_unitaire_jour'], 2) ?> DH)</div>
                                </td>
                                <td><?= number_format($facture['nb_jours'] * $facture['prix_unitaire_jour'], 2) ?> DH</td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark">Soins & Actes Médicaux</div>
                                    <div class="small text-muted">Consultations, soins infirmiers et consommables</div>
                                </td>
                                <td><?= number_format($facture['frais_actes_medicaux'], 2) ?> DH</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="total-section">
                        <div>
                            <div class="info-label" style="color: #b45309;">MONTANT NET À PAYER</div>
                            <div class="small text-muted">Taxes incluses (TTC)</div>
                        </div>
                        <div class="total-amount"><?= number_format($facture['montant_total'], 2) ?> <span style="font-size: 18px;">DH</span></div>
                    </div>

                    <div class="btn-action-group">
                        <button onclick="window.print()" class="btn-print">
                            <i class="fa-solid fa-print me-2"></i>Imprimer le reçu
                        </button>
                        <a href="encaisser.php?id_facture=<?= $facture['id_facture'] ?>" class="btn-confirm">
                            <i class="fa-solid fa-check-circle me-2"></i>VALIDER LE RÈGLEMENT
                        </a>
                    </div>
                    
                    <div class="text-center mt-4">
                        <p class="text-muted" style="font-size: 11px;">Cette facture est éditée numériquement et fait foi de document officiel de paiement.</p>
                    </div>
                </div>
            </div>

        <?php endif; ?>
    </main>
</div>

</body>
</html>