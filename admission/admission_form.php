<?php
session_start();
include "../config/connexion.php";

// 1. Définition des types d'erreurs
$errors = [
    "admission_en_cours" => ["icon" => "fa-solid fa-heart-crack", "class" => "alert-danger"],
    "Désolé, cette chambre est déjà complète" => ["icon" => "fa-solid fa-heart-crack", "class" => "alert-danger"],
    "champ_vide" => ["icon" => "fa-solid fa-triangle-exclamation", "class" => "alert-warning"],
    "erreur_sql" => ["icon" => "fa-solid fa-file-medical", "class" => "alert-info"],
    "success" => ["icon" => "fa-solid fa-circle-check", "class" => "alert-success"],
];

$message_type = "";
$message_text = "";

// 2. Gestion session utilisateur
if (!isset($_SESSION['id_user'])) { $_SESSION['id_user'] = 1; }
$user_id = $_SESSION['user_id'] ?? $_SESSION['id_user'];

$stmt_user = $pdo->prepare("SELECT nom, prenom FROM utilisateurs WHERE id_user = ?");
$stmt_user->execute([$user_id]);
$user_info = $stmt_user->fetch(PDO::FETCH_ASSOC);

// 3. Récupération listes
$patients = $pdo->query("SELECT id_patient, nom, prenom, cin, date_naissance, adresse, telephone FROM patients ORDER BY nom ASC")->fetchAll(PDO::FETCH_ASSOC);
$medecins = $pdo->query("SELECT id_user, nom, prenom FROM utilisateurs WHERE role = 'medecin' ORDER BY nom ASC")->fetchAll(PDO::FETCH_ASSOC);

// 4. Historique des admissions
$lastAdmissions = [];
foreach($patients as $p){
    $stmt = $pdo->prepare("SELECT a.date_admission FROM admissions a WHERE a.id_patient=? ORDER BY a.date_admission DESC LIMIT 1");
    $stmt->execute([$p['id_patient']]);
    $lastAdmissions[$p['id_patient']] = $stmt->fetch(PDO::FETCH_ASSOC);
}

