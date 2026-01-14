<?php
require_once __DIR__ . '/Model.php';  


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
  
   
   public function countActive() {
    $query = "SELECT COUNT(*) AS total 
              FROM {$this->table} 
              WHERE statut = 'en_cours'";
    
    $stmt = $this->conn->prepare($query);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return (int)($row['total'] ?? 0);
}

    
  
    public function countByStatus($statut) {
        return $this->getByColumn('statut', $statut, 'id', 'DESC', null);
    }

    public function getAll($orderBy = 'id', $order = 'DESC', $limit = null)
    {
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

        
        $sql = "SELECT t.*, 
                       u.nom AS resp_nom, 
                       u.prenom AS resp_prenom
                FROM {$this->table} t
                LEFT JOIN users u ON t.responsable_id = u.id
                ORDER BY t.{$orderBy} {$order}";

        if ($limit !== null) {
            $sql .= " LIMIT :limit";
        }

        $stmt = $this->conn->prepare($sql);

        if ($limit !== null) {
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        }

        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    
   
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
    
    public function getLastInsertedId() {
        return $this->conn->lastInsertId();
    }
    
    public function addUserToProject($projectId, $userId) {
        $check = $this->conn->prepare("SELECT COUNT(*) FROM user_project WHERE user_id = :uid AND project_id = :pid");
        $check->execute([':uid' => $userId, ':pid' => $projectId]);
        
        if ($check->fetchColumn() > 0) {
            return true; 
        }

        $query = "INSERT INTO user_project (project_id, user_id, role) VALUES (:pid, :uid, 'responsable')";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':pid', $projectId);
        $stmt->bindParam(':uid', $userId);
        
        return $stmt->execute();
    }


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
    $query = "SELECT p.*, 
            CONCAT(u.nom, ' ', u.prenom) as responsable_name,
            u.email as responsable_email
            FROM " . $this->table . " p
            LEFT JOIN users u ON p.responsable_id = u.id
            WHERE p.responsable_id = :id
            ORDER BY p.id DESC";


    $stmt = $this->conn->prepare($query);
    $stmt->execute(['id' => $responsable_id]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
              JOIN user_project pu ON u.id = pu.user_id
              WHERE pu.project_id = :project_id";
    
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':project_id', $projectId);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function removeUserFromProject($projectId, $userId) {
    $query = "DELETE FROM user_project 
              WHERE project_id = :project_id AND user_id = :user_id";
    
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':project_id', $projectId);
    $stmt->bindParam(':user_id', $userId);
    
    return $stmt->execute();
}

