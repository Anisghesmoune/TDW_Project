<?php
require_once __DIR__ . '/Component.php';
require_once __DIR__ . '/UICard.php'; 

class UINews extends Component {
    // Configuration des icônes
    private $typeIcons = [
        'conférence' => '🎤',
        'atelier' => '🛠️',
        'séminaire' => '📊',
        'soutenance' => '🎓',
        'article' => '📰',
        'default' => '📅'
    ];

    private function getTypeIcon($type) {
        $key = mb_strtolower($type ?? '', 'UTF-8');
        return $this->typeIcons[$key] ?? $this->typeIcons['default'];
    }

    public function render() {
       
        // Début de la section (HTML string)
        $html = '<section class="events-section">';
        $html .= '<h2 class="section-title">Événements à venir</h2>';
        $html .= '<p class="section-subtitle">Rejoignez-nous lors de nos prochains événements scientifiques</p>';
        
        $html .= '<div class="events-grid">';

        if (!empty($this->data)) {
            foreach ($this->data as $newsItem) {
                
                // 1. Préparation des données brutes
                $icon = $this->getTypeIcon($newsItem['type'] ?? 'default');
                $typeLabel = ucfirst($newsItem['type'] ?? 'Événement');
                
                // Gestion de la date pour le tableau ['day', 'month']
                $timestamp = strtotime($newsItem['date_debut'] ?? $newsItem['date_publication'] ?? 'now');
                $dayStr = date('d', $timestamp);
                $monthStr = date('M', $timestamp); // Jan, Feb...
                
                // Lieu et Heure pour les métadonnées
                $timeStr = date('H:i', $timestamp);
                $lieuStr = $newsItem['lieu'] ?? 'ESI';

                // 2. Création de la Carte avec le tableau d'options correct
                $card = new UICard([
                    'title'       => $newsItem['titre'],
                    'description' => substr($newsItem['description'] ?? $newsItem['resume'] ?? '', 0, 100) . '...',
                    'link'        => 'details.php?id=' . ($newsItem['id'] ?? 0),
                    
                    // Tableau Date pour renderDate()
                    'date'        => [
                        'day'   => $dayStr,
                        'month' => $monthStr
                    ],
                    
                    // Tableau Badge pour renderBadge()
                    'badge'       => [
                        'icon' => $icon,
                        'text' => $typeLabel
                    ],
                    
                    // Tableau Metadata pour renderMetadata()
                    'metadata'    => [
                        "📍 $lieuStr",
                        "⏰ $timeStr"
                    ],
                    
                    'class'       => ' generic-card'
                ]);

                // Concaténation du rendu de la carte
                $html .= $card->render();
            }
        } else {
            $html .= '<p style="text-align:center; width:100%;">Aucune actualité pour le moment.</p>';
        }

        $html .= '</div>'; // Fin grid
        $html .= '</section>';

        return $html;
    }
}
?>