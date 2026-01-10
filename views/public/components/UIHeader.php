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
        'title' => 'Historique',
        'url'   => 'index.php?route=reservation-history'
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
        'title' => 'Paramètres',
        'url'   => 'index.php?route=admin-settings'
    ]
];

    
    // Ajout du paramètre $customCss (tableau)
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
        
        // Vérification si l'utilisateur est admin
        $isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
        
        // Génération du Menu
        if ($isAdmin) {
            // Menu Admin Statique
           $menuComponent = new UIMenu($this->adminKeys);
            $menuHtml = $menuComponent->render();


        } else {
            // Menu Public Dynamique
            $menuComponent = new UIMenu($this->menuData);
            $menuHtml = $menuComponent->render();
        }
        
        // Génération des liens CSS dynamiques
        $cssLinks = '';
        // CSS de base toujours présent
        $cssLinks .= '<link rel="stylesheet" href="../views/css/public.css">';
        $cssLinks .= '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">';
        
        // Ajout des CSS spécifiques passés en paramètre
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
    <!-- Injection des fichiers CSS -->
    {$cssLinks}
    <style>:root { --primary-color: {$pColor}; }</style>
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