<?php
include "../config/connexion.php";

if(!isset($_GET['id'])){
    header("Location: admissions_list.php");
    exit;
}

$id = $_GET['id'];
$user_id = null; // Ici tu peux récupérer l'id user connecté
$reason = "Suppression depuis liste des admissions";

// 1️⃣ Copier vers archive + log via la procédure sp_archive_admission
$stmt = $pdo->prepare("CALL sp_archive_admission(?, ?, ?)");
$stmt->execute([$id, $user_id, $reason]);

// Redirection après suppression
header("Location: admissions_list.php");
exit;
?>