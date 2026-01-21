<?php
session_start();
include("../config/connexion.php");

$message = $_SESSION['login_error'] ?? "";
unset($_SESSION['login_error']); 

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    try {
        $stmt = $pdo->prepare("CALL login_user(?, ?)");
        $stmt->execute([$email, $password]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['nom_user'] = $user['nom'] . " " . $user['prenom'];

            switch ($user['role']) {
                case 'admin':
                    header("Location: ../connexion_admin/dashboard_admin.php"); exit;
                case 'medecin':
                    header("Location: dashboard_medecin.php"); exit;
                case 'secretaire':
                    header("Location: ../connexion_secretaire/dashboard_secretaire.php"); exit;
                case 'infirmier':
                    header("Location: ../connexion_infirmier/dashboard_infirmier.php"); exit;
            }
        }

    } catch (PDOException $e) {
        $_SESSION['login_error'] = "Email ou mot de passe incorrect";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Connexion | Gestion Patients</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<style>
/* ===== Global ===== */
body {
    margin:0;
    font-family:"Segoe UI", sans-serif;
    background: linear-gradient(135deg,#0f766e,#5eead4);
}

/* ===== Header ===== */
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
/* ===== Login Section ===== */
.login-section {
    display:flex;
    justify-content:center;
    align-items:center;
    padding:80px 20px;
    min-height: calc(100vh - 120px); /* header + footer */
}
.login-card {
    background:#fff;
    width:100%;
    max-width:480px;  /* légèrement plus grand */
    border-radius:20px;
    padding:45px 35px; /* plus confortable */
    box-shadow:0 20px 60px rgba(0,0,0,0.3);
    transition: transform 0.3s, box-shadow 0.3s;
}
.login-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 25px 70px rgba(0,0,0,0.35);
}

/* Login Header */
.login-header {
    text-align:center;
    margin-bottom:35px;
}
.login-header i {
    font-size:55px;
    color:#0f766e;
}
.login-header h3 {
    margin-top:10px;
    color:#0f766e;
    font-weight:700;
    font-size:22px;
}
.login-header p {
    color:#6b7280;
    font-size:14px;
}

/* Form */
.form-label {
    font-weight:500;
    color:#374151;
}
.form-control {
    border-radius:10px;
    padding:12px 15px;
    border:1px solid #d1d5db;
}
.form-control:focus {
    border-color:#0f766e;
    box-shadow:0 0 0 3px rgba(15,118,110,0.15);
}
.input-group-text {
    border-radius:10px 0 0 10px;
    background:#f9fafb;
    color:#0f766e;
}

/* Bouton */
.btn-login {
    background: linear-gradient(135deg,#0f766e,#14b8a6);
    color:#fff;
    border:none;
    border-radius:12px;
    padding:14px;
    width:100%;
    font-weight:600;
    margin-top:10px;
    transition: all 0.3s ease;
}
.btn-login:hover {
    background: linear-gradient(135deg,#115e59,#0d9488);
    transform: translateY(-1px);
    box-shadow:0 8px 25px rgba(0,0,0,0.3);
}

/* Message d'erreur */
.error-msg {
    background:#fdecea;
    color:#b02a37;
    padding:12px;
    border-radius:10px;
    margin-bottom:18px;
    text-align:center;
    border:1px solid #f5c2c7;
}

/* Footer */
footer {
    background:#0f766e;
    color:#fff;
    text-align:center;
    padding:20px 0;
    font-size:13px;
}
footer a { color:#fff; text-decoration:underline; }

/* Responsive */
@media (max-width:500px){
    .login-card {
        padding:30px 20px;
    }
    .login-header i { font-size:45px; }
    .login-header h3 { font-size:20px; }
}
</style>
<script>
function clearMessage() {
    const msg = document.getElementById('msg');
    if(msg) msg.style.display='none';
}
</script>
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
        <a href="contact.php">Contact</a>
        <a href="login.php" class="btn">Connexion</a>
    </nav>
</header>

<!-- Login -->
<section class="login-section">
    <div class="login-card">
        <div class="login-header">
            <i class="bi bi-heart-pulse-fill"></i>
            <h3>Connexion à l’espace MedicalServices</h3>
            <p>Entrez vos identifiants pour accéder à votre compte</p>
        </div>

        <?php if($message): ?>
            <div id="msg" class="error-msg"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Email</label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-envelope-fill"></i></span>
                    <input type="email" name="email" class="form-control" required oninput="clearMessage()">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Mot de passe</label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-lock-fill"></i></span>
                    <input type="password" name="password" class="form-control" required oninput="clearMessage()">
                </div>
            </div>
            <button type="submit" name="login" class="btn-login">
                <i class="bi bi-box-arrow-in-right"></i> Se connecter
            </button>
        </form>
    </div>
</section>

<!-- Footer -->
<footer>
    © <?= date('Y') ?> MedicalServices – Tous droits réservés
</footer>

</body>
</html>
