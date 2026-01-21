<?php
session_start();
include("../config/connexion.php");

// 1. Vérification sécurité (Rôle Médecin)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'medecin') {
    header("Location: login.php");
    exit;
}

$id_medecin = $_SESSION['user_id'];
$id_suivi = $_GET['id'] ?? null;

if (!$id_suivi) {
    die("Suivi non spécifié.");
}

/* =========================
   Récupération des données
========================= */
// Infos médecin pour le header
$stmt_med = $pdo->prepare("SELECT prenom, nom, specialite FROM utilisateurs WHERE id_user = ?");
$stmt_med->execute([$id_medecin]);
$medecin = $stmt_med->fetch(PDO::FETCH_ASSOC);

// Infos suivi et patient (lié au médecin connecté)
$stmt = $pdo->prepare("
    SELECT s.*, p.nom as patient_nom, p.prenom as patient_prenom, p.id_patient
    FROM suivis s
    JOIN patients p ON s.id_patient = p.id_patient
    WHERE s.id_suivi = ? AND p.id_medecin = ?
");
$stmt->execute([$id_suivi, $id_medecin]);
$suivi = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$suivi) {
    die("Suivi introuvable ou accès refusé.");
}

$message = "";

/* =========================
   Traitement du formulaire
========================= */
if (isset($_POST['modifier'])) {
    $date_suivi = $_POST['date_suivi'];
    $commentaire = $_POST['commentaire'];

    try {
        $stmt_up = $pdo->prepare("
            UPDATE suivis 
            SET date_suivi = ?, commentaire = ?
            WHERE id_suivi = ?
        ");
        $stmt_up->execute([$date_suivi, $commentaire, $id_suivi]);

        header("Location: suivis.php?status=success");
        exit;
    } catch (PDOException $e) {
        $raw_message = $e->getMessage();
        $pos = strpos($raw_message, '1644');
        
        if ($pos !== false) {
            $message = trim(substr($raw_message, $pos + 4));
        } else {
            $message = "Une erreur est survenue lors de la mise à jour.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier Suivi | MedCare</title>
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
                    <i class="fa-solid fa-file-pen"></i>
                </div>
                <div>
                    <div class="medecin-title">Modification de la consultation du</div>
                    <div class="medecin-name"><?= htmlspecialchars($suivi['patient_prenom'].' '.$suivi['patient_nom']) ?></div>
                    <div class="medecin-spec">
                        <i class="fa-solid fa-id-card me-2"></i>
                        Suivi N°: #<?= htmlspecialchars($id_suivi) ?>
                    </div>
                </div>
            </div>

            <?php if($message): ?>
                <div class="alert alert-warning mx-4 mt-4 mb-0 border-0 shadow-sm">
                    <i class="fa-solid fa-circle-exclamation me-2"></i> <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST" class="form-body">
                <div class="section-separator"><i class="fa-solid fa-clock-rotate-left"></i> Chronologie</div>

                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label">Date du suivi</label>
                        <div class="input-group-custom">
                            <i class="fa-solid fa-calendar-day"></i>
                            <input type="date" name="date_suivi" class="form-control-modern" required 
                                   value="<?= htmlspecialchars($suivi['date_suivi']) ?>">
                        </div>
                    </div>
                    
                    <div class="col-12">
                        <div class="section-separator" style="margin-top: 20px;"><i class="fa-solid fa-comment-medical"></i> Expertise médicale</div>
                        <label class="form-label">Observations et compte-rendu</label>
                        <div class="input-group-custom">
                            <i class="fa-solid fa-pen-to-square" style="top: 20px;"></i>
                            <textarea name="commentaire" class="form-control-modern" rows="8"  cols="5"
                                      required><?= htmlspecialchars($suivi['commentaire']) ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="mt-5">
                    <button type="submit" name="modifier" class="btn-update">
                        <i class="fa-solid fa-save"></i> Enregistrer les modifications
                    </button>
                    <div class="text-center mt-3">
                        <a href="suivis.php" class="text-muted small text-decoration-none">
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