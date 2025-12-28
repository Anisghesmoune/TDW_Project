<?php

class EventSectionView {

    private $events;
    private $eventCount;

    private $typeIcons = [
        'conférence' => '🎤',
        'atelier' => '🛠️',
        'séminaire' => '📊',
        'soutenance' => '🎓',
        'default' => '📅'
    ];

    public function __construct(array $events, int $eventCount = 0) {
        $this->events = $events;
        $this->eventCount = $eventCount;
    }

    private function getTypeIcon($type) {
        return $this->typeIcons[$type] ?? $this->typeIcons['default'];
    }

    public function render() {
        ?>
        <section class="events-section">
            <h2 class="section-title">Événements à venir</h2>
            <p class="section-subtitle">
                Rejoignez-nous lors de nos prochains événements scientifiques
            </p>

            <div class="events-grid">
                <?php foreach ($this->events as $event): ?>

                    <?php
                    $card = new Cards([
                        'title' => $event['titre'],
                        'description' => substr($event['description'], 0, 100) . '...',
                        'badge' => [
                            'icon' => $this->getTypeIcon($event['type'] ?? 'default'),
                            'text' => ucfirst($event['type'] ?? 'événement')
                        ],
                        'date' => [
                            'day' => date('d', strtotime($event['date_debut'])),
                            'month' => date('M', strtotime($event['date_debut']))
                        ],
                        'metadata' => [
                            '📍 ' . $event['lieu'],
                            '⏰ ' . date('H:i', strtotime($event['date_debut']))
                        ],
                        'link' => 'event.php?id=' . $event['id'],
                        'class' => 'event-card'
                    ]);

                    $card->display();
                    ?>

                <?php endforeach; ?>
            </div>
        </section>
        <?php
    }
}
