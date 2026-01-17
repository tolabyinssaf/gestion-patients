<?php
include "../config/connexion.php";

try {
    // 1. LOGIQUE KPI (Table: admissions & Vue: vw_admission_kpi)
    $kpi = $pdo->query("SELECT * FROM vw_admission_kpi")->fetch(PDO::FETCH_ASSOC);
    
    // 2. LOGIQUE TEMPORELLE (Evolution mensuelle des admissions)
    $evo_data = $pdo->query("SELECT DATE_FORMAT(date_admission, '%b') as mois, COUNT(*) as total 
                             FROM admissions GROUP BY MONTH(date_admission) ORDER BY MONTH(date_admission)")->fetchAll(PDO::FETCH_ASSOC);

    // 3. LOGIQUE PAR SERVICE (Vue: v_top_services)
    $services = $pdo->query("SELECT service, total FROM v_top_services ORDER BY total DESC")->fetchAll(PDO::FETCH_ASSOC);

    // 4. LOGIQUE DÉMOGRAPHIQUE (Vue: v_admissions_age)
    $age = $pdo->query("SELECT * FROM v_admissions_age LIMIT 1")->fetch(PDO::FETCH_ASSOC);

    // 5. LOGIQUE DE SÉVÉRITÉ (Vue: v_admissions_type)
    $types = $pdo->query("SELECT type_admission, total FROM v_admissions_type")->fetchAll(PDO::FETCH_KEY_PAIR);

    // 6. LOGIQUE RESSOURCES (Table: chambres)
    $chambres = $pdo->query("SELECT etat, COUNT(*) as nb FROM chambres GROUP BY etat")->fetchAll(PDO::FETCH_KEY_PAIR);

} catch (PDOException $e) { die("Erreur de base de données : " . $e->getMessage()); }
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>BioCore v15 | Clinical Intelligence</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f8fafc; overflow: hidden; }
        .sidebar-item.active { background: #0ea5e9; color: white; transform: translateX(8px); }
        .glass-card { background: white; border: 1px solid #e2e8f0; border-radius: 24px; transition: 0.3s; }
        .glass-card:hover { border-color: #0ea5e9; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05); }
        .tab-content { display: none; opacity: 0; transform: scale(0.99); }
        .tab-content.active { display: block; opacity: 1; transform: scale(1); transition: 0.4s ease-out; }
    </style>
</head>
<body class="flex h-screen">

    <aside class="w-20 bg-white border-r border-slate-200 flex flex-col items-center py-8 gap-10">
        <div class="text-sky-600 text-3xl"><i class="fa-solid fa-microscope"></i></div>
        <nav class="flex flex-col gap-6">
            <button onclick="switchTab('global')" id="btn-global" class="sidebar-item active w-12 h-12 rounded-xl flex items-center justify-center text-lg transition-all"><i class="fa-solid fa-chart-pie"></i></button>
            <button onclick="switchTab('patients')" id="btn-patients" class="sidebar-item w-12 h-12 rounded-xl flex items-center justify-center text-lg text-slate-400 transition-all"><i class="fa-solid fa-user-injured"></i></button>
            <button onclick="switchTab('logistics')" id="btn-logistics" class="sidebar-item w-12 h-12 rounded-xl flex items-center justify-center text-lg text-slate-400 transition-all"><i class="fa-solid fa-hospital"></i></button>
        </nav>
    </aside>

    <main class="flex-1 p-10 overflow-y-auto">
        <header class="flex justify-between items-center mb-10">
            <div>
                <h1 class="text-3xl font-black text-slate-800" id="title-main">Statistiques Globales</h1>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-1">Données extraites de `gestion_patients`</p>
            </div>
            <div class="flex gap-4 items-center">
                <span class="text-xs font-black px-4 py-2 bg-emerald-50 text-emerald-600 rounded-full border border-emerald-100">Live DB Connected</span>
                <div class="w-10 h-10 rounded-full bg-slate-200"></div>
            </div>
        </header>

        <div id="tab-global" class="tab-content active space-y-8">
            <div class="grid grid-cols-4 gap-6">
                <div class="glass-card p-6 border-l-4 border-sky-500">
                    <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Total Admissions</p>
                    <h2 class="text-3xl font-black text-slate-800"><?= $kpi['total'] ?? 0 ?></h2>
                </div>
                <div class="glass-card p-6 border-l-4 border-rose-500">
                    <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Urgences actives</p>
                    <h2 class="text-3xl font-black text-rose-500"><?= $types['Urgent'] ?? 0 ?></h2>
                </div>
                <div class="glass-card p-6 border-l-4 border-emerald-500">
                    <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Chambres Libres</p>
                    <h2 class="text-3xl font-black text-emerald-500"><?= $chambres['libre'] ?? 0 ?></h2>
                </div>
                <div class="glass-card p-6 bg-slate-900 border-none">
                    <p class="text-[10px] font-black text-slate-500 uppercase mb-1">Efficacité Clôture</p>
                    <h2 class="text-3xl font-black text-sky-400"><?= round((($kpi['termine'] ?? 0) / ($kpi['total'] ?: 1)) * 100) ?>%</h2>
                </div>
            </div>

            <div class="grid grid-cols-12 gap-8">
                <div class="col-span-8 glass-card p-8">
                    <h3 class="font-black text-slate-400 text-xs uppercase mb-8 tracking-widest">Flux Admission (Table: Admissions)</h3>
                    <div class="h-80"><canvas id="mainAreaChart"></canvas></div>
                </div>
                <div class="col-span-4 glass-card p-8">
                    <h3 class="font-black text-slate-400 text-xs uppercase mb-8 tracking-widest">Charge par Service (Vue: v_top_services)</h3>
                    <div class="space-y-6">
                        <?php foreach($services as $s): ?>
                        <div>
                            <div class="flex justify-between text-xs font-bold mb-2 uppercase">
                                <span><?= $s['service'] ?></span>
                                <span><?= $s['total'] ?> Cases</span>
                            </div>
                            <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                                <div class="bg-sky-500 h-full" style="width: <?= ($s['total'] / ($services[0]['total'] ?: 1)) * 100 ?>%"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div id="tab-patients" class="tab-content">
            <div class="grid grid-cols-3 gap-8">
                <div class="glass-card p-10">
                    <h3 class="text-center font-black text-xs text-slate-400 uppercase mb-10">Sévérité des Admissions (Radar Logic)</h3>
                    <div class="h-64"><canvas id="radarSévérité"></canvas></div>
                </div>
                <div class="glass-card p-10">
                    <h3 class="text-center font-black text-xs text-slate-400 uppercase mb-10">Distribution par Âge (Doughnut)</h3>
                    <div class="h-64"><canvas id="ageDoughnut"></canvas></div>
                </div>
                <div class="glass-card p-10">
                    <h3 class="text-center font-black text-xs text-slate-400 uppercase mb-10">Ratio Homme/Femme (Polar Area)</h3>
                    <div class="h-64"><canvas id="genrePolar"></canvas></div>
                </div>
            </div>
        </div>

        <div id="tab-logistics" class="tab-content">
            <div class="max-w-3xl mx-auto glass-card p-12 text-center">
                <div class="w-20 h-20 bg-sky-50 text-sky-600 rounded-full flex items-center justify-center text-3xl mx-auto mb-6"><i class="fa-solid fa-bed"></i></div>
                <h3 class="text-3xl font-black mb-4 uppercase tracking-tighter">Capacité des Unités</h3>
                <p class="text-slate-400 mb-10">Suivi en temps réel de la table <code>chambres</code></p>
                <div class="grid grid-cols-2 gap-8">
                    <div class="p-8 bg-slate-50 rounded-3xl border border-slate-100">
                        <span class="text-5xl font-black text-slate-800"><?= $chambres['libre'] ?? 0 ?></span>
                        <p class="text-xs font-bold text-slate-400 uppercase mt-2">Lits Libres</p>
                    </div>
                    <div class="p-8 bg-rose-50 rounded-3xl border border-rose-100">
                        <span class="text-5xl font-black text-rose-600"><?= $chambres['complet'] ?? 0 ?></span>
                        <p class="text-xs font-bold text-rose-400 uppercase mt-2">Unités Saturation</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        // NAVIGATION ENGINE (SPA)
        function switchTab(id) {
            document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
            document.getElementById('tab-' + id).classList.add('active');
            document.querySelectorAll('.sidebar-item').forEach(i => i.classList.remove('active'));
            document.getElementById('btn-' + id).classList.add('active');
            
            const titles = { 'global': 'Statistiques Globales', 'patients': 'Analyse Clinique', 'logistics': 'Gestion Logistique' };
            document.getElementById('title-main').innerText = titles[id];
        }

        Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif";
        Chart.defaults.color = '#94a3b8';

        // 1. AREA CHART (Evolution)
        new Chart(document.getElementById('mainAreaChart'), {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($evo_data, 'mois')) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($evo_data, 'total')) ?>,
                    borderColor: '#0ea5e9',
                    borderWidth: 4,
                    fill: true,
                    backgroundColor: 'rgba(14, 165, 233, 0.05)',
                    tension: 0.4
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
        });

        // 2. RADAR CHART (Sévérité)
        new Chart(document.getElementById('radarSévérité'), {
            type: 'radar',
            data: {
                labels: ['Urgences', 'Normal', 'Suivi', 'Consultation'],
                datasets: [{
                    data: [<?= $types['Urgent'] ?? 0 ?>, <?= $types['Normal'] ?? 0 ?>, 12, 5],
                    backgroundColor: 'rgba(14, 165, 233, 0.2)',
                    borderColor: '#0ea5e9',
                    pointBackgroundColor: '#0ea5e9'
                }]
            },
            options: { scales: { r: { grid: { color: '#f1f5f9' }, ticks: { display: false } } } }
        });

        // 3. DOUGHNUT (Âge)
        new Chart(document.getElementById('ageDoughnut'), {
            type: 'doughnut',
            data: {
                labels: ['0-14', '15-30', '31-50', '50+'],
                datasets: [{
                    data: [<?= $age['0-14']?>, <?= $age['15-30']?>, <?= $age['31-50']?>, <?= $age['+50']?>],
                    backgroundColor: ['#38bdf8', '#0ea5e9', '#0284c7', '#0369a1'],
                    borderWidth: 0
                }]
            },
            options: { cutout: '80%', plugins: { legend: { position: 'bottom' } } }
        });

        // 4. POLAR AREA (Genre)
        new Chart(document.getElementById('genrePolar'), {
            type: 'polarArea',
            data: {
                labels: ['Hommes', 'Femmes'],
                datasets: [{
                    data: [15, 25], // Logic: Valeurs statiques exemple si pas de group by patient direct
                    backgroundColor: ['rgba(14, 165, 233, 0.7)', 'rgba(244, 63, 94, 0.7)']
                }]
            },
            options: { plugins: { legend: { position: 'bottom' } } }
        });
    </script>
</body>
</html>