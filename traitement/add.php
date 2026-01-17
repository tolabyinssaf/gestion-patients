<?php
require_once '../config/connexion.php';

// Utilisation d'une transaction pour l'ajout
if(isset($_POST['submit'])) {
    try {
        $id_patient = $_POST['id_patient'];
        $description = $_POST['description'];
        $date_traitement = $_POST['date_traitement'];
        $medicament = $_POST['medicament'];
        $suivi = $_POST['suivi'];
        
        $stmt = $pdo->prepare("CALL sp_ajouter_traitement(?, ?, ?, ?, ?, @id_traitement, @message)");
        $stmt->execute([$id_patient, $description, $date_traitement, $medicament, $suivi]);
        $stmt->closeCursor(); 
        
        $result = $pdo->query("SELECT @id_traitement as id, @message as message")->fetch();
        
        if($result['id'] > 0) {
            $success = true;
            $message = $result['message'];
        } else {
            $success = false;
            $error = $result['message'];
        }
    } catch(PDOException $e) {
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
    <title>Ajouter un traitement | MedCare</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    
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

    * { margin:0; padding:0; box-sizing:border-box; font-family: 'Inter', sans-serif; }
    body { background: var(--bg-body); color: var(--text-main); }

    /* ===== HEADER ===== */
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
        /* --- MAIN CONTENT --- */
        .content { flex: 1; padding: 40px; max-width: 1000px; margin: 0 auto; }
        
        .page-header {
            display: flex; justify-content: space-between; align-items: flex-end;
            margin-bottom: 30px;
        }
        .page-header h1 { font-size: 24px; color: var(--text-main); font-weight: 700; }
        .page-header p { color: var(--text-muted); font-size: 14px; }

        /* --- FORM STYLING --- */
        .card {
            background: var(--white);
            border-radius: var(--radius);
            padding: 30px;
            border: 1px solid var(--border);
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        }

        .form-section-title {
            font-size: 16px; font-weight: 700; color: var(--primary);
            margin-bottom: 20px; display: flex; align-items: center; gap: 10px;
            text-transform: uppercase; letter-spacing: 0.5px;
        }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px; }
        .full-width { grid-column: span 2; }

        .form-group { display: flex; flex-direction: column; gap: 8px; margin-bottom: 20px; }
        .form-group label { font-size: 13px; font-weight: 600; color: var(--text-main); }
        .form-group label.required::after { content: " *"; color: #ef4444; }

        select, input, textarea {
            padding: 12px 16px; border: 1.5px solid var(--border);
            border-radius: 8px; font-size: 14px; background: #fcfcfc;
            transition: 0.3s;
        }
        select:focus, input:focus, textarea:focus {
            outline: none; border-color: var(--primary); background: #fff;
            box-shadow: 0 0 0 4px rgba(15, 118, 110, 0.1);
        }

        /* --- PATIENT INFO CARD --- */
        .patient-preview {
            background: var(--primary-light);
            border: 1px solid rgba(15, 118, 110, 0.2);
            border-radius: 10px; padding: 15px; margin-bottom: 25px;
            display: none; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;
        }
        .preview-item span { display: block; }
        .preview-item .label { font-size: 11px; color: var(--primary); font-weight: 700; text-transform: uppercase; }
        .preview-item .val { font-size: 14px; font-weight: 600; color: var(--primary-dark); }

        /* --- ALERTS --- */
        .alert {
            padding: 15px 20px; border-radius: 8px; margin-bottom: 25px;
            display: flex; align-items: center; gap: 12px; font-size: 14px; font-weight: 500;
        }
        .alert-success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

        /* --- BUTTONS --- */
        .btn {
            padding: 12px 24px; border-radius: 8px; font-weight: 600; font-size: 14px;
            cursor: pointer; transition: 0.2s; border: none; display: inline-flex; align-items: center; gap: 8px;
        }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-dark); transform: translateY(-1px); }
        .btn-outline { background: transparent; border: 1.5px solid var(--border); color: var(--text-muted); }
        .btn-outline:hover { background: #f1f5f9; color: var(--text-main); }

        @media (max-width: 768px) {
            .form-grid { grid-template-columns: 1fr; }
            .full-width { grid-column: span 1; }
            .sidebar { display: none; }
        }
    </style>
</head>
<body>

<header>
    <img src="../images/logo_app2.png" alt="MedCare" class="logo">
    <div style="font-size: 14px; font-weight: 600; color: var(--primary);">
        <i class="fas fa-user-md"></i> Espace Praticien
    </div>
</header>

<div class="container">
    <aside class="sidebar">
        <h3>Menu Médical</h3>
        <a href="../connexio_utilisateur/dashboard_medecin.php"><i class="fa-solid fa-chart-pie"></i> Tableau de bord</a>
        <a href="../connexio_utilisateur/patients.php" ><i class="fa-solid fa-user-group"></i> Mes Patients</a>
        <a href="../connexio_utilisateur/suivis.php"><i class="fa-solid fa-file-medical"></i> Consultations</a>
        <a href="../traitement/list.php"  class="active"><i class="fa-solid fa-pills"></i> Traitements</a>
        <a href="rendezvous.php"><i class="fa-solid fa-calendar-check"></i> Rendez-vous</a>
        <div style="height: 1px; background: rgba(255,255,255,0.1); margin: 20px 0;"></div>
        <a href="../connexio_utilisateur/profil_medcin.php"><i class="fa-solid fa-user"></i> Profil</a>
        <a href="../connexio_utilisateur/deconnexion.php" style="color: #fda4af;"><i class="fa-solid fa-power-off"></i> Déconnexion</a>
    </aside>
    <main class="content">
        <div class="page-header">
            <div>
                <h1>Nouveau Traitement</h1>
                <p>Formulaire de prescription et suivi médical</p>
            </div>
            <a href="list.php" class="btn btn-outline">
                <i class="fas fa-list"></i> Voir la liste
            </a>
        </div>

        <?php if(isset($success)): ?>
            <div class="alert <?= $success ? 'alert-success' : 'alert-error' ?>">
                <i class="fas <?= $success ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                <span><?= $success ? $message : $error ?></span>
            </div>
        <?php endif; ?>

        <div class="card">
            <form method="POST" id="traitementForm">
                
                <div class="form-section-title">
                    <i class="fas fa-id-card"></i> Identification Patient
                </div>
                
                <div class="form-group">
                    <label for="id_patient" class="required">Sélectionner le patient</label>
                    <select name="id_patient" id="id_patient" required onchange="afficherInfosPatient(this.value)">
                        <option value="">-- Rechercher un patient --</option>
                        <?php
                        $stmt = $pdo->query("SELECT * FROM patients ORDER BY nom, prenom");
                        while($patient = $stmt->fetch()):
                        ?>
                        <option value="<?= $patient['id_patient'] ?>" 
                                data-nom="<?= htmlspecialchars($patient['nom']) ?>"
                                data-prenom="<?= htmlspecialchars($patient['prenom']) ?>"
                                data-naissance="<?= $patient['date_naissance'] ?>"
                                data-telephone="<?= htmlspecialchars($patient['telephone']) ?>">
                            <?= htmlspecialchars($patient['nom'] . ' ' . $patient['prenom']) ?> 
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div id="patientInfo" class="patient-preview">
                    </div>

                <div class="form-section-title">
                    <i class="fas fa-stethoscope"></i> Prescription Médicale
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="date_traitement" class="required">Date de consultation</label>
                        <input type="date" name="date_traitement" id="date_traitement" value="<?= date('Y-m-d') ?>" required max="<?= date('Y-m-d') ?>">
                    </div>

                    <div class="form-group">
                        <label for="medicament">Médicaments & Posologie</label>
                        <input type="text" name="medicament" id="medicament" placeholder="Ex: Paracétamol 500mg, 3x/jour">
                    </div>

                    <div class="form-group full-width">
                        <label for="description" class="required">Diagnostic et Détails du traitement</label>
                        <textarea name="description" id="description" placeholder="Saisissez vos observations médicales..." required rows="4"></textarea>
                        <div style="text-align: right; font-size: 11px; color: var(--text-muted); margin-top: 5px;">
                            <span id="charCount">0</span> / 2000 caractères
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label for="suivi">Recommandations de suivi</label>
                        <textarea name="suivi" id="suivi" placeholder="Notes pour la prochaine visite, précautions..." rows="3"></textarea>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 20px; border-top: 1px solid var(--border); padding-top: 25px;">
                    <button type="button" onclick="window.location.href='list.php'" class="btn btn-outline">Annuler</button>
                    <button type="submit" name="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Enregistrer la prescription
                    </button>
                </div>
            </form>
        </div>
    </main>
</div>

<script>
    function afficherInfosPatient(patientId) {
        const select = document.getElementById('id_patient');
        const selectedOption = select.options[select.selectedIndex];
        const preview = document.getElementById('patientInfo');
        
        if (patientId && selectedOption.dataset.nom) {
            const dateNaissance = new Date(selectedOption.dataset.naissance);
            const age = new Date().getFullYear() - dateNaissance.getFullYear();
            
            preview.innerHTML = `
                <div class="preview-item"><span class="label">Patient</span><span class="val">${selectedOption.dataset.nom} ${selectedOption.dataset.prenom}</span></div>
                <div class="preview-item"><span class="label">Âge</span><span class="val">${age} ans</span></div>
                <div class="preview-item"><span class="label">Téléphone</span><span class="val">${selectedOption.dataset.telephone || 'N/A'}</span></div>
            `;
            preview.style.display = 'grid';
        } else {
            preview.style.display = 'none';
        }
    }

    document.getElementById('description').addEventListener('input', function() {
        document.getElementById('charCount').textContent = this.value.length;
    });

    document.getElementById('traitementForm').addEventListener('submit', function(e) {
        if (!confirm('Voulez-vous valider cet enregistrement médical ?')) {
            e.preventDefault();
        }
    });
</script>

</body>
</html>