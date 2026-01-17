<?php
include "../config/connexion.php";

if(!isset($_GET['id'])){
    header("Location: admissions_list.php");
    exit;
}

$id = $_GET['id'];

// Récupération des données
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

$medecins = $pdo->query("SELECT id_user, nom FROM utilisateurs WHERE role='medecin'")->fetchAll(PDO::FETCH_ASSOC);
$chambres = $pdo->query("SELECT id_chambre,numero_chambre  FROM chambres")->fetchAll(PDO::FETCH_ASSOC);

$success = '';
$error = '';

if(isset($_POST['update'])){
    // ... (votre logique PHP reste la même)
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
        $success = "Les modifications ont été enregistrées avec succès.";
        // Actualiser les données locales
        header("Refresh:2");
    }catch(PDOException $e){ $error = "Erreur: ".$e->getMessage(); }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Édition Admission | Système Hospitalier</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #01A28C;
            --primary-dark: #01907d;
            --bg-light: #f4f7f6;
            --text-dark: #1a202c;
            --sidebar-width: 260px;
        }

        body {
            background-color: var(--bg-light);
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--text-dark);
            overflow-x: hidden;
        }

        /* Sidebar Professionnelle */
        .sidebar {
            width: var(--sidebar-width);
            background: #ffffff;
            height: 100vh;
            position: fixed;
            border-right: 1px solid #e2e8f0;
            padding: 1.5rem;
            z-index: 100;
        }

        .brand-logo {
            color: var(--primary);
            font-weight: 800;
            font-size: 1.5rem;
            margin-bottom: 2.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-menu { list-style: none; padding: 0; }
        .nav-item { margin-bottom: 0.5rem; }
        .nav-link {
            display: flex;
            align-items: center;
            padding: 0.8rem 1rem;
            color: #64748b;
            text-decoration: none;
            border-radius: 12px;
            transition: 0.3s;
            font-weight: 500;
        }
        .nav-link i { width: 25px; font-size: 1.1rem; }
        .nav-link:hover, .nav-link.active {
            background: rgba(1, 162, 140, 0.1);
            color: var(--primary);
        }

        /* Contenu Principal */
        .main-wrapper {
            margin-left: var(--sidebar-width);
            padding: 2rem;
        }

        .top-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        /* Card Style */
        .glass-card {
            background: #ffffff;
            border: 1px solid rgba(226, 232, 240, 0.8);
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.02);
            padding: 2rem;
        }

        .patient-badge {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 1.5rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Form Controls Custom */
        .form-label {
            font-weight: 600;
            font-size: 0.9rem;
            color: #4a5568;
            margin-bottom: 0.6rem;
        }

        .form-control, .form-select {
            border: 1.5px solid #edf2f7;
            border-radius: 10px;
            padding: 0.75rem 1rem;
            background-color: #f8fafc;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(1, 162, 140, 0.1);
            background-color: #fff;
        }

        .btn-save {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 10px;
            font-weight: 600;
            transition: 0.3s;
        }

        .btn-save:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            color: white;
        }

        .input-icon-group {
            position: relative;
        }
        .input-icon-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary);
        }
        .input-icon-group .form-control, .input-icon-group .form-select {
            padding-left: 45px;
        }

        .status-pill {
            padding: 0.4rem 1rem;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
        }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="brand-logo">
            <i class="fa-solid fa-house-medical"></i>
            <span>MedSystem</span>
        </div>
        <ul class="nav-menu">
            <li class="nav-item"><a href="dashboard.php" class="nav-link"><i class="fa-solid fa-chart-pie"></i> Dashboard</a></li>
            <li class="nav-item"><a href="admissions_list.php" class="nav-link active"><i class="fa-solid fa-bed-pulse"></i> Admissions</a></li>
            <li class="nav-item"><a href="patients_list.php" class="nav-link"><i class="fa-solid fa-user-injured"></i> Patients</a></li>
            <li class="nav-item"><a href="#" class="nav-link"><i class="fa-solid fa-calendar-check"></i> Rendez-vous</a></li>
        </ul>
    </aside>

    <div class="main-wrapper">
        <header class="top-header">
            <div>
                <h2 class="fw-bold mb-0">Modifier le dossier</h2>
                <small class="text-muted">Gestion administrative des admissions</small>
            </div>
            <a href="admissions_list.php" class="btn btn-outline-secondary rounded-pill px-4">
                <i class="fa-solid fa-chevron-left me-2"></i>Retour
            </a>
        </header>

        <?php if($success): ?>
            <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4">
                <i class="fa-solid fa-circle-check me-2"></i> <?= $success ?>
            </div>
        <?php endif; ?>

        <div class="glass-card">
            <div class="patient-badge">
                <div>
                    <span class="text-uppercase small opacity-75">Dossier Patient</span>
                    <h3 class="mb-0 fw-bold"><?= htmlspecialchars($admission['nom'].' '.$admission['prenom']) ?></h3>
                    <div class="mt-2 small">
                        <span class="me-3"><i class="fa-solid fa-cake-candles me-1"></i> <?= $admission['date_naissance'] ?></span>
                        <span><i class="fa-solid fa-venus-mars me-1"></i> <?= $admission['sexe'] ?></span>
                    </div>
                </div>
                <div class="text-end">
                    <span class="d-block small opacity-75">ID Admission</span>
                    <span class="fs-4 fw-bold">#ADM-<?= $id ?></span>
                </div>
            </div>

            <form method="POST">
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label">Service Médical</label>
                        <div class="input-icon-group">
                            <i class="fa-solid fa-stethoscopes"></i>
                            <input type="text" name="service" class="form-control" value="<?= htmlspecialchars($admission['service']) ?>" required>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Médecin Référent</label>
                        <div class="input-icon-group">
                            <i class="fa-solid fa-user-doctor"></i>
                            <select name="medecin" class="form-select" required>
                                <option value="">Choisir un médecin</option>
                                <?php foreach($medecins as $m): ?>
                                    <option value="<?= $m['id_user'] ?>" <?= $admission['medecin_id']==$m['id_user']?'selected':'' ?>>
                                        Dr. <?= htmlspecialchars($m['nom']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Chambre / Lit</label>
                        <div class="input-icon-group">
                            <i class="fa-solid fa-door-open"></i>
                            <select name="chambre" class="form-select" required>
                                <?php foreach($chambres as $c): ?>
                                    <option value="<?= $c['id_chambre'] ?>" <?= $admission['id_chambre']==$c['id_chambre']?'selected':'' ?>>
                                        N° <?= htmlspecialchars($c['numero_chambre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Priorité d'Admission</label>
                        <div class="input-icon-group">
                            <i class="fa-solid fa-truck-medical"></i>
                            <select name="type_admission" class="form-select" required>
                                <option value="Normal" <?= $admission['type_admission']=='Normal'?'selected':'' ?>>Standard</option>
                                <option value="Urgent" <?= $admission['type_admission']=='Urgent'?'selected':'' ?>>Urgent</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Statut Actuel</label>
                        <div class="input-icon-group">
                            <i class="fa-solid fa-spinner"></i>
                            <select name="statut" class="form-select" required>
                                <option value="En cours" <?= $admission['statut']=='En cours'?'selected':'' ?>>Hospitalisé (En cours)</option>
                                <option value="Terminé" <?= $admission['statut']=='Terminé'?'selected':'' ?>>Sortie (Terminé)</option>
                                <option value="Annulé" <?= $admission['statut']=='Annulé'?'selected':'' ?>>Annulé</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Date de sortie prévue</label>
                        <div class="input-icon-group">
                            <i class="fa-solid fa-calendar-day"></i>
                            <input type="date" name="date_sortie" class="form-control" value="<?= $admission['date_sortie'] ?>">
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Motif Clinique</label>
                        <textarea name="motif" class="form-control" rows="4" placeholder="Saisir le motif détaillé..."><?= htmlspecialchars($admission['motif']) ?></textarea>
                    </div>

                    <div class="col-12 text-end mt-4">
                        <button type="submit" name="update" class="btn btn-save shadow">
                            <i class="fa-solid fa-floppy-disk me-2"></i>Enregistrer les modifications
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>