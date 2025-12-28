<?php
class Partner {
    private $conn;
    private $table = 'partners';
    
    public $id;
    public $name;
    public $logo;
    public $type;
    public $website;
    
    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }
    
    // Récupérer tous les partenaires
    public function getAll() {
        $query = "SELECT * FROM " . $this->table . " ORDER BY nom ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Récupérer par type
    public function getByType($type) {
        $query = "SELECT * FROM " . $this->table . " WHERE type = :type ORDER BY nom ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':type', $type);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
