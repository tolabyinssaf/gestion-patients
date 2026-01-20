<?php
session_start();
include "../config/connexion.php";

// --- LOGIQUE PHP ORIGINALE ---
$errors = [
    "admission_en_cours" => ["icon" => "fa-solid fa-heart-crack", "class" => "alert-error"],
    "champ_vide" => ["icon" => "fa-solid fa-triangle-exclamation", "class" => "alert-warning"],
    "erreur_sql" => ["icon" => "fa-solid fa-file-medical", "class" => "alert-info"],
    "success" => ["icon" => "fa-solid fa-circle-check", "class" => "alert-success"],
    "erreur_patient" => ["icon" => "fa-solid fa-person-circle-xmark", "class" => "alert-error"],
    "erreur_date" => ["icon" => "fa-solid fa-calendar-xmark", "class" => "alert-warning"],
    "erreur_service" => ["icon" => "fa-solid fa-hospital-user", "class" => "alert-warning"],
    "medecin_manquant" => ["icon" => "fa-solid fa-user-doctor", "class" => "alert-warning"]
];

$message_type = "";
$message_text = "";

if (!isset($_SESSION['id_user'])) { $_SESSION['id_user'] = 1; }

$user_id = $_SESSION['user_id'] ?? $_SESSION['id_user'];
$stmt_user = $pdo->prepare("SELECT nom, prenom FROM utilisateurs WHERE id_user = ?");
$stmt_user->execute([$user_id]);
$user_info = $stmt_user->fetch(PDO::FETCH_ASSOC);

$patients = $pdo->query("SELECT id_patient, nom, prenom, cin, date_naissance, adresse, telephone FROM patients ORDER BY nom ASC")->fetchAll(PDO::FETCH_ASSOC);
$medecins = $pdo->query("SELECT id_user, nom, prenom FROM utilisateurs WHERE role = 'medecin' ORDER BY nom ASC")->fetchAll(PDO::FETCH_ASSOC);

$patient_preselectionne = null;
if (isset($_GET['id_patient'])) {
    $id_get = intval($_GET['id_patient']);
    foreach ($patients as $p) {
        if ($p['id_patient'] == $id_get) {
            $patient_preselectionne = $p;
            break;
        }
    }
}

