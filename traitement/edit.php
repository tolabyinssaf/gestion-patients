<?php
require_once '../config/connexion.php';

$id = $_GET['id'];

// Récupérer le traitement avec la vue
$stmt = $pdo->prepare("SELECT * FROM v_traitements_complets WHERE id_traitement = ?");
$stmt->execute([$id]);
$traitement = $stmt->fetch();

if(!$traitement) {
    die("<div class='alert alert-error'><i class='fas fa-exclamation-circle'></i> Traitement non trouvé !</div>");
}

if(isset($_POST['submit'])) {
    try {
        $pdo->beginTransaction();
        
        $id_patient = $_POST['id_patient'];
        $description = $_POST['description'];
        $date_traitement = $_POST['date_traitement'];
        $medicament = $_POST['medicament'];
        $suivi = $_POST['suivi'];
        
        // Appel de la procédure stockée pour la modification
        $stmt = $pdo->prepare("CALL sp_modifier_traitement(?, ?, ?, ?, ?, ?, @success, @message)");
        $stmt->execute([$id, $id_patient, $description, $date_traitement, $medicament, $suivi]);
        
        // Récupérer les résultats
        $result = $pdo->query("SELECT @success as success, @message as message")->fetch();
        
        if($result['success']) {
            $pdo->commit();
            $success = true;
            $message = $result['message'];
            
            // Rafraîchir les données
            $stmt = $pdo->prepare("SELECT * FROM v_traitements_complets WHERE id_traitement = ?");
            $stmt->execute([$id]);
            $traitement = $stmt->fetch();
        } else {
            $pdo->rollBack();
            $success = false;
            $error = $result['message'];
        }
        
    } catch(PDOException $e) {
        $pdo->rollBack();
        $success = false;
        $error = "Erreur: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier un traitement | Système Médical</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Mêmes styles que la page add.php - à conserver pour la cohérence */
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
            --shadow-hover: 0 8px 24px rgba(0, 0, 0, 0.12);
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
            padding: 20px;
        }

        .dashboard-container {
            max-width: 1000px;
            margin: 0 auto;
        }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 25px;
            background: var(--white);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            border-left: 5px solid var(--warning-color);
        }

        .header-content h1 {
            font-size: 28px;
            font-weight: 700;
            color: var(--secondary-color);
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header-content h1 i {
            color: var(--warning-color);
            background: rgba(243, 156, 18, 0.1);
            padding: 10px;
            border-radius: 10px;
        }

        .header-content p {
            color: var(--text-secondary);
            font-size: 15px;
        }

        .btn-primary, .btn-secondary, .btn-warning {
            padding: 14px 24px;
            border-radius: var(--radius-sm);
            font-weight: 600;
            font-size: 15px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: var(--transition);
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: var(--white);
            box-shadow: 0 4px 10px rgba(1, 162, 140, 0.25);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(1, 162, 140, 0.35);
        }

        .btn-secondary {
            background: var(--white);
            color: var(--text-secondary);
            border: 1.5px solid var(--border-color);
        }

        .btn-secondary:hover {
            background: var(--light-bg);
            transform: translateY(-2px);
        }

        .btn-warning {
            background: linear-gradient(135deg, var(--warning-color) 0%, #E67E22 100%);
            color: var(--white);
        }

        .btn-warning:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(243, 156, 18, 0.35);
        }

        .form-container {
            background: var(--white);
            border-radius: var(--radius);
            padding: 30px;
            box-shadow: var(--shadow);
        }

        .form-section {
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--primary-light);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--secondary-color);
        }

        .required::after {
            content: " *";
            color: var(--danger-color);
        }

        select, input[type="text"], input[type="date"], textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1.5px solid var(--border-color);
            border-radius: var(--radius-sm);
            font-size: 14px;
            transition: var(--transition);
            background: var(--white);
        }

        select:focus, input:focus, textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(1, 162, 140, 0.1);
        }

        textarea {
            min-height: 120px;
            resize: vertical;
            font-family: inherit;
        }

        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
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

        .alert-success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            border-left: 4px solid var(--success-color);
        }

        .alert-error {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            border-left: 4px solid var(--danger-color);
        }

        .info-card {
            background: var(--primary-light);
            border-radius: var(--radius-sm);
            padding: 20px;
            margin-bottom: 25px;
            border-left: 4px solid var(--accent-color);
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            font-size: 14px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-label {
            font-weight: 600;
            color: var(--primary-dark);
            font-size: 13px;
            margin-bottom: 4px;
        }

        .info-value {
            color: var(--text-primary);
            font-size: 15px;
        }

        .history-section {
            margin-top: 30px;
            padding: 20px;
            background: var(--light-bg);
            border-radius: var(--radius-sm);
        }

        .history-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--secondary-color);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .history-item {
            padding: 10px 15px;
            background: var(--white);
            border-radius: 6px;
            margin-bottom: 10px;
            border-left: 3px solid var(--text-secondary);
            font-size: 14px;
        }

        .history-date {
            color: var(--text-secondary);
            font-size: 12px;
            margin-top: 5px;
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
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <header class="dashboard-header">
            <div class="header-content">
                <h1><i class="fas fa-edit"></i> Modifier le Traitement</h1>
                <p>ID: #<?= str_pad($traitement['id_traitement'], 4, '0', STR_PAD_LEFT) ?> | 
                   Patient: <?= htmlspecialchars($traitement['nom'] . ' ' . $traitement['prenom']) ?></p>
            </div>
            <div class="header-actions">
                <a href="list.php" class="btn-secondary">
                    <i class="fas fa-arrow-left"></i> Retour à la liste
                </a>
            </div>
        </header>

        <?php if(isset($success)): ?>
            <div class="alert <?= $success ? 'alert-success' : 'alert-error' ?>">
                <i class="fas <?= $success ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                <span><?= $success ? $message : $error ?></span>
            </div>
        <?php endif; ?>

        <div class="info-card">
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Date de création:</span>
                    <span class="info-value"><?= date('d/m/Y H:i', strtotime($traitement['date_creation'])) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Dernière modification:</span>
                    <span class="info-value">
                        <?= $traitement['date_modification'] ? 
                            date('d/m/Y H:i', strtotime($traitement['date_modification'])) : 
                            'Jamais modifié' ?>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Âge du patient:</span>
                    <span class="info-value"><?= $traitement['age'] ?> ans</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Total traitements patient:</span>
                    <span class="info-value"><?= $traitement['total_traitements'] ?></span>
                </div>
            </div>
        </div>

        <div class="form-container">
            <form method="POST" id="editTraitementForm">
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-user-injured"></i>
                        Information Patient
                    </h3>
                    
                    <div class="form-group">
                        <label for="id_patient" class="required">Patient :</label>
                        <select name="id_patient" id="id_patient" required>
                            <?php
                            $stmt = $pdo->query("SELECT * FROM patients ORDER BY nom, prenom");
                            while($patient = $stmt->fetch()):
                                $selected = ($patient['id_patient'] == $traitement['id_patient']) ? "selected" : "";
                            ?>
                            <option value="<?= $patient['id_patient'] ?>" <?= $selected ?>>
                                <?= htmlspecialchars($patient['nom'] . ' ' . $patient['prenom']) ?> 
                                (<?= $patient['date_naissance'] ?>)
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-file-medical"></i>
                        Détails du Traitement
                    </h3>
                    
                    <div class="form-group">
                        <label for="description" class="required">Description :</label>
                        <textarea name="description" id="description" required rows="6"><?= 
                            htmlspecialchars($traitement['description']) ?></textarea>
                        <small style="color: var(--text-secondary); font-size: 13px;">
                            Caractères: <span id="charCount"><?= mb_strlen($traitement['description']) ?></span>/2000
                        </small>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="date_traitement" class="required">Date :</label>
                            <input type="date" name="date_traitement" id="date_traitement" 
                                   value="<?= $traitement['date_traitement'] ?>" required 
                                   max="<?= date('Y-m-d') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="medicament">Médicament :</label>
                            <input type="text" name="medicament" id="medicament" 
                                   value="<?= htmlspecialchars($traitement['medicament']) ?>" 
                                   placeholder="Nom du médicament">
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-clipboard-check"></i>
                        Suivi et Recommandations
                    </h3>
                    
                    <div class="form-group">
                        <label for="suivi">Notes de suivi :</label>
                        <textarea name="suivi" id="suivi" rows="5"><?= 
                            htmlspecialchars($traitement['suivi']) ?></textarea>
                    </div>
                </div>
                
                <?php
                // Afficher l'historique des modifications
                $historyStmt = $pdo->prepare("
                    SELECT * FROM historique_traitements 
                    WHERE id_traitement = ? 
                    ORDER BY date_modification DESC 
                    LIMIT 5
                ");
                $historyStmt->execute([$id]);
                $historique = $historyStmt->fetchAll();
                
                if($historique):
                ?>
                <div class="history-section">
                    <h4 class="history-title">
                        <i class="fas fa-history"></i>
                        Historique des modifications
                    </h4>
                    <?php foreach($historique as $h): ?>
                    <div class="history-item">
                        <strong>Modification le <?= date('d/m/Y H:i', strtotime($h['date_modification'])) ?></strong>
                        <br>
                        <small>Par: <?= $h['utilisateur'] ?></small>
                        <div class="history-date">
                            Champ: <?= $h['champ_modifie'] ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <div class="form-actions">
                    <button type="button" onclick="window.location.href='list.php'" class="btn-secondary">
                        <i class="fas fa-times"></i> Annuler
                    </button>
                    <button type="submit" name="submit" class="btn-warning">
                        <i class="fas fa-save"></i> Enregistrer les modifications
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Suivi des caractères
        document.getElementById('description').addEventListener('input', function() {
            const charCount = this.value.length;
            document.getElementById('charCount').textContent = charCount;
            
            if (charCount > 2000) {
                this.style.borderColor = 'var(--danger-color)';
            } else if (charCount > 1500) {
                this.style.borderColor = 'var(--warning-color)';
            } else {
                this.style.borderColor = 'var(--border-color)';
            }
        });
        
        // Validation de la date
        document.getElementById('date_traitement').addEventListener('change', function() {
            const selectedDate = new Date(this.value);
            const today = new Date();
            
            if (selectedDate > today) {
                alert('La date du traitement ne peut pas être dans le futur');
                this.value = '<?= $traitement['date_traitement'] ?>';
            }
        });
        
        // Confirmation avant soumission
        document.getElementById('editTraitementForm').addEventListener('submit', function(e) {
            const description = document.getElementById('description').value.trim();
            
            if (description.length < 10) {
                e.preventDefault();
                alert('Veuillez saisir une description plus détaillée (minimum 10 caractères)');
                document.getElementById('description').focus();
                return false;
            }
            
            if (!confirm('Confirmez-vous la modification de ce traitement ?')) {
                e.preventDefault();
                return false;
            }
            
            return true;
        });
        
        // Mise en évidence des modifications
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('editTraitementForm');
            const initialValues = {
                id_patient: '<?= $traitement['id_patient'] ?>',
                description: `<?= addslashes($traitement['description']) ?>`,
                date_traitement: '<?= $traitement['date_traitement'] ?>',
                medicament: `<?= addslashes($traitement['medicament']) ?>`,
                suivi: `<?= addslashes($traitement['suivi']) ?>`
            };
            
            const inputs = form.querySelectorAll('select, textarea, input[type="text"], input[type="date"]');
            
            inputs.forEach(input => {
                input.addEventListener('input', function() {
                    const fieldName = this.name;
                    const currentValue = this.value;
                    const originalValue = initialValues[fieldName];
                    
                    if (currentValue !== originalValue) {
                        this.style.borderColor = 'var(--warning-color)';
                        this.style.backgroundColor = 'rgba(243, 156, 18, 0.05)';
                    } else {
                        this.style.borderColor = 'var(--border-color)';
                        this.style.backgroundColor = 'var(--white)';
                    }
                });
                
                // Appliquer la vérification initiale
                const event = new Event('input');
                input.dispatchEvent(event);
            });
        });
    </script>
</body>
</html>