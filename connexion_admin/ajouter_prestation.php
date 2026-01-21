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

$form_message = "";

if (isset($_POST['ajouter'])) {
    $nom = $_POST['nom_prestation'];
    $tarif = $_POST['tarif']; 
    $categorie = $_POST['categorie'];

    try {
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary: #0f766e; 
            --primary-hover: #0d9488;
            --sidebar-bg: #0f172a; 
            --input-dark: #1e293b; 
            --bg-body: #f1f5f9;
            --white: #ffffff;
            --header-height: 75px;
            --sidebar-width: 260px;
        }

        * { margin:0; padding:0; box-sizing:border-box; font-family: 'Inter', sans-serif; }
        body { background: var(--bg-body); color: #1e293b; }

        /* Header & Sidebar (Conservés selon votre menu) */
        header { background: var(--white); padding: 0 40px; height: var(--header-height); display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; position: fixed; top: 0; left: 0; right: 0; z-index: 1000; }
        .sidebar { width: var(--sidebar-width); background: var(--sidebar-bg); padding: 100px 16px 24px; position: fixed; top: 0; left: 0; bottom: 0; z-index: 999; }
        .sidebar a { display: flex; align-items: center; gap: 12px; color: #94a3b8; text-decoration: none; padding: 12px 16px; border-radius: 10px; margin-bottom: 5px; transition: 0.3s; }
        .sidebar a:hover { background: rgba(255,255,255,0.05); color: #fff; }
        .sidebar a.active { background: var(--primary); color: #fff; }
        .user-pill { background: #f0fdfa; padding: 8px 18px; border-radius: 12px; display: flex; align-items: center; gap: 10px; font-size: 14px; font-weight: 700; color: var(--primary); }
        
        .content { margin-left: var(--sidebar-width); padding: 40px; margin-top: var(--header-height); }

        /* Style du Formulaire (Inspiré de la page Patient) */
        .form-card {
            background: var(--white);
            border-radius: 24px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 15px 35px -5px rgba(0, 0, 0, 0.07);
            max-width: 800px;
            margin: 0 auto;
            overflow: hidden;
        }

        .form-header-profile {
            padding: 40px;
            background: var(--primary);
            display: flex; align-items: center; gap: 25px;
            color: white;
            border-bottom: 4px solid rgba(0,0,0,0.1);
        }

        .avatar-huge {
            width: 80px; height: 80px; 
            background: rgba(255,255,255,0.15); 
            border-radius: 20px; display: flex; align-items: center; justify-content: center;
            font-size: 35px; border: 2px solid rgba(255,255,255,0.4);
        }

        .header-text-small { font-size: 13px; text-transform: uppercase; letter-spacing: 1.5px; font-weight: 600; opacity: 0.85; margin-bottom: 4px; }
        .header-text-main { font-size: 24px; font-weight: 800; margin-bottom: 2px; letter-spacing: -0.5px; }

        .form-body { padding: 40px; }

        .section-separator {
            display: flex; align-items: center; margin: 10px 0 30px 0;
            color: var(--primary); font-weight: 700; font-size: 13px;
            text-transform: uppercase; letter-spacing: 1px;
        }
        .section-separator i { margin-right: 12px; }
        .section-separator::after { content: ""; flex: 1; height: 1px; background: #e2e8f0; margin-left: 15px; }

        .form-label { font-weight: 600; font-size: 13px; color: #64748b; margin-bottom: 8px; display: block; }
        .input-group-custom { position: relative; margin-bottom: 25px; }
        .input-group-custom i { position: absolute; left: 18px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 15px; z-index: 10; }

        .form-control-modern {
            width: 100%;
            padding: 13px 15px 13px 50px;
            background: var(--input-dark);
            border: 1px solid var(--input-dark);
            border-radius: 12px;
            color: #ffffff;
            font-size: 14px;
            transition: 0.3s;
        }
        .form-control-modern:focus { outline: none; background: #334155; box-shadow: 0 0 0 4px rgba(15, 118, 110, 0.2); }
        
        /* Ajustement pour le select (retrait de l'icône qui chevauche parfois) */
        .form-select-modern {
            width: 100%;
            padding: 13px 15px 13px 50px;
            background: var(--input-dark);
            border: 1px solid var(--input-dark);
            border-radius: 12px;
            color: #ffffff;
            font-size: 14px;
            cursor: pointer;
        }

        .btn-update {
            background: var(--primary); color: white; padding: 18px 35px;
            border-radius: 14px; font-weight: 700; border: none; transition: 0.3s;
            display: flex; align-items: center; gap: 12px; width: 100%; justify-content: center;
            text-transform: uppercase; letter-spacing: 1px; font-size: 15px;
        }
        .btn-update:hover { background: var(--primary-hover); transform: translateY(-2px); box-shadow: 0 10px 20px rgba(15, 118, 110, 0.3); }
    </style>
</head>
<body>

<header>
    <img src="../images/logo_app2.png" alt="Logo" style="height: 40px;">
    <div class="user-pill">
        <i class="fa-solid fa-user-shield"></i>
        <span>ADMIN : <?= strtoupper($admin['nom']) ?></span>
    </div>
</header>

<aside class="sidebar">
    <a href="dashboard_admin.php"><i class="fa-solid fa-chart-pie"></i> Vue Générale</a>
    <a href="utilisateurs.php"><i class="fa-solid fa-user-md"></i> Utilisateurs</a>
    <a href="prestations.php" class="active"><i class="fa-solid fa-list-check"></i> Actes & Tarifs</a>
    <a href="chambres.php"><i class="fa-solid fa-bed"></i> Gestion Chambres</a>
    <div style="height: 1px; background: rgba(255,255,255,0.1); margin: 20px 0;"></div>
    <a href="facturation_list.php"><i class="fa-solid fa-file-invoice-dollar"></i> Rapports Financiers</a>
    <a href="archives.php"><i class="fa-solid fa-box-archive"></i> Archives</a>
    <a href="profil.php"><i class="fa-solid fa-user-gear"></i> Mon Profil</a>
    <a href="../connexio_utilisateur/login.php" style="color: #fda4af; margin-top: 20px;"><i class="fa-solid fa-power-off"></i> Déconnexion</a>
</aside>

<div class="wrapper">
    <main class="content">
        <div class="form-card">
            <div class="form-header-profile">
                <div class="avatar-huge">
                    <i class="fa-solid fa-hand-holding-medical"></i>
                </div>
                <div>
                    <div class="header-text-small">Administration des tarifs</div>
                    <div class="header-text-main">Nouvelle Prestation</div>
                    <div style="font-size: 13px; opacity: 0.8;">Ajouter un nouvel acte médical au catalogue</div>
                </div>
            </div>

            <?php if($form_message): ?>
                <div class="alert alert-danger mx-4 mt-4 mb-0 border-0 shadow-sm"><?= $form_message ?></div>
            <?php endif; ?>

            <form action="" method="POST" class="form-body">
                <div class="section-separator"><i class="fa-solid fa-gears"></i> Paramètres de l'acte</div>

                <div class="row">
                    <div class="col-12">
                        <label class="form-label">Désignation de la prestation</label>
                        <div class="input-group-custom">
                            <i class="fa-solid fa- signature"></i>
                            <input type="text" name="nom_prestation" class="form-control-modern" placeholder="Ex: Consultation Spécialisée" required>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Catégorie d'acte</label>
                        <div class="input-group-custom">
                            <i class="fa-solid fa-layer-group"></i>
                            <select name="categorie" class="form-select-modern" required>
                                <option value="" disabled selected>Choisir...</option>
                                <option value="Consultation">Consultation</option>
                                <option value="Laboratoire">Laboratoire</option>
                                <option value="Radiologie">Radiologie</option>
                                <option value="Chirurgie">Chirurgie</option>
                                <option value="Soins">Soins</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Tarif Unitaire (DH)</label>
                        <div class="input-group-custom">
                            <i class="fa-solid fa-money-bill-wave"></i>
                            <input type="number" step="0.01" name="tarif" class="form-control-modern" placeholder="0.00" required>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" name="ajouter" class="btn-update">
                        <i class="fa-solid fa-plus-circle"></i> Enregistrer la prestation
                    </button>
                    
                    <div class="text-center mt-3">
                        <a href="prestations.php" class="text-muted small text-decoration-none">
                            <i class="fa-solid fa-arrow-left me-1"></i> Retourner à la liste
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </main>
</div>

</body>
</html>