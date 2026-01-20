<?php
session_start();
include "../config/connexion.php";

// 1. Requête SQL avec GROUP BY et toutes les colonnes nécessaires (type_chambre incluse)
$admissions = $pdo->query("
    SELECT a.*, 
           p.nom, p.prenom, p.date_naissance, p.sexe, p.telephone, p.adresse, p.email,
           u.nom AS medecin_nom,
           c.numero_chambre
    FROM admissions a
    JOIN patients p ON a.id_patient = p.id_patient
    LEFT JOIN utilisateurs u ON a.id_medecin = u.id_user AND u.role='medecin'
    LEFT JOIN chambres c ON a.id_chambre = c.id_chambre
    GROUP BY a.id_admission 
    ORDER BY a.date_admission DESC
")->fetchAll(PDO::FETCH_ASSOC);

// 2. Récupération des KPI
$kpiStmt = $pdo->query("SELECT * FROM vw_admission_kpi");
$kpi = $kpiStmt ? $kpiStmt->fetch(PDO::FETCH_ASSOC) : ['total' => 0, 'en_cours' => 0, 'termine' => 0];
$today = $pdo->query("SELECT COUNT(*) FROM admissions WHERE date_admission = CURDATE()")->fetchColumn();

// 3. Infos Utilisateur connecté
if (!isset($_SESSION['id_user'])) { $_SESSION['id_user'] = 1; }
$user_id = $_SESSION['user_id'] ?? $_SESSION['id_user'];
$stmt_user = $pdo->prepare("SELECT nom, prenom FROM utilisateurs WHERE id_user = ?");
$stmt_user->execute([$user_id]);
$user_info = $stmt_user->fetch(PDO::FETCH_ASSOC);

$statuses = ['En cours','Terminé'];
$services = ['Cardiologie', 'Pédiatrie', 'Chirurgie', 'Général'];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>MedCare | Gestion des Admissions</title>
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
        .user-pill { background: var(--primary-light); padding: 8px 18px; border-radius: 12px; display: flex; align-items: center; gap: 10px; font-size: 14px; font-weight: 600; color: var(--primary); }

        .sidebar { 
            width: var(--sidebar-width); background: var(--sidebar-bg); padding: 24px 16px; 
            position: fixed; top: var(--header-height); left: 0; bottom: 0; z-index: 999;
        }
        .sidebar a { display: flex; align-items: center; gap: 12px; color: #94a3b8; text-decoration: none; padding: 12px 16px; border-radius: 10px; margin-bottom: 5px; transition: 0.2s; }
        .sidebar a:hover, .sidebar a.active { background: var(--primary); color: #fff; }

        .content { margin-left: var(--sidebar-width); margin-top: var(--header-height); padding: 40px; }

        /* KPI Cards */
        .kpi-card { background: var(--white); border-radius: 18px; padding: 20px; border: 1px solid var(--border); display: flex; align-items: center; gap: 15px; }
        .kpi-icon { width: 50px; height: 50px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 24px; }

        .glass-card { background: var(--white); border-radius: 20px; border: 1px solid var(--border); padding: 25px; margin-bottom: 30px; }

        /* Table */
        .table thead th { border: none; color: #64748b; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; padding: 15px 20px; }
        .table tbody tr { background: var(--white); transition: 0.2s; cursor: pointer; border-bottom: 1px solid var(--border); }
        .table tbody tr:hover { background: #f1f5f9; }
        .table tbody td { padding: 18px 20px; vertical-align: middle; }

        /* MODAL DEVELOPPÉ */
        .modal-header { background: linear-gradient(135deg, var(--primary), #14b8a6); color: white; border-radius: 20px 20px 0 0; padding: 25px; }
        .modal-content { border-radius: 20px; border: none; }
        .info-label { font-size: 11px; color: #94a3b8; font-weight: 700; text-transform: uppercase; margin-bottom: 3px; display: block; }
        .info-value { font-weight: 600; color: #1e293b; font-size: 15px; display: block; }
        .section-title { font-size: 14px; font-weight: 700; color: var(--primary); border-bottom: 2px solid #f1f5f9; padding-bottom: 8px; margin-bottom: 15px; display: flex; align-items: center; gap: 8px; }
        .detail-box { background: #f8fafc; border-radius: 12px; padding: 15px; border: 1px solid #eff6f5; height: 100%; }
        .motif-area { background: #fffbeb; border-left: 4px solid #ca9945ff; padding: 15px; border-radius: 8px; font-style: italic; color: #92400e; }
        
        .theme-switch { cursor: pointer; padding: 8px 16px; border-radius: 50px; background: var(--primary-light); border: 1px solid var(--primary); color: var(--primary); font-size: 13px; font-weight: 600; }
    </style>
</head>
<body>

<header>
    <img src="../images/logo_app2.png" alt="Logo" style="height:45px;">
    <div class="d-flex align-items-center gap-3">
        <button id="themeToggle" class="theme-switch">🌙 Mode Sombre</button>
        <div class="user-pill">
            <i class="fa-solid fa-user-tie"></i>
            <span>Séc. <?= htmlspecialchars($user_info['prenom'] ?? '') ?></span>
        </div>
    </div>
</header>

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
        
        <div class="d-flex justify-content-between align-items-center mb-4">
          
            <a href="admission_form.php" class="btn" style="background: var(--primary); color: white; border-radius: 12px; padding: 10px 20px;">
                <i class="bi bi-plus-circle me-2"></i>Nouvelle Admission
            </a>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="kpi-card shadow-sm">
                    <div class="kpi-icon text-white" style="background: #0d9488"><i class="bi bi-clipboard-data"></i></div>
                    <div><div class="small text-muted">Total</div><div class="h4 fw-bold mb-0"><?= $kpi['total'] ?></div></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="kpi-card shadow-sm">
                    <div class="kpi-icon bg-warning text-white"><i class="bi bi-heart-pulse"></i></div>
                    <div><div class="small text-muted">En cours</div><div class="h4 fw-bold mb-0"><?= $kpi['en_cours'] ?></div></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="kpi-card shadow-sm">
                    <div class="kpi-icon bg-success text-white"><i class="bi bi-check-circle"></i></div>
                    <div><div class="small text-muted">Terminées</div><div class="h4 fw-bold mb-0"><?= $kpi['termine'] ?></div></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="kpi-card shadow-sm">
                    <div class="kpi-icon bg-info text-white"><i class="bi bi-calendar-day"></i></div>
                    <div><div class="small text-muted">Aujourd'hui</div><div class="h4 fw-bold mb-0"><?= $today ?></div></div>
                </div>
            </div>
        </div>

        <div class="glass-card shadow-sm">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="small fw-bold text-muted mb-2">Status</label>
                    <select id="filterStatus" class="form-select border-0 bg-light">
                        <option value="">Tous les statuts</option>
                        <?php foreach($statuses as $s): ?><option value="<?= $s ?>"><?= $s ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="small fw-bold text-muted mb-2">Service</label>
                    <select id="filterService" class="form-select border-0 bg-light">
                        <option value="">Tous les services</option>
                        <?php foreach($services as $s): ?><option value="<?= $s ?>"><?= $s ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="small fw-bold text-muted mb-2">Date</label>
                    <input type="text" id="filterDate" class="form-control border-0 bg-light" placeholder="Filtrer par date">
                </div>
            </div>
        </div>

        <div class="table-responsive">
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
                        $badgeClass = ($adm['statut'] == 'En cours') ? 'bg-warning text-dark' : 'bg-success text-white';
                    ?>
                    <tr data-bs-toggle="modal" data-bs-target="#modal<?= $adm['id_admission'] ?>">
                        <td>
                            <div class="fw-bold"><?= htmlspecialchars($adm['nom'].' '.$adm['prenom']) ?></div>
                            <small class="text-muted"><?= $adm['sexe'] ?> | <?= $adm['telephone'] ?></small>
                        </td>
                        <td><?= date('d/m/Y', strtotime($adm['date_admission'])) ?></td>
                        <td><span class="badge bg-light text-dark border"><?= $adm['service'] ?></span></td>
                        <td>Dr. <?= htmlspecialchars($adm['medecin_nom'] ?? '-') ?></td>
                        <td>N° <?= $adm['numero_chambre'] ?? '-' ?></td>
                        <td><span class="badge rounded-pill <?= $badgeClass ?>"><?= $adm['statut'] ?></span></td>
                        <td class="text-end" >
                            <a href="modifier_admission.php?id=<?= $adm['id_admission'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <button onclick="deleteAdmission(<?= $adm['id_admission'] ?>)" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php foreach($admissions as $adm): ?>
<div class="modal fade" id="modal<?= $adm['id_admission'] ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow-lg">
            <div class="modal-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-white p-3 rounded-circle text-primary shadow-sm"><i class="bi bi-file-earmark-medical fs-4"></i></div>
                    <div>
                        <h5 class="mb-0 fw-bold">Dossier Admission #<?= $adm['id_admission'] ?></h5>
                        <small class="opacity-75">Le <?= date('d/m/Y à H:i', strtotime($adm['date_admission'])) ?></small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-4">
                <div class="section-title"><i class="bi bi-person-badge"></i> Informations du Patient</div>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="detail-box">
                            <span class="info-label">Nom Complet</span>
                            <span class="info-value"><?= htmlspecialchars($adm['nom'].' '.$adm['prenom']) ?></span>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="detail-box">
                            <span class="info-label">Sexe</span>
                            <span class="info-value"><?= $adm['sexe'] ?></span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="detail-box">
                            <span class="info-label">Âge</span>
                            <span class="info-value"><?= date_diff(date_create($adm['date_naissance']), date_create('today'))->y ?> ans</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="detail-box text-primary">
                            <span class="info-label">Téléphone</span>
                            <span class="info-value"><?= $adm['telephone'] ?></span>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="detail-box bg-white border-dashed">
                            <span class="info-label">Adresse & Email</span>
                            <span class="info-value small"><?= htmlspecialchars($adm['adresse']) ?> | <?= htmlspecialchars($adm['email'] ?? 'N/A') ?></span>
                        </div>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="section-title"><i class="bi bi-hospital"></i> Détails Hospitalisation</div>
                        <div class="row g-2">
                            <div class="col-6">
                                <span class="info-label">Service</span>
                                <span class="info-value text-primary"><?= $adm['service'] ?></span>
                            </div>
                            <div class="col-6">
                                <span class="info-label">Chambre (Type)</span>
                                <span class="info-value"><?= $adm['numero_chambre'] ?> (<?= $adm['type_chambre'] ?? 'Standard' ?>)</span>
                            </div>
                            <div class="col-12 mt-2">
                                <span class="info-label">Médecin Traitant</span>
                                <span class="info-value">Dr. <?= $adm['medecin_nom'] ?? 'Non assigné' ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 border-start ps-4">
                        <div class="section-title"><i class="bi bi-shield-check"></i> Statut Administratif</div>
                        <div class="mb-3">
                            <span class="info-label">État</span>
                            <span class="badge <?= $adm['statut'] == 'En cours' ? 'bg-dark text-dark' : 'bg-success text-white' ?> px-3 py-2">
                                <?= $adm['statut'] ?>
                            </span>
                        </div>
                        <span class="info-label">Contact d'urgence</span>
                        <span class="info-value small">Utiliser le numéro du patient</span>
                    </div>
                </div>

                <div class="mt-4">
                    <div class="section-title"><i class="bi bi-chat-left-text"></i> Motif de l'admission</div>
                    <div class="motif-area">
                        <?= nl2br(htmlspecialchars($adm['motif'])) ?>
                    </div>
                </div>
            </div>

            <div class="modal-footer bg-light px-4">
                <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

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
        language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/fr-FR.json' },
        pageLength: 10,
        dom: '<"d-flex justify-content-between align-items-center mb-3"f>rtip',
    });

    $('#filterStatus').on('change', function(){ table.column(5).search(this.value).draw(); });
    $('#filterService').on('change', function(){ table.column(2).search(this.value).draw(); });
    $('#filterDate').on('change', function(){ table.column(1).search(this.value).draw(); });


});

function deleteAdmission(id){
    Swal.fire({
        title: 'Confirmer la suppression ?',
        text: "Cette action est irréversible !",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#0f766e',
        confirmButtonText: 'Oui, supprimer'
    }).then((result) => {
        if(result.isConfirmed) window.location.href = 'admission_delete.php?id=' + id;
    });
}
</script>
</body>
</html>