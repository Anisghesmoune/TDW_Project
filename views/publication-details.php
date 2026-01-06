<?php
session_start();
require_once __DIR__ . '/../models/Publications.php';

// 1. Récupération sécurisée de l'ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: publications.php'); // Redirection si pas d'ID
    exit;
}

// 2. Récupération des données via le Modèle
$model = new Publication();
$pub = $model->getByIdWithDetails($id);

if (!$pub) {
    die("Publication introuvable.");
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pub['titre']) ?></title>
    <link rel="stylesheet" href="admin_dashboard.css">
    <link rel="stylesheet" href="teamManagement.css">
        <link rel="stylesheet" href="publication-details.css">


</head>
<body>
    <!-- Sidebar (Masquée à l'impression) -->
    <div class="sidebar">
        <div class="sidebar-header"><h2>📚 Détails</h2></div>
        <?php require_once __DIR__ . '/../views/Sidebar.php'; (new Sidebar("admin"))->render(); ?>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <div>
                <a href="publications.php" class="btn btn-secondary back-btn">← Retour à la liste</a>
            </div>
            <a href="../logout.php" class="logout-btn">Déconnexion</a>
        </div>

        <div class="details-container">
            <div class="header-section">
                <span class="pub-type"><?= htmlspecialchars($pub['type']) ?></span>
                <h1><?= htmlspecialchars($pub['titre']) ?></h1>
                
                <?php 
                $badgeColor = '#6c757d';
                if($pub['statut_validation'] == 'valide') $badgeColor = '#1cc88a';
                if($pub['statut_validation'] == 'en_attente') $badgeColor = '#f6c23e';
                if($pub['statut_validation'] == 'rejete') $badgeColor = '#e74a3b';
                ?>
                <span style="background:<?= $badgeColor ?>; color:white; padding:3px 8px; border-radius:4px; font-size:0.8em;">
                    <?= ucfirst(str_replace('_', ' ', $pub['statut_validation'])) ?>
                </span>
            </div>

            <div class="meta-grid">
                <div class="label">Auteurs :</div>
                <div class="value"><strong><?= htmlspecialchars($pub['auteurs_noms'] ?? 'Non spécifié') ?></strong></div>

                <div class="label">Date :</div>
                <div class="value"><?= date('d/m/Y', strtotime($pub['date_publication'])) ?></div>

                <div class="label">Projet Lié :</div>
                <div class="value"><?= htmlspecialchars($pub['projet_titre'] ?? 'Aucun') ?></div>

                <div class="label">Domaine :</div>
                <div class="value"><?= htmlspecialchars($pub['domaine'] ?? 'Général') ?></div>

                <div class="label">DOI :</div>
                <div class="value"><?= htmlspecialchars($pub['doi'] ?? '-') ?></div>

                <div class="label">Soumis par :</div>
                <div class="value"><?= htmlspecialchars($pub['soumis_par_nom']) ?></div>
            </div>

            <h3>Résumé</h3>
            <div class="resume-box">
                <?= nl2br(htmlspecialchars($pub['resume'])) ?>
            </div>

            <!-- Boutons d'action (Masqués à l'impression) -->
            <div class="actions-bar">
                <button onclick="window.print()" class="btn btn-secondary">
                    🖨️ Imprimer la fiche
                </button>

                <?php if (!empty($pub['lien_telechargement'])): ?>
                    <a href="../controllers/api.php?action=downloadPublication&id=<?= $pub['id'] ?>" class="btn btn-primary" target="_blank">
                        📥 Télécharger le document PDF
                    </a>
                <?php else: ?>
                    <button class="btn btn-secondary" disabled>Aucun fichier joint</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>