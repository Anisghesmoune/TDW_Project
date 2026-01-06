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

        // --- 6. PARTENAIRES ---
        if (!empty($this->data['partners'])) {
            $partnerSection = new UIPartners($this->data['partners']);
            $html .= $partnerSection->render();
        }

        return $html;
    }
}
?>