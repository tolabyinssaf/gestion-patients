<?php
require_once '../config/connexion.php';
session_start();

// Simulation de session si non définie pour l'affichage du header
$nom_medecin = isset($_SESSION['nom']) ? $_SESSION['nom'] : "Docteur";

$traitements = $pdo->query("SELECT t.*, p.nom, p.prenom FROM traitements t JOIN patients p ON t.id_patient = p.id_patient ORDER BY t.date_traitement DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Traitements | MedCare Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0f766e;
            --primary-light: #f0fdfa;
            --primary-hover: #115e59;
            --sidebar-bg: #0f172a;
            --bg-body: #f8fafc;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --white: #ffffff;
            --border: #e2e8f0;
            --accent: #3b82f6;
            --danger: #ef4444;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: var(--bg-body); color: var(--text-main); }

        /* HEADER */
        header {
            background: var(--white);
            padding: 0 40px; height: 75px;
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid var(--border);
            position: sticky; top: 0; z-index: 100;
        }
        .logo { height: 45px; }
        .user-pill {
            background: var(--primary-light); padding: 8px 18px; border-radius: 12px;
            display: flex; align-items: center; gap: 10px;
            font-size: 14px; font-weight: 600; color: var(--primary);
            border: 1px solid rgba(15, 118, 110, 0.1);
        }

        .container { display: flex; min-height: calc(100vh - 75px); }

        /* SIDEBAR */
        .sidebar { width: 260px; background: var(--sidebar-bg); padding: 24px 16px; flex-shrink: 0; }
        .sidebar h3 { color: rgba(255,255,255,0.3); font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 20px; padding-left: 12px; }
        .sidebar a { display: flex; align-items: center; gap: 12px; color: #94a3b8; text-decoration: none; padding: 12px 16px; border-radius: 10px; margin-bottom: 5px; transition: 0.2s; }
        .sidebar a:hover { background: rgba(255,255,255,0.05); color: #fff; }
        .sidebar a.active { background: var(--primary); color: #fff; }

        /* CONTENT */
        .content { flex: 1; padding: 40px; }
        
        .page-header { 
            margin-bottom: 35px; display: flex; justify-content: space-between; align-items: center; 
            background: white; padding: 25px; border-radius: 16px; box-shadow: var(--shadow);
            border-left: 5px solid var(--primary);
        }
        .page-header-title { display: flex; align-items: center; gap: 15px; }
        .page-icon { width: 50px; height: 50px; background: var(--primary-light); color: var(--primary); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .page-header h1 { font-size: 24px; font-weight: 700; }

        /* STATS GRID */
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 15px; display: flex; align-items: center; gap: 15px; box-shadow: var(--shadow); border: 1px solid var(--border); }
        .stat-icon { width: 45px; height: 45px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; color: white; }
        .bg-primary { background: var(--primary); }
        .bg-accent { background: var(--accent); }
        .bg-orange { background: #f59e0b; }
        .stat-info h3 { font-size: 22px; font-weight: 700; }
        .stat-info p { font-size: 13px; color: var(--text-muted); }

        /* SEARCH & TABLE */
        .table-card { background: white; border-radius: 16px; box-shadow: var(--shadow); border: 1px solid var(--border); overflow: hidden; }
        .table-toolbar { padding: 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: #fcfcfc; }
        
        .search-container { position: relative; width: 350px; }
        .search-container i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--text-muted); }
        .search-container input { width: 100%; padding: 10px 15px 10px 45px; border: 1px solid var(--border); border-radius: 10px; outline: none; transition: 0.3s; }
        .search-container input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.1); }

        .btn-add { background: var(--primary); color: white; padding: 10px 20px; border-radius: 10px; text-decoration: none; font-weight: 600; font-size: 14px; display: flex; align-items: center; gap: 8px; transition: 0.3s; }
        .btn-add:hover { background: var(--primary-hover); transform: translateY(-2px); }

        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th { background: #f8fafc; padding: 15px 20px; text-align: left; font-size: 12px; text-transform: uppercase; color: var(--text-muted); font-weight: 700; border-bottom: 2px solid var(--border); }
        .data-table td { padding: 18px 20px; border-bottom: 1px solid var(--border); font-size: 14px; vertical-align: middle; }
        .data-table tr:hover { background: #f1f5f9; }

        /* PATIENT STYLE */
        .patient-box { display: flex; align-items: center; gap: 12px; }
        .avatar { width: 38px; height: 38px; background: var(--primary-light); color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px; }
        .patient-box span { font-weight: 600; color: var(--text-main); }

        /* TAGS */
        .med-tag { background: #e0f2fe; color: #0369a1; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; border: 1px solid #bae6fd; }
        .date-badge { color: var(--text-muted); font-size: 13px; display: flex; align-items: center; gap: 5px; }
        
        /* ACTIONS */
        .action-btns { display: flex; gap: 8px; }
        .btn-circle { width: 34px; height: 34px; border-radius: 8px; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: 0.2s; border: 1px solid var(--border); }
        .btn-edit { color: var(--accent); background: #eff6ff; }
        .btn-edit:hover { background: var(--accent); color: white; }
        .btn-delete { color: var(--danger); background: #fef2f2; }
        .btn-delete:hover { background: var(--danger); color: white; }

        @media (max-width: 1100px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
    </style>
</head>
<body>

<header>
    <img src="../images/logo_app2.png" alt="Logo" class="logo">
    <div class="user-pill">
        <i class="fa-solid fa-user-md"></i>
        <span><?= htmlspecialchars($nom_medecin) ?></span>
    </div>
</header>

<div class="container">
    <aside class="sidebar">
        <h3>Menu Médical</h3>
        <a href="../connexio_utilisateur/dashboard_medecin.php"><i class="fa-solid fa-house"></i> Tableau de bord</a>
        <a href="../connexio_utilisateur/patients.php"><i class="fa-solid fa-users"></i> Mes Patients</a>
        <a href="../connexio_utilisateur/suivis.php"><i class="fa-solid fa-notes-medical"></i> Suivis</a>
        <a href="traitements.php" class="active"><i class="fa-solid fa-pills"></i> Traitements</a>
        <a href="rendezvous.php"><i class="fa-solid fa-calendar-alt"></i> Rendez-vous</a>
        <div style="height: 1px; background: rgba(255,255,255,0.1); margin: 20px 0;"></div>
        <a href="../connexio_utilisateur/profil_medcin.php"><i class="fa-solid fa-user"></i> Profil</a>
        <a href="../connexio_utilisateur/deconnexion.php" style="color: #fda4af;"><i class="fa-solid fa-sign-out-alt"></i> Déconnexion</a>
    </aside>

    <main class="content">
        <div class="page-header">
            <div class="page-header-title">
                <div class="page-icon"><i class="fas fa-prescription"></i></div>
                <div>
                    <h1>Gestion des Traitements</h1>
                    <p style="color: var(--text-muted); font-size: 14px;">Historique des ordonnances et soins administrés</p>
                </div>
            </div>
            <a href="add.php" class="btn-add">
                <i class="fas fa-plus"></i> Nouveau traitement
            </a>
        </div>

        <div class="stats-grid">
            <?php
            $patients_ids = []; $patients_with_medicament = 0; $latest_date = '';
            foreach($traitements as $t) {
                $patients_ids[$t['id_patient']] = true;
                if (!empty($t['medicament'])) { $patients_with_medicament++; }
                if ($t['date_traitement'] > $latest_date || $latest_date == '') { $latest_date = $t['date_traitement']; }
            }
            $total_traitements = count($traitements);
            $total_patients = count($patients_ids);
            $percentage = $total_traitements > 0 ? round(($patients_with_medicament / $total_traitements) * 100) : 0;
            ?>
            <div class="stat-card">
                <div class="stat-icon bg-primary"><i class="fas fa-file-medical"></i></div>
                <div class="stat-info"><h3><?= $total_traitements ?></h3><p>Total Actes</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-accent"><i class="fas fa-user-injured"></i></div>
                <div class="stat-info"><h3><?= $total_patients ?></h3><p>Patients</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-orange"><i class="fas fa-capsules"></i></div>
                <div class="stat-info"><h3><?= $percentage ?>%</h3><p>Médicamenteux</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-primary"><i class="fas fa-calendar-day"></i></div>
                <div class="stat-info"><h3><?= $latest_date ? date('d/m', strtotime($latest_date)) : '--' ?></h3><p>Dernière Maj</p></div>
            </div>
        </div>

        <div class="table-card">
            <div class="table-toolbar">
                <div class="search-container">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Rechercher un patient, un médicament...">
                </div>
                <div style="font-size: 13px; color: var(--text-muted);">
                    Affichage de <strong><?= $total_traitements ?></strong> enregistrements
                </div>
            </div>

            <?php if ($total_traitements > 0): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Patient</th>
                        <th>Traitement / Description</th>
                        <th>Date</th>
                        <th>Médicament</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="treatmentsTable">
                    <?php foreach($traitements as $t): 
                        $initials = strtoupper(substr($t['nom'], 0, 1) . substr($t['prenom'], 0, 1));
                    ?>
                    <tr>
                        <td style="font-weight: 700; color: var(--text-muted);">#<?= str_pad($t['id_traitement'], 3, '0', STR_PAD_LEFT) ?></td>
                        <td>
                            <div class="patient-box">
                                <div class="avatar"><?= $initials ?></div>
                                <span><?= htmlspecialchars($t['nom'] . ' ' . $t['prenom']) ?></span>
                            </div>
                        </td>
                        <td style="max-width: 300px;">
                            <div style="font-size: 13px; line-height: 1.4;">
                                <?= nl2br(htmlspecialchars(mb_strimwidth($t['description'], 0, 100, "..."))) ?>
                            </div>
                        </td>
                        <td>
                            <div class="date-badge">
                                <i class="far fa-calendar-alt"></i>
                                <?= date('d/m/Y', strtotime($t['date_traitement'])) ?>
                            </div>
                        </td>
                        <td>
                            <?php if($t['medicament']): ?>
                                <span class="med-tag"><i class="fas fa-pills"></i> <?= htmlspecialchars($t['medicament']) ?></span>
                            <?php else: ?>
                                <span style="color: #cbd5e1; font-style: italic; font-size: 12px;">Néant</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="action-btns">
                                <a href="edit.php?id=<?= $t['id_traitement'] ?>" class="btn-circle btn-edit" title="Modifier">
                                    <i class="fas fa-pen"></i>
                                </a>
                                <a href="delete.php?id=<?= $t['id_traitement'] ?>" 
                                   class="btn-circle btn-delete" 
                                   onclick="return confirm('Supprimer définitivement ce traitement ?')" title="Supprimer">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <div style="text-align: center; padding: 60px;">
                    <i class="fas fa-folder-open" style="font-size: 40px; color: #e2e8f0; margin-bottom: 15px;"></i>
                    <p style="color: var(--text-muted);">Aucun traitement enregistré pour le moment.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<script>
    // Recherche dynamique
    document.getElementById('searchInput').addEventListener('keyup', function() {
        const searchTerm = this.value.toLowerCase();
        const rows = document.querySelectorAll('#treatmentsTable tr');
        rows.forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(searchTerm) ? '' : 'none';
        });
    });
</script>

</body>
</html>