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
          OR a.statut = 'en cours'
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
            --bg-body: #f1f5f9; /* Un gris très léger pour faire ressortir les cards blanches */
            --border: #e2e8f0;
            --header-height: 75px;
            --sidebar-width: 260px;
            --shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        body { background: var(--bg-body); font-family: 'Inter', sans-serif; color: #1e293b; }

        /* HEADER */
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

        /* SIDEBAR */
        .sidebar { 
            width: var(--sidebar-width); background: var(--sidebar-bg); 
            position: fixed; top: var(--header-height); left: 0; bottom: 0; 
            padding: 24px 16px; z-index: 1000;
        }
        .sidebar a { display: flex; align-items: center; gap: 12px; color: #94a3b8; text-decoration: none; padding: 12px 16px; border-radius: 10px; margin-bottom: 5px; }
        .sidebar a.active { background: var(--primary); color: #fff; }
        .sidebar a:hover:not(.active) { background: rgba(255,255,255,0.05); color: #fff; }

        /* CONTENU */
        .content { 
            margin-left: var(--sidebar-width); margin-top: var(--header-height); 
            padding: 40px; min-height: calc(100vh - var(--header-height));
        }

        /* STATS CARDS - VERSION CLAIRE AVEC OMBRE */
        .stat-pill-box {
            background: #ffffff; 
            border: 1px solid var(--border); 
            border-radius: 20px;
            padding: 20px 25px; 
            display: flex; align-items: center; gap: 18px; 
            box-shadow: var(--shadow); /* Ajout de l'ombre */
            transition: transform 0.2s ease;
        }
        .stat-pill-box:hover { transform: translateY(-3px); }

        /* RECHERCHE - VERSION CLAIRE AVEC OMBRE */
        .sticky-search-wrapper {
            position: sticky; top: var(--header-height); z-index: 900;
            background: var(--bg-body); padding: 20px 0;
        }
        .search-bar-pro {
            background: #ffffff; border: 1px solid var(--border); border-radius: 15px;
            padding: 15px 25px; display: flex; align-items: center; gap: 15px;
            box-shadow: var(--shadow); /* Ajout de l'ombre */
        }
        .search-bar-pro input { border: none; outline: none; width: 100%; font-weight: 500; background: transparent; }

        /* TABLEAU - VERSION CLAIRE AVEC OMBRE */
        .table-card { 
            background: #ffffff; 
            border-radius: 24px; 
            border: 1px solid var(--border); 
            overflow: hidden; 
            box-shadow: var(--shadow); /* Ajout de l'ombre */
            margin-bottom: 50px;
        }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f8fafc; padding: 18px 20px; font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700; letter-spacing: 0.8px; border-bottom: 2px solid #f1f5f9; }
        td { padding: 20px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; background: #fff; }
        tr:last-child td { border-bottom: none; }

        /* BADGES ET BOUTONS */
        .badge-status { padding: 6px 14px; border-radius: 10px; font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px; }
        .status-done { background: #dcfce7; color: #15803d; }
        .status-wait { background: #fff7ed; color: #c2410c; border: 1px solid #ffedd5; }

        .btn-circle { width: 42px; height: 42px; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; transition: 0.3s; border: none; }
        .btn-view { background: #f1f5f9; color: #475569; }
        .btn-view:hover { background: #e2e8f0; color: #1e293b; }
        .btn-add { background: var(--primary); color: #fff; box-shadow: 0 4px 10px rgba(15, 118, 110, 0.3); }
        .btn-add:hover { background: #0d9488; transform: scale(1.05); }

    </style>
</head>
<body>

<header>
    <img src="../images/logo_app2.png" alt="Logo" class="logo" style="height: 45px;">
    <div class="d-flex align-items-center gap-3">
        <div class="user-pill">
            <i class="fa-solid fa-user-nurse"></i>
            <span>Inf. <?= $nom_complet ?></span>
        </div>
    </div>
</header>

<div class="wrapper">
    <aside class="sidebar">
        <h3 style="color:rgba(255,255,255,0.3); font-size:11px; text-transform:uppercase; margin-bottom:20px; padding-left:12px;">Menu Infirmier</h3>
        <a href="dashboard_infirmier.php"><i class="fa-solid fa-chart-line"></i> Vue Générale</a>
        <a href="liste_patients_inf.php" class="active"><i class="fa-solid fa-user-injured"></i> Patients</a>
        <a href="planning.php"><i class="fa-solid fa-calendar-alt"></i> Planning</a>
        <a href="profil_infirmier.php"><i class="fa-solid fa-user"></i> Profil</a>
        <div style="height: 1px; background: rgba(255,255,255,0.1); margin: 20px 0;"></div>
        <a href="../connexio_utilisateur/deconnexion.php" style="color: #fda4af;"><i class="fa-solid fa-power-off"></i> Déconnexion</a>
    </aside>

    <main class="content">
        <div class="row g-4 mb-2">
            <div class="col-md-4">
                <div class="stat-pill-box">
                    <div style="background: #ecfeff; padding: 12px; border-radius: 12px;"><i class="fa-solid fa-bed text-primary fs-4"></i></div>
                    <div><div class="text-muted small fw-medium">Hospitalisés</div><div class="h4 fw-bold mb-0"><?= $total ?></div></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-pill-box">
                    <div style="background: #fff7ed; padding: 12px; border-radius: 12px;"><i class="fa-solid fa-clock-rotate-left text-warning fs-4"></i></div>
                    <div><div class="text-muted small fw-medium">À traiter</div><div class="h4 fw-bold mb-0 text-warning"><?= $attente ?></div></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-pill-box">
                    <div style="background: #f0fdf4; padding: 12px; border-radius: 12px;"><i class="fa-solid fa-circle-check text-success fs-4"></i></div>
                    <div><div class="text-muted small fw-medium">Terminés</div><div class="h4 fw-bold mb-0 text-success"><?= $termine ?></div></div>
                </div>
            </div>
        </div>

        <div class="sticky-search-wrapper">
            <div class="search-bar-pro">
                <i class="fa-solid fa-magnifying-glass text-muted fs-5"></i>
                <input type="text" id="searchInput" placeholder="Rechercher par Nom, Prénom ou CIN...">
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
                            <div class="fw-bold text-dark" style="font-size: 15px;"><?= strtoupper($p['nom']) ?> <?= $p['prenom'] ?></div>
                            <div class="text-muted small fw-medium"><?= $p['cin'] ?></div>
                        </td>
                        <td>
                            <span class="badge bg-light text-primary border mb-1" style="font-weight: 600;"><?= $p['service'] ?></span>
                            <div class="small text-muted"><i class="fa-solid fa-user-md me-1"></i> Dr. <?= $p['med_nom'] ?></div>
                        </td>
                        <td>
                            <?php if($p['dernier_soin']): ?>
                                <div class="fw-bold text-dark"><?= date('H:i', strtotime($p['dernier_soin'])) ?></div>
                                <div class="text-muted small"><?= date('d/m/Y', strtotime($p['dernier_soin'])) ?></div>
                            <?php else: ?>
                                <span class="text-muted small italic">Aucun soin enregistré</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php 
                            $is_today = ($p['dernier_soin'] && date('Y-m-d', strtotime($p['dernier_soin'])) == date('Y-m-d'));
                            if ($is_today): ?>
                                <span class="badge-status status-done"><i class="fa-solid fa-check-double"></i> EFFECTUÉ</span>
                            <?php else: ?>
                                <span class="badge-status status-wait"><i class="fa-solid fa-circle-exclamation"></i> À PLANIFIER</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <a href="dossier_patient.php?id_adm=<?= $p['id_admission'] ?>" class="btn-circle btn-view" title="Dossier"><i class="fa-solid fa-file-medical"></i></a>
                            <a href="saisir_soins.php?id_adm=<?= $p['id_admission'] ?>" class="btn-circle btn-add ms-2" title="Saisir Soin"><i class="fa-solid fa-plus"></i></a>
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
        const text = row.innerText.toUpperCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
});
</script>

</body>
</html>