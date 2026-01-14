<?php
require_once __DIR__ . '/Model.php';
class Publication extends Model {
    public $id;
    public $titre;
    public $resume;
    public $type;
    public $date_publication;
    public $doi;
    public $lien_telechargement;
    public $domaine;
    public $statut_validation;
    public $soumis_par;
    public $date_soumission;
    
    public function __construct() {
        $this->table = 'publications';
        parent::__construct();
    }
 
    public function create() {
        try {
            $this->conn->beginTransaction();

            $query = "INSERT INTO " . $this->table . " 
                      (titre, resume, type, date_publication, doi, lien_telechargement, 
                       domaine, statut_validation, soumis_par) 
                      VALUES (:titre, :resume, :type, :date_publication, :doi, 
                              :lien_telechargement, :domaine, :statut_validation, :soumis_par)";
            
            $stmt = $this->conn->prepare($query);
            
            $this->titre = htmlspecialchars(strip_tags($this->titre));

            $stmt->bindParam(':titre', $this->titre);
            $stmt->bindParam(':resume', $this->resume);
            $stmt->bindParam(':type', $this->type);
            $stmt->bindParam(':date_publication', $this->date_publication);
            $stmt->bindParam(':doi', $this->doi);
            $stmt->bindParam(':lien_telechargement', $this->lien_telechargement);
            $stmt->bindParam(':domaine', $this->domaine);
            $stmt->bindParam(':statut_validation', $this->statut_validation);
            $stmt->bindParam(':soumis_par', $this->soumis_par);
            
            if (!$stmt->execute()) {
                throw new Exception("Erreur lors de l'insertion de la publication");
            }

            $newPublicationId = $this->conn->lastInsertId();

          
            $queryLink = "INSERT INTO user_publication 
                          (user_id, id_publication, ordre_auteur, auteur_correspondant) 
                          VALUES (:uid, :pid, :ordre, :correspondant)";
            
            $stmtLink = $this->conn->prepare($queryLink);
            
            $stmtLink->bindValue(':uid', $this->soumis_par, PDO::PARAM_INT);
            $stmtLink->bindValue(':pid', $newPublicationId, PDO::PARAM_INT);
            $stmtLink->bindValue(':ordre', 1, PDO::PARAM_INT); 
            $stmtLink->bindValue(':correspondant', 1, PDO::PARAM_INT); 
            
            if (!$stmtLink->execute()) {
                throw new Exception("Erreur lors de la liaison auteur-publication");
            }

          
            $this->conn->commit();
            
            return $newPublicationId; 

        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Erreur création publication : " . $e->getMessage());
            return false;
        }
    }
    
  
    public function update() {
        $query = "UPDATE " . $this->table . " 
                  SET titre = :titre, 
                      resume = :resume, 
                      type = :type,
                      date_publication = :date_publication,
                      doi = :doi,
                      lien_telechargement = :lien_telechargement,
                      domaine = :domaine,
                      statut_validation = :statut_validation
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':titre', $this->titre);
        $stmt->bindParam(':resume', $this->resume);
        $stmt->bindParam(':type', $this->type);
        $stmt->bindParam(':date_publication', $this->date_publication);
        $stmt->bindParam(':doi', $this->doi);
        $stmt->bindParam(':lien_telechargement', $this->lien_telechargement);
        $stmt->bindParam(':domaine', $this->domaine);
        $stmt->bindParam(':statut_validation', $this->statut_validation);
        
