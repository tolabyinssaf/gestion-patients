<?php
include "../config/connexion.php";

// Statuts et services
$statuses = ['En cours','Terminé'];
$services = ['Cardiologie', 'Pédiatrie', 'Chirurgie', 'Général'];


$admissions = $pdo->query("
    SELECT a.*, 
           p.nom, p.prenom, p.date_naissance, p.sexe, p.telephone, p.adresse, p.email,
           u.nom AS medecin_nom,
           c.numero_chambre
    FROM admissions a
    JOIN patients p ON a.id_patient = p.id_patient
    LEFT JOIN utilisateurs u ON a.id_medecin = u.id_user AND u.role='medecin'
    LEFT JOIN chambres c ON a.id_chambre = c.id_chambre
    ORDER BY a.date_admission DESC
")->fetchAll(PDO::FETCH_ASSOC);





// KPI global
$kpiStmt = $pdo->query("SELECT * FROM vw_admission_kpi");
$kpi = $kpiStmt ? $kpiStmt->fetch(PDO::FETCH_ASSOC) : null;

if(!$kpi){
    $kpi = [
        'total' => 0,
        'en_cours' => 0,
        'termine' => 0
    ];
}


// Admissions اليوم
$today = $pdo->query("
    SELECT COUNT(*) 
    FROM admissions 
    WHERE date_admission = CURDATE()
")->fetchColumn();

?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Liste des Admissions</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<style>
/* ===== Couleurs Medicale Moderne ===== */
:root {
    --primary-color: #01A28C;
    --secondary-color: #323940ff;
    --accent-color: #01A28C;
    --success-color: #28a745;
    --danger-color: #463032ff;
    --bg-color: #e0f7f5;
    --hover-color: #c1f0eb;
}

body {
    background-color: var(--bg-color);
    font-family: 'Segoe UI', sans-serif;
    padding: 20px;
    transition: background 0.5s ease;
}

/* ===== Header ===== */
h2 i {
    animation: bounce 1.5s infinite;
}

@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-5px); }
}

/* ===== Table ===== */
.table thead {
    background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
    color: #fff;
    font-weight: 600;
}

.table-hover tbody tr {
    transition: all 0.3s ease;
}

#admissionsTable th {
    text-align: center;
    vertical-align: middle;
}
#admissionsTable tbody tr { cursor: pointer; }
/* Badges */
.badge-status {
    font-size: 0.85rem;
    padding: 0.45em 0.75em;
    border-radius: 12px;
    transition: all 0.3s;
}
.badge-status:hover {
    transform: scale(1.1);
}

/* Buttons */
.btn {
    border-radius: 12px;
    font-size: 0.9rem;
    transition: all 0.3s ease;
}
.btn:hover {
    transform: scale(1.1);
    box-shadow: 0 4px 15px rgba(0,0,0,0.25);
}

