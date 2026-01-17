<?php
session_start();
include("../config/connexion.php");

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'infirmier') {
    header("Location: ../login.php"); exit;
}

$user_id = $_SESSION['user_id'];
$id_admission = $_GET['id_adm'] ?? null;

// INFOS INFIRMIER
$stmt_u = $pdo->prepare("SELECT nom, prenom FROM utilisateurs WHERE id_user = ?");
$stmt_u->execute([$user_id]);
$user_inf = $stmt_u->fetch(PDO::FETCH_ASSOC);
$nom_complet = htmlspecialchars($user_inf['prenom'] . " " . $user_inf['nom']);

// INFOS PATIENT
$patient_info = null;
if ($id_admission) {
    $stmt_p = $pdo->prepare("SELECT a.id_admission, p.nom, p.prenom, p.cin FROM admissions a 
                             JOIN patients p ON a.id_patient = p.id_patient 
                             WHERE a.id_admission = ?");
    $stmt_p->execute([$id_admission]);
    $patient_info = $stmt_p->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>MedCare | Dossier de Soins</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #0f766e; 
            --primary-hover: #0d9488;
            --sidebar-bg: #0f172a;
            --bg-body: #f8fafc;
            --border-color: #e2e8f0;
            /* Nouvelles couleurs pour les sections */
            --temp-color: #ef4444;   /* Rouge */
            --tension-color: #3b82f6; /* Bleu */
            --pulse-color: #f59e0b;   /* Orange */
            --act-color: #0f766e;     /* Vert Médical */
            --obs-color: #8b5cf6;     /* Violet */
        }

        body { background: var(--bg-body); font-family: 'Plus Jakarta Sans', sans-serif; color: #334155; }

        header { background: #fff; height: 70px; position: fixed; top: 0; width: 100%; display: flex; align-items: center; padding: 0 30px; justify-content: space-between; border-bottom: 1px solid var(--border-color); z-index: 1000; }
        .sidebar { width: 260px; background: var(--sidebar-bg); position: fixed; top: 70px; left: 0; bottom: 0; padding: 20px; z-index: 999; }
        .content { margin-left: 260px; margin-top: 70px; padding: 40px; }
        .user-pill { background: #f0fdfa; padding: 8px 16px; border-radius: 12px; font-size: 14px; font-weight: 600; color: var(--primary); border: 1px solid #ccfbf1; }
        
        .sidebar a { display: flex; align-items: center; gap: 12px; color: #94a3b8; text-decoration: none; padding: 12px 15px; border-radius: 10px; margin-bottom: 5px; transition: 0.3s; }
        .sidebar a.active { background: var(--primary); color: #fff; box-shadow: 0 4px 12px rgba(15, 118, 110, 0.3); }

        .patient-header-card {
            background: linear-gradient(135deg, #0f172a 0%, #0f766e 100%);
            border-radius: 16px; padding: 20px 30px; color: white;
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 30px; box-shadow: 0 10px 25px rgba(15, 23, 42, 0.15);
        }
        .patient-info h2 { font-size: 1.4rem; font-weight: 700; margin: 0; letter-spacing: -0.5px; }
        .patient-info p { font-size: 0.85rem; margin: 5px 0 0 0; opacity: 0.8; font-weight: 500; }
        .status-badge { background: rgba(34, 197, 94, 0.2); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.3); padding: 5px 12px; border-radius: 8px; font-size: 0.75rem; font-weight: 700; }

        .medical-card {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid var(--border-color);
            padding: 24px;
            margin-bottom: 24px;
            transition: transform 0.2s;
        }

        /* --- STYLES DE COULEURS --- */
        .card-constantes { border-top: 4px solid var(--temp-color); }
        .card-actes { border-top: 4px solid var(--act-color); }
        .card-obs { border-top: 4px solid var(--obs-color); }

        .card-title-line {
            display: flex; align-items: center; gap: 12px; margin-bottom: 20px;
            font-size: 0.95rem; font-weight: 700; color: #1e293b;
            text-transform: uppercase; letter-spacing: 0.5px;
        }

        /* Couleurs spécifiques des icônes et bordures de groupes */
        .color-temp { border-left: 3px solid var(--temp-color) !important; }
        .color-temp i { color: var(--temp-color); }
        
        .color-tension { border-left: 3px solid var(--tension-color) !important; }
        .color-tension i { color: var(--tension-color); }
        
        .color-pulse { border-left: 3px solid var(--pulse-color) !important; }
        .color-pulse i { color: var(--pulse-color); }

        .input-group-custom {
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            padding: 10px 15px;
            transition: all 0.3s;
            background: #fff;
        }
        .input-group-custom:focus-within {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(15, 118, 110, 0.1);
        }
        .input-group-custom label {
            display: block; font-size: 0.7rem; font-weight: 700;
            color: #64748b; margin-bottom: 4px; text-transform: uppercase;
        }
        .input-group-custom input, .input-group-custom select {
            border: none; outline: none; width: 100%; font-weight: 600;
            color: #1e293b; font-size: 1.1rem; background: transparent;
        }
        .unit-text { font-size: 0.85rem; font-weight: 700; color: #94a3b8; }

        .observation-area {
            width: 100%; border: 1.5px solid #e2e8f0; border-radius: 12px;
            padding: 15px; font-weight: 500; resize: none; transition: 0.3s;
        }
        .observation-area:focus {
            outline: none; border-color: var(--obs-color);
            box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.1);
        }

        .btn-save {
            background: var(--primary); color: white; border: none;
            padding: 14px 40px; border-radius: 12px; font-weight: 700;
            letter-spacing: 0.5px; transition: 0.3s;
            box-shadow: 0 4px 12px rgba(15, 118, 110, 0.2);
        }
        .btn-save:hover { background: var(--primary-hover); transform: translateY(-2px); }

    </style>
</head>
<body>

<header>
    <div class="d-flex align-items-center">
        <img src="../images/logo_app2.png" style="height: 38px;">
    </div>
    <div class="user-pill">
        <i class="fa-solid fa-user-nurse me-2"></i>
        <span>Infirmier : <?= $nom_complet ?></span>
    </div>
</header>

    <aside class="sidebar">
        <h3 style="color:rgba(255,255,255,0.3); font-size:11px; text-transform:uppercase; margin-bottom:20px; padding-left:12px;">Menu Infirmier</h3>
        <a href="dashboard_infirmier.php" ><i class="fa-solid fa-chart-line"></i> Tableau de bord</a>
        <a href="liste_patients_inf.php"><i class="fa-solid fa-user-injured"></i> Liste des Patients</a>
        <a href="saisir_soins.php" class="active"><i class="fa-solid fa-notes-medical"></i> Saisir un Soin</a>
        <a href="planning.php"><i class="fa-solid fa-calendar-alt"></i> Planning</a>
        <div style="height: 1px; background: rgba(255,255,255,0.1); margin: 20px 0;"></div>
        <a href="../connexio_utilisateur/deconnexion.php" style="color: #fda4af;"><i class="fa-solid fa-power-off"></i> Déconnexion</a>
    </aside>

<main class="content">
    <div class="container" style="max-width: 900px;">
        
        <?php if($patient_info): ?>
        <div class="patient-header-card">
            <div class="d-flex align-items-center">
                <div style="width: 50px; height: 50px; background: rgba(255,255,255,0.15); border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-right: 20px;">
                    <i class="fa-solid fa-user-injured" style="font-size: 1.5rem;"></i>
                </div>
                <div class="patient-info">
                    <h2><?= strtoupper($patient_info['nom']) ?> <?= $patient_info['prenom'] ?></h2>
                    <p>CIN : <?= $patient_info['cin'] ?> | Admission : #<?= $patient_info['id_admission'] ?></p>
                </div>
            </div>
            <div class="status-badge">PATIENT HOSPITALISÉ</div>
        </div>
        <?php endif; ?>

        <form action="save_soins.php" method="POST">
            <input type="hidden" name="id_admission" value="<?= $id_admission ?>">

            <div class="medical-card card-constantes">
                <div class="card-title-line">
                    <i class="fa-solid fa-heart-pulse"></i> Constantes Vitales
                </div>
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="input-group-custom color-temp">
                            <label><i class="fa-solid fa-temperature-half me-1"></i> Température</label>
                            <div class="d-flex align-items-center">
                                <input type="number" step="0.1" name="temperature" placeholder="37.0">
                                <span class="unit-text">°C</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="input-group-custom color-tension">
                            <label><i class="fa-solid fa-droplet me-1"></i> Tension Artérielle</label>
                            <div class="d-flex align-items-center">
                                <input type="text" name="tension" placeholder="12/8">
                                <span class="unit-text">mmHg</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="input-group-custom color-pulse">
                            <label><i class="fa-solid fa-stethoscope me-1"></i> Fréquence Cardiaque</label>
                            <div class="d-flex align-items-center">
                                <input type="number" name="frequence_c" placeholder="75">
                                <span class="unit-text">BPM</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="medical-card card-actes">
                <div class="card-title-line">
                    <i class="fa-solid fa-hand-holding-medical"></i> Actes & Médication
                </div>
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="input-group-custom">
    <label><i class="fa-solid fa-kit-medical me-1"></i> Type d'acte réalisé</label>
    <select name="id_prestation" class="form-select" required>
        <option value="">-- Choisir la prestation --</option>
        <?php
       
        $stmt_pres = $pdo->query("SELECT id_prestation, nom_prestation, prix_unitaire FROM prestations ORDER BY nom_prestation ASC");
        while($pres = $stmt_pres->fetch()) {
            echo "<option value='{$pres['id_prestation']}'>{$pres['nom_prestation']} ({$pres['prix_unitaire']} DH)</option>";
        }
        ?>
    </select>
</div>
                    </div>
                    <div class="col-md-6">
                        <div class="input-group-custom">
                            <label><i class="fa-solid fa-pills me-1"></i> Produit / Dosage</label>
                            <input type="text" name="medicament" placeholder="Ex: Perfalgan 1g">
                        </div>
                    </div>
                </div>
            </div>

            <div class="medical-card card-obs">
                <div class="card-title-line" style="color: var(--obs-color);">
                    <i class="fa-solid fa-comment-dots"></i> Observations Cliniques
                </div>
                <textarea name="observations" class="observation-area" rows="4" placeholder="Saisissez ici les détails importants de la surveillance..."></textarea>
            </div>

            <div class="text-end mb-5">
                <button type="submit" class="btn-save">
                    <i class="fa-solid fa-check-circle me-2"></i> VALIDER LE DOSSIER DE SOINS
                </button>
            </div>
        </form>
    </div>
</main>

</body>
</html>




 