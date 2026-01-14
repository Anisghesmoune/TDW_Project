<?php
class News extends Model {
    
    
    public function __construct() {
        $this->table = 'news';
        parent::__construct();
    }
    
  
      
    
   
    public function getRecent($limit = 3) {
        return $this->getAll('date_publication', 'DESC', $limit);
    }
    public function getByType($type, $limit = null) {
        return $this->getByColumn('type', $type, 'date_publication', 'DESC', $limit);
    }
    
    
    public function getForSlider() {
        try {
            $query = "SELECT * FROM " . $this->table . " 
                     WHERE affiche_slider = 1 
                     ORDER BY ordre_slider ASC 
                     LIMIT 5";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Erreur getForSlider: " . $e->getMessage());
            return [];
        }
    }
    
   
}
?>