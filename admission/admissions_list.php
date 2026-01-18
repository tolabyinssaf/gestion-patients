<?php
session_start();
include "../config/connexion.php";

// --- LOGIQUE PHP ORIGINALE (CONSERVÉE) ---
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

$kpiStmt = $pdo->query("SELECT * FROM vw_admission_kpi");
$kpi = $kpiStmt ? $kpiStmt->fetch(PDO::FETCH_ASSOC) : ['total' => 0, 'en_cours' => 0, 'termine' => 0];

$today = $pdo->query("SELECT COUNT(*) FROM admissions WHERE date_admission = CURDATE()")->fetchColumn();

if (!isset($_SESSION['id_user'])) { $_SESSION['id_user'] = 1; }
$user_id = $_SESSION['user_id'] ?? $_SESSION['id_user'];
$stmt_user = $pdo->prepare("SELECT nom, prenom FROM utilisateurs WHERE id_user = ?");
$stmt_user->execute([$user_id]);
$user_info = $stmt_user->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>MedCare | Liste des Admissions</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

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
            border-radius: 20px;
            border: 1px solid var(--border);
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
            padding: 25px;
            margin-bottom: 30px;
        }

        .kpi-card {
            background: var(--white); border-radius: 18px; padding: 20px; border: 1px solid var(--border);
            display: flex; align-items: center; gap: 15px; transition: 0.3s;
        }
        .kpi-card:hover { transform: translateY(-5px); }
        .kpi-icon { width: 50px; height: 50px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 24px; }

        /* --- NOUVEAU STYLE TABLEAU ET BARRE RECHERCHE --- */
        .dataTables_filter { float: left !important; margin-bottom: 25px; width: 100%; }
        .dataTables_filter input {
            width: 350px !important; height: 45px;
            padding: 10px 15px 10px 45px !important;
            border-radius: 12px !important;
            border: 1px solid var(--border) !important;
            background: var(--white) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%2364748b' class='bi bi-search' viewBox='0 0 16 16'%3E%3Cpath d='M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z'/%3E%3C/svg%3E") no-repeat 15px center !important;
            transition: all 0.3s; outline: none !important;
        }
        .dataTables_filter input:focus { width: 400px !important; border-color: var(--primary) !important; box-shadow: 0 0 0 4px rgba(15, 118, 110, 0.1) !important; }

        .table { border-collapse: separate !important; border-spacing: 0 10px !important; }
        .table thead th { border: none !important; color: #64748b !important; font-size: 11px !important; text-transform: uppercase; letter-spacing: 1px; padding: 12px 20px !important; }
        .table tbody tr { background: var(--white) !important; box-shadow: 0 2px 5px rgba(0,0,0,0.02); transition: 0.2s; border-radius: 12px; }
        .table tbody tr:hover { transform: scale(1.005); box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .table tbody td { padding: 18px 20px !important; border: none !important; vertical-align: middle !important; }
        .table tbody tr td:first-child { border-radius: 12px 0 0 12px; }
        .table tbody tr td:last-child { border-radius: 0 12px 12px 0; }

        .empty-state { text-align: center; padding: 40px !important; color: #94a3b8; }
        .empty-state i { font-size: 48px; display: block; margin-bottom: 10px; opacity: 0.5; }

        .badge-status { padding: 6px 14px; border-radius: 50px; font-weight: 600; font-size: 10px; text-transform: uppercase; }
        .theme-switch { cursor: pointer; padding: 8px 16px; border-radius: 50px; background: #f1f5f9; border: 1px solid var(--border); font-size: 13px; font-weight: 600; }
        .info-card { background: #f0fdfa; border-radius: 12px; padding: 12px; display: flex; align-items: center; gap: 10px; border: 1px solid #ccfbf1; font-size: 0.9rem; }
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
        <a href="dashboard_secretaire.php"><i class="fa-solid fa-chart-line"></i> Tableau de bord</a>
        <a href="patients_secr.php"><i class="fa-solid fa-user-group"></i> Patients</a>
        <a href="admissions_list.php" class="active"><i class="fa-solid fa-door-open"></i> Admissions</a>
        <a href="suivis.php"><i class="fa-solid fa-calendar-check"></i> Suivis du jour</a>
        <a href="caisse.php"><i class="fa-solid fa-wallet"></i> Caisse & Factures</a>
        <div style="height: 1px; background: rgba(255,255,255,0.1); margin: 20px 0;"></div>
        <a href="../connexio_utilisateur/deconnexion.php" style="color: #fda4af;"><i class="fa-solid fa-power-off"></i> Déconnexion</a>
    </aside>

    <main class="content">
        <div class="container-fluid">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 fw-bold mb-0">Gestion des Admissions</h1>
                <a href="admission_form.php" class="btn" style="background: #0f766e; color: white; display: inline-flex; align-items: center; gap: 8px; border-radius: 12px; padding: 10px 20px;">
                    <i class="bi bi-plus-lg"></i>Nouvelle Admission
                </a>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="kpi-card">
                        <div class="kpi-icon text-white" style="background: #bf7c4cff"><i class="bi bi-people"></i></div>
                        <div><div class="small text-muted">Total</div><div class="h4 fw-bold mb-0"><?= $kpi['total'] ?></div></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="kpi-card">
                        <div class="kpi-icon bg-warning text-white"><i class="bi bi-hourglass-split"></i></div>
                        <div><div class="small text-muted">En cours</div><div class="h4 fw-bold mb-0"><?= $kpi['en_cours'] ?></div></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="kpi-card">
                        <div class="kpi-icon bg-success text-white"><i class="bi bi-check2-circle"></i></div>
                        <div><div class="small text-muted">Terminées</div><div class="h4 fw-bold mb-0"><?= $kpi['termine'] ?></div></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="kpi-card">
                        <div class="kpi-icon bg-info text-white"><i class="bi bi-calendar-check"></i></div>
                        <div><div class="small text-muted">Aujourd'hui</div><div class="h4 fw-bold mb-0"><?= $today ?></div></div>
                    </div>
                </div>
            </div>

            <div class="glass-card">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="small fw-bold text-muted mb-2">Statut</label>
                        <select id="filterStatus" class="form-select">
                            <option value="">Tous les statuts</option>
                            <?php foreach($statuses as $s): ?><option value="<?= $s ?>"><?= $s ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="small fw-bold text-muted mb-2">Service</label>
                        <select id="filterService" class="form-select">
                            <option value="">Tous les services</option>
                            <?php foreach($services as $s): ?><option value="<?= $s ?>"><?= $s ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="small fw-bold text-muted mb-2">Filtrer par date</label>
                        <input type="text" id="filterDate" class="form-control" placeholder="Sélectionner une date">
                    </div>
                </div>
            </div>

            <div class="table-responsive" style="overflow: visible;">
                <table id="admissionsTable" class="table">
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Date Admission</th>
                            <th>Service</th>
                            <th>Médecin</th>
                            <th>Chambre</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($admissions as $adm): 
                            $badgeClass = ($adm['statut'] == 'En cours') ? 'bg-warning' : 'bg-success';
                        ?>
                        <tr data-bs-toggle="modal" data-bs-target="#modal<?= $adm['id_admission'] ?>" style="cursor:pointer">
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="me-3 d-flex align-items-center justify-content-center rounded-circle text-primary fw-bold" style="width:38px; height:38px; background:var(--primary-light); font-size:14px;">
                                        <?= strtoupper(substr($adm['nom'],0,1)) ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($adm['nom'].' '.$adm['prenom']) ?></div>
                                        <div class="small text-muted" style="font-size:11px;"><?= $adm['sexe'] ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="text-dark fw-medium"><?= date('d/m/Y', strtotime($adm['date_admission'])) ?></span></td>
                            <td><span class="badge bg-light text-dark border px-3 py-2"><?= htmlspecialchars($adm['service']) ?></span></td>
                            <td><div class="small fw-bold">Dr. <?= htmlspecialchars($adm['medecin_nom'] ?? '-') ?></div></td>
                            <td><div class="badge bg-white text-muted border fw-normal"><i class="bi bi-door-closed me-1"></i><?= htmlspecialchars($adm['numero_chambre'] ?? '-') ?></div></td>
                            <td><span class="badge badge-status <?= $badgeClass ?>"><?= $adm['statut'] ?></span></td>
                            <td class="text-end" onclick="event.stopPropagation()">
                                <div class="btn-group shadow-sm" style="border-radius: 8px; overflow: hidden;">
                                    <a href="modifier_admission.php?id=<?= $adm['id_admission'] ?>" class="btn btn-sm btn-white border-end"><i class="bi bi-pencil text-primary"></i></a>
                                    <button onclick="deleteAdmission(<?= $adm['id_admission'] ?>)" class="btn btn-sm btn-white text-danger"><i class="bi bi-trash"></i></button>
                                </div>
                            </td>
                        </tr>

                        <div class="modal fade" id="modal<?= $adm['id_admission'] ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header border-0 pb-0">
                                        <h5 class="fw-bold">Dossier d'Admission</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="text-center mb-4">
                                            <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width:60px; height:60px;">
                                                <i class="bi bi-person-vcard fs-3"></i>
                                            </div>
                                            <h5 class="mb-0"><?= htmlspecialchars($adm['nom'].' '.$adm['prenom']) ?></h5>
                                            <span class="badge bg-light text-primary border mt-1">ID: #ADM-<?= $adm['id_admission'] ?></span>
                                        </div>
                                        <div class="row g-2">
                                            <div class="col-6"><div class="info-card"><i class="bi bi-telephone"></i> <?= $adm['telephone'] ?></div></div>
                                            <div class="col-6"><div class="info-card"><i class="bi bi-calendar"></i> <?= $adm['date_naissance'] ?></div></div>
                                            <div class="col-12"><div class="info-card"><i class="bi bi-geo-alt"></i> <?= $adm['adresse'] ?></div></div>
                                            <div class="col-12"><hr class="my-2"></div>
                                            <div class="col-6"><div class="info-card bg-white"><i class="bi bi-hospital"></i> <?= $adm['service'] ?></div></div>
                                            <div class="col-6"><div class="info-card bg-white"><i class="bi bi-person-badge"></i> Dr. <?= $adm['medecin_nom'] ?? '-' ?></div></div>
                                            <div class="col-12"><div class="info-card bg-light border-0"><strong>Motif:</strong> <?= $adm['motif'] ?></div></div>
                                        </div>
                                    </div>
                                    <div class="modal-footer border-0">
                                        <button class="btn btn-primary w-100 rounded-pill" data-bs-dismiss="modal">Fermer</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function () {
    flatpickr("#filterDate", { dateFormat: "Y-m-d" });

    var table = $('#admissionsTable').DataTable({
        dom: '<"d-flex justify-content-between align-items-center mb-0"fB>rtip',
        buttons: [
            { extend: 'excel', className: 'btn btn-sm btn-outline-success rounded-pill me-2', text: '<i class="bi bi-file-excel"></i> Excel' },
            { extend: 'pdf', className: 'btn btn-sm btn-outline-danger rounded-pill', text: '<i class="bi bi-file-pdf"></i> PDF' }
        ],
        language: { 
            search: "", 
            searchPlaceholder: "Rechercher un patient, médecin...",
            emptyTable: "<div class='empty-state'><i class='bi bi-folder2-open'></i>Aucune admission enregistrée pour le moment.</div>",
            zeroRecords: "<div class='empty-state'><i class='bi bi-search'></i>Aucun résultat ne correspond à votre recherche.</div>"
        },
        pageLength: 10
    });

    $('#filterStatus').on('change', function(){ table.column(5).search(this.value).draw(); });
    $('#filterService').on('change', function(){ table.column(2).search(this.value).draw(); });
    $('#filterDate').on('change', function(){ table.column(1).search(this.value).draw(); });

    const toggleBtn = document.getElementById("themeToggle");
    if (localStorage.getItem("theme") === "dark") document.body.classList.add("dark-mode");
    
    toggleBtn.addEventListener("click", () => {
        document.body.classList.toggle("dark-mode");
        const isDark = document.body.classList.contains("dark-mode");
        localStorage.setItem("theme", isDark ? "dark" : "light");
        toggleBtn.innerText = isDark ? "☀️ Mode Clair" : "🌙 Mode Sombre";
    });
});

function deleteAdmission(id){
    Swal.fire({
        title: 'Supprimer ?',
        text: "Cette admission sera définitivement retirée.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#0f766e',
        confirmButtonText: 'Oui, supprimer'
    }).then((result) => {
        if(result.isConfirmed) window.location.href = 'admission_delete.php?id='+id;
    })
}
</script>
</body>
</html>