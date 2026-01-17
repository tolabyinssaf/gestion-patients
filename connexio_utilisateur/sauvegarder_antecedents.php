<?php
session_start();
include("../config/connexion.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_patient = $_POST['id_patient'];

    // 1. On nettoie les anciens antécédents médicaux (pour éviter les doublons lors de la mise à jour)
    $pdo->prepare("DELETE FROM antecedents WHERE id_patient = ? AND categorie = 'Médical'")->execute([$id_patient]);

    // 2. Enregistrement des pathologies médicales cochées
    if (isset($_POST['patho'])) {
        foreach ($_POST['patho'] as $nom => $data) {
            if ($data['active'] == '1') {
                $sql = "INSERT INTO antecedents (id_patient, categorie, nom_pathologie, description) VALUES (?, 'Médical', ?, ?)";
                $pdo->prepare($sql)->execute([$id_patient, $nom, $data['note']]);
            }
        }
    }

    // 3. Enregistrement d'une nouvelle chirurgie si remplie
    if (!empty($_POST['new_chir_nom'])) {
        $sql = "INSERT INTO antecedents (id_patient, categorie, nom_pathologie, date_evenement) VALUES (?, 'Chirurgical', ?, ?)";
        $pdo->prepare($sql)->execute([$id_patient, $_POST['new_chir_nom'], $_POST['new_chir_annee']]);
    }

    header("Location: dossier_patient.php?id=" . $id_patient);
    exit;
}