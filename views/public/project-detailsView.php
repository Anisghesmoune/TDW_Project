<?php
// On récupère les composants
require_once __DIR__ . '/components/UIHeader.php';
require_once __DIR__ . '/components/UIFooter.php';
require_once __DIR__ . '/components/UIMemberCard.php'; // Pour afficher les membres

// Extraction des données
$project = $data['project'];
$members = $data['members'];
$pubs = $data['publications'];
$config = $data['config'];
$menu = $data['menu'];

// CSS Spécifique
$customCss = [
    '../assets/css/public.css',
     '../../views/landingPage.css',
            '../../assets/css/public.css',
          
    '../../assets/css/project-details.css' // CSS à créer étape 4
];

// 1. HEADER
$header = new UIHeader($project['titre'], $config, $menu, $customCss);
echo $header->render();
?>

<main class="main-content">
    
    <!-- EN-TÊTE PROJET -->
    <section class="project-hero">
        <div class="container">
            <a href="projects.php" class="back-link">← Retour au catalogue</a>
            
            <div class="hero-badges">
                <?php foreach($project['thematiques'] as $theme): ?>
                    <span class="theme-badge"><?= htmlspecialchars($theme) ?></span>
                <?php endforeach; ?>
                <span class="status-badge <?= $project['statut'] ?>">
                    <?= ucfirst(str_replace('_', ' ', $project['statut'])) ?>
                </span>
            </div>

            <h1><?= htmlspecialchars($project['titre']) ?></h1>
            
            <div class="project-main-meta">
                <span><i class="fas fa-calendar-alt"></i> Début : <?= date('d/m/Y', strtotime($project['date_debut'])) ?></span>
                <span><i class="fas fa-coins"></i> Financement : <?= htmlspecialchars($project['type_financement']) ?></span>
                <?php if($project['date_fin']): ?>
                    <span><i class="fas fa-flag-checkered"></i> Fin : <?= date('d/m/Y', strtotime($project['date_fin'])) ?></span>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <div class="container project-content-grid">
        
        <!-- COLONNE GAUCHE : DESCRIPTION & PUBS -->
        <div class="content-left">
            <!-- Description -->
            <div class="content-block">
                <h2>À propos du projet</h2>
                <div class="description-text">
                    <?= nl2br(htmlspecialchars($project['description'])) ?>
                </div>
            </div>

            <!-- Publications Associées -->
            <div class="content-block">
                <h2>📚 Publications Associées (<?= count($pubs) ?>)</h2>
                <?php if(!empty($pubs)): ?>
                    <div class="pub-list">
                        <?php foreach($pubs as $pub): ?>
                            <div class="pub-item">
                                <div class="pub-icon"><i class="far fa-file-pdf"></i></div>
                                <div class="pub-info">
                                    <h4><?= htmlspecialchars($pub['titre']) ?></h4>
                                    <p class="pub-meta">
                                        <?= date('d/m/Y', strtotime($pub['date_publication'])) ?> • <?= htmlspecialchars($pub['type']) ?>
                                    </p>
                                    <a href="details.php?id=<?= $pub['id'] ?>" class="pub-link">Voir la fiche</a>
                                    <?php if($pub['lien_telechargement']): ?>
                                        <a href="../controllers/api.php?action=downloadPublication&id=<?= $pub['id'] ?>" class="pub-download" target="_blank">📥 PDF</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="empty-state">Aucune publication liée à ce projet pour le moment.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- COLONNE DROITE : EQUIPE -->
        <div class="content-right">
            
            <!-- Responsable -->
            <div class="content-block">
                <h3>Responsable Scientifique</h3>
                <div class="leader-card">
                    <img src="../<?= htmlspecialchars($project['responsable_photo'] ?? 'assets/img/default-avatar.png') ?>" alt="Photo">
                    <div>
                        <h4><?= htmlspecialchars($project['responsable_nom']) ?></h4>
                        <span class="role"><?= htmlspecialchars($project['responsable_grade'] ?? 'Chercheur') ?></span>
                        <br>
                        <a href="mailto:<?= $project['responsable_email'] ?>" class="contact-btn">Contacter</a>
                    </div>
                </div>
            </div>

            <!-- Membres -->
            <div class="content-block">
                <h3>Membres du projet</h3>
                <?php if(!empty($members)): ?>
                    <div class="members-mini-grid">
                        <?php foreach($members as $m): ?>
                            <!-- On exclut le responsable de la liste pour ne pas faire doublon -->
                            <?php if($m['id'] != $project['responsable_id']): ?>
                                <div class="mini-member">
                                    <img src="../<?= htmlspecialchars($m['photo_profil'] ?? 'assets/img/default-avatar.png') ?>" title="<?= htmlspecialchars($m['membre_nom']) ?>">
                                    <span class="name"><?= htmlspecialchars($m['membre_nom']) ?></span>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>Aucun autre membre.</p>
                <?php endif; ?>
            </div>

        </div>
    </div>
</main>



