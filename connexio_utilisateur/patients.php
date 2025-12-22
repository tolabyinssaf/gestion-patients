<?php
session_start();
include("../config/connexion.php"); 

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'medecin') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = "";
$form_message = "";

// ====== Récupérer infos médecin ======
$stmt = $pdo->prepare("SELECT nom, prenom, email FROM utilisateurs WHERE id_user = ?");
$stmt->execute([$user_id]);
$medecin = $stmt->fetch(PDO::FETCH_ASSOC);

// Nombre total de patients
$stmt2 = $pdo->prepare("SELECT COUNT(*) AS total_patients FROM patients WHERE id_medecin = ?");
$stmt2->execute([$user_id]);
$total_patients = $stmt2->fetch(PDO::FETCH_ASSOC)['total_patients'];

// Derniers suivis (5 derniers)
$stmt3 = $pdo->prepare("SELECT p.nom, p.prenom, s.date_suivi, s.commentaire
                        FROM suivis s
                        JOIN patients p ON s.id_patient = p.id_patient
                        WHERE p.id_medecin = ?
                        ORDER BY s.date_suivi DESC
                        LIMIT 5");
$stmt3->execute([$user_id]);
$derniers_suivis = $stmt3->fetchAll(PDO::FETCH_ASSOC);

// ====== AJOUT PATIENT ======
if (isset($_POST['ajouter'])) {
    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $date_naissance = $_POST['date_naissance'];
    $sexe = $_POST['sexe'];
    $adresse = $_POST['adresse'];
    $telephone = $_POST['telephone'];
    $email = $_POST['email'];
    $date_inscription = date('Y-m-d');

    try {
        $stmt = $pdo->prepare("INSERT INTO patients 
            (nom, prenom, date_naissance, sexe, adresse, telephone, email, date_inscription, id_medecin)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nom, $prenom, $date_naissance, $sexe, $adresse, $telephone, $email, $date_inscription, $user_id]);
        $message = "Patient ajouté avec succès";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), "Duplicate entry") !== false && strpos($e->getMessage(), "email") !== false) {
            $form_message = "Email déjà utilisé";
        } else {
            $form_message = "Erreur lors de l'ajout";
        }
    }
}

// ====== MODIFIER PATIENT ======
if (isset($_POST['modifier'])) {
    $id_patient = $_POST['id_patient'];
    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $date_naissance = $_POST['date_naissance'];
    $sexe = $_POST['sexe'];
    $adresse = $_POST['adresse'];
    $telephone = $_POST['telephone'];
    $email = $_POST['email'];

    try {
        $stmt = $pdo->prepare("UPDATE patients 
            SET nom = ?, prenom = ?, date_naissance = ?, sexe = ?, adresse = ?, telephone = ?, email = ? 
            WHERE id_patient = ? AND id_medecin = ?");
        $stmt->execute([$nom, $prenom, $date_naissance, $sexe, $adresse, $telephone, $email, $id_patient, $user_id]);
        $message = "Patient modifié avec succès";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), "Duplicate entry") !== false && strpos($e->getMessage(), "email") !== false) {
            $form_message = "Email déjà utilisé";
            $edit_patient = ['id_patient'=>$id_patient, 'nom'=>$nom, 'prenom'=>$prenom, 'date_naissance'=>$date_naissance,
                             'sexe'=>$sexe, 'adresse'=>$adresse, 'telephone'=>$telephone, 'email'=>$email];
        } else {
            $form_message = "Erreur lors de la modification";
        }
    }
}

// ====== SUPPRIMER PATIENT ======
if (isset($_GET['supprimer'])) {
    $id_patient = $_GET['supprimer'];
    $stmt = $pdo->prepare("DELETE FROM patients WHERE id_patient = ? AND id_medecin = ?");
    $stmt->execute([$id_patient, $user_id]);
}

// ====== RECHERCHE OU LISTE COMPLETE ======
$search = $_GET['search'] ?? '';

