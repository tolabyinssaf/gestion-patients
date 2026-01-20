<?php
include "../config/connexion.php";

if(!isset($_GET['id'])){
    header("Location: admissions_list.php");
    exit;
}

$id = $_GET['id'];

// Récupération des données avec jointures
$stmt = $pdo->prepare("
    SELECT a.*, 
           p.nom, p.prenom, p.date_naissance, p.sexe, p.telephone, p.adresse, p.email,
           u.id_user AS medecin_id, u.nom AS medecin_nom,
           c.id_chambre, c.numero_chambre AS chambre_numero
    FROM admissions a
    JOIN patients p ON a.id_patient = p.id_patient
    LEFT JOIN utilisateurs u ON a.id_medecin = u.id_user
    LEFT JOIN chambres c ON a.id_chambre = c.id_chambre
    WHERE a.id_admission = ?
");
$stmt->execute([$id]);
$admission = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$admission) die("Admission introuvable");

$medecins = $pdo->query("SELECT id_user, nom, prenom FROM utilisateurs WHERE role='medecin' ORDER BY nom ASC")->fetchAll(PDO::FETCH_ASSOC);
$chambres = $pdo->query("SELECT id_chambre, numero_chambre FROM chambres ORDER BY numero_chambre ASC")->fetchAll(PDO::FETCH_ASSOC);

$success = '';
$error = '';

