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

if (!isset($_GET['id'])) { header("Location: utilisateurs.php"); exit; }
$id_edit = (int)$_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id_user = ?");
$stmt->execute([$id_edit]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) { header("Location: utilisateurs.php"); exit; }

if (isset($_POST['modifier'])) {
    $nom        = $_POST['nom'];
    $prenom     = $_POST['prenom'];
    $email      = $_POST['email'];
    $role       = $_POST['role'];
    $telephone  = $_POST['telephone'];
    $cin        = strtoupper($_POST['cin']);
    $specialite = ($role === 'medecin' && !empty($_POST['specialite'])) ? $_POST['specialite'] : '';

    try {
        $sql = "UPDATE utilisateurs SET nom=?, prenom=?, email=?, role=?, specialite=?, telephone=?, cin=? WHERE id_user=?";
        $pdo->prepare($sql)->execute([$nom, $prenom, $email, $role, $specialite, $telephone, $cin, $id_edit]);
        header("Location: utilisateurs.php?msg=updated");
        exit;
    } catch (PDOException $e) {
        $form_message = "Erreur : " . $e->getMessage();
    }
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
        :root {
            --primary: #0f766e; /* Votre vert menu */
            --primary-light: #f0fdfa;
            --sidebar-bg: #0f172a;
            --bg-body: #f1f5f9;
            --border-color: #e2e8f0;
            --input-bg: #f8fafc; 
        }

        body { background: var(--bg-body); font-family: 'Plus Jakarta Sans', sans-serif; margin: 0; display: flex; min-height: 100vh; color: #1e293b; }

        header { 
            background: #ffffff; padding: 0 40px; height: 75px; 
            display: flex; justify-content: space-between; align-items: center; 
            border-bottom: 1px solid var(--border-color); position: fixed; width: 100%; top: 0; z-index: 1050;
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

        /* CARTE DE NOM FIXE (STICKY) */
        .profile-sticky-card {
            position: sticky;
            top: 95px; /* Juste en dessous du header (75px) + marge */
            z-index: 900;
            background: #ffffff;
            border-radius: 16px;
            padding: 20px 30px;
            margin-bottom: 25px;
            border: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        
        .profile-info-left { display: flex; align-items: center; gap: 20px; }
        
        .profile-avatar {
            width: 55px; height: 55px;
            background: var(--primary);
            color: #ffffff;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem; font-weight: 700;
        }

        .profile-details h2 { 
            margin: 0; font-size: 1.3rem; font-weight: 800; color: var(--primary); 
            text-transform: capitalize;
        }
        
        .role-indicator { font-size: 0.85rem; color: #64748b; font-weight: 500; }

        .matricule-badge {
            background: var(--primary-light); color: var(--primary);
            padding: 6px 14px; border-radius: 10px; font-size: 0.8rem; font-weight: 700;
            border: 1px solid rgba(15, 118, 110, 0.1);
        }

        /* FORMULAIRE */
        .form-card {
            background: #ffffff; border-radius: 20px;
            border: 1px solid var(--border-color);
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .form-body { padding: 40px; }

        .form-section-title {
            font-size: 0.75rem; font-weight: 700; color: #94a3b8;
            text-transform: uppercase; letter-spacing: 0.1em;
            margin-bottom: 25px; display: flex; align-items: center; gap: 15px;
        }
        .form-section-title::after { content: ""; flex: 1; height: 1px; background: #f1f5f9; }

        label { font-size: 0.85rem; font-weight: 600; color: #334155; margin-bottom: 10px; display: block; }

        input, select {
            width: 100%; padding: 12px 16px;
            background-color: var(--input-bg); border: 1.5px solid var(--border-color);
            border-radius: 10px; font-size: 0.95rem; transition: 0.2s;
        }
        input:focus, select:focus {
            outline: none; background-color: #fff; border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(15, 118, 110, 0.08);
        }

        .btn-update {
            background: var(--primary); color: white; border: none;
            padding: 15px; border-radius: 12px; font-weight: 700;
            width: 100%; transition: 0.3s;
        }
        .btn-update:hover { background: #0d5a55; box-shadow: 0 8px 20px rgba(15, 118, 110, 0.2); }
        
        .btn-cancel {
            background: #fff; color: #64748b; border: 1.5px solid var(--border-color);
            padding: 15px; border-radius: 12px; font-weight: 600; width: 100%; text-decoration: none; display: block; text-align: center;
        }
        .btn-cancel:hover { background: #f8fafc; color: #1e293b; }
    </style>
</head>
<body>

<header>
    <img src="../images/logo_app2.png" alt="Logo" style="height: 40px;">
    <div class="user-pill d-flex align-items-center gap-2" style="color: var(--primary); font-weight: 700;">
        <i class="fa-solid fa-circle-user"></i>
        <span>ADMIN : <?= strtoupper($admin['nom']) ?></span>
    </div>
</header>

 <aside class="sidebar">
        <a href="dashboard_admin.php" ><i class="fa-solid fa-chart-pie"></i> Vue Générale</a>
        <a href="utilisateurs.php" class="active"><i class="fa-solid fa-user-md"></i> Utilisateurs</a>
        <a href="prestations.php" ><i class="fa-solid fa-list-check"></i> Actes & Tarifs</a>
        <a href="chambres.php"><i class="fa-solid fa-bed"></i> Gestion Chambres</a>
        <div style="height: 1px; background: rgba(255,255,255,0.1); margin: 20px 0;"></div>
        <a href="facturation_list.php"><i class="fa-solid fa-file-invoice-dollar"></i> Rapports Financiers</a>
        <a href="profil.php" class="<?= basename($_SERVER['PHP_SELF']) == 'profil.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-user-gear"></i> Mon Profil
        </a>
        <a href="../logout.php" style="color: #fda4af; margin-top: 20px;"><i class="fa-solid fa-power-off"></i> Déconnexion</a>
    </aside>

<div class="main-wrapper">
    <main class="content-container">
        
        <div class="profile-sticky-card">
            <div class="profile-info-left">
                <div class="profile-avatar">
                    <?= strtoupper(substr($user['nom'], 0, 1)) ?>
                </div>
                <div class="profile-details">
                    <h2><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></h2>
                    <span class="role-indicator">Membre du personnel • <?= ucfirst($user['role']) ?></span>
                </div>
            </div>
            <div class="matricule-badge">
                <i class="fa-solid fa-hashtag me-1"></i> <?= $user['matricule'] ?>
            </div>
        </div>

        <div class="form-card">
            <form method="POST" class="form-body">
                
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
                        <input type="text" name="cin" value="<?= htmlspecialchars($user['cin']) ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Contact Téléphonique</label>
                        <input type="text" name="telephone" value="<?= htmlspecialchars($user['telephone']) ?>" required>
                    </div>
                </div>

                <span class="form-section-title">Paramètres de compte</span>
                <div class="row mb-4">
                    <div class="col-md-12 mb-3">
                        <label>Adresse Email</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                    </div>
                </div>

                <div class="row mb-5">
                    <div class="col-md-6 mb-3">
                        <label>Attribuer un Rôle</label>
                        <select name="role" id="role" onchange="checkRole()">
                            <option value="medecin" <?= $user['role'] == 'medecin' ? 'selected' : '' ?>>Médecin</option>
                            <option value="infirmier" <?= $user['role'] == 'infirmier' ? 'selected' : '' ?>>Infirmier(e)</option>
                            <option value="secretaire" <?= $user['role'] == 'secretaire' ? 'selected' : '' ?>>Secrétaire</option>
                            <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : '' ?>>Administrateur</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3" id="specDiv" style="display: <?= $user['role'] == 'medecin' ? 'block' : 'none' ?>;">
                        <label>Spécialité</label>
                        <input type="text" name="specialite" value="<?= htmlspecialchars($user['specialite']) ?>" placeholder="ex: Pédiatrie">
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-8">
                        <button type="submit" name="modifier" class="btn-update">
                            Mettre à jour les données
                        </button>
                    </div>
                    <div class="col-md-4">
                        <a href="utilisateurs.php" class="btn btn-cancel">Annuler</a>
                    </div>
                </div>

            </form>
        </div>
    </main>
</div>

<script>
    function checkRole() {
        const role = document.getElementById('role').value;
        const specDiv = document.getElementById('specDiv');
        specDiv.style.display = (role === 'medecin') ? 'block' : 'none';
    }
</script>

</body>
</html>