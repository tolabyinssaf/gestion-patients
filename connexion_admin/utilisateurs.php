<?php
session_start();
include("../config/connexion.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { 
    header("Location: ../login.php"); exit; 
}

// Logique de suppression avec Archivage Manuel
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // 1. On récupère les infos avant de supprimer pour l'archive
    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id_user = ? AND role != 'admin'");
    $stmt->execute([$id]);
    $userToArchive = $stmt->fetch();

    if ($userToArchive) {
        // 2. Insertion dans la table archive (assurez-vous que la table existe)
        $archive = $pdo->prepare("INSERT INTO archives_utilisateurs (id_user, nom, prenom, email, role, specialite, telephone, cin, matricule, date_suppression) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $archive->execute([
            $userToArchive['id_user'], 
            $userToArchive['nom'], 
            $userToArchive['prenom'], 
            $userToArchive['email'], 
            $userToArchive['role'], 
            $userToArchive['specialite'], 
            $userToArchive['telephone'], 
            $userToArchive['cin'], 
            $userToArchive['matricule']
        ]);

        // 3. Suppression de la table principale
        $pdo->prepare("DELETE FROM utilisateurs WHERE id_user = ?")->execute([$id]);
    }
    
    header("Location: utilisateurs.php?msg=deleted"); exit;
}

// Statistiques pour les Cards
$stats = [
    'total' => $pdo->query("SELECT COUNT(*) FROM utilisateurs")->fetchColumn(),
    'medecins' => $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE role='medecin'")->fetchColumn(),
    'infirmiers' => $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE role='infirmier'")->fetchColumn(),
    'autres' => $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE role NOT IN ('medecin', 'infirmier', 'admin')")->fetchColumn()
];

