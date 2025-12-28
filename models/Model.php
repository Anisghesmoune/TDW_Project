<?php
abstract class Model {
    protected $conn;
    protected $table;
    
    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }
    
    /**
     * Récupérer tous les enregistrements
     * @param string $orderBy - Colonne pour le tri (défaut: 'id')
     * @param string $order - Type d'ordre: 'ASC' ou 'DESC' (défaut: 'DESC')
     */
   public function getAll($orderBy = 'id', $order = 'DESC', $limit = null) {
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
        $query = "SELECT * FROM " . $this->table . " ORDER BY " . $orderBy . " " . $order;
        
        if ($limit !== null) {
            $query .= " LIMIT :limit";
        }
        
        $stmt = $this->conn->prepare($query);
        
        if ($limit !== null) {
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Récupérer un enregistrement par ID
     */
    public function getById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Compter le nombre total d'enregistrements
     */
    public function count() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }
    
    /**
     * Récupérer les enregistrements avec pagination
     * @param int $page - Numéro de la page
     * @param int $perPage - Nombre d'éléments par page
     * @param string $orderBy - Colonne pour le tri (défaut: 'id')
     * @param string $order - Type d'ordre: 'ASC' ou 'DESC' (défaut: 'DESC')
     */
    public function getPaginated($page = 1, $perPage = 10, $orderBy = 'id', $order = 'DESC') {
        $offset = ($page - 1) * $perPage;
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
        $query = "SELECT * FROM " . $this->table . " 
                  ORDER BY " . $orderBy . " " . $order . " LIMIT :limit OFFSET :offset";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Supprimer un enregistrement par ID
     */
    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
    
    /**
     * Vérifier si un enregistrement existe
     */
    public function exists($id) {
        $query = "SELECT COUNT(*) as count FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['count'] > 0;
    }
    public function getByColumn($column, $value, $orderBy = 'id', $order = 'DESC', $limit = null) {
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE " . $column . " = :value 
                  ORDER BY " . $orderBy . " " . $order;
        
        if ($limit !== null) {
            $query .= " LIMIT :limit";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':value', $value);
        
        if ($limit !== null) {
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>