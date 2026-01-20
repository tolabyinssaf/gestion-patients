<?php
session_start();
include "../config/connexion.php";

// Sécurité : Vérification Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { 
    header("Location: ../login.php"); 
    exit; 
}

$id = $_GET['id'] ?? null;
$chambre = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM chambres WHERE id_chambre = ?");
    $stmt->execute([$id]);
    $chambre = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= $id ? "Modifier" : "Ajouter" ?> une Chambre | MedCare</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { 
            --primary: #0f766e; 
            --primary-hover: #115e59; 
            --sidebar-bg: #0f172a; 
            --bg-body: #f1f5f9; 
            --white: #ffffff; 
            --border: #e2e8f0; 
            --card-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.04), 0 4px 6px -2px rgba(0, 0, 0, 0.02);
        }
        
        body { background: var(--bg-body); font-family: 'Plus Jakarta Sans', sans-serif; margin: 0; color: #1e293b; }
        
        /* Header & Sidebar (Identiques au Dashboard) */
        header { 
            background: var(--white); padding: 0 40px; height: 75px; 
            display: flex; justify-content: space-between; align-items: center; 
            border-bottom: 1px solid var(--border); position: fixed; width: 100%; top: 0; z-index: 1000;
        }
        .user-pill { background: #f0fdfa; padding: 8px 18px; border-radius: 12px; color: var(--primary); font-weight: 600; display: flex; align-items: center; gap: 10px; }

        .wrapper { display: flex; padding-top: 75px; }
        .sidebar { 
            width: 260px; background: var(--sidebar-bg); height: calc(100vh - 75px); 
            position: fixed; padding: 24px 16px; flex-shrink: 0; 
        }
        .sidebar a { 
            display: flex; align-items: center; gap: 12px; color: #94a3b8; 
            text-decoration: none; padding: 12px 16px; border-radius: 10px; margin-bottom: 5px; transition: 0.3s; 
        }
        .sidebar a:hover, .sidebar a.active { background: var(--primary); color: white; box-shadow: 0 4px 12px rgba(15, 118, 110, 0.3); }

        /* Contenu Principal */
        .main-content { margin-left: 260px; padding: 40px; width: calc(100% - 260px); }

        .form-card { 
            background: white; 
            border-radius: 24px; 
            padding: 40px; 
            border: none; 
            box-shadow: var(--card-shadow); 
            max-width: 900px;
            margin: 0 auto;
        }

        .form-label { font-weight: 700; color: #334155; font-size: 0.9rem; margin-bottom: 8px; }
        .form-control, .form-select { 
            border-radius: 12px; 
            padding: 12px 15px; 
            border: 1px solid #e2e8f0; 
            background: #f8fafc;
            transition: 0.3s;
        }
        .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(15, 118, 110, 0.1); background: white; }

        .btn-save {
            background: var(--primary); 
            color: white;
            border: none; 
            border-radius: 12px; 
            padding: 14px 30px; 
            font-weight: 700; 
            transition: 0.3s;
            width: 100%;
        }
        .btn-save:hover { background: var(--primary-hover); transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(15, 118, 110, 0.2); }
        
        .section-title {
            border-left: 4px solid var(--primary);
            padding-left: 15px;
            margin-bottom: 25px;
            font-weight: 800;
            color: #0f172a;
        }
    </style>
</head>
<body>

<header>
    <img src="../images/logo_app2.png" alt="Logo" style="height: 45px;">
    <div class="user-pill">
        <i class="fa-solid fa-user-shield"></i>
        <span>ADMIN : <?= strtoupper($_SESSION['role']) ?></span>
    </div>
</header>

<div class="wrapper">
    <aside class="sidebar">
        <a href="../connexion_admin/dashboard_admin.php"><i class="fa-solid fa-chart-pie"></i> Vue Générale</a>
        <a href="../connexion_admin/utilisateurs.php"><i class="fa-solid fa-user-md"></i> Utilisateurs</a>
        <a href="../connexion_admin/prestations.php"><i class="fa-solid fa-list-check"></i> Actes & Tarifs</a>
        <a href="gestion_chambres.php" class="active"><i class="fa-solid fa-bed"></i> Gestion Chambres</a>
        <div style="height: 1px; background: rgba(255,255,255,0.1); margin: 20px 0;"></div>
        <a href="../connexion_admin/facturation_list.php"><i class="fa-solid fa-file-invoice-dollar"></i> Rapports Financiers</a>
        <a href="../connexion_admin/archives.php"><i class="fa-solid fa-box-archive"></i> Archives</a>
          <a href="../connexion_admin/profil.php" class="<?= basename($_SERVER['PHP_SELF']) == 'profil.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-user-gear"></i> Mon Profil
        </a>
        <a href="../connexio_utilisateur/login.php" style="color: #fda4af; margin-top: 20px;"><i class="fa-solid fa-power-off"></i> Déconnexion</a>
    </aside>


    <main class="main-content">
        <div class="form-card">
            <div class="d-flex justify-content-between align-items-center mb-5">
                <h3 class="section-title m-0">
                    <?= $id ? "Modifier l'Unité #".$chambre['numero_chambre'] : "Ajouter une Unité Médicale" ?>
                </h3>
                <a href="gestion_chambres.php" class="btn btn-light rounded-pill px-4 fw-bold text-muted border">
                    <i class="fa-solid fa-arrow-left me-2"></i> Retour
                </a>
            </div>

            <form action="save_chambre.php" method="POST">
                <input type="hidden" name="id_chambre" value="<?= $id ?>">

                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label">Numéro de Chambre</label>
                        <input type="text" name="numero" class="form-control" value="<?= $chambre['numero_chambre'] ?? '' ?>" required placeholder="Ex: 101-A">
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Service / Département</label>
                        <select name="service" class="form-select" required>
                            <?php 
                            $services = ['Cardiologie', 'Réanimation', 'Urgences', 'Pédiatrie', 'Gynécologie', 'Chirurgie'];
                            foreach($services as $s): ?>
                                <option value="<?= $s ?>" <?= ($chambre['service'] ?? '') == $s ? 'selected' : '' ?>><?= $s ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Bloc</label>
                        <input type="text" name="bloc" class="form-control" value="<?= $chambre['bloc'] ?? '' ?>" placeholder="Ex: Bloc B">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Étage</label>
                        <input type="number" name="etage" class="form-control" value="<?= $chambre['etage'] ?? '' ?>" placeholder="0">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Capacité (Lits)</label>
                        <input type="number" name="capacite" class="form-control" value="<?= $chambre['capacite'] ?? '1' ?>" required min="1">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Type de Lit</label>
                        <input type="text" name="type_lit" class="form-control" value="<?= $chambre['type_lit'] ?? 'Électrique standard' ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Oxygène Centralisé</label>
                        <select name="oxigene" class="form-select">
                            <option value="1" <?= ($chambre['oxigene'] ?? '') == '1' ? 'selected' : '' ?>>Disponible</option>
                            <option value="0" <?= ($chambre['oxigene'] ?? '') == '0' ? 'selected' : '' ?>>Non disponible</option>
                        </select>
                    </div>

                    <div class="col-12 mt-5">
                        <button type="submit" class="btn btn-save">
                            <i class="fa-solid fa-check-double me-2"></i> <?= $id ? "Mettre à jour l'unité" : "Créer l'unité médicale" ?>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </main>
</div>

</body>
</html>