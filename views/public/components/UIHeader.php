<?php
require_once 'Component.php';
require_once 'UIMenu.php';

class UIHeader extends Component {
    private $pageTitle;
    private $config;
    private $menuData;
    private $customCss; 

    private $adminKeys = [
         [
            'title' => 'Profile',
            'url'   => 'index.php?route=admin-dashboard'
        ],
        [
            'title' => 'Projets',
            'url'   => 'index.php?route=admin-projects'
        ],
        [
            'title' => 'Équipes',
            'url'   => 'index.php?route=admin-teams'
        ],
        [
            'title' => 'Équipements',
            'url'   => 'index.php?route=admin-equipement'
        ],
       
        [
            'title' => 'Publications',
            'url'   => 'index.php?route=admin-publications'
        ],
        [
            'title' => 'Événements',
            'url'   => 'index.php?route=admin-events'
        ],
        [
            'title' => 'Administration', 
            'url'   => '#', 
            'children' => [
                [
                    'title' => 'Opportunités',
                    'url'   => 'index.php?route=opportunities-admin'
                ],
                [
                    'title' => 'Partenaires',
                    'url'   => 'index.php?route=admin-partners'
                ],
                [
                    'title' => 'Paramètres',
                    'url'   => 'index.php?route=admin-settings'
                ],
                [
                    'title' => 'Déconnexion',
                    'url'   => 'index.php?route=logout'
                ]
            ]
        ]
    ];

    public function __construct($pageTitle, $config, $menuData, $customCss = []) {
        $this->pageTitle = $pageTitle;
        $this->config = $config;
        $this->menuData = $menuData;
        $this->customCss = $customCss;
    }
    
    public function render() {
        $pColor = $this->config['primary_color'] ?? '#4e73df';
        $siteName = $this->config['site_name'] ?? 'Laboratoire';
        $logo = $this->config['logo_path'] ?? '../assets/img/logo_default.png';
        
        $isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
        $isMember = isset($_SESSION['user_id']); 
        
        $menuHtml = '';

        if ($isAdmin) {
            $menuComponent = new UIMenu($this->adminKeys);
            $menuHtml = $menuComponent->render();
        } else {
            $finalMenuData = [];
            
            foreach ($this->menuData as $item) {
                if (!$isMember && ($item['title'] === 'Équipements' || $item['title'] === 'Déconnexion')) {
                    continue;
                }
                
                if ($isMember && $item['title'] === 'Connexion') {
                    continue;
                }

                $finalMenuData[] = $item;
                
            }
            
            $menuComponent = new UIMenu($finalMenuData);
            $menuHtml = $menuComponent->render();
        }
        
        $cssLinks = '';
        $cssLinks .= '<link rel="stylesheet" href="../views/css/public.css">';
        $cssLinks .= '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">';
        
        if (!empty($this->customCss)) {
            foreach ($this->customCss as $cssFile) {
                $cssLinks .= '<link rel="stylesheet" href="' . htmlspecialchars($cssFile) . '">';
            }
        }
        
        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$this->pageTitle} - {$siteName}</title>
    {$cssLinks}
    <style>
        :root { --primary-color: {$pColor}; }
        
        .navbar ul li { position: relative; }
        .navbar ul li ul {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            background: white;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            min-width: 180px;
            z-index: 1000;
            border-radius: 4px;
            padding: 0;
        }
        .navbar ul li:hover > ul { display: block; }
        .navbar ul li ul li { display: block; width: 100%; }
        .navbar ul li ul li a {
            display: block;
            padding: 10px 15px;
            color: #333;
            text-decoration: none;
            white-space: nowrap;
        }
        .navbar ul li ul li a:hover { background: #f8f9fa; color: var(--primary-color); }
    </style>
</head>
<body>
    <header>
        <div class="top-bar">
            <nav class="navbar">
                <a href="index.php" class="logo">
                    <img src="../../../{$logo}" alt="Logo">
                    <span class="site-name">{$siteName}</span>
                </a>
                {$menuHtml}
            </nav>
            <div class="social-links">
                <a href="#"><i class="fab fa-facebook"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-linkedin"></i></a>
                <div class="univ-link">
                    <span>|</span>
                    <a href="#">🌐</a>
                </div>
            </div>
        </div>
    </header>
HTML;
    }
}
?>