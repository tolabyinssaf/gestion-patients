<?php
session_start();
include("../config/connexion.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'medecin') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Infos médecin
$stmt = $pdo->prepare("SELECT nom, prenom, email FROM utilisateurs WHERE id_user = ?");
$stmt->execute([$user_id]);
$medecin = $stmt->fetch(PDO::FETCH_ASSOC);

$form_message = "";

// =========================
// AJOUT PATIENT (PROCÉDURE)
// =========================
if (isset($_POST['ajouter'])) {
    $cin = strtoupper(trim($_POST['cin']));
    $nom = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $date_naissance = $_POST['date_naissance'];
    $sexe = $_POST['sexe'] ?? 'H';
    $adresse = trim($_POST['adresse']);
    $telephone = trim($_POST['telephone']);
    $email = trim($_POST['email']);
    $groupe_sanguin = $_POST['groupe_sanguin'];
    $statut = $_POST['statut'] ?? 'Stable';
    $allergies = $_POST['allergies'];
    $date_inscription = date('Y-m-d');

    try {
        $stmt = $pdo->prepare("CALL ajouter_patient_secure(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, @msg)");
        $stmt->execute([$cin, $nom, $prenom, $date_naissance, $sexe, $adresse, $telephone, $email, $groupe_sanguin, $statut, $allergies, $date_inscription, $user_id]);
        $result = $pdo->query("SELECT @msg AS message")->fetch(PDO::FETCH_ASSOC);

        if ($result && $result['message'] === 'OK') {
            header("Location: patients.php?success=1");
            exit;
        } else {
            $form_message = $result['message'] ?? "Erreur inconnue";
        }
    } catch (PDOException $e) {
        $form_message = "Erreur serveur : " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter Patient | MedCare</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0f766e;
            --primary-light: #f0fdfa;
            --primary-hover: #115e59;
            --sidebar-bg: #0f172a;
            --bg-body: #f1f5f9;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --white: #ffffff;
            --border: #cbd5e1;
            --input-bg: #ffffff;
            --error: #dc2626;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: var(--bg-body); color: var(--text-main); }

        /* HEADER FIXE */
        header {
            background: var(--white);
            padding: 0 40px; height: 75px;
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid var(--border);
            position: fixed; top: 0; width: 100%; z-index: 1000;
        }
        .logo { height: 45px; }
        .user-pill {
            background: var(--primary-light); padding: 8px 18px; border-radius: 12px;
            display: flex; align-items: center; gap: 10px;
            font-size: 14px; font-weight: 600; color: var(--primary);
            border: 1px solid rgba(15, 118, 110, 0.2);
        }

        /* CONTAINER AVEC SIDEBAR FIXE */
        .container { display: flex; padding-top: 75px; }
        
        .sidebar { 
            width: 260px; 
            background: var(--sidebar-bg); 
            padding: 24px 16px; 
            flex-shrink: 0; 
            position: fixed; 
            height: calc(100vh - 75px);
            overflow-y: auto;
        }
        .sidebar h3 { color: rgba(255,255,255,0.3); font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 20px; padding-left: 12px; }
        .sidebar a { display: flex; align-items: center; gap: 12px; color: #94a3b8; text-decoration: none; padding: 12px 16px; border-radius: 10px; margin-bottom: 5px; transition: 0.2s; }
        .sidebar a:hover { background: rgba(255,255,255,0.05); color: #fff; }
        .sidebar a.active { background: var(--primary); color: #fff; }

        /* CONTENT AJUSTÉ ET CENTRÉ */
        .content { flex: 1; padding: 40px; margin-left: 260px; }
        .breadcrumb { font-size: 13px; font-weight: 600; color: var(--text-muted); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
        .content h1 { font-size: 30px; font-weight: 800; color: var(--sidebar-bg); margin-bottom: 30px; text-align: center; }

        /* CARD CENTRÉE ET COULEUR VERT DANS HEADER */
        .card { 
            background: #f8fafc; 
            padding: 0; 
            border-radius: 20px; 
            border: 2px solid var(--primary); /* Bordure en accord avec le vert */
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.1);
            overflow: hidden;
            max-width: 850px; /* Limite la largeur pour un meilleur aspect visuel */
            margin: 0 auto;  /* CENTRE LA CARTE HORIZONTALEMENT */
        }
        .card-header { 
            background: var(--primary); /* CHANGÉ EN VERT */
            padding: 20px 40px; 
            margin-bottom: 0;
            display: flex; 
            align-items: center; 
            gap: 15px; 
        }
        .card-header h2 { font-size: 20px; color: #fff; font-weight: 700; }
        .card-header i { color: #fff !important; } /* Icône en blanc pour le contraste */

        form { padding: 40px; }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; }
        .full-width { grid-column: span 2; }

        .field-group { display: flex; flex-direction: column; gap: 8px; }
        .field-group label { font-size: 14px; font-weight: 800; color: var(--sidebar-bg); }

        .form-control { 
            width: 100%; 
            padding: 14px 16px; 
            border: 2px solid #cbd5e1;
            border-radius: 12px; 
            font-size: 15px;
            font-weight: 600;
            color: var(--sidebar-bg);
            transition: all 0.3s ease;
            background: var(--white);
        }
        .form-control:focus { 
            outline: none; 
            border-color: var(--primary); 
            box-shadow: 0 0 0 4px rgba(15, 118, 110, 0.1);
        }

        .btn-submit { 
            background: var(--sidebar-bg); 
            color: white; 
            padding: 16px 30px; 
            border: none; 
            border-radius: 12px; 
            font-weight: 700; 
            font-size: 16px;
            cursor: pointer; 
            margin-top: 25px;
            transition: all 0.3s ease;
            display: flex; align-items: center; gap: 12px; justify-content: center;
            width: 100%;
        }
        .btn-submit:hover { 
            background: var(--primary); 
            transform: translateY(-2px);
        }

        .section-title {
            grid-column: span 2;
            font-size: 13px; font-weight: 800;
            text-transform: uppercase; color: var(--primary);
            letter-spacing: 1.2px; margin-top: 20px;
            display: flex; align-items: center; gap: 10px;
            padding-bottom: 5px;
            border-bottom: 2px solid #e2e8f0;
        }

        .alert {
            margin: 20px 40px 0 40px;
            padding: 15px 20px; border-radius: 12px;
            background: #fee2e2; color: var(--error);
            border-left: 5px solid var(--error);
            font-size: 14px; font-weight: 600;
            display: flex; align-items: center; gap: 12px;
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
        <a href="patients.php" class="active"><i class="fa-solid fa-hospital-user"></i> Patients</a>
        <a href="../traitement/list.php"><i class="fa-solid fa-file-prescription"></i> Traitements</a>
        <a href="suivis.php"><i class="fa-solid fa-calendar-check"></i> Consultations</a>
        <h3 style="font-weight: 800;">Analyse & Gestion</h3>
        <a href="../admission/statistique.php"><i class="fa-solid fa-chart-pie"></i> Statistiques</a>
        <a href="archives.php"><i class="fa-solid fa-box-archive"></i> Archives</a>
        <a href="profil_medcin.php"><i class="fa-solid fa-user-gear"></i> Profil</a>
        <div style="margin-top: 40px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 10px;">
            <a href="deconnexion.php" style="color: #fda4af;"><i class="fa-solid fa-power-off"></i> Déconnexion</a>
        </div>
    </aside>

    <main class="content">
        <div class="breadcrumb" style="text-align:center;">Gestion des patients / Nouveau Dossier</div>
        <h1>Inscrire un nouveau patient</h1>

        <div class="card">
            <div class="card-header">
                <i class="fa-solid fa-id-card-alt" style="font-size: 24px;"></i>
                <h2>Fiche d'identification</h2>
            </div>

            <?php if(!empty($form_message)): ?>
                <div class="alert" id="errorAlert">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <?= $form_message ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="patientForm">
                <div class="form-grid">
                    <div class="field-group">
                        <label>CIN (Carte d'Identité)</label>
                        <input type="text" name="cin" placeholder="Ex: AB12345" class="form-control" required>
                    </div>
                    <div class="field-group">
                        <label>Sexe</label>
                        <select name="sexe" class="form-control" required>
                            <option value="H">Homme</option>
                            <option value="F">Femme</option>
                        </select>
                    </div>

                    <div class="field-group">
                        <label>Nom</label>
                        <input type="text" name="nom" placeholder="Nom du patient" class="form-control" required>
                    </div>
                    <div class="field-group">
                        <label>Prénom</label>
                        <input type="text" name="prenom" placeholder="Prénom du patient" class="form-control" required>
                    </div>

                    <div class="field-group">
                        <label>Date de naissance</label>
                        <input type="date" name="date_naissance" class="form-control" required>
                    </div>
                    <div class="field-group">
                        <label>Téléphone portable</label>
                        <input type="text" name="telephone" placeholder="06XXXXXXXX" class="form-control" required>
                    </div>

                    <div class="field-group full-width">
                        <label>Adresse Email</label>
                        <input type="email" name="email" placeholder="patient@exemple.com" class="form-control" required>
                    </div>

                    <div class="section-title">Informations Médicales</div>

                    <div class="field-group">
                        <label>Groupe Sanguin</label>
                        <select name="groupe_sanguin" class="form-control">
                            <option value="">Sélectionner le groupe</option>
                            <option value="A+">A+</option>
                            <option value="A-">A-</option>
                            <option value="B+">B+</option>
                            <option value="B-">B-</option>
                            <option value="AB+">AB+</option>
                            <option value="AB-">AB-</option>
                            <option value="O+">O+</option>
                            <option value="O-">O-</option>
                        </select>
                    </div>

                    <div class="field-group">
                        <label>Statut Initial</label>
                        <select name="statut" class="form-control">
                            <option value="Stable">Stable</option>
                            <option value="En observation">En observation</option>
                            <option value="Urgent">Urgent</option>
                            <option value="Critique">Critique</option>
                        </select>
                    </div>

                    <div class="field-group full-width">
                        <label>Allergies & Antécédents</label>
                        <textarea name="allergies" class="form-control" rows="3" placeholder="Notez ici les allergies connues ou laissez vide..."></textarea>
                    </div>

                    <div class="field-group full-width">
                        <label>Adresse de résidence complète</label>
                        <input type="text" name="adresse" placeholder="N°, Rue, Quartier, Ville" class="form-control" required>
                    </div>
                </div>

                <button type="submit" name="ajouter" class="btn-submit">
                    <i class="fa-solid fa-save"></i> Enregistrer le Dossier Patient
                </button>
            </form>
        </div>
    </main>
</div>

<script>
document.querySelectorAll('.form-control').forEach(input => {
    input.addEventListener('input', () => {
        const alert = document.getElementById('errorAlert');
        if(alert) {
            alert.style.transition = '0.3s';
            alert.style.opacity = '0';
            setTimeout(() => alert.style.display = 'none', 300);
        }
    });
});
</script>

</body>
</html>