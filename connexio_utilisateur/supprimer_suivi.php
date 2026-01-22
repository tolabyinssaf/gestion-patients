<?php
session_start();
include("../config/connexion.php");

// Vérification sécurité
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'medecin') {
    header("Location: login.php");
    exit;
}

$id_medecin = $_SESSION['user_id'];
$id_suivi = isset($_GET['id_suivi']) ? (int)$_GET['id_suivi'] : null;
$id_patient = isset($_GET['id_patient']) ? (int)$_GET['id_patient'] : null;

if (!$id_suivi || !$id_patient) {
    die("ID suivi ou patient non spécifié.");
}

// Vérifier que le suivi appartient bien au médecin et au patient
$stmt_check = $pdo->prepare("SELECT id_suivi FROM suivis WHERE id_suivi = ? AND id_medecin = ? AND id_patient = ?");
$stmt_check->execute([$id_suivi, $id_medecin, $id_patient]);
$suivi = $stmt_check->fetch();

if ($suivi) {
    $stmt = $pdo->prepare("DELETE FROM suivis WHERE id_suivi = ?");
    $stmt->execute([$id_suivi]);
}

// Redirection vers le dossier patient
header("Location: dossier_patient.php?id_patient=$id_patient");
exit;
