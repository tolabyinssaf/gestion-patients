<?php
session_start();
include("../config/connexion.php");

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'infirmier') {
    header("Location: ../login.php"); exit;
}

// Infos Infirmier + Photo
$inf_id = $_SESSION['user_id'];
$stmt_inf = $pdo->prepare("SELECT nom, prenom FROM utilisateurs WHERE id_user = ?");
$stmt_inf->execute([$inf_id]);
$inf_data = $stmt_inf->fetch();
$nom_complet = htmlspecialchars($inf_data['prenom'] . " " . $inf_data['nom']);
$user_photo = !empty($inf_data['photo']) ? "../images/".$inf_data['photo'] : "../images/default_user.png";

// Récupération Patients

$query = "SELECT a.id_admission, p.nom, p.prenom, p.cin, a.date_admission, a.service, 
                 u.nom as med_nom, u.prenom as med_prenom,
                 (SELECT MAX(date_soin) FROM soins_patients WHERE id_admission = a.id_admission) as dernier_soin
          FROM admissions a
          LEFT JOIN patients p ON a.id_patient = p.id_patient 
          LEFT JOIN utilisateurs u ON a.id_medecin = u.id_user
          WHERE a.date_sortie IS NULL 
          OR a.status = 'en cours'
          ORDER BY a.date_admission DESC";

