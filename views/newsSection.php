<?php
class NewsSectionView {
    private $news;
     private $typeIcons = [
        'conférence' => '🎤',
        'atelier' => '🛠️',
        'séminaire' => '📊',
        'soutenance' => '🎓',
        'default' => '📅'
    ];
    
    public function __construct($news) {
        $this->news = $news;
    }
    private function getTypeIcon($type) {
        return $this->typeIcons[$type] ?? $this->typeIcons['default'];
    }
    public function render() {
?>
<section class="news-section">
    <h2 class="section-title">Actualités Scientifiques</h2>
    <p class="section-subtitle">Découvrez les dernières avancées de notre laboratoire</p>
    
     <div class="events-grid">
                <?php foreach ($this->news as $newsItem): ?>

                    <?php
                    $card = new Cards([
                        'title' => $newsItem['titre'],
                        'description' => substr($newsItem['description'], 0, 100) . '...',
                        'badge' => [
                            'icon' => $this->getTypeIcon($newsItem['type'] ?? 'default'),
                            'text' => ucfirst($newsItem['type'] ?? 'événement')
                        ],
                        'date' => [
                            'day' => date('d', strtotime($newsItem['date_debut'])),
                            'month' => date('M', strtotime($newsItem['date_debut']))
                        ],
                        'metadata' => [
                            '📍 ' . $newsItem['lieu'],
                            '⏰ ' . date('H:i', strtotime($newsItem['date_debut']))
                        ],
                        'link' => 'event.php?id=' . $newsItem['id'],
                        'class' => 'event-card'
                    ]);

                    $card->display();
                    ?>

                <?php endforeach; ?>
            </div>
        </section>
</div>
<?php
    }
}