<?php
session_start();
include("../config/connexion.php");

// Vérification de la session et du rôle
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'medecin') {
    header("Location: login.php");
    exit;
}

// Récupération de l'ID de l'antécédent et de l'ID du patient pour la redirection
$id_ante = $_GET['id'] ?? null;
$id_patient = $_GET['id_p'] ?? null;

if ($id_ante && $id_patient) {
    try {
        // Préparation de la suppression
        // On vérifie aussi l'id_patient pour plus de sécurité
        $stmt = $pdo->prepare("DELETE FROM antecedents WHERE id_ante = ? AND id_patient = ?");
        $stmt->execute([$id_ante, $id_patient]);

        // Redirection vers l'onglet Antécédents (anamnese-tab)
        header("Location: dossier_patient.php?id=" . $id_patient . "#anamnese-tab");
        exit;
        
    } catch (PDOException $e) {
        die("Erreur lors de la suppression : " . $e->getMessage());
    }
} else {
    die("Paramètres manquants pour la suppression.");
}
?>