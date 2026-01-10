<?php
// Imports des dépendances
require_once __DIR__ . '/../../views/public/View.php';
require_once __DIR__ . '/../../views/public/components/UIHeader.php';
require_once __DIR__ . '/../../views/public/components/UIFooter.php';

class PublicationDetailsView extends View {

    /**
     * Méthode principale pour structurer la page
     */
    public function render() {
        // Extraction des données
        $config = $this->data['config'] ?? [];
        $menuData = $this->data['menu'] ?? [];
        
        // Récupération de la publication pour le titre de la page
        $pub = $this->data['publication'] ?? [];
        $pageTitle = $pub['titre'] ?? 'Détails Publication';

        // CSS spécifiques
        $customCss = [
            'views/admin_dashboard.css',
            'views/teamManagement.css',
            'views/landingPage.css',
            'views/publication-details.css', 
            'assets/css/public.css'          
        ];

        // 1. Rendu du Header
        $header = new UIHeader($pageTitle, $config, $menuData, $customCss);
        echo $header->render();

        // 2. Contenu Principal
        // On centre le contenu avec un max-width pour la lisibilité
        echo '<main style="width: 100%; padding: 40px 20px; box-sizing: border-box; background-color: #f8f9fc; min-height: 80vh;">';
        echo $this->content();
        echo '</main>';

        // 3. Rendu du Footer
        $footer = new UIFooter($config, $menuData);
        echo $footer->render();
    }

    /**
     * Contenu spécifique de la fiche publication
     */
    protected function content() {
        $pub = $this->data['publication'];

        // Calcul de la couleur du badge statut
        $badgeColor = '#6c757d'; // Gris par défaut
        if(isset($pub['statut_validation'])) {
            if($pub['statut_validation'] == 'valide') $badgeColor = '#1cc88a'; // Vert
            if($pub['statut_validation'] == 'en_attente') $badgeColor = '#f6c23e'; // Jaune
            if($pub['statut_validation'] == 'rejete') $badgeColor = '#e74a3b'; // Rouge
        }

        ob_start();
        ?>
        
        <!-- Styles internes pour ajuster l'affichage sans sidebar -->
        <style>
            .details-container {
                max-width: 900px;
                margin: 0 auto;
                background: white;
                padding: 40px;
                border-radius: 10px;
                box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            }
            .top-navigation {
                max-width: 900px;
                margin: 0 auto 20px auto;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .header-section { border-bottom: 2px solid #f1f1f1; padding-bottom: 20px; margin-bottom: 30px; }
            .header-section h1 { margin: 10px 0; color: #2c3e50; font-size: 2em; }
            .pub-type { text-transform: uppercase; letter-spacing: 1px; font-size: 0.85em; color: #4e73df; font-weight: bold; }
            
            .meta-grid { 
                display: grid; 
                grid-template-columns: 150px 1fr; 
                gap: 15px; 
                margin-bottom: 30px; 
                background: #f8f9fa; 
                padding: 20px; 
                border-radius: 8px; 
            }
            .meta-grid .label { font-weight: bold; color: #5a5c69; }
            .meta-grid .value { color: #2c3e50; }
            
            .resume-box { 
                line-height: 1.8; 
                color: #444; 
                text-align: justify; 
                margin-bottom: 40px; 
                padding: 20px; 
                border-left: 4px solid #4e73df; 
                background: #fff;
            }
            
            .actions-bar {
                display: flex;
                gap: 15px;
                border-top: 1px solid #eee;
                padding-top: 20px;
            }
            
            @media print {
                .top-navigation, .actions-bar, header, footer { display: none !important; }
                .details-container { box-shadow: none; margin: 0; padding: 0; }
                main { background: white; padding: 0; }
            }
        </style>

        <!-- Navigation du haut -->
        <div class="top-navigation">
            <a href="index.php?route=publications" class="btn btn-secondary" style="text-decoration: none; color: #666;">
                ← Retour à la liste
            </a>
            <!-- Le bouton Logout est géré par le Header, on peut afficher l'utilisateur ici si on veut -->
        </div>

        <div class="details-container">
            <!-- En-tête de la fiche -->
            <div class="header-section">
                <span class="pub-type"><?= htmlspecialchars($pub['type'] ?? 'Publication') ?></span>
                <h1><?= htmlspecialchars($pub['titre']) ?></h1>
                
                <?php if(isset($pub['statut_validation'])): ?>
                    <span style="background:<?= $badgeColor ?>; color:white; padding:5px 10px; border-radius:15px; font-size:0.85em; font-weight:bold; text-transform:uppercase;">
                        <?= ucfirst(str_replace('_', ' ', $pub['statut_validation'])) ?>
                    </span>
                <?php endif; ?>
            </div>

            <!-- Métadonnées -->
            <div class="meta-grid">
                <div class="label">Auteurs :</div>
                <div class="value"><strong><?= htmlspecialchars($pub['auteurs_noms'] ?? 'Non spécifié') ?></strong></div>

                <div class="label">Date :</div>
                <div class="value">
                    <?= !empty($pub['date_publication']) ? date('d/m/Y', strtotime($pub['date_publication'])) : 'Non définie' ?>
                </div>

                <div class="label">Projet Lié :</div>
                <div class="value"><?= htmlspecialchars($pub['projet_titre'] ?? 'Aucun') ?></div>

                <div class="label">Domaine :</div>
                <div class="value"><?= htmlspecialchars($pub['domaine'] ?? 'Général') ?></div>

                <div class="label">DOI :</div>
                <div class="value"><?= htmlspecialchars($pub['doi'] ?? '-') ?></div>

                <div class="label">Soumis par :</div>
                <div class="value"><?= htmlspecialchars($pub['soumis_par_nom'] ?? 'Inconnu') ?></div>
            </div>

            <!-- Résumé -->
            <?php if(!empty($pub['resume'])): ?>
                <h3 style="color: #2c3e50; margin-bottom: 15px;">Résumé</h3>
                <div class="resume-box">
                    <?= nl2br(htmlspecialchars($pub['resume'])) ?>
                </div>
            <?php endif; ?>

            <!-- Boutons d'action -->
            <div class="actions-bar">
                <button onclick="window.print()" class="btn btn-secondary" style="padding: 10px 20px; cursor: pointer; border: 1px solid #ddd; background: #f8f9fa; border-radius: 5px;">
                    🖨️ Imprimer la fiche
                </button>

                <?php if (!empty($pub['lien_telechargement'])): ?>
                    <a href="../controllers/api.php?action=downloadPublication&id=<?= $pub['id'] ?>" target="_blank" 
                       style="padding: 10px 20px; background: #4e73df; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;">
                        📥 Télécharger le document PDF
                    </a>
                <?php else: ?>
                    <button disabled style="padding: 10px 20px; background: #e2e6ea; color: #888; border: none; border-radius: 5px; cursor: not-allowed;">
                        Aucun fichier joint
                    </button>
                <?php endif; ?>
            </div>
        </div>
        
        <?php
        return ob_get_clean();
    }
}
?>