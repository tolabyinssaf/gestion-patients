<?php
// --- VOTRE CODE PHP ORIGINAL (NON MODIFIÉ) ---
require_once '../config/connexion.php';

$id = $_GET['id'];

// Récupérer le traitement avec la vue
$stmt = $pdo->prepare("SELECT * FROM v_traitements_complets WHERE id_traitement = ?");
$stmt->execute([$id]);
$traitement = $stmt->fetch();

if(!$traitement) {
    die("<div class='alert alert-error'><i class='fas fa-exclamation-circle'></i> Traitement non trouvé !</div>");
}

if(isset($_POST['submit'])) {
    try {
        $pdo->beginTransaction();
        
        $id_patient = $_POST['id_patient'];
        $description = $_POST['description'];
        $date_traitement = $_POST['date_traitement'];
        $medicament = $_POST['medicament'];
        $suivi = $_POST['suivi'];
        
        // Appel de la procédure stockée pour la modification
        $stmt = $pdo->prepare("CALL sp_modifier_traitement(?, ?, ?, ?, ?, ?, @success, @message)");
        $stmt->execute([$id, $id_patient, $description, $date_traitement, $medicament, $suivi]);
        
        // Récupérer les résultats
        $result = $pdo->query("SELECT @success as success, @message as message")->fetch();
        
        if($result['success']) {
            $pdo->commit();
            $success = true;
            $message = $result['message'];
            
            // Rafraîchir les données
            $stmt = $pdo->prepare("SELECT * FROM v_traitements_complets WHERE id_traitement = ?");
            $stmt->execute([$id]);
            $traitement = $stmt->fetch();
        } else {
            $pdo->rollBack();
            $success = false;
            $error = $result['message'];
        }
        
    } catch(PDOException $e) {
        $pdo->rollBack();
        $success = false;
        $error = "Erreur: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Traitement | MediApp</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --sidebar-width: 260px;
            --primary: #01A28C;
            --primary-dark: #008976;
            --bg-body: #f4f7f6;
            --text-main: #2c3e50;
            --text-light: #7f8c8d;
            --white: #ffffff;
            --shadow: 0 2px 10px rgba(0,0,0,0.05);
            --radius: 10px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--bg-body); color: var(--text-main); display: flex; min-height: 100vh; }

        /* Sidebar Navigation */
        .sidebar { width: var(--sidebar-width); background: #1a252f; color: white; position: fixed; height: 100vh; padding: 20px 0; z-index: 100; }
        .logo { padding: 0 25px 30px; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 20px; font-size: 22px; font-weight: 700; color: var(--primary); display: flex; align-items: center; gap: 10px; }
        .nav-links { list-style: none; }
        .nav-links li a { display: flex; align-items: center; gap: 12px; padding: 12px 25px; color: #bdc3c7; text-decoration: none; transition: 0.3s; font-weight: 500; }
        .nav-links li a:hover, .nav-links li.active a { background: rgba(255,255,255,0.05); color: var(--primary); border-left: 4px solid var(--primary); }

        /* Main Content */
        .main-content { margin-left: var(--sidebar-width); flex: 1; padding: 0 0 40px 0; width: calc(100% - var(--sidebar-width)); }

        /* Top Header */
        .top-header { background: var(--white); height: 70px; display: flex; align-items: center; justify-content: space-between; padding: 0 40px; box-shadow: var(--shadow); margin-bottom: 30px; position: sticky; top: 0; z-index: 99; }
        .user-profile { display: flex; align-items: center; gap: 10px; }
        .user-avatar { width: 35px; height: 35px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; }

        /* Form & Cards */
        .container { max-width: 1000px; margin: 0 auto; padding: 0 20px; }
        .page-header { margin-bottom: 25px; }
        .card { background: var(--white); border-radius: var(--radius); box-shadow: var(--shadow); padding: 25px; margin-bottom: 25px; border: 1px solid #eef2f3; }
        .section-title { font-size: 16px; font-weight: 600; color: var(--primary); margin-bottom: 20px; display: flex; align-items: center; gap: 10px; text-transform: uppercase; letter-spacing: 0.5px; }
        
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .full-width { grid-column: span 2; }

        label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px; }
        input, select, textarea { width: 100%; padding: 12px; border: 1px solid #dcdde1; border-radius: 6px; font-size: 14px; transition: 0.3s; }
        input:focus, textarea:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(1, 162, 140, 0.1); }
        
        .info-strip { background: #e8f6f3; padding: 15px 20px; border-radius: 8px; display: flex; justify-content: space-between; margin-bottom: 25px; border-left: 4px solid var(--primary); }
        .info-box span { display: block; font-size: 12px; color: var(--text-light); }
        .info-box strong { font-size: 14px; color: var(--text-main); }

        /* Buttons */
        .btn-group { display: flex; gap: 15px; margin-top: 10px; }
        .btn { padding: 12px 25px; border-radius: 6px; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 8px; border: none; transition: 0.3s; }
        .btn-save { background: #f39c12; color: white; }
        .btn-save:hover { background: #e67e22; transform: translateY(-2px); }
        .btn-cancel { background: #95a5a6; color: white; text-decoration: none; }

        /* Alerts */
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; font-weight: 500; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        @media (max-width: 768px) {
            .sidebar { width: 70px; }
            .sidebar .logo span, .nav-links span { display: none; }
            .main-content { margin-left: 70px; width: calc(100% - 70px); }
            .form-grid { grid-template-columns: 1fr; }
            .full-width { grid-column: span 1; }
        }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="logo">
            <i class="fas fa-heartbeat"></i> <span>MediApp</span>
        </div>
        <ul class="nav-links">
            <li><a href="dashboard.php"><i class="fas fa-chart-line"></i> <span>Tableau de bord</span></a></li>
            <li><a href="patients.php"><i class="fas fa-user-injured"></i> <span>Patients</span></a></li>
            <li class="active"><a href="list.php"><i class="fas fa-file-medical"></i> <span>Traitements</span></a></li>
            <li><a href="planning.php"><i class="fas fa-calendar-alt"></i> <span>Rendez-vous</span></a></li>
            <li><a href="settings.php"><i class="fas fa-cog"></i> <span>Paramètres</span></a></li>
        </ul>
    </aside>

    <main class="main-content">
        <header class="top-header">
            <div class="search-bar">
                <span style="color: var(--text-light)">Saisie de dossier médical</span>
            </div>
            <div class="user-profile">
                <div class="user-info" style="text-align: right">
                    <div style="font-weight: 600; font-size: 14px;">Dr. Jean Dupont</div>
                    <div style="font-size: 12px; color: var(--text-light)">Médecin Généraliste</div>
                </div>
                <div class="user-avatar">JD</div>
            </div>
        </header>

        <div class="container">
            <div class="page-header">
                <h2 style="font-size: 24px; margin-bottom: 5px;">Modifier le Traitement</h2>
                <p style="color: var(--text-light); font-size: 14px;">Mise à jour du dossier #<?= str_pad($traitement['id_traitement'], 4, '0', STR_PAD_LEFT) ?></p>
            </div>

            <?php if(isset($success)): ?>
                <div class="alert <?= $success ? 'alert-success' : 'alert-error' ?>">
                    <i class="fas <?= $success ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                    <?= $success ? $message : $error ?>
                </div>
            <?php endif; ?>

            <div class="info-strip">
                <div class="info-box">
                    <span>Patient</span>
                    <strong><?= htmlspecialchars($traitement['nom'] . ' ' . $traitement['prenom']) ?></strong>
                </div>
                <div class="info-box">
                    <span>Âge</span>
                    <strong><?= $traitement['age'] ?> ans</strong>
                </div>
                <div class="info-box">
                    <span>Créé le</span>
                    <strong><?= date('d/m/Y', strtotime($traitement['date_creation'])) ?></strong>
                </div>
                <div class="info-box">
                    <span>Historique</span>
                    <strong><?= $traitement['total_traitements'] ?> traitement(s)</strong>
                </div>
            </div>

            <form method="POST" id="editTraitementForm">
                <div class="card">
                    <h3 class="section-title"><i class="fas fa-info-circle"></i> Informations Générales</h3>
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label class="required">Sélectionner le Patient</label>
                            <select name="id_patient" required>
                                <?php
                                $stmt_p = $pdo->query("SELECT * FROM patients ORDER BY nom, prenom");
                                while($patient = $stmt_p->fetch()):
                                    $selected = ($patient['id_patient'] == $traitement['id_patient']) ? "selected" : "";
                                ?>
                                <option value="<?= $patient['id_patient'] ?>" <?= $selected ?>>
                                    <?= htmlspecialchars($patient['nom'] . ' ' . $patient['prenom']) ?> (<?= $patient['date_naissance'] ?>)
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="required">Date du traitement</label>
                            <input type="date" name="date_traitement" value="<?= $traitement['date_traitement'] ?>" required max="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="form-group">
                            <label>Médicament prescrit</label>
                            <input type="text" name="medicament" value="<?= htmlspecialchars($traitement['medicament']) ?>" placeholder="Ex: Paracétamol 500mg">
                        </div>
                    </div>
                </div>

                <div class="card">
                    <h3 class="section-title"><i class="fas fa-notes-medical"></i> Diagnostic & Suivi</h3>
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label class="required">Description détaillée</label>
                            <textarea name="description" id="description" rows="5" required><?= htmlspecialchars($traitement['description']) ?></textarea>
                        </div>
                        <div class="form-group full-width">
                            <label>Notes de suivi / Recommandations</label>
                            <textarea name="suivi" rows="4"><?= htmlspecialchars($traitement['suivi']) ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="btn-group">
                    <button type="submit" name="submit" class="btn btn-save">
                        <i class="fas fa-save"></i> Enregistrer les modifications
                    </button>
                    <a href="list.php" class="btn btn-cancel">
                        <i class="fas fa-times"></i> Annuler
                    </a>
                </div>
            </form>
        </div>
    </main>

    <script>
        // On conserve vos scripts de validation
        document.getElementById('editTraitementForm').addEventListener('submit', function(e) {
            if (!confirm('Confirmez-vous la modification de ce dossier médical ?')) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>