<?php
session_start();
include("../config/connexion.php");

$id_suivi = $_GET['id'] ?? null;
$patient = $_GET['patient'] ?? null;

if ($id_suivi) {
    $stmt = $pdo->prepare("
        UPDATE suivis 
        SET status = 'termine' 
        WHERE id_suivi = ?
    ");
    $stmt->execute([$id_suivi]);
}

header("Location: dossier_patient.php?id=$patient");
exit;
