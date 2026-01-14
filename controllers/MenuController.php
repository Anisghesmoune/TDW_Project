<?php
require_once __DIR__ . 'models/Menu.php';

class MenuController {
    private $menuModel;

    public function __construct() {
        $this->menuModel = new Menu(); 
    }

 
    public function index() {
        $menuTree = $this->menuModel->getMenuTree();
        include 'views/menu.php'; 
    }

   
    public function getMenuTree() {
        return $this->menuModel->getMenuTree();
    }
}