$users = $pdo->query("SELECT * FROM utilisateurs ORDER BY created_at DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Personnel | MedCare Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary: #0f766e; 
            --primary-light: #f0f9f9;
            --dark: #0f172a;
            --bg: #f8fafc;
        }
        
        body { background-color: var(--bg); font-family: 'Plus Jakarta Sans', sans-serif; color: #1e293b; margin: 0; }

        header { 
            background: #ffffff; padding: 0 40px; height: 75px; 
            display: flex; justify-content: space-between; align-items: center; 
            border-bottom: 1px solid #e2e8f0; position: fixed; width: 100%; top: 0; z-index: 1000; 
        }
        .user-pill { background: var(--primary-light); padding: 8px 18px; border-radius: 12px; color: var(--primary); font-weight: 600; display: flex; align-items: center; gap: 10px; }

        .wrapper { display: flex; padding-top: 75px; }
        .sidebar { width: 260px; background: var(--dark); height: calc(100vh - 75px); position: fixed; padding: 24px 16px; }
        .sidebar a { display: flex; align-items: center; gap: 12px; color: #94a3b8; text-decoration: none; padding: 12px 16px; border-radius: 10px; margin-bottom: 5px; transition: 0.3s; }
        .sidebar a:hover, .sidebar a.active { background: var(--primary); color: white; }
        
        .main-content { margin-left: 260px; padding: 40px; width: calc(100% - 260px); }

        .stat-card { 
            background: #fff; border-radius: 20px; padding: 1.5rem; border: none; 
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); transition: all 0.2s;
            cursor: pointer;
        }
        .stat-card:hover { transform: translateY(-5px); border: 1px solid var(--primary); }
        .icon-box { width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; }

        .table-container { 
            background: #fff; border-radius: 24px; padding: 1.5rem; 
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;
        }
        .table thead th { 
            background: #f8fafc; border-bottom: none; color: #64748b; 
            font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; padding: 1.2rem;
        }
        .table tbody td { padding: 1.2rem; border-bottom: 1px solid #f1f5f9; }
        
        .user-avatar { 
            width: 45px; height: 45px; border-radius: 12px; 
            background: var(--primary);
            color: white; display: flex; align-items: center; justify-content: center; font-weight: 700;
        }
        .role-badge { 
            padding: 6px 12px; border-radius: 10px; font-size: 0.7rem; font-weight: 700; 
            display: inline-flex; align-items: center; gap: 5px;
        }
        .bg-medecin { background: #ccfbf1; color: #115e59; }
        .bg-infirmier { background: #f0fdfa; color: #134e4a; }
        .bg-secretaire { background: #fef3c7; color: #b45309; }
        .bg-admin { background: #e2e8f0; color: #475569; }

        .btn-recruit {
            background: var(--primary); 
            border: none; color: white; border-radius: 14px;
            padding: 0.8rem 1.5rem; font-weight: 600; transition: 0.3s;
        }
        .btn-recruit:hover { background: #0d5a55; transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(15, 118, 110, 0.3); color: white; }
        
        /* Style pour la barre de recherche */
        .search-box {
            max-width: 350px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            padding: 10px 15px;
            background: white;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .search-box input { border: none; outline: none; width: 100%; font-size: 0.9rem; }
    </style>
</head>
<body>

<header>
    <img src="../images/logo_app2.png" alt="Logo" style="height: 45px;">
    <div class="user-pill">
        <i class="fa-solid fa-user-shield"></i>
        <span>ADMIN : <?= strtoupper($_SESSION['role'] ?? 'ADMIN') ?></span>
    </div>
</header>

<div class="wrapper">
 <aside class="sidebar">
        <a href="dashboard_admin.php" ><i class="fa-solid fa-chart-pie"></i> Vue Générale</a>
        <a href="utilisateurs.php" class="active"><i class="fa-solid fa-user-md"></i> Utilisateurs</a>
        <a href="prestations.php"  ><i class="fa-solid fa-list-check"></i> Actes & Tarifs</a>
        <a href="chambres.php"><i class="fa-solid fa-bed"></i> Gestion Chambres</a>
        <div style="height: 1px; background: rgba(255,255,255,0.1); margin: 20px 0;"></div>
        <a href="facturation_list.php"><i class="fa-solid fa-file-invoice-dollar"></i> Rapports Financiers</a>
        <a href="profil.php" class="<?= basename($_SERVER['PHP_SELF']) == 'profil.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-user-gear"></i> Mon Profil
        </a>
        <a href="../logout.php" style="color: #fda4af; margin-top: 20px;"><i class="fa-solid fa-power-off"></i> Déconnexion</a>
    </aside>
    <main class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-5">
            <div class="search-box shadow-sm">
                <i class="fa-solid fa-magnifying-glass text-muted"></i>
                <input type="text" id="searchInput" placeholder="Rechercher par nom, matricule..." onkeyup="searchTable()">
            </div>
          
            <a href="ajouter_utilisateur.php" class="btn btn-recruit shadow-sm">
                <i class="fa-solid fa-plus-circle me-2"></i> Recruter un membre
            </a>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-md-3">
                <div class="stat-card" onclick="filterRole('all')">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="icon-box" style="background: #f0fdfa; color: var(--primary);"><i class="fa-solid fa-users"></i></div>
                        <span class="badge bg-light text-dark fw-bold small">Tous</span>
                    </div>
                    <h3 class="fw-bold mb-0"><?= $stats['total'] ?></h3>
                    <small class="text-muted small">Collaborateurs</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" onclick="filterRole('medecin')">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="icon-box" style="background: #f0fdfa; color: var(--primary);"><i class="fa-solid fa-user-doctor"></i></div>
                        <span class="badge bg-light fw-bold small" style="color: var(--primary);">Docs</span>
                    </div>
                    <h3 class="fw-bold mb-0"><?= $stats['medecins'] ?></h3>
                    <small class="text-muted small">Spécialistes</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" onclick="filterRole('infirmier')">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="icon-box" style="background: #f0fdfa; color: var(--primary);"><i class="fa-solid fa-stethoscope"></i></div>
                        <span class="badge bg-light fw-bold small" style="color: var(--primary);">Soins</span>
                    </div>
                    <h3 class="fw-bold mb-0"><?= $stats['infirmiers'] ?></h3>
                    <small class="text-muted small">Infirmiers</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" onclick="filterRole('autres')">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="icon-box" style="background: #f0fdfa; color: var(--primary);"><i class="fa-solid fa-id-badge"></i></div>
                        <span class="badge bg-light fw-bold small" style="color: var(--primary);">Admin</span>
                    </div>
                    <h3 class="fw-bold mb-0"><?= $stats['autres'] ?></h3>
                    <small class="text-muted small">Autres / Staff</small>
                </div>
            </div>
        </div>

        <div class="table-container shadow-sm border-0">
            <div class="table-responsive">
                <table class="table align-middle" id="userTable">
                    <thead>
                        <tr>
                            <th>IDENTITÉ</th>
                            <th>ID / MATRICULE</th>
                            <th>RÔLE & SPÉCIALITÉ</th>
                            <th>CONTACT</th>
                            <th class="text-end">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($users as $u): ?>
                        <tr class="user-row" data-role="<?= strtolower($u['role']) ?>">
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="user-avatar text-white fw-bold"><?= strtoupper(substr($u['nom'], 0, 1)) ?></div>
                                    <div>
                                        <div class="fw-bold text-dark"><?= $u['nom'].' '.$u['prenom'] ?></div>
                                        <div class="text-muted x-small" style="font-size: 0.75rem;">CIN: <?= $u['cin'] ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="fw-bold font-monospace" style="color: var(--primary);"><?= $u['matricule'] ?></span></td>
                            <td>
                                <span class="role-badge bg-<?= strtolower($u['role']) ?>">
                                    <i class="fa-solid fa-circle fa-2xs me-1 opacity-50"></i> <?= strtoupper($u['role']) ?>
                                </span>
                                <div class="text-muted small mt-1 ps-1"><?= $u['specialite'] ?: '<span class="opacity-50 italic">Généraliste</span>' ?></div>
                            </td>
                            <td>
                                <div class="small fw-medium"><i class="fa-regular fa-envelope me-2 opacity-50"></i><?= $u['email'] ?></div>
                                <div class="small text-muted"><i class="fa-solid fa-phone me-2 opacity-50"></i><?= $u['telephone'] ?></div>
                            </td>
                            <td class="text-end">
    <div class="dropdown">
        <button class="btn btn-light btn-sm rounded-pill px-3" data-bs-toggle="dropdown">
            <i class="fa-solid fa-ellipsis-vertical"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end border-0 shadow rounded-4">
            
            <?php if($u['role'] === 'medecin'): ?>
            <li>
                <a class="dropdown-item py-2" href="voir_patients_medecin.php?id=<?= $u['id_user'] ?>">
                    <i class="fa-solid fa-hospital-user me-2" style="color: #2563eb;"></i> Voir les patients
                </a>
            </li>
            <li><hr class="dropdown-divider opacity-50"></li>
            <?php endif; ?>

            <li>
                <a class="dropdown-item py-2" href="modifier_utilisateur.php?id=<?= $u['id_user'] ?>">
                    <i class="fa-solid fa-user-pen me-2" style="color: var(--primary);"></i> Modifier
                </a>
            </li>

            <?php if($u['role'] != 'admin'): ?>
            <li><hr class="dropdown-divider opacity-50"></li>
            <li>
                <a class="dropdown-item py-2 text-danger" href="javascript:void(0)" onclick="deleteConfirm(<?= $u['id_user'] ?>)">
                    <i class="fa-solid fa-trash-can me-2"></i> Supprimer
                </a>
            </li>
            <?php endif; ?>

        </ul>
    </div>
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
// Fonction de recherche textuelle
function searchTable() {
    let input = document.getElementById("searchInput").value.toUpperCase();
    let rows = document.querySelectorAll(".user-row");
    rows.forEach(row => {
        let text = row.innerText.toUpperCase();
        row.style.display = text.includes(input) ? "" : "none";
    });
}

// Fonction de filtrage par rôle
function filterRole(role) {
    const rows = document.querySelectorAll('.user-row');
    rows.forEach(row => {
        const userRole = row.getAttribute('data-role');
        if (role === 'all') {
            row.style.display = '';
        } else if (role === 'autres') {
            if (userRole !== 'medecin' && userRole !== 'infirmier' && userRole !== 'admin') {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        } else {
            row.style.display = (userRole === role) ? '' : 'none';
        }
    });
}

function deleteConfirm(id) {
    Swal.fire({
        title: 'Retirer ce membre ?',
        text: "Les données seront transférées vers les archives.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#0f766e',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Oui, confirmer',
        cancelButtonText: 'Annuler',
        customClass: { popup: 'rounded-5' }
    }).then((result) => {
        if (result.isConfirmed) { window.location.href = 'utilisateurs.php?delete=' + id; }
    });
}

<?php if(isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
    Swal.fire({ icon: 'success', title: 'Archivé', text: 'Le membre a été retiré et archivé.', showConfirmButton: false, timer: 1500 });
<?php endif; ?>
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>