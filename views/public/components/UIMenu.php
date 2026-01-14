<?php
require_once __DIR__ . '/Component.php';

class UIMenu extends Component {

   
    public function render() {
        if (empty($this->data)) return '';

        return '<ul class="nav-links">' . $this->renderItems($this->data) . '</ul>';
    }

    
    private function renderItems($items) {
        $html = '';

        foreach ($items as $item) {
            $title = htmlspecialchars($item['title'] ?? $item['label'] ?? 'Sans titre');
            $url = htmlspecialchars($item['url'] ?? $item['link'] ?? '#');
            
            if (isset($item['children']) && is_array($item['children']) && !empty($item['children'])) {
                
                $html .= '<li class="has-submenu">';
                $html .= '<a href="#" class="submenu-toggle">' . $title . ' <i class="fas fa-chevron-down" style="font-size:0.8em; margin-left:5px;"></i></a>';
                
                $html .= '<ul class="submenu">';
                $html .= $this->renderItems($item['children']);
                $html .= '</ul>';
                
                $html .= '</li>';

            } else {
                
               $activeClass = '';
                if (isset($_GET['route']) && strpos($url, $_GET['route']) !== false) {
                    $activeClass = 'class="active"';
                }

                $html .= "<li><a href='$url' $activeClass>$title</a></li>";
            }
        }

        return $html;
    }
}
?>