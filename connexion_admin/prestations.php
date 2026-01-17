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
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        :root { 
            --primary: #0f766e; 
            --sidebar-bg: #0f172a; 
            --bg-body: #f8fafc; 
            --border-color: #e2e8f0; 
        }
        
        body { background: var(--bg-body); font-family: 'Plus Jakarta Sans', sans-serif; display: flex; min-height: 100vh; }
        
        /* Layout */
        header { background: #ffffff; padding: 0 40px; height: 75px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); position: fixed; width: 100%; top: 0; z-index: 1050; }
        .sidebar { width: 260px; background: var(--sidebar-bg); height: 100vh; position: fixed; top: 0; padding: 100px 16px 24px; z-index: 1000; }
        .sidebar a { display: flex; align-items: center; gap: 12px; color: #94a3b8; text-decoration: none; padding: 12px 16px; border-radius: 10px; margin-bottom: 5px; transition: 0.3s; }
        .sidebar a:hover, .sidebar a.active { background: var(--primary); color: #ffffff; }
        .main-wrapper { flex: 1; margin-left: 260px; padding-top: 75px; }
        .content-container { padding: 40px; }

        /* Elegant Table */
        .table-card { 
            background: white; 
            border-radius: 20px; 
            border: 1px solid var(--border-color); 
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05);
            overflow: hidden; 
        }
        .table thead th { 
            background: #f8fafc; 
            padding: 20px; 
            font-size: 0.75rem; 
            text-transform: uppercase; 
            letter-spacing: 0.05em; 
            color: #64748b;
            border-bottom: 1px solid var(--border-color);
        }
        .table tbody td { padding: 16px 20px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }
        
        /* Modern Inputs (Modify Inline) */
        .inline-input { 
            border: 1px solid transparent; 
            background: transparent; 
            padding: 8px 12px; 
            font-weight: 500; 
            width: 100%; 
            transition: all 0.2s;
            color: #1e293b;
            border-radius: 8px;
        }
        .inline-input:hover { background: #f1f5f9; cursor: pointer; }
        .inline-input:focus { 
            border-color: var(--primary); 
            background: white; 
            outline: none; 
            box-shadow: 0 0 0 4px rgba(15, 118, 110, 0.1);
            cursor: text;
        }
        
        .price-input { 
            color: var(--primary) !important; 
            font-weight: 700 !important; 
            width: 120px; 
            text-align: right;
        }

        /* Category Badges */
        .cat-select { font-size: 0.85rem; font-weight: 600; color: #475569; width: auto; }

        /* Action Buttons */
        .action-btn { 
            width: 38px; height: 38px; 
            border-radius: 10px; 
            display: inline-flex; 
            align-items: center; 
            justify-content: center; 
            border: none; 
            transition: all 0.2s; 
            cursor: pointer; 
        }
        .save-btn { background: #ecfdf5; color: #059669; display: none; margin-right: 5px; }
        .save-btn:hover { background: #059669; color: white; }
        .delete-btn { background: #fff1f2; color: #e11d48; }
        .delete-btn:hover { background: #e11d48; color: white; }

        .btn-add-modern {
            background: var(--primary);
            color: white;
            padding: 12px 24px;
            border-radius: 14px;
            font-weight: 700;
            text-decoration: none;
            box-shadow: 0 4px 12px rgba(15, 118, 110, 0.25);
            transition: 0.3s;
        }
        .btn-add-modern:hover { transform: translateY(-2px); color: white; opacity: 0.9; }
    </style>
</head>
<body>

<header>
    <img src="../images/logo_app2.png" alt="Logo" style="height: 40px;">
    <div style="background: #f0fdfa; color: var(--primary); padding: 8px 16px; border-radius: 12px; font-weight: 700;">
        <i class="fa-solid fa-user-shield me-2"></i>ADMIN : <?= strtoupper($admin['nom']) ?>
    </div>
</header>

 <aside class="sidebar">
        <a href="dashboard_admin.php" ><i class="fa-solid fa-chart-pie"></i> Vue Générale</a>
        <a href="utilisateurs.php" ><i class="fa-solid fa-user-md"></i> Utilisateurs</a>
        <a href="prestations.php" class="active" ><i class="fa-solid fa-list-check"></i> Actes & Tarifs</a>
        <a href="chambres.php"><i class="fa-solid fa-bed"></i> Gestion Chambres</a>
        <div style="height: 1px; background: rgba(255,255,255,0.1); margin: 20px 0;"></div>
        <a href="facturation_list.php"><i class="fa-solid fa-file-invoice-dollar"></i> Rapports Financiers</a>
        <a href="profil.php" class="<?= basename($_SERVER['PHP_SELF']) == 'profil.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-user-gear"></i> Mon Profil
        </a>
        <a href="../logout.php" style="color: #fda4af; margin-top: 20px;"><i class="fa-solid fa-power-off"></i> Déconnexion</a>
    </aside>

<div class="main-wrapper">
    <main class="content-container">
        <div class="d-flex justify-content-between align-items-center mb-5">
            <div>
                <h2 style="font-weight: 800; color: #0f172a; margin-bottom: 4px;">Catalogue Prestations</h2>
                <p class="text-muted mb-0">Modifiez les informations directement dans le tableau.</p>
            </div>
            <a href="ajouter_prestation.php" class="btn-add-modern">
                <i class="fa-solid fa-plus me-2"></i> Nouvel Acte
            </a>
        </div>

        <div class="table-card">
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
                                <span class="ms-2 fw-bold text-muted small">DH</span>
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
    </main>
</div>

<script>
    // Animation et affichage du bouton save lors d'un changement
    $(document).on('input change', '.inline-input', function() {
        let id = $(this).data('id');
        $(`#save-${id}`).fadeIn(300).css('display', 'inline-flex');
        $(`#row-${id}`).css('background-color', '#fffef2'); // Teinte très légère de modif
    });

    function updatePrestation(id) {
    let nom = $(`#row-${id} .item-nom`).val();
    let cat = $(`#row-${id} .item-cat`).val();
    let prix = $(`#row-${id} .item-prix`).val();

    Swal.fire({
        title: 'Enregistrer les modifications ?',
        text: "Les données de cet acte seront mises à jour.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#0f766e',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Oui, modifier',
        cancelButtonText: 'Annuler',
        customClass: { popup: 'rounded-4' }
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'ajax_prestation.php',
                type: 'POST',
                data: { action: 'update', id: id, nom: nom, cat: cat, prix: prix },
                success: function(res) {
                    if (res.trim() === 'success') {
                        $(`#save-${id}`).fadeOut();
                        $(`#row-${id}`).css('background-color', 'transparent');
                        
                        // ALERTE DE SUCCÈS AU CENTRE
                        Swal.fire({
                            title: 'Mis à jour !',
                            icon: 'success',
                            confirmButtonColor: '#0f766e',
                            customClass: { popup: 'rounded-4' }
                        });
                    } else {
                        Swal.fire('Erreur', 'Erreur lors de la mise à jour (' + res + ')', 'error');
                    }
                }
            });
        }
    });
}
    function confirmDelete(id) {
        Swal.fire({
            title: 'Supprimer cet acte ?',
            text: "Cette action est irréversible.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e11d48',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Oui, supprimer',
            cancelButtonText: 'Annuler',
            heightAuto: false, // Évite les sauts de page
            customClass: { popup: 'rounded-4', confirmButton: 'rounded-3', cancelButton: 'rounded-3' }
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'ajax_prestation.php',
                    type: 'POST',
                    data: { action: 'delete', id: id },
                    success: function(res) {
                        if(res === 'success') {
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