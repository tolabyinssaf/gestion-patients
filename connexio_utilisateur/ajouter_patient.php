<?php
session_start();
include("../config/connexion.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'medecin') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = "";

if (isset($_POST['ajouter'])) {

    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $date_naissance = $_POST['date_naissance'];
    $sexe = $_POST['sexe']; // M ou F
    $adresse = $_POST['adresse'];
    $telephone = $_POST['telephone'];
    $email = $_POST['email'];
    $date_inscription = date('Y-m-d');

    try {
        $stmt = $pdo->prepare("
            INSERT INTO patients
            (nom, prenom, date_naissance, sexe, adresse, telephone, email, date_inscription, id_medecin)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $nom, $prenom, $date_naissance, $sexe,
            $adresse, $telephone, $email,
            $date_inscription, $user_id
        ]);

        $message = "Patient ajouté avec succès";

    } catch (PDOException $e) {

        // ERREUR PROVENANT DU TRIGGER
        if (strpos($e->getMessage(), 'Email déjà utilisé') !== false) {
            $message = "❌ Email déjà utilisé";
        } else {
            $message = "❌ Erreur lors de l'ajout";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Ajouter patient</title>

<style>
body {
    font-family: Arial, sans-serif;
    background: #f9f9f9;
}

.form-container {
    width: 400px;
    margin: 60px auto;
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}

input, select {
    width: 100%;
    padding: 8px;
    margin-bottom: 12px;
}

button {
    background: #01A28C;
    color: white;
    padding: 10px;
    width: 100%;
    border: none;
    border-radius: 5px;
}

.alert {
    background: #f8d7da;
    color: #721c24;
    padding: 10px;
    margin-bottom: 10px;
    border-radius: 4px;
}

.success {
    background: #d4edda;
    color: #155724;
    padding: 10px;
    margin-bottom: 10px;
    border-radius: 4px;
}
</style>
</head>

<body>

<div class="form-container">
<h2>Ajouter un patient</h2>

<?php if ($message): ?>
    <div class="<?= strpos($message,'succès') !== false ? 'success' : 'alert' ?>">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<form method="POST">

<input type="text" name="nom" placeholder="Nom" required>
<input type="text" name="prenom" placeholder="Prénom" required>
<input type="date" name="date_naissance" required>

<select name="sexe" required>
    <option value="">-- Sexe --</option>
    <option value="M">Homme</option>
    <option value="F">Femme</option>
</select>

<input type="text" name="adresse" placeholder="Adresse" required>
<input type="text" name="telephone" placeholder="Téléphone" required>
<input type="email" name="email" placeholder="Email" required>

<button type="submit" name="ajouter">Ajouter</button>

</form>

<br>
<a href="patients.php">⬅ Retour à la liste</a>
</div>

</body>
</html>
