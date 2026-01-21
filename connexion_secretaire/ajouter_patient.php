<?php
session_start();
include("../config/connexion.php");

// 1. Vérification sécurité
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'secretaire') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$medecin_id_target = $_GET['medecin_id'] ?? '';

// Infos secrétaire
$stmt = $pdo->prepare("SELECT nom, prenom FROM utilisateurs WHERE id_user = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Infos médecin
$stmt_med = $pdo->prepare("SELECT nom, prenom, specialite FROM utilisateurs WHERE id_user = ? AND LOWER(role) = 'medecin'");
$stmt_med->execute([$medecin_id_target]);
$medecin_info = $stmt_med->fetch(PDO::FETCH_ASSOC);

if (!$medecin_info) {
    header("Location: dashboard_secretaire.php");
    exit;
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = strtoupper($_POST['nom']);
    $prenom = ucfirst($_POST['prenom']);
    $cin = strtoupper($_POST['cin']);
    $telephone = $_POST['telephone'];
    $date_naissance = $_POST['date_naissance'];
    $sexe = $_POST['sexe'];
    $email = $_POST['email'] ?? null;
    $adresse = $_POST['adresse'] ?? null;

    try {
        $pdo->beginTransaction();
        $check = $pdo->prepare("SELECT id_patient FROM patients WHERE cin = ?");
        $check->execute([$cin]);

        if ($check->rowCount() > 0) {
            $message = "Ce numéro de CIN est déjà enregistré.";
            $pdo->rollBack();
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO patients 
                (nom, prenom, cin, telephone, email, adresse, date_naissance, sexe, date_inscription)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$nom, $prenom, $cin, $telephone, $email, $adresse, $date_naissance, $sexe]);
            $pdo->commit();
            header("Location: dashboard_secretaire.php?patient_added=1");
            exit;
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        $message = "Erreur lors de l’enregistrement.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>MedCare | Admission Patient</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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

        /* En-tête Médical Agrandie */
        .form-header-profile {
            padding: 40px;
            background: var(--primary);
            display: flex; 
            align-items: center; 
            gap: 25px;
            color: white;
            border-bottom: 4px solid rgba(0,0,0,0.1);
        }

        .avatar-huge {
            width: 90px; height: 90px; 
            background: rgba(255,255,255,0.15); 
            border-radius: 22px; 
            display: flex; align-items: center; justify-content: center;
            font-size: 40px; 
            border: 2px solid rgba(255,255,255,0.4);
            box-shadow: 0 8px 15px rgba(0,0,0,0.1);
        }

        .medecin-title {
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-weight: 600;
            opacity: 0.85;
            margin-bottom: 4px;
        }

        .medecin-name {
            font-size: 26px;
            font-weight: 800;
            margin-bottom: 6px;
            letter-spacing: -0.5px;
        }

        .medecin-spec {
            display: inline-flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.2);
            padding: 6px 16px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 500;
            backdrop-filter: blur(4px);
        }

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
        .input-group-custom i { position: absolute; left: 18px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 15px; }

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
        .form-control-modern:focus {
            outline: none;
            background: #334155;
            box-shadow: 0 0 0 4px rgba(15, 118, 110, 0.2);
        }

        .form-select-modern {
            padding: 13px 15px;
            background: var(--input-dark);
            border: 1px solid var(--input-dark);
            border-radius: 12px;
            width: 100%;
            color: #ffffff;
            font-size: 14px;
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
    <img src="../images/logo_app2.png" alt="Logo" style="height: 45px;">
    <div class="user-pill">
        <i class="fa-solid fa-circle-user fa-lg"></i>
        <span>Séc. <?= htmlspecialchars($user['prenom']." ".$user['nom']) ?></span>
    </div>
</header>

<div class="wrapper">
    <aside class="sidebar">
        <h3 style="color:rgba(255,255,255,0.3); font-size:11px; text-transform:uppercase; margin-bottom:20px; padding-left:12px;">Menu Gestion</h3>
        <a href="dashboard_secretaire.php"><i class="fa-solid fa-chart-line"></i> Vue Générale</a>
        <a href="patients_secr.php" class="active"><i class="fa-solid fa-user-group"></i> Patients</a>
        <a href="../admission/admissions_list.php"><i class="fa-solid fa-hospital-user"></i> Admissions</a>
        <a href="suivis.php"><i class="fa-solid fa-calendar-check"></i> Suivis</a>
        <a href="caisse.php"><i class="fa-solid fa-wallet"></i> Caisse & Factures</a>
        <a href="profil_secretaire.php"><i class="fa-solid fa-user"></i> Profil</a>
        <div style="height: 1px; background: rgba(255,255,255,0.1); margin: 20px 0;"></div>
        <a href="../connexio_utilisateur/deconnexion.php" style="color: #fda4af;"><i class="fa-solid fa-power-off"></i> Déconnexion</a>
    </aside>

    <main class="content">
        <div class="form-card">
            <div class="form-header-profile">
                <div class="avatar-huge">
                    <i class="fa-solid fa-user-doctor"></i>
                </div>
                <div>
                    <div class="medecin-title">Médecin Destinataire</div>
                    <div class="medecin-name">Dr. <?= htmlspecialchars($medecin_info['prenom']." ".$medecin_info['nom']) ?></div>
                    <div class="medecin-spec">
                        <i class="fa-solid fa-stethoscope me-2"></i>
                        <?= htmlspecialchars($medecin_info['specialite']) ?>
                    </div>
                </div>
            </div>

            <?php if($message): ?>
                <div class="alert alert-danger mx-4 mt-4 mb-0 border-0 shadow-sm"><?= $message ?></div>
            <?php endif; ?>

            <form action="" method="POST" class="form-body">
                <div class="section-separator"><i class="fa-solid fa-user-tag"></i> Informations Civiles</div>

                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label">Nom de famille</label>
                        <div class="input-group-custom">
                            <i class="fa-solid fa-signature"></i>
                            <input type="text" name="nom" class="form-control-modern" placeholder="NOM" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Prénom</label>
                        <div class="input-group-custom">
                            <i class="fa-solid fa-user-pen"></i>
                            <input type="text" name="prenom" class="form-control-modern" placeholder="Prénom" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Numéro CIN</label>
                        <div class="input-group-custom">
                            <i class="fa-solid fa-address-card"></i>
                            <input type="text" name="cin" class="form-control-modern" placeholder="Numéro CIN" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Date de Naissance</label>
                        <div class="input-group-custom">
                            <i class="fa-solid fa-calendar-day"></i>
                            <input type="date" name="date_naissance" class="form-control-modern" required>
                        </div>
                    </div>
                </div>

                <div class="section-separator"><i class="fa-solid fa-map-location-dot"></i> Contact & Localisation</div>

                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label">Téléphone</label>
                        <div class="input-group-custom">
                            <i class="fa-solid fa-phone"></i>
                            <input type="tel" name="telephone" class="form-control-modern" placeholder="06..." required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Genre</label>
                        <select name="sexe" class="form-select-modern" required>
                            <option value="" disabled selected>Choisir...</option>
                            <option value="M">Masculin</option>
                            <option value="F">Féminin</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Adresse de résidence</label>
                        <div class="input-group-custom">
                            <i class="fa-solid fa-house-medical" style="top: 20px;"></i>
                            <textarea name="adresse" class="form-control-modern" rows="2" placeholder="Adresse complète..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="mt-5">
                    <button type="submit" class="btn-update">
                        <i class="fa-solid fa-folder-plus"></i> Créer et envoyer au médecin
                    </button>
                    <div class="text-center mt-3">
                        <a href="dashboard_secretaire.php" class="text-muted small text-decoration-none">
                            <i class="fa-solid fa-arrow-left me-1"></i> Annuler l'opération
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </main>
</div>

</body>
</html>