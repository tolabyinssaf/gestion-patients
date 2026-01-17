<?php
session_start();
include("../config/connexion.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'medecin') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

if (isset($_GET['id'])) {
    $id_patient = (int)$_GET['id'];

    $stmt = $pdo->prepare("DELETE FROM patients WHERE id_patient = ? AND id_medecin = ?");
    $stmt->execute([$id_patient, $user_id]);
}

header("Location: patients.php");
exit;
?>
