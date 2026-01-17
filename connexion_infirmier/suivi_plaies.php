<?php
session_start();
include("../config/connexion.php");

$id_adm = $_GET['id_adm'] ?? null;
$id_pat = $_GET['id_pat'] ?? null;

if (!$id_adm) die("Accès direct refusé.");

// Récupération des données patient
$stmt = $pdo->prepare("SELECT p.nom, p.prenom FROM admissions a JOIN patients p ON a.id_patient = p.id_patient WHERE a.id_admission = ?");
$stmt->execute([$id_adm]);
$patient = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>MedCare | Suivi des Plaies</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root { --primary: #0d9488; --secondary: #f43f5e; }
        body { background: #f1f5f9; font-family: 'Plus Jakarta Sans', sans-serif; }
        .upload-zone {
            border: 2px dashed #cbd5e1;
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            background: white;
            cursor: pointer;
            transition: 0.3s;
        }
        .upload-zone:hover { border-color: var(--primary); background: #f0fdfa; }
        .preview-img { max-width: 100%; border-radius: 15px; display: none; margin-top: 15px; }
        .btn-save { background: var(--primary); color: white; border-radius: 12px; font-weight: 700; padding: 12px; }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7">
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <h3 class="fw-bold"><i class="fa-solid fa-camera-retro text-primary me-2"></i> Suivi de Plaie</h3>
                <p class="text-muted">Patient : <?= strtoupper($patient['nom']) ?> <?= $patient['prenom'] ?></p>
                <hr>

                <form action="save_plaie.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id_adm" value="<?= $id_adm ?>">
                    <input type="hidden" name="id_pat" value="<?= $id_pat ?>">

                    <div class="mb-4">
                        <label class="form-label fw-bold">Localisation de la plaie</label>
                        <select name="localisation" class="form-select border-0 bg-light">
                            <option>Sacrum (Escarre)</option>
                            <option>Talon droit/gauche</option>
                            <option>Abdomen (Post-op)</option>
                            <option>Jambe (Ulcère)</option>
                            <option>Autre...</option>
                        </select>
                    </div>

                    <div class="upload-zone" onclick="document.getElementById('fileInput').click()">
                        <i class="fa-solid fa-cloud-arrow-up fa-3x text-muted mb-3"></i>
                        <h5>Cliquez pour prendre une photo</h5>
                        <p class="small text-muted">Format JPG ou PNG</p>
                        <input type="file" name="photo_plaie" id="fileInput" accept="image/*" capture="environment" hidden onchange="previewFile()">
                        <img id="preview" class="preview-img">
                    </div>

                    <div class="mt-4">
                        <label class="form-label fw-bold">Aspect de la plaie</label>
                        <div class="d-flex gap-2">
                            <input type="radio" class="btn-check" name="aspect" id="asp1" value="Saine" checked>
                            <label class="btn btn-outline-success btn-sm rounded-pill" for="asp1">Saine</label>
                            
                            <input type="radio" class="btn-check" name="aspect" id="asp2" value="Inflammatoire">
                            <label class="btn btn-outline-warning btn-sm rounded-pill" for="asp2">Inflammatoire</label>
                            
                            <input type="radio" class="btn-check" name="aspect" id="asp3" value="Nécrotique">
                            <label class="btn btn-outline-danger btn-sm rounded-pill" for="asp3">Nécrotique</label>
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="form-label fw-bold">Protocole appliqué</label>
                        <textarea name="protocole" class="form-control border-0 bg-light" rows="3" placeholder="Ex: Nettoyage sérum phy + Pansement hydrocolloïde"></textarea>
                    </div>

                    <button type="submit" class="btn btn-save w-100 mt-4">Enregistrer le soin</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function previewFile() {
        const preview = document.getElementById('preview');
        const file = document.getElementById('fileInput').files[0];
        const reader = new FileReader();
        reader.onloadend = function () { preview.src = reader.result; preview.style.display = 'block'; }
        if (file) reader.readAsDataURL(file);
    }
</script>
</body>
</html>