<?php
require_once '../config/connexion.php';

// 1. Vérification de l'ID pour la modification
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: list.php?error=ID+invalide");
    exit();
}

$id = intval($_GET['id']);

// 2. Récupérer les données actuelles du traitement
$stmt = $pdo->prepare("SELECT * FROM v_traitements_complets WHERE id_traitement = ?");
$stmt->execute([$id]);
$traitement = $stmt->fetch();

if(!$traitement) {
    header("Location: list.php?error=Traitement+non+trouvé");
    exit();
}

$error = '';
$message = '';

// 3. Logique de mise à jour (Soumission du formulaire)
if(isset($_POST['submit'])) {
    try {
        $id_patient = $_POST['id_patient'];
        $description = $_POST['description'];
        $date_traitement = $_POST['date_traitement'];
        $medicament = $_POST['medicament'];
        $suivi = $_POST['suivi'];
        
        // Appel de la procédure de modification
        $stmt = $pdo->prepare("CALL sp_modifier_traitement(?, ?, ?, ?, ?, ?)");
        $stmt->execute([$id, $id_patient, $description, $date_traitement, $medicament, $suivi]);
        
        $result = $stmt->fetch();
        
        if($result && isset($result['success']) && $result['success'] == 1) {
            header("Location: list.php?success=Modification+effectuée");
            exit();
        } else {
            $error = $result['message'] ?? 'Erreur lors de la modification';
        }
    } catch(PDOException $e) {
        $error = "Erreur SQL: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Traitement | MedCare</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #0f766e;
            --primary-hover: #0d9488;
            --sidebar-bg: #0f172a;
            --bg-body: #f1f5f9;
            --input-dark: #1e293b;
            --white: #ffffff;
            --header-height: 75px;
            --sidebar-width: 260px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: var(--bg-body); color: #1e293b; }

        header {
            background: var(--white);
            padding: 0 40px; height: var(--header-height);
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid #e2e8f0;
            position: fixed; top: 0; width: 100%; z-index: 1000;
        }

        .sidebar { 
            width: var(--sidebar-width); background: var(--sidebar-bg); 
            padding: 24px 16px; position: fixed; 
            top: var(--header-height); left: 0; bottom: 0; z-index: 999;
        }
        .sidebar h3 { color: rgba(255,255,255,0.3); font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 20px; padding-left: 12px; }
        .sidebar a { display: flex; align-items: center; gap: 12px; color: #94a3b8; text-decoration: none; padding: 12px 16px; border-radius: 10px; margin-bottom: 5px; transition: 0.3s; }
        .sidebar a:hover { background: rgba(255,255,255,0.05); color: #fff; }
        .sidebar a.active { background: var(--primary); color: #fff; }

        .user-pill {
            background: #f0fdfa; padding: 8px 18px; border-radius: 12px;
            display: flex; align-items: center; gap: 10px;
            font-size: 14px; font-weight: 600; color: var(--primary);
        }

        .content { margin-left: var(--sidebar-width); padding: 40px; margin-top: var(--header-height); }

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
            background: var(--primary); /* Couleur différente pour l'édition */
            display: flex; align-items: center; gap: 25px;
            color: white;
            border-bottom: 4px solid var(--primary);
        }

        .avatar-huge {
            width: 80px; height: 80px; 
            background: rgba(255,255,255,0.1); 
            border-radius: 20px; display: flex; align-items: center; justify-content: center;
            font-size: 35px; border: 2px solid rgba(255,255,255,0.2);
        }

        .header-title-small { font-size: 12px; text-transform: uppercase; letter-spacing: 1.5px; font-weight: 700; opacity: 0.9; margin-bottom: 4px; }
        .header-title-main { font-size: 24px; font-weight: 800; margin-bottom: 0; }

        .form-body { padding: 40px; }

        .section-separator {
            display: flex; align-items: center; margin: 30px 0 20px 0;
            color: var(--primary); font-weight: 800; font-size: 12px;
            text-transform: uppercase; letter-spacing: 1px;
        }
        .section-separator i { margin-right: 12px; font-size: 14px; }
        .section-separator::after { content: ""; flex: 1; height: 1px; background: #e2e8f0; margin-left: 15px; }

        .form-label { font-weight: 700; font-size: 13px; color: #64748b; margin-bottom: 8px; display: block; }
        
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
            font-weight: 500;
            transition: 0.3s;
        }
        .form-control-modern:focus { outline: none; background: #334155; box-shadow: 0 0 0 4px rgba(15, 118, 110, 0.2); }
        
        .form-select-modern {
            padding: 13px 15px 13px 50px;
            background: var(--input-dark);
            border: 1px solid var(--input-dark);
            border-radius: 12px;
            width: 100%;
            color: #ffffff;
            font-size: 14px;
            font-weight: 500;
            appearance: none;
        }

        .patient-quick-info {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 15px;
            padding: 18px 25px;
            margin-top: 10px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
        }
        .info-item .label { font-size: 10px; text-transform: uppercase; color: var(--primary); font-weight: 800; display: block; margin-bottom: 2px; }
        .info-item .val { font-weight: 700; color: var(--sidebar-bg); font-size: 14px; }

        .btn-submit-modern {
            background: var(--primary); color: white; padding: 18px;
            border-radius: 14px; font-weight: 700; border: none; transition: 0.3s;
            display: flex; align-items: center; gap: 12px; width: 100%; justify-content: center;
            text-transform: uppercase; letter-spacing: 1px; font-size: 15px;
            margin-top: 30px; cursor: pointer;
        }
        .btn-submit-modern:hover { background: var(--primary-hover); transform: translateY(-2px); box-shadow: 0 10px 20px rgba(15, 118, 110, 0.2); }

        .char-counter { text-align: right; font-size: 11px; color: #94a3b8; margin-top: 5px; font-weight: 600; }
    </style>
</head>
<body>

<header>
    <img src="../images/logo_app2.png" alt="MedCare" style="height: 45px;">
    <div class="user-pill">
        <i class="fas fa-user-md"></i>
        <span>Espace Médical</span>
    </div>
</header>

<div class="wrapper">
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

    <main class="content">
        <div class="form-card">
            <div class="form-header-profile">
                <div class="avatar-huge">
                    <i class="fa-solid fa-file-pen"></i>
                </div>
                <div>
                    <div class="header-title-small">Modification de la fiche #<?= $id ?></div>
                    <h2 class="header-title-main">Mettre à jour le traitement</h2>
                </div>
            </div>

            <?php if($error): ?>
                <div class="alert alert-danger mx-4 mt-4 mb-0 border-0 shadow-sm">
                    <i class="fa-solid fa-circle-exclamation me-2"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="editTraitementForm" class="form-body">
                
                <div class="section-separator"><i class="fa-solid fa-user-injured"></i> Patient Concerné</div>

                <div class="row g-4">
                    <div class="col-12">
                        <label class="form-label">Patient (Lecture seule pour modification)</label>
                        <div class="input-group-custom">
                            <i class="fa-solid fa-user"></i>
                            <select name="id_patient" id="id_patient" class="form-select-modern" required onchange="afficherInfosPatient(this.value)">
                                <?php
                                $stmt_p = $pdo->query("SELECT * FROM patients ORDER BY nom, prenom");
                                while($p = $stmt_p->fetch()):
                                    $selected = ($p['id_patient'] == $traitement['id_patient']) ? 'selected' : '';
                                ?>
                                <option value="<?= $p['id_patient'] ?>" 
                                        data-nom="<?= htmlspecialchars($p['nom']) ?>"
                                        data-prenom="<?= htmlspecialchars($p['prenom']) ?>"
                                        data-naissance="<?= $p['date_naissance'] ?>"
                                        data-telephone="<?= htmlspecialchars($p['telephone'] ?: 'Non renseigné') ?>"
                                        data-cin="<?= htmlspecialchars($p['CIN'] ?: 'N/C') ?>"
                                        <?= $selected ?>>
                                    <?= htmlspecialchars($p['nom'] . ' ' . $p['prenom']) ?> (<?= $p['CIN'] ?: 'N/C' ?>)
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>

                    <div class="col-12">
                        <div id="patientInfo" class="patient-quick-info">
                            </div>
                    </div>
                </div>

                <div class="section-separator"><i class="fa-solid fa-notes-medical"></i> Détails Thérapeutiques</div>

                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label">Date du traitement</label>
                        <div class="input-group-custom">
                            <i class="fa-solid fa-calendar-day"></i>
                            <input type="date" name="date_traitement" id="date_traitement" class="form-control-modern" 
                                   value="<?= $traitement['date_traitement'] ?>" required max="<?= date('Y-m-d') ?>">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Médicament(s)</label>
                        <div class="input-group-custom">
                            <i class="fa-solid fa-pills"></i>
                            <input type="text" name="medicament" class="form-control-modern" 
                                   value="<?= htmlspecialchars($traitement['medicament'] ?? '') ?>" placeholder="Médicaments prescrits...">
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Diagnostic & Traitement</label>
                        <div class="input-group-custom">
                            <i class="fa-solid fa-stethoscope"></i>
                            <textarea name="description" id="description" class="form-control-modern" rows="5" 
                                      required><?= htmlspecialchars($traitement['description']) ?></textarea>
                        </div>
                        <div class="char-counter"><span id="charCount"><?= strlen($traitement['description']) ?></span> / 2000 caractères</div>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Instructions et Suivi</label>
                        <div class="input-group-custom">
                            <i class="fa-solid fa-comment-medical"></i>
                            <textarea name="suivi" class="form-control-modern" rows="2"><?= htmlspecialchars($traitement['suivi'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>

                <button type="submit" name="submit" class="btn-submit-modern">
                    <i class="fas fa-save"></i> Enregistrer les modifications
                </button>
                
                <div style="text-align: center; margin-top: 25px;">
                    <a href="list.php" style="color: #64748b; text-decoration: none; font-weight: 700; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="fas fa-arrow-left me-1"></i> Annuler et retourner à la liste
                    </a>
                </div>
            </form>
        </div>
    </main>
</div>

<script>
// Fonction pour mettre à jour la bannière d'infos patient
function afficherInfosPatient(id) {
    const select = document.getElementById('id_patient');
    const opt = select.options[select.selectedIndex];
    const banner = document.getElementById('patientInfo');
    
    if (id && opt.dataset.nom) {
        const naiss = new Date(opt.dataset.naissance);
        const age = new Date().getFullYear() - naiss.getFullYear();
        
        banner.innerHTML = `
            <div class="info-item"><span class="label">Identité</span><span class="val">${opt.dataset.nom} ${opt.dataset.prenom}</span></div>
            <div class="info-item"><span class="label">Âge</span><span class="val">${age} ans</span></div>
            <div class="info-item"><span class="label">CIN</span><span class="val">${opt.dataset.cin}</span></div>
            <div class="info-item"><span class="label">Contact</span><span class="val">${opt.dataset.telephone}</span></div>
        `;
        banner.style.display = 'grid';
    } else {
        banner.style.display = 'none';
    }
}

// Initialiser les infos au chargement
window.onload = function() {
    afficherInfosPatient(document.getElementById('id_patient').value);
};

// Compteur de caractères
document.getElementById('description').addEventListener('input', function() {
    document.getElementById('charCount').textContent = this.value.length;
});

// Confirmation avant envoi
document.getElementById('editTraitementForm').addEventListener('submit', function(e) {
    if (!confirm("Voulez-vous vraiment modifier ce traitement ?")) {
        e.preventDefault();
    }
});
</script>

</body>
</html>