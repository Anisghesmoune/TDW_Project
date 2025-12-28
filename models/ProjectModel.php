<?php
require_once 'Model.php';  


class Project extends Model {
     private $id;
    private $titre;
    private $description;
    private $type_financement;
    private $statut;
    private $date_debut;
    private $date_fin;
    private $responsable_id;
    private $id_equipe;

    public function __construct() {
        $this->table = 'projects';
        parent::__construct();
    }
  
    /**
     * Compter les projets actifs
     */
   public function countActive() {
    $query = "SELECT COUNT(*) AS total 
              FROM {$this->table} 
              WHERE statut = 'en_cours'";
    
    $stmt = $this->conn->prepare($query);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return (int)($row['total'] ?? 0);
}

    
    /**
     * Compter par statut
     */
    public function countByStatus($statut) {
        return $this->getByColumn('statut', $statut, 'id', 'DESC', null);
    }
    public function getAll($orderBy = 'id', $order = 'DESC', $limit = null)
    {
        return parent::getAll($orderBy, $order, $limit);
    }
    
    /**
     * Créer un projet
     */
    public function create() {
    $query = "INSERT INTO {$this->table}
        (titre, description, type_financement, statut, date_debut, date_fin, responsable_id, id_equipe)
        VALUES
        (:titre, :description, :type_financement, :statut, :date_debut, :date_fin, :responsable_id, :id_equipe)";
    
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':titre', $this->titre);
    $stmt->bindParam(':description', $this->description);
    $stmt->bindParam(':type_financement', $this->type_financement);
    $stmt->bindParam(':statut', $this->statut);
    $stmt->bindParam(':date_debut', $this->date_debut);
    $stmt->bindParam(':date_fin', $this->date_fin);
    $stmt->bindParam(':responsable_id', $this->responsable_id);
    $stmt->bindParam(':id_equipe', $this->id_equipe);

    return $stmt->execute();
}
// =====================
// SETTERS
// =====================

public function setId($id) {
    $this->id = $id;
}

public function setTitre($titre) {
    $this->titre = $titre;
}

public function setDescription($description) {
    $this->description = $description;
}

public function setTypeFinancement($type_financement) {
    $this->type_financement = $type_financement;
}

public function setStatut($statut) {
    $this->statut = $statut;
}

public function setDateDebut($date_debut) {
    $this->date_debut = $date_debut;
}

public function setDateFin($date_fin) {
    $this->date_fin = $date_fin;
}

public function setResponsableId($responsable_id) {
    $this->responsable_id = $responsable_id;
}

public function setIdEquipe($id_equipe) {
    $this->id_equipe = $id_equipe;
}

    
    /**
     * Mettre à jour un projet
     */
   public function update() {
    $query = "UPDATE {$this->table}
              SET titre = :titre,
                  description = :description,
                  type_financement = :type_financement,
                  statut = :statut,
                  date_debut = :date_debut,
                  date_fin = :date_fin,
                  responsable_id = :responsable_id,
                  id_equipe = :id_equipe
              WHERE id = :id";
    
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':id', $this->id);
    $stmt->bindParam(':titre', $this->titre);
    $stmt->bindParam(':description', $this->description);
    $stmt->bindParam(':type_financement', $this->type_financement);
    $stmt->bindParam(':statut', $this->statut);
    $stmt->bindParam(':date_debut', $this->date_debut);
    $stmt->bindParam(':date_fin', $this->date_fin);
    $stmt->bindParam(':responsable_id', $this->responsable_id);
    $stmt->bindParam(':id_equipe', $this->id_equipe);

    return $stmt->execute();
}
public function delete($id) {
    $query = "DELETE FROM {$this->table} WHERE id = :id";
    
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':id', $id);
    
    return $stmt->execute();
}
public function getById($id) {
    return $this->getByColumn('id', $id, 'id', 'DESC', null);
}
public function getByResponsableId($responsable_id) {
    return $this->getByColumn('responsable_id', $responsable_id, 'id', 'DESC', null);
}
public function getByEquipeId($id_equipe) {
    return $this->getByColumn('id_equipe', $id_equipe, 'id', 'DESC', null); 
}
public  function getByStatut($statut) {
    return $this->getByColumn('statut', $statut, 'id', 'DESC', null);   
}
public function getByTypeFinancement($type_financement) {
    return $this->getByColumn('type_financement', $type_financement, 'id', 'DESC', null); 
}
public function getProjectUsers($projectId) {
    $query = "SELECT u.* FROM users u
              JOIN project_users pu ON u.id = pu.user_id
              WHERE pu.project_id = :project_id";
    
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':project_id', $projectId);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
public function addUserToProject($projectId, $userId) {
    $query = "INSERT INTO project_users (project_id, user_id) 
              VALUES (:project_id, :user_id)";
    
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':project_id', $projectId);
    $stmt->bindParam(':user_id', $userId);
    
    return $stmt->execute();
}
public function removeUserFromProject($projectId, $userId) {
    $query = "DELETE FROM project_users 
              WHERE project_id = :project_id AND user_id = :user_id";
    
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':project_id', $projectId);
    $stmt->bindParam(':user_id', $userId);
    
    return $stmt->execute();
}

