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
    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $telephone = $_POST['telephone'];
    $email = $_POST['email'];
    $adresse = $_POST['adresse'];
    $sexe = $_POST['sexe'];
    $date_naissance = $_POST['date_naissance'];
    $cin = $_POST['cin'];
    // NOUVEAUX CHAMPS RÉCUPÉRÉS
    $groupe_sanguin = $_POST['groupe_sanguin'];
    $statut = $_POST['statut'];
    $allergies = $_POST['allergies'];

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
$stmt = $pdo->prepare("SELECT prenom, nom FROM utilisateurs WHERE id_user = ?");
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
    <style>
        :root {
            --primary: #0f766e;
            --primary-light: #f0fdfa;
            --primary-hover: #115e59;
            --sidebar-bg: #0f172a;
            --bg-body: #f8fafc;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --white: #ffffff;
            --border: #e2e8f0;
        }

        * { margin:0; padding:0; box-sizing:border-box; font-family: 'Inter', sans-serif; }
        body { background: var(--bg-body); color: var(--text-main); }

        header {
            background: var(--white);
            padding: 0 40px;
            height: 75px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border);
            position: sticky; top: 0; z-index: 100;
        }
        .logo { height: 45px; }
        .user-pill {
            background: var(--primary-light);
            padding: 8px 18px;
            border-radius: 12px;
            display: flex; align-items: center; gap: 10px;
            font-size: 14px; font-weight: 600; color: var(--primary);
            border: 1px solid rgba(15, 118, 110, 0.1);
        }

        .wrapper { display: flex; min-height: calc(100vh - 75px); }
        .sidebar { width: 260px; background: var(--sidebar-bg); padding: 24px 16px; flex-shrink: 0; }
        .sidebar h3 { color: rgba(255,255,255,0.3); font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 20px; padding-left: 12px; }
        .sidebar a { display: flex; align-items: center; gap: 12px; color: #94a3b8; text-decoration: none; padding: 12px 16px; border-radius: 10px; margin-bottom: 5px; transition: 0.2s; }
        .sidebar a:hover { background: rgba(255,255,255,0.05); color: #fff; }
        .sidebar a.active { background: var(--primary); color: #fff; }

        .content { flex: 1; padding: 40px; }
        .form-card {
            background: #fff;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            max-width: 850px;
            margin: 0 auto;
        }
        .section-divider {
            border-bottom: 1px solid var(--border);
            margin: 20px 0;
            padding-bottom: 5px;
            color: var(--primary);
            font-weight: bold;
            font-size: 14px;
            text-transform: uppercase;
        }
        .btn-save { background: var(--primary); color: white; border: none; padding: 12px 30px; border-radius: 10px; font-weight: 600; transition: 0.3s; width: 100%; }
        .btn-save:hover { background: var(--primary-hover); transform: translateY(-2px); }
    </style>
</head>
<body>

<header>
    <img src="../images/logo_app2.png" alt="Logo" class="logo">
    <div class="user-pill">
        <i class="fa-solid fa-user-md"></i>
        <span>Dr. <?= htmlspecialchars($medecin['prenom']." ".$medecin['nom']) ?></span>
    </div>
</header>

<div class="wrapper">
    <aside class="sidebar">
        <h3>Menu Médical</h3>
        <a href="dashboard_medecin.php"><i class="fa-solid fa-chart-pie"></i> Tableau de bord</a>
        <a href="patients.php" class="active"><i class="fa-solid fa-user-group"></i> Mes Patients</a>
        <a href="suivis.php"><i class="fa-solid fa-file-medical"></i> Consultations</a>
        <a href="../traitement/list.php"><i class="fa-solid fa-pills"></i> Traitements</a>
        <div style="height: 1px; background: rgba(255,255,255,0.1); margin: 20px 0;"></div>
        <a href="deconnexion.php" style="color: #fda4af;"><i class="fa-solid fa-power-off"></i> Déconnexion</a>
    </aside>

    <main class="content">
        <div class="d-flex align-items-center mb-4">
            <a href="dossier_patient.php?id=<?= $id_patient ?>" class="btn btn-light rounded-circle me-3">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <h2 class="fw-bold mb-0">Modifier la fiche patient</h2>
        </div>

        <?php if($message): ?>
            <div class="alert alert-danger"><?= $message ?></div>
        <?php endif; ?>

        <div class="form-card">
            <form method="POST">
                <div class="section-divider">État Civil</div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small text-muted fw-bold">NOM</label>
                        <input type="text" name="nom" class="form-control" value="<?= htmlspecialchars($patient['nom']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-muted fw-bold">PRÉNOM</label>
                        <input type="text" name="prenom" class="form-control" value="<?= htmlspecialchars($patient['prenom']) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-muted fw-bold">CIN</label>
                        <input type="text" name="cin" class="form-control" value="<?= htmlspecialchars($patient['CIN']) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-muted fw-bold">GENRE</label>
                        <select name="sexe" class="form-select">
                            <option value="H" <?= $patient['sexe'] == 'H' ? 'selected' : '' ?>>Homme</option>
                            <option value="F" <?= $patient['sexe'] == 'F' ? 'selected' : '' ?>>Femme</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-muted fw-bold">DATE DE NAISSANCE</label>
                        <input type="date" name="date_naissance" class="form-control" value="<?= $patient['date_naissance'] ?>" required>
                    </div>

                    <div class="section-divider">Informations Médicales</div>
                    
                    <div class="col-md-6">
                        <label class="form-label small text-muted fw-bold">GROUPE SANGUIN</label>
                        <select name="groupe_sanguin" class="form-select">
                            <option value="" <?= empty($patient['groupe_sanguin']) ? 'selected' : '' ?>>Inconnu</option>
                            <?php $groupes = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']; 
                            foreach($groupes as $g): ?>
                                <option value="<?= $g ?>" <?= $patient['groupe_sanguin'] == $g ? 'selected' : '' ?>><?= $g ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-muted fw-bold">STATUT SANTÉ</label>
                        <select name="statut" class="form-select">
                            <?php $statuts = ['Stable', 'En observation', 'Urgent', 'Critique']; 
                            foreach($statuts as $s): ?>
                                <option value="<?= $s ?>" <?= $patient['statut'] == $s ? 'selected' : '' ?>><?= $s ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small text-muted fw-bold">ALLERGIES</label>
                        <textarea name="allergies" class="form-control" rows="2" placeholder="Aucune allergie connue"><?= htmlspecialchars($patient['allergies']) ?></textarea>
                    </div>

                    <div class="section-divider">Contact & Localisation</div>
                    
                    <div class="col-md-6">
                        <label class="form-label small text-muted fw-bold">TÉLÉPHONE</label>
                        <input type="text" name="telephone" class="form-control" value="<?= htmlspecialchars($patient['telephone']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-muted fw-bold">EMAIL</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($patient['email']) ?>" required>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small text-muted fw-bold">ADRESSE</label>
                        <textarea name="adresse" class="form-control" rows="2"><?= htmlspecialchars($patient['adresse']) ?></textarea>
                    </div>
                    
                    <div class="col-12 mt-4">
                        <button type="submit" class="btn-save">
                            <i class="fa-solid fa-floppy-disk me-2"></i>Mettre à jour le dossier
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </main>
</div>

</body>
</html>