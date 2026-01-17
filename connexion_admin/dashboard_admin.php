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
$total_impayes = $pdo->query("SELECT SUM(montant_total) FROM factures WHERE statut_paiement = 'En attente'")->fetchColumn() ?? 0;
$total_staff = $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE role != 'admin'")->fetchColumn();

// --- 2. LOGIQUE DU GRAPHIQUE (Fixé de Janvier à Juin 2026) ---
$annee_actuelle = 2026;
$mois_labels = ["Jan", "Fév", "Mar", "Avr", "Mai", "Juin"];
$revenus_data = [];

// On boucle précisément sur les 6 premiers mois pour l'affichage
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
    $labels_mode[] = $m['mode_paiement'] ?: 'Non défini';
    $data_mode[] = $m['nb'];
}

// --- 4. TABLEAU DES DERNIÈRES FACTURES ---
$dernieres_factures = $pdo->query("
    SELECT f.*, p.nom, p.prenom 
    FROM factures f
    JOIN patients p ON f.id_patient = p.id_patient
    ORDER BY f.date_facture DESC LIMIT 6
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin | MedCare</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root { --primary: #0f766e; --primary-hover: #115e59; --sidebar-bg: #0f172a; --bg-body: #f8fafc; --white: #ffffff; --border: #e2e8f0; }
        body { background: var(--bg-body); font-family: 'Inter', sans-serif; margin: 0; }
        
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
        .sidebar a:hover, .sidebar a.active { background: var(--primary); color: white; shadow: 0 4px 12px rgba(15, 118, 110, 0.3); }
        
        .main-content { margin-left: 260px; padding: 40px; width: calc(100% - 260px); }
        .card-stats { border: none; border-radius: 15px; background: white; padding: 25px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); border-left: 5px solid var(--primary); transition: 0.3s; }
        .card-stats:hover { transform: translateY(-5px); }
        .table-card { background: white; border-radius: 20px; padding: 30px; border: 1px solid var(--border); box-shadow: 0 4px 12px rgba(0,0,0,0.02); }
        
        .badge-paye { background: #dcfce7; color: #15803d; padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .badge-attente { background: #fef3c7; color: #b45309; padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 600; }
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
        <a href="chambres.php"><i class="fa-solid fa-bed"></i> Gestion Chambres</a>
        <div style="height: 1px; background: rgba(255,255,255,0.1); margin: 20px 0;"></div>
        <a href="facturation_list.php"><i class="fa-solid fa-file-invoice-dollar"></i> Rapports Financiers</a>
        <a href="profil.php" class="<?= basename($_SERVER['PHP_SELF']) == 'profil.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-user-gear"></i> Mon Profil
        </a>
        <a href="../logout.php" style="color: #fda4af; margin-top: 20px;"><i class="fa-solid fa-power-off"></i> Déconnexion</a>
    </aside>

    <main class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-5">
            <div>
                <h2 class="fw-bold mb-1">Rapport d'Activité</h2>
                <p class="text-muted">Semestre 1 - Année 2026</p>
            </div>
            <button class="btn btn-primary rounded-pill px-4 py-2" onclick="location.reload()">
                <i class="fa-solid fa-rotate me-2"></i> Actualiser
            </button>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-md-3">
                <div class="card-stats">
                    <small class="text-muted text-uppercase fw-bold">Revenus Payés</small>
                    <h3 class="fw-bold mt-2 mb-0"><?= number_format($revenu_total, 2) ?> DH</h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card-stats" style="border-left-color: #0ea5e9;">
                    <small class="text-muted text-uppercase fw-bold">Hospitalisations</small>
                    <h3 class="fw-bold mt-2 mb-0"><?= $patients_admis ?> Patients</h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card-stats" style="border-left-color: #f59e0b;">
                    <small class="text-muted text-uppercase fw-bold">En Attente</small>
                    <h3 class="fw-bold mt-2 mb-0 text-warning"><?= number_format($total_impayes, 2) ?> DH</h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card-stats" style="border-left-color: #8b5cf6;">
                    <small class="text-muted text-uppercase fw-bold">Effectif Staff</small>
                    <h3 class="fw-bold mt-2 mb-0"><?= $total_staff ?> Membres</h3>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-md-8">
                <div class="table-card h-100">
                    <h6 class="fw-bold mb-4">Flux Financier (Janv - Juin 2026)</h6>
                    <canvas id="revChart" height="130"></canvas>
                </div>
            </div>
            <div class="col-md-4">
                <div class="table-card h-100">
                    <h6 class="fw-bold mb-4">Modes de Règlement</h6>
                    <canvas id="modeChart"></canvas>
                </div>
            </div>
        </div>

        <div class="table-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h6 class="fw-bold mb-0">Dernières Opérations de Facturation</h6>
                <a href="facturation_list.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3">Tout voir</a>
            </div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="text-muted small">
                        <tr>
                            <th>NUMÉRO</th>
                            <th>NOM DU PATIENT</th>
                            <th>DATE ÉMISSION</th>
                            <th>MONTANT TOTAL</th>
                            <th>ÉTAT DU PAIEMENT</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($dernieres_factures as $f): ?>
                        <tr>
                            <td class="fw-bold text-primary">#<?= $f['numero_facture'] ?></td>
                            <td class="fw-semibold"><?= htmlspecialchars($f['prenom'].' '.$f['nom']) ?></td>
                            <td class="text-muted small"><?= date('d/m/Y', strtotime($f['date_facture'])) ?></td>
                            <td class="fw-bold"><?= number_format($f['montant_total'], 2) ?> DH</td>
                            <td>
                                <span class="<?= $f['statut_paiement'] == 'Payé' ? 'badge-paye' : 'badge-attente' ?>">
                                    <i class="fa-solid fa-circle me-1" style="font-size: 8px;"></i> <?= $f['statut_paiement'] ?>
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
// Chart : Revenus (Fixé Janvier à Juin)
new Chart(document.getElementById('revChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($mois_labels) ?>,
        datasets: [{
            label: 'DH encaissés',
            data: <?= json_encode($revenus_data) ?>,
            borderColor: '#0f766e',
            backgroundColor: 'rgba(15, 118, 110, 0.1)',
            tension: 0.4,
            fill: true,
            pointRadius: 6,
            pointBackgroundColor: '#0f766e'
        }]
    },
    options: { 
        plugins: { legend: { display: false } }, 
        scales: { 
            y: { beginAtZero: true, ticks: { callback: value => value + ' DH' } } 
        } 
    }
});

// Chart : Modes
new Chart(document.getElementById('modeChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($labels_mode) ?>,
        datasets: [{
            data: <?= json_encode($data_mode) ?>,
            backgroundColor: ['#0f766e', '#0ea5e9', '#f59e0b', '#8b5cf6', '#ec4899']
        }]
    },
    options: { cutout: '70%', plugins: { legend: { position: 'bottom' } } }
});
</script>

</body>
</html>