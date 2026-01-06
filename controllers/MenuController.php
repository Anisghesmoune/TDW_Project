<?php
require_once __DIR__ . 'models/Menu.php';

class MenuController {
    private $menuModel;

    public function __construct() {
        $this->menuModel = new Menu(); // On utilise le MODEL, pas un autre controller
    }

    /**
     * Récupérer le menu complet pour une vue
     */
    public function index() {
        $menuTree = $this->menuModel->getMenuTree();
        include 'views/menu.php'; // Vue qui affichera le menu
    }

    /**
     * Pour LandingController ou d'autres controllers
     * Retourne directement le menu en tableau
     */
    public function getMenuTree() {
        return $this->menuModel->getMenuTree();
    }
}
