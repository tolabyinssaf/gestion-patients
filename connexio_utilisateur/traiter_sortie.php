<?php
session_start();
include("../config/connexion.php");

// 1. Vérification de sécurité
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'medecin') {
    header("Location: login.php");
    exit;
}

// 2. Vérifier si l'ID de l'admission est bien présent
if (isset($_GET['id_adm']) && !empty($_GET['id_adm'])) {
    
    $id_adm = $_GET['id_adm'];
    $date_actuelle = date('Y-m-d H:i:s');

    try {
        // 3. Mettre à jour l'admission : On fixe la date de sortie
        // Cela libère automatiquement la chambre pour le dashboard
        $stmt = $pdo->prepare("
            UPDATE admissions 
            SET date_sortie = ? 
            WHERE id_admission = ?
        ");
        
        if ($stmt->execute([$date_actuelle, $id_adm])) {
            // Redirection avec un message de succès (optionnel)
            header("Location: hospitalisation.php?msg=sortie_validee");
            exit;
        } else {
            echo "Erreur lors de la validation de la sortie.";
        }

    } catch (PDOException $e) {
        die("Erreur base de données : " . $e->getMessage());
    }

} else {
    // Si l'ID est manquant, retour à la liste
    header("Location: hospitalisation.php");
    exit;
}
?>