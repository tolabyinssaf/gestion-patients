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
// Infos médecin
$stmt_med = $pdo->prepare("SELECT nom, prenom FROM utilisateurs WHERE id_user = ?");
$stmt_med->execute([$id_medecin]);
$med_data = $stmt_med->fetch(PDO::FETCH_ASSOC);

// Infos suivi et patient (lié au médecin connecté)
$stmt = $pdo->prepare("
    SELECT s.*, p.nom, p.prenom, p.id_patient
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
        
        // On cherche la position du code 1644
        $pos = strpos($raw_message, '1644');
        
        if ($pos !== false) {
            // On récupère tout ce qui est après "1644"
            $clean_message = trim(substr($raw_message, $pos + 4));
        } else {
            // Message générique si le format change
            $clean_message = "Une erreur est survenue lors de la validation.";
        }

        // On ajoute un ID "error-alert" pour pouvoir le cibler en JS
        $message = "<div id='error-alert' class='alert alert-danger shadow-sm'>
                        <i class='fa-solid fa-circle-exclamation me-2'></i>" . $clean_message . "
                    </div>";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier Suivi | MedCare Pro</title>
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
            --header-height: 75px;
            --sidebar-width: 260px;
        }

        * { margin:0; padding:0; box-sizing:border-box; font-family: 'Inter', sans-serif; }
        body { background: var(--bg-body); color: #1e293b; }

        /* --- HEADER & SIDEBAR --- */
        header {
            background: #fff; padding: 0 40px; height: var(--header-height);
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
        .content { margin-left: var(--sidebar-width); padding: 30px 40px; margin-top: var(--header-height); }

        /* --- CARD FORMULAIRE --- */
        .form-card {
            background: #fff; border-radius: 20px; border: 1px solid var(--border);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03); max-width: 900px; margin: 0 auto; overflow: hidden;
        }

        .form-header-profile {
            background: linear-gradient(to right, #f8fafc, #ffffff); padding: 30px;
            border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 20px;
        }

        .avatar-huge {
            width: 70px; height: 70px; background: var(--primary); color: white;
            border-radius: 18px; display: flex; align-items: center; justify-content: center;
            font-size: 28px; font-weight: 700; box-shadow: 0 10px 15px -3px rgba(15, 118, 110, 0.2);
        }

        .form-body { padding: 40px; }

        .section-separator {
            display: flex; align-items: center; margin: 0 0 25px 0;
            color: var(--primary); font-weight: 700; font-size: 13px;
            text-transform: uppercase; letter-spacing: 1px;
        }
        .section-separator i { margin-right: 10px; background: var(--primary-light); padding: 8px; border-radius: 8px; }
        .section-separator::after { content: ""; flex: 1; height: 1px; background: #f1f5f9; margin-left: 15px; }

        .form-label { font-weight: 600; font-size: 13px; color: #64748b; margin-bottom: 8px; }

        .input-group-custom { position: relative; display: flex; align-items: center; }
        .input-group-custom i { position: absolute; left: 15px; color: #94a3b8; font-size: 14px; }

        .form-control-modern {
            width: 100%; padding: 12px 15px 12px 45px; background: #fcfdfe;
            border: 1.5px solid #e2e8f0; border-radius: 12px; transition: all 0.2s;
            font-size: 14px; font-weight: 500;
        }
        .form-control-modern:focus {
            outline: none; border-color: var(--primary); background: white;
            box-shadow: 0 0 0 4px rgba(15, 118, 110, 0.08);
        }

        .btn-update {
            background: var(--primary); color: white; padding: 14px 30px;
            border-radius: 12px; font-weight: 700; border: none; transition: 0.3s;
            display: flex; align-items: center; gap: 10px;
        }
        .btn-update:hover {
            background: #0d9488; transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(15, 118, 110, 0.25);
        }

        .btn-back {
            background: #f1f5f9; color: #475569; padding: 10px 20px;
            border-radius: 10px; font-weight: 600; text-decoration: none; transition: 0.3s;
        }
        .btn-back:hover { background: #e2e8f0; }
    </style>
</head>
<body>

<header>
    <img src="../images/logo_app2.png" alt="Logo" style="height: 45px;">
    <div class="user-pill">
        <i class="fa-solid fa-user-doctor"></i>
        <span>Dr. <?= htmlspecialchars(strtoupper($med_data['nom'])) ?></span>
    </div>
</header>

<div class="wrapper">
    <aside class="sidebar">
        <h3>Unité de Soins</h3>
        <a href="dashboard_medecin.php"><i class="fa-solid fa-chart-line"></i> Dashboard</a>
        <a href="hospitalisation.php"><i class="fa-solid fa-bed-pulse"></i> Patients Admis</a>
        <h3>Dossiers</h3>
        <a href="patients.php"><i class="fa-solid fa-user-group"></i> Base Patients</a>
        <a href="suivis.php" class="active"><i class="fa-solid fa-file-medical"></i> Journal des Soins</a>
        <a href="rendezvous.php"><i class="fa-solid fa-calendar-check"></i> Consultations</a>
        <div style="margin-top: 50px;">
            <a href="deconnexion.php" style="color: #fda4af;"><i class="fa-solid fa-power-off"></i> Déconnexion</a>
        </div>
    </aside>

    <main class="content">
        <div class="mb-4 d-flex justify-content-between align-items-center" style="max-width: 900px; margin: 0 auto;">
           
            <a href="suivis.php" class="btn-back"><i class="fa-solid fa-arrow-left me-2"></i>Retour</a>
        </div>

        <div style="max-width: 900px; margin: 0 auto;">
            <?= $message ?>
        </div>

        <div class="form-card">
            <div class="form-header-profile">
                <div class="avatar-huge">
                    <?= strtoupper(substr($suivi['nom'], 0, 1)) ?>
                </div>
                <div>
                    <h2 class="h4 fw-bold mb-1"><?= strtoupper($suivi['nom']) ?> <?= $suivi['prenom'] ?></h2>
                    <span class="badge bg-light text-dark border px-3 py-2 rounded-pill">
                        <i class="fa-solid fa-folder-open me-2 text-primary"></i>ID Patient : #<?= $suivi['id_patient'] ?>
                    </span>
                </div>
            </div>

            <form action="" method="POST" class="form-body">
                <div class="section-separator">
                    <i class="fa-solid fa-notes-medical"></i> Détails de l'observation
                </div>

                <div class="row g-4">
                    <div class="col-md-5">
                        <label class="form-label">Date du suivi</label>
                        <div class="input-group-custom">
                            <i class="fa-solid fa-calendar-day"></i>
                            <input type="date" name="date_suivi" class="form-control-modern" 
                                   value="<?= $suivi['date_suivi'] ?>" required>
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Observations & Commentaires médicaux</label>
                        <div class="input-group-custom">
                            <i class="fa-solid fa-comment-medical" style="top: 15px;"></i>
                            <textarea name="commentaire" class="form-control-modern" rows="8" 
                                      style="padding-left: 45px;" placeholder="Saisissez vos observations cliniques..." 
                                      required><?= htmlspecialchars($suivi['commentaire']) ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="mt-5 d-flex justify-content-end">
                    <button type="submit" name="modifier" class="btn-update">
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