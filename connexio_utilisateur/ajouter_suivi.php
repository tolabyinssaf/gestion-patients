<?php
session_start();
include("../config/connexion.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'medecin') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$patient_id = $_GET['id_patient'] ?? $_GET['id'] ?? null;

if (!$patient_id) die("Patient non spécifié.");

// Infos médecin pour le header
$stmt_med = $pdo->prepare("SELECT prenom, nom FROM utilisateurs WHERE id_user = ?");
$stmt_med->execute([$user_id]);
$medecin = $stmt_med->fetch(PDO::FETCH_ASSOC);

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
        // On appelle la procédure avec les 5 paramètres nécessaires
        $stmt = $pdo->prepare("CALL ajouter_suivi(?, ?, ?, ?, ?)");
        $stmt->execute([$patient_id, $user_id, $date_suivi, $commentaire, $status]);
        
        header("Location: dossier_patient.php?id=" . $patient_id);
        exit;
    } catch (PDOException $e) {
        // On vérifie si c'est notre erreur personnalisée (Code 1644 ou SQLSTATE 45000)
        if ($e->getCode() == '45000' || strpos($e->getMessage(), '1644') !== false) {
            // errorInfo[2] contient uniquement le texte du MESSAGE_TEXT de votre procédure SQL
            $errorInfo = $e->errorInfo;
            $message = isset($errorInfo[2]) ? $errorInfo[2] : "La date du suivi ne peut pas être antérieure à aujourd'hui.";
            
            // Si le message contient encore des préfixes techniques, on nettoie
            if (strpos($message, 'Unhandled user-defined exception') !== false) {
                $message = "La date du suivi ne peut pas être antérieure à aujourd'hui.";
            }
        } else {
            // Pour les autres types d'erreurs (problème de base de données, etc.)
            $message = "Une erreur est survenue lors de l'enregistrement.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Nouveau Suivi | MedCare</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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

        /* ===== HEADER ===== */
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

        /* ===== LAYOUT ===== */
        .wrapper { display: flex; min-height: calc(100vh - 75px); }

        /* ===== SIDEBAR ===== */
        .sidebar { width: 260px; background: var(--sidebar-bg); padding: 24px 16px; flex-shrink: 0; }
        .sidebar h3 {
            color: rgba(255,255,255,0.3); font-size: 11px; 
            text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 20px; padding-left: 12px;
        }
        .sidebar a {
            display: flex; align-items: center; gap: 12px;
            color: #94a3b8; text-decoration: none;
            padding: 12px 16px; border-radius: 10px;
            margin-bottom: 5px; transition: 0.2s;
        }
        .sidebar a:hover { background: rgba(255,255,255,0.05); color: #fff; }
        .sidebar a.active { background: var(--primary); color: #fff; }

        /* ===== CONTENT ===== */
        .content { flex: 1; padding: 40px; }

        .btn-back {
            color: var(--text-muted); text-decoration: none; font-weight: 600; font-size: 14px;
            display: inline-flex; align-items: center; gap: 8px; margin-bottom: 20px; transition: 0.3s;
        }
        .btn-back:hover { color: var(--primary); transform: translateX(-5px); }

        /* ===== FORM CARD ===== */
        .form-container {
            max-width: 800px;
            background: var(--white);
            border-radius: 20px;
            border: 1px solid var(--border);
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        .form-header {
            background: var(--primary-light);
            padding: 25px 35px;
            border-bottom: 1px solid var(--border);
        }
        .form-body { padding: 35px; }

        .patient-info-mini {
            display: flex; align-items: center; gap: 15px; margin-top: 10px;
        }
        .avatar-mini {
            width: 40px; height: 40px; border-radius: 10px;
            background: var(--primary); color: white;
            display: flex; align-items: center; justify-content: center;
        }

        .label-custom {
            font-size: 13px; font-weight: 700; color: var(--text-muted);
            text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;
        }
        .form-control, .form-select {
            border-radius: 12px; padding: 12px 15px; border: 1px solid var(--border);
            font-size: 15px; transition: 0.3s;
        }
        .form-control:focus {
            border-color: var(--primary); box-shadow: 0 0 0 4px rgba(15, 118, 110, 0.1);
        }

        .btn-submit {
            background: var(--primary); color: white; border: none;
            padding: 14px 30px; border-radius: 12px; font-weight: 600;
            width: 100%; transition: 0.3s; margin-top: 20px;
        }
        .btn-submit:hover {
            background: var(--primary-hover); transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(15, 118, 110, 0.3);
        }

        .alert-custom {
            background: #fff7ed; border-left: 4px solid #f97316;
            color: #9a3412; padding: 15px; border-radius: 8px; margin-bottom: 25px;
        }

        @media(max-width:900px){ .sidebar { display:none; } }
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
        <h3 style="font-weight: 800;">Unité de Soins</h3>
        <a href="dashboard_medecin.php" ><i class="fa-solid fa-chart-line"></i> Vue Générale</a>
        <a href="hospitalisation.php"><i class="fa-solid fa-bed-pulse"></i> Patients Admis</a>
        <a href="patients.php"><i class="fa-solid fa-hospital-user"></i> Patients</a>
        <a href="../traitement/list.php"><i class="fa-solid fa-file-prescription"></i> Traitements</a>
        <a href="suivis.php" class="active"><i class="fa-solid fa-calendar-check"></i> Consultations</a>
        <h3 style="font-weight: 800;">Analyse & Gestion</h3>
        <a href="../admission/statistique.php"><i class="fa-solid fa-chart-pie"></i> Statistiques</a>
        <a href="archives.php"><i class="fa-solid fa-box-archive"></i> Archives</a>
        <a href="profil_medcin.php"><i class="fa-solid fa-user-gear"></i> Profil</a>
        <div style="margin-top: 40px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 10px;">
            <a href="deconnexion.php" style="color: #fda4af;"><i class="fa-solid fa-power-off"></i> Déconnexion</a>
        </div>
    </aside>

    <main class="content">
        <a href="dossier_patient.php?id=<?= $patient_id ?>" class="btn-back">
            <i class="fa-solid fa-arrow-left"></i> Retour
        </a>

        <div class="form-container mx-auto">
            <div class="form-header">
                <div class="patient-info-mini">
                    <div class="avatar-mini"><i class="fa-solid fa-user"></i></div>
                    <div>
                        <div class="small text-muted">Patient concerné</div>
                        <div class="fw-bold"><?= htmlspecialchars($patient['prenom'].' '.$patient['nom']) ?></div>
                    </div>
                </div>
            </div>

            <div class="form-body">
                <?php if($message): ?>
                    <div class="alert-custom">
                        <i class="fa-solid fa-circle-exclamation me-2"></i> <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" id="formSuivi">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="label-custom">Date de la consultation</label>
                            <input type="date" name="date_suivi" class="form-control" required value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="label-custom">Statut du suivi</label>
                            <select name="status" class="form-select" required>
                                <option value="En cours">En cours</option>
                                <option value="termine">Terminé</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="label-custom">Observations & Commentaires</label>
                            <textarea name="commentaire" rows="6" class="form-control" placeholder="Détaillez ici l'évolution du patient..." required></textarea>
                        </div>
                    </div>

                    <button type="submit" name="ajouter_suivi" class="btn-submit">
                        <i class="fa-solid fa-floppy-disk me-2"></i> Enregistrer 
                    </button>
                </form>
            </div>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>