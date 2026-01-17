<?php
// contact.php
session_start();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Contact | MedicalServices</title>
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

/* ===== Contact form ===== */
.contact-section{
    padding:80px 20px;
    background: #f9fafb;
    display:flex;
    justify-content:center;
    align-items:center;
    min-height:calc(100vh - 120px); /* header + footer */
}

.contact-card{
    background:#ffffff;
    max-width:700px;
    width:100%;
    padding:50px 40px;
    border-radius:18px;
    box-shadow:0 15px 50px rgba(0,0,0,0.1);
}

.contact-card h2{
    color:#0f766e;
    margin-bottom:20px;
    text-align:center;
}

.contact-card p{
    color:#374151;
    text-align:center;
    margin-bottom:30px;
}

/* Form styling */
.form-control{
    border-radius:10px;
    padding:12px 15px;
    border:1px solid #d1d5db;
    margin-bottom:20px;
}
.form-control:focus{
    border-color:#0f766e;
    box-shadow:0 0 0 3px rgba(15,118,110,0.15);
}

.btn-contact{
    background: linear-gradient(135deg, #0f766e, #14b8a6);
    color:white;
    border-radius:12px;
    padding:12px;
    font-weight:600;
    width:100%;
    border:none;
    cursor:pointer;
    transition: all 0.3s ease;
}
.btn-contact:hover{
    background: linear-gradient(135deg, #115e59, #0d9488);
    transform: translateY(-1px);
    box-shadow:0 8px 20px rgba(15,118,110,0.3);
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
@media (max-width:768px){
    .contact-card{
        padding:30px 20px;
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
        <a href="../index.php">Accueil</a>
        <a href="services.php">Services</a>
        <a href="#">À propos</a>
        <a href="contact.php">Contact</a>
        <a href="login.php" class="btn">Connexion</a>
    </nav>
</header>

<!-- Contact Form -->
<section class="contact-section">
    <div class="contact-card">
        <h2>Contactez-nous</h2>
        <p>Envoyez-nous un message et nous vous répondrons dans les plus brefs délais.</p>

        <form method="POST" action="#">
            <input type="text" name="nom" placeholder="Nom complet" class="form-control" required>
            <input type="email" name="email" placeholder="Email" class="form-control" required>
            <input type="text" name="objet" placeholder="Objet" class="form-control" required>
            <textarea name="message" placeholder="Votre message" rows="6" class="form-control" required></textarea>
            <button type="submit" class="btn-contact">Envoyer</button>
        </form>
    </div>
</section>

<!-- Footer -->
<footer>
    © <?= date('Y') ?> MedicalServices – Tous droits réservés | <a href="#">Politique de confidentialité</a>
</footer>

</body>
</html>
