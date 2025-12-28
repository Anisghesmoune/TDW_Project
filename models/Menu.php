<?php
class Menu extends Model {
    public $id;
    public $name;
    public $price;
    public $category;
    
    public function __construct() {
        $this->table = 'menu_items';
        parent::__construct();
    }
    
    
    // Récupérer tous les items racines du menu
  
    
    // Récupérer les sous-menus d'un item
    public function getSubmenu($parentId) {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE parent_id = :parent_id 
                  ORDER BY `order` ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':parent_id', $parentId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Récupérer le menu complet en arbre
    public function getMenuTree($parentId = null) {
        // Si $parentId == null → on prend tous les items
        $menus = $parentId === null ? $this->getAllItems() : $this->getSubmenu($parentId);
        return $this->buildTree($menus);
    }

    // Récupérer **tous les items**, pas seulement les racines
    private function getAllItems() {
        $query = "SELECT * FROM " . $this->table . " ORDER BY `order` ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Construire l'arbre récursivement
    private function buildTree($items, $parentId = null) {
        $branch = [];

        foreach ($items as $item) {
            if ($item['parent_id'] == $parentId) {
                $item['children'] = $this->buildTree($items, $item['id']);
                $branch[] = $item;
            }
        }

        return $branch;
    }
}
?>
