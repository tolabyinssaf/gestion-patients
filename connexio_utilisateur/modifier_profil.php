<?php
session_start();
include("../config/connexion.php");

// Vérifier que l'utilisateur est connecté et est médecin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'medecin') {
    header("Location: login.php");
    exit;
}

// Infos médecin
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT nom, prenom, email, telephone FROM utilisateurs WHERE id_user = ?");
$stmt->execute([$user_id]);
$medecin = $stmt->fetch(PDO::FETCH_ASSOC);

$form_message = "";

// ===== MODIFIER PROFIL =====
if (isset($_POST['modifier'])) {
    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $email = $_POST['email'];
    $telephone = $_POST['telephone'];

    try {
        $stmt = $pdo->prepare("UPDATE utilisateurs SET nom=?, prenom=?, email=?, telephone=? WHERE id_user=?");
        $stmt->execute([$nom, $prenom, $email, $telephone, $user_id]);
        header("Location: profil_medcin.php?success=1");
        exit;
    } catch (PDOException $e) {
        if ($e->errorInfo[1] == 1062) {
            $form_message = "<span class='error'><i class='fas fa-exclamation-circle'></i> Cet email est déjà utilisé.</span>";
        } else {
            $form_message = "<span class='error'><i class='fas fa-exclamation-triangle'></i> Erreur lors de la mise à jour.</span>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier Profil | MedCare</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
    :root {
        --primary: #0f766e; /* Le vert que vous avez choisi */
        --primary-light: #f0fdfa;
        --primary-hover: #115e59;
        --sidebar-bg: #0f172a;
        --bg-body: #f8fafc;
        --text-main: #1e293b;
        --text-muted: #64748b;
        --white: #ffffff;
        --border: #e2e8f0;
    }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', system-ui, sans-serif; }
        body { background-color: var(--bg-body); color: var(--text-main); }

        /* --- HEADER --- */
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
    .container { display: flex; min-height: calc(100vh - 75px); }

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

        /* --- CONTENT --- */
        .content { flex: 1; padding: 40px; max-width: 900px; margin: 0 auto; }
        .page-header { margin-bottom: 30px; }
        .page-header h1 { font-size: 28px; color: #1e293b; margin-bottom: 8px; }
        .page-header p { color: var(--text-muted); }

        /* --- CARD & FORM --- */
        .card {
            background: var(--white);
            padding: 35px;
            border-radius: 20px;
            border: 1px solid var(--border);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }

        .input-group { display: flex; flex-direction: column; gap: 8px; }
        .input-group.full { grid-column: span 2; }
        .input-group label { font-size: 14px; font-weight: 600; color: #475569; }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: 15px;
            transition: 0.3s;
            background: #fcfcfc;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 4px rgba(15, 118, 110, 0.1);
        }

        /* --- BUTTONS --- */
        .form-actions { display: flex; gap: 15px; margin-top: 10px; }
        
        button[name="modifier"] {
            background: var(--primary);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            display: flex; align-items: center; gap: 10px;
        }
        button[name="modifier"]:hover { background: var(--primary-dark); transform: translateY(-1px); }

        .btn-cancel {
            background: #f1f5f9;
            color: #64748b;
            padding: 12px 24px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: 0.2s;
        }
        .btn-cancel:hover { background: #e2e8f0; color: #334155; }

        /* --- MESSAGES --- */
        .message {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-size: 14px;
            display: flex; align-items: center;
        }
        .error { color: #b91c1c; background: #fef2f2; border: 1px solid #fee2e2; width: 100%; display: block; }

        @media (max-width: 768px) {
            .form-grid { grid-template-columns: 1fr; }
            .input-group.full { grid-column: span 1; }
            .sidebar { display: none; }
        }
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

<div class="container">
      <aside class="sidebar">
        <h3 style="font-weight: 800;">Unité de Soins</h3>
        <a href="dashboard_medecin.php"><i class="fa-solid fa-chart-line"></i> Vue Générale</a>
        <a href="hospitalisation.php"><i class="fa-solid fa-bed-pulse"></i> Patients Admis</a>
        <a href="patients.php"><i class="fa-solid fa-hospital-user"></i> Patients</a>
        <a href="../traitement/list.php"><i class="fa-solid fa-file-prescription"></i> Traitements</a>
        <a href="suivis.php"><i class="fa-solid fa-calendar-check"></i> Consultations</a>
        <h3 style="font-weight: 800;">Analyse & Gestion</h3>
        <a href="../admission/statistique.php"><i class="fa-solid fa-chart-pie"></i> Statistiques</a>
        <a href="archives.php"><i class="fa-solid fa-box-archive"></i> Archives</a>
        <a href="profil_medcin.php" class="active"><i class="fa-solid fa-user-gear"></i> Profil</a>
        <div style="margin-top: 40px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 10px;">
            <a href="deconnexion.php" style="color: #fda4af;"><i class="fa-solid fa-power-off"></i> Déconnexion</a>
        </div>
    </aside>

    <main class="content">
        <div class="page-header">
            <h1>Paramètres du profil</h1>
            <p>Mettez à jour vos informations professionnelles et de contact.</p>
        </div>

        <div class="card">
            <?php if(!empty($form_message)): ?>
                <div class="message"><?= $form_message ?></div>
            <?php endif; ?>

            <form method="POST" id="profilForm">
                <div class="form-grid">
                    <div class="input-group">
                        <label>Prénom</label>
                        <input type="text" name="prenom" class="form-control" required value="<?= htmlspecialchars($medecin['prenom']) ?>">
                    </div>
                    
                    <div class="input-group">
                        <label>Nom</label>
                        <input type="text" name="nom" class="form-control" required value="<?= htmlspecialchars($medecin['nom']) ?>">
                    </div>

                    <div class="input-group full">
                        <label>Adresse Email Professionnelle</label>
                        <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($medecin['email']) ?>">
                    </div>

                    <div class="input-group full">
                        <label>Numéro de Téléphone</label>
                        <input type="tel" name="telephone" class="form-control" required value="<?= htmlspecialchars($medecin['telephone']) ?>">
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" name="modifier">
                        <i class="fas fa-check-circle"></i> Enregistrer les modifications
                    </button>
                    <a href="profil_medcin.php" class="btn-cancel">Annuler</a>
                </div>
            </form>
        </div>
    </main>
</div>

<script>
// Animation simple : masquer l'erreur lors de la saisie
document.querySelectorAll('#profilForm input').forEach(el => {
    el.addEventListener('input', () => {
        const msg = document.querySelector('.message');
        if(msg) {
            msg.style.opacity = '0';
            setTimeout(() => msg.style.display = 'none', 300);
        }
    });
});
</script>

</body>
</html>