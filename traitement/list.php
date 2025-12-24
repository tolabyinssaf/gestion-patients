<?php
require_once '../config/connexion.php';

$traitements = $pdo->query("SELECT t.*, p.nom, p.prenom FROM traitements t JOIN patients p ON t.id_patient = p.id_patient ORDER BY t.date_traitement DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Traitements | Système Médical</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #01A28C;
            --primary-dark: #01796B;
            --primary-light: #E8F6F3;
            --secondary-color: #2C3E50;
            --accent-color: #3498DB;
            --danger-color: #E74C3C;
            --warning-color: #F39C12;
            --success-color: #2ECC71;
            --light-bg: #F8F9FA;
            --border-color: #E1E8ED;
            --text-primary: #2C3E50;
            --text-secondary: #7F8C8D;
            --text-light: #95A5A6;
            --white: #FFFFFF;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --shadow-hover: 0 8px 24px rgba(0, 0, 0, 0.12);
            --radius: 12px;
            --radius-sm: 8px;
            --transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%);
            color: var(--text-primary);
            line-height: 1.6;
            min-height: 100vh;
            padding: 20px;
        }

        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Header */
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 25px;
            background: var(--white);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            border-left: 5px solid var(--primary-color);
        }

        .header-content h1 {
            font-size: 28px;
            font-weight: 700;
            color: var(--secondary-color);
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header-content h1 i {
            color: var(--primary-color);
            background: var(--primary-light);
            padding: 10px;
            border-radius: 10px;
        }

        .header-content p {
            color: var(--text-secondary);
            font-size: 15px;
        }

        .header-actions {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: var(--white);
            padding: 14px 24px;
            border-radius: var(--radius-sm);
            font-weight: 600;
            font-size: 15px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: var(--transition);
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(1, 162, 140, 0.25);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(1, 162, 140, 0.35);
        }

        .btn-secondary {
            background: var(--white);
            color: var(--primary-color);
            padding: 12px 20px;
            border-radius: var(--radius-sm);
            font-weight: 500;
            font-size: 14px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
            border: 1.5px solid var(--primary-color);
        }

        .btn-secondary:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--white);
            border-radius: var(--radius);
            padding: 25px;
            box-shadow: var(--shadow);
            transition: var(--transition);
            border-top: 4px solid var(--primary-color);
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: var(--white);
            flex-shrink: 0;
        }

        .stat-icon.primary { background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)); }
        .stat-icon.accent { background: linear-gradient(135deg, var(--accent-color), #2980B9); }
        .stat-icon.warning { background: linear-gradient(135deg, var(--warning-color), #E67E22); }

        .stat-content h3 {
            font-size: 32px;
            font-weight: 700;
            color: var(--secondary-color);
            margin-bottom: 5px;
        }

        .stat-content p {
            color: var(--text-secondary);
            font-size: 14px;
            font-weight: 500;
        }

        /* Table Container */
        .table-wrapper {
            background: var(--white);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
        }

        .table-header {
            padding: 25px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table-header h3 {
            font-size: 20px;
            font-weight: 600;
            color: var(--secondary-color);
        }

        .table-actions {
            display: flex;
            gap: 10px;
        }

        .search-box {
            position: relative;
            width: 300px;
        }

        .search-box input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 1.5px solid var(--border-color);
            border-radius: var(--radius-sm);
            font-size: 14px;
            transition: var(--transition);
            background: var(--light-bg);
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(1, 162, 140, 0.1);
        }

        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
        }

        /* Table Styling */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table thead {
            background: var(--primary-light);
            border-bottom: 2px solid var(--border-color);
        }

        .data-table th {
            padding: 18px 20px;
            text-align: left;
            font-weight: 600;
            color: var(--secondary-color);
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid var(--border-color);
        }

        .data-table tbody tr {
            border-bottom: 1px solid var(--border-color);
            transition: var(--transition);
        }

        .data-table tbody tr:hover {
            background: var(--primary-light);
            transform: scale(1.01);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .data-table td {
            padding: 20px;
            vertical-align: middle;
            color: var(--text-primary);
        }

        /* Patient Cell */
        .patient-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .patient-avatar {
            width: 42px;
            height: 42px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-weight: 600;
            font-size: 16px;
            flex-shrink: 0;
        }

        .patient-info h4 {
            font-weight: 600;
            font-size: 15px;
            color: var(--secondary-color);
            margin-bottom: 3px;
        }

        .patient-info p {
            font-size: 13px;
            color: var(--text-secondary);
        }

        /* Description & Suivi Cells */
        .description-cell, .suivi-cell {
            max-width: 280px;
            line-height: 1.5;
        }

        .description-cell p, .suivi-cell p {
            margin-bottom: 5px;
            font-size: 14px;
        }

        .truncate-text {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .show-more {
            color: var(--primary-color);
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            margin-top: 5px;
        }

        /* Date Cell */
        .date-cell {
            white-space: nowrap;
        }

        .date-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--light-bg);
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            color: var(--text-primary);
        }

        /* Medicament Cell */
        .medicament-cell {
            position: relative;
        }

        .medicament-tag {
            display: inline-block;
            background: var(--primary-light);
            color: var(--primary-dark);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            border: 1px solid rgba(1, 162, 140, 0.2);
            max-width: 180px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .no-medicament {
            color: var(--text-light);
            font-style: italic;
            font-size: 13px;
        }

        /* Actions Cell */
        .actions-cell {
            width: 140px;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .action-btn {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: var(--transition);
            border: none;
            cursor: pointer;
            font-size: 14px;
        }

        .action-edit {
            background: rgba(52, 152, 219, 0.1);
            color: var(--accent-color);
            border: 1px solid rgba(52, 152, 219, 0.3);
        }

        .action-edit:hover {
            background: var(--accent-color);
            color: var(--white);
            transform: translateY(-3px);
        }

        .action-delete {
            background: rgba(231, 76, 60, 0.1);
            color: var(--danger-color);
            border: 1px solid rgba(231, 76, 60, 0.3);
        }

        .action-delete:hover {
            background: var(--danger-color);
            color: var(--white);
            transform: translateY(-3px);
        }

        /* Empty State */
        .empty-state {
            padding: 60px 20px;
            text-align: center;
        }

        .empty-state i {
            font-size: 72px;
            color: var(--border-color);
            margin-bottom: 20px;
        }

        .empty-state h3 {
            font-size: 22px;
            color: var(--text-secondary);
            margin-bottom: 10px;
            font-weight: 600;
        }

        .empty-state p {
            color: var(--text-light);
            max-width: 500px;
            margin: 0 auto 30px;
            line-height: 1.6;
        }

        /* Footer */
        .table-footer {
            padding: 20px 25px;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--light-bg);
        }

        .pagination {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .page-btn {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--white);
            color: var(--text-primary);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            border: 1px solid var(--border-color);
        }

        .page-btn.active {
            background: var(--primary-color);
            color: var(--white);
            border-color: var(--primary-color);
        }

        .page-btn:hover:not(.active) {
            background: var(--primary-light);
            border-color: var(--primary-color);
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .data-table {
                display: block;
                overflow-x: auto;
            }
            
            .search-box {
                width: 250px;
            }
        }

        @media (max-width: 768px) {
            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 20px;
            }
            
            .header-actions {
                width: 100%;
                justify-content: space-between;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .table-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .search-box {
                width: 100%;
            }
            
            .table-actions {
                width: 100%;
                justify-content: space-between;
            }
        }

        @media (max-width: 480px) {
            .header-actions {
                flex-direction: column;
                width: 100%;
            }
            
            .btn-primary, .btn-secondary {
                width: 100%;
                justify-content: center;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Header -->
        <header class="dashboard-header">
            <div class="header-content">
                <h1><i class="fas fa-stethoscope"></i> Gestion des Traitements</h1>
                <p>Visualisez et gérez tous les traitements médicaux enregistrés</p>
            </div>
            <div class="header-actions">
                <a href="dashboard.php" class="btn-secondary">
                    <i class="fas fa-arrow-left"></i> Tableau de bord
                </a>
                <a href="add.php" class="btn-primary">
                    <i class="fas fa-plus-circle"></i> Nouveau traitement
                </a>
            </div>
        </header>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <?php
            // Compter les patients uniques
            $patients_ids = [];
            $patients_with_medicament = 0;
            $latest_date = '';
            
            foreach($traitements as $t) {
                $patients_ids[$t['id_patient']] = true;
                if (!empty($t['medicament'])) {
                    $patients_with_medicament++;
                }
                if ($t['date_traitement'] > $latest_date || $latest_date == '') {
                    $latest_date = $t['date_traitement'];
                }
            }
            
            $total_traitements = count($traitements);
            $total_patients = count($patients_ids);
            $percentage_with_medicament = $total_traitements > 0 ? round(($patients_with_medicament / $total_traitements) * 100) : 0;
            ?>
            
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="fas fa-prescription-bottle-alt"></i>
                </div>
                <div class="stat-content">
                    <h3><?= $total_traitements ?></h3>
                    <p>Traitements enregistrés</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon accent">
                    <i class="fas fa-user-injured"></i>
                </div>
                <div class="stat-content">
                    <h3><?= $total_patients ?></h3>
                    <p>Patients traités</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon warning">
                    <i class="fas fa-pills"></i>
                </div>
                <div class="stat-content">
                    <h3><?= $percentage_with_medicament ?>%</h3>
                    <p>Avec médicament prescrit</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-content">
                    <h3><?= $latest_date ? date('d/m', strtotime($latest_date)) : '--' ?></h3>
                    <p>Dernier traitement</p>
                </div>
            </div>
        </div>

        <!-- Table Section -->
        <div class="table-wrapper">
            <div class="table-header">
                <h3>Tous les traitements</h3>
                <div class="table-actions">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Rechercher un traitement ou un patient...">
                    </div>
                    <button class="btn-secondary" id="filterBtn">
                        <i class="fas fa-filter"></i> Filtrer
                    </button>
                </div>
            </div>

            <?php if (count($traitements) > 0): ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Patient</th>
                                <th>Description</th>
                                <th>Date</th>
                                <th>Médicament</th>
                                <th>Suivi</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="treatmentsTable">
                            <?php foreach($traitements as $t): 
                                $initials = substr($t['nom'], 0, 1) . substr($t['prenom'], 0, 1);
                                $date_formatted = date('d/m/Y', strtotime($t['date_traitement']));
                                $description_short = mb_strlen($t['description']) > 120 ? mb_substr($t['description'], 0, 120) . '...' : $t['description'];
                                $suivi_short = $t['suivi'] ? (mb_strlen($t['suivi']) > 100 ? mb_substr($t['suivi'], 0, 100) . '...' : $t['suivi']) : '';
                            ?>
                            <tr>
                                <td><strong>#<?= str_pad($t['id_traitement'], 4, '0', STR_PAD_LEFT) ?></strong></td>
                                <td>
                                    <div class="patient-cell">
                                        <div class="patient-avatar"><?= strtoupper($initials) ?></div>
                                        <div class="patient-info">
                                            <h4><?= htmlspecialchars($t['nom'] . ' ' . $t['prenom']) ?></h4>
                                            <p>ID: <?= $t['id_patient'] ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="description-cell">
                                    <p class="truncate-text"><?= htmlspecialchars($description_short) ?></p>
                                    <?php if(mb_strlen($t['description']) > 120): ?>
                                    <a href="#" class="show-more" data-fulltext="<?= htmlspecialchars($t['description']) ?>">
                                        <span>Voir plus</span> <i class="fas fa-chevron-right"></i>
                                    </a>
                                    <?php endif; ?>
                                </td>
                                <td class="date-cell">
                                    <span class="date-badge">
                                        <i class="far fa-calendar-alt"></i>
                                        <?= $date_formatted ?>
                                    </span>
                                </td>
                                <td class="medicament-cell">
                                    <?php if($t['medicament']): ?>
                                    <span class="medicament-tag" title="<?= htmlspecialchars($t['medicament']) ?>">
                                        <i class="fas fa-pills"></i> <?= htmlspecialchars($t['medicament']) ?>
                                    </span>
                                    <?php else: ?>
                                    <span class="no-medicament">Aucun médicament</span>
                                    <?php endif; ?>
                                </td>
                                <td class="suivi-cell">
                                    <?php if($t['suivi']): ?>
                                    <p class="truncate-text"><?= htmlspecialchars($suivi_short) ?></p>
                                    <?php if(mb_strlen($t['suivi']) > 100): ?>
                                    <a href="#" class="show-more" data-fulltext="<?= htmlspecialchars($t['suivi']) ?>">
                                        <span>Voir plus</span> <i class="fas fa-chevron-right"></i>
                                    </a>
                                    <?php endif; ?>
                                    <?php else: ?>
                                    <span class="no-medicament">Aucun suivi</span>
                                    <?php endif; ?>
                                </td>
                                <td class="actions-cell">
                                    <div class="action-buttons">
                                        <a href="edit.php?id=<?= $t['id_traitement'] ?>" class="action-btn action-edit" title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="delete.php?id=<?= $t['id_traitement'] ?>" 
                                           class="action-btn action-delete" 
                                           title="Supprimer"
                                           onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce traitement ? Cette action est irréversible.')">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="table-footer">
                    <div class="table-info">
                        <span>Affichage de <?= count($traitements) ?> traitement(s)</span>
                    </div>
                    <div class="pagination">
                        <a href="#" class="page-btn active">1</a>
                        <a href="#" class="page-btn">2</a>
                        <a href="#" class="page-btn">3</a>
                        <a href="#" class="page-btn"><i class="fas fa-chevron-right"></i></a>
                    </div>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-file-medical-alt"></i>
                    <h3>Aucun traitement enregistré</h3>
                    <p>Commencez par ajouter votre premier traitement médical. Les traitements vous permettent de suivre les soins apportés à vos patients.</p>
                    <a href="add.php" class="btn-primary">
                        <i class="fas fa-plus-circle"></i> Ajouter un traitement
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#treatmentsTable tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });

        // Show more functionality for truncated text
        document.addEventListener('click', function(e) {
            if (e.target.closest('.show-more')) {
                e.preventDefault();
                const link = e.target.closest('.show-more');
                const fullText = link.getAttribute('data-fulltext');
                
                // Create modal or alert with full text
                alert(fullText);
            }
        });

        // Row hover effects
        const tableRows = document.querySelectorAll('.data-table tbody tr');
        tableRows.forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.transform = 'scale(1.01)';
            });
            
            row.addEventListener('mouseleave', function() {
                this.style.transform = 'scale(1)';
            });
        });

        // Filter button
        document.getElementById('filterBtn').addEventListener('click', function() {
            alert('Fonctionnalité de filtrage - À implémenter');
        });

        // Animation on load
        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.querySelectorAll('.data-table tbody tr');
            rows.forEach((row, index) => {
                row.style.opacity = '0';
                row.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    row.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    row.style.opacity = '1';
                    row.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>