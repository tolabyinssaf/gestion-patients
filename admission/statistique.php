<?php
include "../config/connexion.php";

try {
    // Garder exactement votre logique PHP existante
    $kpi = $pdo->query("SELECT * FROM vw_admission_kpi")->fetch(PDO::FETCH_ASSOC);
    $evo_data = $pdo->query("SELECT DATE_FORMAT(date_admission, '%b') as mois, COUNT(*) as total 
                             FROM admissions GROUP BY MONTH(date_admission) ORDER BY MONTH(date_admission)")->fetchAll(PDO::FETCH_ASSOC);
    $services = $pdo->query("SELECT service, total FROM v_top_services ORDER BY total DESC")->fetchAll(PDO::FETCH_ASSOC);
    $age = $pdo->query("SELECT * FROM v_admissions_age LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $types = $pdo->query("SELECT type_admission, total FROM v_admissions_type")->fetchAll(PDO::FETCH_KEY_PAIR);
    $chambres = $pdo->query("SELECT etat, COUNT(*) as nb FROM chambres GROUP BY etat")->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) { die("Erreur de base de données : " . $e->getMessage()); }
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Statistiques | MedCare Pro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #0f766e;
            --sidebar-bg: #0f172a;
            --bg-body: #f8fafc;
            --header-bg: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --card-bg: #ffffff;
            --border: #e2e8f0;
        }

        /* Mode Sombre - S'active via la classe .dark sur le body */
        body.dark {
            --bg-body: #020617;
            --header-bg: #0f172a;
            --text-main: #f1f5f9;
            --text-muted: #94a3b8;
            --card-bg: #1e293b;
            --border: #334155;
        }

        body { font-family: 'Inter', sans-serif; background: var(--bg-body); color: var(--text-main); transition: all 0.3s ease; }

        /* HEADER */
        header {
            background: var(--header-bg);
            padding: 0 40px;
            height: 75px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border);
            position: fixed;
            top: 0; left: 0; right: 0; 
            z-index: 1000;
        }

        /* SIDEBAR FONCÉE */
        .sidebar { 
            width: 260px; 
            background: var(--sidebar-bg); 
            padding: 24px 16px; 
            position: fixed; 
            top: 75px; left: 0; bottom: 0; 
            z-index: 900;
        }
        .sidebar a {
            display: flex; align-items: center; gap: 12px;
            color: #94a3b8; text-decoration: none;
            padding: 12px 16px; border-radius: 10px;
            margin-bottom: 5px; transition: 0.2s;
        }
        .sidebar a:hover, .sidebar a.active { background: rgba(255,255,255,0.1); color: #fff; }
        .sidebar a.active { background: var(--primary); }

        /* MAIN CONTENT */
        .content { margin-left: 260px; padding: 115px 40px 40px; }
        .glass-card { background: var(--card-bg); border: 1px solid var(--border); border-radius: 20px; }

        /* NAVIGATION TABS */
        .tab-btn { color: var(--text-muted); padding-bottom: 8px; border-bottom: 2px solid transparent; transition: 0.3s; }
        .tab-btn.active { color: var(--primary); border-color: var(--primary); font-weight: 700; }
        
        .tab-content { display: none; }
        .tab-content.active { display: block; animation: fadeIn 0.4s ease-out; }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>

<header>
    <img src="../images/logo_app2.png" alt="Logo" class="h-10">
    <div class="flex items-center gap-6">
        <button onclick="toggleDarkMode()" class="text-slate-500 hover:text-teal-600 transition-colors">
            <i id="theme-icon" class="fa-solid fa-moon text-xl"></i>
        </button>
        <div class="flex items-center gap-3 border-l pl-6 border-slate-200">
            <span class="text-xs font-bold px-3 py-1 bg-emerald-500/10 text-emerald-500 rounded-full">Live DB</span>
            <div class="w-8 h-8 rounded-full bg-teal-600 flex items-center justify-center text-white text-xs font-bold">DR</div>
        </div>
    </div>
</header>

<div class="flex">
    <aside class="sidebar">
        <h3 style="font-weight: 800;">Unité de Soins</h3>
        <a href="../connexio_utilisateur/dashboard_medecin.php"><i class="fa-solid fa-chart-line"></i> Vue Générale</a>
        <a href="../connexio_utilisateur/hospitalisation.php"><i class="fa-solid fa-bed-pulse"></i> Patients Admis</a>
        <a href="../connexio_utilisateur/patients.php"><i class="fa-solid fa-hospital-user"></i> Patients</a>
        <a href="../traitement/list.php"><i class="fa-solid fa-file-prescription"></i> Traitements</a>
        <a href="../connexio_utilisateur/suivis.php"><i class="fa-solid fa-calendar-check"></i> Consultations</a>
        <h3 style="font-weight: 800;">Analyse & Gestion</h3>
        <a href="../admission/statistique.php" class="active"><i class="fa-solid fa-chart-pie"></i> Statistiques</a>
        <a href="../connexio_utilisateur/archives.php"><i class="fa-solid fa-box-archive"></i> Archives</a>
        <a href="../connexio_utilisateur/profil_medcin.php"><i class="fa-solid fa-user-gear"></i> Profil</a>
        <div style="margin-top: 40px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 10px;">
            <a href="../connexio_utilisateur/deconnexion.php" style="color: #fda4af;"><i class="fa-solid fa-power-off"></i> Déconnexion</a>
        </div>
    </aside>

    <main class="content w-full">
        <div class="flex justify-between items-end mb-10">
            <div>
                <h1 class="text-3xl font-black" id="title-main">Analytique Globale</h1>
                <p class="text-sm text-slate-500 mt-1">Données consolidées de l'unité de soins</p>
            </div>
            <div class="flex gap-8">
                <button onclick="switchTab('global')" id="btn-global" class="tab-btn active">Vue d'ensemble</button>
                <button onclick="switchTab('patients')" id="btn-patients" class="tab-btn">Démographie</button>
                <button onclick="switchTab('logistics')" id="btn-logistics" class="tab-btn">Ressources</button>
            </div>
        </div>

        <div id="tab-global" class="tab-content active space-y-8">
            <div class="grid grid-cols-4 gap-6">
                <div class="glass-card p-6 border-l-4 border-teal-500">
                    <p class="text-[10px] font-bold text-slate-500 uppercase mb-1">Total Admissions</p>
                    <h2 class="text-3xl font-black"><?= $kpi['total'] ?? 0 ?></h2>
                </div>
                <div class="glass-card p-6 border-l-4 border-rose-500">
                    <p class="text-[10px] font-bold text-slate-500 uppercase mb-1">Urgences actives</p>
                    <h2 class="text-3xl font-black text-rose-500"><?= $types['Urgent'] ?? 0 ?></h2>
                </div>
                <div class="glass-card p-6 border-l-4 border-sky-500">
                    <p class="text-[10px] font-bold text-slate-500 uppercase mb-1">Lits Libres</p>
                    <h2 class="text-3xl font-black text-sky-500"><?= $chambres['libre'] ?? 0 ?></h2>
                </div>
                <div class="glass-card p-6 bg-slate-900 border-none">
                    <p class="text-[10px] font-bold text-slate-400 uppercase mb-1">Efficacité</p>
                    <h2 class="text-3xl font-black text-teal-400"><?= round((($kpi['termine'] ?? 0) / ($kpi['total'] ?: 1)) * 100) ?>%</h2>
                </div>
            </div>

            <div class="grid grid-cols-12 gap-8">
                <div class="col-span-8 glass-card p-8">
                    <h3 class="font-bold text-xs uppercase mb-8 tracking-widest opacity-50">Flux Admission Annuel</h3>
                    <div class="h-80"><canvas id="mainAreaChart"></canvas></div>
                </div>
                <div class="col-span-4 glass-card p-8">
                    <h3 class="font-bold text-xs uppercase mb-8 tracking-widest opacity-50">Saturation par Service</h3>
                    <div class="space-y-6">
                        <?php foreach($services as $s): ?>
                        <div>
                            <div class="flex justify-between text-xs font-bold mb-2 uppercase">
                                <span><?= $s['service'] ?></span>
                                <span class="opacity-50"><?= $s['total'] ?></span>
                            </div>
                            <div class="w-full bg-slate-200 dark:bg-slate-700 h-1.5 rounded-full overflow-hidden">
                                <div class="bg-teal-500 h-full" style="width: <?= ($s['total'] / ($services[0]['total'] ?: 1)) * 100 ?>%"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div id="tab-patients" class="tab-content">
            <div class="grid grid-cols-3 gap-8">
                <div class="glass-card p-8">
                    <h3 class="text-center font-bold text-xs uppercase mb-10 opacity-50">Sévérité</h3>
                    <div class="h-64"><canvas id="radarSévérité"></canvas></div>
                </div>
                <div class="glass-card p-8">
                    <h3 class="text-center font-bold text-xs uppercase mb-10 opacity-50">Tranches d'âge</h3>
                    <div class="h-64"><canvas id="ageDoughnut"></canvas></div>
                </div>
                <div class="glass-card p-8">
                    <h3 class="text-center font-bold text-xs uppercase mb-10 opacity-50">Genre</h3>
                    <div class="h-64"><canvas id="genrePolar"></canvas></div>
                </div>
            </div>
        </div>

        <div id="tab-logistics" class="tab-content">
            <div class="max-w-4xl mx-auto glass-card p-12 text-center">
                <i class="fa-solid fa-bed text-teal-500 text-5xl mb-6"></i>
                <h3 class="text-2xl font-black mb-2 uppercase">Capacité Hospitalière</h3>
                <p class="text-slate-500 mb-10">Statut des unités d'hébergement</p>
                <div class="grid grid-cols-2 gap-8">
                    <div class="p-10 bg-emerald-500/10 rounded-3xl border border-emerald-500/20">
                        <span class="text-6xl font-black text-emerald-500"><?= $chambres['libre'] ?? 0 ?></span>
                        <p class="text-xs font-bold uppercase mt-4 opacity-60">Lits Disponibles</p>
                    </div>
                    <div class="p-10 bg-rose-500/10 rounded-3xl border border-rose-500/20">
                        <span class="text-6xl font-black text-rose-500"><?= $chambres['complet'] ?? 0 ?></span>
                        <p class="text-xs font-bold uppercase mt-4 opacity-60">Unités Saturées</p>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
    // GESTION DU MODE SOMBRE
    function toggleDarkMode() {
        const body = document.body;
        const icon = document.getElementById('theme-icon');
        body.classList.toggle('dark');
        
        if(body.classList.contains('dark')) {
            icon.classList.replace('fa-moon', 'fa-sun');
            localStorage.setItem('theme', 'dark');
        } else {
            icon.classList.replace('fa-sun', 'fa-moon');
            localStorage.setItem('theme', 'light');
        }
    }

    // GESTION DES ONGLETS
    function switchTab(id) {
        document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
        document.getElementById('tab-' + id).classList.add('active');
        
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.getElementById('btn-' + id).classList.add('active');

        const titles = { 'global': 'Analytique Globale', 'patients': 'Analyse Clinique', 'logistics': 'Gestion Logistique' };
        document.getElementById('title-main').innerText = titles[id];
    }

    // Charger le thème préféré
    if(localStorage.getItem('theme') === 'dark') {
        document.body.classList.add('dark');
        document.getElementById('theme-icon').classList.replace('fa-moon', 'fa-sun');
    }

    // CONFIGURATION GRAPHIQUES (Gardée identique)
    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.color = '#94a3b8';

    new Chart(document.getElementById('mainAreaChart'), {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($evo_data, 'mois')) ?>,
            datasets: [{
                data: <?= json_encode(array_column($evo_data, 'total')) ?>,
                borderColor: '#0f766e',
                backgroundColor: 'rgba(15, 118, 110, 0.1)',
                fill: true,
                tension: 0.4,
                borderWidth: 3
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
    });

    new Chart(document.getElementById('ageDoughnut'), {
        type: 'doughnut',
        data: {
            labels: ['0-14', '15-30', '31-50', '50+'],
            datasets: [{
                data: [<?= $age['0-14']?>, <?= $age['15-30']?>, <?= $age['31-50']?>, <?= $age['+50']?>],
                backgroundColor: ['#2dd4bf', '#0d9488', '#0f766e', '#115e59'],
                borderWidth: 0
            }]
        },
        options: { cutout: '75%', plugins: { legend: { position: 'bottom' } } }
    });

    new Chart(document.getElementById('radarSévérité'), {
        type: 'radar',
        data: {
            labels: ['Urgences', 'Normal', 'Suivi', 'Consultation'],
            datasets: [{
                data: [<?= $types['Urgent'] ?? 0 ?>, <?= $types['Normal'] ?? 0 ?>, 12, 5],
                backgroundColor: 'rgba(15, 118, 110, 0.2)',
                borderColor: '#0f766e'
            }]
        },
        options: { scales: { r: { grid: { color: '#334155' }, ticks: { display: false } } } }
    });

    new Chart(document.getElementById('genrePolar'), {
        type: 'polarArea',
        data: {
            labels: ['Hommes', 'Femmes'],
            datasets: [{
                data: [15, 25],
                backgroundColor: ['rgba(15, 118, 110, 0.7)', 'rgba(244, 63, 94, 0.7)']
            }]
        },
        options: { plugins: { legend: { position: 'bottom' } } }
    });
</script>
</body>
</html>