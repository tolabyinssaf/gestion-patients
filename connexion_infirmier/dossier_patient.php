<?php
session_start();
include("../config/connexion.php");

// Vérification session
if (!isset($_SESSION['user_id'])) { 
    header("Location: ../login.php"); 
    exit; 
}

$id_admission = $_GET['id_adm'] ?? null;

if (!$id_admission) { 
    echo "Admission non trouvée"; 
    exit; 
}

try {
    // 1. RÉCUPÉRATION DES INFOS (Admission + Patient + Médecin)
    $stmt = $pdo->prepare("SELECT a.*, p.*, 
                            u.nom as med_nom, u.prenom as med_prenom 
                            FROM admissions a 
                            JOIN patients p ON a.id_patient = p.id_patient
                            LEFT JOIN utilisateurs u ON a.id_medecin = u.id_user 
                            WHERE a.id_admission = ?");
    $stmt->execute([$id_admission]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) { 
        echo "Données de l'admission introuvables"; 
        exit; 
    }

    // 2. RÉCUPÉRATION DES ANTÉCÉDENTS (Table dédiée)
    $stmt_ant = $pdo->prepare("SELECT * FROM antecedents WHERE id_patient = ? ORDER BY date_evenement DESC");
    $stmt_ant->execute([$data['id_patient']]);
    $liste_antecedents = $stmt_ant->fetchAll(PDO::FETCH_ASSOC);

    // 3. RÉCUPÉRATION DE L'HISTORIQUE DES SOINS (Avec constantes vitales)
    $stmt_soins = $pdo->prepare("SELECT s.*, u.nom as inf_nom, u.prenom as inf_prenom 
                                  FROM soins_patients s
                                  JOIN utilisateurs u ON s.id_infirmier = u.id_user
                                  WHERE s.id_admission = ? 
                                  ORDER BY s.date_soin DESC");
    $stmt_soins->execute([$id_admission]);
    $historique_soins = $stmt_soins->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
}

$nom_complet = strtoupper($data['nom'] ?? '') . ' ' . ($data['prenom'] ?? '');
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Dossier Patient | #<?= $id_admission ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root { --primary: #0f766e; --sidebar-bg: #0f172a; --bg-body: #f8fafc; --border-color: #e2e8f0; }
        body { background: var(--bg-body); font-family: 'Plus Jakarta Sans', sans-serif; color: #334155; }
        header { background: #fff; height: 70px; position: fixed; top: 0; width: 100%; display: flex; align-items: center; padding: 0 30px; justify-content: space-between; border-bottom: 1px solid var(--border-color); z-index: 1000; }
        .sidebar { width: 260px; background: var(--sidebar-bg); position: fixed; top: 70px; left: 0; bottom: 0; padding: 20px; z-index: 999; }
        .content { margin-left: 260px; margin-top: 70px; padding: 40px; }
        .sidebar a { display: flex; align-items: center; gap: 12px; color: #94a3b8; text-decoration: none; padding: 12px 15px; border-radius: 10px; margin-bottom: 5px; }
        .sidebar a.active { background: var(--primary); color: white; }
        .card-custom { background: white; border-radius: 16px; border: 1px solid var(--border-color); padding: 20px; margin-bottom: 20px; }
        
        @media print {
            header, .sidebar, .no-print { display: none !important; }
            .content { margin-left: 0 !important; margin-top: 0 !important; padding: 0px !important; }
        }
    </style>
</head>
<body>

<header>
    <img src="../images/logo_app2.png" style="height: 38px;">
    <div class="badge bg-light text-dark border">Session : <?= ucfirst($_SESSION['role'] ?? 'Utilisateur') ?></div>
</header>

    <aside class="sidebar">
        <h3 style="color:rgba(255,255,255,0.3); font-size:11px; text-transform:uppercase; margin-bottom:20px; padding-left:12px;">Menu Infirmier</h3>
        <a href="dashboard_infirmier.php" ><i class="fa-solid fa-chart-line"></i> Vue Générale</a>
        <a href="liste_patients_inf.php" class="active"><i class="fa-solid fa-user-injured"></i> Patients</a>
        <a href="planning.php"><i class="fa-solid fa-calendar-alt"></i> Planning</a>
        <a href="profil_infirmier.php"><i class="fa-solid fa-user"></i> Profil</a>
        <div style="height: 1px; background: rgba(255,255,255,0.1); margin: 20px 0;"></div>
        <a href="../connexio_utilisateur/deconnexion.php" style="color: #fda4af;"><i class="fa-solid fa-power-off"></i> Déconnexion</a>
    </aside>

<main class="content">

    <div class="bg-white rounded-4 shadow-sm p-3 mb-4 d-flex justify-content-between align-items-center border-0">
        <div class="d-flex align-items-center gap-3">
            <div class="d-flex align-items-center justify-content-center fw-bold text-white shadow-sm" 
                 style="width: 55px; height: 55px; background: linear-gradient(135deg, var(--primary), #14b8a6); border-radius: 14px; font-size: 1.3rem;">
                <?= substr($data['nom'], 0, 1) ?>
            </div>
            <div>
                <h4 class="mb-0 fw-bold"><?= $nom_complet ?></h4>
                <div class="d-flex gap-3 mt-1" style="font-size: 0.75rem; color: #64748b;">
                    <span><b>CIN :</b> <?= $data['CIN'] ?></span>
                    <span><b>SERVICE :</b> <?= $data['service'] ?></span>
                    <span><b>ID :</b> #<?= $id_admission ?></span>
                </div>
            </div>
        </div>
        <div class="d-flex gap-2 no-print">
            <button onclick="window.print()" class="btn btn-light btn-sm border fw-bold text-secondary"><i class="fa-solid fa-print"></i></button>
            <a href="saisir_soins.php?id_adm=<?= $id_admission ?>" class="btn btn-primary btn-sm px-4 fw-bold" style="background: var(--primary); border:0; border-radius: 8px;">
                <i class="fa-solid fa-plus-circle me-2"></i> AJOUTER UN SOIN
            </a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="bg-white rounded-4 shadow-sm p-4 border-0 mb-4">
                <h6 class="fw-bold mb-3" style="color: #1e293b;"><i class="fa-solid fa-file-medical text-primary me-2"></i> HISTORIQUE CLINIQUE</h6>
                
                <div class="mb-4">
                    <label class="d-block text-muted fw-bold mb-2" style="font-size: 0.65rem; text-transform: uppercase;">Antécédents du Patient</label>
                    <div class="list-group list-group-flush border rounded-3 overflow-hidden">
                        <?php if (empty($liste_antecedents)): ?>
                            <div class="list-group-item small text-muted italic text-center">Aucun antécédent répertorié</div>
                        <?php else: ?>
                            <?php foreach ($liste_antecedents as $ant): ?>
                                <div class="list-group-item p-2">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <span class="fw-bold text-dark small"><?= htmlspecialchars($ant['nom_pathologie']) ?></span>
                                        <span class="badge bg-light text-muted border-0 small" style="font-size: 0.6rem;"><?= date('Y', strtotime($ant['date_evenement'])) ?></span>
                                    </div>
                                    <div class="text-muted" style="font-size: 0.7rem;"><?= htmlspecialchars($ant['description']) ?></div>
                                    <span class="badge bg-info-subtle text-info mt-1" style="font-size: 0.55rem;"><?= strtoupper($ant['categorie']) ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="p-3 rounded-4" style="background: #fff1f2; border: 1px dashed #fda4af;">
                    <label class="d-block text-danger fw-bold mb-1" style="font-size: 0.65rem; text-transform: uppercase;"><i class="fa-solid fa-circle-exclamation"></i> Allergies</label>
                    <span class="text-danger fw-bold" style="font-size: 0.85rem;"><?= !empty($data['allergies']) ? htmlspecialchars($data['allergies']) : 'Aucune allergie' ?></span>
                </div>
            </div>

            <div class="bg-white rounded-4 shadow-sm p-4 border-0">
                <h6 class="fw-bold mb-3" style="color: #1e293b;"><i class="fa-solid fa-hospital text-primary me-2"></i> INFOS ADMISSION</h6>
                <div class="mb-2">
                    <label class="d-block text-muted fw-bold mb-1" style="font-size: 0.65rem; text-transform: uppercase;">Médecin</label>
                    <p class="small fw-bold mb-0">Dr. <?= $data['med_prenom'] ?> <?= $data['med_nom'] ?></p>
                </div>
                <div class="mb-2">
                    <label class="d-block text-muted fw-bold mb-1" style="font-size: 0.65rem; text-transform: uppercase;">Motif</label>
                    <p class="small mb-0"><?= htmlspecialchars($data['motif']) ?></p>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="bg-white rounded-4 shadow-sm p-4 border-0">
                <h6 class="fw-bold mb-4" style="color: #1e293b;"><i class="fa-solid fa-clock-rotate-left text-primary me-2"></i> SURVEILLANCE INFIRMIÈRE</h6>

                <div class="timeline">
                    <?php if (empty($historique_soins)): ?>
                        <div class="text-center py-5 opacity-50"><i class="fa-solid fa-folder-open fa-2x mb-2"></i><p class="small">Aucun acte de soin.</p></div>
                    <?php else: ?>
                        <?php foreach ($historique_soins as $soin): ?>
                        <div class="d-flex gap-3 mb-4">
                            <div class="text-end" style="min-width: 65px;">
                                <div class="fw-bold text-dark small"><?= date('H:i', strtotime($soin['date_soin'])) ?></div>
                                <div class="text-muted" style="font-size: 0.65rem;"><?= date('d/m/Y', strtotime($soin['date_soin'])) ?></div>
                            </div>
                            <div class="d-flex flex-column align-items-center">
                                <div class="rounded-circle bg-primary" style="width: 10px; height: 10px; margin-top: 5px;"></div>
                                <div class="bg-light flex-grow-1" style="width: 2px; margin: 5px 0;"></div>
                            </div>
                            <div class="bg-light rounded-4 p-3 flex-grow-1">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="fw-bold text-primary small text-uppercase"><?= htmlspecialchars($soin['type_acte']) ?></span>
                                    <span class="badge bg-white text-muted border fw-normal" style="font-size: 0.6rem;">Inf. <?= htmlspecialchars($soin['inf_prenom']) ?></span>
                                </div>

                                <div class="d-flex gap-2 mb-3">
                                    <?php if($soin['temperature']): ?>
                                        <span class="bg-white px-2 py-1 rounded border small fw-bold text-danger">T° <?= $soin['temperature'] ?>°C</span>
                                    <?php endif; ?>
                                    <?php if($soin['tension']): ?>
                                        <span class="bg-white px-2 py-1 rounded border small fw-bold text-primary">PA <?= $soin['tension'] ?></span>
                                    <?php endif; ?>
                                    <?php if($soin['frequence_cardiaque']): ?>
                                        <span class="bg-white px-2 py-1 rounded border small fw-bold text-success">FC <?= $soin['frequence_cardiaque'] ?> bpm</span>
                                    <?php endif; ?>
                                </div>

                                <?php if($soin['medicament']): ?>
                                    <div class="p-2 bg-warning-subtle rounded border-start border-warning border-4 mb-2" style="font-size: 0.75rem;">
                                        <i class="fa-solid fa-pills me-1"></i> <b>Traitement :</b> <?= htmlspecialchars($soin['medicament']) ?>
                                    </div>
                                <?php endif; ?>

                                <?php if($soin['observations']): ?>
                                    <p class="mb-0 text-secondary small italic">"<?= htmlspecialchars($soin['observations']) ?>"</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>
</body>
</html>