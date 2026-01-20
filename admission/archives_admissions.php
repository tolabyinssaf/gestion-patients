<?php
session_start();
include "../config/connexion.php";

try {
    // 1. Njibu l-archives + DATEDIFF + Tracabilité

        $query = "
        SELECT 
            arc.id_admission, arc.id_patient, arc.date_admission, arc.archived_at, 
            arc.service, arc.motif, arc.archive_reason, arc.id_medecin, arc.id_chambre,
            p.nom, p.prenom, p.telephone, p.sexe, p.date_naissance, p.CIN,
            u.nom AS medecin_nom,
            c.numero_chambre,
            DATEDIFF(arc.archived_at, arc.date_admission) as nb_jours
        FROM admissions_archive arc
        JOIN patients p ON arc.id_patient = p.id_patient
        LEFT JOIN utilisateurs u ON arc.id_medecin = u.id_user
        LEFT JOIN chambres c ON arc.id_chambre = c.id_chambre
        GROUP BY arc.id_admission
        ORDER BY arc.archived_at DESC";

    $stmt = $pdo->query($query);
    $archives = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Stats dyal l-yawm
    $sorties_today = $pdo->query("SELECT COUNT(*) FROM admissions_archive WHERE DATE(archived_at) = CURDATE()")->fetchColumn();
    $total_archives = count($archives);

} catch (PDOException $e) {
    die("Erreur SQL : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Archive ADmission</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #0d9488; --danger: #ef4444; --dark: #0f172a; --bg: #f8fafc; }
        body { background: var(--bg); font-family: 'Plus Jakarta Sans', sans-serif; color: #1e293b; }
        
        .sidebar { width: 260px; height: 100vh; position: fixed; background: var(--dark); padding: 2rem 1rem; color: white; z-index: 100; }
        .main-content { margin-left: 260px; padding: 2.5rem; }
        
        /* Design "Kif kant" - Glassmorphism & Cards */
        .stat-card { background: white; border-radius: 24px; padding: 1.5rem; border: 1px solid rgba(0,0,0,0.05); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.04); transition: 0.3s; }
        .stat-card:hover { transform: translateY(-5px); }
        .icon-box { width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; }

        .archive-card { background: white; border-radius: 30px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.05); }
        .table thead th { background: #f8fafc; color: #64748b; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; padding: 1.5rem 1.25rem; }
        
        /* Action Buttons Styled */
        .action-btn { width: 38px; height: 38px; border-radius: 12px; border: 1px solid #e2e8f0; display: inline-flex; align-items: center; justify-content: center; transition: 0.3s; background: white; color: #64748b; }
        .btn-view:hover { color: var(--primary); border-color: var(--primary); background: #f0fdfa; }
        .btn-undo:hover { color: #f59e0b; border-color: #f59e0b; background: #fffbeb; }
        .btn-delete:hover { color: var(--danger); border-color: var(--danger); background: #fef2f2; }

        /* Filter Section */
        .filter-pane { background: white; border-radius: 20px; padding: 1.5rem; margin-bottom: 2rem; border: 1px solid #e2e8f0; }
        .form-control, .form-select { border-radius: 12px; padding: 0.6rem 1rem; border: 1px solid #e2e8f0; background: #f8fafc; }

        @media print { .sidebar, .no-print, .filter-pane { display: none !important; } .main-content { margin-left: 0; padding: 0; } }
    </style>
</head>
<body>

    <aside class="sidebar">
        <h3 style="color:rgba(255,255,255,0.3); font-size:11px; text-transform:uppercase; margin-bottom:20px; padding-left:12px;">Menu Gestion</h3>
        <a href="../connexion_secretaire/dashboard_secretaire.php"><i class="fa-solid fa-chart-line"></i> Vue Générale</a>
        <a href="../connexion_secretaire/patients_secr.php" ><i class="fa-solid fa-user-group"></i> Patients</a>
         <a href="admissions_list.php" ><i class="fa-solid fa-hospital-user"></i> Admissions</a>
        <a href="../connexion_secretaire/suivis.php"><i class="fa-solid fa-calendar-check"></i> Suivis</a>
        <a href="../connexion_secretaire/caisse.php"><i class="fa-solid fa-wallet"></i> Caisse & Factures</a>
        <a href="archives_admissions.php" class="active"><i class="fa-solid fa-box-archive"></i> Archives</a>
         <a href="../connexion_secretaire/profil_secretaire.php"><i class="fa-solid fa-user"></i> Profil</a>
        <div style="height: 1px; background: rgba(255,255,255,0.1); margin: 20px 0;"></div>
        <a href="../connexio_utilisateur/deconnexion.php" style="color: #fda4af;"><i class="fa-solid fa-power-off"></i> Déconnexion</a>
    </aside>

<main class="main-content">
    <div class="d-flex justify-content-between align-items-end mb-4 no-print">
        
        <div class="d-flex gap-2">
       
            <button onclick="window.print()" class="btn btn-dark rounded-pill px-4 fw-bold"><i class="bi bi-printer me-2"></i>Imprimer</button>
        </div>
    </div>

    <div class="row g-4 mb-4 no-print">
        <div class="col-md-6">
            <div class="stat-card d-flex align-items-center gap-3">
                <div class="icon-box bg-primary bg-opacity-10 text-primary"><i class="bi bi-collection-fill"></i></div>
                <div><h4 class="fw-800 mb-0"><?= $total_archives ?></h4><p class="text-muted small mb-0">Total des Dossiers Archivés</p></div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="stat-card d-flex align-items-center gap-3">
                <div class="icon-box bg-info bg-opacity-10 text-info"><i class="bi bi-check2-circle"></i></div>
                <div><h4 class="fw-800 mb-0"><?= $sorties_today ?></h4><p class="text-muted small mb-0">Sorties (Dernières 24h)</p></div>
            </div>
        </div>
    </div>

    <div class="filter-pane no-print">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="small fw-bold text-muted mb-2">Recherche Patient / CIN</label>
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-end-0"><i class="bi bi-search"></i></span>
                    <input type="text" id="searchPatient" class="form-control border-start-0" placeholder="Nom, Prénom ou CIN...">
                </div>
            </div>
            <div class="col-md-3">
                <label class="small fw-bold text-muted mb-2">Filtrer par Service</label>
                <select id="filterService" class="form-select">
                    <option value="">Tous les services</option>
                    <?php 
                        $services = array_unique(array_column($archives, 'service'));
                        foreach($services as $s) echo "<option value='".htmlspecialchars($s)."'>$s</option>";
                    ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="small fw-bold text-muted mb-2">Date d'archivage</label>
                <input type="date" id="filterDate" class="form-control">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-light w-100 rounded-3 fw-bold border" onclick="resetFilters()">Effacer</button>
            </div>
        </div>
    </div>

    <div class="archive-card">
        <table class="table align-middle mb-0" id="archiveTable">
            <thead>
                <tr>
                    <th>Ref ID</th>
                    <th>Patient</th>
                    <th>Période d'Hosp.</th>
                    <th>Service & Médecin</th>
                    <th>Status / Chambre</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($archives as $row): ?>
                <tr data-service="<?= htmlspecialchars($row['service']) ?>" data-date="<?= date('Y-m-d', strtotime($row['archived_at'])) ?>">
                    <td class="fw-bold text-muted ps-4">#<?= $row['id_admission'] ?></td>
                    <td>
                        <div class="fw-bold text-dark"><?= htmlspecialchars($row['nom'].' '.$row['prenom']) ?></div>
                        <small class="text-muted">CIN: <?= $row['CIN'] ?></small>
                    </td>
                    <td>
                        <div class="small fw-500">In: <?= date('d/m/y', strtotime($row['date_admission'])) ?></div>
                        <div class="small text-primary fw-800">Out: <?= date('d/m/y', strtotime($row['archived_at'])) ?></div>
                    </td>
                    <td>
                        <div class="badge bg-light text-primary border-0 rounded-pill px-3 mb-1 fw-bold"><?= strtoupper($row['service']) ?></div>
                        <div class="small text-muted">Dr. <?= htmlspecialchars($row['medecin_nom']) ?></div>
                    </td>
                    <td>
                        <span class="small fw-bold text-muted"><i class="bi bi-door-open me-1"></i>Ch. <?= $row['numero_chambre'] ?? '--' ?></span>
                        <div class="small text-success fw-bold"><?= $row['nb_jours'] ?> jours</div>
                    </td>
                    <td class="text-end pe-4">
                        <div class="d-flex gap-2 justify-content-end no-print">
                            <button class="action-btn btn-view" title="Voir Dossier" data-bs-toggle="modal" data-bs-target="#view<?= $row['id_admission'] ?>"><i class="bi bi-eye-fill"></i></button>
                            <button class="action-btn btn-undo" title="Restaurer" onclick="confirmAction('restaurer_admission.php?id=<?= $row['id_admission'] ?>', 'Voulez-vous réactiver ce dossier ?')"><i class="bi bi-arrow-counterclockwise"></i></button>
                            <button class="action-btn btn-delete" title="Supprimer" onclick="confirmAction('supprimer_archive.php?id=<?= $row['id_admission'] ?>', 'Attention: Suppression définitive !')"><i class="bi bi-trash3-fill"></i></button>
                        </div>
                    </td>
                </tr>

                <div class="modal fade" id="view<?= $row['id_admission'] ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-xl modal-dialog-centered">
                        <div class="modal-content border-0 shadow-lg" style="border-radius: 30px;">
                            <div class="modal-header border-0 p-4 bg-dark text-white">
                                <div>
                                    <h4 class="fw-bold mb-0">Fiche d'Archivage #<?= $row['id_admission'] ?></h4>
                                    <small class="opacity-50 text-uppercase">Référence Dossier Médical</small>
                                </div>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body p-4 bg-light bg-opacity-50">
                                <div class="row g-4">
                                    <div class="col-md-4">
                                        <div class="bg-white p-4 rounded-4 shadow-sm border h-100">
                                            <h6 class="fw-800 text-primary small text-uppercase mb-3">Identité Patient</h6>
                                            <h5 class="fw-bold"><?= htmlspecialchars($row['nom'].' '.$row['prenom']) ?></h5>
                                            <p class="text-muted mb-1"><b>CIN:</b> <?= $row['CIN'] ?></p>
                                            <p class="text-muted"><b>Né le:</b> <?= date('d/m/Y', strtotime($row['date_naissance'])) ?></p>
                                            <hr>
                                            <div class="p-3 rounded-3 bg-danger bg-opacity-10">
                                                <small class="d-block fw-bold text-danger">Raison de l'archive:</small>
                                                <span class="fw-bold"><?= $row['archive_reason'] ?? 'Sortie Autorisée' ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="bg-white p-4 rounded-4 shadow-sm border">
                                            <h6 class="fw-800 text-primary small text-uppercase mb-3">Détails Hospitalisation</h6>
                                            <div class="row g-3">
                                                <div class="col-6"><small class="text-muted d-block">Médecin Traitant</small><span class="fw-bold">Dr. <?= $row['medecin_nom'] ?></span></div>
                                                <div class="col-6"><small class="text-muted d-block">Service</small><span class="fw-bold"><?= $row['service'] ?></span></div>
                                                <div class="col-6"><small class="text-muted d-block">Date d'Entrée</small><span class="fw-bold"><?= $row['date_admission'] ?></span></div>
                                                <div class="col-6"><small class="text-muted d-block">Date d'Archivage</small><span class="fw-bold"><?= $row['archived_at'] ?></span></div>
                                            </div>
                                            <hr>
                                            <h6 class="fw-800 text-dark small text-uppercase mb-2">Motif / Rapport Médical :</h6>
                                            <div class="p-3 rounded-3 bg-light border-start border-primary border-4">
                                                <p class="small text-muted mb-0 lh-lg"><?= nl2br(htmlspecialchars($row['motif'])) ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer border-0 p-4">
                                <button class="btn btn-outline-dark rounded-pill px-4 fw-bold" onclick="window.print()">Exporter PDF</button>
                                <button class="btn btn-primary rounded-pill px-4 fw-bold" data-bs-dismiss="modal">Fermer</button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>

<script>
// --- Système de Filtrage Dynamique ---
const searchPatient = document.getElementById('searchPatient');
const filterService = document.getElementById('filterService');
const filterDate = document.getElementById('filterDate');
const tableRows = document.querySelectorAll('#archiveTable tbody tr');

function applyFilters() {
    const searchVal = searchPatient.value.toLowerCase();
    const serviceVal = filterService.value;
    const dateVal = filterDate.value;

    tableRows.forEach(row => {
        const text = row.innerText.toLowerCase();
        const service = row.getAttribute('data-service');
        const date = row.getAttribute('data-date');

        const matchesSearch = text.includes(searchVal);
        const matchesService = serviceVal === "" || service === serviceVal;
        const matchesDate = dateVal === "" || date === dateVal;

        row.style.display = (matchesSearch && matchesService && matchesDate) ? "" : "none";
    });
}

searchPatient.addEventListener('keyup', applyFilters);
filterService.addEventListener('change', applyFilters);
filterDate.addEventListener('change', applyFilters);

function filterToday() {
    const today = new Date().toISOString().split('T')[0];
    filterDate.value = today;
    applyFilters();
}

function resetFilters() {
    searchPatient.value = "";
    filterService.value = "";
    filterDate.value = "";
    applyFilters();
}

function confirmAction(url, msg) {
    if(confirm(msg)) window.location.href = url;
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>