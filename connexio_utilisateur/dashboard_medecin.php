<?php
session_start();
include("../config/connexion.php");

// Vérifier que l'utilisateur est connecté et est médecin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'medecin') {
    header("Location: login.php");
    exit;
}

// Infos médecin
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT nom, prenom, email FROM utilisateurs WHERE id_user = ?");
$stmt->execute([$user_id]);
$medecin = $stmt->fetch(PDO::FETCH_ASSOC);

// Nombre patients
$stmt2 = $pdo->prepare("SELECT COUNT(*) AS total_patients FROM patients WHERE id_medecin = ?");
$stmt2->execute([$user_id]);
$total_patients = $stmt2->fetch(PDO::FETCH_ASSOC)['total_patients'];

// Derniers suivis
$stmt3 = $pdo->prepare("
    SELECT p.nom, p.prenom, s.date_suivi, s.commentaire
    FROM suivis s
    JOIN patients p ON s.id_patient = p.id_patient
    WHERE p.id_medecin = ?
    ORDER BY s.date_suivi DESC
    LIMIT 5
");
$stmt3->execute([$user_id]);
$derniers_suivis = $stmt3->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Dashboard Médecin</title>

<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
:root{
    --primary:#01A28C;
    --gray:#737978;
    --white:#ffffff;
    --bg:#f4f6f6;
}

body{
    margin:0;
    font-family: "Segoe UI", sans-serif;
    background:var(--bg);
}

/* ===== HEADER ===== */
.header{
    background:var(--primary);
    color:white;
    padding:20px;
    display:flex;
    justify-content:space-between;
    align-items:center;
}

.header h1{
    margin:0;
    font-size:22px;
}

.header span{
    font-size:14px;
    opacity:0.9;
}

/* ===== NAV ===== */
.nav{
    background:white;
    padding:12px 20px;
    display:flex;
    gap:20px;
    border-bottom:1px solid #ddd;
}

.nav a{
    text-decoration:none;
    color:var(--gray);
    font-weight:600;
}

.nav a i{
    margin-right:6px;
    color:var(--primary);
}

.nav a:hover{
    color:var(--primary);
}

/* ===== MAIN ===== */
.main{
    padding:25px;
}

/* ===== STATS ===== */
.stats{
    display:grid;
    grid-template-columns: repeat(auto-fit, minmax(250px,1fr));
    gap:20px;
    margin-bottom:30px;
}

.stat-card{
    background:white;
    border-radius:12px;
    padding:20px;
    display:flex;
    align-items:center;
    gap:15px;
    box-shadow:0 4px 10px rgba(0,0,0,0.05);
}

.stat-card i{
    font-size:32px;
    color:var(--primary);
}

.stat-card h2{
    margin:0;
    font-size:24px;
}

.stat-card p{
    margin:0;
    color:var(--gray);
}

/* ===== SECTION ===== */
.section{
    background:white;
    border-radius:12px;
    padding:20px;
    margin-bottom:25px;
    box-shadow:0 4px 10px rgba(0,0,0,0.05);
}

.section h3{
    margin-top:0;
    color:var(--primary);
}

/* ===== TABLE ===== */
table{
    width:100%;
    border-collapse:collapse;
}

th{
    background:#f1f1f1;
    text-align:left;
    padding:10px;
    color:#333;
}

td{
    padding:10px;
    border-top:1px solid #eee;
}

tr:hover{
    background:#f9f9f9;
}

/* ===== FOOTER LIST ===== */
.features li{
    margin-bottom:8px;
    color:var(--gray);
}
</style>
</head>

<body>

<!-- HEADER -->
<div class="header">
    <div>
        <h1><i class="fa-solid fa-user-doctor"></i> Dr. <?= htmlspecialchars($medecin['prenom']." ".$medecin['nom']) ?></h1>
        <span><?= htmlspecialchars($medecin['email']) ?></span>
    </div>
</div>

<!-- NAV -->
<div class="nav">
    <a href="dashboard_medecin.php"><i class="fa-solid fa-house"></i> Accueil</a>
    <a href="patients.php"><i class="fa-solid fa-users"></i> Patients</a>
    <a href="suivis.php"><i class="fa-solid fa-notes-medical"></i> Suivis</a>
    <a href="traitements.php"><i class="fa-solid fa-pills"></i> Traitements</a>
    <a href="rendezvous.php"><i class="fa-solid fa-calendar-check"></i> Rendez-vous</a>
    <a href="deconnexion.php"><i class="fa-solid fa-right-from-bracket"></i> Déconnexion</a>
</div>

<!-- MAIN -->
<div class="main">

    <!-- STATS -->
    <div class="stats">
        <div class="stat-card">
            <i class="fa-solid fa-users"></i>
            <div>
                <h2><?= $total_patients ?></h2>
                <p>Patients suivis</p>
            </div>
        </div>
    </div>

    <!-- DERNIERS SUIVIS -->
    <div class="section">
        <h3><i class="fa-solid fa-clock-rotate-left"></i> Derniers suivis</h3>

        <?php if ($derniers_suivis): ?>
        <table>
            <thead>
                <tr>
                    <th>Patient</th>
                    <th>Date</th>
                    <th>Commentaire</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($derniers_suivis as $s): ?>
                <tr>
                    <td><?= htmlspecialchars($s['nom']." ".$s['prenom']) ?></td>
                    <td><?= htmlspecialchars($s['date_suivi']) ?></td>
                    <td><?= htmlspecialchars($s['commentaire']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p style="color:var(--gray)">Aucun suivi récent.</p>
        <?php endif; ?>
    </div>

   
</div>

</body>
</html>
