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
    public function getAllPartners() {
        $sql = "SELECT * FROM partners ORDER BY date_partenariat DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $sql = "SELECT * FROM partners WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createPartner($data) {
        $sql = "INSERT INTO partners (nom, type, logo, url, description, email_contact, date_partenariat) 
                VALUES (:nom, :type, :logo, :url, :description, :email_contact, :date_partenariat)";
        
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':nom' => $data['nom'],
            ':type' => $data['type'],
            ':logo' => $data['logo'], // Chemin du fichier
            ':url' => $data['url'],
            ':description' => $data['description'],
            ':email_contact' => $data['email_contact'],
            ':date_partenariat' => !empty($data['date_partenariat']) ? $data['date_partenariat'] : date('Y-m-d')
        ]);
    }

    public function updatePartner($id, $data) {
        $sql = "UPDATE partners SET 
                nom = :nom, 
                type = :type, 
                url = :url, 
                description = :description, 
                email_contact = :email_contact, 
                date_partenariat = :date_partenariat";
        
        // Si on met à jour le logo
        if (!empty($data['logo'])) {
            $sql .= ", logo = :logo";
        }

        $sql .= " WHERE id = :id";
        
        $params = [
            ':id' => $id,
            ':nom' => $data['nom'],
            ':type' => $data['type'],
            ':url' => $data['url'],
            ':description' => $data['description'],
            ':email_contact' => $data['email_contact'],
            ':date_partenariat' => $data['date_partenariat']
        ];

        if (!empty($data['logo'])) {
            $params[':logo'] = $data['logo'];
        }

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($params);
    }

    public function deletePartner($id) {
        $sql = "DELETE FROM partners WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    public function getStats() {
        $stats = ['total' => 0];
        $stmt = $this->conn->query("SELECT COUNT(*) as c FROM partners");
        $stats['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['c'];
        return $stats;
    }
}
?>
