<?php
session_start();
include("../config/connexion.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { 
    header("Location: ../login.php"); exit; 
}

// Logique de suppression avec Archivage Manuel
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id_user = ? AND role != 'admin'");
    $stmt->execute([$id]);
    $userToArchive = $stmt->fetch();

    if ($userToArchive) {
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
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary: #0d9488; 
            --primary-dark: #0f172a;
            --accent: #0d9488;
            --bg: #f1f5f9;
            --text-main: #0f172a;
            --text-muted: #475569;
        }
        
        body { background-color: var(--bg); font-family: 'Plus Jakarta Sans', sans-serif; color: var(--text-main); margin: 0; }

        header { 
            background: #ffffff; padding: 0 40px; height: 75px; 
            display: flex; justify-content: space-between; align-items: center; 
            border-bottom: 2px solid #e2e8f0; position: fixed; width: 100%; top: 0; z-index: 1000; 
        }
        .user-pill { background: var(--primary-dark); padding: 8px 18px; border-radius: 12px; color: #fff; font-weight: 700; display: flex; align-items: center; gap: 10px; }

        .wrapper { display: flex; padding-top: 75px; }
        .sidebar { width: 260px; background: var(--primary-dark); height: calc(100vh - 75px); position: fixed; padding: 24px 16px; box-shadow: 4px 0 10px rgba(0,0,0,0.1); }
        .sidebar a { display: flex; align-items: center; gap: 12px; color: #94a3b8; text-decoration: none; padding: 14px 16px; border-radius: 10px; margin-bottom: 5px; transition: 0.3s; font-weight: 500; }
        .sidebar a:hover, .sidebar a.active { background: var(--primary); color: white; box-shadow: 0 4px 12px rgba(13, 148, 136, 0.3); }
        
        .main-content { margin-left: 260px; padding: 40px; width: calc(100% - 260px); }

        .stat-card { 
            background: #fff; border-radius: 20px; padding: 1.8rem; border: none; 
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06); 
            transition: all 0.3s ease; cursor: pointer; height: 100%; border-left: 5px solid transparent;
        }
        .stat-card:hover { transform: translateY(-5px); border-left-color: var(--primary); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        .icon-box { width: 52px; height: 52px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; }

        .table-container { 
            background: #fff; border-radius: 20px; padding: 1.5rem; 
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); border: 1px solid #e2e8f0;
        }
        .table thead th { 
            background: #f8fafc; border-bottom: 2px solid #e2e8f0; color: var(--primary-dark); 
            font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; padding: 1.2rem; font-weight: 800;
        }
        
        /* --- REGLAGE DES IMAGES (AVATARS) --- */
        .user-avatar { 
            width: 45px; 
            height: 45px; 
            border-radius: 10px; 
            background: var(--primary-dark); 
            color: white; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-weight: 800; 
            font-size: 1rem; 
            border: 2px solid #fff; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            overflow: hidden; /* Empêche l'image de dépasser */
            flex-shrink: 0; /* Empêche l'avatar de se déformer */
        }
        .user-avatar img { 
            width: 100%; 
            height: 100%; 
            object-fit: cover; /* Recadre l'image proprement */
        }
        /* ------------------------------------ */

        .identite-principal { color: var(--text-main); font-weight: 700; font-size: 1rem; }
        .role-badge { padding: 6px 14px; border-radius: 8px; font-size: 0.75rem; font-weight: 800; display: inline-flex; align-items: center; gap: 6px; }
        .bg-medecin { background: #0d9488; color: #fff; }
        .bg-infirmier { background: #0284c7; color: #fff; }
        .bg-secretaire { background: #d97706; color: #fff; }
        .bg-admin { background: #475569; color: #fff; }

        .btn-recruit { background: var(--primary-dark); border: none; color: white; border-radius: 12px; padding: 0.9rem 1.8rem; font-weight: 700; transition: 0.3s; }
        .btn-recruit:hover { background: var(--primary); transform: translateY(-2px); color: white; box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
        
        .search-box { 
            max-width: 400px; border-radius: 14px; border: 2px solid #e2e8f0; 
            padding: 12px 18px; background: white; display: flex; align-items: center; gap: 12px; 
        }
        .search-box input { border: none; outline: none; width: 100%; font-size: 1rem; color: var(--text-main); font-weight: 600; }
    </style>
</head>
<body>

<header>
    <img src="../images/logo_app2.png" alt="Logo" style="height: 48px;">
    <div class="user-pill shadow-sm">
        <i class="fa-solid fa-user-shield"></i>
        <span>ADMIN : <?= strtoupper($_SESSION['role'] ?? 'ADMIN') ?></span>
    </div>
</header>

<div class="wrapper">
    <aside class="sidebar">
        <a href="dashboard_admin.php"><i class="fa-solid fa-chart-pie"></i> Vue Générale</a>
        <a href="utilisateurs.php" class="active"><i class="fa-solid fa-user-md"></i> Utilisateurs</a>
        <a href="prestations.php"><i class="fa-solid fa-list-check"></i> Actes & Tarifs</a>
        <a href="../chambres/gestion_chambres.php"><i class="fa-solid fa-bed"></i> Gestion Chambres</a>
        <div style="height: 1px; background: rgba(255,255,255,0.1); margin: 20px 0;"></div>
        <a href="facturation_list.php"><i class="fa-solid fa-file-invoice-dollar"></i> Rapports Financiers</a>
        <a href="archives.php"><i class="fa-solid fa-box-archive"></i> Archives</a>
        <a href="profil.php"><i class="fa-solid fa-user-gear"></i> Mon Profil</a>
        <a href="../connexio_utilisateur/login.php" style="color: #fb7185; margin-top: 20px; font-weight: 700;"><i class="fa-solid fa-power-off"></i> Déconnexion</a>
    </aside>

    <main class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-5">
            <div class="search-box shadow-sm">
                <i class="fa-solid fa-magnifying-glass text-primary"></i>
                <input type="text" id="searchInput" placeholder="Rechercher un membre..." onkeyup="searchTable()">
            </div>
            <a href="ajouter_utilisateur.php" class="btn btn-recruit shadow">
                <i class="fa-solid fa-plus-circle me-2"></i> RECRUTER UN MEMBRE
            </a>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-md-3">
                <div class="stat-card" onclick="filterRole('all')">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="icon-box" style="background: #f1f5f9; color: #0f172a;"><i class="fa-solid fa-users"></i></div>
                        <span class="badge bg-dark">Total</span>
                    </div>
                    <h2 class="fw-bold mb-0"><?= $stats['total'] ?></h2>
                    <small class="text-muted fw-bold">Collaborateurs actifs</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" onclick="filterRole('medecin')">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="icon-box" style="background: #ccfbf1; color: #0d9488;"><i class="fa-solid fa-user-doctor"></i></div>
                        <span class="badge" style="background: #0d9488;">Docs</span>
                    </div>
                    <h2 class="fw-bold mb-0"><?= $stats['medecins'] ?></h2>
                    <small class="text-muted fw-bold">Médecins spécialistes</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" onclick="filterRole('infirmier')">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="icon-box" style="background: #e0f2fe; color: #0284c7;"><i class="fa-solid fa-stethoscope"></i></div>
                        <span class="badge" style="background: #0284c7;">Soins</span>
                    </div>
                    <h2 class="fw-bold mb-0"><?= $stats['infirmiers'] ?></h2>
                    <small class="text-muted fw-bold">Corps infirmier</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" onclick="filterRole('autres')">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="icon-box" style="background: #fef3c7; color: #d97706;"><i class="fa-solid fa-id-badge"></i></div>
                        <span class="badge" style="background: #d97706;">Staff</span>
                    </div>
                    <h2 class="fw-bold mb-0"><?= $stats['autres'] ?></h2>
                    <small class="text-muted fw-bold">Administration & Staff</small>
                </div>
            </div>
        </div>

        <div class="table-container shadow border-0">
            <div class="table-responsive">
                <table class="table align-middle" id="userTable">
                    <thead>
                        <tr>
                            <th>IDENTITÉ DU PERSONNEL</th>
                            <th>MATRICULE / CIN</th>
                            <th>RÔLE & SPÉCIALITÉ</th>
                            <th>CONTACTS</th>
                            <th class="text-end">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($users as $u): 
                            $photoPath = "../uploads/photos/" . $u['photo'];
                            $hasPhoto = (!empty($u['photo']) && file_exists($photoPath));
                            $initials = strtoupper(substr($u['nom'], 0, 1) . substr($u['prenom'], 0, 1));
                        ?>
                        <tr class="user-row" data-role="<?= strtolower($u['role']) ?>">
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="user-avatar">
                                        <?php if($hasPhoto): ?>
                                            <img src="<?= $photoPath ?>" alt="Photo">
                                        <?php else: ?>
                                            <?= $initials ?>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <div class="identite-principal"><?= strtoupper($u['nom']).' '.$u['prenom'] ?></div>
                                        <div class="text-muted small fw-bold">ID: #<?= $u['id_user'] ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= $u['matricule'] ?></div>
                                <div class="text-muted small fw-bold">CIN: <?= $u['cin'] ?></div>
                            </td>
                            <td>
                                <span class="role-badge bg-<?= strtolower($u['role']) ?>">
                                    <i class="fa-solid fa-circle fa-2xs"></i> <?= strtoupper($u['role']) ?>
                                </span>
                                <div class="text-dark small mt-1 fw-bold"><?= $u['specialite'] ?: 'Généraliste' ?></div>
                            </td>
                            <td>
                                <div class="fw-bold text-dark" style="font-size: 0.9rem;"><?= $u['email'] ?></div>
                                <div class="text-muted fw-bold small"><?= $u['telephone'] ?></div>
                            </td>
                            <td class="text-end">
                                <div class="dropdown">
                                    <button class="btn btn-dark btn-sm rounded-pill px-3 shadow-sm" data-bs-toggle="dropdown">
                                        <i class="fa-solid fa-ellipsis-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg rounded-3 p-2">
                                        <?php if($u['role'] === 'medecin'): ?>
                                        <li><a class="dropdown-item py-2 fw-bold" href="voir_patients_medecin.php?id=<?= $u['id_user'] ?>"><i class="fa-solid fa-hospital-user me-2 text-primary"></i> Voir les patients</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <?php endif; ?>
                                        <li><a class="dropdown-item py-2 fw-bold" href="javascript:void(0)" onclick="editConfirm(<?= $u['id_user'] ?>)"><i class="fa-solid fa-user-pen me-2 text-info"></i> Modifier</a></li>
                                        <?php if($u['role'] != 'admin'): ?>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item py-2 fw-bold text-danger" href="javascript:void(0)" onclick="deleteConfirm(<?= $u['id_user'] ?>)"><i class="fa-solid fa-trash-can me-2"></i> Supprimer</a></li>
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
function searchTable() {
    let input = document.getElementById("searchInput").value.toUpperCase();
    let rows = document.querySelectorAll(".user-row");
    rows.forEach(row => {
        let text = row.innerText.toUpperCase();
        row.style.display = text.includes(input) ? "" : "none";
    });
}

function filterRole(role) {
    const rows = document.querySelectorAll('.user-row');
    rows.forEach(row => {
        const userRole = row.getAttribute('data-role');
        if (role === 'all') row.style.display = '';
        else if (role === 'autres') {
            row.style.display = (userRole !== 'medecin' && userRole !== 'infirmier' && userRole !== 'admin') ? '' : 'none';
        } else row.style.display = (userRole === role) ? '' : 'none';
    });
}

function editConfirm(id) {
    Swal.fire({
        title: 'Modifier ce membre ?',
        text: "Accès à la fiche d'édition du personnel.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#0f172a',
        confirmButtonText: 'Oui, modifier',
        cancelButtonText: 'Annuler'
    }).then((result) => {
        if (result.isConfirmed) { window.location.href = 'modifier_utilisateur.php?id=' + id; }
    });
}

function deleteConfirm(id) {
    Swal.fire({
        title: 'Retirer ce membre ?',
        text: "Les données seront déplacées vers les archives.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        confirmButtonText: 'Oui, archiver',
        cancelButtonText: 'Annuler'
    }).then((result) => {
        if (result.isConfirmed) { window.location.href = 'utilisateurs.php?delete=' + id; }
    });
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>