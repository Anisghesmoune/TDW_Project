<?php
require_once __DIR__ . '/Component.php';

class UICard extends Component {
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
        $this->customClass = $options['class'] ?? 'generic-card';
    }

    private function renderDate() {
        if (empty($this->date)) {
            return '';
        }

        $day = $this->date['day'] ?? '';
        $month = $this->date['month'] ?? '';

      return <<<HTML
        <div class="">
            <span class="day">{$day}</span>   
            <span class="month">{$month}</span> 
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
        if (empty($this->badge)) return '';
        
        $icon = $this->badge['icon'] ?? '';
        $text = $this->badge['text'] ?? '';
        
        return <<<HTML
        <div class="card-badge">
            <span class="badge-icon">{$icon}</span> 
            <span class="badge-text">{$text}</span>
        </div>
HTML;
    }

    private function renderMetadata() {
        if (empty($this->metadata)) return '';
        
        $metaHtml = '';
        foreach($this->metadata as $m) {
            $metaHtml .= '<span>' . htmlspecialchars($m) . '</span>';
        }
        
        return <<<HTML
        <div class="card-meta">
            {$metaHtml}
        </div>
HTML;
    }

    public function render() {
        $dateHtml = $this->renderDate();
        $imageHtml = $this->renderImage();
        $badgeHtml = $this->renderBadge();
        $metadataHtml = $this->renderMetadata();
        
        $title = htmlspecialchars($this->title);
        $description = htmlspecialchars($this->description);
        
        $cardContent = <<<HTML
        
        <div class="event-card">
            {$dateHtml}
        {$imageHtml}
            {$badgeHtml}
            <h3>{$title}</h3>
            <p>{$description}</p>
            {$metadataHtml}
            <span class="card-link"></span>
        </div>
HTML;
        
        if ($this->link) {
            return <<<HTML
            <a href="{$this->link}" class="{$this->customClass}" style="text-decoration:none; color:inherit;">
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

    public function display() {
        echo $this->render();
    }
}
?>