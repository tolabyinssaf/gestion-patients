<?php
session_start();
include("../config/connexion.php");

// Vérification Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { 
    header("Location: ../login.php"); exit; 
}

$user_id = $_SESSION['user_id'];
$stmt_admin = $pdo->prepare("SELECT nom, prenom FROM utilisateurs WHERE id_user = ?");
$stmt_admin->execute([$user_id]);
$admin = $stmt_admin->fetch(PDO::FETCH_ASSOC);

if (!isset($_GET['id'])) { 
    header("Location: utilisateurs.php"); 
    exit; 
}

$id_edit = (int)$_GET['id'];

// Récupérer les informations de l'utilisateur à modifier
$stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id_user = ?");
$stmt->execute([$id_edit]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) { 
    header("Location: utilisateurs.php"); 
    exit; 
}

$form_message = "";
$cin_message = "";
$cin_valid = true;

// Gestion de la photo
$photoPath = "";
$photoExists = false;
$initials = strtoupper(substr($user['prenom'], 0, 1) . substr($user['nom'], 0, 1));

if (!empty($user['photo'])) {
    $photoPath = "../uploads/photos/" . $user['photo'];
    $photoExists = file_exists($photoPath);
}

$photo_name = $user['photo'] ?? null;

// Si le formulaire est soumis
if (isset($_POST['modifier'])) {
    $nom        = trim($_POST['nom']);
    $prenom     = trim($_POST['prenom']);
    $email      = trim($_POST['email']);
    $role       = $_POST['role'];
    $telephone  = trim($_POST['telephone']);
    $cin        = strtoupper(trim($_POST['cin']));
    $specialite = ($role === 'medecin' && !empty($_POST['specialite'])) ? trim($_POST['specialite']) : '';
    
    // ============ VÉRIFICATION DU CIN AVEC CURSEUR ============
    try {
        $sql_check_cin = "CALL verifier_cin(?, ?)";
        $stmt_check = $pdo->prepare($sql_check_cin);
        $stmt_check->execute([$id_edit, $cin]);
        $result_check = $stmt_check->fetch(PDO::FETCH_ASSOC);
        $stmt_check->closeCursor();
        
        if ($result_check['cin_existe'] == 1) {
            $cin_valid = false;
            $cin_message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                              <i class="fa-solid fa-triangle-exclamation me-2"></i>
                              <strong>'.$result_check['message'].'</strong><br>
                              Utilisateur concerné: '.$result_check['prenom_cin'].' '.$result_check['nom_cin'].'
                              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>';
        } else {
            // Pas de message de succès ici comme demandé
            $cin_valid = true;
        }
        
    } catch (PDOException $e) {
        $cin_message = '<div class="alert alert-warning">Erreur CIN: '.$e->getMessage().'</div>';
        $cin_valid = false;
    }
    
    // ============ VÉRIFICATION DE L'EMAIL ============
    $email_valid = true;
    try {
        $sql_check_email = "SELECT COUNT(*) as count FROM utilisateurs WHERE email = ? AND id_user != ?";
        $stmt_email = $pdo->prepare($sql_check_email);
        $stmt_email->execute([$email, $id_edit]);
        $email_count = $stmt_email->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($email_count > 0) {
            $cin_message .= '<div class="alert alert-danger mt-2"><i class="fa-solid fa-triangle-exclamation me-2"></i>Email déjà utilisé.</div>';
            $email_valid = false;
        }
    } catch (PDOException $e) {
        $email_valid = false;
    }
    
    // ============ GESTION DE LA PHOTO ============
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = "../uploads/photos/";
        $fileExtension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        if (in_array(strtolower($fileExtension), ['jpg', 'jpeg', 'png', 'gif'])) {
            if ($photo_name && file_exists($uploadDir . $photo_name)) unlink($uploadDir . $photo_name);
            $photo_name = "user_" . $id_edit . "_" . time() . "." . $fileExtension;
            move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $photo_name);
        }
    }
    
    // ============ MISE À JOUR SI VALIDE ============
    if ($cin_valid && $email_valid) {
        try {
            $sql = "UPDATE utilisateurs SET nom = ?, prenom = ?, email = ?, role = ?, specialite = ?, telephone = ?, cin = ?, photo = ? WHERE id_user = ?";
            $stmt_update = $pdo->prepare($sql);
            $stmt_update->execute([$nom, $prenom, $email, $role, $specialite, $telephone, $cin, $photo_name, $id_edit]);
            
            $stmt->execute([$id_edit]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            header("Location: utilisateurs.php?msg=success");
        } catch (PDOException $e) {
            $form_message = '<div class="alert alert-danger">Erreur: ' . $e->getMessage() . '</div>';
        }
    }
    // LE BLOC ELSE QUI AFFIChAIT "Veuillez corriger les erreurs..." A ÉTÉ SUPPRIMÉ ICI
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Profil | MedCare Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --primary: #0f766e; --primary-light: #f0fdfa; --sidebar-bg: #0f172a; --bg-body: #f1f5f9; --border-color: #e2e8f0; --input-bg: #f8fafc; }
        body { background: var(--bg-body); font-family: 'Plus Jakarta Sans', sans-serif; margin: 0; display: flex; min-height: 100vh; color: #1e293b; }
        header { background: #ffffff; padding: 0 40px; height: 75px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); position: fixed; width: 100%; top: 0; z-index: 1050; }
        .sidebar { width: 260px; background: var(--sidebar-bg); height: 100vh; position: fixed; top: 0; padding: 100px 16px 24px; z-index: 1000; }
        .sidebar a { display: flex; align-items: center; gap: 12px; color: #94a3b8; text-decoration: none; padding: 12px 16px; border-radius: 10px; margin-bottom: 5px; transition: 0.3s; }
        .sidebar a:hover, .sidebar a.active { background: var(--primary); color: #ffffff; }
        .main-wrapper { flex: 1; margin-left: 260px; padding-top: 75px; }
        .content-container { padding: 40px; max-width: 900px; margin: 0 auto; }
        .profile-sticky-card { position: sticky; top: 95px; z-index: 900; background: #ffffff; border-radius: 16px; padding: 20px 30px; margin-bottom: 25px; border: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .profile-info-left { display: flex; align-items: center; gap: 20px; }
        .profile-avatar { width: 55px; height: 55px; background: var(--primary); color: #ffffff; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 700; overflow: hidden; }
        .user-photo { width: 100%; height: 100%; object-fit: cover; }
        .profile-details h2 { margin: 0; font-size: 1.3rem; font-weight: 800; color: var(--primary); }
        .matricule-badge { background: var(--primary-light); color: var(--primary); padding: 6px 14px; border-radius: 10px; font-size: 0.8rem; font-weight: 700; }
        .form-card { background: #ffffff; border-radius: 20px; border: 1px solid var(--border-color); }
        .form-body { padding: 40px; }
        .form-section-title { font-size: 0.75rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; margin-bottom: 25px; display: flex; align-items: center; gap: 15px; }
        .form-section-title::after { content: ""; flex: 1; height: 1px; background: #f1f5f9; }
        label { font-size: 0.85rem; font-weight: 600; color: #334155; margin-bottom: 10px; display: block; }
        input, select { width: 100%; padding: 12px 16px; background-color: var(--input-bg); border: 1.5px solid var(--border-color); border-radius: 10px; }
        .cin-input-container { position: relative; }
        .cin-check-btn { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: var(--primary-light); border: 1px solid var(--primary); color: var(--primary); padding: 6px 12px; border-radius: 8px; cursor: pointer; }
        .cin-validation { font-size: 0.85rem; margin-top: 5px; padding: 8px 12px; border-radius: 8px; }
        .cin-invalid { background: #fee2e2; color: #991b1b; border-left: 4px solid #ef4444; }
        .btn-update { background: var(--primary); color: white; border: none; padding: 15px; border-radius: 12px; font-weight: 700; width: 100%; }
        .btn-cancel { background: #fff; color: #64748b; border: 1.5px solid var(--border-color); padding: 15px; border-radius: 12px; width: 100%; text-decoration: none; display: block; text-align: center; }
        .photo-section { text-align: center; margin-bottom: 30px; }
        .photo-preview { width: 120px; height: 120px; border-radius: 20px; object-fit: cover; border: 4px solid white; box-shadow: 0 4px 15px rgba(0,0,0,0.1); margin-bottom: 15px; }
        .photo-upload-btn { display: inline-block; background: var(--primary-light); color: var(--primary); padding: 10px 20px; border-radius: 10px; cursor: pointer; font-weight: 600; }
    </style>
</head>
<body>

<header>
    <img src="../images/logo_app2.png" alt="Logo" style="height: 40px;">
    <div style="color: var(--primary); font-weight: 700;">
        <i class="fa-solid fa-circle-user"></i> ADMIN : <?= strtoupper($admin['nom']) ?>
    </div>
</header>

<aside class="sidebar">
        <a href="dashboard_admin.php" ><i class="fa-solid fa-chart-pie"></i> Vue Générale</a>
        <a href="utilisateurs.php"><i class="fa-solid fa-user-md"></i> Utilisateurs</a>
        <a href="prestations.php"><i class="fa-solid fa-list-check"></i> Actes & Tarifs</a>
        <a href="../chambres/gestion_chambres.php"><i class="fa-solid fa-bed"></i> Gestion Chambres</a>
        <div style="height: 1px; background: rgba(255,255,255,0.1); margin: 20px 0;"></div>
        <a href="facturation_list.php"><i class="fa-solid fa-file-invoice-dollar"></i> Rapports Financiers</a>
         <a href="archives.php">
            <i class="fa-solid fa-box-archive"></i> Archives
        </a>
        <a href="profil.php" class="<?= basename($_SERVER['PHP_SELF']) == 'profil.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-user-gear"></i> Mon Profil
        </a>
        <a href="../connexio_utilisateur/login.php" style="color: #fda4af; margin-top: 20px;"><i class="fa-solid fa-power-off"></i> Déconnexion</a>
    </aside>

<div class="main-wrapper">
    <main class="content-container">
        
        <div class="profile-sticky-card">
            <div class="profile-info-left">
                <div class="profile-avatar">
                    <?php if($photoExists): ?>
                        <img src="<?= $photoPath ?>" class="user-photo" id="currentPhoto">
                    <?php else: ?>
                        <?= $initials ?>
                    <?php endif; ?>
                </div>
                <div class="profile-details">
                    <h2><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></h2>
                    <span style="color: #64748b;">Personnel • <?= ucfirst($user['role']) ?></span>
                </div>
            </div>
            <div class="matricule-badge"># <?= $user['matricule'] ?></div>
        </div>

        <?php if (!empty($form_message)) echo $form_message; ?>

        <div class="form-card">
            <form method="POST" class="form-body" enctype="multipart/form-data">
                
                <div class="photo-section">
                    <div>
                        <?php if($photoExists): ?>
                            <img src="<?= $photoPath ?>" class="photo-preview" id="photoPreview">
                        <?php else: ?>
                            <div class="photo-preview" style="background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-size: 2rem;"><?= $initials ?></div>
                        <?php endif; ?>
                    </div>
                    <label class="photo-upload-btn">
                        <i class="fa-solid fa-camera me-2"></i>Changer la photo
                        <input type="file" name="photo" accept="image/*" style="display: none;" onchange="previewPhoto(event)">
                    </label>
                </div>
                
                <span class="form-section-title">Informations de base</span>
                <div class="row mb-4">
                    <div class="col-md-6 mb-3">
                        <label>Nom</label>
                        <input type="text" name="nom" value="<?= htmlspecialchars($user['nom']) ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Prénom</label>
                        <input type="text" name="prenom" value="<?= htmlspecialchars($user['prenom']) ?>" required>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6 mb-3">
                        <label>Numéro CIN</label>
                        <div class="cin-input-container">
                            <input type="text" name="cin" id="cin_input" value="<?= htmlspecialchars($user['cin']) ?>" required oninput="this.value = this.value.toUpperCase()" onblur="verifierCin()">
                            <button type="button" class="cin-check-btn" onclick="verifierCin()"><i class="fa-solid fa-magnifying-glass"></i></button>
                        </div>
                        <div id="cin_result"><?php if (!empty($cin_message)) echo $cin_message; ?></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Téléphone</label>
                        <input type="text" name="telephone" value="<?= htmlspecialchars($user['telephone']) ?>" required>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-12 mb-3">
                        <label>Email</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                    </div>
                </div>

                <div class="row mb-5">
                    <div class="col-md-6 mb-3">
                        <label>Rôle</label>
                        <select name="role" id="role" onchange="checkRole()">
                            <option value="medecin" <?= $user['role'] == 'medecin' ? 'selected' : '' ?>>Médecin</option>
                            <option value="infirmier" <?= $user['role'] == 'infirmier' ? 'selected' : '' ?>>Infirmier(e)</option>
                            <option value="secretaire" <?= $user['role'] == 'secretaire' ? 'selected' : '' ?>>Secrétaire</option>
                            <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : '' ?>>Administrateur</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3" id="specDiv" style="display: <?= $user['role'] == 'medecin' ? 'block' : 'none' ?>;">
                        <label>Spécialité</label>
                        <input type="text" name="specialite" value="<?= htmlspecialchars($user['specialite']) ?>">
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-8"><button type="submit" name="modifier" class="btn-update" id="submitBtn">Mettre à jour</button></div>
                    <div class="col-md-4"><a href="utilisateurs.php" class="btn-cancel">Annuler</a></div>
                </div>
            </form>
        </div>
    </main>
</div>

<script>
function verifierCin() {
    const cinValue = document.getElementById('cin_input').value.trim().toUpperCase();
    const resultDiv = document.getElementById('cin_result');
    if (cinValue === '') return;
    
    fetch('verifier_cin_ajax.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `id_user=<?= $id_edit ?>&cin=${encodeURIComponent(cinValue)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.cin_existe == 1) {
            resultDiv.innerHTML = `<div class="cin-validation cin-invalid"><strong>${data.message}</strong> (${data.prenom_cin} ${data.nom_cin})</div>`;
            document.getElementById('submitBtn').disabled = true;
        } else {
            resultDiv.innerHTML = '';
            document.getElementById('submitBtn').disabled = false;
        }
    });
}

function previewPhoto(event) {
    const reader = new FileReader();
    reader.onload = function(){
        document.getElementById('photoPreview').src = reader.result;
        if(document.getElementById('currentPhoto')) document.getElementById('currentPhoto').src = reader.result;
    };
    reader.readAsDataURL(event.target.files[0]);
}

function checkRole() {
    document.getElementById('specDiv').style.display = (document.getElementById('role').value === 'medecin') ? 'block' : 'none';
}
</script>
</body>
</html>