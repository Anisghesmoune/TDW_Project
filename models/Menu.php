<?php
require_once 'Model.php';

class Menu extends Model {
    
    public function __construct() {
        $this->table = 'menu_items';
        parent::__construct();
    }
    
    // =========================================================
    // PARTIE 1 : LECTURE ET ARBORESCENCE (EXISTANT + AMÉLIORÉ)
    // =========================================================

    /**
     * Récupérer tous les items à plat (utilisé pour l'admin)
     */
  
    /**
     * Récupérer les sous-menus d'un item
     */
    public function getSubmenu($parentId) {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE parent_id = :parent_id 
                  AND is_active = 1 
                  ORDER BY `order` ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':parent_id', $parentId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupérer le menu complet en arbre
     */
    public function getMenuTree($parentId = null) {
        // On récupère tout en une seule requête pour optimiser
        $items = $this->getAllItems();
        return $this->buildTree($items, $parentId);
    }

    // Récupérer tous les items actifs
    private function getAllItems() {
        $query = "SELECT * FROM " . $this->table . " WHERE is_active = 1 ORDER BY `order` ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Construire l'arbre récursivement
    private function buildTree($items, $parentId = null) {
        $branch = [];

        foreach ($items as $item) {
            // Comparaison souple (==) pour gérer null vs 0
            if ($item['parent_id'] == $parentId) {
                $children = $this->buildTree($items, $item['id']);
                if ($children) {
                    $item['children'] = $children;
                }
                $branch[] = $item;
            }
        }

        return $branch;
    }

    // =========================================================
    // PARTIE 2 : MODIFICATION DU MENU (NOUVELLES FONCTIONS)
    // =========================================================

    /**
     * Ajouter un nouvel élément de menu
     */
    public function create($title, $url, $order = 0, $parent_id = null) {
        try {
            $query = "INSERT INTO " . $this->table . " (title, url, `order`, parent_id, is_active) 
                      VALUES (:title, :url, :order, :parent_id, 1)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':title', $title);
            $stmt->bindValue(':url', $url);
            $stmt->bindValue(':order', $order, PDO::PARAM_INT);
            $stmt->bindValue(':parent_id', $parent_id, $parent_id ? PDO::PARAM_INT : PDO::PARAM_NULL);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Mettre à jour un élément existant
     */
    public function update($id, $title, $url, $order, $parent_id = null) {
        try {
            $query = "UPDATE " . $this->table . " 
                      SET title = :title, 
                          url = :url, 
                          `order` = :order, 
                          parent_id = :parent_id 
                      WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->bindValue(':title', $title);
            $stmt->bindValue(':url', $url);
            $stmt->bindValue(':order', $order, PDO::PARAM_INT);
            $stmt->bindValue(':parent_id', $parent_id, $parent_id ? PDO::PARAM_INT : PDO::PARAM_NULL);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Supprimer un élément
     */
    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Méthode "Nucléaire" pour la sauvegarde globale depuis les Paramètres
     * (Vide la table et réinsère tout, utile pour gérer l'ordre facilement via JS)
     */
    public function replaceAll($items) {
        try {
            $this->conn->beginTransaction();
            
            // 1. Vider la table
            $this->conn->exec("TRUNCATE TABLE " . $this->table); // Ou DELETE FROM si contraintes clés étrangères
            
            // 2. Préparer l'insertion
            $query = "INSERT INTO " . $this->table . " (title, url, `order`, parent_id, is_active) 
                      VALUES (:title, :url, :order, :parent_id, 1)";
            $stmt = $this->conn->prepare($query);
            
            // 3. Insérer chaque item
            foreach ($items as $item) {
                // Gestion des champs manquants éventuels
                $ordre = isset($item['ordre']) ? $item['ordre'] : (isset($item['order']) ? $item['order'] : 0);
                
                $stmt->bindValue(':title', $item['title']);
                $stmt->bindValue(':url', $item['url']);
                $stmt->bindValue(':order', $ordre, PDO::PARAM_INT);
                $stmt->bindValue(':parent_id', $item['parent_id'] ?? null, PDO::PARAM_NULL);
                $stmt->execute();
            }
            
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }
}
?>