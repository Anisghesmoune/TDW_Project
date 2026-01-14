<?php
require_once __DIR__ . '/Component.php';
require_once __DIR__ . '/UICard.php'; 

class UINews extends Component {
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
       
        $html = '<section class="events-section">';
       $html .= '<h2 class="section-title">À la une</h2>';
       $html .= '<p class="section-subtitle">Découvrez les dernières avancées du laboratoire</p>';
        $html .= '<div class="events-grid">';

        if (!empty($this->data)) {
            foreach ($this->data as $newsItem) {
                
                $icon = $this->getTypeIcon($newsItem['type'] ?? 'default');
                $typeLabel = ucfirst($newsItem['type'] ?? 'Événement');
                
                $timestamp = strtotime($newsItem['date_debut'] ?? $newsItem['date_publication'] ?? 'now');
                $dayStr = date('d', $timestamp);
                $monthStr = date('M', $timestamp); // Jan, Feb...
                
                $timeStr = date('H:i', $timestamp);
                $lieuStr = $newsItem['lieu'] ?? 'ESI';

                $card = new UICard([
                    'title'       => $newsItem['titre'],
                    'description' => substr($newsItem['description'] ?? $newsItem['resume'] ?? '', 0, 100) . '...',
                    
                    'date'        => [
                        'day'   => $dayStr,
                        'month' => $monthStr
                    ],
                    
                    'badge'       => [
                        'icon' => $icon,
                        'text' => $typeLabel
                    ],
                    
                    'metadata'    => [
                        "📍 $lieuStr",
                        "⏰ $timeStr"
                    ],
                    
                    'class'       => ' generic-card'
                ]);

                $html .= $card->render();
            }
        } else {
            $html .= '<p style="text-align:center; width:100%;">Aucune actualité pour le moment.</p>';
        }

        $html .= '</div>'; 
        $html .= '</section>';

        return $html;
    }
}
?>