<?php
session_start();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Services | MedicalServices</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- Bootstrap + Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

<style>
/* ===== Global ===== */
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:"Segoe UI", sans-serif;
}

/* ===== Header identique à index ===== */
header{
    background:white;
    padding:15px 50px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    box-shadow:0 4px 15px rgba(0,0,0,0.1);
    position: sticky;
    top:0;
    z-index:100;
}
.logo {
    display:flex;
    align-items:center;
    gap:20px;
}
.logo-img {
    width:250px;
    height:auto;
}
.logo-text {
    font-size:36px;
    font-weight:700;
    color:#0f766e;
    letter-spacing:1px;
}
nav a{
    margin-left:30px;
    text-decoration:none;
    color:#374151;
    font-weight:500;
    font-size:16px;
    padding:6px 12px;
    border-radius:8px;
    transition: all 0.3s ease;
}
nav a:hover{
    background:#0f766e;
    color:white;
    box-shadow: 0 4px 12px rgba(15,118,110,0.3);
}
nav a.btn{
    background:#0f766e;
    color:white;
    padding:8px 20px;
    font-weight:600;
}
nav a.btn:hover{
    background:#115e59;
    box-shadow:0 6px 18px rgba(15,118,110,0.4);
}
@media (max-width:768px){
    header{
        flex-direction:column;
        align-items:flex-start;
        padding:15px 30px;
    }
    nav{
        margin-top:10px;
        width:100%;
        display:flex;
        flex-direction:column;
    }
    nav a{
        margin:8px 0;
    }
}

/* ===== Services Section ===== */
.services-section{
    padding:80px 20px;
    background:#f9fafb;
}

.services-section h2{
    text-align:center;
    font-size:32px;
    color:#0f766e;
    margin-bottom:50px;
}

.service-cards{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:30px;
}

.service-card{
    background:#ffffff;
    padding:30px;
    border-radius:18px;
    box-shadow:0 10px 30px rgba(0,0,0,0.08);
    text-align:center;
    transition:0.3s;
}

.service-card:hover{
    transform: translateY(-5px);
    box-shadow:0 12px 40px rgba(15,118,110,0.2);
}

.service-card img{
    width:80px;
    margin-bottom:20px;
}

.service-card h3{
    color:#0f766e;
    margin-bottom:15px;
}

.service-card p{
    color:#374151;
    font-size:15px;
}

/* Footer */
footer{
    background:#0f766e;
    color:#fff;
    padding:40px 20px;
    text-align:center;
    font-size:14px;
}
footer a{
    color:#5eead4;
    text-decoration:none;
}
footer a:hover{
    text-decoration:underline;
}

/* Responsive */
@media (max-width:992px){
    .service-cards{
        grid-template-columns:1fr 1fr;
    }
}
@media (max-width:768px){
    .service-cards{
        grid-template-columns:1fr;
    }
}
</style>
</head>

<body>

<!-- Header -->
<header>
    <div class="logo">
        <img src="../images/logo_app2.png" alt="MedicalServices Logo" class="logo-img">
       
    </div>
    <nav>
        <a href="index.php">Accueil</a>
        <a href="services.php">Services</a>
        <a href="#">À propos</a>
        <a href="contact.php">Contact</a>
        <a href="login.php" class="btn">Connexion</a>
    </nav>
</header>

<!-- Services Section -->
<section class="services-section">
    <h2>Nos Services Médicaux</h2>

    <div class="service-cards">
        <div class="service-card">
            <img src="https://cdn-icons-png.flaticon.com/512/2966/2966327.png" alt="Dossiers Patients">
            <h3>Gestion des Dossiers Patients</h3>
            <p>Centralisation des informations médicales de chaque patient pour un suivi complet.</p>
        </div>

        <div class="service-card">
            <img src="https://cdn-icons-png.flaticon.com/512/2920/2920244.png" alt="Admissions">
            <h3>Admissions</h3>
            <p>Gestion facile des entrées et sorties des patients avec historique clair et rapide.</p>
        </div>

        <div class="service-card">
            <img src="https://cdn-icons-png.flaticon.com/512/3050/3050525.png" alt="Traitements">
            <h3>Gestion des Traitements</h3>
            <p>Suivi des prescriptions et traitements administrés à chaque patient.</p>
        </div>

        <div class="service-card">
            <img src="https://cdn-icons-png.flaticon.com/512/2490/2490584.png" alt="Suivis">
            <h3>Suivis des Patients</h3>
            <p>Contrôle des rendez-vous, consultations et évolution médicale.</p>
        </div>

        <div class="service-card">
            <img src="https://cdn-icons-png.flaticon.com/512/3211/3211049.png" alt="Statistiques">
            <h3>Statistiques et Rapports</h3>
            <p>Analyse des données de soins pour améliorer la prise de décision médicale.</p>
        </div>

        <div class="service-card">
            <img src="https://cdn-icons-png.flaticon.com/512/3143/3143460.png" alt="Support">
            <h3>Support 24/7</h3>
            <p>Assistance continue pour résoudre les problèmes et répondre aux questions des utilisateurs.</p>
        </div>
    </div>
</section>

<!-- Footer -->
<footer>
    © <?= date('Y') ?> MedicalServices – Tous droits réservés | <a href="#">Politique de confidentialité</a>
</footer>

</body>
</html>
