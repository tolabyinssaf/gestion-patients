<?php
session_start();
include("../config/connexion.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'medecin') {
    header("Location: login.php");
    exit;
}

$id_medecin = $_SESSION['user_id'];

/* =========================
   Récupérer tous les suivis
   du médecin avec patients
========================= */
$stmt = $pdo->prepare("
    SELECT 
        s.id_suivi,
        s.date_suivi,
        s.commentaire,
        s.status,
        p.id_patient,
        p.nom,
        p.prenom
    FROM suivis s
    JOIN patients p ON s.id_patient = p.id_patient
    WHERE p.id_medecin = ?
    ORDER BY s.date_suivi DESC
");
$stmt->execute([$id_medecin]);
$suivis = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Suivis - Médecin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
.status-termine {
    color: red;
    font-weight: bold;
}
</style>
</head>

<body class="bg-light">

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container-fluid">
    <a class="navbar-brand" href="#">Dashboard Médecin</a>
    <div class="d-flex">
      <a href="patients.php" class="btn btn-light me-2">Patients</a>
      <a href="../connexion_utilisateurs/deconnexion.php" class="btn btn-danger">Déconnexion</a>
    </div>
  </div>
</nav>

<div class="container mt-4">

<h3 class="mb-4">📋 Liste des suivis</h3>

<?php if (!empty($suivis)): ?>
    <?php foreach ($suivis as $s): ?>
        <div class="card mb-3 border-warning">
            <div class="card-header">
                👤 Patient :
                <strong><?= htmlspecialchars($s['prenom'].' '.$s['nom']) ?></strong>
                |
                📅 <?= htmlspecialchars($s['date_suivi']) ?>
            </div>

            <div class="card-body">
                <p>
                    <strong>Commentaire :</strong><br>
                    <?= nl2br(htmlspecialchars($s['commentaire'])) ?>
                </p>

                <p>
                    <strong>Statut :</strong>
                    <?php if ($s['status'] === 'Terminé'): ?>
                        <span class="status-termine">Terminé</span>
                    <?php else: ?>
                        <span class="text-success">En cours</span>
                    <?php endif; ?>
                </p>

                <!-- ACTIONS -->
                <div class="mt-2">
                    <a href="dossier_patient.php?id=<?= $s['id_patient'] ?>"
                       class="btn btn-info btn-sm">
                       📁 Dossier patient
                    </a>

                    <?php if ($s['status'] !== 'Terminé'): ?>
                        <a href="modifier_statut_suivi.php?id=<?= $s['id_suivi'] ?>"
                           class="btn btn-success btn-sm">
                           ✔ Marquer terminé
                        </a>
                    <?php else: ?>
                        <a href="supprimer_suivi.php?id=<?= $s['id_suivi'] ?>"
                           class="btn btn-danger btn-sm"
                           onclick="return confirm('Supprimer ce suivi ?')">
                           🗑 Supprimer
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <div class="alert alert-info">
        Aucun suivi trouvé.
    </div>
<?php endif; ?>

</div>

</body>
</html>
