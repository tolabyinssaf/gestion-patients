<?php
session_start();
include "../config/connexion.php";

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM admissions_archive WHERE id_admission = ?");
        $stmt->execute([$id]);
        header("Location: archives_admissions.php?status=deleted");
    } catch (Exception $e) {
        die("Erreur : " . $e->getMessage());
    }
}