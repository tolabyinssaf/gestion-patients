<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>MedCare | Gestion Médicale</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
/* ===== Global ===== */
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:"Segoe UI", sans-serif;
}



/* ===== Header / Navbar ===== */
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
    display: flex;
    align-items: center; /* aligne le texte avec l'image */
    gap: 20px;           /* espace entre logo et texte */
}

.logo-img {
    width: 250px;         /* largeur du logo */
    height: auto;         /* hauteur automatique pour garder les proportions */
}

.logo-text {
    font-size: 36px;       /* texte plus grand */
    font-weight: 700;
    color: #0f766e;
    letter-spacing: 1px;
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

/* Responsive Header */
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

/* ===== Hero ===== */
.hero{
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:80px 60px;
    background:linear-gradient(120deg,#e0f7f4,#ffffff);
    animation: fadeIn 0.6s ease-in-out;
}

.hero-text{
    width:50%;
}

.hero-text h1{
    font-size:44px;
    color:#0f766e;
    margin-bottom:20px;
}

.hero-text p{
    font-size:18px;
    margin-bottom:30px;
    color:#374151;
}

.hero-text a{
    padding:14px 26px;
    background:#0f766e;
    color:white;
    text-decoration:none;
    border-radius:8px;
    font-size:16px;
    transition:0.3s;
}

.hero-text a:hover{
    background:#115e59;
}

.hero img{
    width:420px;
}

/* ===== Features ===== */
.features{
    padding:70px 60px;
    background:#ffffff;
}

.features h2{
    text-align:center;
    margin-bottom:50px;
    font-size:32px;
    color:#0f766e;
}

.cards{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:30px;
}

.card{
    background:#f1f5f9;
    padding:30px;
    border-radius:12px;
    box-shadow:0 4px 12px rgba(0,0,0,0.08);
    text-align:center;
    transition:0.3s;
}

.card:hover{
    transform:translateY(-5px);
    box-shadow:0 8px 20px rgba(15,118,110,0.2);
}

.card img{
    width:80px;
    margin-bottom:20px;
}

.card h3{
    margin-bottom:15px;
    color:#0f766e;
}

/* ===== Call to action ===== */


.cta {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 60px 20px;
  background: transparent; /* <-- change ici */
}

body .cta-card{
  background: rgba(0, 0, 0, 0.8); /* fond noir transparent */
  backdrop-filter: blur(12px);    /* flou pour effet moderne */
  padding: 60px 40px;
  border-radius: 20px;
  text-align: center;              /* texte centré */
  color: #ffffff;                  /* texte blanc pour contraste */
  max-width: 600px;
  width: 100%;
  box-shadow: 0 15px 40px rgba(0,0,0,0.3);
  animation: float 6s ease-in-out infinite;
}

.cta-card h2 {
  font-size: 36px;
  margin-bottom: 20px;
}

.cta-card p {
  font-size: 18px;
  margin-bottom: 30px;
}

.cta-btn {
  display: inline-block;
  padding: 16px 32px;
  background: #fff;
  color: #0f766e;
  font-weight: bold;
  font-size: 16px;
  border-radius: 10px;
  text-decoration: none;
  transition: all 0.3s ease;
  box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.cta-btn:hover {
  background: #399284ff;
  color: #fff;
  box-shadow: 0 8px 20px rgba(0,0,0,0.3);
}

/* Animation flottante de la card */
@keyframes float {
  0% { transform: translateY(0); }
  50% { transform: translateY(-10px); }
  100% { transform: translateY(0); }
}

/* Responsive */
@media (max-width:768px){
  .cta-card {
    padding: 40px 20px;
  }
  .cta-card h2 {
    font-size: 28px;
  }
  .cta-card p {
    font-size: 16px;
  }
  .cta-btn {
    padding: 12px 24px;
    font-size: 14px;
  }
}




/* ===== Animations ===== */
@keyframes fadeIn{
    from{opacity:0; transform:translateY(20px);}
    to{opacity:1; transform:translateY(0);}
}

/* ===== Responsive général ===== */
@media (max-width:768px){
    .hero{
        flex-direction:column;
        text-align:center;
    }
    .hero-text, .hero img{
        width:100%;
    }
    .cards{
        grid-template-columns:1fr;
    }
}
</style>
</head>

<body>

<!-- ===== HEADER ===== -->
<header>
    <div class="logo">
        <img src="../images/logo_app2.png" alt="MedicalServices Logo" class="logo-img">
        
    </div>
    <nav>
        <a href="#">Accueil</a>
        <a href="services.php">Services</a>
        <a href="#">À propos</a>
        <a href="contact.php">Contact</a>
        <a href="login.php" class="btn">Connexion</a>
    </nav>
</header>


<!-- ===== HERO ===== -->
<section class="hero">
    <div class="hero-text">
        <h1>Plateforme de Gestion Médicale Moderne</h1>
        <p>
            MedicalServices vous permet de gérer efficacement les patients, rendez-vous,
            dossiers médicaux et prescriptions dans une seule application web sécurisée.
        </p>
        <a href="login.php">Accéder à l’application</a>
    </div>

    <img src="https://cdn-icons-png.flaticon.com/512/387/387561.png" alt="Medical App">
</section>

<!-- ===== FEATURES ===== -->
<!-- ===== FEATURES ===== -->
<section class="features">
    <h2>Fonctionnalités principales</h2>

    <div class="cards">

        <div class="card">
            <img src="https://cdn-icons-png.flaticon.com/512/2966/2966327.png">
            <h3>Dossiers des patients</h3>
            <p>
                Création et gestion des dossiers médicaux des patients avec
                historique complet et informations personnelles.
            </p>
        </div>

        <div class="card">
            <img src="https://cdn-icons-png.flaticon.com/512/2920/2920244.png">
            <h3>Admissions & consultations</h3>
            <p>
                Organisation des admissions des patients et gestion
                des consultations médicales en toute simplicité.
            </p>
        </div>

        <div class="card">
            <img src="https://cdn-icons-png.flaticon.com/512/3050/3050525.png">
            <h3>Traitements & suivi</h3>
            <p>
                Suivi des traitements, prescriptions et évolution
                de l’état de santé des patients.
            </p>
        </div>

    </div>
</section>


<!-- ===== CTA ===== -->
<section  class="cta" style="display:flex; justify-content:center; align-items:center; padding:60px 20px; background:transparent;">

  <div class="cta-card">
    <h2>Commencez dès aujourd’hui</h2>
    <p>Une solution fiable pour les professionnels de santé</p>
    <a href="login.php" class="cta-btn">Se connecter</a>
  </div>
</section>


<!-- ===== FOOTER ===== -->
<!-- ===== FOOTER STYLE OPENMRS ===== -->
<!-- ===== FOOTER STYLE OPENMRS ===== -->
<!-- ===== FOOTER AVEC IMAGE MEDICALE ET CARTE ===== -->
<!-- ===== FOOTER ===== -->
<!-- ===== FOOTER ===== -->
<!-- ===== FOOTER ===== -->
<footer style="
    background: url('../images/img5.jpg') center/cover no-repeat;
    position: relative;
    color: #fff;
    padding: 100px 20px 50px 20px;
">

    <!-- Carte noire centrée -->
    <div style="
        background: rgba(0,0,0,0.85);
        max-width: 1200px;
        margin: 0 auto;
        padding: 50px;
        border-radius: 20px;
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        box-shadow: 0 15px 40px rgba(0,0,0,0.6);
        position: relative;
        z-index: 2;
    ">
        <div style="flex:1 1 250px; margin-bottom:20px;">
            <h3 style="color:#5eead4; margin-bottom:20px;">MedicalServices</h3>
            <p>Email : contact@medicalservices.com</p>
            <p>Tél : +212 6 XX XX XX XX</p>
            <p>Adresse : Kénitra, Maroc</p>
        </div>

        <div style="flex:1 1 250px; margin-bottom:20px;">
            <h4 style="color:#fff; border-bottom:2px solid #5eead4; padding-bottom:5px;">Liens rapides</h4>
            <ul style="list-style:none; padding:0; margin-top:10px;">
                <li><a href="#" style="color:#ccc; text-decoration:none;">Accueil</a></li>
                <li><a href="#" style="color:#ccc; text-decoration:none;">Fonctionnalités</a></li>
                <li><a href="#" style="color:#ccc; text-decoration:none;">Services</a></li>
                <li><a href="login.php" style="color:#ccc; text-decoration:none;">Connexion</a></li>
            </ul>
        </div>

        <div style="flex:1 1 250px; margin-bottom:20px;">
            <h4 style="color:#fff; border-bottom:2px solid #5eead4; padding-bottom:5px;">À propos</h4>
            <p>Application web médicale complète pour gérer patients, rendez-vous et dossiers médicaux. Simple, rapide et sécurisé.</p>
        </div>
    </div>

    <!-- Footer bottom -->
    <div style="text-align:center; font-size:13px; color:black; margin-top:40px; font-weight:bold;">
        © 2025 MedicalServices – Tous droits réservés
    </div>

</footer>




</body>
</html>
