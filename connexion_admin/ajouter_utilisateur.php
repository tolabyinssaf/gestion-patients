<?php
// ... (Logique PHP identique conservée)
session_start();
include("../config/connexion.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { 
    header("Location: ../login.php"); exit; 
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT nom, prenom FROM utilisateurs WHERE id_user = ?");
$stmt->execute([$user_id]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

$form_message = "";

if (isset($_POST['ajouter'])) {
    $nom        = $_POST['nom'];
    $prenom     = $_POST['prenom'];
    $email      = $_POST['email'];
    $password   = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role       = $_POST['role'];
    $telephone  = $_POST['telephone'];
    $cin        = strtoupper($_POST['cin']);
    $specialite = ($role === 'medecin' && !empty($_POST['specialite'])) ? $_POST['specialite'] : '';

    try {
        $stmt = $pdo->prepare("CALL sp_AjouterUtilisateur(?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nom, $prenom, $email, $password, $role, $specialite, $telephone, $cin]);
        header("Location: utilisateurs.php?success=added");
        exit;
    } catch (PDOException $e) {
        $form_message = (strpos($e->getMessage(), '45000') !== false) ? 
            preg_replace('/SQLSTATE\[45000\]:.*: \d+ /', '', $e->getMessage()) : "Erreur : " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recrutement | MedicalServices</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary: #0f766e;
            --sidebar-bg: #0f172a;
            --bg-body: #f1f5f9;
            --border-color: #e2e8f0;
            /* Nouveau gris pour les inputs */
            --input-bg: #f1f5f9; 
            --input-border: #cbd5e1;
        }

        body { 
            background: var(--bg-body); 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            margin: 0; display: flex; min-height: 100vh; 
        }

        /* --- HEADER & SIDEBAR --- */
        header { 
            background: #ffffff; padding: 0 40px; height: 75px; 
            display: flex; justify-content: space-between; align-items: center; 
            border-bottom: 1px solid var(--border-color); position: fixed; width: 100%; top: 0; z-index: 1001;
        }
        .user-pill { color: var(--primary); font-weight: 700; font-size: 0.9rem; }

        .sidebar { 
            width: 260px; background: var(--sidebar-bg); height: 100vh; 
            position: fixed; top: 0; padding: 100px 16px 24px 16px; z-index: 1000;
        }
        .sidebar a { 
            display: flex; align-items: center; gap: 12px; color: #94a3b8; 
            text-decoration: none; padding: 12px 16px; border-radius: 8px; margin-bottom: 4px; transition: 0.2s; 
        }
        .sidebar a:hover, .sidebar a.active { background: var(--primary); color: #ffffff; }

        /* --- FORMULAIRE --- */
        .main-wrapper { flex: 1; margin-left: 260px; padding-top: 75px; }
        .content-container { padding: 40px; max-width: 1000px; margin: 0 auto; }

        .form-card {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .form-header {
            padding: 24px 32px;
            border-bottom: 1px solid var(--border-color);
            background: #fafafa;
        }

        .form-header h2 { font-size: 1.25rem; font-weight: 700; margin: 0; color: #0f172a; }
        .form-header p { font-size: 0.875rem; color: #64748b; margin: 4px 0 0 0; }

        .form-body { padding: 32px; }

        .form-section-title {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 20px;
            display: block;
        }

        .row-gap { margin-bottom: 24px; }

        label { 
            display: block; 
            font-size: 0.875rem; 
            font-weight: 600; 
            color: #334155; 
            margin-bottom: 8px; 
        }

        /* INPUTS GRISÉS */
        input, select {
            width: 100%;
            padding: 12px 16px;
            background-color: var(--input-bg); /* Gris un peu foncé */
            border: 1.5px solid var(--input-border);
            border-radius: 10px;
            font-size: 0.95rem;
            color: #1e293b;
            transition: all 0.2s ease-in-out;
        }

        input:focus, select:focus {
            outline: none;
            background-color: #ffffff; /* Devient blanc au focus pour la clarté */
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(15, 118, 110, 0.1);
        }

        input::placeholder {
            color: #94a3b8;
        }

        .btn-save {
            background: var(--primary);
            color: white;
            border: none;
            padding: 14px 28px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 1rem;
            width: 100%;
            transition: 0.3s;
            margin-top: 10px;
            box-shadow: 0 4px 6px -1px rgba(15, 118, 110, 0.2);
        }

        .btn-save:hover {
            background: #115e59;
            transform: translateY(-1px);
            box-shadow: 0 10px 15px -3px rgba(15, 118, 110, 0.3);
        }

        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecdd3;
            color: #991b1b;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>

<header>
    <img src="../images/logo_app2.png" alt="Logo" style="height: 40px;">
    <div class="user-pill">
        <i class="fa-solid fa-circle-user me-2"></i>
        ADMIN : <?= strtoupper($admin['nom']) ?>
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
        
        <?php if($form_message): ?>
            <div class="alert-error">
                <i class="fas fa-circle-exclamation me-2"></i> <?= $form_message ?>
            </div>
        <?php endif; ?>

        <div class="form-card">
            <div class="form-header">
                <h2>Enregistrer un nouveau membre</h2>
                <p>Remplissez les informations pour créer l'accès au système.</p>
            </div>

            <form method="POST" class="form-body">
                
                <span class="form-section-title">Identité & Contact</span>
                <div class="row row-gap">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <label>Nom</label>
                        <input type="text" name="nom" placeholder="ex: El Amrani" required>
                    </div>
                    <div class="col-md-6">
                        <label>Prénom</label>
                        <input type="text" name="prenom" placeholder="ex: Yassine" required>
                    </div>
                </div>

                <div class="row row-gap">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <label>CIN</label>
                        <input type="text" name="cin" placeholder="ex: AB123456" required>
                    </div>
                    <div class="col-md-6">
                        <label>Téléphone</label>
                        <input type="text" name="telephone" placeholder="06 00 00 00 00" required>
                    </div>
                </div>

                <span class="form-section-title">Accès & Rôle</span>
                <div class="row row-gap">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <label>Email Professionnel</label>
                        <input type="email" name="email" placeholder="email@medicalservices.ma" required>
                    </div>
                    <div class="col-md-6">
                        <label>Mot de passe</label>
                        <input type="password" name="password" placeholder="••••••••" required>
                    </div>
                </div>

                <div class="row row-gap">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <label>Fonction / Rôle</label>
                        <select name="role" id="role" onchange="checkRole()">
                            <option value="medecin">Médecin</option>
                            <option value="infirmier">Infirmier(e)</option>
                            <option value="secretaire">Secrétaire</option>
                            <option value="admin">Administrateur</option>
                        </select>
                    </div>
                    <div class="col-md-6" id="specDiv">
                        <label>Spécialité</label>
                        <input type="text" name="specialite" placeholder="ex: Cardiologie">
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-12 text-end">
                        <button type="submit" name="ajouter" class="btn-save">
                            Enregistrer
                        </button>
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
    window.onload = checkRole;
</script>

</body>
</html>