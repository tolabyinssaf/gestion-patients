<?php
require_once '../config/connexion.php';

// Utilisation d'une transaction pour l'ajout
if(isset($_POST['submit'])) {
    try {
        
        
        $id_patient = $_POST['id_patient'];
        $description = $_POST['description'];
        $date_traitement = $_POST['date_traitement'];
        $medicament = $_POST['medicament'];
        $suivi = $_POST['suivi'];
        
        // Appel de la procédure stockée
        $stmt = $pdo->prepare("CALL sp_ajouter_traitement(?, ?, ?, ?, ?, @id_traitement, @message)");
$stmt->execute([$id_patient, $description, $date_traitement, $medicament, $suivi]);
$stmt->closeCursor(); 
        
        // Récupérer les résultats
        $result = $pdo->query("SELECT @id_traitement as id, @message as message")->fetch();
        
        if($result['id'] > 0) {
            $success = true;
            $message = $result['message'];
        } else {
            $success = false;
            $error = $result['message'];
        }
        
    } catch(PDOException $e) {
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
    <title>Ajouter un traitement | Système Médical</title>
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
            border-left: 5px solid var(--primary-color);
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
            color: var(--primary-color);
            background: var(--primary-light);
            padding: 10px;
            border-radius: 10px;
        }

        .header-content p {
            color: var(--text-secondary);
            font-size: 15px;
        }

        .btn-primary, .btn-secondary {
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
            color: var(--primary-color);
            border: 1.5px solid var(--primary-color);
        }

        .btn-secondary:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
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

        .patient-card {
            background: var(--primary-light);
            border-radius: var(--radius-sm);
            padding: 15px;
            margin-bottom: 20px;
            border-left: 4px solid var(--primary-color);
        }

        .patient-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
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
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <header class="dashboard-header">
            <div class="header-content">
                <h1><i class="fas fa-plus-circle"></i> Ajouter un Traitement</h1>
                <p>Enregistrez un nouveau traitement médical</p>
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

        <div class="form-container">
            <form method="POST" id="traitementForm">
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-user-injured"></i>
                        Sélection du Patient
                    </h3>
                    
                    <div class="form-group">
                        <label for="id_patient" class="required">Patient :</label>
                        <select name="id_patient" id="id_patient" required onchange="afficherInfosPatient(this.value)">
                            <option value="">Sélectionnez un patient</option>
                            <?php
                            // Utilisation d'une requête préparée
                            $stmt = $pdo->query("SELECT * FROM patients ORDER BY nom, prenom");
                            while($patient = $stmt->fetch()):
                            ?>
                            <option value="<?= $patient['id_patient'] ?>" 
                                    data-nom="<?= htmlspecialchars($patient['nom']) ?>"
                                    data-prenom="<?= htmlspecialchars($patient['prenom']) ?>"
                                    data-naissance="<?= $patient['date_naissance'] ?>"
                                    data-telephone="<?= htmlspecialchars($patient['telephone']) ?>">
                                <?= htmlspecialchars($patient['nom'] . ' ' . $patient['prenom']) ?> 
                                (<?= $patient['date_naissance'] ?>)
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div id="patientInfo" class="patient-card" style="display: none;">
                        <h4>Informations du patient sélectionné</h4>
                        <div class="patient-info" id="patientDetails"></div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-file-medical"></i>
                        Détails du Traitement
                    </h3>
                    
                    <div class="form-group">
                        <label for="description" class="required">Description du traitement :</label>
                        <textarea name="description" id="description" 
                                  placeholder="Décrivez en détail le traitement à appliquer..." 
                                  required rows="5"></textarea>
                        <small style="color: var(--text-secondary); font-size: 13px;">
                            Caractères: <span id="charCount">0</span>/2000
                        </small>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="date_traitement" class="required">Date du traitement :</label>
                            <input type="date" name="date_traitement" id="date_traitement" 
                                   value="<?= date('Y-m-d') ?>" required max="<?= date('Y-m-d') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="medicament">Médicament(s) prescrit(s) :</label>
                            <input type="text" name="medicament" id="medicament" 
                                   placeholder="Nom du médicament, dosage, fréquence...">
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
                        <textarea name="suivi" id="suivi" 
                                  placeholder="Recommandations, précautions, effets secondaires observés..."
                                  rows="4"></textarea>
                        <small style="color: var(--text-secondary); font-size: 13px;">
                            Ces informations aideront pour le suivi médical
                        </small>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" onclick="window.location.href='list.php'" class="btn-secondary">
                        <i class="fas fa-times"></i> Annuler
                    </button>
                    <button type="submit" name="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Enregistrer le traitement
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Afficher les informations du patient sélectionné
        function afficherInfosPatient(patientId) {
            const select = document.getElementById('id_patient');
            const selectedOption = select.options[select.selectedIndex];
            const patientInfo = document.getElementById('patientInfo');
            const patientDetails = document.getElementById('patientDetails');
            
            if (patientId && selectedOption.dataset.nom) {
                // Calculer l'âge
                const dateNaissance = new Date(selectedOption.dataset.naissance);
                const today = new Date();
                let age = today.getFullYear() - dateNaissance.getFullYear();
                const m = today.getMonth() - dateNaissance.getMonth();
                if (m < 0 || (m === 0 && today.getDate() < dateNaissance.getDate())) {
                    age--;
                }
                
                patientDetails.innerHTML = `
                    <div class="info-item">
                        <span class="info-label">Nom complet:</span>
                        <span>${selectedOption.dataset.nom} ${selectedOption.dataset.prenom}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Âge:</span>
                        <span>${age} ans</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Date de naissance:</span>
                        <span>${selectedOption.dataset.naissance}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Téléphone:</span>
                        <span>${selectedOption.dataset.telephone || 'Non renseigné'}</span>
                    </div>
                `;
                patientInfo.style.display = 'block';
            } else {
                patientInfo.style.display = 'none';
            }
        }
        
        // Compter les caractères de la description
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
                this.value = today.toISOString().split('T')[0];
            }
        });
        
        // Soumission du formulaire avec confirmation
        document.getElementById('traitementForm').addEventListener('submit', function(e) {
            const description = document.getElementById('description').value.trim();
            
            if (description.length < 10) {
                e.preventDefault();
                alert('Veuillez saisir une description plus détaillée (minimum 10 caractères)');
                document.getElementById('description').focus();
                return false;
            }
            
            if (!confirm('Confirmez-vous l\'ajout de ce traitement ?')) {
                e.preventDefault();
                return false;
            }
            
            return true;
        });
        
        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('date_traitement').value = today;
            document.getElementById('date_traitement').max = today;
            
            // Simuler le changement pour afficher le compteur
            const descTextarea = document.getElementById('description');
            document.getElementById('charCount').textContent = descTextarea.value.length;
        });
    </script>
</body>
</html>