        return $stmt->execute();
    }
   public function countByStatus($statut = null) {
        if ($statut === null) {
            return $this->count();
        }
        
        $query = "SELECT COUNT(*) as total FROM " . $this->table . " 
                  WHERE statut_validation = :statut";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':statut', $statut);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'] ?? 0;
    }
    public function countValidated() {
        return $this->countByStatus('valide');
    }
    
  
    public function countPending() {
        return $this->countByStatus('en_attente');
    }
  
    public function getByType($type, $limit = null) {
        return $this->getByColumn('type', $type, 'date_publication', 'DESC', $limit);
    }
    public function getByDomain($domaine, $limit = null) {
        return $this->getByColumn('domaine', $domaine, 'date_publication', 'DESC', $limit);
    }
   
    public function getByValidationStatus($statut, $limit = null) {
        return $this->getByColumn('statut_validation', $statut, 'date_soumission', 'DESC', $limit);
    }
    
 
    public function getByUser($userId, $limit = null) {
        return $this->getByColumn('soumis_par', $userId, 'date_soumission', 'DESC', $limit);
    }
  
    public function countByYear($year) {
        $query = "SELECT COUNT(*) as total FROM " . $this->table . " 
                  WHERE YEAR(date_publication) = :year 
                  AND statut_validation = 'valide'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':year', $year, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'] ?? 0;
    }
     
    public function getRecent($limit = 5) {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE statut_validation = 'valide'
                  ORDER BY date_publication DESC 
                  LIMIT :limit";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
   
    public function validate($id) {
        $query = "UPDATE " . $this->table . " 
                  SET statut_validation = 'valide' 
                  WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
    
   
    public function reject($id) {
        $query = "UPDATE " . $this->table . " 
                  SET statut_validation = 'rejete' 
                  WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
    
   
   public function search($keyword, $limit = null) {
        $term = '%' . $keyword . '%';
        
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE (titre LIKE :k1 
                        OR resume LIKE :k2 
                        OR domaine LIKE :k3)
                  AND statut_validation = 'valide'
                  ORDER BY date_publication DESC";
        
        if ($limit !== null) {
            $query .= " LIMIT :limit";
        }
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindValue(':k1', $term);
        $stmt->bindValue(':k2', $term);
        $stmt->bindValue(':k3', $term);
        
        if ($limit !== null) {
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    
   
    public function getStatsByType() {
        $query = "SELECT type, COUNT(*) as total 
                  FROM " . $this->table . " 
                  WHERE statut_validation = 'valide'
                  GROUP BY type 
                  ORDER BY total DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    

 
    public function getDistinctDomains() {
        try {
            $query = "SELECT DISTINCT domaine FROM publications 
                      WHERE domaine IS NOT NULL 
                      ORDER BY domaine";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $domains = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return ['success' => true, 'data' => $domains];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
  
    public function getDistinctYears() {
        try {
            $query = "SELECT DISTINCT YEAR(date_publication) as year 
                      FROM publications 
                      WHERE date_publication IS NOT NULL 
                      ORDER BY year DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $years = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return ['success' => true, 'data' => $years];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
  
    public function getStatsByDomain() {
        $query = "SELECT domaine, COUNT(*) as total 
                  FROM " . $this->table . " 
                  WHERE statut_validation = 'valide'
                  GROUP BY domaine 
                  ORDER BY total DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getFiltered($page = 1, $perPage = 10, $filters = []) {
        $offset = ($page - 1) * $perPage;
        $conditions = [];
        $params = [];
        
        if (!empty($filters['type'])) {
            $conditions[] = "type = :type";
            $params[':type'] = $filters['type'];
        }
        
        if (!empty($filters['statut'])) {
            $conditions[] = "statut_validation = :statut";
            $params[':statut'] = $filters['statut'];
        }
        
        if (!empty($filters['domaine'])) {
            $conditions[] = "domaine = :domaine";
            $params[':domaine'] = $filters['domaine'];
        }
        
        if (!empty($filters['year'])) {
            $conditions[] = "YEAR(date_publication) = :year";
            $params[':year'] = $filters['year'];
        }
        
        $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
        
        $query = "SELECT * FROM " . $this->table . " 
                  {$whereClause}
                  ORDER BY date_publication DESC 
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
  
    public function countFiltered($filters = []) {
        $conditions = [];
        $params = [];
        
        if (!empty($filters['type'])) {
            $conditions[] = "type = :type";
            $params[':type'] = $filters['type'];
        }
        
        if (!empty($filters['statut'])) {
            $conditions[] = "statut_validation = :statut";
            $params[':statut'] = $filters['statut'];
        }
        
        if (!empty($filters['domaine'])) {
            $conditions[] = "domaine = :domaine";
            $params[':domaine'] = $filters['domaine'];
        }
        
        if (!empty($filters['year'])) {
            $conditions[] = "YEAR(date_publication) = :year";
            $params[':year'] = $filters['year'];
        }
        
        $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
        
        $query = "SELECT COUNT(*) as total FROM " . $this->table . " {$whereClause}";
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'] ?? 0;
    }
     public function getAllForReport($filters = []) {
        $conditions = ["p.statut_validation = 'valide'"];
        $params = [];

        if (!empty($filters['year'])) {
            $conditions[] = "YEAR(p.date_publication) = :year";
            $params[':year'] = $filters['year'];
        }
        if (!empty($filters['domaine'])) {
            $conditions[] = "p.domaine = :domaine";
            $params[':domaine'] = $filters['domaine'];
        }

        $sql = "SELECT p.*, 
                       GROUP_CONCAT(CONCAT(u.nom, ' ', u.prenom) ORDER BY up.ordre_auteur SEPARATOR ', ') as auteurs_noms
                FROM " . $this->table . " p
                LEFT JOIN user_publication up ON p.id = up.id_publication
                LEFT JOIN users u ON up.user_id = u.id
                WHERE " . implode(' AND ', $conditions) . "
                GROUP BY p.id
                ORDER BY p.type, p.date_publication DESC";

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $value) $stmt->bindValue($key, $value);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getByIdWithDetails($id) {
        $query = "SELECT p.*, 
                         CONCAT(u_submit.nom, ' ', u_submit.prenom) as soumis_par_nom,
                         GROUP_CONCAT(CONCAT(u.nom, ' ', u.prenom) ORDER BY up.ordre_auteur SEPARATOR ', ') as auteurs_noms
                  FROM " . $this->table . " p
                  LEFT JOIN user_publication up ON p.id = up.id_publication
                  LEFT JOIN users u ON up.user_id = u.id
                  LEFT JOIN users u_submit ON p.soumis_par = u_submit.id
                  WHERE p.id = :id
                  GROUP BY p.id
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    
}


?>
        