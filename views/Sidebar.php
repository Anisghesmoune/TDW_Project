<?php
require_once '../models/Settings.php';

class Sidebar {

    // Déclaration des propriétés seulement (sans valeurs dynamiques ici)
    private $settingsModel;
    private $config;
    private $pColor;
    private $sColor;
    private array $items = [];

    public function __construct(string $role) {
        // 1. Initialisation du Modèle et récupération de la config
        $this->settingsModel = new Settings();
        $this->config = $this->settingsModel->getAllSettings();

        // 2. Définition des couleurs (avec valeurs par défaut)
        $this->pColor = $this->config['primary_color'] ?? '#e74c3c';
        $this->sColor = $this->config['sidebar_color'] ?? '#2c3e50';

        // 3. Construction du menu
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
            $this->items[] = ['label' => 'Équipes',       'icon' => '🔬', 'link' => 'team-management.php'];
            $this->items[] = ['label' => 'Projets',       'icon' => '📁', 'link' => 'manage-projects.php'];
            $this->items[] = ['label' => 'Publications',  'icon' => '📄', 'link' => 'publications.php']; // ou index.php dans le dossier publications
            $this->items[] = ['label' => 'Équipements',   'icon' => '🖥️', 'link' => 'equipement_management.php'];
            $this->items[] = ['label' => 'Événements',    'icon' => '📅', 'link' => 'event-management.php'];
            $this->items[] = ['label' => 'Actualités',    'icon' => '📰', 'link' => 'news.php'];
            $this->items[] = ['label' => 'Partenaires',   'icon' => '🤝', 'link' => 'partners.php'];
            $this->items[] = ['label' => 'Paramètres',    'icon' => '⚙️', 'link' => 'settings.php'];
        }
    }

    public function render() {
        // 1. Injection des variables CSS dynamiques
        echo "<style>
            :root {
                --primary-color: {$this->pColor};
                --sidebar-color: {$this->sColor};
            }
        </style>";

        // 2. Affichage du menu
        echo '<ul class="sidebar-menu">';

        // Récupération du nom du fichier actuel pour la classe "active"
        $currentPage = basename($_SERVER['PHP_SELF']);

        foreach ($this->items as $item) {
            // Vérifie si le lien correspond à la page actuelle
            $activeClass = ($currentPage == $item['link']) ? 'active' : '';

            echo "
                <li>
                    <a href='{$item['link']}' class='{$activeClass}'>
                        {$item['icon']} {$item['label']}
                    </a>
                </li>
            ";
        }

        echo '</ul>';
    }
}
?>