// 5. TRAITEMENT DU FORMULAIRE
if(isset($_POST['submit'])) {
    try {
        if($_POST['id_patient'] === 'nouveau') { header("Location: d2.php"); exit; }
        
        $id_patient = intval($_POST['id_patient']);
        $type_admission = $_POST['type_admission'] ?? 'Normal';
        $chambre = !empty($_POST['chambre']) ? intval($_POST['chambre']) : null;
        $id_medecin = !empty($_POST['id_medecin']) ? intval($_POST['id_medecin']) : null;
        $date_adm = $_POST['date_admission'];
        $service = $_POST['service'];
        $motif = trim($_POST['motif']);

        $stmt = $pdo->prepare("CALL sp_add_admission_safe(?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$id_patient, $date_adm, $service, $motif, $type_admission, $chambre, $id_medecin]);
        $stmt->closeCursor();

        header("Location: admissions_list.php?success=1");
        exit;
        
    } catch(PDOException $e) {
        $errorMsg = $e->getMessage();
        if(strpos($errorMsg, 'Admission déjà en cours') !== false) {
            $message_type = "admission_en_cours"; $message_text = "Attention : Ce patient a déjà une hospitalisation active.";
        } elseif(strpos($errorMsg, 'Désolé, cette chambre est déjà complète') !== false) {
            $message_type = "Désolé, cette chambre est déjà complète"; $message_text = "Désolé, cette chambre est déjà complète.";
        } else {
            $message_type = "erreur_sql"; $message_text = "Erreur technique : " . $errorMsg;
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

        header { background: var(--white); padding: 0 40px; height: var(--header-height); display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; position: fixed; top: 0; left: 0; right: 0; z-index: 1000; }
        .sidebar { width: var(--sidebar-width); background: var(--sidebar-bg); padding: 24px 16px; position: fixed; top: var(--header-height); left: 0; bottom: 0; z-index: 999; }
        .sidebar h3 { color: rgba(255,255,255,0.3); font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 20px; padding-left: 12px; }
        .sidebar a { display: flex; align-items: center; gap: 12px; color: #94a3b8; text-decoration: none; padding: 12px 16px; border-radius: 10px; margin-bottom: 5px; transition: 0.3s; }
        .sidebar a:hover { background: rgba(255,255,255,0.05); color: #fff; }
        .sidebar a.active { background: var(--primary); color: #fff; }

        .user-pill { background: #f0fdfa; padding: 8px 18px; border-radius: 12px; display: flex; align-items: center; gap: 10px; font-size: 14px; font-weight: 600; color: var(--primary); }
        .content { margin-left: var(--sidebar-width); padding: 30px 40px; margin-top: var(--header-height); }

        .form-card { background: var(--white); border-radius: 24px; border: 1px solid #e2e8f0; box-shadow: 0 15px 35px -5px rgba(0, 0, 0, 0.07); max-width: 900px; margin: 0 auto; overflow: hidden; }
        .form-header-profile { padding: 40px; background: var(--primary); display: flex; align-items: center; gap: 25px; color: white; border-bottom: 4px solid rgba(0,0,0,0.1); }
        .avatar-huge { width: 90px; height: 90px; background: rgba(255,255,255,0.15); border-radius: 22px; display: flex; align-items: center; justify-content: center; font-size: 40px; border: 2px solid rgba(255,255,255,0.4); }
        .header-title-small { font-size: 13px; text-transform: uppercase; letter-spacing: 1.5px; font-weight: 600; opacity: 0.85; margin-bottom: 4px; }
        .header-title-main { font-size: 26px; font-weight: 800; margin-bottom: 6px; letter-spacing: -0.5px; }

        .form-body { padding: 40px; }
        .section-separator { display: flex; align-items: center; margin: 30px 0 20px 0; color: var(--primary); font-weight: 700; font-size: 13px; text-transform: uppercase; letter-spacing: 1px; }
        .section-separator::after { content: ""; flex: 1; height: 1px; background: #e2e8f0; margin-left: 15px; }

        /* INPUTS MODERNES (FONCÉS) */
        .form-label { font-weight: 600; font-size: 13px; color: #64748b; margin-bottom: 8px; display: block; }
        .input-group-custom { position: relative; }
        .input-group-custom i { position: absolute; left: 18px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 15px; z-index: 10; }
        .form-control-modern, .form-select-modern { 
            width: 100%; padding: 13px 15px 13px 50px; background: var(--input-dark); border: 1px solid var(--input-dark); 
            border-radius: 12px; color: #ffffff; font-size: 14px; transition: 0.3s; 
        }
        .form-control-modern:focus, .form-select-modern:focus { outline: none; background: #334155; box-shadow: 0 0 0 4px rgba(15, 118, 110, 0.2); }

        /* LISTE DE RECHERCHE */
        .patient-list { 
            position: absolute; width: 100%; max-height: 250px; overflow-y: auto; 
            background: #1e293b; border-radius: 12px; z-index: 1050; display: none; border: 1px solid #334155; margin-top: 5px;
        }
        .patient-item { padding: 12px 18px; border-bottom: 1px solid #334155; cursor: pointer; color: #cbd5e1; }
        .patient-item:hover { background: var(--primary); color: white; }

        /* BANNER PATIENT INFO */
        .patient-quick-info { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 15px 20px; margin-bottom: 20px; display: none; grid-template-columns: repeat(3, 1fr); gap: 15px; }
        .info-item .label { font-size: 11px; text-transform: uppercase; color: var(--primary); font-weight: 700; display: block; }
        .info-item .val { font-weight: 700; color: var(--sidebar-bg); font-size: 14px; }

        .btn-submit-modern {
            background: var(--primary); color: white; padding: 18px 35px; border-radius: 14px; font-weight: 700; border: none; transition: 0.3s;
            display: flex; align-items: center; gap: 12px; width: 100%; justify-content: center; text-transform: uppercase; letter-spacing: 1px; margin-top: 20px;
        }
        .btn-submit-modern:hover { background: var(--primary-hover); transform: translateY(-2px); box-shadow: 0 10px 20px rgba(15, 118, 110, 0.3); }
    </style>
</head>
<body>

<header>
    <img src="../images/logo_app2.png" alt="Logo" style="height: 45px;">
    <div class="user-pill">
        <i class="fa-solid fa-hospital-user"></i>
        <span>Secrétaire : <?= htmlspecialchars($user_info['prenom']." ".$user_info['nom']) ?></span>
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
         <a href="../connexion_secretaire/profil_secretaire.php"><i class="fa-solid fa-user"></i> Profil</a>
        <div style="height: 1px; background: rgba(255,255,255,0.1); margin: 20px 0;"></div>
        <a href="../connexio_utilisateur/deconnexion.php" style="color: #fda4af;"><i class="fa-solid fa-power-off"></i> Déconnexion</a>
    </aside>

    <main class="content">
        <div class="form-card">
            <div class="form-header-profile">
                <div class="avatar-huge"><i class="fa-solid fa-file-medical"></i></div>
                <div>
                    <div class="header-title-small">Gestion Hospitalière</div>
                    <div class="header-title-main">Nouvelle Admission</div>
                </div>
            </div>

            <?php if($message_type): ?>
                <div class="alert <?= $errors[$message_type]['class'] ?> mx-4 mt-4 mb-0 border-0 shadow-sm">
                    <i class="<?= $errors[$message_type]['icon'] ?> me-2"></i> <?= htmlspecialchars($message_text) ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="admissionForm" class="form-body">
                
                <div class="section-separator"><i class="fa-solid fa-user-tag"></i> 1. Identification Patient</div>
                
                <div class="col-12 mb-4">
                    <label class="form-label">Rechercher le patient (Nom ou CIN)</label>
                    <div class="input-group-custom">
                        <i class="fa-solid fa-search"></i>
                        <input type="text" id="patientSearch" class="form-control-modern" placeholder="Saisir pour rechercher...">
                        <div class="patient-list" id="patientList">
                            <?php foreach($patients as $p): ?>
                               <div class="patient-item" data-id="<?= $p['id_patient'] ?>" 
                                    data-nom="<?= strtolower($p['nom']) ?>" data-prenom="<?= strtolower($p['prenom']) ?>"
                                    data-cin="<?= strtolower($p['cin'] ?? '') ?>">
                                    <strong><?= htmlspecialchars($p['nom'].' '.$p['prenom']) ?></strong> 
                                    <span style="opacity:0.6; font-size:12px;"> (CIN: <?= htmlspecialchars($p['cin'] ?? '-') ?>)</span>
                               </div>
                            <?php endforeach; ?>
                            <div class="patient-item" data-id="nouveau" style="color: #5eead4; font-weight:700;">
                                <i class="fa-solid fa-plus-circle me-2"></i> Créer un nouveau patient
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="id_patient" id="id_patient" required>
                </div>

                <div id="patientInfo" class="patient-quick-info">
                    <div class="info-item"><span class="label">Identité</span><span class="val" id="info_nom_complet"></span></div>
                    <div class="info-item"><span class="label">Téléphone</span><span class="val" id="info_tel"></span></div>
                    <div class="info-item"><span class="label">Dernière Visite</span><span class="val" id="info_admission"></span></div>
                </div>

                <div class="section-separator"><i class="fa-solid fa-stethoscope"></i> 2. Détails de l'Hospitalisation</div>
                
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label">Date d'admission</label>
                        <div class="input-group-custom">
                            <i class="fa-solid fa-calendar"></i>
                            <input type="text" name="date_admission" id="date_admission" class="form-control-modern" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Service Médical</label>
                        <div class="input-group-custom">
                            <i class="fa-solid fa-hospitals"></i>
                            <select name="service" id="serviceSelect" class="form-select-modern" required>
                                <option value="">-- Choisir Service --</option>
                                <option value="Cardiologie">Cardiologie</option>
                                <option value="Pédiatrie">Pédiatrie</option>
                                <option value="Chirurgie">Chirurgie</option>
                                <option value="Urgences">Urgences</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Chambre / Lit</label>
                        <div class="input-group-custom">
                            <i class="fa-solid fa-bed"></i>
                            <select name="chambre" id="chambreSelect" class="form-select-modern">
                                <option value="">Choisir un service d'abord...</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Priorité</label>
                        <div class="input-group-custom">
                            <i class="fa-solid fa-clock"></i>
                            <select name="type_admission" class="form-select-modern">
                                <option value="Normal">Consultation Normale</option>
                                <option value="Urgent">Urgence Médicale</option>
                                <option value="Programme">Admission Programmée</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Médecin Traitant</label>
                        <div class="input-group-custom">
                            <i class="fa-solid fa-user-doctor"></i>
                            <input type="text" id="medecinSearch" class="form-control-modern" placeholder="Rechercher un médecin...">
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
                    <div class="col-12">
                        <label class="form-label">Motif de l'admission</label>
                        <div class="input-group-custom">
                            <i class="fa-solid fa-comment-medical" style="top:20px; transform:none;"></i>
                            <textarea name="motif" class="form-control-modern" rows="3" style="padding-top:15px;" placeholder="Symptômes ou observations..."></textarea>
                        </div>
                    </div>
                </div>

                <button type="submit" name="submit" class="btn-submit-modern">
                    <i class="fa-solid fa-check-circle"></i> Confirmer l'Admission
                </button>
            </form>
        </div>
    </main>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
const patientsData = <?= json_encode($patients); ?>;
const lastAdmissions = <?= json_encode($lastAdmissions); ?>;

// Recherche Patient (Même logique que l'original)
$("#patientSearch").on("focus click keyup", function(){
    $("#patientList").show();
    let value = $(this).val().toLowerCase();
    $("#patientList .patient-item").each(function(){
        const nom = $(this).data("nom") || "";
        const prenom = $(this).data("prenom") || "";
        const cin = $(this).data("cin") || "";
        $(this).toggle(nom.includes(value) || prenom.includes(value) || cin.includes(value));
    });
});

$(document).on("click", "#patientList .patient-item", function(){
    const id = $(this).data("id");
    if(id === "nouveau"){ window.location.href = "d2.php"; return; }
    const patient = patientsData.find(p => p.id_patient == id);
    const lastAdm = lastAdmissions[id];

    $("#id_patient").val(id);
    $("#patientSearch").val(patient.nom + " " + patient.prenom);
    $("#patientList").hide();

    $("#info_nom_complet").text(patient.nom.toUpperCase() + " " + patient.prenom);
    $("#info_tel").text(patient.telephone || 'N/C');
    $("#info_admission").text(lastAdm ? lastAdm.date_admission : "Nouveau Patient");
    $("#patientInfo").css('display', 'grid');
});

// Recherche Médecin
$("#medecinSearch").on("focus click keyup", function() {
    $("#medecinList").show();
    let value = $(this).val().toLowerCase();
    $(".medecin-item").each(function() {
        const nom = $(this).data("nom") || "";
        const prenom = $(this).data("prenom") || "";
        $(this).toggle(nom.includes(value) || prenom.includes(value));
    });
});

$(document).on("click", ".medecin-item", function() {
    $("#id_medecin").val($(this).data("id"));
    $("#medecinSearch").val($(this).text().trim());
    $("#medecinList").hide();
});

// AJAX Chambres
$('#serviceSelect').on('change', function(){
    let service = $(this).val();
    $.get('get_chambres.php', {service: service}, function(data){
        let chambres = JSON.parse(data);
        let options = '<option value="">-- Choisir Chambre --</option>';
        chambres.forEach(c => { options += `<option value="${c.id_chambre}">${c.numero_chambre}</option>`; });
        $('#chambreSelect').html(options);
    });
});

// Fermer listes au clic extérieur
$(document).click(function(e){
    if(!$(e.target).closest(".input-group-custom").length){
        $(".patient-list").hide();
    }
});

flatpickr("#date_admission", { dateFormat: "Y-m-d", defaultDate: new Date() });
</script>

</body>
</html>