<?php
session_start();
include("../config/connexion.php");

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

        // Vérifier doublon CIN
        $check = $pdo->prepare("SELECT id_patient FROM patients WHERE cin = ?");
        $check->execute([$cin]);

        if ($check->rowCount() > 0) {
            $message = "Ce numéro de CIN est déjà enregistré.";
            $pdo->rollBack();
        } else {

            // ✅ INSERT PATIENT SEULEMENT
            $stmt = $pdo->prepare("
                INSERT INTO patients 
                (nom, prenom, cin, telephone, email, adresse, date_naissance, sexe, date_inscription)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");

            $stmt->execute([
                $nom,
                $prenom,
                $cin,
                $telephone,
                $email,
                $adresse,
                $date_naissance,
                $sexe
            ]);

            $pdo->commit();

            header("Location: dashboard_secretaire.php?patient_added=1");
            exit;
        }

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $message = "Erreur lors de l’enregistrement.";
    }
}
?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>MedCare | Admission Patient</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
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

        /* Conservation de votre police et base */
        * { margin:0; padding:0; box-sizing:border-box; font-family: 'Plus Jakarta Sans', 'Inter', sans-serif; }
        body { background: var(--bg-body); color: #1e293b; }

        /* HEADER ET SIDEBAR INCHANGÉS (STRUCTURE) */
        header {
            background: var(--white); padding: 0 40px; height: var(--header-height);
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid var(--border); position: fixed; top: 0; left: 0; right: 0; z-index: 1000;
        }
        .logo { height: 45px; }
        .sidebar { 
            width: var(--sidebar-width); background: var(--sidebar-bg); padding: 24px 16px; 
            position: fixed; top: var(--header-height); left: 0; bottom: 0; z-index: 999;
        }
        .sidebar a { display: flex; align-items: center; gap: 12px; color: #94a3b8; text-decoration: none; padding: 12px 16px; border-radius: 10px; margin-bottom: 5px; }
        .sidebar a.active { background: var(--primary); color: #fff; }
        .content { margin-left: var(--sidebar-width); margin-top: var(--header-height); padding: 40px; }
        .user-pill { background: var(--primary-light); padding: 8px 18px; border-radius: 12px; display: flex; align-items: center; gap: 10px; font-size: 14px; font-weight: 600; color: var(--primary); }

        /* --- AMÉLIORATION DU FORMULAIRE UNIQUEMENT --- */
        .form-container { max-width: 850px; margin: auto; }
        
        .doctor-dest-card {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: white; padding: 14px; border-radius: 18px;
            display: flex; align-items: center; gap: 20px;
            margin-bottom: 30px; border: 1px solid rgba(255,255,255,0.1);
        }
        .doc-icon-circle {
            width: 55px; height: 55px; background: var(--primary);
            border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 22px;
            box-shadow: 0 8px 15px rgba(15, 118, 110, 0.3);
        }

        .form-card { 
            background: white; padding: 40px; border-radius: 24px; 
            border: 1px solid var(--border);
            box-shadow: 0 10px 30px rgba(0,0,0,0.02);
        }

        .section-header {
            display: flex; align-items: center; gap: 10px;
            margin-bottom: 25px; padding-bottom: 12px;
            border-bottom: 2px solid #f8fafc;
            color: #0f172a; font-weight: 700;
        }
        
        .input-group-text { 
            background: #f8fafc; border-color: #e2e8f0; color: #94a3b8;
            padding-left: 15px; border-radius: 12px 0 0 12px !important;
        }
        .form-control, .form-select { 
            border-color: #e2e8f0; padding: 12px 15px; font-size: 15px;
            border-radius: 0 12px 12px 0 !important;
            transition: all 0.2s ease;
        }
        .form-control:focus, .form-select:focus { 
            border-color: var(--primary); box-shadow: 0 0 0 4px rgba(15, 118, 110, 0.1);
            background-color: #fff;
        }
        
        label { font-weight: 600; color: #64748b; font-size: 13px; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;}

        .btn-save { 
           background: linear-gradient(135deg, #1e293b 0%, #0f766e 100%); color: white; border: none; padding: 16px; 
            border-radius: 14px; font-weight: 700; width: 100%; transition: 0.3s;
            text-transform: uppercase; letter-spacing: 1px; font-size: 16px;
            box-shadow: 0 10px 20px rgba(15, 118, 110, 0.2);
        }
        .btn-save:hover {            background: linear-gradient(135deg, #0f766e 0%, #1e293b 100%); transform: translateY(-2px); box-shadow: 0 15px 25px rgba(15, 118, 110, 0.3); }
        
    </style>
</head>
<body>

<header>
    <img src="../images/logo_app2.png" alt="Logo" class="logo">
    <div class="user-pill">
        <i class="fa-solid fa-circle-user"></i>
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
        <div class="form-container">
            <div class="mb-4 d-flex justify-content-between align-items-center">
                <div>
                    <a href="dashboard_secretaire.php" class="text-decoration-none text-muted small fw-bold"><i class="fa-solid fa-arrow-left me-1"></i> ANNULER</a>
                   
                </div>
                <div class="text-end">
                    <span class="badge bg-white text-success border border-success-subtle rounded-pill px-3 py-2">Patient #ID-NEW</span>
                </div>
            </div>

            <div class="doctor-dest-card">
                <div class="doc-icon-circle">
                    <i class="fa-solid fa-user-doctor"></i>
                </div>
                <div>
                    <p class="mb-0 small opacity-50 text-uppercase fw-bold" style="font-size: 10px; letter-spacing: 1px;">Médecin Traitant</p>
                    <h5 class="mb-0 fw-bold">Dr. <?= htmlspecialchars($medecin_info['nom']." ".$medecin_info['prenom']) ?></h5>
                    <span class="small" style="color: #2dd4bf;"><i class="fa-solid fa-tags me-1"></i> <?= htmlspecialchars($medecin_info['specialite']) ?></span>
                </div>
                <div class="ms-auto d-none d-sm-block">
                    <i class="fa-solid fa-shield-heart fa-2x opacity-25"></i>
                </div>
            </div>

            <?php if($message): ?>
                <div class="alert alert-danger border-0 shadow-sm rounded-4 mb-4 d-flex align-items-center">
                    <i class="fa-solid fa-circle-exclamation fs-4 me-3"></i> <?= $message ?>
                </div>
            <?php endif; ?>

            <div class="form-card">
                <form action="" method="POST">
                    <div class="section-header">
                        <i class="fa-solid fa-address-card text-primary"></i> Dossier Patient
                    </div>
                    
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label>Nom</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
                                <input type="text" name="nom" class="form-control" required placeholder="Ex: ALAOUI">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label>Prénom</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
                                <input type="text" name="prenom" class="form-control" required placeholder="Ex: Ahmed">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label>N° CIN</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-id-badge"></i></span>
                                <input type="text" name="cin" class="form-control" required placeholder="AB123456">
                            </div>
                        </div>
                        <div class="col-md-6">
    <label>Adresse Email</label>
    <div class="input-group">
        <span class="input-group-text"><i class="fa-solid fa-envelope"></i></span>
        <input type="email" name="email" class="form-control" placeholder="exemple@mail.com">
    </div>
</div>

<div class="col-md-6">
    <label>Téléphone Mobile</label>
    <div class="input-group">
        <span class="input-group-text"><i class="fa-solid fa-mobile-screen"></i></span>
        <input type="text" name="telephone" class="form-control" required placeholder="06 00 00 00 00">
    </div>
</div>

<div class="col-12">
    <label>Adresse Résidentielle</label>
    <div class="input-group">
        <span class="input-group-text"><i class="fa-solid fa-location-dot"></i></span>
        <input type="text" name="adresse" class="form-control" placeholder="N° Rue, Quartier, Ville">
    </div>
</div>
                        <div class="col-md-6">
                            <label>Date de Naissance</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-calendar-day"></i></span>
                                <input type="date" name="date_naissance" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label>Sexe</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-venus-mars"></i></span>
                                <select name="sexe" class="form-select" required>
                                    <option value="" selected disabled>Choisir...</option>
                                    <option value="M">Masculin</option>
                                    <option value="F">Féminin</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-12 mt-5">
                            <button type="submit" class="btn-save">
                                <i class="fa-solid fa-folder-plus me-2"></i> Enregistrer
                            </button>
                            <div class="text-center mt-3">
                                <small class="text-muted"><i class="fa-solid fa-lock me-1"></i> Connexion sécurisée SSL - Certifié MedCare</small>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

</body>
</html>