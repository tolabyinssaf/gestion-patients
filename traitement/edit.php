<?php
require_once '../config/connexion.php';

// Vérifier l'ID
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: list.php?error=ID+invalide");
    exit();
}

$id = intval($_GET['id']);

// Récupérer le traitement avec la vue
$stmt = $pdo->prepare("SELECT * FROM v_traitements_complets WHERE id_traitement = ?");
$stmt->execute([$id]);
$traitement = $stmt->fetch();

if(!$traitement) {
    header("Location: list.php?error=Traitement+non+trouvé");
    exit();
}

$success = false;
$error = '';
$message = '';

if(isset($_POST['submit'])) {
    try {
        $id_patient = $_POST['id_patient'];
        $description = $_POST['description'];
        $date_traitement = $_POST['date_traitement'];
        $medicament = $_POST['medicament'];
        $suivi = $_POST['suivi'];
        
        $stmt = $pdo->prepare("CALL sp_modifier_traitement(?, ?, ?, ?, ?, ?)");
        $stmt->execute([$id, $id_patient, $description, $date_traitement, $medicament, $suivi]);
        
        $result = $stmt->fetch();
        
        if($result && isset($result['success']) && $result['success'] == 1) {
            $success = true;
            $message = $result['message'];
            
            // Rafraîchir les données
            $stmt = $pdo->prepare("SELECT * FROM v_traitements_complets WHERE id_traitement = ?");
            $stmt->execute([$id]);
            $traitement = $stmt->fetch();
        } else {
            $error = $result['message'] ?? 'Erreur inconnue lors de la modification';
        }
        
    } catch(PDOException $e) {
        $error = "Erreur SQL: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Traitement | MedCare</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #0f766e;
            --primary-light: #f0fdfa;
            --sidebar-bg: #0f172a;
            --bg-body: #f1f5f9;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --white: #ffffff;
            --border: #cbd5e1;
            --error: #dc2626;
            --success: #16a34a;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: var(--bg-body); color: var(--text-main); }

        /* Header */
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

        /* Layout */
        .container { display: flex; padding-top: 75px; }
        
        .sidebar { 
            width: 260px; background: var(--sidebar-bg); 
            padding: 24px 16px; position: fixed; 
            height: calc(100vh - 75px); overflow-y: auto;
        }
        .sidebar h3 { color: rgba(255,255,255,0.3); font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 20px; padding-left: 12px; }
        .sidebar a { display: flex; align-items: center; gap: 12px; color: #94a3b8; text-decoration: none; padding: 12px 16px; border-radius: 10px; margin-bottom: 5px; transition: 0.2s; }
        .sidebar a:hover { background: rgba(255,255,255,0.05); color: #fff; }
        .sidebar a.active { background: var(--primary); color: #fff; }

        /* Main Content */
        .main-content { flex: 1; padding: 40px; margin-left: 260px; }
        .breadcrumb { font-size: 13px; font-weight: 600; color: var(--text-muted); margin-bottom: 8px; text-align: center; text-transform: uppercase; }
        h1 { font-size: 30px; font-weight: 800; color: var(--sidebar-bg); margin-bottom: 30px; text-align: center; }

        /* Banner Info */
        .patient-banner {
            max-width: 900px; margin: 0 auto 25px;
            background: var(--white); padding: 20px 30px;
            border-radius: 15px; border: 1px solid var(--border);
            display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
        }
        .info-box { display: flex; flex-direction: column; }
        .info-box label { font-size: 11px; text-transform: uppercase; color: var(--text-muted); font-weight: 700; margin-bottom: 4px; }
        .info-box span { font-weight: 700; color: var(--primary); font-size: 15px; }

        /* Card Style */
        .card { 
            background: #f8fafc; border-radius: 20px; 
            border: 2px solid var(--primary); 
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.1);
            overflow: hidden; max-width: 900px; margin: 0 auto 30px;
        }
        .card-header { 
            background: var(--primary); padding: 20px 40px; 
            display: flex; align-items: center; gap: 15px; 
        }
        .card-header h2 { font-size: 20px; color: #fff; font-weight: 700; }
        .card-header i { color: #fff; font-size: 24px; } 

        form { padding: 40px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; }
        .full-width { grid-column: span 2; }

        .field-group { display: flex; flex-direction: column; gap: 8px; }
        .field-group label { font-size: 14px; font-weight: 800; color: var(--sidebar-bg); }
        .required::after { content: ' *'; color: var(--error); }

        .form-control { 
            width: 100%; padding: 14px 16px; 
            border: 2px solid #cbd5e1; border-radius: 12px; 
            font-size: 15px; font-weight: 600; background: var(--white);
        }
        .form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 4px rgba(15, 118, 110, 0.1); }

        /* Buttons */
        .btn-group { display: flex; gap: 15px; margin-top: 30px; }
        .btn { 
            flex: 1; padding: 16px; border: none; border-radius: 12px; 
            font-weight: 700; font-size: 15px; cursor: pointer; 
            display: flex; align-items: center; justify-content: center; gap: 10px; transition: 0.3s;
            text-decoration: none;
        }
        .btn-save { background: var(--sidebar-bg); color: white; }
        .btn-save:hover { background: var(--primary); transform: translateY(-2px); }
        .btn-delete { background: #fee2e2; color: var(--error); }
        .btn-delete:hover { background: var(--error); color: white; }

        /* History Table */
        .history-section { max-width: 900px; margin: 0 auto; }
        .history-title { font-size: 18px; font-weight: 800; color: var(--sidebar-bg); margin-bottom: 15px; display: flex; align-items: center; gap: 10px; }
        .table-container { background: white; border-radius: 15px; border: 1px solid var(--border); overflow: hidden; }
        .hist-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .hist-table th { background: #f8fafc; padding: 12px 15px; text-align: left; color: var(--text-muted); font-weight: 700; border-bottom: 1px solid var(--border); }
        .hist-table td { padding: 12px 15px; border-bottom: 1px solid var(--border); font-weight: 500; }
        .badge-field { background: #e2e8f0; padding: 4px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; }

        .alert { padding: 15px 40px; border-bottom: 1px solid var(--border); font-weight: 600; display: flex; align-items: center; gap: 10px; }
        .alert-error { background: #fee2e2; color: var(--error); }
        .alert-success { background: #dcfce7; color: var(--success); }
    </style>
</head>
<body>

<header>
    <img src="../images/logo_app2.png" alt="MedCare" class="logo">
    <div class="user-pill">
        <i class="fas fa-user-md"></i>
        <span>Espace Médical</span>
    </div>
</header>

<div class="container">
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
    </aside>

    <main class="main-content">
        <div class="breadcrumb">Dossier #<?= str_pad($traitement['id_traitement'], 5, '0', STR_PAD_LEFT) ?> / Modification</div>
        <h1>Mise à jour du traitement</h1>

        <div class="patient-banner">
            <div class="info-box"><label>Patient</label><span><?= htmlspecialchars($traitement['nom'] . ' ' . $traitement['prenom']) ?></span></div>
            <div class="info-box"><label>Âge / CIN</label><span><?= $traitement['age'] ?? '--' ?> ans / <?= $traitement['cin'] ?></span></div>
            <div class="info-box"><label>Création</label><span><?= date('d/m/Y', strtotime($traitement['date_creation'])) ?></span></div>
            <div class="info-box"><label>Dernière MAJ</label><span><?= date('d/m/Y H:i', strtotime($traitement['date_modification'])) ?></span></div>
        </div>

        <div class="card">
            <div class="card-header">
                <i class="fas fa-edit"></i>
                <h2>Édition de la prescription</h2>
            </div>

            <?php if($success || $error): ?>
                <div class="alert alert-<?= $success ? 'success' : 'error' ?>">
                    <i class="fas <?= $success ? 'fa-check-circle' : 'fa-circle-exclamation' ?>"></i>
                    <?= htmlspecialchars($success ? $message : $error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="editTraitementForm">
                <div class="form-grid">
                    <div class="field-group full-width">
                        <label for="id_patient" class="required">Patient</label>
                        <select name="id_patient" id="id_patient" class="form-control" required>
                            <?php
                            $stmt_p = $pdo->query("SELECT * FROM patients ORDER BY nom, prenom");
                            while($p = $stmt_p->fetch()):
                                $sel = ($p['id_patient'] == $traitement['id_patient']) ? "selected" : "";
                            ?>
                            <option value="<?= $p['id_patient'] ?>" <?= $sel ?>>
                                <?= htmlspecialchars($p['nom'] . ' ' . $p['prenom']) ?> (CIN: <?= $p['CIN'] ?>)
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="field-group">
                        <label for="date_traitement" class="required">Date du traitement</label>
                        <input type="date" name="date_traitement" class="form-control" value="<?= $traitement['date_traitement'] ?>" required max="<?= date('Y-m-d') ?>">
                    </div>

                    <div class="field-group">
                        <label for="medicament">Médicament(s)</label>
                        <input type="text" name="medicament" class="form-control" value="<?= htmlspecialchars($traitement['medicament'] ?? '') ?>">
                    </div>

                    <div class="field-group full-width">
                        <label for="description" class="required">Diagnostic et Traitement</label>
                        <textarea name="description" id="description" class="form-control" rows="5" required><?= htmlspecialchars($traitement['description']) ?></textarea>
                        <div style="text-align: right; font-size: 11px; color: var(--text-muted); font-weight: 700; margin-top: 5px;">
                            <span id="charCount"><?= strlen($traitement['description']) ?></span> / 2000 caractères
                        </div>
                    </div>

                    <div class="field-group full-width">
                        <label for="suivi">Instructions et Suivi</label>
                        <textarea name="suivi" class="form-control" rows="3"><?= htmlspecialchars($traitement['suivi'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="btn-group">
                    <button type="submit" name="submit" class="btn btn-save">
                        <i class="fas fa-save"></i> Enregistrer les modifications
                    </button>
                    <a href="delete.php?id=<?= $id ?>" class="btn btn-delete" onclick="return confirm('Supprimer définitivement ce traitement ?')">
                        <i class="fas fa-trash"></i> Supprimer
                    </a>
                </div>
                
                <div style="text-align: center; margin-top: 25px;">
                    <a href="list.php" style="color: var(--text-muted); text-decoration: none; font-weight: 700; font-size: 13px;">
                        <i class="fas fa-chevron-left"></i> Retourner à la liste sans modifier
                    </a>
                </div>
            </form>
        </div>

        <div class="history-section">
            <div class="history-title"><i class="fas fa-history"></i> Historique des modifications</div>
            <div class="table-container">
                <table class="hist-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Champ</th>
                            <th>Ancienne valeur</th>
                            <th>Nouvelle valeur</th>
                            <th>Auteur</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt_h = $pdo->prepare("SELECT * FROM historique_traitements WHERE id_traitement = ? ORDER BY date_modification DESC LIMIT 5");
                        $stmt_h->execute([$id]);
                        $hists = $stmt_h->fetchAll();
                        if($hists): foreach($hists as $h): ?>
                        <tr>
                            <td style="white-space:nowrap;"><?= date('d/m/Y H:i', strtotime($h['date_modification'])) ?></td>
                            <td><span class="badge-field"><?= htmlspecialchars($h['champ_modifie']) ?></span></td>
                            <td style="color:var(--text-muted);"><?= htmlspecialchars(mb_strimwidth($h['ancienne_valeur'], 0, 40, '...')) ?></td>
                            <td style="color:var(--primary);"><?= htmlspecialchars(mb_strimwidth($h['nouvelle_valeur'], 0, 40, '...')) ?></td>
                            <td><?= htmlspecialchars($h['utilisateur'] ?? 'Système') ?></td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr><td colspan="5" style="text-align:center; color:var(--text-muted); padding:20px;">Aucun historique disponible</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<script>
document.getElementById('description').addEventListener('input', function() {
    document.getElementById('charCount').textContent = this.value.length;
});

document.getElementById('editTraitementForm').addEventListener('submit', function(e) {
    if (!confirm("Voulez-vous vraiment appliquer ces modifications ?")) {
        e.preventDefault();
    }
});
</script>

</body>
</html>