<?php
include("../config/connexion.php");

// On vérifie que l'action est bien reçue
if(isset($_POST['action'])) {
    $id = $_POST['id'];

    // CAS 1 : MODIFICATION
    if($_POST['action'] == 'update') {
        $nom = $_POST['nom'];
        $cat = $_POST['cat'];
        $prix = $_POST['prix'];
        
        // Vérifiez bien que vos noms de colonnes sont corrects (id_prestation, nom_prestation, etc.)
        $stmt = $pdo->prepare("UPDATE prestations SET nom_prestation=?, categorie=?, prix_unitaire=? WHERE id_prestation=?");
        if($stmt->execute([$nom, $cat, $prix, $id])) {
            echo 'success';
        } else {
            echo 'error_db';
        }
    }

    // CAS 2 : SUPPRESSION
    if($_POST['action'] == 'delete') {
        $stmt = $pdo->prepare("DELETE FROM prestations WHERE id_prestation=?");
        if($stmt->execute([$id])) {
            echo 'success';
        } else {
            echo 'error_db';
        }
    }
}
?>