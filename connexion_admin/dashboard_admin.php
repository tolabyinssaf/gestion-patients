<?php
session_start();
include("../config/connexion.php");

// Sécurité : Vérification Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { 
    header("Location: ../login.php"); 
    exit; 
}

// --- 1. STATISTIQUES EN TEMPS RÉEL ---
$revenu_total = $pdo->query("SELECT SUM(montant_total) FROM factures WHERE statut_paiement = 'Payé'")->fetchColumn() ?? 0;
$patients_admis = $pdo->query("SELECT COUNT(*) FROM admissions WHERE date_sortie IS NULL")->fetchColumn();
// Utilisation de LIKE pour être plus souple sur la casse
$total_impayes = $pdo->query("SELECT SUM(montant_total) FROM factures WHERE statut_paiement LIKE 'En attente'")->fetchColumn() ?? 0;
$total_staff = $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE role != 'admin'")->fetchColumn();

// --- 2. LOGIQUE DU GRAPHIQUE (Fixé de Janvier à Juin 2026) ---
$annee_actuelle = 2026;
$mois_labels = ["Jan", "Fév", "Mar", "Avr", "Mai", "Juin"];
$revenus_data = [];

for ($m = 1; $m <= 6; $m++) {
    $mois_format = str_pad($m, 2, "0", STR_PAD_LEFT);
    $date_recherche = $annee_actuelle . "-" . $mois_format;

    $sql = "SELECT SUM(montant_total) FROM factures 
            WHERE DATE_FORMAT(date_facture, '%Y-%m') = :date_cible 
            AND statut_paiement = 'Payé'";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['date_cible' => $date_recherche]);
    $total_mois = $stmt->fetchColumn() ?? 0;

    $revenus_data[] = (float)$total_mois;
}

// --- 3. RÉPARTITION DES MODES DE PAIEMENT ---
$modes = $pdo->query("SELECT mode_paiement, COUNT(*) as nb FROM factures GROUP BY mode_paiement")->fetchAll(PDO::FETCH_ASSOC);
$labels_mode = []; $data_mode = [];
foreach($modes as $m) {
    $labels_mode[] = $m['mode_paiement'] ?: 'par carte';
    $data_mode[] = $m['nb'];
}

