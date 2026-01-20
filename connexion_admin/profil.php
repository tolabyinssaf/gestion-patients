<?php
session_start();
include("../config/connexion.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { 
    header("Location: ../login.php"); exit; 
}

$id_user = $_SESSION['user_id'];
$update_status = "";

// Traitement de la modification
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['sauvegarder'])) {
    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $email = $_POST['email'];
    
    $stmt_old = $pdo->prepare("SELECT photo FROM utilisateurs WHERE id_user = ?");
    $stmt_old->execute([$id_user]);
    $old_photo = $stmt_old->fetchColumn();
    $photo_name = $old_photo;

    if (isset($_FILES['nouvelle_photo']) && $_FILES['nouvelle_photo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = "../uploads/photos/";
        $fileExtension = pathinfo($_FILES['nouvelle_photo']['name'], PATHINFO_EXTENSION);
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array(strtolower($fileExtension), $allowedExtensions)) {
            if ($old_photo && file_exists($uploadDir . $old_photo)) {
                unlink($uploadDir . $old_photo);
            }
            $photo_name = "admin_" . $id_user . "_" . time() . "." . $fileExtension;
            move_uploaded_file($_FILES['nouvelle_photo']['tmp_name'], $uploadDir . $photo_name);
        }
    }

    $update = $pdo->prepare("UPDATE utilisateurs SET nom = ?, prenom = ?, email = ?, photo = ? WHERE id_user = ?");
    if ($update->execute([$nom, $prenom, $email, $photo_name, $id_user])) {
        $update_status = "success";
    } else {
        $update_status = "error";
    }
}

$stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id_user = ?");
$stmt->execute([$id_user]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

$photoPath = "../uploads/photos/" . $admin['photo'];
$final_photo = (!empty($admin['photo']) && file_exists($photoPath)) ? $photoPath : "https://ui-avatars.com/api/?name=" . urlencode($admin['prenom'] . ' ' . $admin['nom']) . "&background=0f766e&color=fff&size=128";
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil | Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        :root { --primary: #0f766e; --primary-dark: #134e4a; --primary-soft: #f0fdf4; --sidebar-bg: #0f172a; --bg-body: #f8fafc; --border: #e2e8f0; --text-main: #1e293b; --text-muted: #64748b; }
        
        body { background: var(--bg-body); font-family: 'Plus Jakarta Sans', sans-serif; display: flex; min-height: 100vh; margin: 0; color: var(--text-main); }
        header { background: #ffffff; padding: 0 40px; height: 75px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); position: fixed; width: 100%; top: 0; z-index: 1050; }
        
        .sidebar { width: 260px; background: var(--sidebar-bg); height: 100vh; position: fixed; top: 0; padding: 100px 16px 24px; z-index: 1000; }
        .sidebar a { display: flex; align-items: center; gap: 12px; color: #94a3b8; text-decoration: none; padding: 12px 16px; border-radius: 12px; margin-bottom: 5px; transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .sidebar a:hover, .sidebar a.active { background: var(--primary); color: #ffffff; transform: translateX(5px); }
        
        .main-wrapper { flex: 1; margin-left: 260px; padding-top: 75px; transition: 0.3s; }
        .content-container { padding: 40px; max-width: 1000px; margin: 0 auto; }

        /* Profil Card Styling */
        .profile-card { background: white; border-radius: 24px; border: 1px solid var(--border); box-shadow: 0 20px 40px rgba(0,0,0,0.03); overflow: hidden; position: relative; }
        .profile-banner { height: 160px; background: linear-gradient(135deg, #0f766e 0%, #14b8a6 100%); position: relative; }
        .profile-banner::after { content: ""; position: absolute; bottom: 0; left: 0; right: 0; height: 50%; background: linear-gradient(to top, rgba(0,0,0,0.1), transparent); }
        
        .profile-body { padding: 0 50px 50px; margin-top: -60px; position: relative; }
        
        /* Avatar & Upload */
        .avatar-container { position: relative; width: 140px; height: 140px; transition: 0.3s; }
        .avatar-img { width: 140px; height: 140px; border-radius: 40px; border: 6px solid white; box-shadow: 0 10px 25px rgba(0,0,0,0.1); object-fit: cover; background: white; }
        
        .photo-edit-btn { 
            position: absolute; bottom: 5px; right: 5px; 
            background: var(--primary); color: white; width: 40px; height: 40px; 
            border-radius: 12px; display: flex; align-items: center; justify-content: center; 
            cursor: pointer; border: 4px solid white; box-shadow: 0 4px 10px rgba(0,0,0,0.15); transition: 0.3s;
        }
        .photo-edit-btn:hover { background: var(--primary-dark); transform: rotate(15deg) scale(1.1); }

        /* Information Boxes */
        .info-box { padding: 24px; background: #ffffff; border-radius: 20px; border: 1px solid #f1f5f9; transition: 0.3s; height: 100%; display: flex; flex-direction: column; justify-content: center; }
        .info-box:hover { border-color: var(--primary); box-shadow: 0 10px 20px rgba(15, 118, 110, 0.05); }
        
        .info-label { font-size: 0.75rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1.2px; margin-bottom: 8px; }
        .info-value { font-size: 1.1rem; font-weight: 600; color: var(--text-main); }
        
        .edit-input { width: 100%; border: 2px solid #e2e8f0; border-radius: 12px; padding: 10px 15px; font-weight: 600; transition: 0.3s; outline: none; }
        .edit-input:focus { border-color: var(--primary); background: var(--primary-soft); }

        /* Badges & Text */
        .matricule-badge { background: #1e293b; color: #fff; padding: 6px 14px; border-radius: 10px; font-weight: 700; font-size: 0.85rem; letter-spacing: 0.5px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .status-tag { font-size: 0.8rem; font-weight: 700; background: #dcfce7; color: #166534; padding: 6px 16px; border-radius: 10px; display: inline-flex; align-items: center; gap: 6px; }

        /* Buttons */
        .btn-edit, .btn-save { background: #1e293b; color: white; padding: 12px 28px; border-radius: 14px; font-weight: 700; transition: 0.3s; border: none; box-shadow: 0 4px 12px rgba(30, 41, 59, 0.2); }
        .btn-edit:hover, .btn-save:hover { background: var(--primary); transform: translateY(-2px); }
        .btn-cancel { background: #f1f5f9; color: #64748b; padding: 12px 28px; border-radius: 14px; font-weight: 700; border: none; margin-right: 12px; }

        .hidden { display: none !important; }
        .locked-box { background: #f8fafc !important; border: 1px dashed #cbd5e1 !important; }
    </style>
</head>
<body>

<header>
    <img src="../images/logo_app2.png" alt="Logo" style="height: 35px;">
    <div style="background: var(--primary-soft); color: var(--primary); padding: 10px 20px; border-radius: 15px; font-weight: 800; font-size: 0.9rem;">
        <i class="fa-solid fa-crown me-2"></i>ESPACE ADMINISTRATEUR
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
        <a href="profil.php" class="<?= basename($_SERVER['PHP_SELF']) == 'profil.php' ? 'active' : '' ?>" class="active">
        <i class="fa-solid fa-user-gear"></i> Mon Profil
        </a>
        <a href="../connexio_utilisateur/login.php" style="color: #fda4af; margin-top: 20px;"><i class="fa-solid fa-power-off"></i> Déconnexion</a>
    </aside>

<div class="main-wrapper">
    <main class="content-container">
        
        <form method="POST" id="profileForm" enctype="multipart/form-data">
            <div class="profile-card">
                <div class="profile-banner"></div>
                
                <div class="profile-body">
                    <div class="d-flex align-items-end justify-content-between mb-4">
                        <div class="avatar-container">
                            <img src="<?= $final_photo ?>" alt="Avatar" class="avatar-img" id="previewImg">
                            <label for="photoInput" class="photo-edit-btn hidden" id="photoBtn">
                                <i class="fa-solid fa-camera"></i>
                            </label>
                            <input type="file" name="nouvelle_photo" id="photoInput" class="hidden" accept="image/*" onchange="previewFile()">
                        </div>
                        
                        <div id="view-actions">
                            <button type="button" class="btn-edit" onclick="enableEdit()">
                                <i class="fa-solid fa-pen-nib me-2"></i>modifier
                            </button>
                        </div>
                        <div id="edit-actions" class="hidden">
                            <button type="button" class="btn-cancel" onclick="disableEdit()">Annuler</button>
                            <button type="submit" name="sauvegarder" class="btn-save">
                                <i class="fa-solid fa-check-circle me-2"></i>Enregistrer
                            </button>
                        </div>
                    </div>

                    <div class="row align-items-center mb-5">
                        <div class="col-lg-7">
                            <h1 class="display-6 fw-800 mb-2"><?= $admin['prenom'] ?> <?= $admin['nom'] ?></h1>
                            <div class="d-flex flex-wrap gap-3 align-items-center">
                                <span class="status-tag"><i class="fa-solid fa-shield-check"></i> Administrateur</span>
                                <span class="matricule-badge"><i class="fa-solid fa-id-badge me-2 text-info"></i>Matricule: <?= $admin['matricule'] ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="row g-4">
                        <div class="col-md-4">
                            <div class="info-box">
                                <div class="info-label">Prénom</div>
                                <div class="info-value txt-view"><?= $admin['prenom'] ?></div>
                                <input type="text" name="prenom" class="edit-input hidden" value="<?= $admin['prenom'] ?>" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-box">
                                <div class="info-label">Nom de famille</div>
                                <div class="info-value txt-view"><?= $admin['nom'] ?></div>
                                <input type="text" name="nom" class="edit-input hidden" value="<?= $admin['nom'] ?>" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-box">
                                <div class="info-label">Adresse Email</div>
                                <div class="info-value txt-view"><?= $admin['email'] ?></div>
                                <input type="email" name="email" class="edit-input hidden" value="<?= $admin['email'] ?>" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-box locked-box">
                                <div class="info-label">Numéro CIN</div>
                                <div class="info-value"><?= $admin['cin'] ?></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-box locked-box">
                                <div class="info-label">Téléphone</div>
                                <div class="info-value"><?= $admin['telephone'] ?></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-box locked-box">
                                <div class="info-label">Date d'inscription</div>
                                <div class="info-value"><?= date('d/m/Y', strtotime($admin['date_inscription'] ?? 'now')) ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 p-4 rounded-4 bg-light border d-flex align-items-start gap-3">
                        <div class="text-primary fs-4 mt-1"><i class="fa-solid fa-circle-info"></i></div>
                        <div>
                            <p class="small text-secondary m-0">
                                <strong>Sécurité du compte :</strong> Certaines informations sensibles (Matricule, CIN) sont verrouillées pour garantir l'intégrité du système. Pour toute modification, veuillez contacter le support technique.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </main>
</div>

<script>
    function enableEdit() {
        document.querySelectorAll('.txt-view').forEach(el => el.classList.add('hidden'));
        document.querySelectorAll('.edit-input').forEach(el => el.classList.remove('hidden'));
        document.getElementById('view-actions').classList.add('hidden');
        document.getElementById('edit-actions').classList.remove('hidden');
        document.getElementById('photoBtn').classList.remove('hidden');
    }

    function disableEdit() {
        document.querySelectorAll('.txt-view').forEach(el => el.classList.remove('hidden'));
        document.querySelectorAll('.edit-input').forEach(el => el.classList.add('hidden'));
        document.getElementById('view-actions').classList.remove('hidden');
        document.getElementById('edit-actions').classList.add('hidden');
        document.getElementById('photoBtn').classList.add('hidden');
    }

    function previewFile() {
        const preview = document.getElementById('previewImg');
        const file = document.getElementById('photoInput').files[0];
        const reader = new FileReader();
        reader.onloadend = function() { preview.src = reader.result; }
        if (file) { reader.readAsDataURL(file); }
    }

    <?php if ($update_status === "success"): ?>
        Swal.fire({
            title: 'Profil mis à jour !',
            text: 'Vos informations ont été enregistrées avec succès.',
            icon: 'success',
            confirmButtonColor: '#0f766e',
            customClass: { popup: 'rounded-4' }
        });
    <?php elseif ($update_status === "error"): ?>
        Swal.fire({
            title: 'Erreur',
            text: 'Une erreur est survenue lors de la mise à jour.',
            icon: 'error'
        });
    <?php endif; ?>
</script>

</body>
</html>