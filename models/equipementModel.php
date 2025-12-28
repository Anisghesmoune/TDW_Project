<?php
class Equipment extends Model {
    protected $table = 'equipment';
    
    /**
     * Create a new equipment
     */
    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                  (nom, id_type, etat, description, localisation, derniere_maintenance, prochaine_maintenance) 
                  VALUES (:nom, :id_type, :etat, :description, :localisation, :derniere_maintenance, :prochaine_maintenance)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':nom', $data['nom']);
        $stmt->bindParam(':id_type', $data['id_type'], PDO::PARAM_INT);
        $stmt->bindParam(':etat', $data['etat']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':localisation', $data['localisation']);
        $stmt->bindParam(':derniere_maintenance', $data['derniere_maintenance']);
        $stmt->bindParam(':prochaine_maintenance', $data['prochaine_maintenance']);
        
        return $stmt->execute();
    }
    
    /**
     * Update equipment
     */
    public function update($id, $data) {
        $query = "UPDATE " . $this->table . " 
                  SET nom = :nom, 
                      id_type = :id_type, 
                      etat = :etat, 
                      description = :description, 
                      localisation = :localisation, 
                      derniere_maintenance = :derniere_maintenance, 
                      prochaine_maintenance = :prochaine_maintenance 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':nom', $data['nom']);
        $stmt->bindParam(':id_type', $data['id_type'], PDO::PARAM_INT);
        $stmt->bindParam(':etat', $data['etat']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':localisation', $data['localisation']);
        $stmt->bindParam(':derniere_maintenance', $data['derniere_maintenance']);
        $stmt->bindParam(':prochaine_maintenance', $data['prochaine_maintenance']);
        
        return $stmt->execute();
    }
    
    /**
     * Get equipment with type information (JOIN)
     */
    public function getAllWithTypes($orderBy = 'id', $order = 'DESC') {
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
        $query = "SELECT e.*, et.nom as type_nom, et.description as type_description, et.icone 
                  FROM " . $this->table . " e 
                  LEFT JOIN equipment_types et ON e.id_type = et.id 
                  ORDER BY e." . $orderBy . " " . $order;
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get equipment by status
     */
    public function getByStatus($etat, $orderBy = 'id', $order = 'DESC') {
        return $this->getByColumn('etat', $etat, $orderBy, $order);
    }
    
    /**
     * Get equipment by type
     */
    public function getByType($id_type, $orderBy = 'id', $order = 'DESC') {
        return $this->getByColumn('id_type', $id_type, $orderBy, $order);
    }
    
    /**
     * Get equipment needing maintenance (upcoming or overdue)
     */
    public function getMaintenanceNeeded($daysAhead = 30) {
        $query = "SELECT e.*, et.nom as type_nom 
                  FROM " . $this->table . " e 
                  LEFT JOIN equipment_types et ON e.id_type = et.id 
                  WHERE e.prochaine_maintenance IS NOT NULL 
                  AND e.prochaine_maintenance <= DATE_ADD(NOW(), INTERVAL :days DAY) 
                  ORDER BY e.prochaine_maintenance ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':days', $daysAhead, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Update equipment status
     */
    public function updateStatus($id, $etat) {
        $query = "UPDATE " . $this->table . " SET etat = :etat WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':etat', $etat);
        return $stmt->execute();
    }
    
    /**
     * Update maintenance dates
     */
    public function updateMaintenance($id, $derniere_maintenance, $prochaine_maintenance) {
        $query = "UPDATE " . $this->table . " 
                  SET derniere_maintenance = :derniere_maintenance, 
                      prochaine_maintenance = :prochaine_maintenance 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':derniere_maintenance', $derniere_maintenance);
        $stmt->bindParam(':prochaine_maintenance', $prochaine_maintenance);
        return $stmt->execute();
    }
    
    /**
     * Get equipment statistics by status
     */
    public function getStatsByStatus() {
        $query = "SELECT etat, COUNT(*) as count 
                  FROM " . $this->table . " 
                  GROUP BY etat";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get equipment statistics by type
     */
    public function getStatsByType() {
        $query = "SELECT et.nom as type_nom, et.icone, COUNT(e.id) as count 
                  FROM equipment_types et 
                  LEFT JOIN " . $this->table . " e ON et.id = e.id_type 
                  GROUP BY et.id, et.nom, et.icone 
                  ORDER BY count DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Search equipment by name or location
     */
    public function search($searchTerm, $orderBy = 'id', $order = 'DESC') {
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
        $query = "SELECT e.*, et.nom as type_nom, et.icone 
                  FROM " . $this->table . " e 
                  LEFT JOIN equipment_types et ON e.id_type = et.id 
                  WHERE e.nom LIKE :search 
                  OR e.localisation LIKE :search 
                  OR e.description LIKE :search 
                  ORDER BY e." . $orderBy . " " . $order;
        
        $stmt = $this->conn->prepare($query);
        $searchParam = '%' . $searchTerm . '%';
        $stmt->bindParam(':search', $searchParam);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get available equipment (libre status)
     */
    public function getAvailable($orderBy = 'nom', $order = 'ASC') {
        return $this->getByStatus('libre', $orderBy, $order);
    }
    
    /**
     * Check if equipment is available
     */
    public function isAvailable($id) {
        $query = "SELECT etat FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result && $result['etat'] === 'libre';
    }
}
?>