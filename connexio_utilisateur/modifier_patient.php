<?php
session_start();
include("../config/connexion.php");

// 1. Vérification de l'authentification
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'medecin') {
    header("Location: login.php");
    exit;
}

$id_medecin = $_SESSION['user_id'];
$id_patient = $_GET['id'] ?? null;

if (!$id_patient) {
    die("Patient non spécifié.");
}

// 2. Récupération des données actuelles du patient
$stmt = $pdo->prepare("SELECT * FROM patients WHERE id_patient = ? AND id_medecin = ?");
$stmt->execute([$id_patient, $id_medecin]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$patient) {
    die("Patient introuvable ou vous n'avez pas l'autorisation de le modifier.");
}

// 3. Traitement du formulaire de mise à jour
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = strtoupper(trim($_POST['nom']));
    $prenom = ucfirst(trim($_POST['prenom']));
    $telephone = trim($_POST['telephone']);
    $email = trim($_POST['email']);
    $adresse = trim($_POST['adresse']);
    $sexe = $_POST['sexe'];
    $date_naissance = $_POST['date_naissance'];
    $cin = strtoupper(trim($_POST['cin']));
    $groupe_sanguin = $_POST['groupe_sanguin'];
    $statut = $_POST['statut'];
    $allergies = trim($_POST['allergies']);

    $sql = "UPDATE patients SET 
            nom = ?, prenom = ?, telephone = ?, email = ?, 
            adresse = ?, sexe = ?, date_naissance = ?, CIN = ?,
            groupe_sanguin = ?, statut = ?, allergies = ?
            WHERE id_patient = ? AND id_medecin = ?";
    
    $stmt = $pdo->prepare($sql);
    if ($stmt->execute([$nom, $prenom, $telephone, $email, $adresse, $sexe, $date_naissance, $cin, $groupe_sanguin, $statut, $allergies, $id_patient, $id_medecin])) {
        header("Location: dossier_patient.php?id=" . $id_patient . "&success=1");
        exit;
    } else {
        $message = "Une erreur est survenue lors de la mise à jour.";
    }
}

// Récupération infos médecin pour le header
$stmt = $pdo->prepare("SELECT prenom, nom, specialite FROM utilisateurs WHERE id_user = ?");
$stmt->execute([$id_medecin]);
$medecin = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier Patient | MedCare</title>
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
        .sidebar h3 { color:rgba(255,255,255,0.3); font-size:11px; text-transform:uppercase; margin-bottom:20px; padding-left:12px; font-weight: 800; }
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
        .input-group-custom i.fa-textarea { top: 20px; }

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
        <i class="fa-solid fa-user-md"></i>
        <span>Dr. <?= htmlspecialchars($medecin['prenom']." ".$medecin['nom']) ?></span>
    </div>
</header>

<div class="wrapper">
    <aside class="sidebar">
        <h3>Unité de Soins</h3>
        <a href="dashboard_medecin.php"><i class="fa-solid fa-chart-line"></i> Vue Générale</a>
        <a href="hospitalisation.php"><i class="fa-solid fa-bed-pulse"></i> Patients Admis</a>
        <a href="patients.php" class="active"><i class="fa-solid fa-hospital-user"></i> Patients</a>
        <a href="../traitement/list.php"><i class="fa-solid fa-file-prescription"></i> Traitements</a>
        <a href="suivis.php"><i class="fa-solid fa-calendar-check"></i> Consultations</a>
        <div style="height: 1px; background: rgba(255,255,255,0.1); margin: 20px 0;"></div>
        <a href="deconnexion.php" style="color: #fda4af;"><i class="fa-solid fa-power-off"></i> Déconnexion</a>
    </aside>

    <main class="content">
        <div class="form-card">
            <div class="form-header-profile">
                <div class="avatar-huge">
                    <i class="fa-solid fa-user-pen"></i>
                </div>
                <div>
                    <div class="medecin-title">Modification du dossier de</div>
                    <div class="medecin-name"><?= htmlspecialchars($patient['prenom']." ".$patient['nom']) ?></div>
                    <div class="medecin-spec">
                        <i class="fa-solid fa-id-card me-2"></i>
                        Patient ID: #<?= $id_patient ?>
                    </div>
                </div>
            </div>

            <?php if($message): ?>
                <div class="alert alert-danger mx-4 mt-4 mb-0 border-0 shadow-sm"><?= $message ?></div>
            <?php endif; ?>

            <form method="POST" class="form-body">
                <div class="section-separator"><i class="fa-solid fa-user-tag"></i> Informations Civiles</div>

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
                            <input type="date" name="date_naissance" class="form-control-modern" value="<?= $patient['date_naissance'] ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Téléphone</label>
                        <div class="input-group-custom">
                            <i class="fa-solid fa-phone"></i>
                            <input type="tel" name="telephone" class="form-control-modern" value="<?= htmlspecialchars($patient['telephone']) ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Genre</label>
                        <select name="sexe" class="form-select-modern" required>
                            <option value="H" <?= $patient['sexe'] == 'H' ? 'selected' : '' ?>>Homme</option>
                            <option value="F" <?= $patient['sexe'] == 'F' ? 'selected' : '' ?>>Femme</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Adresse Email</label>
                        <div class="input-group-custom">
                            <i class="fa-solid fa-envelope"></i>
                            <input type="email" name="email" class="form-control-modern" value="<?= htmlspecialchars($patient['email']) ?>">
                        </div>
                    </div>
                </div>

                <div class="section-separator"><i class="fa-solid fa-heart-pulse"></i> Informations Médicales</div>

                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label">Groupe Sanguin</label>
                        <select name="groupe_sanguin" class="form-select-modern">
                            <option value="">Sélectionner</option>
                            <?php $groupes = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']; 
                            foreach($groupes as $g): ?>
                                <option value="<?= $g ?>" <?= $patient['groupe_sanguin'] == $g ? 'selected' : '' ?>><?= $g ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Statut Santé</label>
                        <select name="statut" class="form-select-modern">
                            <?php $statuts = ['Stable', 'En observation', 'Urgent', 'Critique']; 
                            foreach($statuts as $s): ?>
                                <option value="<?= $s ?>" <?= $patient['statut'] == $s ? 'selected' : '' ?>><?= $s ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Allergies & Antécédents</label>
                        <div class="input-group-custom">
                            <i class="fa-solid fa-hand-dots fa-textarea" style="top:20px;"></i>
                            <textarea name="allergies" class="form-control-modern" rows="2"><?= htmlspecialchars($patient['allergies']) ?></textarea>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Adresse de résidence</label>
                        <div class="input-group-custom">
                            <i class="fa-solid fa-house-chimney-medical fa-textarea" style="top:20px;"></i>
                            <textarea name="adresse" class="form-control-modern" rows="2" required><?= htmlspecialchars($patient['adresse']) ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="mt-5">
                    <button type="submit" class="btn-update">
                        <i class="fa-solid fa-save"></i> Enregistrer les modifications
                    </button>
                    <div class="text-center mt-3">
                        <a href="dossier_patient.php?id=<?= $id_patient ?>" class="text-muted small text-decoration-none">
                            <i class="fa-solid fa-arrow-left me-1"></i> Annuler et retourner au dossier
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </main>
</div>

</body>
</html>