.btn-primary { background-color: var(--primary-color); border:none; color:#fff; }
.btn-success { background-color: var(--success-color); border:none; color:#fff; }
.btn-warning { background-color: var(--accent-color); border:none; color:#fff; }
.btn-danger { background-color: var(--danger-color); border:none; color:#fff; }
.btn-info { background-color: #154147ff; border:none; color:#fff; }

/* Filters Card */
.filter-card {
    background-color: #fff;
    border-radius: 15px;
    padding: 25px;
    margin-bottom: 25px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.1);
}
.filter-card select,
.filter-card input {
    border-radius: 12px;
    border: 2px solid var(--primary-color);
    padding: 10px 12px;
    font-size: 0.95rem;
    color: var(--secondary-color);
    background-color: #e6f7f6;
    transition: all 0.3s ease;
    text-align: center;
}
.filter-card select:focus,
.filter-card input:focus {
    outline: none;
    border-color: #028090;
    box-shadow: 0 0 10px rgba(1,162,140,0.6);
    background-color: #ffffff;
}
.filter-card select:hover,
.filter-card input:hover { background-color: #d9f5f3; cursor:pointer; }

/* ===== Modals Professionnelles ===== */
/* ================================ */
/* ===== MODAL MEDICAL PRO ======= */
/* ================================ */

/* تعطيل أي حركة داخل المودال */
.modal,
.modal * {
    animation: none !important;
    transform: none !important;
    transition: none !important;
}

/* خلفية المودال */
.modal-backdrop.show {
    opacity: 0.55;
    background-color: #0f2f2d;
}

/* Container */
.modal-dialog {
    max-width: 520px;
}

/* المحتوى */
.modal-content {
    border-radius: 22px;
    border: 1.5px solid #01A28C;
    background-color: #ffffff;
    box-shadow:
        0 10px 25px rgba(0,0,0,0.18),
        0 0 0 4px rgba(1,162,140,0.08);
}

/* ===== Header ===== */
.modal-header {
    background: linear-gradient(90deg, #01A28C, #028090);
    color: #ffffff;
    padding: 16px 22px;
    border-bottom: none;
}

.modal-title {
    font-size: 1.05rem;
    font-weight: 600;
    letter-spacing: 0.3px;
}

/* زر الإغلاق */
.modal-header .btn-close {
    filter: invert(1);
    opacity: 0.9;
}

/* ===== Body ===== */
.modal-body {
    padding: 20px 22px;
    font-size: 0.95rem;
    color: #2f3e46;
}

/* معلومات المريض */
.modal-body p {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
    padding: 8px 10px;
    border-radius: 10px;
    background-color: #f4fbfa;
    border: 1px solid #e0f2ef;
}

.modal-body i {
    color: #01A28C;
    font-size: 1.05rem;
    margin-right: 10px;
    min-width: 20px;
}

/* ===== Footer ===== */
.modal-footer {
    padding: 14px 20px;
    background-color: #f2fdfc;
    border-top: 1px solid #e2f3f1;
}

.modal-footer .btn {
    border-radius: 10px;
    padding: 6px 18px;
    font-size: 0.9rem;
}

/* منع أي hover تأثير من الصفحة */
.modal-open tr:hover,
.modal-open .card:hover {
    transform: none !important;
}

/* Responsive */
@media (max-width: 576px) {
    .modal-dialog {
        margin: 10px;
    }
}

/* Animation bounce */
@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-4px); }
}

/* Scrollable body */
.modal-dialog-scrollable .modal-body {
    max-height: 400px;
    overflow-y: auto;
}

/* Custom scrollbar */
.modal-body::-webkit-scrollbar {
    width: 8px;
}
.modal-body::-webkit-scrollbar-thumb {
    background-color: #01A28C;
    border-radius: 8px;
}
.modal-body::-webkit-scrollbar-track {
    background: #e0f7f5;
}

/* Recent highlight (optional) */
.recent {
    animation: pulse 1.5s infinite;
    background-color: #d1f7d6 !important;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.03); }
    100% { transform: scale(1); }
}


/* Flatpickr custom */
.flatpickr-calendar {
    border-radius: 12px;
    border: 2px solid var(--primary-color);
}
.flatpickr-day.today {
    background: var(--accent-color);
    color: #fff;
}
.flatpickr-day.selected {
    background: var(--primary-color);
    color: #fff;
    border-radius: 8px;
}

/* Animation entrées */
tr {
    transition: transform 0.3s, background 0.3s;
}
.card {
    border-radius: 16px;
    transition: all 0.3s ease;
    background: #ffffff;
}

.card:hover {
    transform: translateY(-6px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
}

.card i {
    color: var(--primary-color);
}

/* Pour les boutons Actions (Modifier / Supprimer / Traitement) */
td .btn i {
    color: #fff !important;
}

/* Pour les icônes dans la modal header */
.modal-header .modal-title i {
    color: #fff !important;
}

.dataTables_filter {
    float: right; /* kanb9aw fi right */
    text-align: right;
    margin-bottom: 15px;
}

.dataTables_filter label {
    font-weight: 500;
    color: var(--secondary-color);
    display: flex;
    align-items: center;
    gap: 8px;
}

.dataTables_filter input {
    border-radius: 12px;
    border: 2px solid var(--primary-color);
    padding: 6px 12px;
    font-size: 0.95rem;
    background-color: #e6f7f6;
    color: var(--secondary-color);
    transition: all 0.3s ease;
}

.dataTables_filter input:focus {
    outline: none;
    border-color: var(--accent-color);
    box-shadow: 0 0 8px rgba(1,162,140,0.5);
    background-color: #ffffff;
}
/* ================================ */
/* ===== MEDICAL BUTTON THEME ===== */
/* ================================ */

/* Base button */
.btn {
    border-radius: 10px;
    font-size: 0.85rem;
    font-weight: 500;
    padding: 6px 14px;
    transition: background-color 0.2s ease, box-shadow 0.2s ease;
    box-shadow: none;
}

/* Hover général (خفيف) */
.btn:hover {
    box-shadow: 0 4px 12px rgba(1,162,140,0.25);
}

/* ===== Ajouter / Primary ===== */
.btn-primary {
    background: linear-gradient(90deg, #01A28C, #028090);
    border: none;
    color: #fff;
}
.btn-primary:hover {
    background: linear-gradient(90deg, #028090, #01A28C);
}

/* ===== Modifier ===== */
.btn-warning {
    background-color: #e6f7f6;
    border: 1.5px solid #01A28C;
    color: #01A28C;
}
.btn-warning:hover {
    background-color: #01A28C;
    color: #fff;
}

/* ===== Supprimer ===== */
.btn-danger {
    background-color: #fdecea;
    border: 1.5px solid #d9534f;
    color: #d9534f;
}
.btn-danger:hover {
    background-color: #d9534f;
    color: #fff;
}

/* ===== Traitement / Info ===== */
.btn-info {
    background-color: #eaf6fb;
    border: 1.5px solid #028090;
    color: #028090;
}
.btn-info:hover {
    background-color: #028090;
    color: #fff;
}

/* ===== Bouton Fermer (Modal) ===== */
.modal-footer .btn-info {
    background-color: #01A28C;
    border: none;
    color: #fff;
}
.modal-footer .btn-info:hover {
    background-color: #028090;
}

/* ===== Icônes toujours visibles ===== */
.btn i {
    color: inherit !important;
}
/* =============================== */
/* 🖤 DARK MEDICAL MODE — PREMIUM */
/* =============================== */

body.dark-mode {
    background: radial-gradient(circle at top, #020617, #000);
    color: #4d5158ff;
}

/* ===== CARDS ===== */
body.dark-mode .card {
    background: linear-gradient(180deg, #020a1f, #020617);
    border: 1px solid #1e293b;
    border-radius: 18px;
    box-shadow:
        inset 0 0 0 1px rgba(20,184,166,0.05),
        0 20px 40px rgba(0,0,0,0.85);
}

/* ===== HEADERS ===== */
body.dark-mode h1,
body.dark-mode h2,
body.dark-mode h3 {
   color: #c1f0eb;
}

body.dark-mode h6 {
   color: #c1f0eb;
}
/* ===== TABLE ===== */
body.dark-mode table {
    border-collapse: separate;
    border-spacing: 0 6px;
    color: #495876ff;
}

body.dark-mode .table thead th {
    background-color: #020617;
    color: #14b8a6;
    border-bottom: 1px solid #1e293b;
    font-weight: 600;
}

body.dark-mode .table tbody tr {
    background-color: #020a1f;
    border: 1px solid #1e293b;
}

body.dark-mode .table tbody td {
    border-top: none;
    color: #5d6a85ff;
}

/* ===== MODAL ===== */
body.dark-mode .modal-content {
    background: linear-gradient(180deg, #020a1f, #020617);
    border-radius: 22px;
    border: 1px solid rgba(20,184,166,0.4);
    box-shadow:
        0 30px 60px rgba(0,0,0,0.9),
        inset 0 0 20px rgba(20,184,166,0.08);
}

body.dark-mode .modal-header {
    background: linear-gradient(90deg, #14b8a6, #020617);
    color: #fff;
    border-bottom: 1px solid #1e293b;
}

body.dark-mode .modal-footer {
    background-color: #020617;
}

/* ===== INPUTS ===== */
body.dark-mode .form-control,
body.dark-mode .form-select {
    background-color: #54908bff;
    border: 1px solid #1e293b;
    color: #dee0e4ff;
    border-radius: 12px;
}
body.dark-mode .form-control:focus {
    border-color: #27b5a5ff;
    box-shadow: 0 0 0 1px rgba(20,184,166,0.4);
}

body.dark-mode .form-control::placeholder {
    color: #fff;
}

/* ===== BUTTONS ===== */
body.dark-mode .btn-primary {
    background: linear-gradient(135deg, #14b8a6, #0f766e);
    border: none;
    box-shadow: 0 10px 25px rgba(20,184,166,0.45);
}

body.dark-mode .btn-outline-secondary {
    border-color: #14b8a6;
    color: #14b8a6;
}

/* ===== BADGES ===== */
body.dark-mode .badge-success {
    background-color: #064e3b;
    color: #5eead4;
}

body.dark-mode .badge-warning {
    background-color: #422006;
    color: #facc15;
}

body.dark-mode .badge-danger {
    background-color: #450a0a;
    color: #f87171;
}
body.dark-mode .filter-card{
    background-color: rgba(20,184,166,0.4);
    color: black;
    font-size: 20px;
}

.info-card {
    display: flex;
    align-items: center;
    padding: 10px 14px;
    border-radius: 12px;
    background: #f4fbfa;
    border: 1px solid #d0ece9;
    font-size: 0.95rem;
    gap: 10px;
    transition: transform 0.2s, background 0.2s;
}
.info-card i {
    color: var(--primary-color);
    font-size: 1.2rem;
}
.info-card:hover {
    transform: translateY(-2px);
    background: #e0f7f5;
}



</style>
</head>
<body>

<div class="container">

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 style="color:#01A28C"><i class="bi bi-hospital-fill me-2"></i>Liste des Admissions</h2>
      <button id="themeToggle" class="btn btn-outline-secondary btn-sm">
    🌙 'Dark'
</button>
</div>
<!-- KPI CARDS -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card shadow-sm text-center p-3">
            
            <i class="bi bi-clipboard-data fs-2 text-primary"></i>
            <h6 class="mt-2" >Total Admissions</h6>
            <h3 class="fw-bold"><?= $kpi['total'] ?></h3>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card shadow-sm text-center p-3">
            <i class="bi bi-heart-pulse fs-2 text-warning"></i>
            <h6 class="mt-2">En cours</h6>
            <h3 class="fw-bold"><?= $kpi['en_cours'] ?></h3>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card shadow-sm text-center p-3">
            <i class="bi bi-check-circle fs-2 text-success"></i>
            <h6 class="mt-2" >Terminées</h6>
            <h3 class="fw-bold"><?= $kpi['termine'] ?></h3>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card shadow-sm text-center p-3">
            <i class="bi bi-calendar-day fs-2 text-info"></i>
            <h6 class="mt-2" >Aujourd’hui</h6>
            <h3 class="fw-bold"><?= $today ?></h3>
        </div>
    </div>

</div>


<!-- Filters -->
<div class="filter-card row g-3 mb-4 " style="padding-left: 25%;" >
    <div class="col-md-3">
        <label><i class="bi bi-clock-history me-1"></i>Status :</label>
        <select id="filterStatus" class="form-select">
            <option value="">Tous</option>
            <?php foreach($statuses as $s): ?>
                <option value="<?= $s ?>"><?= $s ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3">
        <label><i class="bi bi-hospital me-1"></i>Service :</label>
        <select id="filterService" class="form-select">
            <option value="">Tous</option>
            <?php foreach($services as $s): ?>
                <option value="<?= $s ?>"><?= $s ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3">
        <label><i class="bi bi-calendar-event me-1"></i>Date :</label>
        <input type="text" id="filterDate" class="form-control" placeholder="Sélectionner une date">
    </div>
</div>

<!-- Admissions Table -->

<div class="card p-3">
        <div class="d-flex justify-content-start mb-3">
        <a href="admission_form.php" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle me-1"></i>Admission
        </a>
    </div>
<table id="admissionsTable" class="table table-striped table-hover table-bordered">
    <thead>
        <tr>
            <th>Patient</th>
            <th>Date</th>
            <th>Service</th>
            <th>Médecin</th>
            <th>Chambre</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($admissions as $adm):
            $badge = match($adm['statut']){
                'En cours' => '<span class="badge badge-status bg-warning text-white"><i class="bi bi-heart-pulse me-1"></i> En cours</span>',
                'Terminé' => '<span class="badge badge-status bg-success text-white"><i class="bi bi-check2-circle me-1"></i> Terminé</span>',
                default => '<span class="badge badge-status bg-secondary text-white"><i class="bi bi-journal-medical me-1"></i> Autre</span>'
            };
            $recent_class = (strtotime($adm['date_admission']) >= strtotime('-7 days')) ? 'recent' : '';
        ?>
        <tr class="<?= $recent_class ?>" data-bs-toggle="modal" data-bs-target="#modal<?= $adm['id_admission'] ?>">
            <td><?= htmlspecialchars($adm['nom'].' '.$adm['prenom']) ?></td>
            <td><?= $adm['date_admission'] ?></td>
            <td><?= htmlspecialchars($adm['service']) ?></td>
             <td><?= htmlspecialchars($adm['medecin_nom'] ?? '-') ?></td>
             <td><?= htmlspecialchars($adm['numero_chambre'] ?? '-') ?></td>
            <td><?= $badge ?></td>
            <td class="text-center d-flex justify-content-center gap-1">
                <a href="modifier_admission.php?id=<?= $adm['id_admission'] ?>" class="btn btn-warning btn-sm"><i class="bi bi-pencil-square me-1"></i> Modifier</a>
                <a href="#" onclick="deleteAdmission(<?= $adm['id_admission'] ?>)" class="btn btn-danger btn-sm"><i class="bi bi-trash3 me-1"></i> Supprimer</a>
            </td>
        </tr>

        <!-- Modal patient -->
<!-- Modal patient -->
<div class="modal fade" id="modal<?= $adm['id_admission'] ?>" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content" style="background-color:#f2f2f2; border:2px solid #14b8a6;">
      <div class="modal-header" style="background-color:#14b8a6; color:#fff;">
        <h5 class="modal-title"><i class="bi bi-person-lines-fill me-1"></i>Détails Admission</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
<div class="modal-body">
  <div class="row g-3">
    <!-- Patient -->
    <div class="col-md-6">
      <div class="info-card"><i class="bi bi-person"></i> <strong>Nom:</strong> <?= htmlspecialchars($adm['nom']) ?></div>
    </div>
    <div class="col-md-6">
      <div class="info-card"><i class="bi bi-person-badge"></i> <strong>Prénom:</strong> <?= htmlspecialchars($adm['prenom']) ?></div>
    </div>
    <div class="col-md-6">
      <div class="info-card"><i class="bi bi-gender-ambiguous"></i> <strong>Sexe:</strong> <?= htmlspecialchars($adm['sexe']) ?></div>
    </div>
    <div class="col-md-6">
      <div class="info-card"><i class="bi bi-calendar-event"></i> <strong>Date Naissance:</strong> <?= htmlspecialchars($adm['date_naissance']) ?></div>
    </div>
    <div class="col-md-6">
      <div class="info-card"><i class="bi bi-telephone"></i> <strong>Téléphone:</strong> <?= htmlspecialchars($adm['telephone']) ?></div>
    </div>
    <div class="col-md-6">
      <div class="info-card"><i class="bi bi-geo-alt"></i> <strong>Adresse:</strong> <?= htmlspecialchars($adm['adresse']) ?></div>
    </div>
    <div class="col-md-12">
      <div class="info-card"><i class="bi bi-envelope"></i> <strong>Email:</strong> <?= htmlspecialchars($adm['email']) ?></div>
    </div>

    <div class="col-md-12"><hr></div>

    <!-- Admission -->
    <div class="col-md-6">
      <div class="info-card"><i class="bi bi-calendar-plus"></i> <strong>Date Admission:</strong> <?= htmlspecialchars($adm['date_admission']) ?></div>
    </div>
    <div class="col-md-6">
      <div class="info-card"><i class="bi bi-hospital"></i> <strong>Service:</strong> <?= htmlspecialchars($adm['service']) ?></div>
    </div>
    <div class="col-md-6">
      <div class="info-card"><i class="bi bi-person-workspace"></i> <strong>Médecin:</strong> <?= htmlspecialchars($adm['medecin_nom'] ?? '-') ?></div>
    </div>
    <div class="col-md-6">
      <div class="info-card"><i class="bi bi-journal-text"></i> <strong>Type Admission:</strong> <?= htmlspecialchars($adm['type_admission'] ?? '-') ?></div>
    </div>
    <div class="col-md-12">
      <div class="info-card"><i class="bi bi-card-text"></i> <strong>Motif:</strong> <?= htmlspecialchars($adm['motif']) ?></div>
    </div>
    <div class="col-md-12">
      <div class="info-card"><i class="bi bi-patch-check"></i> <strong>Status:</strong> <?= htmlspecialchars($adm['statut']) ?></div>
    </div>
  </div>
</div>

      <div class="modal-footer">
        <button type="button" class="btn btn-info" data-bs-dismiss="modal"><i class="bi bi-x-circle me-1"></i> Fermer</button>
      </div>
    </div>
  </div>
</div>


        <?php endforeach; ?>
    </tbody>
</table>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function () {
    // Flatpickr
    flatpickr("#filterDate", {
        dateFormat: "Y-m-d",
        allowInput: true
    });

    // DataTable
      var table = $('#admissionsTable').DataTable({
        dom: 'Bfrtip',
        buttons: [
            { extend: 'excelHtml5', className: 'btn btn-success me-1', text: '<i class="bi bi-file-earmark-spreadsheet"></i> Excel' },
            { extend: 'pdfHtml5', className: 'btn btn-danger me-1', text: '<i class="bi bi-file-earmark-pdf"></i> PDF' },
            { extend: 'print', className: 'btn btn-info', text: '<i class="bi bi-printer"></i> Imprimer' }
        
        ],
        paging: false,   // Supprimer pagination
        info: false,     // Supprimer info "Showing X of Y"
        searching: true  // Maintenir recherche globale
    });

    // Filtres
    $('#filterStatus').on('change', function(){ table.column(4).search(this.value).draw(); });
    $('#filterService').on('change', function(){ table.column(2).search(this.value).draw(); });
    $('#filterDate').on('change', function(){ table.column(1).search(this.value).draw(); });
});

function deleteAdmission(id){
    Swal.fire({
        title: 'Êtes-vous sûr ?',
        text: "Cette action est irréversible !",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#01A28C',
        cancelButtonColor: '#1e2a38',
        confirmButtonText: 'Oui, supprimer !'
    }).then((result) => {
        if(result.isConfirmed){
            window.location.href = 'admission_delete.php?id='+id;
        }
    })
}


const toggleBtn = document.getElementById("themeToggle");

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
</script>

</body>
</html>