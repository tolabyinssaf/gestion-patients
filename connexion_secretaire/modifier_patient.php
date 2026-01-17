<?php
session_start();
include("../config/connexion.php");

// 1. Vérification sécurité
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'secretaire') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = ""; // Pour afficher une confirmation

// --- LOGIQUE DE MISE À JOUR (AUTO-TRAITEMENT) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id_patient'])) {
    $id_p = $_POST['id_patient'];
    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $cin = $_POST['cin'];
    $dn = $_POST['date_naissance'];
    $tel = $_POST['telephone'];
    $email = $_POST['email'];
    $sexe = $_POST['sexe'];
    $adr = $_POST['adresse'];

    $sql_update = "UPDATE patients SET nom=?, prenom=?, CIN=?, date_naissance=?, telephone=?, email=?, sexe=?, adresse=? WHERE id_patient=?";
    $stmt_up = $pdo->prepare($sql_update);
    
    if ($stmt_up->execute([$nom, $prenom, $cin, $dn, $tel, $email, $sexe, $adr, $id_p])) {
        header("Location: patients_secr.php?status=updated");
        exit;
    } else {
        $message = "<div class='alert alert-danger'>Erreur lors de la mise à jour.</div>";
    }
}

// 2. Récupération des infos du patient (Après mise à jour pour afficher les nouvelles données)
if (!isset($_GET['id']) && !isset($_POST['id_patient'])) {
    header("Location: patients_secr.php");
    exit;
}

$id_patient = isset($_GET['id']) ? $_GET['id'] : $_POST['id_patient'];
$stmt = $pdo->prepare("SELECT * FROM patients WHERE id_patient = ?");
$stmt->execute([$id_patient]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$patient) {
    die("Patient introuvable.");
}

