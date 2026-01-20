<?php
session_start();
include "../config/connexion.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id_chambre'] ?? null;
    $numero = $_POST['numero'];
    $service = $_POST['service'];
    $bloc = $_POST['bloc'];
    $etage = $_POST['etage'];
    $capacite = $_POST['capacite'];
    $type_lit = $_POST['type_lit'];
    $oxigene = $_POST['oxigene'];

    try {
        if ($id) {
            // Appel de la procédure de Modification
            $sql = "CALL sp_UpdateChambre(?, ?, ?, ?, ?, ?, ?, ?)";
            $params = [$id, $numero, $service, $bloc, $etage, $capacite, $type_lit, $oxigene];
        } else {
            // Appel de la procédure d'Ajout
            $sql = "CALL sp_AddChambre(?, ?, ?, ?, ?, ?, ?)";
            $params = [$numero, $service, $bloc, $etage, $capacite, $type_lit, $oxigene];
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        header("Location: gestion_chambres.php?success=1");
        exit();

    } catch (PDOException $e) {
        die("Erreur lors de l'exécution de la procédure : " . $e->getMessage());
    }
}