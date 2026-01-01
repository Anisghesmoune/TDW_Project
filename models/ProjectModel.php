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
        // 1. Sécurisation de la direction du tri (pour éviter les injections SQL)
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

        
        $sql = "SELECT t.*, 
                       u.nom AS resp_nom, 
                       u.prenom AS resp_prenom
                FROM {$this->table} t
                LEFT JOIN users u ON t.responsable_id = u.id
                ORDER BY t.{$orderBy} {$order}";

        // 3. Ajout de la limite si elle est définie
        if ($limit !== null) {
            $sql .= " LIMIT :limit";
        }

        // 4. Préparation de la requête
        $stmt = $this->conn->prepare($sql);

        // 5. Binding de la limite (sécurité int)
        if ($limit !== null) {
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        }

        // 6. Exécution et retour des résultats
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    // On écrit une requête SQL manuelle pour joindre la table utilisateurs
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
public function addUserToProject($projectId, $userId) {
    $query = "INSERT INTO user_project (project_id, user_id) 
              VALUES (:project_id, :user_id)";
    
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':project_id', $projectId);
    $stmt->bindParam(':user_id', $userId);
    
    return $stmt->execute();
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
    // Liste blanche des colonnes autorisées pour ORDER BY (sécurité)
    $allowedOrderBy = ['id', 'title', 'description', 'start_date', 'end_date', 'status', 'budget', 'responsable_id', 'created_at'];
    
    // Valider $orderBy avec une liste blanche
    if (!in_array($orderBy, $allowedOrderBy)) {
        $orderBy = 'id'; // Valeur par défaut si invalide
    }
    
    // Valider $order
    $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
    
    // Construire la requête (pas besoin de quote() car validé par liste blanche)
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
    
    // Ajouter tous les utilisateurs du projet
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

/**
 * Statistiques par encadrant (responsable)
 */
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

/**
 * Statistiques par année
 */
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

/**
 * Get projects by thematic ID with full details
 */
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
/**
 * Statistiques par type de financement
 */
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

/**
 * Statistiques par équipe
 */

/**
 * Statistiques globales avancées
 */
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

/**
 * Top 5 des projets avec le plus de membres
 */
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

/**
 * Projets récents (30 derniers jours)
 */
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



}