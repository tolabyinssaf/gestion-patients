<?php
require_once '../config/connexion.php';
// requireLogin(); // Assurez-vous que cette fonction est active

$nom_complet = "Espace Médical"; // À remplacer par votre variable de session

// Vérifier l'ID
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: list.php?error=ID+invalide");
    exit();
}

$id = intval($_GET['id']);

// Récupérer le traitement avec les infos du patient pour l'affichage
$stmt = $pdo->prepare("
    SELECT t.*, p.nom, p.prenom 
    FROM traitements t
    LEFT JOIN patients p ON t.id_patient = p.id_patient
    WHERE t.id_traitement = ?
");
$stmt->execute([$id]);
$traitement = $stmt->fetch();

if(!$traitement) {
    header("Location: list.php?error=Traitement+non+trouvé");
    exit();
}

// Logique de suppression
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt_del = $pdo->prepare("CALL sp_supprimer_traitement(?, ?)");
        $raison = !empty($_POST['raison']) ? $_POST['raison'] : "Suppression manuelle";
        $stmt_del->execute([$id, $raison]);
        
        $result = $stmt_del->fetch();
        
        if($result['success']) {
            header("Location: list.php?success=" . urlencode($result['message']));
        } else {
            $error = $result['message'];
        }
    } catch(PDOException $e) {
        $error = "Erreur système : " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supprimer Traitement | MedCare</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0f766e; 
            --sidebar-bg: #0f172a;
            --bg-body: #f8fafc;
            --white: #ffffff;
            --danger: #e11d48;
            --header-height: 75px;
            --sidebar-width: 260px;
        }

        * { margin:0; padding:0; box-sizing:border-box; font-family: 'Inter', sans-serif; }
        body { background: var(--bg-body); color: #1e293b; }

        /* HEADER */
        header {
            background: var(--white);
            padding: 0 40px; 
            height: var(--header-height);
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid #e2e8f0;
            position: fixed; top: 0; left: 0; right: 0; z-index: 1000;
        }
        .logo { height: 45px; }
        .user-pill { background: #f0fdfa; padding: 8px 18px; border-radius: 12px; color: var(--primary); font-weight: 600; font-size: 14px; }

        /* SIDEBAR */
        .sidebar { 
            width: var(--sidebar-width); background: var(--sidebar-bg); 
            position: fixed; top: var(--header-height); left: 0; bottom: 0; 
            padding: 24px 16px; z-index: 999;
        }
        .sidebar h3 { color: rgba(255,255,255,0.3); font-size: 11px; text-transform: uppercase; margin: 20px 0 10px 12px; }
        .sidebar a { 
            display: flex; align-items: center; gap: 12px; color: #94a3b8; 
            text-decoration: none; padding: 12px 16px; border-radius: 10px; margin-bottom: 5px;
        }
        .sidebar a:hover { background: rgba(255,255,255,0.05); color: #fff; }
        .sidebar a.active { background: var(--primary); color: #fff; }

        /* CONTENT */
        .main-content { margin-left: var(--sidebar-width); margin-top: var(--header-height); padding: 40px; }

        .delete-container {
            max-width: 600px; margin: 0 auto; background: var(--white);
            border-radius: 20px; overflow: hidden; border: 1px solid #e2e8f0;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
        }

        .delete-header { background: var(--danger); color: white; padding: 30px; text-align: center; }
        .delete-header i { font-size: 40px; margin-bottom: 10px; }
        
        .delete-body { padding: 30px; }

        .info-box { 
            background: #f1f5f9; border-radius: 12px; padding: 20px; margin-bottom: 25px;
            border-left: 4px solid var(--danger);
        }
        .info-line { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 14px; }
        .info-line strong { color: #64748b; font-weight: 500; }
        .info-line span { font-weight: 700; color: #1e293b; }

        .warning-alert {
            background: #fffbeb; border: 1px solid #fde68a; color: #92400e;
            padding: 15px; border-radius: 10px; font-size: 13px; margin-bottom: 25px;
            display: flex; gap: 10px; align-items: center;
        }

        textarea {
            width: 100%; height: 100px; padding: 15px; border-radius: 10px;
            border: 2px solid #e2e8f0; outline: none; transition: 0.3s; margin-bottom: 20px;
        }
        textarea:focus { border-color: var(--danger); }

        .btn-group { display: flex; gap: 15px; }
        .btn {
            flex: 1; padding: 14px; border-radius: 10px; text-align: center;
            text-decoration: none; font-weight: 600; cursor: pointer; border: none;
        }
        .btn-cancel { background: #e2e8f0; color: #475569; }
        .btn-confirm { background: var(--danger); color: white; }
        .btn-confirm:hover { background: #be123c; }

    </style>
</head>
<body>

<header>
    <img src="../images/logo_app2.png" alt="MedCare" class="logo">
    <div class="user-pill"><i class="fas fa-user-md"></i> <?= $nom_complet ?></div>
</header>

<div class="wrapper">
    <aside class="sidebar">
        <h3 style="font-weight: 800;">Unité de Soins</h3>
        <a href="../connexio_utilisateur/dashboard_medecin.php"><i class="fa-solid fa-chart-line"></i> Vue Générale</a>
        <a href="../connexio_utilisateur/hospitalisation.php"><i class="fa-solid fa-bed-pulse"></i> Patients Admis</a>
        <a href="../connexio_utilisateur/patients.php"><i class="fa-solid fa-hospital-user"></i> Patients</a>
        <a href="list.php" class="active"><i class="fa-solid fa-file-prescription"></i> Traitements</a>
        <a href="../connexio_utilisateur/suivis.php"><i class="fa-solid fa-calendar-check"></i> Consultations</a>
        <h3 style="font-weight: 800;">Analyse & Gestion</h3>
        <a href="../admission/statistique.php"><i class="fa-solid fa-chart-pie"></i> Statistiques</a>
        <a href="../connexio_utilisateur/archives.php"><i class="fa-solid fa-box-archive"></i> Archives</a>
        <a href="../connexio_utilisateur/profil_medcin.php"><i class="fa-solid fa-user-gear"></i> Profil</a>
        <div style="margin-top: 40px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 10px;">
            <a href="../connexio_utilisateur/deconnexion.php" style="color: #fda4af;"><i class="fa-solid fa-power-off"></i> Déconnexion</a>
        </div>
    </aside>

    <main class="main-content">
        <div class="delete-container">
            <div class="delete-header">
                <i class="fas fa-exclamation-triangle"></i>
                <h2>Confirmer la suppression</h2>
            </div>

            <div class="delete-body">
                <div class="info-box">
                    <div class="info-line">
                        <strong>Traitement</strong>
                        <span>#<?= str_pad($traitement['id_traitement'], 4, '0', STR_PAD_LEFT) ?></span>
                    </div>
                    <div class="info-line">
                        <strong>Patient</strong>
                        <span><?= htmlspecialchars($traitement['nom'] . ' ' . $traitement['prenom']) ?></span>
                    </div>
                    <div class="info-line">
                        <strong>Médicament</strong>
                        <span><?= htmlspecialchars($traitement['medicament'] ?: 'N/A') ?></span>
                    </div>
                </div>

                <div class="warning-alert">
                    <i class="fas fa-info-circle"></i>
                    cette action archivera les données, elles ne seront plus visibles dans la liste active.
                </div>

                <form method="POST">
                    <label style="font-size: 13px; font-weight: 600; margin-bottom: 8px; display: block;">Raison de l'archivage :</label>
                    <textarea name="raison" placeholder="Ex: Erreur de saisie, Traitement terminé..."></textarea>

                    <div class="btn-group">
                        <a href="list.php" class="btn btn-cancel">Annuler</a>
                        <button type="submit" class="btn btn-confirm">Confirmer l'archivage</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

</body>
</html>