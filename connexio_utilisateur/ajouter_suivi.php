<?php
session_start();
include("../config/connexion.php"); // connexion DB

// Vérifier que l'utilisateur est connecté et est médecin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'medecin') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$patient_id = $_GET['id'] ?? null;

if (!$patient_id) {
    die("Patient non spécifié.");
}

// Vérifier que le patient appartient au médecin
$stmt = $pdo->prepare("SELECT * FROM patients WHERE id_patient = ? AND id_medecin = ?");
$stmt->execute([$patient_id, $user_id]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$patient) {
    die("Patient introuvable.");
}

$message = "";

// Ajouter suivi via procédure
if (isset($_POST['ajouter_suivi'])) {
    $date_suivi = $_POST['date_suivi'];
    $commentaire = $_POST['commentaire'];
    $status = $_POST['status'];

    try {
        $stmt = $pdo->prepare("CALL ajouter_suivi(?, ?, ?, ?)");
        $stmt->execute([$patient_id, $date_suivi, $commentaire, $status]);

        // Si tout est OK, redirection vers le dossier patient
        header("Location: dossier_patient.php?id=" . $patient_id);
        exit;

    } catch (PDOException $e) {
        // Afficher seulement le message si date < aujourd'hui
        if (strpos($e->getMessage(), "La date du suivi ne peut pas") !== false) {
            $message = "La date du suivi ne peut pas être antérieure à aujourd'hui.";
        } else {
            $message = "Erreur lors de l'ajout du suivi.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Ajouter Suivi - <?= htmlspecialchars($patient['prenom'].' '.$patient['nom']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background: #f9f9f9; }
.container { margin-top: 30px; max-width: 600px; }
.card { padding: 20px; }
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container-fluid">
    <a class="navbar-brand" href="#">Dashboard Médecin</a>
    <div class="d-flex">
      <a href="dossier_patient.php?id=<?= $patient_id ?>" class="btn btn-light me-2">Retour Dossier</a>
      <a href="../connexion_utilisateurs/deconnexion.php" class="btn btn-danger">Déconnexion</a>
    </div>
  </div>
</nav>

<div class="container">
    <div class="card">
        <h3>Ajouter un suivi pour <?= htmlspecialchars($patient['prenom'].' '.$patient['nom']) ?></h3>
        <?php if($message): ?>
            <div id="message" class="alert alert-warning"><?= $message ?></div>
        <?php endif; ?>
        <form method="POST" id="formSuivi">
            <div class="mb-3">
                <label for="date_suivi" class="form-label">Date du suivi</label>
                <input type="date" id="date_suivi" name="date_suivi" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="commentaire" class="form-label">Commentaire</label>
                <textarea id="commentaire" name="commentaire" class="form-control" rows="4" required></textarea>
            </div>
            <div class="mb-3">
                <label for="status" class="form-label">Status</label>
                <select id="status" name="status" class="form-control" required>
                    <option value="En cours">En cours</option>
                    <option value="Terminé">Terminé</option>
                </select>
            </div>
            <button type="submit" name="ajouter_suivi" class="btn btn-success">Ajouter Suivi</button>
        </form>
    </div>
</div>

<script>
// Faire disparaître le message dès que l'utilisateur commence à taper
const messageDiv = document.getElementById('message');
const inputs = document.querySelectorAll('#formSuivi input, #formSuivi textarea, #formSuivi select');
inputs.forEach(input => {
    input.addEventListener('input', () => {
        if(messageDiv) messageDiv.style.display = 'none';
    });
});
</script>

</body>
</html>
