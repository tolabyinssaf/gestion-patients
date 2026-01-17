<?php
session_start();
include "../config/connexion.php";

// تعريف الرسائل والـ themes
$errors = [
    "admission_en_cours" => [
        "icon" => "bi bi-heartbreak-fill",
        "class" => "alert-error"
    ],
    "champ_vide" => [
        "icon" => "bi bi-exclamation-triangle-fill",
        "class" => "alert-warning"
    ],
    "erreur_sql" => [
        "icon" => "bi bi-file-medical-fill",
        "class" => "alert-info"
    ],
    "success" => [
        "icon" => "bi bi-heart-fill",
        "class" => "alert-success"
    ],
    "erreur_patient" => [
        "icon" => "bi bi-person-x-fill",
        "class" => "alert-error"
    ],
    "erreur_date" => [
        "icon" => "bi bi-calendar-x",
        "class" => "alert-warning"
    ],
    "erreur_service" => [
        "icon" => "bi bi-hospital-x",
        "class" => "alert-warning"
    ]
];
$stmt = $pdo->prepare("
    SELECT 
        a.date_admission,
        a.service,
        c.numero_chambre AS chambre,   -- الرقم ديال الغرفة
        CONCAT(u.nom, ' ', u.prenom) AS nom_medecin
    FROM admissions a
    LEFT JOIN utilisateurs u ON a.id_medecin = u.id_user
    LEFT JOIN chambres c ON a.id_chambre = c.id_chambre
    WHERE a.id_patient=?
    ORDER BY a.date_admission DESC
    LIMIT 1
");




$message_type = "";
$message_text = "";

if (!isset($_SESSION['id_user'])) {
    $_SESSION['id_user'] = 1; // admin par défaut
}

// جلب جميع المرضى
$patients = $pdo->query("
    SELECT id_patient, nom, prenom, cin, date_naissance, adresse, telephone
    FROM patients
    ORDER BY nom ASC
")->fetchAll(PDO::FETCH_ASSOC);
 
$medecins = $pdo->query("
    SELECT id_user, nom, prenom
    FROM utilisateurs
    WHERE role = 'medecin'
    ORDER BY nom ASC
")->fetchAll(PDO::FETCH_ASSOC);

// جلب آخر admission لكل patient
$lastAdmissions = [];
foreach($patients as $p){
 $stmt = $pdo->prepare("
    SELECT 
        a.date_admission,
        a.service,
        c.numero_chambre AS chambre,   -- الرقم ديال الغرفة
        CONCAT(u.nom, ' ', u.prenom) AS nom_medecin
    FROM admissions a
    LEFT JOIN utilisateurs u ON a.id_medecin = u.id_user
    LEFT JOIN chambres c ON a.id_chambre = c.id_chambre
    WHERE a.id_patient=?
    ORDER BY a.date_admission DESC
    LIMIT 1
");

    $stmt->execute([$p['id_patient']]);
    $lastAdmissions[$p['id_patient']] = $stmt->fetch(PDO::FETCH_ASSOC);
}


if(isset($_POST['submit'])) {
    try {
        if($_POST['id_patient'] === 'nouveau') {
            header("Location: d2.php");
            exit;
        } else {
            $id_patient = $_POST['id_patient'];
        }
     
         $type_admission = $_POST['type_admission'] ?? 'Normal';
$chambre = $_POST['chambre'] ?? null;
$id_medecin = empty($_POST['id_medecin']) ? null : $_POST['id_medecin'];

$stmt = $pdo->prepare("CALL sp_add_admission_safe(?,?,?,?,?,?,?)");
$stmt->execute([
    $id_patient,
    $_POST['date_admission'],
    $_POST['service'],
    $_POST['motif'],
    $type_admission,
    $chambre,
    $id_medecin
]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $id_admission = $result['id_admission'] ?? null;
        $stmt->closeCursor();

        // // Ajouter log
        // $stmt_log = $pdo->prepare("INSERT INTO admission_logs (id_admission, user_id, action, description) VALUES (?, ?, ?, ?)");
        // $stmt_log->execute([
        //     $id_admission,
        //     $_SESSION['id_user'],
        //     'ajout',
        //     'Admission ajoutée pour patient ID ' . $id_patient
        // ]);

        $message_type = "success";
        $message_text = "Admission ajoutée avec succès !";

    } catch(PDOException $e) {
        $errorMsg = $e->getMessage();
        if(strpos($errorMsg, 'Admission déjà en cours') !== false){
            $message_type = "admission_en_cours";
            $message_text = "Ce patient a déjà une admission en cours.";
        } elseif(strpos($errorMsg, 'Patient introuvable') !== false){
            $message_type = "erreur_patient";
            $message_text = "Patient introuvable !";
        } elseif(strpos($errorMsg, "Date d'admission invalide") !== false){
            $message_type = "erreur_date";
            $message_text = "La date d'admission ne peut pas être dans le passé.";
        } elseif(strpos($errorMsg, 'Service invalide') !== false){
            $message_type = "erreur_service";
            $message_text = "Service invalide !";
        }  elseif(strpos($errorMsg, 'Le service est obligatoire') !== false){
            $message_type = "champ_vide";
            $message_text = "Le champ service est obligatoire.";

        } elseif(strpos($errorMsg, 'Le motif est obligatoire') !== false){
            $message_type = "champ_vide";
            $message_text = "Le champ motif est obligatoire.";

        
        }else {
            $message_type = "erreur_sql";
            $message_text = "Erreur lors de l'ajout de l'admission.";
        }
        } catch(Exception $e) {
        $message_type = "erreur_sql";
        $message_text = "Erreur inattendue !";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Nouvelle Admission</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<style>
body{background:#f2f6f6;}
.card-header{background:#01A28C; color:#fff;}
.text-main{color:#01A28C;}
.form-control:focus, .form-select:focus {border-color: #01A28C; box-shadow: 0 0 8px rgba(1,162,140,0.5);}
.is-invalid {border-color: #dc3545 !important; box-shadow: 0 0 5px rgba(220,53,69,0.4);}
.alert-custom {background:#f8d7da; color:#842029; border:none;}
.badge-status {font-size: 0.9rem; padding: 0.5em 0.75em;}
.patient-search {position: relative;}
.patient-list {position: absolute; width: 100%; max-height: 220px; overflow-y: auto; background: #fff; border-radius: 10px; box-shadow: 0 10px 25px rgba(0,0,0,0.15); z-index: 1000; display: none;}
.patient-item {padding: 10px 15px; cursor: pointer; border-bottom: 1px solid #f1f1f1;}
.patient-item:hover {background: #e0f7f5;}
.agenda-box {background: linear-gradient(135deg, #01A28C, #01907d); color: white; border-radius: 20px; padding: 25px; text-align: center;}
.agenda-box i {font-size: 2.5rem; margin-bottom: 10px;}
.agenda-box p {margin: 5px 0; font-size: 1.05rem;}
/* Alertes Médicales - White Background, Centered Text, Theme Colors */
.alert-medical {
    border-radius: 16px;
    padding: 18px 20px;
    display: flex;
    align-items: center;
    justify-content: center; /* النص فالوسط */
    gap: 15px;
    font-weight: 600;
    font-size: 1rem;
    background: #fff; /* خلفية بيضاء */
    position: relative;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    border-left: 6px solid transparent;
    text-align: center;
    transition: transform 0.3s, box-shadow 0.3s;
    animation: slideDown 0.5s ease-out forwards;
}

/* Hover effect */
.alert-medical:hover {
    transform: translateY(-2px);
}

/* Icon */
.alert-medical i {
    font-size: 1.8rem;
}

/* Types with colored text and shadow (cool/gray theme) */
.alert-error { 
    color: #e74c3c; /* أحمر */
    border-left-color: #e74c3c;
    box-shadow: 0 4px 20px rgba(231,76,60,0.3);
}

.alert-warning { 
    color: #7f8c8d; /* رمادي هادئ بدل الأصفر */
    border-left-color: #7f8c8d;
    box-shadow: 0 4px 20px rgba(127,140,141,0.3);
}

.alert-info { 
    color: #1e2a38; /* لون theme أزرق داكن */
    border-left-color: #1e2a38;
    box-shadow: 0 4px 20px rgba(30,42,56,0.3);
}

.alert-success { 
    color: #01A28C; /* أخضر theme */
    border-left-color: #01A28C;
    box-shadow: 0 4px 20px rgba(1,162,140,0.3);
}

/* Bouton fermeture */
.alert-medical .btn-close {
    position: absolute;
    top: 12px;
    right: 12px;
    background: rgba(0,0,0,0.05);
    border-radius: 50%;
    width: 26px;
    height: 26px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: inherit; /* نفس لون النص */
    transition: background 0.3s;
}

.alert-medical .btn-close:hover {
    background: rgba(0,0,0,0.1);
}

/* Animation slide */
@keyframes slideDown {
    from { transform: translateY(-40px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}
/* تعديل حجم و theme ديال الـ calendar */
.flatpickr-calendar {
    width: 620px !important;       /* width أكبر */
    background-color: #01A28C !important;
    color: #fff;
    border-radius: 12px !important;
    box-shadow: 0 5px 20px rgba(0,0,0,0.2);
    
    /* تمركز calendar فوق input */
    left: 50% !important;
    transform: translateX(-50%) !important;
}

/* الأيام */
.flatpickr-day {
    
    color: #fff;
}

.flatpickr-day.today {
    background: #01907d;
}

.flatpickr-day.selected {
    background: #f9a825;
    color: #000;
}

.flatpickr-weekday {
    color: #fff;
}

/* hover effect على الأيام */
.flatpickr-day:hover {
    background: #f9a825;
    color: #000;
    border-radius: 6px;
    transition: 0.2s;
}
/* =============================== */
/* 🌑 DARK MEDICAL MODE — ADMISSION */
/* =============================== */

body.dark-mode {
    background: radial-gradient(circle at top, #020617, #000);
    color: #cbd5e1;
}

/* ===== CARD PRINCIPALE ===== */
body.dark-mode .card {
    background: linear-gradient(180deg, #020a1f, #020617);
    border: 1px solid #1e293b;
    box-shadow: 0 20px 40px rgba(0,0,0,0.85);
}

/* ===== HEADER ===== */
body.dark-mode .card-header {
    background: linear-gradient(90deg, #14b8a6, #020617);
    color: #fff;
}
body.dark-mode label{
    color: #c1f0eb;
}

/* ===== TITRES ===== */
body.dark-mode h4,
body.dark-mode h5,
body.dark-mode .text-main {
    color: #5eead4 !important;
}

/* ===== INPUTS ===== */
body.dark-mode .form-control,
body.dark-mode .form-select,
body.dark-mode textarea {
    background-color: #020617;
    border: 1px solid #1e293b;
    color: #e5e7eb;
}

body.dark-mode .form-control::placeholder {
    color: #64748b;
}

body.dark-mode .form-control:focus {
    border-color: #14b8a6;
    box-shadow: 0 0 0 1px rgba(20,184,166,0.4);
}

/* ===== PATIENT LIST ===== */
body.dark-mode .patient-list {
    background: #020617;
    border: 1px solid #1e293b;
}

body.dark-mode .patient-item {
    color: #cbd5e1;
}

body.dark-mode .patient-item:hover {
    background: rgba(20,184,166,0.15);
}

/* ===== INFO CARDS ===== */
body.dark-mode .agenda-box {
    background: linear-gradient(135deg, #0f766e, #020617);
}

/* ===== ALERT MEDICAL ===== */
body.dark-mode .alert-medical {
    background: #020617;
}

/* ===== BUTTONS ===== */
body.dark-mode .btn {
    border-radius: 12px;
}

body.dark-mode .btn-outline-secondary {
    border-color: #14b8a6;
    color: #14b8a6;
}

body.dark-mode .btn-outline-secondary:hover {
    background: #14b8a6;
    color: #020617;
}

body.dark-mode .btn-success,
body.dark-mode .btn[style] {
    background: linear-gradient(135deg, #14b8a6, #0f766e) !important;
    border: none;
    color: #fff;
    box-shadow: 0 10px 25px rgba(20,184,166,0.4);
}

/* ===== FLATPICKR ===== */
body.dark-mode .flatpickr-calendar {
    background: #020617 !important;
    color: #fff;
    border: 1px solid #14b8a6;
}

body.dark-mode .flatpickr-day {
    color: #fff;
}

body.dark-mode .flatpickr-day.selected {
    background: #14b8a6;
    color: #020617;
}



</style>
</head>

<body class="p-4">
    <div class="d-flex justify-content-end mb-3">
    <button id="themeToggle" class="btn btn-outline-secondary btn-sm">
        🌙 Dark
    </button>
</div>

<div class="container">
<div class="card shadow-lg border-0">
    <div class="card-header ">
        <h4 class="mb-0"><i class="bi bi-clipboard-heart me-2"></i>Nouvelle Admission</h4>
        <small>Gestion des admissions</small>
    </div>
    <div class="card-body">
<?php if($message_type): ?>
<div class="alert-medical <?= $errors[$message_type]['class'] ?>" id="alertMedical">
    <i class="<?= $errors[$message_type]['icon'] ?>"></i>
    <div><?= htmlspecialchars($message_text) ?></div>
    <button type="button" class="btn-close" onclick="$('#alertMedical').fadeOut('slow')"></button>
</div>

<script>
setTimeout(() => { $("#alertMedical").fadeOut('slow'); }, 5000);
</script>
<?php endif; ?>

        <form method="POST" id="admissionForm">
            <h5 class="text-main mt-3"><i class="bi bi-person-lines-fill me-1"></i>Informations Patient</h5>
            <hr>
            <div class="mb-3">
                <label class="form-label"><i class="bi bi-person me-1"></i>Patient</label>
               <div class="mb-3 patient-search">
                <input type="text" id="patientSearch" class="form-control" placeholder="Tapez le nom du patient...">
                <input type="hidden" name="id_patient" id="id_patient" required>
                <div class="patient-list" id="patientList">
                    <?php foreach($patients as $p): ?>
                       <div class="patient-item"
                            data-id="<?= $p['id_patient'] ?>"
                            data-nom="<?= strtolower($p['nom']) ?>"
                            data-prenom="<?= strtolower($p['prenom']) ?>"
                            data-cin="<?= strtolower($p['cin'] ?? '') ?>">
                            <i class="bi bi-person-circle me-1 text-main"></i>
                            <?= htmlspecialchars($p['nom'].' '.$p['prenom']) ?>
                            <small class="text-muted">(<?= htmlspecialchars($p['cin'] ?? '-') ?>)</small>
                        </div>

                    <?php endforeach; ?>
                    <div class="patient-item text-success fw-bold" data-id="nouveau">
                        <i class="bi bi-plus-circle me-1"></i> Nouveau patient
                    </div>
                </div>
            </div>
            </div>

            <div id="patientInfo" style="display:none;" class="mt-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="card shadow-sm border-0">
                            <div class="card-body">
                                <h5 class="card-title text-main text-center"><i class="bi bi-person-circle me-1 "></i>Infos Patient</h5>
                                <hr>
                                <p><i class="bi bi-person me-1"></i> <strong>Nom:</strong> <span id="info_nom"></span></p>
                                <p><i class="bi bi-person-badge me-1"></i> <strong>Prénom:</strong> <span id="info_prenom"></span></p>
                                <p><i class="bi bi-calendar-event me-1"></i> <strong>Date Naissance:</strong> <span id="info_date"></span></p>
                                <p><i class="bi bi-geo-alt me-1"></i> <strong>Adresse:</strong> <span id="info_adresse"></span></p>
                                <p><i class="bi bi-telephone me-1"></i> <strong>Téléphone:</strong> <span id="info_tel"></span></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-body d-flex align-items-center justify-content-center">
                                <div class="agenda-box w-100">
                                    <i class="bi bi-calendar-heart"></i>
                                    <h5 class="mb-3">Dernière Admission</h5>
                                    <p><strong>Date :</strong> <span id="info_admission">-</span></p>
                                    <p><strong>Service :</strong> <span id="info_service">-</span></p>
                                    <p><strong>Chambre :</strong> <span id="info_chambre">-</span></p>
                                   <p><strong>Médecin :</strong> <span id="info_medecin">-</span></p>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <h5 class="text-main mt-4"><i class="bi bi-hospital me-1"></i>Informations Admission</h5>
            <hr>
            <div class="mb-3">
                <label class="form-label"><i class="bi bi-calendar-date me-1"></i>Date admission</label>
               <input type="text" name="date_admission" id="date_admission" class="form-control" placeholder="YYYY-MM-DD" required>

            </div>
            <div class="mb-3">
                <label class="form-label"><i class="bi bi-heart-pulse me-1"></i>Service</label>
                <select name="service" id="serviceSelect" class="form-select">
                    <option value="">-- Choisir Service --</option>
                    <option value="Cardiologie">Cardiologie</option>
                    <option value="Pédiatrie">Pédiatrie</option>
                    <option value="Chirurgie">Chirurgie</option>
                </select>
                </div>
    <div class="mb-3">
    <label class="form-label"><i class="bi bi-door-open me-1"></i>Chambre</label>
      <select name="chambre" id="chambreSelect" class="form-select">
    <option value="">-- Choisir Chambre --</option>
</select>


    </div>

<!-- <div class="mb-3">
    <label class="form-label"><i class="bi bi-door-open me-1"></i>Chambre</label>
    <input type="text" name="chambre" class="form-control" placeholder="Ex: 101A">
</div> -->

<div class="mb-3 patient-search">
    <label class="form-label">
        <i class="bi bi-person-badge me-1"></i>Médecin
    </label>

    <input type="text" id="medecinSearch" class="form-control"
           placeholder="Nom ou prénom du médecin...">

    <input type="hidden" name="id_medecin" id="id_medecin">

    <div class="patient-list" id="medecinList">
        <?php foreach($medecins as $m): ?>
            <div class="medecin-item patient-item"
                 data-id="<?= $m['id_user'] ?>"
                 data-nom="<?= strtolower($m['nom']) ?>"
                 data-prenom="<?= strtolower($m['prenom']) ?>">
                <i class="bi bi-person-badge me-1 text-main"></i>
                <?= htmlspecialchars($m['nom'].' '.$m['prenom']) ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>



            <div class="mb-3">
                <label class="form-label"><i class="bi bi-chat-left-text me-1"></i>Motif</label>
                <textarea name="motif" class="form-control" ></textarea>
            </div>
            
            <div class="d-flex gap-2">
                <button name="submit" class="btn " style=" background:#01907d"><i class="bi bi-check-circle me-1"></i>Ajouter Admission</button>
                <a href="admissions_list.php" class="btn btn-outline-secondary"><i class="bi bi-list-ul me-1"></i>Liste</a>
            </div>

        </form>
    </div>
</div>
</div>

<script>
const patientsData = <?php echo json_encode($patients); ?>;
const lastAdmissions = <?php echo json_encode($lastAdmissions); ?>;

const searchInput = $("#patientSearch");
const patientList = $("#patientList");

searchInput.on("focus click keyup", function(){
    patientList.show();
    let value = $(this).val().toLowerCase();

    $(".patient-item").each(function(){
        const nom = $(this).data("nom") || "";
        const prenom = $(this).data("prenom") || "";
        const cin = $(this).data("cin") || "";

        $(this).toggle(
            nom.includes(value) ||
            prenom.includes(value) ||
            cin.includes(value)
        );
    });
});


$(document).on("click", "#patientList .patient-item", function(){

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
    $("#info_adresse").text(patient.adresse);
    $("#info_tel").text(patient.telephone);

    if(lastAdm){
    $("#info_admission").text(lastAdm.date_admission);
    $("#info_service").text(lastAdm.service);
$("#info_chambre").text(lastAdm.chambre ?? "-");
    $("#info_medecin").text(lastAdm.nom_medecin ?? "-");


    } else {
        $("#info_admission").text("Aucune admission");
        $("#info_service").text("-");
    }

    $("#patientInfo").slideDown();
});

$(document).click(function(e){
    if(!$(e.target).closest(".patient-search").length){
        patientList.hide();
    }
});
</script>
<script>
flatpickr("#date_admission", {
    dateFormat: "Y-m-d",
    defaultDate: new Date(),
    position: "above", 
    allowInput: true,
    onReady: function(selectedDates, dateStr, instance) {
        const cal = instance.calendarContainer;
        cal.style.backgroundColor = "#01A28C";   // theme
        cal.style.color = "#fff";                 // text color
        cal.style.borderRadius = "10px";
        cal.style.padding = "10px";
        cal.style.boxShadow = "0 5px 20px rgba(0,0,0,0.15)";
        cal.style.width = "280px";               // width
        cal.style.top = "-250px";    
        instance.calendarContainer.style.zIndex = "1000";            // فوق input
    },
    
    onOpen: function(selectedDates, dateStr, instance) {
        const cal = instance.calendarContainer;
        cal.style.top = "-250px";  // دائما فوق input
    }
});



const toggleBtn = document.getElementById("themeToggle");

// load saved theme
if (localStorage.getItem("theme") === "dark") {
    document.body.classList.add("dark-mode");
    toggleBtn.innerText = "☀️ Light";
}

toggleBtn.addEventListener("click", () => {
    document.body.classList.toggle("dark-mode");

    if (document.body.classList.contains("dark-mode")) {
        localStorage.setItem("theme", "dark");
        toggleBtn.innerText = "☀️ Light";
    } else {
        localStorage.setItem("theme", "light");
        toggleBtn.innerText = "🌙 Dark";
    }
});

const medSearch = $("#medecinSearch");
const medList = $("#medecinList");

medSearch.on("focus click keyup", function(){
    medList.show();
    let value = $(this).val().toLowerCase();

    $("#medecinList .patient-item").each(function(){
        const nom = $(this).data("nom") || "";
        const prenom = $(this).data("prenom") || "";

        $(this).toggle(
            nom.includes(value) ||
            prenom.includes(value)
        );
    });
});

$(document).on("click", "#medecinList .patient-item", function(){
    $("#id_medecin").val($(this).data("id"));
    medSearch.val($(this).text().trim());
    medList.hide();
});
$(document).click(function(e){
    if(!$(e.target).closest(".patient-search").length){
        $("#medecinList").hide();
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


</script>
</body>
</html>