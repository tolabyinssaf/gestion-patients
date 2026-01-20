<?php
session_start();
include("../config/connexion.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Récupération des données envoyées par le formulaire
    $id_admission = $_POST['id_admission'];
    $id_patient   = $_POST['id_patient'];
    $nb_jours     = $_POST['nb_jours'];
    $montant      = $_POST['montant_total'];
    $mode         = $_POST['mode_paiement'];
    $couverture   = $_POST['type_couverture'];

    try {
        // Démarrer une transaction pour être sûr que tout s'enregistre bien
        $pdo->beginTransaction();

        // 2. Générer un numéro de facture unique (Ex: FAC-2026-10)
        $annee = date("Y");
        $num_facture = "FAC-" . $annee . "-" . str_pad($id_admission, 4, "0", STR_PAD_LEFT);

        // 3. ENREGISTRER DANS LA TABLE FACTURES
        $sql_facture = "INSERT INTO factures (id_admission, numero_facture, montant_total, date_facture, mode_paiement, type_couverture, nb_jours) 
                        VALUES (?, ?, ?, NOW(), ?, ?, ?)";
        $stmt = $pdo->prepare($sql_facture);
        $stmt->execute([$id_admission, $num_facture, $montant, $mode, $couverture, $nb_jours]);

        // 4. METTRE À JOUR L'ADMISSION (Clôturer le séjour)
        // On change le statut pour qu'elle n'apparaisse plus comme "En cours"
        $sql_update = "UPDATE admissions SET statut = 'Terminée' WHERE id_admission = ?";
        $pdo->prepare($sql_update)->execute([$id_admission]);

        // Valider toutes les opérations
        $pdo->commit();

        // 5. Rediriger vers la page de succès ou d'impression
        header("Location: caisse.php?msg=success&fac=" . $num_facture);
        exit;

    } catch (Exception $e) {
        // En cas d'erreur, on annule tout
        $pdo->rollBack();
        die("Erreur d'enregistrement : " . $e->getMessage());
    }
}