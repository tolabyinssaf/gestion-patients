<?php
include "../config/connexion.php";

$q = isset($_GET['q']) ? trim($_GET['q']) : '';

if($q !== ''){
    $stmt = $pdo->prepare("SELECT id_patient, nom, prenom, date_naissance, adresse, telephone 
                           FROM patients 
                           WHERE nom LIKE ? OR prenom LIKE ? 
                           ORDER BY nom ASC LIMIT 10");
    $stmt->execute(["%$q%", "%$q%"]);
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach($patients as $p){
        echo '<div class="patient-item" 
                   data-id="'.$p['id_patient'].'" 
                   data-nom="'.htmlspecialchars($p['nom']).'" 
                   data-prenom="'.htmlspecialchars($p['prenom']).'">
                <i class="bi bi-person-circle me-1 text-main"></i> '
                .htmlspecialchars($p['nom'].' '.$p['prenom']).'
              </div>';
    }
    echo '<div class="patient-item text-success fw-bold" data-id="nouveau">
            <i class="bi bi-plus-circle me-1"></i> Nouveau patient
          </div>';
}