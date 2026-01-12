<?php
require_once __DIR__ . '/Component.php';

class UIMenu extends Component {

    /**
     * Génère le HTML du menu (supporte les sous-menus infinis via récursivité)
     */
    public function render() {
        if (empty($this->data)) return '';

        // On appelle une fonction récursive interne pour gérer les niveaux
        return '<ul class="nav-links">' . $this->renderItems($this->data) . '</ul>';
    }

    /**
     * Méthode privée récursive pour parcourir les items et sous-items
     */
    private function renderItems($items) {
        $html = '';

        foreach ($items as $item) {
            // Gestion des clés variables (title/label, url/link) pour la compatibilité
            $title = htmlspecialchars($item['title'] ?? $item['label'] ?? 'Sans titre');
            $url = htmlspecialchars($item['url'] ?? $item['link'] ?? '#');
            
            // Vérification s'il y a des enfants
            if (isset($item['children']) && is_array($item['children']) && !empty($item['children'])) {
                
                // --- CAS : MENU DÉROULANT ---
                $html .= '<li class="has-submenu">';
                // Le lien parent pointe souvent vers # ou javascript:void(0) pour ne pas recharger la page
                $html .= '<a href="#" class="submenu-toggle">' . $title . ' <i class="fas fa-chevron-down" style="font-size:0.8em; margin-left:5px;"></i></a>';
                
                // Création de la sous-liste
                $html .= '<ul class="submenu">';
                // Appel récursif pour afficher les enfants
                $html .= $this->renderItems($item['children']);
                $html .= '</ul>';
                
                $html .= '</li>';

            } else {
                
                // --- CAS : LIEN SIMPLE ---
                // On vérifie si c'est la page active (optionnel, pour le style)
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