if ($search) {
    $stmt = $pdo->prepare("SELECT * FROM patients 
        WHERE id_medecin = ? AND (nom LIKE ? OR prenom LIKE ?)
        ORDER BY nom ASC");
    $stmt->execute([$user_id, "%$search%", "%$search%"]);
} else {
    $stmt = $pdo->prepare("SELECT * FROM patients WHERE id_medecin = ? ORDER BY nom ASC");
    $stmt->execute([$user_id]);
}

$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ====== SI ÉDITION ======
$edit_patient = null;
if (isset($_GET['edit'])) {
    $stmt_edit = $pdo->prepare("SELECT * FROM patients WHERE id_patient = ? AND id_medecin = ?");
    $stmt_edit->execute([$_GET['edit'], $user_id]);
    $edit_patient = $stmt_edit->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Mes patients</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f9f9f9; margin:0; padding:0; }
header { background: #01A28C; color:white; padding:20px; text-align:center; }
header h1 { margin:0; font-size:24px; }
header p { margin:5px 0 0 0; color:#f0f0f0; }
nav { background:white; padding:10px 20px; display:flex; gap:15px; box-shadow:0 2px 5px rgba(0,0,0,0.1);}
nav a { text-decoration:none; color:#737978; font-weight:500; display:flex; align-items:center; gap:5px; transition:0.3s;}
nav a:hover { color:#01A28C; }
.container { padding:20px; max-width:1200px; margin:auto; }
.message { color:red; font-weight:bold; margin-bottom:20px; }
.form-container { background:white; padding:25px; border-radius:12px; box-shadow:0 4px 15px rgba(0,0,0,0.1); margin-bottom:30px; display:none; }
.form-container h2 { color:#01A28C; }
.form-container input, .form-container select { width:100%; padding:10px; margin-bottom:12px; border:1px solid #ccc; border-radius:6px; }
.form-container button { padding:10px 20px; background:#01A28C; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600; }
.form-container button:hover { background:#018f7a; }
#addPatientBtn { margin-bottom:20px; padding:12px 20px; background:#01A28C; color:white; border:none; border-radius:8px; cursor:pointer; font-weight:600; }
#addPatientBtn:hover { background:#018f7a; }
.search-box input { padding:8px; width:250px; border-radius:6px; border:1px solid #ccc; }
.search-box button { padding:8px 14px; margin-left:5px; border:none; border-radius:6px; background:#01A28C; color:white; cursor:pointer; }
.search-box button:hover { background:#018f7a; }
table { width:100%; border-collapse:collapse; background:white; border-radius:12px; overflow:hidden; box-shadow:0 4px 15px rgba(0,0,0,0.08); }
th, td { padding:14px; text-align:left; }
th { background:#01A28C; color:white; }
tr:hover { background:#f1f1f1; }
.action-btn { padding:6px 10px; border-radius:6px; margin-right:5px; font-size:14px; display:inline-flex; align-items:center; gap:4px; text-decoration:none; color:white; }
.action-btn.edit { background:#ffc107; }
.action-btn.edit:hover { background:#e0a800; }
.action-btn.delete { background:#dc3545; }
.action-btn.delete:hover { background:#c82333; }
.action-btn.view { background:#01A28C; }
.action-btn.view:hover { background:#018f7a; }
.no-patient { font-style:italic; color:#737978; margin-top:10px; }
</style>
</head>
<body>

<header>
<h1>Bienvenue Dr. <?= htmlspecialchars($medecin['prenom'] . " " . $medecin['nom']) ?></h1>
<p>Email : <?= htmlspecialchars($medecin['email']) ?></p>
</header>

<nav>
<a href="dashboard_medecin.php"><i class="bi bi-house-door-fill"></i> Accueil</a>
<a href="patients.php"><i class="bi bi-people-fill"></i> Mes patients</a>
<a href="suivis.php"><i class="bi bi-journal-medical"></i> Suivis</a>
<a href="traitements.php"><i class="bi bi-capsule"></i> Traitements</a>
<a href="rendezvous.php"><i class="bi bi-calendar-check-fill"></i> Rendez-vous</a>
<a href="deconnexion.php"><i class="bi bi-box-arrow-right"></i> Déconnexion</a>
</nav>

<div class="container">
<h2>Résumé</h2>
<p>Nombre total de patients : <?= $total_patients ?></p>

<h1>Mes patients</h1>

<?php if(!empty($message)): ?>
<p id="message" class="message"><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

<form method="GET" class="search-box" id="searchForm">
<input type="text" name="search" placeholder="Rechercher un patient..." value="<?= htmlspecialchars($search) ?>" id="searchInput">
<button type="submit"><i class="bi bi-search"></i> Rechercher</button>
</form>

<button id="addPatientBtn"><i class="bi bi-plus-lg"></i> Ajouter un patient</button>

<div class="form-container" id="patientForm" style="display: <?= $edit_patient || !empty($form_message) ? 'block' : 'none' ?>;">
<h2><?= $edit_patient ? 'Modifier patient' : 'Ajouter un patient' ?></h2>
<form method="POST" id="formPatient">
<input type="hidden" name="id_patient" value="<?= $edit_patient['id_patient'] ?? '' ?>">

<?php if(!empty($form_message)): ?>
<div style="color:red; font-weight:bold; margin-bottom:10px;">
    <?= htmlspecialchars($form_message) ?>
</div>
<?php endif; ?>

<?php
// Pré-remplissage des champs
$nom_val = $_POST['nom'] ?? ($edit_patient['nom'] ?? '');
$prenom_val = $_POST['prenom'] ?? ($edit_patient['prenom'] ?? '');
$date_naissance_val = $_POST['date_naissance'] ?? ($edit_patient['date_naissance'] ?? '');
$sexe_val = $_POST['sexe'] ?? ($edit_patient['sexe'] ?? '');
$adresse_val = $_POST['adresse'] ?? ($edit_patient['adresse'] ?? '');
$telephone_val = $_POST['telephone'] ?? ($edit_patient['telephone'] ?? '');
$email_val = $_POST['email'] ?? ($edit_patient['email'] ?? '');
?>

<input type="text" name="nom" placeholder="Nom" value="<?= htmlspecialchars($nom_val) ?>" required>
<input type="text" name="prenom" placeholder="Prénom" value="<?= htmlspecialchars($prenom_val) ?>" required>
<input type="date" name="date_naissance" value="<?= htmlspecialchars($date_naissance_val) ?>" required>
<select name="sexe" required>
<option value="Homme" <?= $sexe_val=='Homme'?'selected':'' ?>>Homme</option>
<option value="Femme" <?= $sexe_val=='Femme'?'selected':'' ?>>Femme</option>
</select>
<input type="text" name="adresse" placeholder="Adresse" value="<?= htmlspecialchars($adresse_val) ?>" required>
<input type="text" name="telephone" placeholder="Téléphone" value="<?= htmlspecialchars($telephone_val) ?>" required>
<input type="email" name="email" placeholder="Email" value="<?= htmlspecialchars($email_val) ?>" required>

<button type="submit" name="<?= $edit_patient ? 'modifier' : 'ajouter' ?>">
<i class="bi bi-check-circle-fill"></i> <?= $edit_patient ? 'Modifier' : 'Ajouter' ?></button>
</form>
</div>

<?php if(count($patients)>0): ?>
<table>
<thead>
<tr>
<th>ID</th><th>Nom</th><th>Prénom</th><th>Date de naissance</th><th>Sexe</th><th>Adresse</th><th>Téléphone</th><th>Email</th><th>Actions</th>
</tr>
</thead>
<tbody>
<?php foreach($patients as $p): ?>
<tr>
<td><?= $p['id_patient'] ?></td>
<td><?= htmlspecialchars($p['nom']) ?></td>
<td><?= htmlspecialchars($p['prenom']) ?></td>
<td><?= $p['date_naissance'] ?></td>
<td><?= $p['sexe'] ?></td>
<td><?= htmlspecialchars($p['adresse']) ?></td>
<td><?= htmlspecialchars($p['telephone']) ?></td>
<td><?= htmlspecialchars($p['email']) ?></td>
<td>
<a href="?edit=<?= $p['id_patient'] ?>" class="action-btn edit"><i class="bi bi-pencil-fill"></i> Modifier</a>
<a href="?supprimer=<?= $p['id_patient'] ?>" onclick="return confirm('Voulez-vous vraiment supprimer ce patient ?')" class="action-btn delete"><i class="bi bi-trash-fill"></i> Supprimer</a>
<a href="dossier_patient.php?id=<?= $p['id_patient'] ?>" class="action-btn view"><i class="bi bi-eye-fill"></i> Voir dossier</a>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php else: ?>
<p class="no-patient">Aucun patient trouvé.</p>
<?php endif; ?>
</div>

<script>
const addBtn = document.getElementById('addPatientBtn');
const formContainer = document.getElementById('patientForm');
const form = document.getElementById('formPatient');

addBtn.addEventListener('click', () => {
    formContainer.style.display = 'block';
    form.reset();
});

const message = document.getElementById('message');
const inputs = document.querySelectorAll('input, select');
inputs.forEach(input => input.addEventListener('input', ()=> message.textContent=''));

document.getElementById('searchForm').addEventListener('submit', ()=>{
    setTimeout(()=>{document.getElementById('searchInput').value='';}, 50);
});
</script>

</body>
</html>