public function getAllProjectsWithUsers($orderBy = 'id', $order = 'DESC', $limit = null) {
    $projects = $this->getAll($orderBy, $order, $limit);
    
    foreach ($projects as &$project) {
        $project['users'] = $this->getProjectUsers($project['id']);
    }
    
    return $projects;
}
public function searchProjects($keyword, $orderBy = 'id', $order = 'DESC', $limit = null) {
    $query = "SELECT * FROM {$this->table} 
              WHERE titre LIKE :keyword OR description LIKE :keyword 
              ORDER BY {$orderBy} {$order}";
    
    if ($limit) {
        $query .= " LIMIT {$limit}";
    }
    
    $stmt = $this->conn->prepare($query);
    $likeKeyword = '%' . $keyword . '%';
    $stmt->bindParam(':keyword', $likeKeyword);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);   
}
public function getProjectsPublications($projectId) {
    $query = "SELECT p.* FROM publications p
              JOIN project_publications pp ON p.id = pp.publication_id
              WHERE pp.project_id = :project_id";
    
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':project_id', $projectId);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
public function addPublicationToProject($projectId, $publicationId) {
    $query = "INSERT INTO project_publications (project_id, publication_id) 
              VALUES (:project_id, :publication_id)";
    
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':project_id', $projectId);
    $stmt->bindParam(':publication_id', $publicationId);
    
    return $stmt->execute();
}
public function removePublicationFromProject($projectId, $publicationId) {
    $query = "DELETE FROM project_publication 
              WHERE project_id = :project_id AND publication_id = :publication_id";
    
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':project_id', $projectId);
    $stmt->bindParam(':publication_id', $publicationId);
    
    return $stmt->execute();
}
public function getProjectThematics($projectId) {
    $query = "SELECT t.* FROM thematics t
              JOIN project_thematique pt ON t.id = pt.thematic_id
              WHERE pt.project_id = :project_id";
    
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':project_id', $projectId);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
public function addThematicToProject($projectId, $thematicId) {
    $query = "INSERT INTO project_thematique (project_id, thematic_id) 
              VALUES (:project_id, :thematic_id)";
    
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':project_id', $projectId);
    $stmt->bindParam(':thematic_id', $thematicId);
    
    return $stmt->execute();
}
public function removeThematicFromProject($projectId, $thematicId) {
    $query = "DELETE FROM project_thematique 
              WHERE project_id = :project_id AND thematic_id = :thematic_id";
    
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':project_id', $projectId);
    $stmt->bindParam(':thematic_id', $thematicId);
    
    return $stmt->execute();
}



}