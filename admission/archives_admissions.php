<?php
// 1. Connexion & Logic Backend
$host = 'localhost'; $db = 'gestion_patients'; $user = 'root'; $pass = '';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) { die("Erreur : " . $e->getMessage()); }

// --- API Logic لباش نجبدو البيانات بـ AJAX ---
if (isset($_GET['get_full_history'])) {
    header('Content-Type: application/json');
    $id_adm = $_GET['get_full_history'];

    // نجبدو معلومات المريض والقبول
    $stmt = $pdo->prepare("SELECT a.*, p.nom, p.prenom, p.CIN, p.sexe, u.nom as medecin 
                           FROM admissions_archive a 
                           JOIN patients p ON a.id_patient = p.id_patient 
                           LEFT JOIN utilisateurs u ON a.id_medecin = u.id_user 
                           WHERE a.id_admission = ?");
    $stmt->execute([$id_adm]);
    $base = $stmt->fetch(PDO::FETCH_ASSOC);

    // نجبدو الأدوية (Traitements)
    $stmt = $pdo->prepare("SELECT * FROM traitements WHERE id_admission = ?");
    $stmt->execute([$id_adm]);
    $drugs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // نجبدو التتبع (Suivis/Vitals)
    $stmt = $pdo->prepare("SELECT * FROM suivis WHERE id_patient = ? ORDER BY date_suivi DESC");
    $stmt->execute([$base['id_patient']]);
    $vitals = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['info' => $base, 'drugs' => $drugs, 'vitals' => $vitals]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Archives Médicales | Premium System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { background: #f4f7fa; font-family: 'Plus Jakarta Sans', sans-serif; overflow: hidden; }
        .glass-sidebar { background: white; height: 100vh; border-right: 1px solid #e2e8f0; width: 400px; position: fixed; display: flex; flex-direction: column; }
        .main-stage { margin-left: 400px; height: 100vh; overflow-y: auto; padding: 40px; }
        .archive-item { cursor: pointer; border: 1px solid transparent; transition: 0.3s; border-radius: 12px; margin-bottom: 10px; }
        .archive-item:hover { background: #f8fbff; border-color: #d1e1ff; }
        .archive-item.active { background: #eef4ff; border-left: 5px solid #4361ee; }
        .vitals-card { background: white; border-radius: 16px; border: none; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
        .timeline-badge { width: 12px; height: 12px; border-radius: 50%; background: #4361ee; position: absolute; left: -6px; top: 10px; }
        .timeline-box { border-left: 2px dashed #cbd5e1; padding-left: 20px; position: relative; padding-bottom: 20px; }
        .search-bar { background: #f1f5f9; border: none; border-radius: 10px; padding: 12px 20px; }
    </style>
</head>
<body>

<div class="glass-sidebar p-4 shadow-sm">
    <div class="d-flex align-items-center mb-4">
        <div class="bg-primary text-white p-2 rounded-3 me-3"><i class="bi bi-shield-lock-fill fs-4"></i></div>
        <h4 class="mb-0 fw-bold">Archive Pro</h4>
    </div>

    <input type="text" id="searchBox" class="form-control search-bar mb-4" placeholder="Rechercher CIN ou Nom...">

    <div class="overflow-auto pe-2" id="listArchives">
        <?php
        $stmt = $pdo->query("SELECT a.id_admission, p.nom, p.prenom, a.service, a.date_sortie 
                             FROM admissions_archive a 
                             JOIN patients p ON a.id_patient = p.id_patient 
                             ORDER BY a.archived_at DESC");
        while($row = $stmt->fetch()): ?>
        <div class="archive-item p-3 shadow-sm bg-white" onclick="loadFullDossier(<?= $row['id_admission'] ?>, this)">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="fw-bold text-dark text-uppercase"><?= $row['nom'] ?> <?= $row['prenom'] ?></div>
                    <span class="badge bg-light text-primary small mt-1"><?= $row['service'] ?></span>
                </div>
                <small class="text-muted"><?= date('d/m/y', strtotime($row['date_sortie'])) ?></small>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div>

<main class="main-stage">
    <div id="emptyView" class="text-center mt-5 opacity-50">
        <i class="bi bi-folder2-open display-1"></i>
        <h3 class="mt-3">Sélectionnez un dossier pour voir l'historique médical</h3>
    </div>

    <div id="dossierView" style="display: none;">
        <div class="d-flex justify-content-between align-items-end mb-5">
            <div>
                <h1 class="fw-bold mb-1" id="pName">--</h1>
                <p class="text-muted"><i class="bi bi-fingerprint"></i> CIN: <span id="pCIN" class="fw-bold">--</span> | Genre: <span id="pSexe">--</span></p>
            </div>
            <button class="btn btn-dark rounded-pill px-4 shadow" onclick="window.print()">
                <i class="bi bi-printer me-2"></i> Imprimer le Rapport
            </button>
        </div>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="vitals-card p-4 mb-4">
                    <h5 class="fw-bold mb-4 text-primary"><i class="bi bi-info-circle me-2"></i>Détails de l'Admission</h5>
                    <div class="row g-3">
                        <div class="col-6"><label class="small text-muted">Médecin</label><p class="fw-bold" id="pDoc">--</p></div>
                        <div class="col-6"><label class="small text-muted">Service</label><p class="fw-bold" id="pService">--</p></div>
                        <div class="col-6 border-top pt-2"><label class="small text-muted">Date Entrée</label><p id="pIn">--</p></div>
                        <div class="col-6 border-top pt-2"><label class="small text-muted">Date Sortie</label><p id="pOut">--</p></div>
                        <div class="col-12 border-top pt-2"><label class="small text-muted">Motif Principal</label><p class="bg-light p-3 rounded" id="pMotif">--</p></div>
                    </div>
                </div>

                <div class="vitals-card p-4">
                    <h5 class="fw-bold mb-4 text-success"><i class="bi bi-capsule me-2"></i>Traitements Administrés</h5>
                    <div id="pDrugs" class="list-group list-group-flush"></div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="vitals-card p-4 mb-4 bg-primary text-white shadow-lg">
                    <h6 class="mb-4">Dernières Constantes</h6>
                    <div class="d-flex justify-content-around text-center">
                        <div><div class="small opacity-75">Tension</div><h3 id="vTension">--</h3></div>
                        <div class="border-start border-white-50 ps-4"><div class="small opacity-75">Temp.</div><h3 id="vTemp">-- °C</h3></div>
                    </div>
                </div>

                <div class="vitals-card p-4">
                    <h5 class="fw-bold mb-4"><i class="bi bi-clock-history me-2"></i>Historique des Suivis</h5>
                    <div id="vTimeline"></div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
async function loadFullDossier(id, element) {
    // Style active
    document.querySelectorAll('.archive-item').forEach(el => el.classList.remove('active'));
    element.classList.add('active');
    
    document.getElementById('emptyView').style.display = 'none';
    document.getElementById('dossierView').style.display = 'block';

    const res = await fetch(`archives_professional.php?get_full_history=${id}`);
    const data = await res.json();

    // Mapping Info
    document.getElementById('pName').innerText = data.info.nom + ' ' + data.info.prenom;
    document.getElementById('pCIN').innerText = data.info.CIN;
    document.getElementById('pSexe').innerText = data.info.sexe == 'M' ? 'Masculin' : 'Féminin';
    document.getElementById('pDoc').innerText = 'Dr. ' + (data.info.medecin || 'Généraliste');
    document.getElementById('pService').innerText = data.info.service;
    document.getElementById('pIn').innerText = data.info.date_admission;
    document.getElementById('pOut').innerText = data.info.date_sortie;
    document.getElementById('pMotif').innerText = data.info.motif || "Pas de motif spécifié";

    // Mapping Drugs
    let dHTML = '';
    data.drugs.forEach(d => {
        dHTML += `<div class="list-group-item d-flex justify-content-between align-items-center">
                    <div><b>${d.medicament}</b><br><small>${d.frequence}</small></div>
                    <span class="badge bg-success-subtle text-success rounded-pill">${d.dosage}</span>
                  </div>`;
    });
    document.getElementById('pDrugs').innerHTML = dHTML || '<p class="text-muted">Aucun médicament prescrit.</p>';

    // Mapping Vitals & Timeline
    if(data.vitals.length > 0) {
        document.getElementById('vTension').innerText = data.vitals[0].tension;
        document.getElementById('vTemp').innerText = data.vitals[0].temperature + " °C";
        
        let tHTML = '';
        data.vitals.forEach(v => {
            tHTML += `<div class="timeline-box">
                        <div class="timeline-badge"></div>
                        <div class="small fw-bold">${v.date_suivi}</div>
                        <div class="text-muted x-small">${v.remarques}</div>
                      </div>`;
        });
        document.getElementById('vTimeline').innerHTML = tHTML;
    } else {
        document.getElementById('vTimeline').innerHTML = 'Pas de suivi.';
    }
}

// Recherche instantanée
document.getElementById('searchBox').addEventListener('input', function() {
    let val = this.value.toLowerCase();
    document.querySelectorAll('.archive-item').forEach(item => {
        item.style.display = item.innerText.toLowerCase().includes(val) ? "block" : "none";
    });
});
</script>

</body>
</html>