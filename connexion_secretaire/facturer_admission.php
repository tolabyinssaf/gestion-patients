<?php
session_start();
include("../config/connexion.php");

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'secretaire') {
    header("Location: login.php");
    exit;
}

// Récupération de l'ID Admission
$id_admission = isset($_GET['id_adm']) ? intval($_GET['id_adm']) : (isset($_GET['id_admission']) ? intval($_GET['id_admission']) : null);
$user_id = $_SESSION['user_id'];

if (!$id_admission) { header("Location: admissions.php"); exit; }

// 1. Infos utilisateur (Header)
$stmt_u = $pdo->prepare("SELECT nom, prenom FROM utilisateurs WHERE id_user = ?");
$stmt_u->execute([$user_id]);
$user_info = $stmt_u->fetch(PDO::FETCH_ASSOC);

// 2. Récupérer les données de l'admission pour le calcul
$stmt = $pdo->prepare("SELECT a.*, p.nom as pat_nom, p.prenom as pat_prenom, p.cin, u.nom as nom_medecin 
                       FROM admissions a 
                       JOIN patients p ON a.id_patient = p.id_patient 
                       JOIN utilisateurs u ON a.id_medecin = u.id_user 
                       WHERE a.id_admission = ?");
$stmt->execute([$id_admission]);
$adm = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$adm) { die("Admission introuvable."); }

// 3. LOGIQUE DE CALCUL
$tarif_nuit = 600; 
$date_entree = new DateTime($adm['date_admission']);
$date_sortie = new DateTime(); 
$interval = $date_entree->diff($date_sortie);
$nb_jours = max(1, $interval->days); 
$frais_soins = 150.00; 
$montant_total = ($nb_jours * $tarif_nuit) + $frais_soins;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Génération Facture | MedCare</title>
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

        /* HEADER & SIDEBAR - COPIE EXACTE */
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

        /* INVOICE STYLING - COPIE EXACTE */
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
            height: 8px; background: var(--primary);
        }
        .invoice-body { padding: 50px; }
        
        .info-label { color: #64748b; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px; }
        .info-value { font-weight: 700; color: #1e293b; font-size: 16px; }

        .item-table { width: 100%; margin: 30px 0; border-collapse: separate; border-spacing: 0 10px; }
        .item-table td { background: #f8fafc; padding: 20px; border: 1px solid #f1f5f9; }
        .item-table td:first-child { border-radius: 12px 0 0 12px; }
        .item-table td:last-child { border-radius: 0 12px 12px 0; text-align: right; font-weight: 700; color: var(--primary); }

        .total-section {
            background: #f0fdfa; border: 2px solid #ccfbf1; border-radius: 16px;
            padding: 25px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;
        }
        .total-amount { font-size: 36px; font-weight: 800; color: var(--primary); letter-spacing: -1px; }

        /* FORMULAIRE STYLISÉ POUR LE CENTRE */
        .form-select-custom {
            padding: 15px; border-radius: 14px; border: 1px solid #e2e8f0;
            background-color: #f8fafc; font-weight: 600; color: #1e293b; transition: 0.3s;
        }
        .form-select-custom:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(13, 148, 136, 0.1); outline: none; }

        .btn-confirm { 
            background: var(--sidebar-bg); color: white; border: none; padding: 20px; 
            border-radius: 16px; font-weight: 700; width: 100%; transition: 0.3s; 
            text-transform: uppercase; letter-spacing: 1px; text-decoration: none; display: block; text-align: center;
        }
        .btn-confirm:hover { transform: translateY(-3px); box-shadow: 0 15px 30px rgba(15, 23, 42, 0.2); color: white; }
    </style>
</head>
<body>

<header>
    <div class="d-flex align-items-center">
        <img src="../images/logo_app2.png" alt="Logo" style="height: 42px;">
    </div>
    <div class="user-pill">
        <i class="fa-solid fa-file-invoice-dollar me-2 text-primary"></i>
        <span>Séc. <?= htmlspecialchars($user_info['prenom']) ?></span>
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
        <div class="invoice-card">
            <div class="invoice-header d-flex justify-content-between align-items-start">
                <div>
                    <h1 class="fw-800 mb-1" style="font-weight: 800; font-size: 28px;">ÉDITION DE LA FACTURE</h1>
                    <p class="opacity-75 mb-0">Admission #<?= $adm['id_admission'] ?></p>
                </div>
                <div class="text-end">
                    <div class="info-label text-white-50">Date Facturation</div>
                    <div class="info-value text-white"><?= date('d M Y') ?></div>
                </div>
            </div>

            <div class="invoice-body">
                <div class="row mb-5">
                    <div class="col-md-6">
                        <div class="info-label">Patient concerné</div>
                        <div class="info-value"><?= strtoupper($adm['pat_nom']) ?> <?= $adm['pat_prenom'] ?></div>
                        <div class="text-muted small">CIN : <?= $adm['cin'] ?></div>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <div class="info-label">Médecin Traitant</div>
                        <div class="info-value text-dark">Dr. <?= $adm['nom_medecin'] ?></div>
                        <div class="text-muted small">Date admission : <?= date('d/m/Y', strtotime($adm['date_admission'])) ?></div>
                    </div>
                </div>

                <table class="item-table">
                    <tbody>
                        <tr>
                            <td>
                                <div class="fw-bold text-dark">Frais de séjour</div>
                                <div class="small text-muted"><?= $nb_jours ?> jours × <?= number_format($tarif_nuit, 2) ?> DH</div>
                            </td>
                            <td><?= number_format($nb_jours * $tarif_nuit, 2) ?> DH</td>
                        </tr>
                        <tr>
                            <td>
                                <div class="fw-bold text-dark">Actes & Soins</div>
                                <div class="small text-muted">Consultations et soins standard</div>
                            </td>
                            <td><?= number_format($frais_soins, 2) ?> DH</td>
                        </tr>
                    </tbody>
                </table>

                <div class="total-section">
                    <div>
                        <div class="info-label" style="color: var(--primary);">NET À PAYER</div>
                        <div class="small text-muted">Total des prestations (TTC)</div>
                    </div>
                    <div class="total-amount"><?= number_format($montant_total, 2) ?> <span style="font-size: 18px;">DH</span></div>
                </div>

                <form action="process_facture.php" method="POST">
                    <input type="hidden" name="id_admission" value="<?= $adm['id_admission'] ?>">
                    <input type="hidden" name="id_patient" value="<?= $adm['id_patient'] ?>">
                    <input type="hidden" name="nb_jours" value="<?= $nb_jours ?>">
                    <input type="hidden" name="montant_total" value="<?= $montant_total ?>">

                    <div class="row g-4 mb-5">
                        <div class="col-md-6">
                            <label class="info-label">Mode de Règlement</label>
                            <select name="mode_paiement" class="form-select form-select-custom" required>
                                <option value="Espèces">💵 Espèces</option>
                                <option value="Carte Bancaire">💳 Carte Bancaire</option>
                                <option value="Chèque">📝 Chèque</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="info-label">Type de Couverture</label>
                            <select name="type_couverture" class="form-select form-select-custom" required>
                                <option value="Privé">Privé (Aucune)</option>
                                <option value="AMO">AMO</option>
                                <option value="CNOPS">CNOPS</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="btn-confirm">
                        <i class="fa-solid fa-check-circle me-2"></i> Valider le paiement & Imprimer
                    </button>
                </form>
            </div>
        </div>
    </main>
</div>

</body>
</html>