// --- 4. TABLEAU DES DERNIÈRES FACTURES ---
$dernieres_factures = $pdo->query("
    SELECT f.*, p.nom, p.prenom 
    FROM factures f
    LEFT JOIN patients p ON f.id_patient = p.id_patient
    ORDER BY f.date_facture DESC LIMIT 6
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin | MedCare</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root { 
            --primary: #0f766e; 
            --primary-hover: #115e59; 
            --sidebar-bg: #0f172a; 
            --bg-body: #f1f5f9; 
            --white: #ffffff; 
            --border: #e2e8f0; 
            --card-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.04), 0 4px 6px -2px rgba(0, 0, 0, 0.02);
        }
        
        body { background: var(--bg-body); font-family: 'Plus Jakarta Sans', sans-serif; margin: 0; color: #1e293b; }
        
        /* Garder Header et Sidebar inchangés */
        header { 
            background: var(--white); padding: 0 40px; height: 75px; 
            display: flex; justify-content: space-between; align-items: center; 
            border-bottom: 1px solid var(--border); position: fixed; width: 100%; top: 0; z-index: 1000;
        }
        .user-pill { background: #f0fdfa; padding: 8px 18px; border-radius: 12px; color: var(--primary); font-weight: 600; display: flex; align-items: center; gap: 10px; }

        .wrapper { display: flex; padding-top: 75px; }
        .sidebar { 
            width: 260px; background: var(--sidebar-bg); height: calc(100vh - 75px); 
            position: fixed; padding: 24px 16px; flex-shrink: 0; 
        }
        .sidebar a { 
            display: flex; align-items: center; gap: 12px; color: #94a3b8; 
            text-decoration: none; padding: 12px 16px; border-radius: 10px; margin-bottom: 5px; transition: 0.3s; 
        }
        .sidebar a:hover, .sidebar a.active { background: var(--primary); color: white; box-shadow: 0 4px 12px rgba(15, 118, 110, 0.3); }
        
        /* --- MODERNISATION DU CENTRE --- */
        .main-content { margin-left: 260px; padding: 40px; width: calc(100% - 260px); }

        .dashboard-header h2 { font-weight: 800; letter-spacing: -0.5px; color: #0f172a; }

        /* Cartes de statistiques améliorées */
        .card-stats { 
            border: none; 
            border-radius: 20px; 
            background: white; 
            padding: 30px; 
            box-shadow: var(--card-shadow); 
            border-bottom: 4px solid var(--primary); 
            transition: all 0.3s ease;
            height: 100%;
        }
        .card-stats:hover { transform: translateY(-5px); box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.08); }
        .card-stats small { font-weight: 700; color: #64748b; text-transform: uppercase; font-size: 11px; letter-spacing: 1px; }
        .card-stats h3 { font-weight: 800; color: #0f172a; margin-top: 8px; }

        /* Zones Graphiques */
        .chart-card { 
            background: white; 
            border-radius: 24px; 
            padding: 25px; 
            border: 1px solid rgba(226, 232, 240, 0.6); 
            box-shadow: var(--card-shadow);
        }
        .chart-title { font-weight: 700; color: #0f172a; margin-bottom: 20px; font-size: 1rem; }

        /* Tableau Modernisé */
        .table-card { 
            background: white; 
            border-radius: 24px; 
            padding: 30px; 
            border: none; 
            box-shadow: var(--card-shadow); 
        }
        .table thead th { 
            background: #f8fafc; 
            border: none; 
            color: #64748b; 
            font-weight: 700; 
            text-transform: uppercase; 
            font-size: 11px; 
            padding: 15px;
        }
        .table tbody td { padding: 18px 15px; border-bottom: 1px solid #f1f5f9; color: #334155; }
        
        .badge-paye { background: #dcfce7; color: #15803d; padding: 6px 14px; border-radius: 10px; font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; }
        .badge-attente { background: #fef3c7; color: #b45309; padding: 6px 14px; border-radius: 10px; font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; }
        
        .btn-modern {
            background: var(--primary); border: none; border-radius: 12px; padding: 10px 24px; font-weight: 600; transition: 0.3s;
        }
        .btn-modern:hover { background: var(--primary-hover); transform: scale(1.02); }
    </style>
</head>
<body>

<header>
    <img src="../images/logo_app2.png" alt="Logo" style="height: 45px;">
    <div class="user-pill">
        <i class="fa-solid fa-user-shield"></i>
        <span>ADMIN : <?= strtoupper($_SESSION['role']) ?></span>
    </div>
</header>

<div class="wrapper">
    <aside class="sidebar">
        <a href="dashboard_admin.php" class="active"><i class="fa-solid fa-chart-pie"></i> Vue Générale</a>
        <a href="utilisateurs.php"><i class="fa-solid fa-user-md"></i> Utilisateurs</a>
        <a href="prestations.php"><i class="fa-solid fa-list-check"></i> Actes & Tarifs</a>
        <a href="../chambres/gestion_chambres.php"><i class="fa-solid fa-bed"></i> Gestion Chambres</a>
        <div style="height: 1px; background: rgba(255,255,255,0.1); margin: 20px 0;"></div>
        <a href="facturation_list.php"><i class="fa-solid fa-file-invoice-dollar"></i> Rapports Financiers</a>
        <a href="archives.php"><i class="fa-solid fa-box-archive"></i> Archives</a>
        <a href="profil.php" class="<?= basename($_SERVER['PHP_SELF']) == 'profil.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-user-gear"></i> Mon Profil
        </a>
        <a href="../connexio_utilisateur/login.php" style="color: #fda4af; margin-top: 20px;"><i class="fa-solid fa-power-off"></i> Déconnexion</a>
    </aside>

    <main class="main-content">
        <div class="dashboard-header d-flex justify-content-between align-items-end mb-5">
            <div>
                <h2 class="mb-1">Tableau de Bord</h2>
                <p class="text-muted mb-0">Rapport financier • Semestre 1 - 2026</p>
            </div>
            <button class="btn btn-modern text-white shadow-sm" onclick="location.reload()">
                <i class="fa-solid fa-arrows-rotate me-2"></i> Actualiser les données
            </button>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-md-3">
                <div class="card-stats">
                    <small>Revenus Encaissés</small>
                    <h3><?= number_format($revenu_total, 2) ?> <span style="font-size: 0.9rem; opacity: 0.6;">DH</span></h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card-stats" style="border-left-color: #0ea5e9; border-bottom-color: #0ea5e9;">
                    <small>Hospitalisations</small>
                    <h3><?= $patients_admis ?> <span style="font-size: 0.9rem; opacity: 0.6;">PATIENTS</span></h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card-stats" style="border-left-color: #f59e0b; border-bottom-color: #f59e0b;">
                    <small>Montant en Attente</small>
                    <h3 class="text-warning"><?= number_format($total_impayes, 2) ?> <span style="font-size: 0.9rem; color:#0f172a; opacity: 0.6;">DH</span></h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card-stats" style="border-left-color: #8b5cf6; border-bottom-color: #8b5cf6;">
                    <small>Staff Actif</small>
                    <h3><?= $total_staff ?> <span style="font-size: 0.9rem; opacity: 0.6;">MEMBRES</span></h3>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-md-8">
                <div class="chart-card h-100">
                    <h6 class="chart-title">Évolution du Flux Financier (Jan - Juin)</h6>
                    <canvas id="revChart" height="130"></canvas>
                </div>
            </div>
            <div class="col-md-4">
                <div class="chart-card h-100">
                    <h6 class="chart-title">Répartition Paiements</h6>
                    <canvas id="modeChart"></canvas>
                </div>
            </div>
        </div>

        <div class="table-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h6 class="fw-bold mb-0">Dernières Opérations</h6>
                <a href="facturation_list.php" class="btn btn-sm btn-light rounded-pill px-3 fw-bold text-muted">Voir l'historique</a>
            </div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Référence</th>
                            <th>Patient</th>
                            <th>Date d'émission</th>
                            <th>Montant</th>
                            <th>État</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($dernieres_factures as $f): ?>
                        <tr>
                            <td class="fw-bold text-primary">#<?= $f['numero_facture'] ?></td>
                            <td class="fw-semibold text-dark">
    <?= htmlspecialchars(($f['prenom'] ?? '—') . ' ' . ($f['nom'] ?? '—')) ?>
</td>

                            <td class="text-muted small"><?= date('d M Y', strtotime($f['date_facture'])) ?></td>
                            <td class="fw-bold text-dark"><?= number_format($f['montant_total'], 2) ?> DH</td>
                            <td>
                                <span class="<?= $f['statut_paiement'] == 'Payé' ? 'badge-paye' : 'badge-attente' ?>">
                                    <i class="fa-solid fa-circle me-2" style="font-size: 6px;"></i> <?= $f['statut_paiement'] ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<script>
// Configuration du graphique linéaire
new Chart(document.getElementById('revChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($mois_labels) ?>,
        datasets: [{
            label: 'Revenus (DH)',
            data: <?= json_encode($revenus_data) ?>,
            borderColor: '#0f766e',
            backgroundColor: 'rgba(15, 118, 110, 0.08)',
            borderWidth: 3,
            tension: 0.4,
            fill: true,
            pointRadius: 4,
            pointBackgroundColor: '#fff',
            pointBorderColor: '#0f766e',
            pointBorderWidth: 2
        }]
    },
    options: { 
        plugins: { legend: { display: false } }, 
        scales: { 
            y: { grid: { borderDash: [5, 5] }, ticks: { font: { size: 11 } } },
            x: { grid: { display: false } }
        } 
    }
});

// Configuration du graphique en anneau
new Chart(document.getElementById('modeChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($labels_mode) ?>,
        datasets: [{
            data: <?= json_encode($data_mode) ?>,
            backgroundColor: ['#0f766e', '#0ea5e9', '#f59e0b', '#8b5cf6', '#ec4899'],
            hoverOffset: 10,
            borderWidth: 0
        }]
    },
    options: { 
        cutout: '75%', 
        plugins: { 
            legend: { 
                position: 'bottom',
                labels: { padding: 20, font: { size: 11, weight: '600' }, usePointStyle: true }
            } 
        } 
    }
});
</script>

</body>
</html>