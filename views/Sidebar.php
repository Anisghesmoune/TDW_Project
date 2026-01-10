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

    // Dictionnaire des icônes standards pour mappage automatique
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
        'Historique'      => '🕒',
        'Mon Espace'      => '👤',
        'Mon Profil'      => '✏️',
        'Accueil'         => '🏠',
        'Contact'         => '📞',
        'Déconnexion'     => '🚪'
    ];

    public function __construct(string $role) {
        $this->settingsModel = new Settings();
        $this->menuModel = new Menu();
        
        $this->config = $this->settingsModel->getAllSettings();
        // Couleurs par défaut si non définies
        $this->pColor = $this->config['primary_color'] ?? '#e74c3c';
        $this->sColor = $this->config['sidebar_color'] ?? '#2c3e50';

        $this->buildMenu($role);
    }

    private function buildMenu() {
        // Vérification du rôle Admin (via la session)
        $isAdmin = (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === 1) ;

       
        if ($isAdmin) {
            $this->items[] = [
                'label' => "Vue d'ensemble",
                'icon'  => $this->standardIcons["Vue d'ensemble"],
                'link'  => 'index.php?route=admin-dashboard'
            ];

            // Liste des liens Admin codés en dur
            $adminKeys = [
                'Projets'      => 'index.php?route=admin-projects',
                'Équipes'      => 'index.php?route=admin-teams',
                'Équipements'  => 'index.php?route=admin-equipement',
                'Historique'   => 'index.php?route=reservation-history',
                'Publications' => 'index.php?route=admin-publications',
                'Événements'   => 'index.php?route=admin-events',
                'Paramètres'   => 'views/Settings.php' 
            ];

            foreach ($adminKeys as $label => $link) {
                $this->items[] = [
                    'label' => $label,
                    'icon'  => $this->standardIcons[$label] ?? $this->defaultIcon,
                    'link'  => $link
                ];
            }
        } 
      
        else {
            // 1. Liens fixes pour le membre connecté
            $this->items[] = [
                'label' => "Mon Espace",
                'icon'  => $this->standardIcons["Mon Espace"], 
                'link'  => 'index.php?route=dashboard-user'
            ];
            
            $this->items[] = [
                'label' => "Mon Profil",
                'icon'  => $this->standardIcons["Mon Profil"],
                'link'  => 'index.php?route=profile-user'
            ];

            // 2. Liens Dynamiques (Gérés depuis les Paramètres)
            $dbItems = $this->menuModel->getAll(); 
            
            if (!empty($dbItems)) {
                foreach ($dbItems as $dbItem) {
                    $label = $dbItem['title'] ?? $dbItem['label'];
                    $dbIcon = $dbItem['icon'] ?? '';
                    $url = $dbItem['url'] ?? $dbItem['link'];

                    // --- Gestion de l'icône ---
                    if (!empty($dbIcon)) {
                        $finalIcon = $dbIcon;
                    } elseif (isset($this->standardIcons[$label])) {
                        $finalIcon = $this->standardIcons[$label];
                    } else {
                        $finalIcon = $this->defaultIcon;
                    }

                    // --- Correction automatique de l'URL ---
                    // Si l'utilisateur a écrit juste "projects" au lieu de "index.php?route=projects"
                    if (strpos($url, 'index.php') === false && strpos($url, 'http') === false) {
                        $url = 'index.php?route=' . $url;
                    }

                    $this->items[] = [
                        'label' => $label,
                        'icon'  => $finalIcon,
                        'link'  => $url
                    ];
                }
            }
        }

        // =========================================================
        // COMMUN : DÉCONNEXION (Toujours à la fin)
        // =========================================================
        $this->items[] = [
            'label' => "Déconnexion",
            'icon'  => $this->standardIcons["Déconnexion"],
            'link'  => 'index.php?route=logout',
            'class' => 'logout-link'
        ];
    }

    public function render() {
        // Injection CSS Dynamique pour les couleurs
        echo "<style>
            :root {
                --primary-color: {$this->pColor};
                --sidebar-color: {$this->sColor};
            }
        </style>";

        echo '<ul class="sidebar-menu">';

        // LOGIQUE ACTIVE CLASS (Mise en surbrillance du lien actuel)
        $currentRoute = $_GET['route'] ?? 'home'; 
        // On récupère le nom du script actuel (ex: Settings.php) pour le cas Paramètres
        $currentScript = basename($_SERVER['PHP_SELF']); 

        foreach ($this->items as $item) {
            $isActive = false;

            // Analyse du lien pour voir s'il correspond à la page actuelle
            if (strpos($item['link'], 'route=') !== false) {
                // Comparaison par paramètre route
                $parsedUrl = parse_url($item['link']);
                if (isset($parsedUrl['query'])) {
                    parse_str($parsedUrl['query'], $queryParams);
                    if (isset($queryParams['route']) && $queryParams['route'] === $currentRoute) {
                        $isActive = true;
                    }
                }
            } elseif (strpos($item['link'], $currentScript) !== false) {
                // Comparaison par nom de fichier (ex: views/Settings.php)
                $isActive = true;
            }

            $activeClass = $isActive ? 'active' : '';
            $icon = $item['icon'] ?: $this->defaultIcon;
            $extraClass = $item['class'] ?? '';

            echo "
                <li>
                    <a href='{$item['link']}' class='{$activeClass} {$extraClass}'>
                        {$icon} {$item['label']}
                    </a>
                </li>
            ";
        }

        echo '</ul>';
    }
}
?>