<?php
require_once __DIR__ . '/Component.php';

class UIMenu extends Component {

    public function render() {
        if (empty($this->data)) return '';

        $html = '<ul class="nav-links">';
        
        foreach ($this->data as $item) {
            $title = htmlspecialchars($item['title'] ?? $item['label']); // Adaptation selon votre BDD
            $url = htmlspecialchars($item['url'] ?? $item['link']);
            
            // Gestion simplifiée (ajoutez la récursivité ici si vous avez des sous-menus)
            $html .= "<li><a href='$url'>$title</a></li>";
        }
        
        

        

        return $html;
    }
}
?>