<?php
require_once __DIR__ . '/Component.php';
require_once __DIR__ . '/UICard.php'; 

class UIEvents extends Component {
    private $typeIcons = [
        'conférence' => '🎤',
        'atelier' => '🛠️',
        'séminaire' => '📊',
        'soutenance' => '🎓',
        'default' => '📅'
    ];

    private function getTypeIcon($type) {
        $key = mb_strtolower($type ?? '', 'UTF-8');
        return $this->typeIcons[$key] ?? $this->typeIcons['default'];
    }

    public function render() {
        $html = '<section class="events-section">';
        $html .= '<h2 class="section-title">Événements à venir</h2>';
        $html .= '<p class="section-subtitle">Rejoignez-nous lors de nos prochains événements scientifiques</p>';
        
        $html .= '<div class="events-grid">';

        if (!empty($this->data)) {
            foreach ($this->data as $event) {
                $typeText = ucfirst($event['type'] ?? 'événement');
                $icon = $this->getTypeIcon($event['type'] ?? '');
                
                $dateDay = date('d', strtotime($event['date_debut']));
                $dateMonth = date('M', strtotime($event['date_debut']));
                $time = date('H:i', strtotime($event['date_debut']));
                
               
                $html .= "
                <article class='event-card'>
                    <div class='card-badge'>
                        <span class='badge-icon'>$icon</span>
                        <span class='badge-text'>$typeText</span>
                    </div>
                    <div class='card-date'>
                        <span class='day'>$dateDay</span>
                        <span class='month'>$dateMonth</span>
                    </div>
                    <div class='card-content'>
                        <h3>".htmlspecialchars($event['titre'])."</h3>
                        <p class='description'>".htmlspecialchars(substr($event['description'], 0, 100))."...</p>
                        <div class='card-meta'>
                            <span>📍 ".htmlspecialchars($event['localisation'])."</span>
                            <span>⏰ $time</span>
                        </div>
                        <a href='index.php?route=eventsLists' class='card-link'>En savoir plus →</a>
                    </div>
                </article>";
            }
        } else {
            $html .= '<p class="no-events">Aucun événement à venir pour le moment.</p>';
        }

        $html .= '</div>'; 
        $html .= '</section>';

        return $html;
    }
}
?>