if(isset($_POST['update'])){
    $service = htmlspecialchars($_POST['service']);
    $statut = $_POST['statut'];
    $type_admission = $_POST['type_admission'];
    $motif = htmlspecialchars($_POST['motif']);
    $date_sortie = !empty($_POST['date_sortie']) ? $_POST['date_sortie'] : null;
    $medecin = $_POST['medecin'];
    $chambre = $_POST['chambre'];

    try{
        $stmt = $pdo->prepare("UPDATE admissions SET service=?, statut=?, date_sortie=?, type_admission=?, motif=?, id_medecin=?, id_chambre=? WHERE id_admission=?");
        $stmt->execute([$service, $statut, $date_sortie, $type_admission, $motif, $medecin, $chambre, $id]);
        header("Location: admissions_list.php");
        header("Refresh:2");
    }catch(PDOException $e){ 
        $error = "Erreur: ".$e->getMessage(); 
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>MedCare | Édition Admission</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

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
            --accent: #14b8a6; 
            --gradient-vert: linear-gradient(135deg, #0f172a, #1ca499ff);
        }

        * { margin:0; padding:0; box-sizing:border-box; font-family: 'Inter', sans-serif; }
        body { background: var(--bg-body); color: #1e293b; }

        header {
            background: var(--white);
            padding: 0 40px; 
            height: var(--header-height);
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid var(--border);
            position: fixed; top: 0; left: 0; right: 0; z-index: 1000;
        }

        .sidebar { 
            width: var(--sidebar-width); background: var(--sidebar-bg); padding: 24px 16px; 
            position: fixed; top: var(--header-height); left: 0; bottom: 0; z-index: 999;
        }
        .sidebar a { display: flex; align-items: center; gap: 12px; color: #94a3b8; text-decoration: none; padding: 12px 16px; border-radius: 10px; margin-bottom: 5px; transition: 0.2s; font-size: 14px; }
        .sidebar a:hover, .sidebar a.active { background: var(--primary); color: #fff; }

        .content { margin-left: var(--sidebar-width); margin-top: var(--header-height); padding: 40px; }

        .glass-card {
            background: var(--white);
            border-radius: 24px;
            border: 1px solid var(--border);
            box-shadow: 0 10px 40px rgba(0,0,0,0.04);
            padding: 40px;
            max-width: 1000px;
            margin: 0 auto;
        }

        .patient-badge-header {
            background: var(--gradient-vert);
            color: white;
            padding: 25px;
            border-radius: 20px;
            margin-bottom: 35px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .section-header {
            display: flex; align-items: center; gap: 15px;
            margin-bottom: 25px; padding-bottom: 12px;
            border-bottom: 1px solid var(--border);
        }

        .section-icon {
            width: 35px; height: 35px; background: var(--primary-light);
            color: var(--primary); border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
        }

        .form-label { font-weight: 600; font-size: 13px; color: #64748b; margin-bottom: 8px; }
        
        .form-control, .form-select {
            padding: 12px 16px; border-radius: 12px;
            border: 2px solid #f1f5f9; background: #f8fafc;
            font-weight: 500; transition: 0.3s;
        }

        .form-control:focus { border-color: var(--accent); background: #fff; box-shadow: none; }

        .btn-submit {
            background: var(--gradient-vert); border: none;
            padding: 14px 30px; border-radius: 12px;
            font-weight: 700; color: white; transition: 0.3s;
        }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(15, 118, 110, 0.2); }

        .alert-success { 
            background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534;
            border-radius: 15px; padding: 15px; margin-bottom: 20px;
        }
    </style>
</head>
<body>

<header>
    <img src="../images/logo_app2.png" alt="Logo" style="height: 45px;">
    <div class="d-flex align-items-center gap-3">
        <div style="background: var(--primary-light); padding: 8px 18px; border-radius: 12px; color: var(--primary); font-weight: 600; font-size: 14px;">
            <i class="fa-solid fa-user-pen me-2"></i>Mode Édition
        </div>
    </div>
</header>

<aside class="sidebar">
    <h3 style="color:rgba(255,255,255,0.3); font-size:11px; text-transform:uppercase; margin-bottom:20px; padding-left:12px;">Menu Gestion</h3>
    <a href="../connexion_secretaire/dashboard_secretaire.php"><i class="fa-solid fa-chart-line"></i> Vue Générale</a>
    <a href="../connexion_secretaire/patients_secr.php" ><i class="fa-solid fa-user-group"></i> Patients</a>
    <a href="admissions_list.php" class="active"><i class="fa-solid fa-hospital-user"></i> Admissions</a>
    <a href="../connexion_secretaire/suivis.php"><i class="fa-solid fa-calendar-check"></i> Suivis</a>
    <a href="../connexion_secretaire/caisse.php"><i class="fa-solid fa-wallet"></i> Caisse & Factures</a>
    <div style="height: 1px; background: rgba(255,255,255,0.1); margin: 20px 0;"></div>
    <a href="../connexio_utilisateur/deconnexion.php" style="color: #fda4af;"><i class="fa-solid fa-power-off"></i> Déconnexion</a>
</aside>

<main class="content">
    <div class="container-fluid">
        
        <?php if($success): ?>
            <div class="alert-success shadow-sm">
                <i class="fa-solid fa-circle-check me-2"></i> <?= $success ?>
            </div>
        <?php endif; ?>

        <div class="glass-card">
            <div class="patient-badge-header">
                <div>
                    <span style="text-transform: uppercase; font-size: 11px; opacity: 0.8; letter-spacing: 1px;">Dossier d'admission</span>
                    <h2 class="mb-0 fw-bold"><?= htmlspecialchars($admission['nom'].' '.$admission['prenom']) ?></h2>
                    <div class="mt-2" style="font-size: 13px; opacity: 0.9;">
                        <span class="me-3"><i class="fa-solid fa-cake-candles me-1"></i> <?= $admission['date_naissance'] ?></span>
                        <span><i class="fa-solid fa-id-card me-1"></i> #ADM-<?= $id ?></span>
                    </div>
                </div>
                <div class="text-end">
                    <a href="admissions_list.php" class="btn btn-sm btn-light rounded-pill px-3" style="color: var(--primary); font-weight: 600;">
                        <i class="fa-solid fa-arrow-left me-1"></i> Retour
                    </a>
                </div>
            </div>

            <form method="POST">
                <div class="section-header">
                    <div class="section-icon"><i class="fa-solid fa- stethoscope"></i></div>
                    <h5 class="mb-0 fw-bold" style="color: var(--primary); font-size: 16px;">Informations Médicales & Séjour</h5>
                </div>

                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label">Service Médical</label>
                        <input type="text" name="service" class="form-control" value="<?= htmlspecialchars($admission['service']) ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Médecin Responsable</label>
                        <select name="medecin" class="form-select" required>
                            <?php foreach($medecins as $m): ?>
                                <option value="<?= $m['id_user'] ?>" <?= $admission['medecin_id']==$m['id_user']?'selected':'' ?>>
                                    Dr. <?= htmlspecialchars($m['nom'].' '.$m['prenom']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Chambre Affectée</label>
                        <select name="chambre" class="form-select">
                            <option value="">-- Sans chambre --</option>
                            <?php foreach($chambres as $c): ?>
                                <option value="<?= $c['id_chambre'] ?>" <?= $admission['id_chambre']==$c['id_chambre']?'selected':'' ?>>
                                    N° <?= htmlspecialchars($c['numero_chambre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Priorité</label>
                        <select name="type_admission" class="form-select">
                            <option value="Normal" <?= $admission['type_admission']=='Normal'?'selected':'' ?>>Standard</option>
                            <option value="Urgent" <?= $admission['type_admission']=='Urgent'?'selected':'' ?>>Urgence</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Statut du Patient</label>
                        <select name="statut" class="form-select">
                            <option value="En cours" <?= $admission['statut']=='En cours'?'selected':'' ?>>Hospitalisé</option>
                            <option value="Terminé" <?= $admission['statut']=='Terminé'?'selected':'' ?>>Sortie effectuée</option>
                            <option value="Annulé" <?= $admission['statut']=='Annulé'?'selected':'' ?>>Annulé</option>
                        </select>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Date de sortie prévisionnelle</label>
                        <input type="date" name="date_sortie" class="form-control" value="<?= $admission['date_sortie'] ?>">
                    </div>

                    <div class="col-12">
                        <label class="form-label">Motif de l'admission / Observations</label>
                        <textarea name="motif" class="form-control" rows="4"><?= htmlspecialchars($admission['motif']) ?></textarea>
                    </div>

                    <div class="col-12 text-end mt-5">
                        <button type="submit" name="update" class="btn btn-submit">
                            <i class="fa-solid fa-floppy-disk me-2"></i> Mettre à jour le dossier
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>