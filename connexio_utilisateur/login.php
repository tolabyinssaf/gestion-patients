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

            switch ($user['role']) {
                case 'admin':
                    header("Location: dashboard_admin.php"); exit;
                case 'medecin':
                    header("Location: dashboard_medecin.php"); exit;
                case 'accueil':
                    header("Location: dashboard_accueil.php"); exit;
                case 'infirmier':
                    header("Location: dashboard_infirmier.php"); exit;
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

<!-- Bootstrap + Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

<style>
body {
    background: linear-gradient(135deg, #01A28C, #3BC7B3);
    height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: 'Segoe UI', sans-serif;
}

.login-card {
    background: #FFFFFF;
    width: 420px;
    border-radius: 15px;
    box-shadow: 0 15px 40px rgba(0,0,0,0.15);
    padding: 30px;
}

.login-header {
    text-align: center;
    margin-bottom: 25px;
}

.login-header i {
    font-size: 55px;
    color: #01A28C;
}

.login-header h3 {
    margin-top: 10px;
    color: #01A28C;
}

.login-header p {
    color: #737978;
    font-size: 14px;
}

.form-control {
    border-radius: 10px;
    padding: 10px;
}

.btn-login {
    background: #01A28C;
    color: white;
    border-radius: 10px;
    padding: 10px;
    font-weight: bold;
    transition: 0.3s;
}

.btn-login:hover {
    background: #018a78;
}

.error-msg {
    background: #fdecea;
    color: #b02a37;
    padding: 10px;
    border-radius: 8px;
    font-size: 14px;
    margin-bottom: 15px;
    text-align: center;
}
</style>

<script>
function clearMessage() {
    const msg = document.getElementById('msg');
    if (msg) msg.style.display = 'none';
}
</script>
</head>

<body>

<div class="login-card">
    <div class="login-header">
        <i class="bi bi-heart-pulse-fill"></i>
        <h3>Gestion des Patients</h3>
        <p>Connexion sécurisée à votre espace</p>
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

        <button type="submit" name="login" class="btn btn-login w-100">
            <i class="bi bi-box-arrow-in-right"></i> Se connecter
        </button>
    </form>

    <div class="text-center mt-3 text-muted" style="font-size:13px;">
        © <?= date('Y') ?> Application médicale
    </div>
</div>

</body>
</html>
