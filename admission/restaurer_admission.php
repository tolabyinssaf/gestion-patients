<?php
session_start();
include "../config/connexion.php";

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    try {
        $pdo->beginTransaction();

        // 1. Njibu l-ma3loumat mn l-archive
        $stmt = $pdo->prepare("SELECT * FROM admissions_archive WHERE id_admission = ?");
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data) {
            // 2. N-rj3u l-data l-table admissions l-asliya
            // Noto: Statut ghadi yrje3 'En cours' bach yban f la liste active
            $sql = "INSERT INTO admissions (id_admission, id_patient, id_medecin, id_chambre, date_admission, motif, service, statut) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'En cours')";
            
            $insert = $pdo->prepare($sql);
            $insert->execute([
                $data['id_admission'],
                $data['id_patient'],
                $data['id_medecin'],
                $data['id_chambre'],
                $data['date_admission'],
                $data['motif'],
                $data['service']
            ]);

            // 3. N-ms7u l-admission mn l-archive bach matb9ach m3awda
            $delete = $pdo->prepare("DELETE FROM admissions_archive WHERE id_admission = ?");
            $delete->execute([$id]);

            $pdo->commit();
            header("Location: archives_admissions.php?msg=restoration_success");
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Erreur lors de la restauration : " . $e->getMessage());
    }
}