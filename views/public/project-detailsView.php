<?php
// Imports des dépendances
require_once __DIR__ . '/../../views/public/View.php';
require_once __DIR__ . '/../../views/public/components/UIHeader.php';
require_once __DIR__ . '/../../views/public/components/UIFooter.php';

class ProjectDetailsView extends View {

    /**
     * Méthode principale pour structurer la page
     */
    public function render() {
        // Récupération des données globales
        $config = $this->data['config'] ?? [];
        $menuData = $this->data['menu'] ?? [];
        
        // Titre de la page (Nom du projet ou défaut)
        $projectTitle = $this->data['project']['titre'] ?? 'Détails du projet';

        // CSS Spécifiques
        $customCss = [
            'assets/css/public.css',
            'views/landingPage.css',
            'assets/css/project-details.css', // Assurez-vous que ce fichier existe
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'
        ];

        // 1. Rendu du Header
        $header = new UIHeader($projectTitle, $config, $menuData, $customCss);
        echo $header->render();

        // 2. Contenu Principal
        echo '<main class="main-content" style="background-color: #f8f9fc; padding-bottom: 50px;">';
        echo $this->content();
        echo '</main>';

        // 3. Rendu du Footer
        $footer = new UIFooter($config, $menuData);
        echo $footer->render();
    }

