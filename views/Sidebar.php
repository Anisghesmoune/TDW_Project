<?php
require_once __DIR__ . '/../models/Settings.php';
require_once __DIR__ . '/../models/Menu.php'; 

class Sidebar {
    private $settingsModel;
    private $menuModel;
    private $config;
    private $pColor;
    private $sColor;
    private array $items = [];

    // Icône générique de secours
    private $defaultIcon = '🔗'; 

    // Dictionnaire des icônes standards (comme l'admin)
    private $standardIcons = [
        'Vue d\'ensemble' => '📊',
        'Utilisateurs'    => '👥',
        'Équipes'         => '🔬',
        'Projets'         => '📁',
        'Publications'    => '📄',
        'Équipements'     => '🖥️',
        'Événements'      => '📅',
        'Actualités'      => '📰',
        'Partenaires'     => '🤝',
        'Paramètres'      => '⚙️',
        'Accueil'         => '🏠',
        'Contact'         => '📧'
    ];

    public function __construct(string $role) {
        $this->settingsModel = new Settings();
        $this->menuModel = new Menu();
        
        $this->config = $this->settingsModel->getAllSettings();
        $this->pColor = $this->config['primary_color'] ?? '#e74c3c';
        $this->sColor = $this->config['sidebar_color'] ?? '#2c3e50';

        $this->buildMenu($role);
    }

    private function buildMenu(string $role) {
        // --- A. LIEN DASHBOARD ---
        $this->items[] = [
            'label' => "Vue d'ensemble",
            'icon'  => $this->standardIcons["Vue d'ensemble"],
            'link'  => 'dashboard.php'
        ];
        $isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
        // --- B. LIENS ADMINISTRATEUR ---
        if ($isAdmin) {
            // Liste des clés à ajouter pour l'admin
            $adminKeys = [
                'Utilisateurs' => 'users.php',
                'Équipes'      => 'team-management.php',
                'Projets'      => 'manage-projects.php',
                'Publications' => 'publications.php',
                'Équipements'  => 'equipement_management.php',
                'Événements'   => 'event-management.php',
                'Actualités'   => 'news.php',
                'Partenaires'  => 'partners.php',
                'Paramètres'   => 'settings.php'
            ];

            foreach ($adminKeys as $label => $link) {
                $this->items[] = [
                    'label' => $label,
                    'icon'  => $this->standardIcons[$label] ?? $this->defaultIcon,
                    'link'  => $link
                ];
            }
        }

        // --- C. LIENS DYNAMIQUES (Base de Données) ---
        $dbItems = $this->menuModel->getAll(); 
        
        if (!empty($dbItems)) {
            foreach ($dbItems as $dbItem) {
                $label = $dbItem['title'] ?? $dbItem['label'];
                $dbIcon = $dbItem['icon'] ?? '';
                
                // LOGIQUE INTELLIGENTE :
                // 1. Si icône en BDD -> on l'utilise
                // 2. Sinon, on cherche si le label existe dans les icônes standards (ex: "Projets")
                // 3. Sinon, on met l'icône par défaut (🔗)
                
                if (!empty($dbIcon)) {
                    $finalIcon = $dbIcon;
                } elseif (isset($this->standardIcons[$label])) {
                    $finalIcon = $this->standardIcons[$label];
                } else {
                    $finalIcon = $this->defaultIcon;
                }

                $this->items[] = [
                    'label' => $label,
                    'icon'  => $finalIcon,
                    'link'  => $dbItem['url'] ?? $dbItem['link']
                ];
            }
        }
    }

    public function render() {
        // Injection CSS Dynamique
        echo "<style>
            :root {
                --primary-color: {$this->pColor};
                --sidebar-color: {$this->sColor};
            }
        </style>";

        echo '<ul class="sidebar-menu">';

        $currentPage = basename($_SERVER['PHP_SELF']); 

        foreach ($this->items as $item) {
            $linkPage = basename($item['link']);
            $activeClass = ($currentPage === $linkPage) ? 'active' : '';
            
            // Dernière sécurité si l'icône est vide (ne devrait pas arriver avec la logique ci-dessus)
            $icon = $item['icon'] ?: $this->defaultIcon;

            echo "
                <li>
                    <a href='{$item['link']}' class='{$activeClass}'>
                        {$icon} {$item['label']}
                    </a>
                </li>
            ";
        }

        echo '</ul>';
    }
}
?>