<?php
// Imports
require_once __DIR__ . '/../../views/public/View.php';
require_once __DIR__ . '/../../views/public/components/UIHeader.php';
require_once __DIR__ . '/../../views/public/components/UIFooter.php';
require_once __DIR__ . '/../../views/public/components/UISlider.php';
require_once __DIR__ . '/../../views/public/components/UIPartners.php';
// Nouveaux imports
require_once __DIR__ . '/../../views/public/components/UINews.php';
require_once __DIR__ . '/../../views/public/components/UIEvents.php';
// Wrapper pour l'ancien organigramme si pas encore converti
require_once __DIR__ . '/../../views/public/components/UIOrganigramme.php'; 


class HomeView extends View {

    public function render() {
        $config = $this->data['config'] ?? [];
        $menuData = $this->data['menu'] ?? [];

        // --- DÉFINITION DES CSS SPÉCIFIQUES ---
        // Ajoutez ici tous les fichiers CSS nécessaires pour la page d'accueil
        $customCss = [
            '../../views/landingPage.css',
            '../../views/organigramme.css',
            '../../views/diaporama.css',
            
        ];

        // 1. Header (On passe les CSS en 4ème argument)
        $header = new UIHeader("Accueil", $config, $menuData, $customCss);
        echo $header->render();

        // 2. Contenu
        echo '<main class="main-content">';
        echo $this->content();
        echo '</main>';

        // 3. Footer
        $footer = new UIFooter($config,$menuData);
        echo $footer->render();
    }

    protected function content() {
        $html = '';

        // --- 1. SLIDER ---
        if (!empty($this->data['slides'])) {
            $slider = new UISlider($this->data['slides']);
            $html .= $slider->render();
        }

        // --- 2. ACTUALITÉS ---
        if (!empty($this->data['news'])) {
            $newsSection = new UINews($this->data['news']);
            $html .= $newsSection->render();
        }

        // // --- 3. À PROPOS ---
        // $html .= '<section class="about-section container" style="text-align:center; padding:40px 20px;">
        //             <h2 class="section-title">À Propos</h2>
        //             <p>Le laboratoire d\'informatique ESI est un pôle d\'excellence...</p>
        //           </section>';

        // --- 4. ÉVÉNEMENTS ---
        if (!empty($this->data['events'])) {
            $eventsSection = new UIEvents($this->data['events']);
            $html .= $eventsSection->render();
        }

        // --- 5. ORGANIGRAMME ---
        if (isset($this->data['organigramme'])) {
            ob_start();
            $orgData = $this->data['organigramme'];
            $orgView = new OrganigrammeSectionView(
                $orgData['director'] ?? null,
                $orgData['hierarchyTree'] ?? [],
                $orgData['stats'] ?? null
            );
            $orgView->render();
            $html .= ob_get_clean();
        }
        if (!empty($this->data['opportunities'])) {
            $html .= '<section class="opportunities-section container" style="padding: 40px 20px; background-color: #f8f9fc;">';
            $html .= '<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">';
            $html .= '<h2 class="section-title" style="color:#2c3e50; border-left:5px solid #4e73df; padding-left:15px;">🚀 Offres et Opportunités</h2>';
            // Lien vers la page complète des opportunités
            $html .= '<a href="index.php?route=opportunities" class="btn-link" style="color:#4e73df; text-decoration:none; font-weight:bold;">Voir tout →</a>';
            $html .= '</div>';
            
            $html .= '<div class="grid-container" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap:20px;">';
            
            foreach ($this->data['opportunities'] as $opp) {
                // Couleurs de badges selon le type
                $badgeColor = '#6c757d'; // gris par défaut
                if ($opp['type'] == 'stage') $badgeColor = '#1cc88a'; // vert
                if ($opp['type'] == 'thèse') $badgeColor = '#4e73df'; // bleu
                if ($opp['type'] == 'bourse') $badgeColor = '#f6c23e'; // jaune
                
                $html .= '<div class="card-item" style="background:white; padding:20px; border-radius:10px; box-shadow:0 2px 5px rgba(0,0,0,0.05); transition:transform 0.2s;">';
                $html .= '<span style="background:'.$badgeColor.'; color:white; padding:3px 10px; border-radius:15px; font-size:0.8em; text-transform:uppercase; font-weight:bold;">'.htmlspecialchars($opp['type']).'</span>';
                $html .= '<h3 style="margin:15px 0 10px; font-size:1.1em; color:#333;">'.htmlspecialchars($opp['titre']).'</h3>';
                // Description courte
                $desc = substr(strip_tags($opp['description']), 0, 80) . '...';
                $html .= '<p style="color:#666; font-size:0.9em; line-height:1.5;">'.$desc.'</p>';
                $html .= '<div style="margin-top:15px; padding-top:15px; border-top:1px solid #eee; display:flex; justify-content:space-between; align-items:center;">';
                $html .= '<small style="color:#888;">📅 Fin: '.date('d/m/Y', strtotime($opp['date_expiration'])).'</small>';
                // Lien vers détail (ancre ou page dédiée)
                $html .= '<a href="index.php?route=opportunities" style="color:#4e73df; text-decoration:none; font-size:0.9em;">Détails</a>';
                $html .= '</div>';
                $html .= '</div>';
            }
            
            $html .= '</div>';
            $html .= '</section>';
        }

        // --- 6. PARTENAIRES ---
        if (!empty($this->data['partners'])) {
            $partnerSection = new UIPartners($this->data['partners']);
            $html .= $partnerSection->render();
        }

        return $html;
    }
}
?>