$stmt = $pdo->prepare($query);
$stmt->execute();
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Stats
$total = count($patients);
$termine = 0;
foreach($patients as $row) {
    if ($row['dernier_soin'] && date('Y-m-d', strtotime($row['dernier_soin'])) == date('Y-m-d')) $termine++;
}
$attente = $total - $termine;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>MedCare | Liste des Soins</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #0f766e; 
            --primary-light: #f0fdfa;
            --sidebar-bg: #0f172a;
            --bg-body: #f8fafc;
            --border: #e2e8f0;
            --header-height: 75px;
            --sidebar-width: 260px;
        }

        body { background: var(--bg-body); font-family: 'Inter', sans-serif; color: #1e293b; }

        /* HEADER (STYLE EXACT DU DASHBOARD) */
        header {
            background: #fff;
            padding: 0 40px; 
            height: var(--header-height);
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid var(--border);
            position: fixed; top: 0; left: 0; right: 0; z-index: 1001;
        }
        .user-pill { 
            background: var(--primary-light); padding: 5px 15px; border-radius: 12px; 
            display: flex; align-items: center; gap: 10px; font-weight: 600; color: var(--primary); 
        }
        .user-photo-circle { 
            width: 35px; height: 35px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary);
        }

        /* SIDEBAR (STYLE EXACT DU DASHBOARD) */
        .sidebar { 
            width: var(--sidebar-width); background: var(--sidebar-bg); 
            position: fixed; top: var(--header-height); left: 0; bottom: 0; 
            padding: 24px 16px; z-index: 1000;
        }
        .sidebar a { display: flex; align-items: center; gap: 12px; color: #94a3b8; text-decoration: none; padding: 12px 16px; border-radius: 10px; margin-bottom: 5px; }
        .sidebar a.active { background: var(--primary); color: #fff; }
        .sidebar a:hover:not(.active) { background: rgba(255,255,255,0.05); color: #fff; }

        /* CONTENU CENTRAL */
        .content { 
            margin-left: var(--sidebar-width); margin-top: var(--header-height); 
            padding: 40px; min-height: calc(100vh - var(--header-height));
        }

        /* BARRE DE RECHERCHE FIXE (STICKY) */
        .sticky-search-wrapper {
            position: sticky;
            top: var(--header-height);
            z-index: 900;
            background: var(--bg-body);
            padding: 20px 0;
            margin-bottom: 10px;
        }
        .search-bar-pro {
            background: #fff; border: 1px solid var(--border); border-radius: 12px;
            padding: 12px 20px; display: flex; align-items: center; gap: 12px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        }
        .search-bar-pro input { border: none; outline: none; width: 100%; font-weight: 500; }

        /* STATS CARDS */
        .stat-pill-box {
            background: #fff; border: 1px solid var(--border); border-radius: 15px;
            padding: 15px 25px; display: flex; align-items: center; gap: 15px; flex: 1;
        }

        /* TABLEAU PROFESSIONNEL */
        .table-card { background: #fff; border-radius: 20px; border: 1px solid var(--border); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f8fafc; padding: 15px 20px; font-size: 11px; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; }
        td { padding: 18px 20px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }

        /* STATUS COLORS (ORANGE AU LIEU DE ROUGE) */
        .badge-status { padding: 6px 12px; border-radius: 8px; font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; gap: 5px; }
        .status-done { background: #dcfce7; color: #15803d; }
        .status-wait { background: #fef3c7; color: #b45309; } /* Orange doux */

        .btn-circle { width: 38px; height: 38px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; transition: 0.2s; }
        .btn-view { background: #f1f5f9; color: #475569; }
        .btn-add { background: var(--primary); color: #fff; }
    </style>
</head>
<body>

<header>
    <img src="../images/logo_app2.png" alt="Logo" class="logo" style="height: 45px;">
    <div class="d-flex align-items-center gap-3">
        <div class="user-pill">
            <span>Inf. <?= $nom_complet ?></span>
        </div>
    </div>
</header>

<div class="wrapper">
    <aside class="sidebar">
        <h3 style="color:rgba(255,255,255,0.3); font-size:11px; text-transform:uppercase; margin-bottom:20px; padding-left:12px;">Menu Infirmier</h3>
        <a href="dashboard_infirmier.php"><i class="fa-solid fa-chart-line"></i> Tableau de bord</a>
        <a href="liste_patients_inf.php" class="active"><i class="fa-solid fa-user-injured"></i> Liste des Soins</a>
        <a href="saisir_soins.php"><i class="fa-solid fa-notes-medical"></i> Saisir un Soin</a>
        <a href="planning.php"><i class="fa-solid fa-calendar-alt"></i> Planning</a>
        <div style="height: 1px; background: rgba(255,255,255,0.1); margin: 20px 0;"></div>
        <a href="../connexio_utilisateur/deconnexion.php" style="color: #fda4af;"><i class="fa-solid fa-power-off"></i> Déconnexion</a>
    </aside>

    <main class="content">
        <div class="row g-4 mb-2">
            <div class="col-md-4">
                <div class="stat-pill-box">
                    <i class="fa-solid fa-bed text-primary fs-4"></i>
                    <div><div class="text-muted small">Hospitalisés</div><div class="h5 fw-bold mb-0"><?= $total ?></div></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-pill-box">
                    <i class="fa-solid fa-clock-rotate-left text-warning fs-4"></i>
                    <div><div class="text-muted small">À traiter</div><div class="h5 fw-bold mb-0 text-warning"><?= $attente ?></div></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-pill-box">
                    <i class="fa-solid fa-circle-check text-success fs-4"></i>
                    <div><div class="text-muted small">Terminés</div><div class="h5 fw-bold mb-0 text-success"><?= $termine ?></div></div>
                </div>
            </div>
        </div>

        <div class="sticky-search-wrapper">
            <div class="search-bar-pro">
                <i class="fa-solid fa-magnifying-glass text-muted"></i>
                <input type="text" id="searchInput" placeholder="Rechercher un patient par Nom ou CIN...">
            </div>
        </div>

        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>Identité Patient</th>
                        <th>Service & Médecin</th>
                        <th>Dernier Passage</th>
                        <th>Vigilance Soin</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody id="patientTable">
                    <?php foreach($patients as $p): ?>
                    <tr>
                        <td>
                            <div class="fw-bold text-dark"><?= strtoupper($p['nom']) ?> <?= $p['prenom'] ?></div>
                            <div class="text-muted small"><?= $p['cin'] ?></div>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark mb-1"><?= $p['service'] ?></span>
                            <div class="small text-muted">Dr. <?= $p['med_nom'] ?></div>
                        </td>
                        <td>
                            <?php if($p['dernier_soin']): ?>
                                <div class="fw-semibold text-dark"><?= date('H:i', strtotime($p['dernier_soin'])) ?></div>
                                <div class="text-muted small"><?= date('d/m/Y', strtotime($p['dernier_soin'])) ?></div>
                            <?php else: ?>
                                <span class="text-muted small">Aucun soin</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php 
                            $is_today = ($p['dernier_soin'] && date('Y-m-d', strtotime($p['dernier_soin'])) == date('Y-m-d'));
                            if ($is_today): ?>
                                <span class="badge-status status-done"><i class="fa-solid fa-check"></i> EFFECTUÉ</span>
                            <?php else: ?>
                                <span class="badge-status status-wait"><i class="fa-solid fa-clock"></i> À PLANIFIER</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <a href="dossier_patient.php?id_adm=<?= $p['id_admission'] ?>" class="btn-circle btn-view" title="Dossier"><i class="fa-solid fa-file-medical"></i></a>
                            <a href="saisir_soins.php?id_adm=<?= $p['id_admission'] ?>" class="btn-circle btn-add ms-1" title="Saisir Soin"><i class="fa-solid fa-plus"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<script>
document.getElementById('searchInput').addEventListener('keyup', function() {
    const filter = this.value.toUpperCase();
    const rows = document.querySelectorAll('#patientTable tr');
    
    rows.forEach(row => {
        const text = row.cells[0].innerText.toUpperCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
});
</script>

</body>
</html>