public function getAllProjectsWithUsers($orderBy = 'id', $order = 'DESC', $limit = null) {
    $allowedOrderBy = ['id', 'title', 'description', 'start_date', 'end_date', 'status', 'budget', 'responsable_id', 'created_at'];
    
    if (!in_array($orderBy, $allowedOrderBy)) {
        $orderBy = 'id'; // Valeur par défaut si invalide
    }
    
    $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
    
    $query = "SELECT 
                p.*,
                CONCAT(u.prenom, ' ', u.nom) as responsable_name,
                u.email as responsable_email,
                u.role as responsable_role
              FROM projects p
              LEFT JOIN users u ON p.responsable_id = u.id
              ORDER BY p.$orderBy $order";
    
    if ($limit !== null && is_numeric($limit)) {
        $query .= " LIMIT " . intval($limit);
    }
    
    $stmt = $this->conn->prepare($query);
    $stmt->execute();
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($projects as &$project) {
        $project['users'] = $this->getProjectUsers($project['id']);
    }
    
    return $projects;
}
public function searchProjects($keyword, $orderBy = 'id', $order = 'DESC', $limit = null) {
    $query = "SELECT * FROM {$this->table} 
              WHERE titre LIKE :keyword1 OR description LIKE :keyword2 
              ORDER BY {$orderBy} {$order}";
    
    if ($limit) {
        $query .= " LIMIT " . (int)$limit;
    }
    
    $stmt = $this->conn->prepare($query);
    
    $likeKeyword = '%' . $keyword . '%';
    
    $stmt->bindValue(':keyword1', $likeKeyword);
    $stmt->bindValue(':keyword2', $likeKeyword);
    
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
    $query = "SELECT t.* FROM thematiques t
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
public function getProjectsByThematic() {
    $query = "SELECT 
                t.id,
                t.nom as thematic_name,
                COUNT(DISTINCT pt.project_id) as project_count
              FROM thematiques t
              LEFT JOIN project_thematique pt ON t.id = pt.thematic_id
              GROUP BY t.id, t.nom
              ORDER BY project_count DESC";
    
    $stmt = $this->conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


public function getProjectsByResponsable() {
    $query = "SELECT 
                u.id,
                CONCAT(u.prenom, ' ', u.nom) as responsable_name,
                u.role,
                COUNT(p.id) as project_count,
                SUM(CASE WHEN p.statut = 'en_cours' THEN 1 ELSE 0 END) as active_count,
                SUM(CASE WHEN p.statut = 'termine' THEN 1 ELSE 0 END) as completed_count,
                SUM(CASE WHEN p.statut = 'soumis' THEN 1 ELSE 0 END) as submitted_count
              FROM users u
              LEFT JOIN projects p ON u.id = p.responsable_id
              GROUP BY u.id, u.prenom, u.nom, u.role
              HAVING project_count > 0
              ORDER BY project_count DESC";
    
    $stmt = $this->conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


public function getProjectsByYear() {
    $query = "SELECT 
                YEAR(date_debut) as year,
                COUNT(*) as project_count,
                SUM(CASE WHEN statut = 'en_cours' THEN 1 ELSE 0 END) as active_count,
                SUM(CASE WHEN statut = 'termine' THEN 1 ELSE 0 END) as completed_count,
                SUM(CASE WHEN statut = 'soumis' THEN 1 ELSE 0 END) as submitted_count
              FROM projects
              WHERE date_debut IS NOT NULL
              GROUP BY YEAR(date_debut)
              ORDER BY year DESC";
    
    $stmt = $this->conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


public function getProjectsByThematicId($thematicId) {
    $query = "SELECT DISTINCT
                p.*,
                CONCAT(u.prenom, ' ', u.nom) as responsable_name,
                t.nom as thematic_name
              FROM projects p
              LEFT JOIN users u ON p.responsable_id = u.id
              LEFT JOIN project_thematique pt ON p.id = pt.project_id
              LEFT JOIN thematiques t ON pt.thematic_id = t.id
              WHERE pt.thematic_id = :thematic_id
              ORDER BY p.date_debut DESC";
    
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':thematic_id', $thematicId, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function getProjectsByFinancement() {
    $query = "SELECT 
                type_financement,
                COUNT(*) as project_count,
                SUM(CASE WHEN statut = 'en_cours' THEN 1 ELSE 0 END) as active_count
              FROM projects
              GROUP BY type_financement
              ORDER BY project_count DESC";
    
    $stmt = $this->conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


public function getAdvancedStats() {
    $query = "SELECT 
                COUNT(*) as total_projects,
                SUM(CASE WHEN statut = 'en_cours' THEN 1 ELSE 0 END) as active_projects,
                SUM(CASE WHEN statut = 'termine' THEN 1 ELSE 0 END) as completed_projects,
                SUM(CASE WHEN statut = 'soumis' THEN 1 ELSE 0 END) as submitted_projects,
                AVG(DATEDIFF(COALESCE(date_fin, CURDATE()), date_debut)) as avg_duration_days,
                COUNT(DISTINCT responsable_id) as unique_responsables,
                COUNT(DISTINCT id_equipe) as teams_involved
              FROM projects
              WHERE date_debut IS NOT NULL";
    
    $stmt = $this->conn->prepare($query);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

public function getTopProjectsByMembers() {
    $query = "SELECT 
                p.id,
                p.titre,
                CONCAT(u.prenom, ' ', u.nom) as responsable,
                COUNT(DISTINCT pu.user_id) as member_count
              FROM projects p
              LEFT JOIN user_project pu ON p.id = pu.project_id
              LEFT JOIN users u ON p.responsable_id = u.id
              GROUP BY p.id, p.titre, responsable
              ORDER BY member_count DESC
              LIMIT 5";
    
    $stmt = $this->conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


public function getRecentProjects($days = 30) {
    $query = "SELECT 
                COUNT(*) as count
              FROM projects
              WHERE date_creation >= DATE_SUB(CURDATE(), INTERVAL :days DAY)";
    
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':days', $days, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
public function getAllThematics() {
        try {
            $stmt = $this->conn->query("SELECT * FROM thematiques ORDER BY nom");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return []; 
        }
    }
    public function getPublicProjects($filters = []) {
    $sql = "SELECT p.*, 
                   CONCAT(u.nom, ' ', u.prenom) as responsable_nom 
            FROM " . $this->table . " p
            LEFT JOIN users u ON p.responsable_id = u.id
            WHERE 1=1"; 

    $params = [];

    if (!empty($filters['search'])) {
        $sql .= " AND (p.titre LIKE :search1 OR p.description LIKE :search2)";
        $term = '%' . $filters['search'] . '%';
        $params[':search1'] = $term;
        $params[':search2'] = $term;
    }

    if (!empty($filters['thematique'])) {
        $sql .= " AND FIND_IN_SET(:theme, p.thematiques)";
        $params[':theme'] = $filters['thematique']; 
    }

    if (!empty($filters['responsable'])) {
        $sql .= " AND p.responsable_id = :resp";
        $params[':resp'] = $filters['responsable'];
    }

    if (!empty($filters['statut'])) {
        $sql .= " AND p.statut = :statut";
        $params[':statut'] = $filters['statut'];
    }

    $sql .= " ORDER BY p.date_debut DESC";

    try {
        $stmt = $this->conn->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch(PDOException $e) {
        error_log("Erreur SQL getPublicProjects: " . $e->getMessage());
        return [];
    }
}
     public function getProjectDetails($id) {
        $query = "SELECT 
                    p.*,
                    CONCAT(u.nom, ' ', u.prenom) as responsable_nom,
                    u.photo_profil as responsable_photo,
                    u.email as responsable_email,
                    u.grade as responsable_grade
                  FROM projects p
                  LEFT JOIN users u ON p.responsable_id = u.id
                  WHERE p.id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $project = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($project) {
            $stmtThemes = $this->conn->prepare("
                SELECT t.nom 
                FROM thematiques t
                JOIN project_thematique pt ON t.id = pt.thematic_id
                WHERE pt.project_id = :pid
            ");
            $stmtThemes->bindParam(':pid', $id);
            $stmtThemes->execute();
            $project['thematiques'] = $stmtThemes->fetchAll(PDO::FETCH_COLUMN);
        }
        
        return $project;
    }

  
   public function getProjectMembers($id) {
        $query = "SELECT 
                    u.id, 
                    CONCAT(u.nom, ' ', u.prenom) as membre_nom, 
                    u.photo_profil, 
                    u.grade,  
                    up.role as role_projet
                  FROM users u
                  JOIN user_project up ON u.id = up.user_id
                  WHERE up.project_id = :id
                  ORDER BY u.nom ASC"; 
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

  
    public function getProjectPublications($id) {
        $query = "SELECT pub.* 
                  FROM publications pub
                  JOIN project_publication pp ON pub.id = pp.publication_id
                  WHERE pp.project_id = :id
                  ORDER BY pub.date_publication DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
public function getProjectsByUserId($userId) {
    
        $query = "SELECT p.*, 
                         (SELECT role FROM user_project WHERE project_id = p.id AND user_id = :uid1 LIMIT 1) as role_dans_projet,
                         (SELECT COUNT(*) FROM user_project WHERE project_id = p.id) as nb_membres
                  FROM projects p
                  WHERE p.responsable_id = :uid2
                     OR p.id IN (SELECT project_id FROM user_project WHERE user_id = :uid3)
                  ORDER BY p.date_debut DESC";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindValue(':uid1', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':uid2', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':uid3', $userId, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}