    /**
     * Contenu spécifique de la page Projet
     */
    protected function content() {
        // Extraction des données spécifiques
        $project = $this->data['project'];
        $members = $this->data['members'] ?? [];
        $pubs = $this->data['publications'] ?? [];

        // Sécurisation des thématiques (si c'est une chaine ou null, on convertit en tableau)
        $themes = $project['thematiques'];
        if (is_string($themes)) {
            $themes = explode(',', $themes);
        } elseif (!is_array($themes)) {
            $themes = [];
        }

        ob_start();
        ?>
        
        <!-- EN-TÊTE PROJET (HERO) -->
        <section class="project-hero" style="background: linear-gradient(135deg, #4e73df 0%, #224abe 100%); color: white; padding: 60px 0; margin-bottom: 40px;">
            <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
                <a href="index.php?route=projects" class="back-link" style="color: rgba(255,255,255,0.8); text-decoration: none; display: inline-block; margin-bottom: 20px;">
                    ← Retour au catalogue
                </a>
                
                <div class="hero-badges" style="margin-bottom: 15px;">
                    <?php foreach($themes as $theme): ?>
                        <span class="theme-badge" style="background: rgba(255,255,255,0.2); padding: 5px 12px; border-radius: 20px; font-size: 0.85em; margin-right: 5px;">
                            <?= htmlspecialchars(trim($theme)) ?>
                        </span>
                    <?php endforeach; ?>
                    
                    <?php 
                        // Style badge statut
                        $statutColor = 'rgba(255,255,255,0.2)';
                        if($project['statut'] == 'en_cours') $statutColor = '#1cc88a';
                        if($project['statut'] == 'termine') $statutColor = '#858796';
                    ?>
                    <span class="status-badge" style="background: <?= $statutColor ?>; padding: 5px 12px; border-radius: 20px; font-size: 0.85em; font-weight: bold; text-transform: uppercase;">
                        <?= ucfirst(str_replace('_', ' ', $project['statut'])) ?>
                    </span>
                </div>

                <h1 style="font-size: 2.5em; margin: 0 0 20px 0; font-weight: 700;"><?= htmlspecialchars($project['titre']) ?></h1>
                
                <div class="project-main-meta" style="display: flex; gap: 20px; flex-wrap: wrap; opacity: 0.9;">
                    <span><i class="fas fa-calendar-alt"></i> Début : <?= date('d/m/Y', strtotime($project['date_debut'])) ?></span>
                    <span><i class="fas fa-coins"></i> Financement : <?= htmlspecialchars($project['type_financement'] ?? 'Non spécifié') ?></span>
                    <?php if(!empty($project['date_fin'])): ?>
                        <span><i class="fas fa-flag-checkered"></i> Fin : <?= date('d/m/Y', strtotime($project['date_fin'])) ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <div class="container project-content-grid" style="max-width: 1200px; margin: 0 auto; padding: 0 20px; display: grid; grid-template-columns: 2fr 1fr; gap: 40px;">
            
            <!-- COLONNE GAUCHE : DESCRIPTION & PUBS -->
            <div class="content-left">
                <!-- Description -->
                <div class="content-block" style="background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px;">
                    <h2 style="border-bottom: 2px solid #f8f9fa; padding-bottom: 15px; margin-top: 0; color: #4e73df;">À propos du projet</h2>
                    <div class="description-text" style="line-height: 1.8; color: #5a5c69; text-align: justify;">
                        <?= nl2br(htmlspecialchars($project['description'])) ?>
                    </div>
                </div>

                <!-- Publications Associées -->
                <div class="content-block" style="background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                    <h2 style="border-bottom: 2px solid #f8f9fa; padding-bottom: 15px; margin-top: 0; color: #6f42c1;">📚 Publications Associées (<?= count($pubs) ?>)</h2>
                    
                    <?php if(!empty($pubs)): ?>
                        <div class="pub-list">
                            <?php foreach($pubs as $pub): ?>
                                <div class="pub-item" style="display: flex; align-items: flex-start; gap: 15px; padding: 15px 0; border-bottom: 1px solid #eee;">
                                    <div class="pub-icon" style="background: #ffebee; color: #e74a3b; padding: 10px; border-radius: 8px; font-size: 1.5em;">
                                        <i class="far fa-file-pdf"></i>
                                    </div>
                                    <div class="pub-info" style="flex: 1;">
                                        <h4 style="margin: 0 0 5px 0; color: #2e384d;"><?= htmlspecialchars($pub['titre']) ?></h4>
                                        <p class="pub-meta" style="font-size: 0.9em; color: #858796; margin: 0 0 10px 0;">
                                            <?= date('d/m/Y', strtotime($pub['date_publication'])) ?> • <?= htmlspecialchars($pub['type'] ?? 'Article') ?>
                                        </p>
                                        
                                        <div style="display: flex; gap: 10px;">
                                            <a href="index.php?route=publication-details&id=<?= $pub['id'] ?>" class="pub-link" style="color: #4e73df; text-decoration: none; font-size: 0.9em; font-weight: bold;">Voir la fiche</a>
                                            <?php if(!empty($pub['lien_telechargement'])): ?>
                                                <a href="<?= htmlspecialchars($pub['lien_telechargement']) ?>" class="pub-download" target="_blank" style="color: #1cc88a; text-decoration: none; font-size: 0.9em; font-weight: bold;">📥 Télécharger</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="empty-state" style="color: #888; font-style: italic; padding: 20px 0;">Aucune publication liée à ce projet pour le moment.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- COLONNE DROITE : EQUIPE -->
            <div class="content-right">
                
                <!-- Responsable -->
                <div class="content-block" style="background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px;">
                    <h3 style="margin-top: 0; color: #2e384d; border-bottom: 1px solid #eee; padding-bottom: 10px;">Responsable Scientifique</h3>
                    <div class="leader-card" style="display: flex; align-items: center; gap: 15px; margin-top: 15px;">
                        <img src="<?= htmlspecialchars(!empty($project['responsable_photo']) ? $project['responsable_photo'] : 'assets/img/default-avatar.png') ?>" 
                             alt="Photo" 
                             style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover; border: 2px solid #4e73df;">
                        <div>
                            <h4 style="margin: 0; color: #4e73df;"><?= htmlspecialchars($project['responsable_nom'] . ' ' . ($project['responsable_prenom'] ?? '')) ?></h4>
                            <span class="role" style="font-size: 0.85em; color: #666;"><?= htmlspecialchars($project['responsable_grade'] ?? 'Chercheur') ?></span>
                            <br>
                            <?php if(!empty($project['responsable_email'])): ?>
                                <a href="mailto:<?= $project['responsable_email'] ?>" class="contact-btn" style="font-size: 0.85em; color: #858796; text-decoration: none; margin-top: 5px; display: inline-block;">
                                    <i class="far fa-envelope"></i> Contacter
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Membres -->
                <div class="content-block" style="background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                    <h3 style="margin-top: 0; color: #2e384d; border-bottom: 1px solid #eee; padding-bottom: 10px;">Membres du projet</h3>
                    <?php if(!empty($members)): ?>
                        <div class="members-mini-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(60px, 1fr)); gap: 15px; margin-top: 15px;">
                            <?php foreach($members as $m): ?>
                                <!-- On exclut le responsable si déjà affiché au dessus -->
                                <?php if($m['id'] != ($project['responsable_id'] ?? 0)): ?>
                                    <div class="mini-member" style="text-align: center;">
                                        <img src="<?= htmlspecialchars(!empty($m['photo_profil']) ? $m['photo_profil'] : 'assets/img/default-avatar.png') ?>" 
                                             title="<?= htmlspecialchars($m['membre_nom'] . ' ' . $m['membre_prenom']) ?>"
                                             style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 2px solid #f8f9fc; transition: transform 0.2s;">
                                        <span class="name" style="display: block; font-size: 0.75em; color: #5a5c69; margin-top: 5px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            <?= htmlspecialchars($m['membre_nom']) ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p style="color: #888; font-size: 0.9em;">Aucun autre membre affecté.</p>
                    <?php endif; ?>
                </div>

            </div>
        </div>

        <!-- Styles Responsive inline pour s'assurer que ça marche sans le fichier CSS externe -->
        <style>
            @media (max-width: 900px) {
                .project-content-grid { grid-template-columns: 1fr !important; }
            }
            .mini-member img:hover { transform: scale(1.1); border-color: #4e73df !important; cursor: pointer; }
        </style>

        <?php
        return ob_get_clean();
    }
}
?>