// 3. Infos secrétaire
$stmt_user = $pdo->prepare("SELECT nom, prenom FROM utilisateurs WHERE id_user = ?");
$stmt_user->execute([$user_id]);
$user = $stmt_user->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>MedCare | Dossier Patient</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0f766e; 
            --primary-light: #f0fdfa;
            --sidebar-bg: #0f172a;
            --bg-body: #f8fafc;
            --border: #e2e8f0;
            --white: #ffffff;
            --header-height: 75px;
            --sidebar-width: 260px;
        }

        * { margin:0; padding:0; box-sizing:border-box; font-family: 'Inter', sans-serif; }
        body { background: var(--bg-body); color: #1e293b; }

        /* --- HEADER & SIDEBAR --- */
        header {
            background: var(--white); padding: 0 40px; height: var(--header-height);
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid var(--border); position: fixed; top: 0; left: 0; right: 0; z-index: 1000;
        }
        .sidebar { 
            width: var(--sidebar-width); background: var(--sidebar-bg); padding: 24px 16px; 
            position: fixed; top: var(--header-height); left: 0; bottom: 0; z-index: 999;
        }
        .sidebar h3 { color: rgba(255,255,255,0.3); font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 20px; padding-left: 12px; }
        .sidebar a { display: flex; align-items: center; gap: 12px; color: #94a3b8; text-decoration: none; padding: 12px 16px; border-radius: 10px; margin-bottom: 5px; transition: 0.3s; }
        .sidebar a:hover { background: rgba(255,255,255,0.05); color: #fff; }
        .sidebar a.active { background: var(--primary); color: #fff; }
        .user-pill { background: var(--primary-light); padding: 8px 18px; border-radius: 12px; display: flex; align-items: center; gap: 10px; font-size: 14px; font-weight: 600; color: var(--primary); }

        /* --- CONTENU --- */
        .content { 
            margin-left: var(--sidebar-width); 
            padding: 30px 40px; 
            margin-top: var(--header-height);
        }

        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 25px;
        }

        /* --- CARD FORMULAIRE --- */
        .form-card {
            background: var(--white);
            border-radius: 20px;
            border: 1px solid var(--border);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
            max-width: 1000px;
            margin: 0 auto;
            overflow: hidden;
        }

        .form-header-profile {
            background: linear-gradient(to right, #f8fafc, #ffffff);
            padding: 30px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .avatar-huge {
            width: 70px; height: 70px;
            background: var(--primary);
            color: white;
            border-radius: 18px;
            display: flex; align-items: center; justify-content: center;
            font-size: 28px; font-weight: 700;
            box-shadow: 0 10px 15px -3px rgba(15, 118, 110, 0.2);
        }

        .form-body { padding: 40px; }

        .section-separator {
            display: flex;
            align-items: center;
            margin: 30px 0 20px 0;
            color: var(--primary);
            font-weight: 700;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .section-separator i { margin-right: 10px; background: var(--primary-light); padding: 8px; border-radius: 8px; }
        .section-separator::after { content: ""; flex: 1; height: 1px; background: #f1f5f9; margin-left: 15px; }

        .form-label {
            font-weight: 600;
            font-size: 13px;
            color: #64748b;
            margin-bottom: 8px;
        }

        .input-group-custom {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-group-custom i {
            position: absolute;
            left: 15px;
            color: #94a3b8;
            font-size: 14px;
        }

        .form-control-modern {
            width: 100%;
            padding: 12px 15px 12px 45px;
            background: #fcfdfe;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            transition: all 0.2s;
            font-size: 14px;
            font-weight: 500;
        }

        .form-control-modern:focus {
            outline: none;
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 4px rgba(15, 118, 110, 0.08);
        }

        .form-select-modern {
            padding: 12px 15px;
            background: #fcfdfe;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            width: 100%;
            font-size: 14px;
        }

        .btn-update {
            background: var(--primary);
            color: white;
            padding: 14px 35px;
            border-radius: 12px;
            font-weight: 700;
            border: none;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-update:hover {
            background: #0d9488;
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(15, 118, 110, 0.25);
        }

        .btn-back {
            background: #f1f5f9;
            color: #475569;
            padding: 14px 25px;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: 0.3s;
        }
        .btn-back:hover { background: #e2e8f0; color: #1e293b; }
    </style>
</head>
<body>

<header>
    <img src="../images/logo_app2.png" alt="Logo" style="height: 45px;">
    <div class="user-pill">
        <i class="fa-solid fa-circle-user fa-lg"></i>
        <span>Séc. <?= htmlspecialchars($user['prenom']." ".$user['nom']) ?></span>
    </div>
</header>

<div class="wrapper">
    <aside class="sidebar">
        <h3>Menu Gestion</h3>
        <a href="dashboard_secretaire.php"><i class="fa-solid fa-chart-line"></i> Tableau de bord</a>
        <a href="patients_secr.php" class="active"><i class="fa-solid fa-user-group"></i> Patients</a>
        <a href="admissions.php"><i class="fa-solid fa-door-open"></i> Salle d'attente</a>
        <a href="suivis.php"><i class="fa-solid fa-calendar-check"></i> Suivis du jour</a>
        <a href="caisse.php"><i class="fa-solid fa-wallet"></i> Caisse & Factures</a>
        <div style="height: 1px; background: rgba(255,255,255,0.1); margin: 20px 0;"></div>
        <a href="../connexio_utilisateur/deconnexion.php" style="color: #fda4af;"><i class="fa-solid fa-power-off"></i> Déconnexion</a>
    </aside>

    <main class="content">
        <div style="max-width: 1000px; margin: 0 auto;">
            <?= $message ?>
        </div>

        <div class="page-header">
            <div>
                <nav style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="patients_secr.php" class="text-decoration-none text-muted small">Annuaire</a></li>
                        <li class="breadcrumb-item active small" aria-current="page">Fiche Patient</li>
                    </ol>
                </nav>
            </div>
            <a href="patients_secr.php" class="btn-back">
                <i class="fa-solid fa-xmark me-2"></i>Annuler
            </a>
        </div>

        <div class="form-card">
            <div class="form-header-profile">
                <div class="avatar-huge">
                    <?= strtoupper(substr($patient['nom'], 0, 1)) ?>
                </div>
                <div>
                    <h2 class="h4 fw-bold mb-1"><?= strtoupper($patient['nom']) ?> <?= $patient['prenom'] ?></h2>
                    <span class="badge bg-light text-dark border px-3 py-2 rounded-pill">
                        <i class="fa-solid fa-fingerprint me-2 text-primary"></i>Dossier N° <?= $patient['id_patient'] ?>
                    </span>
                </div>
            </div>

            <form action="" method="POST" class="form-body">
                <input type="hidden" name="id_patient" value="<?= $patient['id_patient'] ?>">

                <div class="section-separator">
                    <i class="fa-solid fa-user"></i> Informations Personnelles
                </div>

                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label">Nom de famille</label>
                        <div class="input-group-custom">
                            <i class="fa-solid fa-signature"></i>
                            <input type="text" name="nom" class="form-control-modern" value="<?= htmlspecialchars($patient['nom']) ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Prénom</label>
                        <div class="input-group-custom">
                            <i class="fa-solid fa-user-pen"></i>
                            <input type="text" name="prenom" class="form-control-modern" value="<?= htmlspecialchars($patient['prenom']) ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Numéro CIN</label>
                        <div class="input-group-custom">
                            <i class="fa-solid fa-address-card"></i>
                            <input type="text" name="cin" class="form-control-modern" value="<?= htmlspecialchars($patient['CIN']) ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Date de Naissance</label>
                        <div class="input-group-custom">
                            <i class="fa-solid fa-calendar-day"></i>
                            <input type="date" name="date_naissance" class="form-control-modern" value="<?= $patient['date_naissance'] ?>">
                        </div>
                    </div>
                </div>

                <div class="section-separator">
                    <i class="fa-solid fa-hospital-user"></i> Coordonnées & Contact
                </div>

                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label">Téléphone</label>
                        <div class="input-group-custom">
                            <i class="fa-solid fa-phone"></i>
                            <input type="tel" name="telephone" class="form-control-modern" value="<?= htmlspecialchars($patient['telephone']) ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Adresse Email</label>
                        <div class="input-group-custom">
                            <i class="fa-solid fa-envelope"></i>
                            <input type="email" name="email" class="form-control-modern" value="<?= htmlspecialchars($patient['email']) ?>" placeholder="exemple@mail.com">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Sexe</label>
                        <select name="sexe" class="form-select-modern">
                            <option value="H" <?= $patient['sexe'] == 'H' ? 'selected' : '' ?>>Homme</option>
                            <option value="F" <?= $patient['sexe'] == 'F' ? 'selected' : '' ?>>Femme</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Adresse de résidence</label>
                        <div class="input-group-custom">
                            <i class="fa-solid fa-location-dot" style="top: 15px;"></i>
                            <textarea name="adresse" class="form-control-modern" rows="3" style="padding-left: 45px;"><?= htmlspecialchars($patient['adresse']) ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="mt-5 d-flex justify-content-end gap-3">
                    <button type="submit" class="btn-update">
                        <i class="fa-solid fa-floppy-disk"></i>
                        Enregistrer les modifications
                    </button>
                </div>
            </form>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>