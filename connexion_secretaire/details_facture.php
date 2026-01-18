<?php
session_start();
include("../config/connexion.php");

$id_admission = $_GET['id_adm'] ?? null;

if (!$id_admission) {
    header("Location: liste_patients.php"); exit;
}

// 1. RECUPERER LA FACTURE (Générée par le Trigger)
$stmt = $pdo->prepare("SELECT f.*, p.nom, p.prenom, p.cin, a.date_admission 
                       FROM factures f 
                       JOIN admissions a ON f.id_admission = a.id_admission 
                       JOIN patients p ON a.id_patient = p.id_patient 
                       WHERE f.id_admission = ?");
$stmt->execute([$id_admission]);
$facture = $stmt->fetch();

// 2. RECUPERER LE DETAIL DES SOINS (Saisis par l'infirmier)
$stmt_soins = $pdo->prepare("SELECT s.*, pr.nom_prestation, pr.prix_unitaire 
                             FROM soins_patients s
                             JOIN prestations pr ON s.id_prestation = pr.id_prestation
                             WHERE s.id_admission = ?
                             ORDER BY s.date_soin DESC");
$stmt_soins->execute([$id_admission]);
$liste_soins = $stmt_soins->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Détail Facture | MedCare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">
    <a href="liste_patients_inf.php" class="btn btn-secondary mb-4">
        <i class="fa-solid fa-arrow-left"></i> Retour à la liste
    </a>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 text-primary">Détails des actes médicaux</h5>
                </div>
                <div class="card-body">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date & Heure</th>
                                <th>Acte / Prestation</th>
                                <th class="text-end">Prix Unitaire</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($liste_soins as $soin): ?>
                            <tr>
                                <td><?= date('d/m/Y H:i', strtotime($soin['date_soin'])) ?></td>
                                <td>
                                    <strong><?= $soin['nom_prestation'] ?></strong><br>
                                    <small class="text-muted"><?= $soin['observations'] ?></small>
                                </td>
                                <td class="text-end"><?= number_format($soin['prix_unitaire'], 2) ?> DH</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm border-0 bg-primary text-white p-4">
                <h6 class="text-uppercase opacity-75">Patient</h6>
                <h4><?= strtoupper($facture['nom']) ?> <?= $facture['prenom'] ?></h4>
                <hr>
                <div class="d-flex justify-content-between mb-2">
                    <span>Frais de séjour :</span>
                    <span><?= $facture['nb_jours'] * $facture['prix_unitaire_jour'] ?> DH</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Frais actes (Soins) :</span>
                    <span><?= $facture['frais_actes_medicaux'] ?> DH</span>
                </div>
                <hr>
                <div class="d-flex justify-content-between mb-4">
                    <span class="h5">TOTAL À PAYER :</span>
                    <span class="h5"><?= number_format($facture['montant_total'], 2) ?> DH</span>
                </div>
                
                <form action="valider_paiement.php" method="POST">
                    <input type="hidden" name="id_admission" value="<?= $id_admission ?>">
                    <button type="submit" class="btn btn-light w-100 fw-bold py-3">
                        ENCAISSER & CLÔTURER
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

</body>
</html>