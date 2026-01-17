<?php
session_start();
include("../config/connexion.php");

// Sécurité
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'secretaire') {
    header("Location: login.php"); exit;
}

$id_admission = $_GET['id_admission'] ?? null;
if (!$id_admission) { header("Location: dashboard_secretaire.php"); exit; }

// 1. Récupérer les données de l'admission
$stmt = $pdo->prepare("SELECT a.*, p.nom, p.prenom, p.cin, u.nom as nom_medecin 
                       FROM admissions a 
                       JOIN patients p ON a.id_patient = p.id_patient 
                       JOIN utilisateurs u ON a.id_medecin = u.id_user 
                       WHERE a.id_admission = ?");
$stmt->execute([$id_admission]);
$adm = $stmt->fetch(PDO::FETCH_ASSOC);

// 2. LOGIQUE DE CALCUL PROFESSIONNELLE
$tarif_nuit = 600; // Prix fixe par nuitée
$date_entree = new DateTime($adm['date_admission']);
$date_sortie = new DateTime(); // Aujourd'hui
$interval = $date_entree->diff($date_sortie);
$nb_jours = $interval->days;

if ($nb_jours == 0) $nb_jours = 1; // Toute journée entamée est due
$frais_soins = 150.00; // Forfait soins infirmiers fixe
$montant_total = ($nb_jours * $tarif_nuit) + $frais_soins;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Facturation Admission | MedCare</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --primary: #0f766e; --bg-body: #f8fafc; --sidebar-bg: #0f172a; }
        body { background: var(--bg-body); font-family: 'Plus Jakarta Sans', sans-serif; }
        .sidebar { width: 260px; background: var(--sidebar-bg); position: fixed; top: 75px; left: 0; bottom: 0; padding: 20px; }
        .content { margin-left: 260px; padding: 110px 40px 40px; }
        header { background: white; height: 75px; position: fixed; top: 0; width: 100%; z-index: 1000; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; padding: 0 40px; }
        .card-billing { background: white; border-radius: 20px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .stat-box { background: #f1f5f9; padding: 15px; border-radius: 12px; text-align: center; }
    </style>
</head>
<body>

<header>
    <img src="../images/logo_app2.png" style="height:45px;">
    <h5 class="ms-4 mb-0 fw-bold">Clôture de Séjour</h5>
</header>

<div class="sidebar">
    <a href="dashboard_secretaire.php" style="color:white; text-decoration:none;"><i class="fa-solid fa-house me-2"></i> Retour au Dashboard</a>
</div>

<main class="content">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card card-billing p-4">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6 class="text-muted small text-uppercase fw-bold">Patient</h6>
                        <h4 class="fw-bold"><?= $adm['nom'].' '.$adm['prenom'] ?> (<?= $adm['cin'] ?>)</h4>
                        <p class="text-muted"><i class="fa-solid fa-user-doctor me-1"></i> Dr. <?= $adm['nom_medecin'] ?></p>
                    </div>
                    <div class="col-md-6 text-end">
                        <span class="badge bg-success-subtle text-success rounded-pill px-3 py-2">Admission active #<?= $adm['id_admission'] ?></span>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="stat-box">
                            <div class="small text-muted">Durée</div>
                            <div class="h4 fw-bold mb-0"><?= $nb_jours ?> Nuit(s)</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-box">
                            <div class="small text-muted">Tarif Chambre</div>
                            <div class="h4 fw-bold mb-0"><?= $tarif_nuit ?> DH/J</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-box bg-primary text-white">
                            <div class="small opacity-75">Total Cumulé</div>
                            <div class="h4 fw-bold mb-0"><?= number_format($montant_total, 2) ?> DH</div>
                        </div>
                    </div>
                </div>

                <form action="process_facture.php" method="POST">
                    <input type="hidden" name="id_admission" value="<?= $adm['id_admission'] ?>">
                    <input type="hidden" name="id_patient" value="<?= $adm['id_patient'] ?>">
                    <input type="hidden" name="nb_jours" value="<?= $nb_jours ?>">
                    <input type="hidden" name="prix_unitaire_jour" value="<?= $tarif_nuit ?>">
                    <input type="hidden" name="frais_actes" value="<?= $frais_soins ?>">
                    <input type="hidden" name="montant_total" value="<?= $montant_total ?>">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="fw-bold small mb-2">MODE DE PAIEMENT</label>
                            <select name="mode_paiement" class="form-select" required>
                                <option value="Espèces">Espèces</option>
                                <option value="Carte Bancaire">Carte Bancaire</option>
                                <option value="Chèque">Chèque</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-bold small mb-2">COUVERTURE MÉDICALE</label>
                            <select name="type_couverture" class="form-select" required>
                                <option value="Privé">Privé (Aucune)</option>
                                <option value="AMO">AMO</option>
                                <option value="CNOPS">CNOPS</option>
                                <option value="Mutuelle">Mutuelle Privée</option>
                            </select>
                        </div>
                        <div class="col-12 mt-4">
                            <button type="submit" class="btn btn-dark w-100 py-3 fw-bold rounded-3">
                                <i class="fa-solid fa-receipt me-2"></i> ENREGISTRER LE PAIEMENT ET GÉNÉRER LE REÇU
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

</body>
</html>