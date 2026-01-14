<?php
require_once __DIR__ . '/Model.php';

class ThematicModel extends Model {
    protected $table = 'thematics';
    
   
    public function getById($id) {
        $query = "SELECT * FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
  
    public function create($data) {
        $query = "INSERT INTO {$this->table} (nom, description) 
                  VALUES (:nom, :description)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':nom', $data['nom']);
        $stmt->bindParam(':description', $data['description']);
        
        return $stmt->execute();
    }
    
  
    public function update($id, $data) {
        $query = "UPDATE {$this->table} 
                  SET nom = :nom, 
                      description = :description
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':nom', $data['nom']);
        $stmt->bindParam(':description', $data['description']);
        
        return $stmt->execute();
    }
    
   
    public function delete($id) {
        $queryAssoc = "DELETE FROM project_thematique WHERE thematic_id = :id";
        $stmtAssoc = $this->conn->prepare($queryAssoc);
        $stmtAssoc->bindParam(':id', $id, PDO::PARAM_INT);
        $stmtAssoc->execute();
        
        $query = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }
    
 
    public function countProjects($thematicId) {
        $query = "SELECT COUNT(*) as count 
                  FROM project_thematique 
                  WHERE thematic_id = :thematic_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':thematic_id', $thematicId, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] ?? 0;
    }
    
  
    public function search($keyword) {
        $query = "SELECT * FROM {$this->table} 
                  WHERE nom LIKE :keyword 
                     OR description LIKE :keyword
                  ORDER BY nom ASC";
        
        $stmt = $this->conn->prepare($query);
        $searchTerm = "%{$keyword}%";
        $stmt->bindParam(':keyword', $searchTerm);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
  
    public function getMostUsed($limit = 10) {
        $query = "SELECT 
                    t.*,
                    COUNT(pt.project_id) as project_count
                  FROM {$this->table} t
                  LEFT JOIN project_thematique pt ON t.id = pt.thematic_id
                  GROUP BY t.id
                  ORDER BY project_count DESC
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}