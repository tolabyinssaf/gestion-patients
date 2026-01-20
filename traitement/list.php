<?php
require_once '../config/connexion.php';

// Messages
$success = isset($_GET['success']) ? urldecode($_GET['success']) : '';
$error = isset($_GET['error']) ? urldecode($_GET['error']) : '';

// Récupérer les traitements avec pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

// Compter le total
$stmt = $pdo->query("SELECT COUNT(*) as total FROM traitements");
$total_traitements = $stmt->fetch()['total'];
$total_pages = ceil($total_traitements / $limit);

// Récupérer les traitements paginés
$stmt = $pdo->prepare("
    SELECT t.*, p.nom, p.prenom, p.CIN, p.telephone 
    FROM traitements t 
    JOIN patients p ON t.id_patient = p.id_patient 
    ORDER BY t.date_traitement DESC, t.id_traitement DESC 
    LIMIT ? OFFSET ?
");
$stmt->bindValue(1, $limit, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$traitements = $stmt->fetchAll();

// Statistiques
$stats = $pdo->query("
    SELECT 
        COUNT(*) as total,
        COUNT(DISTINCT id_patient) as patients,
        SUM(CASE WHEN medicament IS NOT NULL AND medicament != '' THEN 1 ELSE 0 END) as avec_medicament,
        MAX(date_traitement) as dernier_traitement
    FROM traitements
")->fetch();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Traitements | MedCare Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #0f766e;
            --primary-light: #f0fdfa;
            --sidebar-bg: #0f172a;
            --bg-body: #f1f5f9;
            --danger: #e11d48;
            --success: #10b981;
            --warning: #f59e0b;
            --info: #3b82f6;
            --text-main: #1e293b;
            --text-muted: #64748b;
        }

        * { margin:0; padding:0; box-sizing:border-box; font-family: 'Inter', sans-serif; }
        body { background: var(--bg-body); color: var(--text-main); }

        /* Header Modernisé */
        header { 
            background: #fff; padding: 0 40px; height: 70px; 
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid #e2e8f0; position: sticky; top: 0; z-index: 100;
        }
        .user-pill { 
            background: var(--primary-light); 
            padding: 8px 15px; 
            border-radius: 10px; 
            color: var(--primary); 
            font-weight: 700; 
            font-size: 14px;
            display: flex; align-items: center; gap: 8px;
        }

        .container { display: flex; min-height: calc(100vh - 70px); }

        /* Sidebar Modernisée */
        .sidebar { 
            width: 260px; 
            background: var(--sidebar-bg); 
            padding: 25px 15px;
        }
        .sidebar h3 { 
            color: #475569; 
            font-size: 11px; 
            text-transform: uppercase; 
            letter-spacing: 1px; 
            margin: 20px 0 10px 10px; 
        }
        .sidebar a { 
            display: flex; align-items: center; gap: 12px; 
            color: #94a3b8; text-decoration: none; 
            padding: 12px; border-radius: 8px; 
            margin-bottom: 4px; transition: 0.3s; 
        }
        .sidebar a:hover, .sidebar a.active { 
            background: var(--primary); color: #fff; 
        }

        /* Content Area */
        .main-content { flex: 1; padding: 40px; }

        .page-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 35px;
        }
        .page-title h1 { font-size: 26px; font-weight: 900; color: #0f172a; }
        .page-title p { color: var(--text-muted); }

        .btn-add {
            background: var(--primary); color: white;
            padding: 12px 20px; border-radius: 10px;
            text-decoration: none; font-weight: 700;
            display: flex; align-items: center; gap: 8px;
            transition: 0.3s;
        }
        .btn-add:hover { opacity: 0.9; transform: translateY(-2px); }

        /* Stats Grid */
        .stats-grid { 
            display: grid; grid-template-columns: repeat(4, 1fr); 
            gap: 20px; margin-bottom: 40px; 
        }
        .stat-card { 
            background: #fff; padding: 25px; border-radius: 20px; 
            border: 1px solid #e2e8f0; display: flex; align-items: center; gap: 20px;
        }
        .stat-icon {
            width: 50px; height: 50px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center; font-size: 20px;
        }
        .icon-primary { background: #eff6ff; color: var(--info); }
        .icon-secondary { background: #fef2f2; color: var(--danger); }
        .icon-success { background: #f0fdf4; color: var(--success); }
        .icon-warning { background: #fff7ed; color: var(--warning); }

        /* Table Design */
        .table-container { 
            background: #fff; border-radius: 20px; 
            padding: 30px; border: 1px solid #e2e8f0;
        }
        .table-toolbar {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 25px;
        }
        .search-box {
            position: relative; width: 300px;
        }
        .search-box input {
            width: 100%; padding: 10px 15px 10px 40px;
            border: 1px solid #e2e8f0; border-radius: 10px;
            outline: none; transition: 0.3s;
        }
        .search-box i {
            position: absolute; left: 15px; top: 50%;
            transform: translateY(-50%); color: var(--text-muted);
        }

        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th { 
            text-align: left; padding: 15px; 
            color: #64748b; font-size: 12px; 
            text-transform: uppercase; border-bottom: 2px solid #f1f5f9; 
        }
        .data-table td { padding: 15px; border-bottom: 1px solid #f1f5f9; }

        /* Patient Cell Customization */
        .patient-cell { display: flex; align-items: center; gap: 12px; }
        .patient-avatar {
            width: 35px; height: 35px; background: var(--primary-light);
            color: var(--primary); border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: 12px;
        }
        .patient-name { font-weight: 700; }
        .patient-cin { font-size: 11px; color: var(--text-muted); }

        /* Badges */
        .date-badge {
            background: #f8fafc; padding: 5px 10px;
            border-radius: 6px; font-size: 13px; font-weight: 600;
        }
        .medicament-tag {
            background: #f1f5f9; color: #475569;
            padding: 4px 10px; border-radius: 6px; font-size: 12px;
        }
        .no-medicament { color: #cbd5e1; font-style: italic; }

        /* Actions */
        .action-buttons { display: flex; gap: 8px; }
        .btn-action {
            width: 32px; height: 32px; border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            text-decoration: none; transition: 0.2s;
        }
        .btn-edit { background: var(--primary-light); color: var(--primary); }
        .btn-delete { background: #fff1f2; color: var(--danger); }
        .btn-action:hover { transform: scale(1.1); }

        /* Alerts */
        .alert {
            padding: 15px 20px; border-radius: 12px; margin-bottom: 25px;
            display: flex; align-items: center; gap: 12px;
        }
        .alert-success { background: #dcfce7; color: #166534; }
        .alert-error { background: #fee2e2; color: #991b1b; }

        /* Pagination */
        .pagination {
            display: flex; justify-content: center; gap: 8px; margin-top: 30px;
        }
        .pagination a, .pagination span {
            padding: 8px 14px; border-radius: 8px; text-decoration: none;
            font-weight: 600; font-size: 14px;
        }
        .pagination a { background: white; border: 1px solid #e2e8f0; color: var(--text-main); }
        .pagination .current { background: var(--primary); color: white; }
    </style>
</head>
<body>

<header>
    <img src="../images/logo_app2.png" style="height: 40px;" alt="MedCare Logo">
    <div class="user-pill">
        <i class="fa-solid fa-user-doctor"></i>
        <span>Espace Médical</span>
    </div>
</header>

<div class="container">
      <aside class="sidebar">
        <h3 style="font-weight: 800;">Unité de Soins</h3>
        <a href="../connexio_utilisateur/dashboard_medecin.php"><i class="fa-solid fa-chart-line"></i> Vue Générale</a>
        <a href="../connexio_utilisateur/hospitalisation.php"><i class="fa-solid fa-bed-pulse"></i> Patients Admis</a>
        <a href="../connexio_utilisateur/patients.php"><i class="fa-solid fa-hospital-user"></i> Patients</a>
        <a href="list.php" class="active"><i class="fa-solid fa-file-prescription"></i> Traitements</a>
        <a href="../connexio_utilisateur/suivis.php"><i class="fa-solid fa-calendar-check"></i> Consultations</a>
        <h3 style="font-weight: 800;">Analyse & Gestion</h3>
        <a href="../admission/statistique.php"><i class="fa-solid fa-chart-pie"></i> Statistiques</a>
        <a href="../connexio_utilisateur/archives.php"><i class="fa-solid fa-box-archive"></i> Archives</a>
        <a href="../connexio_utilisateur/profil_medcin.php"><i class="fa-solid fa-user-gear"></i> Profil</a>
        <div style="margin-top: 40px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 10px;">
            <a href="../connexio_utilisateur/deconnexion.php" style="color: #fda4af;"><i class="fa-solid fa-power-off"></i> Déconnexion</a>
        </div>
    </aside>
    <main class="main-content">
        <div class="page-header">
            <div class="page-title">
                <h1>Gestion des traitements</h1>
                <p>Suivi des prescriptions et médications</p>
            </div>
            <a href="add.php" class="btn-add">
                <i class="fas fa-plus"></i> Nouveau traitement
            </a>
        </div>

        <?php if($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?= htmlspecialchars($success) ?></span>
            </div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon icon-primary"><i class="fas fa-file-medical"></i></div>
                <div>
                    <h3 style="font-size: 24px; font-weight: 900;"><?= number_format($stats['total']) ?></h3>
                    <p style="color: var(--text-muted); font-size: 13px;">Traitements</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-secondary"><i class="fas fa-user-injured"></i></div>
                <div>
                    <h3 style="font-size: 24px; font-weight: 900;"><?= number_format($stats['patients']) ?></h3>
                    <p style="color: var(--text-muted); font-size: 13px;">Patients</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-success"><i class="fas fa-capsules"></i></div>
                <div>
                    <h3 style="font-size: 24px; font-weight: 900;"><?= $stats['total'] > 0 ? round(($stats['avec_medicament'] / $stats['total']) * 100) : 0 ?>%</h3>
                    <p style="color: var(--text-muted); font-size: 13px;">Médicamentés</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-warning"><i class="fas fa-calendar-day"></i></div>
                <div>
                    <h3 style="font-size: 24px; font-weight: 900;"><?= $stats['dernier_traitement'] ? date('d/m', strtotime($stats['dernier_traitement'])) : '--' ?></h3>
                    <p style="color: var(--text-muted); font-size: 13px;">Dernière mise à jour</p>
                </div>
            </div>
        </div>

        <div class="table-container">
            <div class="table-toolbar">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Rechercher...">
                </div>
                <div style="font-size: 13px; color: var(--text-muted);">
                    Total: <strong><?= number_format($total_traitements) ?></strong> enregistrements
                </div>
            </div>

            <?php if(count($traitements) > 0): ?>
                <table class="data-table" id="treatmentsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Patient</th>
                            <th>Description</th>
                            <th>Date</th>
                            <th>Médicament</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($traitements as $t): 
                            $initials = strtoupper(substr($t['nom'] ?? '', 0, 1) . substr($t['prenom'] ?? '', 0, 1));
                        ?>
                        <tr>
                            <td style="font-weight: 700; color: var(--text-muted);">#<?= str_pad($t['id_traitement'], 5, '0', STR_PAD_LEFT) ?></td>
                            <td>
                                <div class="patient-cell">
                                    <div class="patient-avatar"><?= $initials ?></div>
                                    <div class="patient-info">
                                        <div class="patient-name"><?= htmlspecialchars($t['nom'] . ' ' . $t['prenom']) ?></div>
                                        <div class="patient-cin"><?= htmlspecialchars($t['CIN'] ?? 'S/C') ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div style="color: var(--text-muted); font-size: 14px; max-width: 250px;" title="<?= htmlspecialchars($t['description']) ?>">
                                    <?= htmlspecialchars(mb_strimwidth($t['description'], 0, 60, '...')) ?>
                                </div>
                            </td>
                            <td><span class="date-badge"><?= date('d/m/Y', strtotime($t['date_traitement'])) ?></span></td>
                            <td>
                                <?php if(!empty($t['medicament'])): ?>
                                    <span class="medicament-tag"><i class="fas fa-pills" style="margin-right: 5px;"></i><?= htmlspecialchars(mb_strimwidth($t['medicament'], 0, 25, '...')) ?></span>
                                <?php else: ?>
                                    <span class="no-medicament">Aucun</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons" style="justify-content: flex-end;">
                                    <a href="edit.php?id=<?= $t['id_traitement'] ?>" class="btn-action btn-edit" title="Modifier"><i class="fas fa-edit"></i></a>
                                    <a href="delete.php?id=<?= $t['id_traitement'] ?>" class="btn-action btn-delete" onclick="return confirmDelete(<?= $t['id_traitement'] ?>)" title="Supprimer"><i class="fas fa-trash"></i></a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php if($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if($page > 1): ?>
                            <a href="?page=<?= $page - 1 ?>"><i class="fas fa-chevron-left"></i></a>
                        <?php endif; ?>
                        
                        <?php for($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <?php if($i == $page): ?>
                                <span class="current"><?= $i ?></span>
                            <?php else: ?>
                                <a href="?page=<?= $i ?>"><?= $i ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if($page < $total_pages): ?>
                            <a href="?page=<?= $page + 1 ?>"><i class="fas fa-chevron-right"></i></a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
            <?php else: ?>
                <div style="text-align: center; padding: 50px;">
                    <i class="fas fa-folder-open" style="font-size: 40px; color: #e2e8f0; margin-bottom: 15px;"></i>
                    <h3 style="color: var(--text-muted);">Aucun traitement trouvé</h3>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<script>
document.getElementById('searchInput').addEventListener('keyup', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('#treatmentsTable tbody tr');
    rows.forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(searchTerm) ? '' : 'none';
    });
});

function confirmDelete(id) {
    return confirm(`Supprimer le traitement #${id} ?`);
}
</script>

</body>
</html>