<?php
require_once __DIR__ . '/../../views/public/View.php';
require_once __DIR__ . '/../../views/public/components/UIHeader.php';
require_once __DIR__ . '/../../views/public/components/UIFooter.php';
require_once __DIR__ . '/../../views/public/components/UIProjectCards.php';

class ProjectsView extends View {

    public function render() {
        $config = $this->data['config'] ?? [];
        $menuData = $this->data['menu'] ?? [];

        $customCss = [
            '../../views/landingPage.css',
            '../../assets/css/public.css',
            '../../assets/css/components.css',
            '../../assets/css/project.css'
        ];

        $header = new UIHeader("Catalogue des Projets", $config, $menuData, $customCss);
        echo $header->render();

        echo '<main class="main-content">';
        echo $this->content();
        echo '</main>';

       
    }

    protected function content() {
        $html = '';
        $projects = $this->data['projects'] ?? [];
        $lists = $this->data['filters_data'];
        $current = $this->data['active_filters'];

        // --- EN-TÊTE ---
        $html .= <<<HTML
        <section class="container" style="text-align:center; padding: 40px 20px;">
            <h1 class="page-title" style="color:var(--primary-color);">Catalogue des Projets</h1>
            <p>Découvrez nos projets de recherche classés par thématiques</p>
        </section>
HTML;

        // --- PRÉPARATION DES OPTIONS (FILTRES) ---
        
        // 1. Thématiques
        $optThemes = '<option value="">Toutes les thématiques</option>';
        foreach($lists['thematics'] as $t) {
            $sel = ($current['thematique'] == $t['id']) ? 'selected' : '';
            $optThemes .= "<option value='{$t['id']}' $sel>{$t['nom']}</option>";
        }

        // 2. Responsables
        $optLeaders = '<option value="">Tous les responsables</option>';
        foreach($lists['leaders'] as $l) {
            $sel = ($current['responsable'] == $l['id']) ? 'selected' : '';
            $nom = htmlspecialchars($l['nom'] . ' ' . $l['prenom']);
            $optLeaders .= "<option value='{$l['id']}' $sel>$nom</option>";
        }

        // 3. Statuts (Correction de l'erreur ici)
        // On calcule les variables 'selected' AVANT le bloc HTML
        $selStatut = $current['statut'];
        $sEnCours = ($selStatut == 'en_cours') ? 'selected' : '';
        $sTermine = ($selStatut == 'termine') ? 'selected' : '';
        $sSoumis  = ($selStatut == 'soumis') ? 'selected' : '';
// $searchValue = isset($_GET['search']) ? htmlspecialchars(trim($_GET['search']), ENT_QUOTES, 'UTF-8') : '';        // --- BARRE DE FILTRES ---
        $html .= <<<HTML
        <section class="container filter-container">
            <form action="" method="GET" class="project-filters">
    <!-- <input type="text" name="search" placeholder="Mot-clé..." value="{}" class="form-control">                 -->
                <select name="thematique" class="form-control">$optThemes</select>
                <select name="responsable" class="form-control">$optLeaders</select>
                
                <select name="statut" class="form-control">
                    <option value="">Tous statuts</option>
                    <option value="en_cours" $sEnCours>En cours</option>
                    <option value="termine" $sTermine>Terminé</option>
                    <option value="soumis" $sSoumis>Soumis</option>
                </select>

                <button type="submit" class="btn-filter">Filtrer</button>
                <a href="projects.php" class="btn-reset">Réinitialiser</a>
            </form>
        </section>
HTML;

        // --- LISTE DES PROJETS ---
        $html .= '<section class="container">';
        
        if (empty($projects)) {
            $html .= '<div class="alert alert-info" style="text-align:center; margin-top:20px;">Aucun projet ne correspond à votre recherche.</div>';
        } else {
            $html .= '<div class="grid-layout project-grid">';
            foreach ($projects as $proj) {
                $card = new UIProjectCard($proj);
                $html .= $card->render();
            }
            $html .= '</div>';
        }
        
        $html .= '</section>';

        return $html;
    }
}
?>