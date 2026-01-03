<?php
require_once '../views/View.php';
require_once '../views/components/UISection.php';
require_once '../views/components/UICard.php';
require_once '../views/components/UISlider.php';

class HomeView extends View {

    protected function content() {
        $html = '';

        // --- 1. LE SLIDER ---
        // On transforme les données brutes en objets Slider
        if (!empty($this->slides)) {
            // $slider = new UISlider($this->slides);
            $html .= $slider->render();
        }

        // --- 2. SECTION ACTUALITÉS ---
        $newsSection = new UISection("Dernières Actualités");
        
        foreach ($this->news as $item) {
            // Logique d'affichage : choix de l'icône selon le type
            $icon = ($item['type'] === 'article') ? 'far fa-newspaper' : 'fas fa-project-diagram';
            
            // Création du composant Carte
            $card = new UICard(
                $item['titre'],
                date('d/m/Y', strtotime($item['date_publication'])),
                $item['resume'],
                'details.php?id=' . $item['id'],
                $icon
            );
            $newsSection->addComponent($card);
        }
        $html .= $newsSection->render();

        // --- 3. SECTION ÉVÉNEMENTS ---
        $eventSection = new UISection("Agenda", "#f8f9fc"); // Fond gris
        
        foreach ($this->upcomingEvents as $evt) {
            $card = new UICard(
                $evt['titre'],
                $evt['lieu'], // Meta = Lieu
                "Le " . date('d/m/Y', strtotime($evt['date_debut'])), // Desc = Date
                'events.php?id=' . $evt['id'],
                'far fa-calendar-alt'
            );
            $eventSection->addComponent($card);
        }
        $html .= $eventSection->render();

        return $html;
    }
}
?>