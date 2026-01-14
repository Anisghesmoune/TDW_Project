<?php
require_once __DIR__ . '/Model.php';

class TeamsModel extends Model {
    private $name;
    private $description;
    private $domaine_recherche;
    private $chef_equipe_id;

    public function __construct() {
        $this->table = 'teams';
        parent::__construct();
    }
    
  
    
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
    
  
    public function update($team_id) {
        $query = "UPDATE {$this->table} 
                  SET nom = :nom, 
                      description = :description, 
                      domaine_recherche = :domaine_recherche, 
                      chef_equipe_id = :chef_equipe_id
                  WHERE id = :team_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':nom', $this->name);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':domaine_recherche', $this->domaine_recherche);
        $stmt->bindParam(':chef_equipe_id', $this->chef_equipe_id);
        $stmt->bindParam(':team_id', $team_id);
        
        return $stmt->execute();
    }
    
   
    public function delete($team_id) {
        $this->removeAllMembers($team_id);
        
        $query = "DELETE FROM {$this->table} WHERE id = :team_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':team_id', $team_id);
        
        return $stmt->execute();
    }
    
 
    public function getTeamById($team_id) {
        return $this->getById($team_id);
    }
    
   
    public function getAllTeams() {
        return $this->getAll('id', 'DESC');
    }
    
 
    public function getByChefEquipeId($chef_equipe_id) {
        return $this->getByColumn('chef_equipe_id', $chef_equipe_id, 'id', 'DESC');
    }
    
 
    public function getByDomaine($domaine) {
        return $this->getByColumn('domaine_recherche', $domaine, 'name', 'ASC');
    }
 
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
    
 
    public function addMember($team_id, $user_id, $role = 'membre') {
    try {
        if ($this->isMemberInTeam($team_id, $user_id)) {
            return false;
        }
        
        $query = "INSERT INTO user_team (team_id, user_id, role, date_ajout) 
                  VALUES (:team_id, :user_id, :role, NOW())";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':team_id', $team_id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':role', $role, PDO::PARAM_STR);
        
        return $stmt->execute();
        
    } catch (PDOException $e) {
        error_log("Erreur addMember: " . $e->getMessage());
        return false;
    }
}

   
   public function removeMember($team_id, $user_id) {
    try {
        $query = "DELETE FROM user_team 
                  WHERE team_id = :team_id AND user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':team_id', $team_id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        
        return $stmt->execute();
        
    } catch (PDOException $e) {
        error_log("Erreur removeMember: " . $e->getMessage());
        return false;
    }
}
    
   
    public function removeAllMembers($team_id) {
        $query = "DELETE FROM user_team WHERE team_id = :team_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':team_id', $team_id);
        
        return $stmt->execute();
    }
    
    
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
    
 
    public function countMembers($team_id) {
        $query = "SELECT COUNT(*) as count FROM user_team 
                  WHERE team_id = :team_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':team_id', $team_id);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }
    
   
    public function getTeamPublications($teamId, $limit = null) {
    $query = "SELECT 
                tp.description as contribution_desc,
                p.id as pub_id, 
                p.titre, 
                p.date_publication, 
                u.nom as auteur_nom, 
                u.prenom as auteur_prenom
            FROM team_publications tp
            JOIN publications p ON tp.publication_id = p.id
            JOIN users u ON tp.auteur_id = u.id
            WHERE tp.team_id = :teamId 
            ORDER BY p.date_publication DESC";

    if ($limit !== null) {
        $query .= " LIMIT :limit";
    }
    
    $stmt = $this->conn->prepare($query);
    
    $stmt->bindParam(':teamId', $teamId, PDO::PARAM_INT);
    
    if ($limit !== null) {
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT); 
    }
    
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
public function getTeamEquipments($team_id) {
    $query =  "SELECT 
            e.*, 
            te.date_attribution
        FROM team_equipments te
        JOIN equipment e ON te.equipment_id = e.id
        WHERE te.team_id = :team_id";
        
    $stmt = $this->conn->prepare($query);
    
    $stmt->bindParam(':team_id', $team_id, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
   
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
    
public function getByTeam($team_id) {
    $query = "SELECT e.*, et.nom as type_nom, et.description as type_description, 
              et.icone, et_link.date_attribution
              FROM " . $this->table . " e
              INNER JOIN team_equipments et_link ON e.id = et_link.equipment_id
              LEFT JOIN equipment_types et ON e.id_type = et.id
              WHERE et_link.team_id = :team_id
              ORDER BY et_link.date_attribution DESC";
    
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':team_id', $team_id, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
    public function getAvailableForTeam($team_id) {
    $query = "
        SELECT e.id, e.nom, e.etat, t.nom AS type_nom
        FROM equipment e
        LEFT JOIN equipment_types t ON e.id_type = t.id
        WHERE e.etat = 'libre'
        AND e.id NOT IN (
            SELECT equipment_id 
            FROM team_equipments 
            WHERE team_id = :team_id
        )
        ORDER BY e.nom
    ";
    
    $stmt = $this->conn->prepare($query);
    $stmt->execute(['team_id' => $team_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


public function assignEquipment($team_id, $equipment_id) {
    try {
        $this->conn->beginTransaction();

        $query = "SELECT etat FROM equipment WHERE id = :equipment_id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute(['equipment_id' => $equipment_id]);
        $equipment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$equipment) {
            throw new Exception("Équipement inexistant");
        }

        if ($equipment['etat'] !== 'libre') {
            throw new Exception("Équipement non disponible");
        }

        $query = "INSERT INTO team_equipments (team_id, equipment_id, date_attribution) 
                  VALUES (:team_id, :equipment_id, NOW())";
        $stmt = $this->conn->prepare($query);
        $stmt->execute(['team_id' => $team_id, 'equipment_id' => $equipment_id]);

        $query = "UPDATE equipment SET etat = 'réservé' WHERE id = :equipment_id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute(['equipment_id' => $equipment_id]);

        $this->conn->commit();

        return ['success' => true, 'message' => 'Équipement assigné avec succès'];

    } catch (Exception $e) {
        $this->conn->rollBack();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

public function unassignEquipment($team_id, $equipment_id) {
    try {
        $this->conn->beginTransaction();
        
        $query = "DELETE FROM team_equipments 
                WHERE team_id = :team_id AND equipment_id = :equipment_id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            'team_id' => $team_id,
            'equipment_id' => $equipment_id
        ]);
        
        $query = "UPDATE equipment SET etat = 'libre' WHERE id = :equipment_id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute(['equipment_id' => $equipment_id]);
        
        $this->conn->commit();
        return ['success' => true, 'message' => 'Équipement assigné avec succès'];
        
    } catch (Exception $e) {
        $this->conn->rollBack();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}
    
 
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
    public function getTeamLeader($teamId) {
        $sql = "SELECT u.* 
                FROM users u
                JOIN user_team ut ON u.id = ut.user_id
                WHERE ut.team_id = :tid AND ut.role = 'chef'
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':tid', $teamId);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
     public function getAllMembersFlat() {
        $sql = "SELECT u.*, t.nom as team_name, ut.role as team_role 
                FROM users u
                LEFT JOIN user_team ut ON u.id = ut.user_id
                LEFT JOIN teams t ON ut.team_id = t.id
                WHERE u.role != 'admin'
                ORDER BY u.nom";
        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

}