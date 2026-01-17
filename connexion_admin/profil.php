<?php
session_start();
include("../config/connexion.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { 
    header("Location: ../login.php"); exit; 
}

$id_user = $_SESSION['user_id'];
$update_status = "";

// Traitement de la modification dans la même page
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['sauvegarder'])) {
    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $email = $_POST['email'];

    $update = $pdo->prepare("UPDATE utilisateurs SET nom = ?, prenom = ?, email = ? WHERE id_user = ?");
    if ($update->execute([$nom, $prenom, $email, $id_user])) {
        $update_status = "success";
    } else {
        $update_status = "error";
    }
}

// Récupération des données fraîches
$stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id_user = ?");
$stmt->execute([$id_user]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

$avatar_url = "https://ui-avatars.com/api/?name=" . urlencode($admin['prenom'] . ' ' . $admin['nom']) . "&background=0f766e&color=fff&size=128";
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
        :root { 
            --primary: #0f766e; 
            --primary-soft: #f0fdf4;
            --sidebar-bg: #0f172a;
            --bg-body: #f8fafc;
            --border: #e2e8f0;
        }
        
        body { background: var(--bg-body); font-family: 'Plus Jakarta Sans', sans-serif; display: flex; min-height: 100vh; margin: 0; }

        header { 
            background: #ffffff; padding: 0 40px; height: 75px; 
            display: flex; justify-content: space-between; align-items: center; 
            border-bottom: 1px solid var(--border); position: fixed; width: 100%; top: 0; z-index: 1050;
        }

        .sidebar { 
            width: 260px; background: var(--sidebar-bg); height: 100vh; 
            position: fixed; top: 0; padding: 100px 16px 24px; z-index: 1000;
        }
        .sidebar a { 
            display: flex; align-items: center; gap: 12px; color: #94a3b8; 
            text-decoration: none; padding: 12px 16px; border-radius: 10px; margin-bottom: 5px; transition: 0.3s; 
        }
        .sidebar a:hover, .sidebar a.active { background: var(--primary); color: #ffffff; }

        .main-wrapper { flex: 1; margin-left: 260px; padding-top: 75px; }
        .content-container { padding: 40px; max-width: 900px; margin: 0 auto; }

        .profile-card {
            background: white; border-radius: 20px; border: 1px solid var(--border);
            box-shadow: 0 10px 30px rgba(0,0,0,0.02); overflow: hidden;
        }

        .profile-banner {
            height: 120px; background: linear-gradient(135deg, var(--primary), #14b8a6);
        }

        .profile-body { padding: 0 40px 40px; margin-top: -60px; }

        .avatar-img {
            width: 120px; height: 120px; border-radius: 30px; 
            border: 5px solid white; box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            object-fit: cover; background: #fff;
        }

        .info-box {
            padding: 20px; background: #fcfdfe; border-radius: 15px;
            border: 1px solid #f1f5f9; height: 100%;
        }

        .info-label {
            font-size: 0.7rem; font-weight: 800; color: #94a3b8;
            text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px;
        }

        .info-value { font-size: 1rem; font-weight: 600; color: #1e293b; }
        
        /* Inputs d'édition */
        .edit-input {
            width: 100%; border: 1px solid var(--primary); border-radius: 8px;
            padding: 5px 10px; font-weight: 600; color: var(--primary);
            outline: none; background: white;
        }

        .btn-edit, .btn-save {
            background: #1e293b; color: white; padding: 10px 20px; border-radius: 10px;
            text-decoration: none; font-weight: 700; font-size: 0.85rem; transition: 0.3s; border: none;
        }
        .btn-edit:hover, .btn-save:hover { background: var(--primary); color: white; }
        
        .btn-cancel {
            background: #f1f5f9; color: #64748b; padding: 10px 20px; border-radius: 10px;
            text-decoration: none; font-weight: 700; font-size: 0.85rem; border: none; margin-right: 10px;
        }

        .status-tag {
            font-size: 0.75rem; font-weight: 700; background: #dcfce7;
            color: #166534; padding: 4px 12px; border-radius: 8px;
        }

        .hidden { display: none !important; }
    </style>
</head>
<body>

<header>
    <img src="../images/logo_app2.png" alt="Logo" style="height: 35px;">
    <div style="background: var(--primary-soft); color: var(--primary); padding: 8px 16px; border-radius: 12px; font-weight: 700;">
        <i class="fa-solid fa-user-shield me-2"></i>ADMIN : <?= strtoupper($admin['nom']) ?>
    </div>
</header>

<aside class="sidebar">
    <a href="dashboard_admin.php"><i class="fa-solid fa-chart-pie"></i> Vue Générale</a>
    <a href="utilisateurs.php"><i class="fa-solid fa-user-md"></i> Utilisateurs</a>
    <a href="chambres.php"><i class="fa-solid fa-bed"></i> Gestion Chambres</a>
    <a href="profil.php" class="active"><i class="fa-solid fa-user-gear"></i> Mon Profil</a>
    <hr style="border-color: rgba(255,255,255,0.1)">
    <a href="../logout.php" style="color: #fda4af;"><i class="fa-solid fa-power-off"></i> Déconnexion</a>
</aside>

<div class="main-wrapper">
    <main class="content-container">
        
        <form method="POST" id="profileForm">
            <div class="profile-card">
                <div class="profile-banner"></div>
                
                <div class="profile-body">
                    <div class="d-flex align-items-end justify-content-between mb-4">
                        <img src="<?= $avatar_url ?>" alt="Avatar" class="avatar-img">
                        
                        <div id="view-actions">
                            <button type="button" class="btn-edit" onclick="enableEdit()">
                                <i class="fa-solid fa-user-pen me-2"></i>Modifier le profil
                            </button>
                        </div>
                        <div id="edit-actions" class="hidden">
                            <button type="button" class="btn-cancel" onclick="disableEdit()">Annuler</button>
                            <button type="submit" name="sauvegarder" class="btn-save">
                                <i class="fa-solid fa-floppy-disk me-2"></i>Sauvegarder
                            </button>
                        </div>
                    </div>

                    <div class="mb-5">
                        <div class="d-flex align-items-center gap-3">
                            <h2 class="fw-800 text-dark m-0"><?= $admin['prenom'] ?> <?= $admin['nom'] ?></h2>
                            <span class="status-tag">Compte Administrateur</span>
                        </div>
                        <p class="text-secondary mt-1">Gérez vos accès et vos informations personnelles.</p>
                    </div>

                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="info-box">
                                <div class="info-label">Prénom</div>
                                <div class="info-value txt-view"><?= $admin['prenom'] ?></div>
                                <input type="text" name="prenom" class="edit-input hidden" value="<?= $admin['prenom'] ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-box">
                                <div class="info-label">Nom de famille</div>
                                <div class="info-value txt-view"><?= $admin['nom'] ?></div>
                                <input type="text" name="nom" class="edit-input hidden" value="<?= $admin['nom'] ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-box">
                                <div class="info-label">Adresse Email</div>
                                <div class="info-value txt-view"><?= $admin['email'] ?></div>
                                <input type="email" name="email" class="edit-input hidden" value="<?= $admin['email'] ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-box" style="background: #f8fafc; opacity: 0.7;">
                                <div class="info-label">Rôle Système</div>
                                <div class="info-value">Administrateur Principal</div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 p-3 rounded-4 bg-light border d-flex align-items-center gap-3">
                        <div class="text-primary fs-4 ps-2"><i class="fa-solid fa-shield-halved"></i></div>
                        <p class="small text-secondary m-0">
                            <strong>Sécurité :</strong> Vos informations sont protégées. Vos modifications sont prises en compte immédiatement après sauvegarde.
                        </p>
                    </div>
                </div>
            </div>
        </form>

    </main>
</div>

<script>
    // Fonction pour passer en mode édition
    function enableEdit() {
        document.querySelectorAll('.txt-view').forEach(el => el.classList.add('hidden'));
        document.querySelectorAll('.edit-input').forEach(el => el.classList.remove('hidden'));
        document.getElementById('view-actions').classList.add('hidden');
        document.getElementById('edit-actions').classList.remove('hidden');
    }

    // Fonction pour annuler
    function disableEdit() {
        document.querySelectorAll('.txt-view').forEach(el => el.classList.remove('hidden'));
        document.querySelectorAll('.edit-input').forEach(el => el.classList.add('hidden'));
        document.getElementById('view-actions').classList.remove('hidden');
        document.getElementById('edit-actions').classList.add('hidden');
    }

    // Affichage de l'alerte SweetAlert2 centrée après traitement PHP
    <?php if ($update_status === "success"): ?>
        Swal.fire({
            title: 'Succès !',
            text: 'Votre profil a été mis à jour avec élégance.',
            icon: 'success',
            confirmButtonColor: '#0f766e',
            timer: 3000
        });
    <?php elseif ($update_status === "error"): ?>
        Swal.fire({
            title: 'Erreur',
            text: 'Impossible de mettre à jour les données.',
            icon: 'error',
            confirmButtonColor: '#d33'
        });
    <?php endif; ?>
</script>

</body>
</html>