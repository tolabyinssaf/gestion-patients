<?php
session_start();
include("../config/connexion.php");

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'secretaire') {
    header("Location: login.php");
    exit;
}

$id_patient = $_GET['id'] ?? null;
if (!$id_patient) { header("Location: patients_secr.php"); exit; }

// 1. Récupérer les infos du Patient
$stmt = $pdo->prepare("SELECT * FROM patients WHERE id_patient = ?");
$stmt->execute([$id_patient]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);

// 2. Récupérer la dernière admission non facturée (pour la lier à la facture)
$stmt_adm = $pdo->prepare("SELECT a.*, u.nom as nom_medecin FROM admissions a 
                           JOIN utilisateurs u ON a.id_medecin = u.id_user 
                           WHERE a.id_patient = ? ORDER BY a.date_admission DESC LIMIT 1");
$stmt_adm->execute([$id_patient]);
$last_admission = $stmt_adm->fetch(PDO::FETCH_ASSOC);

// 3. Traitement de la Facture (POST)
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_facture'])) {
    $montant = $_POST['montant_total'];
    $mode = $_POST['mode_paiement'];
    $couverture = $_POST['type_couverture'];
    $statut = $_POST['statut_paiement'];
    $id_adm_facture = $_POST['id_admission'];
    $num_facture = "FAC-" . date('Ymd') . "-" . rand(100, 999);

    try {
        $sql = "INSERT INTO factures (id_admission, id_patient, date_facture, numero_facture, montant_total, mode_paiement, type_couverture, statut_paiement) 
                VALUES (?, ?, NOW(), ?, ?, ?, ?, ?)";
        $pdo->prepare($sql)->execute([$id_adm_facture, $id_patient, $num_facture, $montant, $mode, $couverture, $statut]);
        $message = "success";
    } catch (Exception $e) {
        $message = "Erreur : " . $e->getMessage();
    }
}

// 4. Récupérer l'historique des factures du patient
$stmt_fac = $pdo->prepare("SELECT * FROM factures WHERE id_patient = ? ORDER BY date_facture DESC");
$stmt_fac->execute([$id_patient]);
$factures = $stmt_fac->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Dossier & Facturation | <?= htmlspecialchars($patient['nom']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --primary: #0f766e; --sidebar-bg: #0f172a; --bg-body: #f8fafc; }
        body { background: var(--bg-body); font-family: 'Plus Jakarta Sans', sans-serif; }
        .sidebar { width: 260px; background: var(--sidebar-bg); position: fixed; top: 75px; left: 0; bottom: 0; padding: 20px; }
        .sidebar a { color: #94a3b8; text-decoration: none; display: block; padding: 12px; border-radius: 8px; margin-bottom: 5px; }
        .sidebar a.active { background: var(--primary); color: white; }
        .content { margin-left: 260px; padding: 110px 40px 40px; }
        header { background: white; height: 75px; position: fixed; top: 0; width: 100%; z-index: 1000; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; padding: 0 40px; }
        
        .card-custom { background: white; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 12px rgba(0,0,0,0.03); margin-bottom: 20px; padding: 25px; }
        .badge-status { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .bg-paye { background: #dcfce7; color: #166534; }
        .btn-save { background: var(--primary); color: white; border: none; padding: 12px; border-radius: 10px; font-weight: 600; width: 100%; }
    </style>
</head>
<body>

<header>
    <img src="../images/logo_app2.png" style="height:45px;">
    <h5 class="ms-4 mb-0 fw-bold text-dark">Dossier Patient : <?= htmlspecialchars($patient['nom']." ".$patient['prenom']) ?></h5>
</header>

<div class="sidebar">
    <a href="dashboard_secretaire.php"><i class="fa-solid fa-house me-2"></i> Tableau de bord</a>
    <a href="patients_secr.php" class="active"><i class="fa-solid fa-users me-2"></i> Patients</a>
    <a href="caisse.php"><i class="fa-solid fa-wallet me-2"></i> Caisse</a>
</div>

<main class="content">
    <?php if($message === "success"): ?>
        <div class="alert alert-success border-0 shadow-sm mb-4"><i class="fa-solid fa-check-circle me-2"></i> Facture enregistrée avec succès !</div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-7">
            <div class="card-custom">
                <h6 class="text-muted text-uppercase mb-3" style="font-size: 12px; letter-spacing: 1px;">Informations Personnelles</h6>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">CIN</label>
                        <div class="fw-bold"><?= $patient['cin'] ?></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Téléphone</label>
                        <div class="fw-bold"><?= $patient['telephone'] ?></div>
                    </div>
                </div>
            </div>

            <div class="card-custom">
                <h6 class="fw-bold mb-4">Historique des Factures</h6>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr class="text-muted" style="font-size: 13px;">
                                <th>N° Facture</th>
                                <th>Date</th>
                                <th>Montant</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($factures as $f): ?>
                            <tr>
                                <td class="fw-bold"><?= $f['numero_facture'] ?></td>
                                <td class="small"><?= date('d/m/Y', strtotime($f['date_facture'])) ?></td>
                                <td class="fw-bold text-primary"><?= $f['montant_total'] ?> DH</td>
                                <td><span class="badge-status bg-paye"><?= $f['statut_paiement'] ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card-custom border-primary" style="border-width: 2px;">
                <h5 class="fw-bold mb-3"><i class="fa-solid fa-file-invoice-dollar text-primary me-2"></i>Nouvelle Facture</h5>
                
                <?php if($last_admission): ?>
                <div class="p-3 bg-light rounded-3 mb-4">
                    <small class="text-muted">Consultation en cours :</small>
                    <div class="fw-bold">Dr. <?= htmlspecialchars($last_admission['nom_medecin']) ?></div>
                    <small class="text-primary">Admission #<?= $last_admission['id_admission'] ?></small>
                </div>

                <form method="POST">
                    <input type="hidden" name="id_admission" value="<?= $last_admission['id_admission'] ?>">
                    
                    <div class="mb-3">
                        <label class="small fw-bold">MONTANT TOTAL (DH)</label>
                        <input type="number" name="montant_total" class="form-control form-control-lg" placeholder="0.00" required>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold">MODE DE PAIEMENT</label>
                        <select name="mode_paiement" class="form-select">
                            <option value="Espèces">Espèces</option>
                            <option value="Carte Bancaire">Carte Bancaire</option>
                            <option value="Chèque">Chèque</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold">COUVERTURE</label>
                        <select name="type_couverture" class="form-select">
                            <option value="Sans">Privé (Sans)</option>
                            <option value="AMO">AMO</option>
                            <option value="CNOPS">CNOPS</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="small fw-bold">STATUT</label>
                        <select name="statut_paiement" class="form-select">
                            <option value="Payé">Payé</option>
                            <option value="En attente">En attente</option>
                        </select>
                    </div>

                    <button type="submit" name="ajouter_facture" class="btn-save">
                        <i class="fa-solid fa-print me-2"></i>Enregistrer & Imprimer
                    </button>
                </form>
                <?php else: ?>
                    <div class="alert alert-warning small">Aucune admission active trouvée pour ce patient.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

</body>
</html>