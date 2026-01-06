<?php
require_once __DIR__ . '/Component.php';
require_once __DIR__ . '/UICard.php'; // On utilise UICard pour l'intérieur (si vous le souhaitez)

class UIEvents extends Component {
    // Configuration des icônes pour les badges
    private $typeIcons = [
        'conférence' => '🎤',
        'atelier' => '🛠️',
        'séminaire' => '📊',
        'soutenance' => '🎓',
        'default' => '📅'
    ];

    private function getTypeIcon($type) {
        // Normalisation (minuscule) pour la clé
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
            foreach ($this->data as $event) {
                // Préparation des données pour la carte
                $typeText = ucfirst($event['type'] ?? 'événement');
                $icon = $this->getTypeIcon($event['type'] ?? '');
                
                $dateDay = date('d', strtotime($event['date_debut']));
                $dateMonth = date('M', strtotime($event['date_debut']));
                $time = date('H:i', strtotime($event['date_debut']));
                
                // Option 1 : Utiliser votre classe 'UICard' si elle existe et est compatible
                // Option 2 (ici) : Générer le HTML spécifique à vos événements pour coller à votre ancien design
                
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
                            <span>📍 ".htmlspecialchars($event['lieu'])."</span>
                            <span>⏰ $time</span>
                        </div>
                        <a href='event.php?id=".$event['id']."' class='card-link'>En savoir plus →</a>
                    </div>
                </article>";
            }
        } else {
            $html .= '<p class="no-events">Aucun événement à venir pour le moment.</p>';
        }

        $html .= '</div>'; // Fin events-grid
        $html .= '</section>';

        return $html;
    }
}
?>