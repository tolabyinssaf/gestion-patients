<?php
session_start();
include("../config/connexion.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'medecin') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT nom, prenom, email FROM utilisateurs WHERE id_user = ?");
$stmt->execute([$user_id]);
$medecin = $stmt->fetch(PDO::FETCH_ASSOC);

$form_message = "";

if (isset($_POST['ajouter'])) {
    $cin = strtoupper($_POST['cin']); 
    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $date_naissance = $_POST['date_naissance'];
    $sexe = $_POST['sexe'] ?? 'H';
    $adresse = $_POST['adresse'];
    $telephone = $_POST['telephone'];
    $email = $_POST['email'];
    // NOUVEAUX CHAMPS
    $groupe_sanguin = $_POST['groupe_sanguin'];
    $statut = $_POST['statut'] ?? 'Stable';
    $allergies = $_POST['allergies'];
    
    $date_inscription = date('Y-m-d');

    try {
        // Mise à jour de l'appel SQL (Assurez-vous que votre procédure "ajouter_patient" accepte ces 3 nouveaux paramètres)
        // Sinon, utilisez une requête INSERT directe ci-dessous :
        $sql = "INSERT INTO patients (cin, nom, prenom, date_naissance, sexe, adresse, telephone, email, groupe_sanguin, statut, allergies, date_inscription, id_medecin) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$cin, $nom, $prenom, $date_naissance, $sexe, $adresse, $telephone, $email, $groupe_sanguin, $statut, $allergies, $date_inscription, $user_id]);
        
        header("Location: patients.php?success=1");
        exit;
    } catch (PDOException $e) {
        if ($e->errorInfo[1] == 1062) {
            $form_message = "CIN ou Email déjà utilisé par un autre patient.";
        } else {
            $form_message = "Erreur lors de l'enregistrement : " . $e->getMessage();
        }
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
            --error: #dc2626;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', "Segoe UI", sans-serif; }
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

        .container { display: flex; min-height: calc(100vh - 75px); }

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

        .content { flex: 1; padding: 40px; max-width: 1000px; margin: 0 auto; }
        .breadcrumb { font-size: 14px; color: var(--text-muted); margin-bottom: 8px; }
        .content h1 { font-size: 28px; color: #1e293b; margin-bottom: 30px; }

        .card { 
            background: var(--white); 
            padding: 35px; 
            border-radius: 12px; 
            border: 1px solid var(--border);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        .card-header { margin-bottom: 25px; border-bottom: 1px solid #f1f5f9; padding-bottom: 15px; }
        .card-header h2 { font-size: 18px; color: var(--primary); }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .full-width { grid-column: span 2; }

        .field-group { display: flex; flex-direction: column; gap: 8px; }
        .field-group label { font-size: 14px; font-weight: 600; color: #475569; }

        .form-control { 
            width: 100%; 
            padding: 12px 16px; 
            border: 1px solid var(--border); 
            border-radius: 8px; 
            font-size: 14px;
            transition: border 0.2s;
            background: #fcfcfc;
        }
        .form-control:focus { outline: none; border-color: var(--primary); background: #fff; }

        .btn-submit { 
            background: var(--primary); 
            color: white; 
            padding: 14px 28px; 
            border: none; 
            border-radius: 8px; 
            font-weight: 600; 
            cursor: pointer; 
            margin-top: 20px;
            transition: 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
            justify-content: center;
            width: 100%;
        }
        .btn-submit:hover { background: var(--primary-hover); transform: translateY(-1px); }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            background: #fef2f2;
            color: var(--error);
            border: 1px solid #fee2e2;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title {
            grid-column: span 2;
            font-size: 14px;
            text-transform: uppercase;
            color: var(--text-muted);
            letter-spacing: 1px;
            margin-top: 15px;
            border-bottom: 1px dashed var(--border);
            padding-bottom: 5px;
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
        <h3>Menu Médical</h3>
        <a href="dashboard_medecin.php"><i class="fa-solid fa-chart-pie"></i> Tableau de bord</a>
        <a href="patients.php" class="active"><i class="fa-solid fa-user-group"></i> Mes Patients</a>
        <a href="suivis.php"><i class="fa-solid fa-file-medical"></i> Consultations</a>
        <a href="../traitement/list.php"><i class="fa-solid fa-pills"></i> Traitements</a>
        <div style="height: 1px; background: rgba(255,255,255,0.1); margin: 20px 0;"></div>
        <a href="deconnexion.php" style="color: #fda4af;"><i class="fa-solid fa-power-off"></i> Déconnexion</a>
    </aside>

    <main class="content">
        <div class="breadcrumb">Gestion des patients / Nouveau</div>
        <h1>Ajouter un patient</h1>

        <div class="card">
            <div class="card-header">
                <h2><i class="fa-solid fa-address-card"></i> Fiche d'admission</h2>
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
                        <label>CIN</label>
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
                        <input type="text" name="nom" class="form-control" required>
                    </div>
                    <div class="field-group">
                        <label>Prénom</label>
                        <input type="text" name="prenom" class="form-control" required>
                    </div>

                    <div class="field-group">
                        <label>Date de naissance</label>
                        <input type="date" name="date_naissance" class="form-control" required>
                    </div>
                    <div class="field-group">
                        <label>Téléphone</label>
                        <input type="text" name="telephone" placeholder="06XXXXXXXX" class="form-control" required>
                    </div>

                    <div class="field-group full-width">
                        <label>Email</label>
                        <input type="email" name="email" placeholder="patient@exemple.com" class="form-control" required>
                    </div>

                    <div class="section-title">Informations Médicales</div>

                    <div class="field-group">
                        <label>Groupe Sanguin</label>
                        <select name="groupe_sanguin" class="form-control">
                            <option value="">Inconnu</option>
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
                        <label>Allergies connues</label>
                        <textarea name="allergies" class="form-control" rows="2" placeholder="Ex: Pénicilline, Pollen... (Laissez vide si aucune)"></textarea>
                    </div>

                    <div class="field-group full-width">
                        <label>Adresse complète</label>
                        <input type="text" name="adresse" class="form-control" required>
                    </div>
                </div>

                <button type="submit" name="ajouter" class="btn-submit">
                    <i class="fa-solid fa-plus-circle"></i> Créer le dossier patient
                </button>
            </form>
        </div>
    </main>
</div>

<script>
document.querySelectorAll('.form-control').forEach(input => {
    input.addEventListener('input', () => {
        const alert = document.getElementById('errorAlert');
        if(alert) alert.style.display = 'none';
    });
});
</script>

</body>
</html>