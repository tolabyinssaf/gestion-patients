<?php
require_once '../config/connexion.php';

// Simuler la session si nécessaire
$nom_complet = "Espace Médical"; 

// Vérifier l'ID
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: list.php?error=ID+invalide");
    exit();
}

$id = intval($_GET['id']);

// Récupérer le traitement
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

// Logique de suppression/archivage
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt_del = $pdo->prepare("CALL sp_supprimer_traitement(?, ?)");
        $raison = !empty($_POST['raison']) ? $_POST['raison'] : "Suppression manuelle";
        $stmt_del->execute([$id, $raison]);
        
        $result = $stmt_del->fetch();
        
        if($result && isset($result['success']) && $result['success'] == 1) {
            header("Location: list.php?success=Archivage+réussi");
            exit();
        } else {
            $error = $result['message'] ?? "Erreur lors de l'archivage";
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
    <title>Archivage Traitement | MedCare</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #0f766e;
            --primary-light: #f0fdfa;
            --sidebar-bg: #0f172a;
            --bg-body: #f1f5f9;
            --white: #ffffff;
            --danger: #be123c;
            --danger-light: #fff1f2;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border: #cbd5e1;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: var(--bg-body); color: var(--text-main); }

        /* HEADER */
        header {
            background: var(--white);
            padding: 0 40px; height: 75px;
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid var(--border);
            position: fixed; top: 0; width: 100%; z-index: 1000;
        }
        .logo { height: 45px; }
        .user-pill {
            background: var(--primary-light); padding: 8px 18px; border-radius: 12px;
            display: flex; align-items: center; gap: 10px;
            font-size: 14px; font-weight: 600; color: var(--primary);
            border: 1px solid rgba(15, 118, 110, 0.2);
        }

        /* LAYOUT */
        .container { display: flex; padding-top: 75px; }
        
        .sidebar { 
            width: 260px; background: var(--sidebar-bg); 
            padding: 24px 16px; position: fixed; 
            height: calc(100vh - 75px); overflow-y: auto;
        }
        .sidebar h3 { color: rgba(255,255,255,0.3); font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px; margin: 20px 0 10px 12px; }
        .sidebar a { display: flex; align-items: center; gap: 12px; color: #94a3b8; text-decoration: none; padding: 12px 16px; border-radius: 10px; margin-bottom: 5px; transition: 0.2s; }
        .sidebar a:hover { background: rgba(255,255,255,0.05); color: #fff; }
        .sidebar a.active { background: var(--primary); color: #fff; }

        .main-content { flex: 1; padding: 60px; margin-left: 260px; display: flex; justify-content: center; }

        /* DELETE CARD */
        .delete-card {
            width: 100%; max-width: 550px;
            background: var(--white); border-radius: 24px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            overflow: hidden; border: 1px solid var(--border);
        }

        .card-top {
            background: var(--danger-light); padding: 40px 30px;
            text-align: center; border-bottom: 1px solid #fecdd3;
        }
        .icon-circle {
            width: 70px; height: 70px; background: var(--danger);
            color: white; border-radius: 50%; display: flex;
            align-items: center; justify-content: center;
            font-size: 30px; margin: 0 auto 20px;
            box-shadow: 0 10px 15px -3px rgba(190, 18, 60, 0.3);
        }
        .card-top h2 { color: var(--danger); font-size: 24px; font-weight: 800; }
        .card-top p { color: #9f1239; font-size: 14px; margin-top: 5px; font-weight: 500; }

        .card-body { padding: 30px; }

        .info-preview {
            background: var(--bg-body); border-radius: 16px;
            padding: 20px; margin-bottom: 25px; border: 1px dashed var(--border);
        }
        .info-item { display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 14px; }
        .info-item:last-child { margin-bottom: 0; }
        .info-item label { color: var(--text-muted); font-weight: 600; }
        .info-item span { color: var(--text-main); font-weight: 800; }

        .reason-label { display: block; margin-bottom: 10px; font-size: 14px; font-weight: 700; color: var(--sidebar-bg); }
        textarea {
            width: 100%; height: 100px; padding: 15px; border-radius: 12px;
            border: 2px solid var(--border); outline: none; transition: 0.3s;
            resize: none; font-size: 14px; background: #fff;
        }
        textarea:focus { border-color: var(--danger); box-shadow: 0 0 0 4px rgba(190, 18, 60, 0.1); }

        .actions { display: flex; gap: 15px; margin-top: 25px; }
        .btn {
            flex: 1; padding: 16px; border-radius: 12px; border: none;
            font-weight: 700; font-size: 15px; cursor: pointer;
            display: flex; align-items: center; justify-content: center; gap: 10px;
            transition: 0.3s; text-decoration: none;
        }
        .btn-cancel { background: #e2e8f0; color: var(--text-muted); }
        .btn-cancel:hover { background: #cbd5e1; color: var(--text-main); }
        
        .btn-delete { background: var(--danger); color: white; }
        .btn-delete:hover { background: #9f1239; transform: translateY(-2px); }

        .error-msg {
            background: #fee2e2; color: var(--danger);
            padding: 12px; border-radius: 10px; margin-bottom: 20px;
            font-size: 13px; font-weight: 600; text-align: center;
        }
    </style>
</head>
<body>

<header>
    <img src="../images/logo_app2.png" alt="MedCare" class="logo">
    <div class="user-pill">
        <i class="fas fa-user-md"></i>
        <span><?= htmlspecialchars($nom_complet) ?></span>
    </div>
</header>

<div class="container">
    <aside class="sidebar">
        <h3>Unité de Soins</h3>
        <a href="../connexio_utilisateur/dashboard_medecin.php"><i class="fa-solid fa-chart-line"></i> Vue Générale</a>
        <a href="../connexio_utilisateur/hospitalisation.php"><i class="fa-solid fa-bed-pulse"></i> Patients Admis</a>
        <a href="../connexio_utilisateur/patients.php"><i class="fa-solid fa-hospital-user"></i> Patients</a>
        <a href="list.php" class="active"><i class="fa-solid fa-file-prescription"></i> Traitements</a>
        <a href="../connexio_utilisateur/suivis.php"><i class="fa-solid fa-calendar-check"></i> Consultations</a>
        
        <h3>Analyse & Gestion</h3>
        <a href="../admission/statistique.php"><i class="fa-solid fa-chart-pie"></i> Statistiques</a>
        <a href="../connexio_utilisateur/archives.php"><i class="fa-solid fa-box-archive"></i> Archives</a>
        <a href="../connexio_utilisateur/profil_medcin.php"><i class="fa-solid fa-user-gear"></i> Profil</a>
        
        <div style="margin-top: 40px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 10px;">
            <a href="../connexio_utilisateur/deconnexion.php" style="color: #fda4af;"><i class="fa-solid fa-power-off"></i> Déconnexion</a>
        </div>
    </aside>

    <main class="main-content">
        <div class="delete-card">
            <div class="card-top">
                <div class="icon-circle">
                    <i class="fas fa-archive"></i>
                </div>
                <h2>Archivage du traitement</h2>
                <p>Cette action retirera le traitement de la liste active.</p>
            </div>

            <div class="card-body">
                <?php if(isset($error)): ?>
                    <div class="error-msg">
                        <i class="fas fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <div class="info-preview">
                    <div class="info-item">
                        <label>Patient :</label>
                        <span><?= htmlspecialchars($traitement['nom'] . ' ' . $traitement['prenom']) ?></span>
                    </div>
                    <div class="info-item">
                        <label>Date :</label>
                        <span><?= date('d/m/Y', strtotime($traitement['date_traitement'])) ?></span>
                    </div>
                    <div class="info-item">
                        <label>Médicament :</label>
                        <span><?= htmlspecialchars($traitement['medicament'] ?: 'Non spécifié') ?></span>
                    </div>
                </div>

                <form method="POST">
                    <label class="reason-label">Motif de l'archivage</label>
                    <textarea name="reason" placeholder="Pourquoi souhaitez-vous archiver ce traitement ?" required></textarea>

                    <div class="actions">
                        <a href="list.php" class="btn btn-cancel">
                            <i class="fas fa-arrow-left"></i> Retour
                        </a>
                        <button type="submit" class="btn btn-delete">
                            <i class="fas fa-check"></i> Confirmer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

</body>
</html>