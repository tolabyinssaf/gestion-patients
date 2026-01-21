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
    <title>MedCare | <?= $id ? "Modifier" : "Ajouter" ?> une Chambre</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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

        header { background: var(--white); padding: 0 40px; height: var(--header-height); display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; position: fixed; top: 0; left: 0; right: 0; z-index: 1000; }
        .sidebar { width: var(--sidebar-width); background: var(--sidebar-bg); padding: 24px 16px; position: fixed; top: var(--header-height); left: 0; bottom: 0; z-index: 999; }
        .sidebar a { display: flex; align-items: center; gap: 12px; color: #94a3b8; text-decoration: none; padding: 12px 16px; border-radius: 10px; margin-bottom: 5px; transition: 0.3s; }
        .sidebar a:hover { background: rgba(255,255,255,0.05); color: #fff; }
        .sidebar a.active { background: var(--primary); color: #fff; }
        .user-pill { background: #f0fdfa; padding: 8px 18px; border-radius: 12px; display: flex; align-items: center; gap: 10px; font-size: 14px; font-weight: 600; color: var(--primary); }
        .content { margin-left: var(--sidebar-width); padding: 30px 40px; margin-top: var(--header-height); }

        .form-card {
            background: var(--white);
            border-radius: 24px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 15px 35px -5px rgba(0, 0, 0, 0.07);
            max-width: 900px;
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
            width: 90px; height: 90px; 
            background: rgba(255,255,255,0.15); 
            border-radius: 22px; display: flex; align-items: center; justify-content: center;
            font-size: 40px; border: 2px solid rgba(255,255,255,0.4);
        }

        .medecin-title { font-size: 13px; text-transform: uppercase; letter-spacing: 1.5px; font-weight: 600; opacity: 0.85; margin-bottom: 4px; }
        .medecin-name { font-size: 26px; font-weight: 800; margin-bottom: 6px; letter-spacing: -0.5px; }
        .medecin-spec { display: inline-flex; align-items: center; background: rgba(255, 255, 255, 0.2); padding: 6px 16px; border-radius: 30px; font-size: 14px; font-weight: 500; backdrop-filter: blur(4px); }

        .form-body { padding: 40px; }

        .section-separator {
            display: flex; align-items: center; margin: 30px 0 20px 0;
            color: var(--primary); font-weight: 700; font-size: 13px;
            text-transform: uppercase; letter-spacing: 1px;
        }
        .section-separator i { margin-right: 12px; }
        .section-separator::after { content: ""; flex: 1; height: 1px; background: #e2e8f0; margin-left: 15px; }

        .form-label { font-weight: 600; font-size: 13px; color: #64748b; margin-bottom: 8px; display: block; }
        .input-group-custom { position: relative; }
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

        .form-select-modern {
            padding: 13px 15px 13px 50px;
            background: var(--input-dark);
            border: 1px solid var(--input-dark);
            border-radius: 12px;
            width: 100%;
            color: #ffffff;
            font-size: 14px;
            appearance: none;
        }

        .btn-update {
            background: var(--primary); color: white; padding: 18px 35px;
            border-radius: 14px; font-weight: 700; border: none; transition: 0.3s;
            display: flex; align-items: center; gap: 12px; width: 100%; justify-content: center;
            text-transform: uppercase; letter-spacing: 1px; font-size: 15px;
            cursor: pointer;
        }
        .btn-update:hover { background: var(--primary-hover); transform: translateY(-2px); box-shadow: 0 10px 20px rgba(15, 118, 110, 0.3); }
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
    <a href="../connexion_admin/prestations.php" ><i class="fa-solid fa-list-check"></i> Actes & Tarifs</a>
    <a href="gestion_chambres.php" class="active"><i class="fa-solid fa-bed"></i> Gestion Chambres</a>
    <div style="height: 1px; background: rgba(255,255,255,0.1); margin: 20px 0;"></div>
    <a href="../connexion_admin/facturation_list.php"><i class="fa-solid fa-file-invoice-dollar"></i> Rapports Financiers</a>
    <a href="../connexion_admin/archives.php"><i class="fa-solid fa-box-archive"></i> Archives</a>
    <a href="../connexion_admin/profil.php"><i class="fa-solid fa-user-gear"></i> Mon Profil</a>
    <a href="../connexio_utilisateur/login.php" style="color: #fda4af; margin-top: 20px;"><i class="fa-solid fa-power-off"></i> Déconnexion</a>
</aside>

    <main class="content">
        <div class="form-card">
            <div class="form-header-profile">
                <div class="avatar-huge">
                    <i class="fa-solid fa-hospital"></i>
                </div>
                <div>
                    <div class="medecin-title">Configuration des ressources</div>
                    <div class="medecin-name"><?= $id ? "Modification Chambre #".$chambre['numero_chambre'] : "Nouvelle Unité Médicale" ?></div>
                    <div class="medecin-spec">
                        <i class="fa-solid fa-map-location-dot me-2"></i>
                        Localisation & Capacité Hospitalière
                    </div>
                </div>
            </div>

            <form action="save_chambre.php" method="POST" class="form-body">
                <input type="hidden" name="id_chambre" value="<?= $id ?>">
                
                <div class="section-separator"><i class="fa-solid fa-door-open"></i> Identification de l'espace</div>

                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label">Numéro de Chambre</label>
                        <div class="input-group-custom">
                            <i class="fa-solid fa-hashtag"></i>
                            <input type="text" name="numero" class="form-control-modern" value="<?= $chambre['numero_chambre'] ?? '' ?>" required placeholder="Ex: 101-A">
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Service / Département</label>
                        <div class="input-group-custom">
                            <i class="fa-solid fa-house-medical-circle-check"></i>
                            <select name="service" class="form-select-modern" required>
                                <?php 
                                $services = ['Cardiologie', 'Réanimation', 'Urgences', 'Pédiatrie', 'Gynécologie', 'Chirurgie'];
                                foreach($services as $s): ?>
                                    <option value="<?= $s ?>" <?= ($chambre['service'] ?? '') == $s ? 'selected' : '' ?>><?= $s ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Bloc</label>
                        <div class="input-group-custom">
                            <i class="fa-solid fa-building"></i>
                            <input type="text" name="bloc" class="form-control-modern" value="<?= $chambre['bloc'] ?? '' ?>" placeholder="Ex: Bloc B">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Étage</label>
                        <div class="input-group-custom">
                            <i class="fa-solid fa-layer-group"></i>
                            <input type="number" name="etage" class="form-control-modern" value="<?= $chambre['etage'] ?? '' ?>" placeholder="0">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Capacité (Lits)</label>
                        <div class="input-group-custom">
                            <i class="fa-solid fa-bed"></i>
                            <input type="number" name="capacite" class="form-control-modern" value="<?= $chambre['capacite'] ?? '1' ?>" required min="1">
                        </div>
                    </div>
                </div>

                <div class="section-separator"><i class="fa-solid fa-gears"></i> Équipements & Confort</div>

                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label">Type de Lit</label>
                        <div class="input-group-custom">
                            <i class="fa-solid fa-toolbox"></i>
                            <input type="text" name="type_lit" class="form-control-modern" value="<?= $chambre['type_lit'] ?? 'Électrique standard' ?>">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Oxygène Centralisé</label>
                        <div class="input-group-custom">
                            <i class="fa-solid fa-lungs"></i>
                            <select name="oxigene" class="form-select-modern">
                                <option value="1" <?= ($chambre['oxigene'] ?? '') == '1' ? 'selected' : '' ?>>Disponible</option>
                                <option value="0" <?= ($chambre['oxigene'] ?? '') == '0' ? 'selected' : '' ?>>Non disponible</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="mt-5">
                    <button type="submit" class="btn-update">
                        <i class="fa-solid fa-save"></i> <?= $id ? "Mettre à jour l'unité" : "Créer l'unité médicale" ?>
                    </button>
                    <div class="text-center mt-3">
                        <a href="gestion_chambres.php" class="text-muted small text-decoration-none">
                            <i class="fa-solid fa-arrow-left me-1"></i> Annuler et retourner à la liste
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </main>
</div>

</body>
</html>