<?php
session_start();
include("../config/connexion.php");

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'infirmier') {
    header("Location: ../login.php"); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_user = $_SESSION['user_id'];
    $id_admission = $_POST['id_admission'] ?? null;

    // Récupération des données du formulaire
    $temperature = !empty($_POST['temperature']) ? $_POST['temperature'] : null;
    $tension     = !empty($_POST['tension']) ? htmlspecialchars($_POST['tension']) : null;
    $frequence   = !empty($_POST['frequence_c']) ? $_POST['frequence_c'] : null;
    $type_acte   = htmlspecialchars($_POST['type_acte'] ?? '');
    $medicament  = htmlspecialchars($_POST['medicament'] ?? '');
    $observations = htmlspecialchars($_POST['observations'] ?? '');

    // Données de gestion par défaut
    $date_soin = date('Y-m-d H:i:s');
    $id_prestation = 1; // ID par défaut pour "Soin infirmier"
    $quantite = 1;
    $statut_facturation = 'non_facture';

    if ($id_admission) {
        try {
          $sql = "INSERT INTO soins_patients (
            id_admission, 
            id_prestation, 
            quantite, 
            date_soin, 
            id_infirmier, 
            statut_facturation, 
            temperature, 
            tension, 
            frequence_cardiaque, 
            type_acte, 
            medicament, 
            observations
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $id_admission,
                $id_prestation,
                $quantite,
                $date_soin,
                $id_user,
                $statut_facturation,
                $temperature,
                $tension,
                $frequence,
                $type_acte,
                $medicament,
                $observations
            ]);

            // Redirection vers la liste des patients
            header("Location: liste_patients_inf.php?status=success");
            exit;

        } catch (PDOException $e) {
            die("Erreur technique : " . $e->getMessage());
        }
    }
}
?>