$lastAdmissions = [];
foreach($patients as $p){
    $stmt = $pdo->prepare("SELECT a.date_admission, a.service, c.numero_chambre AS chambre, CONCAT(u.nom, ' ', u.prenom) AS nom_medecin 
                            FROM admissions a LEFT JOIN utilisateurs u ON a.id_medecin = u.id_user 
                            LEFT JOIN chambres c ON a.id_chambre = c.id_chambre WHERE a.id_patient=? ORDER BY a.date_admission DESC LIMIT 1");
    $stmt->execute([$p['id_patient']]);
    $lastAdmissions[$p['id_patient']] = $stmt->fetch(PDO::FETCH_ASSOC);
}

if(isset($_POST['submit'])) {
    try {
        if($_POST['id_patient'] === 'nouveau') { 
            header("Location: d2.php"); 
            exit; 
        }

        // 1. VERIFICATION MEDECIN (EMPECHE LE NULL)
        if (empty($_POST['id_medecin'])) {
            $message_type = "medecin_manquant";
            $message_text = "Erreur : Veuillez sélectionner un médecin dans la liste suggérée.";
        } else {
            $id_patient = $_POST['id_patient'];
            $date_admission = $_POST['date_admission'];
            $service = $_POST['service'];
            $motif = $_POST['motif'];
            $type_admission = $_POST['type_admission'] ?? 'Normal';
            $chambre = (!empty($_POST['chambre'])) ? $_POST['chambre'] : null;
            $id_medecin = $_POST['id_medecin']; 

            // Préparation de l'appel
            $stmt = $pdo->prepare("CALL sp_add_admission_safe(?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->bindValue(1, $id_patient, PDO::PARAM_INT);
            $stmt->bindValue(2, $date_admission);
            $stmt->bindValue(3, $service);
            $stmt->bindValue(4, $motif);
            $stmt->bindValue(5, $type_admission);
            $stmt->bindValue(6, $chambre, $chambre === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindValue(7, $id_medecin, PDO::PARAM_INT); // Ici on force l'INT car on a vérifié qu'il n'est pas vide

            $stmt->execute();
            $stmt->closeCursor();

            $message_type = "success";
            header("Location: admissions_list.php");
        }

    } catch(PDOException $e) {
        $errorMsg = $e->getMessage();
        if(strpos($errorMsg, 'Admission déjà en cours') !== false) $message_type = "admission_en_cours";
        elseif(strpos($errorMsg, 'Patient introuvable') !== false) $message_type = "erreur_patient";
        elseif(strpos($errorMsg, "Date d'admission invalide") !== false) $message_type = "erreur_date";
        elseif(strpos($errorMsg, 'Service invalide') !== false) $message_type = "erreur_service";
        else { 
            $message_type = "erreur_sql"; 
            $message_text = "Erreur base de données : " . $errorMsg; 
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>MedCare | Nouvelle Admission</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

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
            --gradient-vert: linear-gradient(135deg, #0f172a,#1ca499ff);
        }

        body.dark-mode {
            --bg-body: #0f172a;
            --white: #1e293b;
            --border: #334155;
            color: #f1f5f9;
        }

        * { margin:0; padding:0; box-sizing:border-box; font-family: 'Inter', sans-serif; }
        body { background: var(--bg-body); color: #1e293b; transition: 0.3s ease; }

        header {
            background: var(--white);
            padding: 0 40px; 
            height: var(--header-height);
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid var(--border);
            position: fixed; top: 0; left: 0; right: 0; z-index: 1000;
        }
        .logo { height: 45px; }
        .user-pill { background: var(--primary-light); padding: 8px 18px; border-radius: 12px; display: flex; align-items: center; gap: 10px; font-size: 14px; font-weight: 600; color: var(--primary); }

        .sidebar { 
            width: var(--sidebar-width); background: var(--sidebar-bg); padding: 24px 16px; 
            position: fixed; top: var(--header-height); left: 0; bottom: 0; z-index: 999;
        }
        .sidebar a { display: flex; align-items: center; gap: 12px; color: #94a3b8; text-decoration: none; padding: 12px 16px; border-radius: 10px; margin-bottom: 5px; transition: 0.2s; }
        .sidebar a:hover, .sidebar a.active { background: var(--primary); color: #fff; }

        .content { margin-left: var(--sidebar-width); margin-top: var(--header-height); padding: 40px; }

        .glass-card {
            background: var(--white);
            border-radius: 24px;
            border: 1px solid var(--border);
            box-shadow: 0 10px 40px rgba(0,0,0,0.04);
            padding: 40px;
            max-width: 1100px;
            margin: 0 auto;
        }

        .main-card-header {
            border-bottom: 1px solid var(--border);
            padding-bottom: 25px;
            margin-bottom: 35px;
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
            padding-bottom: 12px;
        }

        .section-icon {
            width: 40px;
            height: 40px;
            background: var(--gradient-vert);
            color: white;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .section-title-text {
            font-size: 16px;
            font-weight: 700;
            color: var(--primary);
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-label { font-weight: 600; font-size: 13px; color: #64748b; margin-bottom: 8px; }
        
        .form-control, .form-select {
            padding: 12px 16px;
            border-radius: 12px;
            border: 2px solid #f1f5f9;
            background: #f8fafc;
            font-weight: 500;
            transition: 0.3s;
        }

        .form-control:focus, .form-select:focus {
            background: #fff;
            border-color: var(--accent);
            box-shadow: 0 0 0 4px rgba(45, 212, 191, 0.1);
        }

        .search-container {
            background: #f8fafc;
            padding: 20px;
            border-radius: 16px;
            border: 1px solid var(--border);
        }

        .info-card {
            background: var(--primary-light);
            border: 1px solid rgba(15, 118, 110, 0.1);
            border-radius: 16px;
            padding: 20px;
        }

        .last-visit-badge {
            background: var(--gradient-vert);
            color: white;
            border-radius: 16px;
            padding: 20px;
        }

        .patient-list { 
            position: absolute; width: 100%; max-height: 250px; overflow-y: auto; 
            background: var(--white); border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); 
            z-index: 1050; display: none; border: 1px solid var(--border); margin-top: 5px;
        }
        .patient-item { padding: 12px 18px; border-bottom: 1px solid #f1f5f9; cursor: pointer; }
        .patient-item:hover { background: var(--primary-light); }

        .btn-submit {
            background: var(--gradient-vert);
            border: none;
            padding: 14px 35px;
            border-radius: 12px;
            font-weight: 700;
            color: white;
            transition: 0.3s;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(15, 118, 110, 0.2);
            color: white;
        }

        .alert-medical.alert-warning { 
            background: #fef9c3; 
            color: #854d0e;  
            border: 1px solid #fde047;
            border-radius: 12px;
            padding: 15px;
            font-weight: 500;
        }

        .alert-success { 
            background: #f0fdf4 !important; 
            border: 1px solid #bbf7d0 !important;
            color: #166534 !important;
        }
        
        .theme-switch { cursor: pointer; padding: 8px 16px; border-radius: 50px; background: #f1f5f9; border: 1px solid var(--border); font-size: 13px; font-weight: 600; }
    </style>
</head>
<body>

<header>
    <img src="../images/logo_app2.png" alt="Logo" class="logo">
    <div class="d-flex align-items-center gap-3">
        <button id="themeToggle" class="theme-switch">🌙 Mode Sombre</button>
        <div class="user-pill">
            <i class="fa-solid fa-user-tie"></i>
            <span>Séc. <?= htmlspecialchars($user_info['prenom']." ".$user_info['nom']) ?></span>
        </div>
    </div>
</header>

<div class="wrapper">
    <aside class="sidebar">
        <h3 style="color:rgba(255,255,255,0.3); font-size:11px; text-transform:uppercase; margin-bottom:20px; padding-left:12px;">Menu Gestion</h3>
        <a href="../connexion_secretaire/dashboard_secretaire.php"><i class="fa-solid fa-chart-line"></i> Vue Générale</a>
        <a href="../connexion_secretaire/patients_secr.php" ><i class="fa-solid fa-user-group"></i> Patients</a>
         <a href="admissions_list.php" class="active"><i class="fa-solid fa-hospital-user"></i> Admissions</a>
        <a href="../connexion_secretaire/suivis.php"><i class="fa-solid fa-calendar-check"></i> Suivis</a>
        <a href="../connexion_secretaire/caisse.php"><i class="fa-solid fa-wallet"></i> Caisse & Factures</a>
        <a href="archives_admissions.php"><i class="fa-solid fa-box-archive"></i> Archives</a>
         <a href="../connexion_secretaire/profil_secretaire.php"><i class="fa-solid fa-user"></i> Profil</a>
        <div style="height: 1px; background: rgba(255,255,255,0.1); margin: 20px 0;"></div>
        <a href="../connexio_utilisateur/deconnexion.php" style="color: #fda4af;"><i class="fa-solid fa-power-off"></i> Déconnexion</a>
    </aside>

    <main class="content">
        <div class="container-fluid">
            
            <?php if($message_type): ?>
            <div class="alert-medical <?= $errors[$message_type]['class'] ?> mb-4 shadow-sm">
                <div class="d-flex align-items-center gap-3">
                    <i class="<?= $errors[$message_type]['icon'] ?> fs-4"></i>
                    <div class="flex-grow-1"><?= htmlspecialchars($message_text) ?></div>
                    <button type="button" class="btn-close" onclick="this.parentElement.parentElement.style.display='none'"></button>
                </div>
            </div>
            <?php endif; ?>

            <div class="glass-card">
                <div class="main-card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h3 fw-800 mb-1" style="color: var(--primary); font-weight: 800;">Nouvelle Admission</h1>
                        <p class="text-muted small mb-0">Enregistrement d'un patient en salle d'attente ou hospitalisation</p>
                    </div>
                    <a href="admissions_list.php" class="btn btn-light btn-sm rounded-pill px-3">
                        <i class="fa-solid fa-arrow-left me-1"></i> Liste
                    </a>
                </div>

                <form method="POST" id="admissionForm">
                    
                    <div class="section-header">
                        <div class="section-icon"><i class="fa-solid fa-id-card"></i></div>
                        <h5 class="section-title-text">1. Identification du Patient</h5>
                    </div>

                    <?php if (!$patient_preselectionne): ?>
                    <div class="search-container mb-4">
                        <label class="form-label">Rechercher par Nom ou CIN</label>
                        <div class="input-group patient-search">
                            <span class="input-group-text bg-white border-0"><i class="fa-solid fa-magnifying-glass text-teal"></i></span>
                            <input type="text" id="patientSearch" class="form-control border-0" placeholder="Commencez à taper...">
                            
                            <div class="patient-list" id="patientList">
                                <?php foreach($patients as $p): ?>
                                    <div class="patient-item" data-id="<?= $p['id_patient'] ?>" 
                                        data-nom="<?= strtolower($p['nom']) ?>" 
                                        data-prenom="<?= strtolower($p['prenom']) ?>"
                                        data-cin="<?= strtolower($p['cin'] ?? '') ?>">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <span class="fw-bold d-block"><?= htmlspecialchars($p['nom'].' '.$p['prenom']) ?></span>
                                                <span class="text-muted small"><?= htmlspecialchars($p['cin'] ?? '-') ?></span>
                                            </div>
                                            <i class="fa-solid fa-chevron-right text-light"></i>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                <div class="patient-item text-primary fw-bold border-top" data-id="nouveau">
                                    <i class="fa-solid fa-plus-circle me-2"></i> Créer une nouvelle fiche
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="id_patient" id="id_patient" required>
                    </div>
                    <?php else: ?>
                        <div class="alert alert-light border d-flex align-items-center justify-content-between mb-4 shadow-sm" style="border-radius: 16px; background: #f8fafc;">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                                    <i class="fa-solid fa-user"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-bold"><?= htmlspecialchars($patient_preselectionne['nom'].' '.$patient_preselectionne['prenom']) ?></h6>
                                    <small class="text-muted">CIN: <?= htmlspecialchars($patient_preselectionne['cin'] ?? 'Non renseigné') ?></small>
                                </div>
                            </div>
                            <input type="hidden" name="id_patient" id="id_patient" value="<?= $patient_preselectionne['id_patient'] ?>" required>
                        </div>
                    <?php endif; ?>

                    <div id="patientInfo" class="mb-5" <?= $patient_preselectionne ? '' : 'style="display:none;"' ?>>
                        <div class="row g-3">
                            <div class="col-md-7">
                                <div class="info-card h-100">
                                    <div class="row g-3">
                                        <div class="col-6">
                                            <label class="form-label mb-0">Patient</label>
                                            <div class="fw-bold fs-5 text-uppercase">
                                                <span id="info_nom"><?= $patient_preselectionne['nom'] ?? '' ?></span> 
                                                <span id="info_prenom" class="text-capitalize"><?= $patient_preselectionne['prenom'] ?? '' ?></span>
                                            </div>
                                        </div>
                                        <div class="col-6 text-end">
                                            <span class="badge bg-white text-success border">Dossier Actif</span>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label mb-0">Né(e) le</label>
                                            <div id="info_date" class="fw-medium"><?= $patient_preselectionne['date_naissance'] ?? '' ?></div>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label mb-0">Téléphone</label>
                                            <div id="info_tel" class="fw-medium"><?= $patient_preselectionne['telephone'] ?? '' ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="last-visit-badge h-100">
                                    <?php 
                                        $last = $patient_preselectionne ? ($lastAdmissions[$patient_preselectionne['id_patient']] ?? null) : null;
                                    ?>
                                    <div class="small text-white-50 mb-2">DERNIÈRE VISITE</div>
                                    <div class="d-flex align-items-center gap-3 mb-2">
                                        <i class="fa-solid fa-clock-rotate-left opacity-50"></i>
                                        <span id="info_admission" class="fw-bold"><?= $last['date_admission'] ?? 'Aucune' ?></span>
                                    </div>
                                    <div class="d-flex align-items-center gap-3">
                                        <i class="fa-solid fa-hospital opacity-50"></i>
                                        <span class="fw-medium"><span id="info_service"><?= $last['service'] ?? '-' ?></span> (Ch. <span id="info_chambre"><?= $last['chambre'] ?? '-' ?></span>)</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="section-header">
                        <div class="section-icon"><i class="fa-solid fa-stethoscope"></i></div>
                        <h5 class="section-title-text">2. Détails de l'admission</h5>
                    </div>

                    <div class="row g-4 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">Date <span class="text-danger">*</span></label>
                            <input type="text" name="date_admission" id="date_admission" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Service <span class="text-danger">*</span></label>
                            <select name="service" id="serviceSelect" class="form-select" required>
                                <option value="">-- Sélectionner --</option>
                                <option value="Cardiologie">Cardiologie</option>
                                <option value="Pédiatrie">Pédiatrie</option>
                                <option value="Chirurgie">Chirurgie</option>
                                <option value="Urgences">Urgences</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Chambre / Lit</label>
                            <select name="chambre" id="chambreSelect" class="form-select">
                                <option value="">Choisir service d'abord...</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <div class="position-relative">
                                <label class="form-label">Médecin Responsable <span class="text-danger">*</span></label>
                                <input type="text" id="medecinSearch" class="form-control" placeholder="Nom du médecin..." required autocomplete="off">
                                <input type="hidden" name="id_medecin" id="id_medecin">
                                <div class="patient-list" id="medecinList">
                                    <?php foreach($medecins as $m): ?>
                                        <div class="medecin-item patient-item" data-id="<?= $m['id_user'] ?>" 
                                             data-nom="<?= strtolower($m['nom']) ?>" data-prenom="<?= strtolower($m['prenom']) ?>">
                                            Dr. <?= htmlspecialchars($m['nom'].' '.$m['prenom']) ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Priorité</label>
                            <select name="type_admission" class="form-select">
                                <option value="Normal">Consultation Normale</option>
                                <option value="Urgent">Urgence Médicale</option>
                                <option value="Programme">Admission Programmée</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Motif de consultation <span class="text-danger">*</span></label>
                            <textarea name="motif" class="form-control" rows="3" placeholder="Symptômes, notes importantes..." required></textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-3 pt-4 border-top">
                        <button type="reset" class="btn btn-light rounded-pill px-4">Effacer</button>
                        <button type="submit" name="submit" class="btn btn-submit">
                            <i class="fa-solid fa-check-circle me-2"></i>CONFIRMER L'ADMISSION
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
const patientsData = <?php echo json_encode($patients); ?>;
const lastAdmissions = <?php echo json_encode($lastAdmissions); ?>;

// --- Recherche Patient ---
const searchInput = $("#patientSearch");
const patientList = $("#patientList");

searchInput.on("focus click keyup", function(){
    patientList.show();
    let value = $(this).val().toLowerCase();
    $("#patientList .patient-item").each(function(){
        const nom = $(this).data("nom") || "";
        const prenom = $(this).data("prenom") || "";
        const cin = $(this).data("cin") || "";
        $(this).toggle(nom.includes(value) || prenom.includes(value) || cin.includes(value));
    });
});

$(document).on("click", "#patientList .patient-item", function(e){
    e.stopPropagation();
    const id = $(this).data("id");
    if(id === "nouveau"){ window.location.href = "d2.php"; return; }

    const patient = patientsData.find(p => p.id_patient == id);
    const lastAdm = lastAdmissions[id];

    $("#id_patient").val(id);
    searchInput.val(patient.nom + " " + patient.prenom);
    patientList.hide();

    $("#info_nom").text(patient.nom);
    $("#info_prenom").text(patient.prenom);
    $("#info_date").text(patient.date_naissance);
    $("#info_tel").text(patient.telephone);

    if(lastAdm){
        $("#info_admission").text(lastAdm.date_admission);
        $("#info_service").text(lastAdm.service);
        $("#info_chambre").text(lastAdm.chambre ?? "-");
    } else {
        $("#info_admission").text("Aucune");
        $("#info_service").text("-");
        $("#info_chambre").text("-");
    }
    $("#patientInfo").slideDown();
});

// --- Recherche Médecin ---
const medSearch = $("#medecinSearch");
const medList = $("#medecinList");

medSearch.on("focus click keyup", function(){
    medList.show();
    let value = $(this).val().toLowerCase();
    $("#medecinList .medecin-item").each(function(){
        const text = $(this).text().toLowerCase();
        $(this).toggle(text.includes(value));
    });
});

$(document).on("mousedown", ".medecin-item", function(e){
    e.preventDefault(); 
    const idMed = $(this).attr("data-id");
    const nomMed = $(this).text().trim();

    if(idMed){
        $("#id_medecin").val(idMed);
        $("#medecinSearch").val(nomMed);
        $("#medecinList").hide();
    }
});

// --- Sécurité Anti-Null à l'envoi ---
$("#admissionForm").on("submit", function(e) {
    if (!$("#id_medecin").val()) {
        e.preventDefault();
        alert("Attention : Vous devez sélectionner un médecin dans la liste suggérée !");
        $("#medecinSearch").focus();
    }
});

$(document).click(function(e){
    if(!$(e.target).closest(".position-relative, .search-container").length){
        patientList.hide();
        medList.hide();
    }
});

$('#serviceSelect').on('change', function(){
    let service = $(this).val();
    $('#chambreSelect').html('<option>Chargement...</option>');
    $.get('get_chambres.php', {service: service}, function(data){
        let chambres = JSON.parse(data);
        let options = '<option value="">-- Choisir Chambre --</option>';
        chambres.forEach(c => {
            options += `<option value="${c.id_chambre}">${c.numero_chambre}</option>`;
        });
        $('#chambreSelect').html(options);
    });
});

flatpickr("#date_admission", { dateFormat: "Y-m-d", defaultDate: new Date() });

const toggleBtn = document.getElementById("themeToggle");
if (localStorage.getItem("theme") === "dark") {
    document.body.classList.add("dark-mode");
    toggleBtn.innerText = "☀️ Mode Clair";
}
toggleBtn.addEventListener("click", () => {
    document.body.classList.toggle("dark-mode");
    const isDark = document.body.classList.contains("dark-mode");
    localStorage.setItem("theme", isDark ? "dark" : "light");
    toggleBtn.innerText = isDark ? "☀️ Mode Clair" : "🌙 Mode Sombre";
});
</script>
</body>
</html>