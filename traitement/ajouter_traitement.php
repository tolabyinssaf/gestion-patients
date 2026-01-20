<?php
require_once '../config/connexion.php';

// Récupérer l'ID patient depuis l'URL si fourni
$patient_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : 0;
$current_patient = null;
$age_patient = null;

if($patient_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM patients WHERE id_patient = ?");
    $stmt->execute([$patient_id]);
    $current_patient = $stmt->fetch();
    
    if($current_patient && $current_patient['date_naissance']) {
        $dateNaissance = new DateTime($current_patient['date_naissance']);
        $aujourdhui = new DateTime();
        $age_patient = $aujourdhui->diff($dateNaissance)->y;
    }
}

$message = '';
$type_message = '';

if(isset($_POST['submit'])) {
    $id_patient = $_POST['id_patient'] ?? '';
    $description = $_POST['description'] ?? '';
    $date_traitement = $_POST['date_traitement'] ?? '';
    $medicament = $_POST['medicament'] ?? '';
    $suivi = $_POST['suivi'] ?? '';
    
    if(empty($id_patient) || empty($description) || empty($date_traitement)) {
        $type_message = 'error';
        $message = 'Tous les champs obligatoires doivent être remplis';
    } else {
        try {
            $stmt = $pdo->prepare("CALL sp_ajouter_traitement(?, ?, ?, ?, ?)");
            $stmt->execute([$id_patient, $description, $date_traitement, $medicament, $suivi]);
            
            $result = $stmt->fetch();
            $message = 'Traitement ajouté avec succès';
            header("Location: list.php?success=" . urlencode($message));
            exit();
            
        } catch(PDOException $e) {
            $type_message = 'error';
            $message = "Erreur : " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouveau Traitement | MedCare</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #0f766e;
            --primary-light: #f0fdfa;
            --sidebar-bg: #0f172a;
            --bg-body: #f1f5f9;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --white: #ffffff;
            --border: #cbd5e1;
            --error: #dc2626;
            --success: #16a34a;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: var(--bg-body); color: var(--text-main); }

        /* Header */
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

        /* Layout */
        .container { display: flex; padding-top: 75px; }
        
        .sidebar { 
            width: 260px; background: var(--sidebar-bg); 
            padding: 24px 16px; position: fixed; 
            height: calc(100vh - 75px); overflow-y: auto;
        }
        .sidebar h3 { color: rgba(255,255,255,0.3); font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 20px; padding-left: 12px; }
        .sidebar a { display: flex; align-items: center; gap: 12px; color: #94a3b8; text-decoration: none; padding: 12px 16px; border-radius: 10px; margin-bottom: 5px; transition: 0.2s; }
        .sidebar a:hover { background: rgba(255,255,255,0.05); color: #fff; }
        .sidebar a.active { background: var(--primary); color: #fff; }

        /* Main Content */
        .main-content { flex: 1; padding: 40px; margin-left: 260px; }
        .breadcrumb { font-size: 13px; font-weight: 600; color: var(--text-muted); margin-bottom: 8px; text-align: center; text-transform: uppercase; }
        h1 { font-size: 30px; font-weight: 800; color: var(--sidebar-bg); margin-bottom: 30px; text-align: center; }

        /* Card (Exactement comme ajouter_patient) */
        .card { 
            background: #f8fafc; 
            border-radius: 20px; 
            border: 2px solid var(--primary); 
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.1);
            overflow: hidden;
            max-width: 900px; 
            margin: 0 auto;
        }
        .card-header { 
            background: var(--primary); 
            padding: 20px 40px; 
            display: flex; 
            align-items: center; 
            gap: 15px; 
        }
        .card-header h2 { font-size: 20px; color: #fff; font-weight: 700; }
        .card-header i { color: #fff; font-size: 24px; } 

        form { padding: 40px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; }
        .full-width { grid-column: span 2; }

        .field-group { display: flex; flex-direction: column; gap: 8px; }
        .field-group label { font-size: 14px; font-weight: 800; color: var(--sidebar-bg); }
        .required::after { content: ' *'; color: var(--error); }

        .form-control { 
            width: 100%; padding: 14px 16px; 
            border: 2px solid #cbd5e1; border-radius: 12px; 
            font-size: 15px; font-weight: 600;
            background: var(--white); transition: all 0.3s;
        }
        .form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 4px rgba(15, 118, 110, 0.1); }

        /* Patient Banner Info */
        #patientInfo {
            grid-column: span 2;
            background: var(--primary-light);
            padding: 15px 20px;
            border-radius: 12px;
            border: 1px dashed var(--primary);
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin-bottom: 10px;
        }
        .info-item { display: flex; flex-direction: column; }
        .info-label { font-size: 11px; text-transform: uppercase; color: var(--primary); font-weight: 700; }
        .info-value { font-weight: 700; color: var(--sidebar-bg); font-size: 14px; }

        .section-title {
            grid-column: span 2; font-size: 13px; font-weight: 800;
            text-transform: uppercase; color: var(--primary);
            letter-spacing: 1.2px; margin-top: 20px;
            display: flex; align-items: center; gap: 10px;
            border-bottom: 2px solid #e2e8f0; padding-bottom: 5px;
        }

        .btn-submit { 
            background: var(--sidebar-bg); color: white; 
            padding: 16px; border: none; border-radius: 12px; 
            font-weight: 700; font-size: 16px; cursor: pointer; 
            margin-top: 30px; width: 100%;
            display: flex; align-items: center; gap: 12px; justify-content: center;
            transition: 0.3s;
        }
        .btn-submit:hover { background: var(--primary); transform: translateY(-2px); }

        .alert {
            padding: 15px 40px; border-bottom: 1px solid var(--border);
            font-weight: 600; display: flex; align-items: center; gap: 10px;
        }
        .alert-error { background: #fee2e2; color: var(--error); }
        .alert-success { background: #dcfce7; color: var(--success); }
    </style>
</head>
<body>

<header>
    <img src="../images/logo_app2.png" alt="MedCare" class="logo">
    <div class="user-pill">
        <i class="fas fa-user-md"></i>
        <span>Espace Médical</span>
    </div>
</header>

<div class="container">
    <aside class="sidebar">
        <h3 style="font-weight: 800;">Unité de Soins</h3>
        <a href="../connexio_utilisateur/dashboard_medecin.php"><i class="fa-solid fa-chart-line"></i> Vue Générale</a>
        <a href="../connexio_utilisateur/hospitalisation.php"><i class="fa-solid fa-bed-pulse"></i> Patients Admis</a>
        <a href="../connexio_utilisateur/patients.php"><i class="fa-solid fa-hospital-user"></i> Patients</a>
        <a href="list.php" class="active"><i class="fa-solid fa-file-prescription"></i> Traitements</a>
        <a href="../connexio_utilisateur/suivis.php"><i class="fa-solid fa-calendar-check"></i> Consultations</a>
        <h3 style="font-weight: 800;">Analyse & Gestion</h3>
        <a href="../admission/statistique.php"><i class="fa-solid fa-chart-pie"></i> Statistiques</a>
        <a href="../connexio_utilisateur/archives.php"><i class="fa-solid fa-box-archive"></i> Archives</a>
        <a href="../connexio_utilisateur/profil_medcin.php"><i class="fa-solid fa-user-gear"></i> Profil</a>
        <div style="margin-top: 40px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 10px;">
            <a href="../connexio_utilisateur/deconnexion.php" style="color: #fda4af;"><i class="fa-solid fa-power-off"></i> Déconnexion</a>
        </div>
    </aside>

    <main class="main-content">
   

        <div class="card">
            <div class="card-header">
                <i class="fas fa-prescription-bottle-medical"></i>
                <h2>Détails du traitement</h2>
            </div>

            <?php if($message): ?>
                <div class="alert alert-<?= $type_message == 'error' ? 'error' : 'success' ?>">
                    <i class="fas <?= $type_message == 'error' ? 'fa-circle-exclamation' : 'fa-check-circle' ?>"></i>
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="traitementForm">
                <div class="form-grid">
                    
                    <div class="field-group full-width">
                        <label for="id_patient" class="required">Patient concerné</label>
                        <select name="id_patient" id="id_patient" class="form-control" required onchange="afficherInfosPatient(this.value)">
                            <option value="">-- Sélectionner un patient --</option>
                            <?php
                            $stmt = $pdo->query("SELECT * FROM patients ORDER BY nom, prenom");
                            while($p = $stmt->fetch()):
                                $selected = ($patient_id > 0 && $p['id_patient'] == $patient_id) ? 'selected' : '';
                            ?>
                            <option value="<?= $p['id_patient'] ?>" 
                                    data-nom="<?= htmlspecialchars($p['nom']) ?>"
                                    data-prenom="<?= htmlspecialchars($p['prenom']) ?>"
                                    data-naissance="<?= $p['date_naissance'] ?>"
                                    data-telephone="<?= htmlspecialchars($p['telephone']) ?>"
                                    data-cin="<?= htmlspecialchars($p['CIN'] ?? '') ?>"
                                    <?= $selected ?>>
                                <?= htmlspecialchars($p['nom'] . ' ' . $p['prenom']) ?> (<?= $p['CIN'] ?: 'N/C' ?>)
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div id="patientInfo" style="<?= ($current_patient) ? 'display: grid;' : 'display: none;' ?>">
                        <?php if($current_patient): ?>
                            <div class="info-item"><span class="info-label">Identité</span><span class="info-value"><?= htmlspecialchars($current_patient['nom'] . ' ' . $current_patient['prenom']) ?></span></div>
                            <div class="info-item"><span class="info-label">Âge</span><span class="info-value"><?= $age_patient ?> ans</span></div>
                            <div class="info-item"><span class="info-label">CIN</span><span class="info-value"><?= htmlspecialchars($current_patient['CIN'] ?: 'N/A') ?></span></div>
                            <div class="info-item"><span class="info-label">Contact</span><span class="info-value"><?= htmlspecialchars($current_patient['telephone'] ?: 'N/A') ?></span></div>
                        <?php endif; ?>
                    </div>

                    <div class="section-title"><i class="fas fa-pen-nib"></i> Informations Thérapeutiques</div>

                    <div class="field-group">
                        <label for="date_traitement" class="required">Date du traitement</label>
                        <input type="date" name="date_traitement" id="date_traitement" class="form-control" 
                               value="<?= date('Y-m-d') ?>" required max="<?= date('Y-m-d') ?>">
                    </div>

                    <div class="field-group">
                        <label for="medicament">Médicament(s) prescrit(s)</label>
                        <input type="text" name="medicament" class="form-control" placeholder="Ex: Paracétamol 500mg, 3x/jour">
                    </div>

                    <div class="field-group full-width">
                        <label for="description" class="required">Diagnostic et Traitement</label>
                        <textarea name="description" id="description" class="form-control" rows="5" 
                                  placeholder="Observations cliniques et détails de la prise en charge..." required></textarea>
                        <div style="text-align: right; font-size: 11px; color: var(--text-muted); font-weight: 700; margin-top: 4px;">
                            <span id="charCount">0</span> / 2000 caractères
                        </div>
                    </div>

                    <div class="field-group full-width">
                        <label for="suivi">Instructions de suivi & Recommandations</label>
                        <textarea name="suivi" class="form-control" rows="3" 
                                  placeholder="Conseils au patient, date de la prochaine consultation..."></textarea>
                    </div>

                </div>

                <button type="submit" name="submit" class="btn-submit">
                    <i class="fas fa-save"></i> Enregistrer la prescription
                </button>
                
                <div style="text-align: center; margin-top: 20px;">
                    <a href="list.php" style="color: var(--text-muted); text-decoration: none; font-weight: 700; font-size: 14px;">
                        <i class="fas fa-arrow-left"></i> Annuler et retourner à la liste
                    </a>
                </div>
            </form>
        </div>
    </main>
</div>

<script>
function afficherInfosPatient(id) {
    const select = document.getElementById('id_patient');
    const opt = select.options[select.selectedIndex];
    const banner = document.getElementById('patientInfo');
    
    if (id && opt.dataset.nom) {
        const naiss = new Date(opt.dataset.naissance);
        const age = new Date().getFullYear() - naiss.getFullYear();
        
        banner.innerHTML = `
            <div class="info-item"><span class="info-label">Identité</span><span class="info-value">${opt.dataset.nom} ${opt.dataset.prenom}</span></div>
            <div class="info-item"><span class="info-label">Âge</span><span class="info-value">${age} ans</span></div>
            <div class="info-item"><span class="info-label">CIN</span><span class="info-value">${opt.dataset.cin || 'N/A'}</span></div>
            <div class="info-item"><span class="info-label">Contact</span><span class="info-value">${opt.dataset.telephone || 'N/A'}</span></div>
        `;
        banner.style.display = 'grid';
    } else {
        banner.style.display = 'none';
    }
}

document.getElementById('description').addEventListener('input', function() {
    document.getElementById('charCount').textContent = this.value.length;
});

document.getElementById('traitementForm').addEventListener('submit', function(e) {
    const dateInput = document.getElementById('date_traitement');
    const today = new Date().toISOString().split('T')[0];
    
    if (dateInput.value > today) {
        alert("⚠️ La date ne peut pas être dans le futur !");
        e.preventDefault();
        return;
    }

    if (!confirm("Confirmez-vous l'enregistrement de ce traitement ?")) {
        e.preventDefault();
    }
});
</script>

</body>
</html>