<?php
require_once 'Component.php';

class UIHeader extends Component {
    private $title;
    private $config;
    private $menuItems;

    public function __construct($title, $config) {
        $this->title = $title;
        $this->config = $config;
        $this->menuItems = [
            'Accueil' => 'index.php',
            'Projets' => 'projects.php',
            'Publications' => 'publications.php',
            'Équipements' => 'equipments.php',
            'Équipes' => 'teams.php',
            'Contact' => 'contact.php'
        ];
    }

    public function render() {
        $pColor = $this->config['primary_color'] ?? '#4e73df';
        $siteName = $this->config['site_name'] ?? 'Laboratoire';
        $logo = $this->config['logo_path'] ?? '../assets/img/logo_default.png';

        // Construction du menu
        $navLinks = "";
        foreach ($this->menuItems as $label => $link) {
            $navLinks .= "<li><a href='$link'>$label</a></li>";
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$this->title} - {$siteName}</title>
    <link rel="stylesheet" href="../assets/css/public.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>:root { --primary-color: {$pColor}; }</style>
</head>
<body>
<header>
    <div class="top-bar">
        <a href="#"><i class="fab fa-facebook"></i></a>
        <a href="#"><i class="fab fa-twitter"></i></a>
        <span>|</span>
        <a href="#">Université</a>
    </div>
    <nav class="navbar">
        <a href="index.php" class="logo">
            <img src="../{$logo}" alt="Logo">
            <span style="margin-left:10px; font-weight:bold; color:#333;">{$siteName}</span>
        </a>
        <ul class="nav-links">
            {$navLinks}
            <li><a href="../login.php" class="btn-login">Accès Membre</a></li>
        </ul>
    </nav>
</header>
HTML;
    }
}
?>