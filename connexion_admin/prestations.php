<?php
session_start();
include("../config/connexion.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { 
    header("Location: ../login.php"); exit; 
}

$user_id = $_SESSION['user_id'];
$stmt_admin = $pdo->prepare("SELECT nom, prenom FROM utilisateurs WHERE id_user = ?");
$stmt_admin->execute([$user_id]);
$admin = $stmt_admin->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT * FROM prestations ORDER BY nom_prestation ASC");
$prestations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prestations | MedCare Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        :root { 
            --primary: #10b981; 
            --primary-dark: #0f766e; 
            --sidebar-bg: #0f172a; 
            --bg-body: #f8fafc; 
            --border-color: #e2e8f0; 
            --table-outer-dark: #1e293b; /* Couleur du cadre extérieur */
        }
        
        body { background: var(--bg-body); font-family: 'Plus Jakarta Sans', sans-serif; display: flex; min-height: 100vh; margin: 0; }
        
        header { background: #ffffff; padding: 0 40px; height: 75px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); position: fixed; width: 100%; top: 0; z-index: 1050; }
        .sidebar { width: 260px; background: var(--sidebar-bg); height: 100vh; position: fixed; top: 0; padding: 100px 16px 24px; z-index: 1000; }
        .sidebar a { display: flex; align-items: center; gap: 12px; color: #94a3b8; text-decoration: none; padding: 12px 16px; border-radius: 10px; margin-bottom: 5px; transition: 0.3s; }
        .sidebar a:hover, .sidebar a.active { background: var(--primary-dark); color: #ffffff; }
        .main-wrapper { flex: 1; margin-left: 260px; padding-top: 75px; }
        .content-container { padding: 40px; }

        /* --- TABLEAU : CENTRE BLANC ET TEXTE NOIR --- */
        .table-card { 
            background: var(--table-outer-dark); /* Cadre extérieur sombre */
            border-radius: 24px; 
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
            padding: 15px; /* Épaisseur du cadre sombre */
        }

        .table-inner-white {
            background: #ffffff; /* Centre de la table en blanc */
            border-radius: 16px;
            overflow: hidden;
        }

        .table { margin-bottom: 0; background: #ffffff; }
        
        .table thead th { 
            background: #f8fafc; 
            padding: 20px; 
            font-size: 0.75rem; 
            text-transform: uppercase; 
            letter-spacing: 0.05em; 
            color: var(--primary-dark); 
            font-weight: 800;
            border-bottom: 2px solid #f1f5f9;
        }

        .table tbody td { 
            padding: 16px 20px; 
            vertical-align: middle; 
            border-bottom: 1px solid #f1f5f9;
            color: #000000 !important; /* TEXTE EN NOIR */
            font-weight: 700 !important; /* TEXTE EN BOLD */
        }
        
        /* Modern Inputs (Adaptés au fond BLANC et texte NOIR) */
        .inline-input { 
            border: 1px solid transparent; 
            background: transparent; 
            padding: 8px 12px; 
            font-weight: 700; /* BOLD */
            width: 100%; 
            transition: all 0.2s;
            color: #000000; /* Texte Noir */
            border-radius: 8px;
            outline: none;
        }
        .inline-input:hover { background: #f1f5f9; cursor: pointer; }
        .inline-input:focus { 
            border-color: var(--primary); 
            background: #ffffff; 
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
            cursor: text;
        }
        
        .price-input { 
            color: #000000 !important; 
            text-align: right;
        }

        .cat-select { 
            appearance: none;
            cursor: pointer;
        }

        /* Action Buttons */
        .action-btn { 
            width: 38px; height: 38px; 
            border-radius: 12px; 
            display: inline-flex; 
            align-items: center; 
            justify-content: center; 
            border: none; 
            transition: 0.2s; 
            cursor: pointer; 
        }
        .save-btn { background: #10b981; color: white; display: none; margin-right: 5px; }
        .delete-btn { background: #fee2e2; color: #ef4444; }
        .delete-btn:hover { background: #ef4444; color: white; }

        .btn-add-modern {
            background: var(--primary-dark);
            color: white;
            padding: 12px 24px;
            border-radius: 14px;
            font-weight: 700;
            text-decoration: none;
            transition: 0.3s;
        }
        .btn-add-modern:hover { background: var(--primary); color: white; }
    </style>
</head>
<body>

<header>
    <img src="../images/logo_app2.png" alt="Logo" style="height: 40px;">
    <div style="background: #f0fdfa; color: var(--primary-dark); padding: 8px 16px; border-radius: 12px; font-weight: 700;">
        <i class="fa-solid fa-user-shield me-2"></i>ADMIN : <?= strtoupper($admin['nom']) ?>
    </div>
</header>

<aside class="sidebar">
    <a href="dashboard_admin.php"><i class="fa-solid fa-chart-pie"></i> Vue Générale</a>
    <a href="utilisateurs.php"><i class="fa-solid fa-user-md"></i> Utilisateurs</a>
    <a href="prestations.php" class="active"><i class="fa-solid fa-list-check"></i> Actes & Tarifs</a>
    <a href="../chambres/gestion_chambres.php"><i class="fa-solid fa-bed"></i> Gestion Chambres</a>
    <div style="height: 1px; background: rgba(255,255,255,0.1); margin: 20px 0;"></div>
    <a href="facturation_list.php"><i class="fa-solid fa-file-invoice-dollar"></i> Rapports Financiers</a>
    <a href="archives.php"><i class="fa-solid fa-box-archive"></i> Archives</a>
    <a href="profil.php"><i class="fa-solid fa-user-gear"></i> Mon Profil</a>
    <a href="../connexio_utilisateur/login.php" style="color: #fda4af; margin-top: 20px;"><i class="fa-solid fa-power-off"></i> Déconnexion</a>
</aside>

<div class="main-wrapper">
    <main class="content-container">
        <div class="d-flex justify-content-between align-items-center mb-5">
            <div>
                <h2 style="font-weight: 800; color: #0f172a; margin-bottom: 4px;">Catalogue Prestations</h2>
                <p class="text-muted mb-0">Gestion de la tarification (Texte noir sur fond blanc).</p>
            </div>
            <a href="ajouter_prestation.php" class="btn-add-modern">
                <i class="fa-solid fa-plus me-2"></i> Nouvel Acte
            </a>
        </div>

        <div class="table-card">
            <div class="table-inner-white">
                <table class="table table-borderless mb-0">
                    <thead>
                        <tr>
                            <th style="width: 40%">Désignation de l'acte</th>
                            <th style="width: 25%">Catégorie</th>
                            <th style="width: 20%" class="text-end">Tarif Unitaire</th>
                            <th style="width: 15%" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($prestations as $p): ?>
                        <tr id="row-<?= $p['id_prestation'] ?>">
                            <td>
                                <input type="text" class="inline-input item-nom" value="<?= htmlspecialchars($p['nom_prestation']) ?>" data-id="<?= $p['id_prestation'] ?>">
                            </td>
                            <td>
                                <select class="inline-input cat-select item-cat" data-id="<?= $p['id_prestation'] ?>">
                                    <option value="Consultation" <?= $p['categorie']=='Consultation'?'selected':'' ?>>Consultation</option>
                                    <option value="Laboratoire" <?= $p['categorie']=='Laboratoire'?'selected':'' ?>>Laboratoire</option>
                                    <option value="Radiologie" <?= $p['categorie']=='Radiologie'?'selected':'' ?>>Radiologie</option>
                                    <option value="Chirurgie" <?= $p['categorie']=='Chirurgie'?'selected':'' ?>>Chirurgie</option>
                                    <option value="Soins" <?= $p['categorie']=='Soins'?'selected':'' ?>>Soins</option>
                                </select>
                            </td>
                            <td class="text-end">
                                <div class="d-flex align-items-center justify-content-end">
                                    <input type="number" step="0.01" class="inline-input price-input item-prix" value="<?= $p['prix_unitaire'] ?>" data-id="<?= $p['id_prestation'] ?>">
                                    <span class="ms-2 fw-bold text-dark small">DH</span>
                                </div>
                            </td>
                            <td class="text-end">
                                <button class="action-btn save-btn" id="save-<?= $p['id_prestation'] ?>" onclick="updatePrestation(<?= $p['id_prestation'] ?>)" title="Enregistrer">
                                    <i class="fa-solid fa-check"></i>
                                </button>
                                <button class="action-btn delete-btn" onclick="confirmDelete(<?= $p['id_prestation'] ?>)" title="Supprimer">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<script>
    $(document).on('input change', '.inline-input', function() {
        let id = $(this).data('id');
        $(`#save-${id}`).fadeIn(300).css('display', 'inline-flex');
        $(`#row-${id}`).css('background-color', '#f0fdf4'); 
    });

    function updatePrestation(id) {
        let nom = $(`#row-${id} .item-nom`).val();
        let cat = $(`#row-${id} .item-cat`).val();
        let prix = $(`#row-${id} .item-prix`).val();

        $.ajax({
            url: 'ajax_prestation.php',
            type: 'POST',
            data: { action: 'update', id: id, nom: nom, cat: cat, prix: prix },
            success: function(res) {
                if (res.trim() === 'success') {
                    $(`#save-${id}`).fadeOut();
                    $(`#row-${id}`).css('background-color', 'transparent');
                    Swal.fire({ title: 'Mis à jour !', icon: 'success', timer: 1500, showConfirmButton: false });
                } else {
                    Swal.fire('Erreur', 'Erreur lors de la mise à jour', 'error');
                }
            }
        });
    }

    function confirmDelete(id) {
        Swal.fire({
            title: 'Supprimer cet acte ?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e11d48',
            confirmButtonText: 'Supprimer',
            cancelButtonText: 'Annuler'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'ajax_prestation.php',
                    type: 'POST',
                    data: { action: 'delete', id: id },
                    success: function(res) {
                        if(res.trim() === 'success') {
                            $(`#row-${id}`).fadeOut(400, function() { $(this).remove(); });
                            Swal.fire({ title: 'Supprimé !', icon: 'success', timer: 1500, showConfirmButton: false });
                        }
                    }
                });
            }
        })
    }
</script>
</body>
</html>