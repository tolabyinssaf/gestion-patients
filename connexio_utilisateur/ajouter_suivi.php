<?php
session_start();
include("../config/connexion.php");

// 1. Vérification sécurité
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'medecin') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$patient_id = $_GET['id_patient'] ?? $_GET['id'] ?? null;

if (!$patient_id) die("Patient non spécifié.");

// Infos médecin pour le header et l'en-tête de carte
$stmt_med = $pdo->prepare("SELECT prenom, nom, specialite FROM utilisateurs WHERE id_user = ?");
$stmt_med->execute([$user_id]);
$medecin = $stmt_med->fetch(PDO::FETCH_ASSOC);

// Infos patient
$stmt = $pdo->prepare("SELECT * FROM patients WHERE id_patient = ? AND id_medecin = ?");
$stmt->execute([$patient_id, $user_id]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$patient) die("Patient introuvable.");

$message = "";
if (isset($_POST['ajouter_suivi'])) {
    $date_suivi = $_POST['date_suivi'];
    $commentaire = $_POST['commentaire'];
    $status = $_POST['status'];

    try {
        $stmt = $pdo->prepare("CALL ajouter_suivi(?, ?, ?, ?, ?)");
        $stmt->execute([$patient_id, $user_id, $date_suivi, $commentaire, $status]);
        
        header("Location: dossier_patient.php?id=" . $patient_id);
        exit;
    } catch (PDOException $e) {
        if ($e->getCode() == '45000' || strpos($e->getMessage(), '1644') !== false) {
            $errorInfo = $e->errorInfo;
            $message = isset($errorInfo[2]) ? $errorInfo[2] : "La date du suivi ne peut pas être antérieure à aujourd'hui.";
            if (strpos($message, 'Unhandled user-defined exception') !== false) {
                $message = "La date du suivi ne peut pas être antérieure à aujourd'hui.";
            }
        } else {
            $message = "Une erreur est survenue lors de l'enregistrement.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>MedCare | Nouveau Suivi</title>
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
            display: flex; align-items: center; margin: 10px 0 25px 0;
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
        
        textarea.form-control-modern { padding-top: 15px; }
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
        <h3 style="color:rgba(255,255,255,0.3); font-size:11px; text-transform:uppercase; margin-bottom:20px; padding-left:12px;">Unité de Soins</h3>
        <a href="dashboard_medecin.php"><i class="fa-solid fa-chart-line"></i> Vue Générale</a>
        <a href="hospitalisation.php"><i class="fa-solid fa-bed-pulse"></i> Patients Admis</a>
        <a href="patients.php"><i class="fa-solid fa-hospital-user"></i> Patients</a>
        <a href="../traitement/list.php"><i class="fa-solid fa-file-prescription"></i> Traitements</a>
        <a href="suivis.php" class="active"><i class="fa-solid fa-calendar-check"></i> Consultations</a>
        <div style="height: 1px; background: rgba(255,255,255,0.1); margin: 20px 0;"></div>
        <a href="deconnexion.php" style="color: #fda4af;"><i class="fa-solid fa-power-off"></i> Déconnexion</a>
    </aside>

    <main class="content">
        <div class="form-card">
            <div class="form-header-profile">
                <div class="avatar-huge">
                    <i class="fa-solid fa-notes-medical"></i>
                </div>
                <div>
                    <div class="medecin-title">Nouvelle consultation pour</div>
                    <div class="medecin-name"><?= htmlspecialchars($patient['prenom'].' '.$patient['nom']) ?></div>
                    <div class="medecin-spec">
                        <i class="fa-solid fa-user-injured me-2"></i>
                        Patient ID: #<?= htmlspecialchars($patient['id_patient']) ?>
                    </div>
                </div>
            </div>

            <?php if($message): ?>
                <div class="alert alert-warning mx-4 mt-4 mb-0 border-0 shadow-sm">
                    <i class="fa-solid fa-circle-exclamation me-2"></i> <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST" class="form-body">
                <div class="section-separator"><i class="fa-solid fa-clock-rotate-left"></i> Paramètres du suivi</div>

                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label">Date de la consultation</label>
                        <div class="input-group-custom">
                            <i class="fa-solid fa-calendar-day"></i>
                            <input type="date" name="date_suivi" class="form-control-modern" required value="<?= date('Y-m-d') ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Statut du patient</label>
                        <select name="status" class="form-select-modern" required>
                            <option value="En cours">En cours</option>
                            <option value="termine">Terminé / Sortie</option>
                        </select>
                    </div>
                    
                    <div class="col-12">
                        <div class="section-separator" style="margin-top: 20px;"><i class="fa-solid fa-comment-medical"></i> Observations cliniques</div>
                        <label class="form-label">Compte-rendu et commentaires</label>
                        <div class="input-group-custom">
                            <i class="fa-solid fa-pen-to-square" style="top: 20px;"></i>
                            <textarea name="commentaire" class="form-control-modern" rows="6" placeholder="Saisissez ici les détails de l'examen, l'évolution des symptômes..." required></textarea>
                        </div>
                    </div>
                </div>

                <div class="mt-5">
                    <button type="submit" name="ajouter_suivi" class="btn-update">
                        <i class="fa-solid fa-floppy-disk"></i> Enregistrer le suivi médical
                    </button>
                    <div class="text-center mt-3">
                        <a href="dossier_patient.php?id=<?= $patient_id ?>" class="text-muted small text-decoration-none">
                            <i class="fa-solid fa-arrow-left me-1"></i> Retour au dossier patient
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </main>
</div>

</body>
</html>