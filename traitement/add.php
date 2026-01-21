<?php
require_once '../config/connexion.php';

// Récupérer l'ID patient depuis l'URL si fourni
$patient_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : 0;
$current_patient = null;
$age_patient = null;

// Si un ID patient est fourni, récupérer ses infos
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
    <title>Nouveau Traitement | MedCare</title>
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

        /* HEADER */
        header { 
            background: var(--white); 
            padding: 0 40px; 
            height: var(--header-height); 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            border-bottom: 1px solid #e2e8f0; 
            position: fixed; 
            top: 0; left: 0; right: 0; 
            z-index: 1000; 
        }

        /* SIDEBAR */
        .sidebar { 
            width: var(--sidebar-width); 
            background: var(--sidebar-bg); 
            padding: 24px 16px; 
            position: fixed; 
            top: var(--header-height); 
            left: 0; bottom: 0; 
            z-index: 999; 
        }
        .sidebar h3 { color: rgba(255,255,255,0.3); font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 20px; padding-left: 12px; }
        .sidebar a { display: flex; align-items: center; gap: 12px; color: #94a3b8; text-decoration: none; padding: 12px 16px; border-radius: 10px; margin-bottom: 5px; transition: 0.3s; }
        .sidebar a:hover { background: rgba(255,255,255,0.05); color: #fff; }
        .sidebar a.active { background: var(--primary); color: #fff; }

        .user-pill { background: #f0fdfa; padding: 8px 18px; border-radius: 12px; display: flex; align-items: center; gap: 10px; font-size: 14px; font-weight: 600; color: var(--primary); }
        .content { margin-left: var(--sidebar-width); padding: 30px 40px; margin-top: var(--header-height); }

        /* CARD STYLE */
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

        .header-title-small { font-size: 13px; text-transform: uppercase; letter-spacing: 1.5px; font-weight: 600; opacity: 0.85; margin-bottom: 4px; }
        .header-title-main { font-size: 26px; font-weight: 800; margin-bottom: 6px; letter-spacing: -0.5px; }

        .form-body { padding: 40px; }

        .section-separator {
            display: flex; align-items: center; margin: 30px 0 20px 0;
            color: var(--primary); font-weight: 700; font-size: 13px;
            text-transform: uppercase; letter-spacing: 1px;
        }
        .section-separator i { margin-right: 12px; }
        .section-separator::after { content: ""; flex: 1; height: 1px; background: #e2e8f0; margin-left: 15px; }

        .form-label { font-weight: 600; font-size: 13px; color: #64748b; margin-bottom: 8px; display: block; }
        .input-group-custom { position: relative; }
        .input-group-custom i { position: absolute; left: 18px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 15px; z-index: 10; }
        .input-group-custom textarea + i { top: 20px; transform: none; }

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
        .form-control-modern::placeholder { color: #94a3b8; opacity: 0.6; }

        .form-select-modern {
            padding: 13px 15px 13px 50px;
            background: var(--input-dark);
            border: 1px solid var(--input-dark);
            border-radius: 12px;
            width: 100%;
            color: #ffffff;
            font-size: 14px;
            appearance: none;
        }

        /* Banner Patient Rapide */
        .patient-quick-info {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 20px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }
        .info-item .label { font-size: 11px; text-transform: uppercase; color: var(--primary); font-weight: 700; display: block; }
        .info-item .val { font-weight: 700; color: var(--sidebar-bg); font-size: 14px; }

        .btn-submit-modern {
            background: var(--primary); color: white; padding: 18px 35px;
            border-radius: 14px; font-weight: 700; border: none; transition: 0.3s;
            display: flex; align-items: center; gap: 12px; width: 100%; justify-content: center;
            text-transform: uppercase; letter-spacing: 1px; font-size: 15px;
            margin-top: 20px;
        }
        .btn-submit-modern:hover { background: var(--primary-hover); transform: translateY(-2px); box-shadow: 0 10px 20px rgba(15, 118, 110, 0.3); }

        .char-counter { text-align: right; font-size: 11px; color: #94a3b8; margin-top: 5px; font-weight: 600; }
    </style>
</head>
<body>

<header>
    <img src="../images/logo_app2.png" alt="Logo" style="height: 45px;">
    <div class="user-pill">
        <i class="fa-solid fa-user-md"></i>
        <span>Espace Médical</span>
    </div>
</header>

<div class="wrapper">
    <aside class="sidebar">
        <h3>Unité de Soins</h3>
        <a href="../connexio_utilisateur/dashboard_medecin.php"><i class="fa-solid fa-chart-line"></i> Vue Générale</a>
        <a href="../connexio_utilisateur/hospitalisation.php"><i class="fa-solid fa-bed-pulse"></i> Patients Admis</a>
        <a href="../connexio_utilisateur/patients.php"><i class="fa-solid fa-hospital-user"></i> Patients</a>
        <a href="list.php" class="active"><i class="fa-solid fa-file-prescription"></i> Traitements</a>
        <a href="../connexio_utilisateur/suivis.php"><i class="fa-solid fa-calendar-check"></i> Consultations</a>
        <h3>Analyse & Gestion</h3>
        <a href="../admission/statistique.php"><i class="fa-solid fa-chart-pie"></i> Statistiques</a>
        <a href="../connexio_utilisateur/archives.php"><i class="fa-solid fa-box-archive"></i> Archives</a>
        <a href="../connexio_utilisateur/profil_medcin.php"><i class="fa-solid fa-user-gear"></i> Profil</a>
        <div style="margin-top: 40px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 10px;">
            <a href="../connexio_utilisateur/deconnexion.php" style="color: #fda4af;"><i class="fa-solid fa-power-off"></i> Déconnexion</a>
        </div>
    </aside>

    <main class="content">
        <div class="form-card">
            <div class="form-header-profile">
                <div class="avatar-huge">
                    <i class="fa-solid fa-file-medical"></i>
                </div>
                <div>
                    <div class="header-title-small">Prescription Médicale</div>
                    <div class="header-title-main">Nouveau Traitement</div>
                </div>
            </div>

            <?php if($message): ?>
                <div class="alert alert-danger mx-4 mt-4 mb-0 border-0 shadow-sm">
                    <i class="fa-solid fa-circle-exclamation me-2"></i><?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="traitementForm" class="form-body">
                
                <div class="section-separator"><i class="fa-solid fa-hospital-user"></i> Identification Patient</div>

                <div class="row g-4">
                    <div class="col-12">
                        <label class="form-label">Sélectionner le patient</label>
                        <div class="input-group-custom">
                            <i class="fa-solid fa-search"></i>
                            <select name="id_patient" id="id_patient" class="form-select-modern" required onchange="afficherInfosPatient(this.value)">
                                <option value="">-- Choisir un patient --</option>
                                <?php
                                $stmt = $pdo->query("SELECT * FROM patients ORDER BY nom, prenom");
                                while($p = $stmt->fetch()):
                                    $sel = ($patient_id > 0 && $p['id_patient'] == $patient_id) ? 'selected' : '';
                                ?>
                                <option value="<?= $p['id_patient'] ?>" 
                                        data-nom="<?= htmlspecialchars($p['nom']) ?>"
                                        data-prenom="<?= htmlspecialchars($p['prenom']) ?>"
                                        data-naissance="<?= $p['date_naissance'] ?>"
                                        data-cin="<?= htmlspecialchars($p['CIN'] ?? '') ?>"
                                        <?= $sel ?>>
                                    <?= htmlspecialchars($p['nom'] . ' ' . $p['prenom']) ?> (<?= $p['CIN'] ?: 'N/C' ?>)
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>

                    <div class="col-12">
                        <div id="patientInfo" class="patient-quick-info" style="<?= ($current_patient) ? 'display: grid;' : 'display: none;' ?>">
                            <?php if($current_patient): ?>
                                <div class="info-item"><span class="label">Patient</span><span class="val"><?= htmlspecialchars($current_patient['nom'].' '.$current_patient['prenom']) ?></span></div>
                                <div class="info-item"><span class="label">Âge</span><span class="val"><?= $age_patient ?> ans</span></div>
                                <div class="info-item"><span class="label">CIN</span><span class="val"><?= htmlspecialchars($current_patient['CIN'] ?: 'N/C') ?></span></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="section-separator"><i class="fa-solid fa- prescription"></i> Détails de la Prescription</div>

                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label">Date du traitement</label>
                        <div class="input-group-custom">
                            <i class="fa-solid fa-calendar-check"></i>
                            <input type="date" name="date_traitement" class="form-control-modern" value="<?= date('Y-m-d') ?>" required max="<?= date('Y-m-d') ?>">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Médicament(s)</label>
                        <div class="input-group-custom">
                            <i class="fa-solid fa-pills"></i>
                            <input type="text" name="medicament" class="form-control-modern" placeholder="Ex: Paracétamol 500mg, Amoxicilline...">
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Diagnostic et Observations</label>
                        <div class="input-group-custom">
                            <i class="fa-solid fa-notes-medical"></i>
                            <textarea name="description" id="description" class="form-control-modern" rows="4" placeholder="Décrivez l'état du patient et le soin apporté..." required></textarea>
                        </div>
                        <div class="char-counter"><span id="charCount">0</span> / 2000 caractères</div>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Instructions de suivi</label>
                        <div class="input-group-custom">
                            <i class="fa-solid fa-comment-medical"></i>
                            <textarea name="suivi" class="form-control-modern" rows="2" placeholder="Date de rappel, recommandations alimentaires, etc."></textarea>
                        </div>
                    </div>
                </div>

                <div class="mt-5">
                    <button type="submit" name="submit" class="btn-submit-modern">
                        <i class="fa-solid fa-floppy-disk"></i> Enregistrer le traitement
                    </button>
                    <div class="text-center mt-3">
                        <a href="list.php" class="text-muted small text-decoration-none">
                            <i class="fa-solid fa-arrow-left me-1"></i> Annuler et retourner
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </main>
</div>

<script>
function afficherInfosPatient(patientId) {
    const select = document.getElementById('id_patient');
    const opt = select.options[select.selectedIndex];
    const banner = document.getElementById('patientInfo');
    
    if (patientId && opt.dataset.nom) {
        const naissance = new Date(opt.dataset.naissance);
        const age = new Date().getFullYear() - naissance.getFullYear();
        
        banner.innerHTML = `
            <div class="info-item"><span class="label">Patient</span><span class="val">${opt.dataset.nom} ${opt.dataset.prenom}</span></div>
            <div class="info-item"><span class="label">Âge</span><span class="val">${age} ans</span></div>
            <div class="info-item"><span class="label">CIN</span><span class="val">${opt.dataset.cin || 'N/C'}</span></div>
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
    if (!confirm("Voulez-vous vraiment enregistrer ce traitement ?")) {
        e.preventDefault();
    }
});
</script>

</body>
</html>