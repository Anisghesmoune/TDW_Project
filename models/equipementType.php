<?php
class EquipmentType extends Model {
    protected $table = 'equipment_types';
    
  
    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                  (nom, description, icone) 
                  VALUES (:nom, :description, :icone)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':nom', $data['nom']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':icone', $data['icone']);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }
    

    public function update($id, $data) {
        $query = "UPDATE " . $this->table . " 
                  SET nom = :nom, 
                      description = :description, 
                      icone = :icone 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':nom', $data['nom']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':icone', $data['icone']);
        
        return $stmt->execute();
    }
    
   
    public function getWithEquipmentCount($id) {
        $query = "SELECT et.*, COUNT(e.id) as equipment_count 
                  FROM " . $this->table . " et 
                  LEFT JOIN equipment e ON et.id = e.id_type 
                  WHERE et.id = :id 
                  GROUP BY et.id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
 
    public function getAllWithCounts($orderBy = 'nom', $order = 'ASC') {
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
        $query = "SELECT et.*, COUNT(e.id) as equipment_count 
                  FROM " . $this->table . " et 
                  LEFT JOIN equipment e ON et.id = e.id_type 
                  GROUP BY et.id, et.nom, et.description, et.icone 
                  ORDER BY et." . $orderBy . " " . $order;
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
   
    public function nameExists($nom, $excludeId = null) {
        $query = "SELECT COUNT(*) as count FROM " . $this->table . " 
                  WHERE nom = :nom";
        
        if ($excludeId !== null) {
            $query .= " AND id != :excludeId";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':nom', $nom);
        
        if ($excludeId !== null) {
            $stmt->bindParam(':excludeId', $excludeId, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['count'] > 0;
    }
    
  
    public function canDelete($id) {
        $query = "SELECT COUNT(*) as count FROM equipment WHERE id_type = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['count'] == 0;
    }
    
  
    public function getEquipmentByType($id, $orderBy = 'nom', $order = 'ASC') {
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
        $query = "SELECT * FROM equipment 
                  WHERE id_type = :id 
                  ORDER BY " . $orderBy . " " . $order;
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
   
    public function search($searchTerm, $orderBy = 'nom', $order = 'ASC') {
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
        $query = "SELECT et.*, COUNT(e.id) as equipment_count 
                  FROM " . $this->table . " et 
                  LEFT JOIN equipment e ON et.id = e.id_type 
                  WHERE et.nom LIKE :search OR et.description LIKE :search 
                  GROUP BY et.id, et.nom, et.description, et.icone 
                  ORDER BY et." . $orderBy . " " . $order;
        
        $stmt = $this->conn->prepare($query);
        $searchParam = '%' . $searchTerm . '%';
        $stmt->bindParam(':search', $searchParam);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    
    public function getTypeStats($id) {
        $query = "SELECT 
                    COUNT(*) as total_equipment,
                    SUM(CASE WHEN etat = 'libre' THEN 1 ELSE 0 END) as available,
                    SUM(CASE WHEN etat = 'réserve' THEN 1 ELSE 0 END) as reserved,
                    SUM(CASE WHEN etat = 'en_maintenance' THEN 1 ELSE 0 END) as in_maintenance
                  FROM equipment 
                  WHERE id_type = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
   
    public function getTypesWithAvailableEquipment($orderBy = 'nom', $order = 'ASC') {
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
        $query = "SELECT DISTINCT et.* 
                  FROM " . $this->table . " et 
                  INNER JOIN equipment e ON et.id = e.id_type 
                  WHERE e.etat = 'libre' 
                  ORDER BY et." . $orderBy . " " . $order;
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
   
    public function getMostUsedTypes($limit = 5) {
        $query = "SELECT et.*, COUNT(r.id) as reservation_count 
                  FROM " . $this->table . " et 
                  LEFT JOIN equipment e ON et.id = e.id_type 
                  LEFT JOIN reservations r ON e.id = r.id_equipement 
                  GROUP BY et.id, et.nom, et.description, et.icone 
                  ORDER BY reservation_count DESC 
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
   
    public function duplicate($id, $newName) {
        $original = $this->getById($id);
        
        if (!$original) {
            return false;
        }
        
        $data = [
            'nom' => $newName,
            'description' => $original['description'],
            'icone' => $original['icone']
        ];
        
        return $this->create($data);
    }
    
   
    public function getPaginatedWithCounts($page = 1, $perPage = 10, $orderBy = 'nom', $order = 'ASC') {
        $offset = ($page - 1) * $perPage;
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
        
        $query = "SELECT et.*, COUNT(e.id) as equipment_count 
                  FROM " . $this->table . " et 
                  LEFT JOIN equipment e ON et.id = e.id_type 
                  GROUP BY et.id, et.nom, et.description, et.icone 
                  ORDER BY et." . $orderBy . " " . $order . " 
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
   
    public function deleteAndReassign($id, $newTypeId = null) {
        try {
            $this->conn->beginTransaction();
            
            if ($newTypeId !== null) {
                $query = "UPDATE equipment SET id_type = :newTypeId WHERE id_type = :oldId";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':newTypeId', $newTypeId, PDO::PARAM_INT);
                $stmt->bindParam(':oldId', $id, PDO::PARAM_INT);
                $stmt->execute();
            }
            
         
            $result = $this->delete($id);
            
            $this->conn->commit();
            return $result;
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }
    
   
   
}
?>