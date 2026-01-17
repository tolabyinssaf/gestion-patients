<?php
session_start();
include("../config/connexion.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération des données
    $id_adm = $_POST['id_admission'];
    $id_pat = $_POST['id_patient'];
    $id_user = $_SESSION['user_id'];
    $nb_jours = $_POST['nb_jours'];
    $prix_unit = $_POST['prix_unitaire_jour'];
    $frais_actes = $_POST['frais_actes'];
    $total = $_POST['montant_total'];
    $mode = $_POST['mode_paiement'];
    $couverture = $_POST['type_couverture'];
    
    // Génération du numéro de facture unique
    $numero_fac = "FAC-" . date('Ymd') . "-" . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);

    try {
        $pdo->beginTransaction();

        // 1. Insertion dans la table factures
        $sql_fac = "INSERT INTO factures (id_admission, id_patient, id_utilisateur, numero_facture, date_facture, 
                    nb_jours, prix_unitaire_jour, frais_actes_medicaux, montant_total, 
                    mode_paiement, type_couverture, statut_paiement) 
                    VALUES (?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, 'Payé')";
        
        $stmt = $pdo->prepare($sql_fac);
        $stmt->execute([$id_adm, $id_pat, $id_user, $numero_fac, $nb_jours, $prix_unit, $frais_actes, $total, $mode, $couverture]);
        $id_facture_generee = $pdo->lastInsertId();

        // 2. Mise à jour de l'admission : On ajoute la date de sortie pour libérer le patient
        $sql_adm = "UPDATE admissions SET date_sortie = NOW() WHERE id_admission = ?";
        $pdo->prepare($sql_adm)->execute([$id_adm]);

        $pdo->commit();
        
        // Redirection vers une page de succès ou l'impression
        header("Location: dashboard_secretaire.php?msg=paye_ok&fac=" . $id_facture_generee);
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        die("Erreur lors de la facturation : " . $e->getMessage());
    }
}