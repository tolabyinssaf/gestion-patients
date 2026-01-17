<?php
require_once '../config/connexion.php';

$id = $_GET['id'];

if(!isset($id) || empty($id)) {
    header("Location: list.php?error=ID manquant");
    exit();
}

// Vérifier si le traitement existe
$stmt = $pdo->prepare("SELECT t.*, p.nom, p.prenom 
                       FROM traitements t 
                       JOIN patients p ON t.id_patient = p.id_patient 
                       WHERE t.id_traitement = ?");
$stmt->execute([$id]);
$traitement = $stmt->fetch();

if(!$traitement) {
    header("Location: list.php?error=Traitement non trouvé");
    exit();
}

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // ⚠️ CORRECTION ICI : UN SEUL PARAMÈTRE !
        // La procédure sp_supprimer_traitement n'a qu'1 paramètre
        $stmt = $pdo->prepare("CALL sp_supprimer_traitement(?)");
        $stmt->execute([$id]);
        
        // La procédure retourne un message avec SELECT
        $result = $stmt->fetch();
        $message = isset($result['message']) ? $result['message'] : 'Traitement supprimé avec succès';
        
        header("Location: list.php?success=" . urlencode($message));
        exit();
        
    } catch(PDOException $e) {
        $error = "Erreur système: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supprimer un traitement | Système Médical</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #01A28C;
            --primary-dark: #01796B;
            --primary-light: #E8F6F3;
            --secondary-color: #2C3E50;
            --accent-color: #3498DB;
            --danger-color: #E74C3C;
            --warning-color: #F39C12;
            --success-color: #2ECC71;
            --light-bg: #F8F9FA;
            --border-color: #E1E8ED;
            --text-primary: #2C3E50;
            --text-secondary: #7F8C8D;
            --white: #FFFFFF;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --radius: 12px;
            --radius-sm: 8px;
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%);
            color: var(--text-primary);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .confirmation-container {
            max-width: 600px;
            width: 100%;
            background: var(--white);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .confirmation-header {
            background: linear-gradient(135deg, var(--danger-color) 0%, #C0392B 100%);
            color: var(--white);
            padding: 30px;
            text-align: center;
        }

        .confirmation-header i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.9;
        }

        .confirmation-header h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .confirmation-header p {
            font-size: 16px;
            opacity: 0.9;
        }

        .confirmation-content {
            padding: 30px;
        }

        .traitement-info {
            background: var(--light-bg);
            border-radius: var(--radius-sm);
            padding: 20px;
            margin-bottom: 25px;
            border-left: 4px solid var(--danger-color);
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .info-label {
            font-weight: 600;
            color: var(--secondary-color);
        }

        .info-value {
            color: var(--text-primary);
            text-align: right;
            max-width: 60%;
            word-break: break-word;
        }

        .warning-box {
            background: rgba(231, 76, 60, 0.1);
            border: 1px solid rgba(231, 76, 60, 0.3);
            border-radius: var(--radius-sm);
            padding: 20px;
            margin-bottom: 25px;
            text-align: center;
        }

        .warning-box i {
            color: var(--danger-color);
            font-size: 32px;
            margin-bottom: 15px;
        }

        .warning-box h3 {
            color: var(--danger-color);
            margin-bottom: 10px;
        }

        .warning-box p {
            color: var(--text-secondary);
            font-size: 14px;
        }

        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .btn {
            padding: 14px 24px;
            border-radius: var(--radius-sm);
            font-weight: 600;
            font-size: 15px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: var(--transition);
            border: none;
            cursor: pointer;
            flex: 1;
        }

        .btn-danger {
            background: linear-gradient(135deg, var(--danger-color) 0%, #C0392B 100%);
            color: var(--white);
        }

        .btn-danger:hover {
            background: linear-gradient(135deg, #C0392B 0%, #A93226 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(231, 76, 60, 0.3);
        }

        .btn-secondary {
            background: var(--light-bg);
            color: var(--text-secondary);
            border: 1.5px solid var(--border-color);
        }

        .btn-secondary:hover {
            background: var(--white);
            transform: translateY(-2px);
            border-color: var(--text-secondary);
        }

        .alert {
            padding: 15px 20px;
            border-radius: var(--radius-sm);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideIn 0.3s ease;
        }

        .alert-error {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            border-left: 4px solid var(--danger-color);
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .form-actions {
                flex-direction: column;
            }
            
            .info-item {
                flex-direction: column;
                text-align: left;
            }
            
            .info-value {
                text-align: left;
                max-width: 100%;
                margin-top: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="confirmation-container">
        <div class="confirmation-header">
            <i class="fas fa-exclamation-triangle"></i>
            <h1>Confirmer la suppression</h1>
            <p>Cette action est irréversible</p>
        </div>
        
        <div class="confirmation-content">
            <?php if(isset($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>
            
            <div class="traitement-info">
                <div class="info-item">
                    <span class="info-label">ID Traitement:</span>
                    <span class="info-value">#<?= str_pad($traitement['id_traitement'], 4, '0', STR_PAD_LEFT) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Patient:</span>
                    <span class="info-value"><?= htmlspecialchars($traitement['nom'] . ' ' . $traitement['prenom']) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Date du traitement:</span>
                    <span class="info-value"><?= date('d/m/Y', strtotime($traitement['date_traitement'])) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Description:</span>
                    <span class="info-value"><?= htmlspecialchars(mb_substr($traitement['description'], 0, 100) . (mb_strlen($traitement['description']) > 100 ? '...' : '')) ?></span>
                </div>
                <?php if($traitement['medicament']): ?>
                <div class="info-item">
                    <span class="info-label">Médicament:</span>
                    <span class="info-value"><?= htmlspecialchars($traitement['medicament']) ?></span>
                </div>
                <?php endif; ?>
                <div class="info-item">
                    <span class="info-label">Date de création:</span>
                    <span class="info-value"><?= date('d/m/Y H:i', strtotime($traitement['date_creation'])) ?></span>
                </div>
            </div>
            
            <div class="warning-box">
                <i class="fas fa-radiation-alt"></i>
                <h3>Attention ! Action critique</h3>
                <p>La suppression de ce traitement est définitive. Toutes les données associées seront perdues et cette action sera enregistrée dans les journaux du système.</p>
            </div>
            
            <form method="POST" id="deleteForm">
                <div class="form-actions">
                    <a href="list.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Annuler
                    </a>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash-alt"></i> Confirmer la suppression
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('deleteForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const confirmationMessage = 'Êtes-vous ABSOLUMENT sûr de vouloir supprimer ce traitement ?\n\nCette action est IRREVERSIBLE.';
            
            if (confirm(confirmationMessage)) {
                // Ajouter un indicateur de chargement
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Suppression en cours...';
                submitBtn.disabled = true;
                
                // Soumettre le formulaire
                this.submit();
            }
        });
    </script>
</body>
</html>