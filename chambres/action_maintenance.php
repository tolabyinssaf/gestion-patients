<?php
include "../config/connexion.php";
$id = $_GET['id'];
$etat = $_GET['etat'];

$stmt = $pdo->prepare("UPDATE chambres SET etat = ? WHERE id_chambre = ?");
$stmt->execute([$etat, $id]);

header("Location: room_view.php?id=" . $id);