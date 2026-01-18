<?php
session_start();
include("../config/connexion.php");

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'infirmier') {
    header("Location: ../login.php"); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_user = $_SESSION['user_id'];
    $id_admission = $_POST['id_admission'] ?? null;

    // Récupération dynamique de la prestation choisie
    // On récupère l'ID envoyé par le <select name="id_prestation"> du formulaire
    $id_prestation = !empty($_POST['id_prestation']) ? $_POST['id_prestation'] : null;

    $temperature = !empty($_POST['temperature']) ? $_POST['temperature'] : null;
    $tension     = !empty($_POST['tension']) ? htmlspecialchars($_POST['tension']) : null;
    $frequence   = !empty($_POST['frequence_c']) ? $_POST['frequence_c'] : null;
    $medicament  = htmlspecialchars($_POST['medicament'] ?? '');
    $observations = htmlspecialchars($_POST['observations'] ?? '');

    $date_soin = date('Y-m-d H:i:s');
    $quantite = 1;

    if ($id_admission && $id_prestation) {
        try {
            // L'INSERTION QUI DECLENCHE LE TRIGGER DE FACTURATION
            $sql = "INSERT INTO soins_patients (
                id_admission, 
                id_prestation, 
                quantite, 
                date_soin, 
                id_infirmier, 
                temperature, 
                tension, 
                frequence_cardiaque, 
                medicament, 
                observations
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $id_admission,
                $id_prestation,
                $quantite,
                $date_soin,
                $id_user,
                $temperature,
                $tension,
                $frequence,
                $medicament,
                $observations
            ]);

            header("Location: liste_patients_inf.php?status=success");
            exit;

        } catch (PDOException $e) {
            die("Erreur technique : " . $e->getMessage());
        }
    } else {
        die("Erreur : Données manquantes (Admission ou Prestation).");
    }
}
?>