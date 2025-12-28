<?php
class Sidebar {

    private array $items = [];

    public function __construct(string $role) {
        $this->buildMenu($role);
    }

    private function buildMenu(string $role) {

        // Menu commun
        $this->items[] = [
            'label' => "Vue d'ensemble",
            'icon'  => '📊',
            'link'  => 'dashboard.php'
        ];

        // Admin uniquement
        if ($role === 'admin') {
            $this->items[] = ['label' => 'Utilisateurs',  'icon' => '👥', 'link' => 'users.php'];
            $this->items[] = ['label' => 'Équipes',       'icon' => '🔬', 'link' => 'teams.php'];
            $this->items[] = ['label' => 'Projets',       'icon' => '📁', 'link' => 'projects.php'];
            $this->items[] = ['label' => 'Publications',  'icon' => '📄', 'link' => 'publications.php'];
            $this->items[] = ['label' => 'Équipements',   'icon' => '🖥️', 'link' => 'equipment.php'];
            $this->items[] = ['label' => 'Événements',    'icon' => '📅', 'link' => 'events.php'];
            $this->items[] = ['label' => 'Actualités',    'icon' => '📰', 'link' => 'news.php'];
            $this->items[] = ['label' => 'Partenaires',   'icon' => '🤝', 'link' => 'partners.php'];
            $this->items[] = ['label' => 'Paramètres',    'icon' => '⚙️', 'link' => 'settings.php'];
        }
    }

    public function render() {
        echo '<ul class="sidebar-menu">';

        foreach ($this->items as $item) {
            echo "
                <li>
                    <a href='{$item['link']}'>
                        {$item['icon']} {$item['label']}
                    </a>
                </li>
            ";
        }

        echo '</ul>';
    }
}
