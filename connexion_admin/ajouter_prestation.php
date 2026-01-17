<?php
session_start();
include("../config/connexion.php");

// Vérification Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { 
    header("Location: ../login.php"); exit; 
}

$user_id = $_SESSION['user_id'];
// Récupérer les infos de l'admin pour le header
$stmt_admin = $pdo->prepare("SELECT nom, prenom FROM utilisateurs WHERE id_user = ?");
$stmt_admin->execute([$user_id]);
$admin = $stmt_admin->fetch(PDO::FETCH_ASSOC);

if (isset($_POST['ajouter'])) {
    $nom = $_POST['nom_prestation'];
    $tarif = $_POST['tarif']; // Correspond au prix_unitaire
    $categorie = $_POST['categorie'];

    try {
        // Correction de la requête SQL avec vos colonnes exactes
        // Note : id_prestation est généralement en AUTO_INCREMENT donc on ne l'insère pas manuellement
        $stmt = $pdo->prepare("INSERT INTO prestations (nom_prestation, categorie, prix_unitaire) VALUES (?, ?, ?)");
        $stmt->execute([$nom, $categorie, $tarif]);
        
        header("Location: prestations.php?msg=added");
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
    <title>Nouvel Acte | MedCare Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary: #0f766e;
            --sidebar-bg: #0f172a;
            --bg-body: #f1f5f9;
            --border-color: #e2e8f0;
        }

        body { background: var(--bg-body); font-family: 'Plus Jakarta Sans', sans-serif; margin: 0; display: flex; min-height: 100vh; }

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
        .content-container { padding: 50px 20px; max-width: 650px; margin: 0 auto; }

        .form-card {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            overflow: hidden;
        }

        .form-header-banner {
            background: var(--primary);
            padding: 25px 35px;
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .form-header-banner i {
            background: rgba(255, 255, 255, 0.2);
            width: 50px; height: 50px;
            display: flex; align-items: center; justify-content: center;
            border-radius: 12px; font-size: 1.4rem; color: white;
        }

        .form-header-banner h2 { font-size: 1.25rem; font-weight: 700; color: white; margin: 0; }
        .form-header-banner p { font-size: 0.85rem; color: rgba(255,255,255,0.8); margin: 0; }

        .form-body { padding: 35px; }
        .input-group-custom { margin-bottom: 20px; }
        .input-group-custom label {
            display: block; font-size: 0.82rem; font-weight: 700;
            color: #475569; margin-bottom: 8px; text-transform: uppercase;
        }

        .input-wrapper { position: relative; }
        .input-wrapper i {
            position: absolute; left: 16px; top: 50%;
            transform: translateY(-50%); color: #94a3b8; font-size: 1rem;
        }

        .input-wrapper input, .input-wrapper select {
            width: 100%; padding: 12px 15px 12px 45px;
            background: #f8fafc; border: 1px solid var(--border-color);
            border-radius: 10px; font-size: 0.95rem; transition: 0.3s;
        }

        .input-wrapper input:focus, .input-wrapper select:focus {
            outline: none; border-color: var(--primary); background: white;
            box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.1);
        }

        .btn-submit {
            background: var(--primary); color: white; border: none;
            padding: 14px; border-radius: 10px; font-weight: 700; width: 100%; transition: 0.3s;
        }
        .btn-submit:hover { background: #0d5a55; }
        .btn-back { display: block; text-align: center; margin-top: 15px; color: #64748b; text-decoration: none; font-size: 0.85rem; font-weight: 600; }
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
        <a href="utilisateurs.php"><i class="fa-solid fa-user-md"></i> Utilisateurs</a>
        <a href="prestations.php" class="active"><i class="fa-solid fa-list-check"></i> Actes & Tarifs</a>
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
        <?php if(isset($form_message)): ?>
            <div class="alert alert-danger mb-4 rounded-4"><?= $form_message ?></div>
        <?php endif; ?>

        <div class="form-card">
            <div class="form-header-banner">
                <i class="fa-solid fa-file-invoice-dollar"></i>
                <div>
                    <h2>Nouvelle Prestation</h2>
                    <p>Configuration des tarifs et catégories</p>
                </div>
            </div>

            <form method="POST" class="form-body">
                <div class="input-group-custom">
                    <label>Nom de la prestation</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-notes-medical"></i>
                        <input type="text" name="nom_prestation" placeholder="Ex: Consultation Générale" required>
                    </div>
                </div>

                <div class="input-group-custom">
                    <label>Catégorie</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-layer-group"></i>
                        <select name="categorie" required>
                            <option value="" disabled selected>Choisir une catégorie...</option>
                            <option value="Consultation">Consultation</option>
                            <option value="Laboratoire">Laboratoire</option>
                            <option value="Radiologie">Radiologie</option>
                            <option value="Chirurgie">Chirurgie</option>
                            <option value="Soins">Soins</option>
                        </select>
                    </div>
                </div>

                <div class="input-group-custom">
                    <label>Prix Unitaire (DH)</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-money-bill-wave"></i>
                        <input type="number" step="0.01" name="tarif" placeholder="0.00" required>
                    </div>
                </div>

                <button type="submit" name="ajouter" class="btn-submit">
                    <i class="fa-solid fa-plus-circle me-2"></i> Enregistrer l'acte
                </button>

                <a href="prestations.php" class="btn-back">
                    <i class="fa-solid fa-arrow-left me-1"></i> Annuler et retourner
                </a>
            </form>
        </div>
    </main>
</div>

</body>
</html>