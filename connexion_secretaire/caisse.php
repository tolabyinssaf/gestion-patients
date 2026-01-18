<?php
session_start();
include("../config/connexion.php");

// Récupérer toutes les admissions en cours avec le montant actuel calculé par le Trigger
$query = "SELECT f.*, p.nom, p.prenom, a.date_admission, a.service 
          FROM factures f
          JOIN admissions a ON f.id_admission = a.id_admission
          JOIN patients p ON a.id_patient = p.id_patient
          WHERE a.statut = 'En cours' 
          ORDER BY f.date_facture DESC";
$factures = $pdo->query($query)->fetchAll();
?>

<div class="container mt-4">
    <h2 class="mb-4"><i class="fa-solid fa-file-invoice-dollar text-primary"></i> Suivi des Factures en Temps Réel</h2>
    
    <div class="card shadow-sm border-0">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
                <tr>
                    <th>Patient</th>
                    <th>Date Entrée</th>
                    <th>Frais Séjour</th>
                    <th>Frais Soins (Trigger)</th>
                    <th>Total Actuel</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($factures as $f): ?>
                <tr>
                    <td><strong><?= strtoupper($f['nom']) ?></strong> <?= $f['prenom'] ?></td>
                    <td><?= date('d/m/Y', strtotime($f['date_admission'])) ?></td>
                    <td><?= $f['nb_jours'] * $f['prix_unitaire_jour'] ?> DH</td>
                    <td class="text-primary fw-bold"><?= $f['frais_actes_medicaux'] ?> DH</td>
                    <td class="text-success fw-bold"><?= $f['montant_total'] ?> DH</td>
                    <td>
                        <a href="details_facture.php?id_adm=<?= $f['id_admission'] ?>" class="btn btn-sm btn-outline-primary">
                            <i class="fa-solid fa-eye"></i> Détails / Encaisser
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>