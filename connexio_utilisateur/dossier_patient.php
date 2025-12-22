<?php
session_start();
include("../config/connexion.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'medecin') {
    header("Location: login.php");
    exit;
}

$id_medecin = $_SESSION['user_id'];
$id_patient = $_GET['id'] ?? null;

if (!$id_patient) {
    die("Patient non spécifié");
}

/* =======================
   Infos patient
======================= */
$stmt = $pdo->prepare("
    SELECT * FROM patients 
    WHERE id_patient = ? AND id_medecin = ?
");
$stmt->execute([$id_patient, $id_medecin]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$patient) {
    die("Patient introuvable");
}

/* =======================
   Suivis
======================= */
$stmt = $pdo->prepare("
    SELECT * FROM suivis 
    WHERE id_patient = ?
    ORDER BY date_suivi DESC
");
$stmt->execute([$id_patient]);
$suivis = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =======================
   Traitements
======================= */
$stmt = $pdo->prepare("
    SELECT * FROM traitements 
    WHERE id_patient = ?
    ORDER BY date_traitement DESC
");
$stmt->execute([$id_patient]);
$traitements = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Dossier Patient</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
.status-termine {
    color: red;
    font-weight: bold;
}
</style>
</head>
<body class="bg-light">

<div class="container mt-4">

<!-- ================= PROFIL PATIENT ================= -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        🧑‍⚕️ Profil du patient
    </div>
    <div class="card-body">
        <p><strong>Nom :</strong> <?= htmlspecialchars($patient['nom']) ?></p>
        <p><strong>Prénom :</strong> <?= htmlspecialchars($patient['prenom']) ?></p>
        <p><strong>Date de naissance :</strong> <?= htmlspecialchars($patient['date_naissance']) ?></p>
        <p><strong>Sexe :</strong> <?= htmlspecialchars($patient['sexe']) ?></p>
        <p><strong>Téléphone :</strong> <?= htmlspecialchars($patient['telephone']) ?></p>
        <p><strong>Email :</strong> <?= htmlspecialchars($patient['email']) ?></p>
        <p><strong>Adresse :</strong> <?= htmlspecialchars($patient['adresse']) ?></p>

        <a href="ajouter_suivi.php?id=<?= $id_patient ?>" class="btn btn-warning me-2">
            ➕ Ajouter suivi
        </a>
        <a href="ajouter_traitement.php?id=<?= $id_patient ?>" class="btn btn-success">
            ➕ Ajouter traitement
        </a>
    </div>
</div>

<!-- ================= SUIVIS ================= -->
<h4 class="mb-3">📌 Suivis</h4>

<?php if (!empty($suivis)): ?>
    <?php foreach ($suivis as $s): ?>
        <div class="card mb-3 border-warning">
            <div class="card-header">
                📅 Date du suivi : <?= htmlspecialchars($s['date_suivi']) ?>
            </div>
            <div class="card-body">
                <p>
                    <strong>Commentaire :</strong><br>
                    <?= nl2br(htmlspecialchars($s['commentaire'])) ?>
                </p>

                <p>
                    <strong>Statut :</strong>
                    <?php if ($s['status'] === 'termine'): ?>
                        <span class="status-termine">Terminé</span>
                    <?php else: ?>
                        <span class="text-success">En cours</span>
                    <?php endif; ?>
                </p>

                <?php if ($s['status'] !== 'termine'): ?>
                    <a href="modifier_statut_suivi.php?id=<?= $s['id_suivi'] ?>&patient=<?= $id_patient ?>"
                       class="btn btn-success btn-sm">
                        ✔ Marquer comme terminé
                    </a>
                <?php else: ?>
                    <a href="supprimer_suivi.php?id=<?= $s['id_suivi'] ?>&patient=<?= $id_patient ?>"
                       class="btn btn-danger btn-sm"
                       onclick="return confirm('Supprimer ce suivi ?')">
                        🗑 Supprimer
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <p class="text-muted">Aucun suivi enregistré.</p>
<?php endif; ?>

<!-- ================= TRAITEMENTS ================= -->
<h4 class="mt-4 mb-3">💊 Traitements</h4>

<?php if (!empty($traitements)): ?>
    <?php foreach ($traitements as $t): ?>
        <div class="card mb-3 border-success">
            <div class="card-header">
                📅 Date du traitement : <?= htmlspecialchars($t['date_traitement']) ?>
            </div>
            <div class="card-body">
                <p><strong>Description :</strong><br>
                    <?= nl2br(htmlspecialchars($t['description'])) ?>
                </p>
                <p><strong>Médicament :</strong>
                    <?= htmlspecialchars($t['medicament']) ?>
                </p>
            </div>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <p class="text-muted">Aucun traitement enregistré.</p>
<?php endif; ?>

</div>

</body>
</html>
