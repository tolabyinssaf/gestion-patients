<?php
session_start();
include("../config/connexion.php");

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'secretaire') {
    header("Location: ../login.php"); exit;
}

// 1. RÉCUPÉRATION DES FILTRES
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

if (isset($_GET['medecin_id'])) {
    $_SESSION['filter_medecin'] = $_GET['medecin_id'];
}
$filter_medecin = $_SESSION['filter_medecin'] ?? '';

// 2. LISTE DES MÉDECINS (Pour le filtre)
$medecins_list = $pdo->query("SELECT id_user, nom, prenom FROM utilisateurs WHERE LOWER(role) = 'medecin' ORDER BY nom ASC")->fetchAll();

// 3. REQUÊTE SQL AVEC CAST DE DATE (Pour éviter les erreurs DATETIME)
// On utilise DATE() pour extraire uniquement la partie Y-m-d de la colonne date_suivi
$sql = "SELECT 
            s.id_suivi, 
            s.date_suivi, 
            s.commentaire, 
            s.status,
            p.nom AS pat_nom, 
            p.prenom AS pat_prenom, 
            u.nom AS med_nom 
        FROM suivis s
        JOIN patients p ON s.id_patient = p.id_patient
        JOIN utilisateurs u ON s.id_medecin = u.id_user
        WHERE DATE(s.date_suivi) = :date_sel";

if (!empty($filter_medecin)) {
    $sql .= " AND s.id_medecin = :id_med";
}

$sql .= " ORDER BY s.date_suivi ASC";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':date_sel', $selected_date);
if (!empty($filter_medecin)) {
    $stmt->bindValue(':id_med', $filter_medecin, PDO::PARAM_INT);
}
$stmt->execute();
$suivis = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- ZONE DE DÉBOGAGE (A enlever après test) ---
// echo "DEBUG: Date cherchée: $selected_date | Médecin ID: $filter_medecin | Nombre trouvés: ".count($suivis);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Suivis Patients</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { background: #f4f7f6; }
        .main-container { padding: 30px; margin-top: 50px; }
        .filter-bar { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .status-badge { padding: 5px 10px; border-radius: 5px; font-size: 12px; font-weight: bold; }
        .status-encours { background: #fff3cd; color: #856404; }
        .status-termine { background: #d4edda; color: #155724; }
    </style>
</head>
<body>

<div class="container main-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fa-solid fa-calendar-days text-primary me-2"></i>Suivis Médicaux</h2>
        <a href="dashboard_secretaire.php" class="btn btn-secondary btn-sm">Retour Dashboard</a>
    </div>

    <div class="filter-bar">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label fw-bold">Date de consultation</label>
                <input type="date" name="date" class="form-control" value="<?= $selected_date ?>" onchange="this.form.submit()">
            </div>
            <div class="col-md-5">
                <label class="form-label fw-bold">Médecin</label>
                <select name="medecin_id" class="form-select" onchange="this.form.submit()">
                    <option value="">-- Tous les médecins --</option>
                    <?php foreach($medecins_list as $m): ?>
                        <option value="<?= $m['id_user'] ?>" <?= $filter_medecin == $m['id_user'] ? 'selected' : '' ?>>
                            Dr. <?= htmlspecialchars($m['nom']." ".$m['prenom']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <a href="suivis.php?date=<?= date('Y-m-d') ?>" class="btn btn-outline-dark w-100">Aujourd'hui</a>
            </div>
        </form>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th class="ps-4">Heure</th>
                        <th>Patient</th>
                        <th>Médecin</th>
                        <th>Commentaire</th>
                        <th>Statut</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(!empty($suivis)): ?>
                        <?php foreach($suivis as $s): ?>
                        <tr>
                            <td class="ps-4 fw-bold"><?= date('H:i', strtotime($s['date_suivi'])) ?></td>
                            <td><?= strtoupper($s['pat_nom']) ?> <?= $s['pat_prenom'] ?></td>
                            <td><span class="text-primary">Dr. <?= $s['med_nom'] ?></span></td>
                            <td class="text-muted small"><?= htmlspecialchars($s['commentaire']) ?></td>
                            <td>
                                <span class="status-badge <?= strtolower($s['status']) == 'terminé' ? 'status-termine' : 'status-encours' ?>">
                                    <?= $s['status'] ?: 'En attente' ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <a href="dossier_patient.php?id=<?= $s['id_patient'] ?>" class="btn btn-sm btn-light border"><i class="fa-solid fa-eye"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="fa-regular fa-folder-open fa-3x mb-3"></i>
                                    <p>Aucun suivi enregistré pour ce médecin à cette date (<?= date('d/m/Y', strtotime($selected_date)) ?>).</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>