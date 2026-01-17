<?php
include "../config/connexion.php";

$service = $_GET['service'] ?? '';

if(!$service) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("SELECT id_chambre, numero_chambre FROM chambres WHERE service = ? ORDER BY numero_chambre ASC");
$stmt->execute([$service]);
$chambres = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($chambres);