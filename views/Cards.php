<?php
class Cards {
    private $title;
    private $description;
    private $badge;
    private $metadata;
    private $image;
    private $link;
    private $date;
    private $customClass;
    
    public function __construct($options = []) {
        $this->title = $options['title'] ?? '';
        $this->description = $options['description'] ?? '';
        $this->badge = $options['badge'] ?? null; 
        $this->metadata = $options['metadata'] ?? []; 
        $this->image = $options['image'] ?? null;
        $this->link = $options['link'] ?? null;
        $this->date = $options['date'] ?? null; 
        $this->customClass = $options['class'] ?? 'card';
    }

    private function renderDate(){
        if ($this->date) {
        return '';}
        return  <<<'HTML'
        <div class="card-date">
            <span class="day">{$this->date['day']}</span>   
            <span class="month">{$this->date['month']}</span> 
        </div>
HTML;
        }
         private function renderImage() {
        if (!$this->image) return '';
        
        return <<<HTML
        <div class="card-image">
            <img src="{$this->image}" alt="{$this->title}">
        </div>
HTML;
    }
    private function renderBadge() {
        if (!$this->badge) return '';
        
        $icon = $this->badge['icon'] ?? '';
        $text = $this->badge['text'] ?? '';
        
        return <<<HTML
        <p class="card-badge">
            {$icon} {$text}
        </p>
HTML;
    }
private function renderMetadata() {
        if (empty($this->metadata)) return '';
        
        $metaHtml = implode('<br>', array_map('htmlspecialchars', $this->metadata));
        
        return <<<HTML
        <p class="card-meta">
            {$metaHtml}
        </p>
HTML;
    }
    /**
     * Générer le HTML de la carte
     */
    public function render() {
        $dateHtml = $this->renderDate();
        $imageHtml = $this->renderImage();
        $badgeHtml = $this->renderBadge();
        $metadataHtml = $this->renderMetadata();
        
        $title = htmlspecialchars($this->title);
        $description = htmlspecialchars($this->description);
        
        $cardContent = <<<HTML
        {$dateHtml}
        {$imageHtml}
        <div class="card-content">
            <h3>{$title}</h3>
            {$badgeHtml}
            <p>{$description}</p>
            {$metadataHtml}
        </div>
HTML;
        
        // Si un lien est fourni, envelopper dans un <a>
        if ($this->link) {
            return <<<HTML
            <a href="{$this->link}" class="{$this->customClass}">
                {$cardContent}
            </a>
HTML;
        }
        
        return <<<HTML
        <div class="{$this->customClass}">
            {$cardContent}
        </div>
HTML;
    }
    /**
     * Afficher directement la carte
     */
    public function display() {
        echo $this->render();
    }
    
    /**
     * Méthode statique pour créer et afficher rapidement une carte
     */
    public static function create($options) {
        $card = new self($options);
        return $card->render();
    }
}