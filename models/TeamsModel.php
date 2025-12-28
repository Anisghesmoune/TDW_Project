<?php
require_once 'Model.php';

// ============================================================
// MODEL AMÉLIORÉ : TeamsModel.php
// ===========================================================
class TeamsModel extends Model {
    private $name;
    private $description;
    private $domaine_recherche;
    private $chef_equipe_id;

    public function __construct() {
        $this->table = 'teams';
        parent::__construct();
    }
    
    // ============================================================
    // SETTERS (pour faciliter l'utilisation)
    // ============================================================
    
    public function setName($name) {
        $this->name = $name;
    }
    
    public function setDescription($description) {
        $this->description = $description;
    }
    
    public function setDomaineRecherche($domaine_recherche) {
        $this->domaine_recherche = $domaine_recherche;
    }
    
    public function setChefEquipeId($chef_equipe_id) {
        $this->chef_equipe_id = $chef_equipe_id;
    }
    
    // ============================================================
    // CRUD OPERATIONS
    // ============================================================
    
    /**
     * Créer une nouvelle équipe
     */
    public function create() {
        $query = "INSERT INTO {$this->table} 
                  (name, description, domaine_recherche, chef_equipe_id) 
                  VALUES (:name, :description, :domaine_recherche, :chef_equipe_id)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':domaine_recherche', $this->domaine_recherche);
        $stmt->bindParam(':chef_equipe_id', $this->chef_equipe_id);
        
        return $stmt->execute();
    }
    
    /**
     * Mettre à jour une équipe
     */
    public function update($team_id) {
        $query = "UPDATE {$this->table} 
                  SET name = :name, 
                      description = :description, 
                      domaine_recherche = :domaine_recherche, 
                      chef_equipe_id = :chef_equipe_id
                  WHERE id = :team_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':domaine_recherche', $this->domaine_recherche);
        $stmt->bindParam(':chef_equipe_id', $this->chef_equipe_id);
        $stmt->bindParam(':team_id', $team_id);
        
        return $stmt->execute();
    }
    
    /**
     * Supprimer une équipe
     */
    public function delete($team_id) {
        // D'abord supprimer les membres de l'équipe
        $this->removeAllMembers($team_id);
        
        // Puis supprimer l'équipe
        $query = "DELETE FROM {$this->table} WHERE id = :team_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':team_id', $team_id);
        
        return $stmt->execute();
    }
    
    // ============================================================
    // RÉCUPÉRATION DE DONNÉES
    // ============================================================
    
    /**
     * Récupérer une équipe par ID
     */
    public function getTeamById($team_id) {
        return $this->getById($team_id);
    }
    
    /**
     * Récupérer toutes les équipes
     */
    public function getAllTeams() {
        return $this->getAll('id', 'DESC');
    }
    
    /**
     * Récupérer les équipes d'un chef
     */
    public function getByChefEquipeId($chef_equipe_id) {
        return $this->getByColumn('chef_equipe_id', $chef_equipe_id, 'id', 'DESC');
    }
    
    /**
     * Récupérer les équipes par domaine
     */
    public function getByDomaine($domaine) {
        return $this->getByColumn('domaine_recherche', $domaine, 'name', 'ASC');
    }
    
    /**
     * Rechercher des équipes par nom
     */
    public function searchByName($keyword) {
        $keyword = '%' . $keyword . '%';
        $query = "SELECT * FROM {$this->table} 
                  WHERE name LIKE :keyword 
                  ORDER BY name ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':keyword', $keyword);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // ============================================================
    // GESTION DES MEMBRES
    // ============================================================
    
    /**
     * Récupérer les membres d'une équipe
     */
    public function getTeamMembers($team_id) {
        $query = "SELECT u.* FROM users u
                  JOIN user_team tm ON u.id = tm.user_id
                  WHERE tm.team_id = :team_id
                  ORDER BY u.nom ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':team_id', $team_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Ajouter un membre à une équipe
     */
    public function addMember($team_id, $user_id) {
        // Vérifier si le membre n'existe pas déjà
        if ($this->isMemberInTeam($team_id, $user_id)) {
            return false;
        }
        
        $query = "INSERT INTO user_team (team_id, user_id, date_ajout) 
                  VALUES (:team_id, :user_id, NOW())";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':team_id', $team_id);
        $stmt->bindParam(':user_id', $user_id);
        
        return $stmt->execute();
    }
    
    /**
     * Retirer un membre d'une équipe
     */
    public function removeMember($team_id, $user_id) {
        $query = "DELETE FROM user_team 
                  WHERE team_id = :team_id AND user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':team_id', $team_id);
        $stmt->bindParam(':user_id', $user_id);
        
        return $stmt->execute();
    }
    
    /**
     * Supprimer tous les membres d'une équipe
     */
    public function removeAllMembers($team_id) {
        $query = "DELETE FROM user_team WHERE team_id = :team_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':team_id', $team_id);
        
        return $stmt->execute();
    }
    
    /**
     * Vérifier si un utilisateur est membre d'une équipe
     */
    public function isMemberInTeam($team_id, $user_id) {
        $query = "SELECT COUNT(*) as count FROM user_team 
                  WHERE team_id = :team_id AND user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':team_id', $team_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }
    
    /**
     * Compter les membres d'une équipe
     */
    public function countMembers($team_id) {
        $query = "SELECT COUNT(*) as count FROM user_team 
                  WHERE team_id = :team_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':team_id', $team_id);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }
    
    // ============================================================
    // MÉTHODES AVEC INFORMATIONS ENRICHIES
    // ============================================================
    
    /**
     * Récupérer toutes les équipes avec le nombre de membres et le nom du chef
     */
    public function getAllTeamsWithDetails() {
        $query = "SELECT t.*, 
                         u.nom as chef_nom, 
                         u.prenom as chef_prenom,
                         COUNT(tm.user_id) as nb_membres
                  FROM {$this->table} t
                  LEFT JOIN users u ON t.chef_equipe_id = u.id
                  LEFT JOIN user_team tm ON t.id = tm.team_id
                  GROUP BY t.id
                  ORDER BY t.nom ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Récupérer une équipe avec ses détails complets
     */
    public function getTeamWithDetails($team_id) {
        $query = "SELECT t.*, 
                         u.nom as chef_nom, 
                         u.prenom as chef_prenom,
                         u.email as chef_email,
                         COUNT(tm.user_id) as nb_membres
                  FROM {$this->table} t
                  LEFT JOIN users u ON t.chef_equipe_id = u.id
                  LEFT JOIN user_team tm ON t.id = tm.team_id
                  WHERE t.id = :team_id
                  GROUP BY t.id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':team_id', $team_id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Récupérer les utilisateurs qui ne sont PAS dans une équipe
     */
    public function getAvailableUsers($team_id) {
        $query = "SELECT u.* FROM users u
                  WHERE u.id NOT IN (
                      SELECT user_id FROM user_team WHERE team_id = :team_id
                  )
                  AND u.statut = 'actif'
                  ORDER BY u.nom ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':team_id', $team_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}