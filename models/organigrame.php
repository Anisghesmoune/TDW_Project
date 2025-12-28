<?php

class OrganigrammeModel {
    private $db;
    private $table = 'organigramme';
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function create($data) {
        try {
            $query = "INSERT INTO {$this->table} 
                     (id_utilisateur, poste, niveau_hierarchique, date_nomination, superieur_id) 
                     VALUES (:id_utilisateur, :poste, :niveau_hierarchique, :date_nomination, :superieur_id)";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id_utilisateur', $data['id_utilisateur'], PDO::PARAM_INT);
            $stmt->bindParam(':poste', $data['poste']);
            $stmt->bindParam(':niveau_hierarchique', $data['niveau_hierarchique'], PDO::PARAM_INT);
            $stmt->bindParam(':date_nomination', $data['date_nomination']);
            $stmt->bindParam(':superieur_id', $data['superieur_id'], PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                return [
                    'success' => true, 
                    'message' => 'Entrée organigramme créée avec succès',
                    'id' => $this->db->lastInsertId()
                ];
            }
            
            return ['success' => false, 'message' => 'Erreur lors de la création'];
        } catch(PDOException $e) {
            error_log("Erreur create organigramme: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur système'];
        }
    }
    
    /**
     * Mettre à jour une entrée de l'organigramme
     */
     public function update($id, $data) {
        try {
            $query = "UPDATE {$this->table} 
                     SET poste = :poste,
                         niveau_hierarchique = :niveau_hierarchique,
                         date_nomination = :date_nomination,
                         superieur_id = :superieur_id
                     WHERE id = :id";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':poste', $data['poste']);
            $stmt->bindParam(':niveau_hierarchique', $data['niveau_hierarchique'], PDO::PARAM_INT);
            $stmt->bindParam(':date_nomination', $data['date_nomination']);
            $stmt->bindParam(':superieur_id', $data['superieur_id'], PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("Erreur update organigramme: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Supprimer une entrée de l'organigramme
     */
    public function delete($id) {
        try {
            $query = "DELETE FROM {$this->table} WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("Erreur delete organigramme: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Récupérer l'organigramme complet avec les informations des utilisateurs
     */
    public function getFullHierarchy() {
        try {
            $query = "SELECT 
                        o.id,
                        o.id_utilisateur,
                        o.poste,
                        o.niveau_hierarchique,
                        o.date_nomination,
                        o.superieur_id,
                        u.nom,
                        u.prenom,
                        u.email,
                        u.photo_profil,
                        u.grade,
                        u.role,
                        sup.nom as superieur_nom,
                        sup.prenom as superieur_prenom
                     FROM {$this->table} o
                     INNER JOIN users u ON o.id_utilisateur = u.id
                     LEFT JOIN users sup ON o.superieur_id = sup.id
                     ORDER BY o.niveau_hierarchique ASC, o.poste ASC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            error_log("Erreur getFullHierarchy: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupérer l'organigramme sous forme d'arbre hiérarchique
     */
    public function getHierarchyTree() {
        try {
            $allMembers = $this->getFullHierarchy();
            
            // Construire l'arbre hiérarchique
            $tree = [];
            $indexed = [];
            
            // Indexer tous les membres par leur ID
            foreach ($allMembers as $member) {
                $member['subordinates'] = [];
                $indexed[$member['id_utilisateur']] = $member;
            }
            
            // Construire l'arbre
            foreach ($indexed as $id => $member) {
                if ($member['superieur_id'] && isset($indexed[$member['superieur_id']])) {
                    // Ajouter comme subordonné
                    $indexed[$member['superieur_id']]['subordinates'][] = &$indexed[$id];
                } else {
                    // C'est un nœud racine (directeur)
                    $tree[] = &$indexed[$id];
                }
            }
            
            return $tree;
        } catch(Exception $e) {
            error_log("Erreur getHierarchyTree: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupérer le directeur du laboratoire
     */
    public function getDirector() {
        try {
            $query = "SELECT 
                        o.id,
                        o.id_utilisateur,
                        o.poste,
                        o.date_nomination,
                        u.nom,
                        u.prenom,
                        u.email,
                        u.photo_profil,
                        u.grade,
                        u.domaine_recherche
                     FROM {$this->table} o
                     INNER JOIN users u ON o.id_utilisateur = u.id
                     WHERE o.poste LIKE '%directeur%' OR o.niveau_hierarchique = 1
                     ORDER BY o.niveau_hierarchique ASC
                     LIMIT 1";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetch();
        } catch(PDOException $e) {
            error_log("Erreur getDirector: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Récupérer les membres par niveau hiérarchique
     */
    public function getByLevel($level) {
        try {
            $query = "SELECT 
                        o.id,
                        o.id_utilisateur,
                        o.poste,
                        o.date_nomination,
                        u.nom,
                        u.prenom,
                        u.email,
                        u.photo_profil,
                        u.grade
                     FROM {$this->table} o
                     INNER JOIN users u ON o.id_utilisateur = u.id
                     WHERE o.niveau_hierarchique = :level
                     ORDER BY o.poste ASC";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':level', $level, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            error_log("Erreur getByLevel: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupérer les subordonnés d'un utilisateur
     */
    public function getSubordinates($userId) {
        try {
            $query = "SELECT 
                        o.id,
                        o.id_utilisateur,
                        o.poste,
                        o.niveau_hierarchique,
                        u.nom,
                        u.prenom,
                        u.email,
                        u.photo_profil,
                        u.grade
                     FROM {$this->table} o
                     INNER JOIN users u ON o.id_utilisateur = u.id
                     WHERE o.superieur_id = :user_id
                     ORDER BY o.niveau_hierarchique ASC, o.poste ASC";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            error_log("Erreur getSubordinates: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupérer le supérieur d'un utilisateur
     */
    public function getSuperior($userId) {
        try {
            $query = "SELECT 
                        sup.id,
                        sup.nom,
                        sup.prenom,
                        sup.email,
                        sup.photo_profil,
                        sup.grade,
                        o2.poste as poste_superieur
                     FROM {$this->table} o
                     INNER JOIN users sup ON o.superieur_id = sup.id
                     LEFT JOIN {$this->table} o2 ON o2.id_utilisateur = sup.id
                     WHERE o.id_utilisateur = :user_id
                     LIMIT 1";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch();
        } catch(PDOException $e) {
            error_log("Erreur getSuperior: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Récupérer l'entrée organigramme d'un utilisateur
     */
    public function getByUserId($userId) {
        try {
            $query = "SELECT 
                        o.*,
                        u.nom,
                        u.prenom,
                        u.email,
                        u.photo_profil,
                        u.grade,
                        sup.nom as superieur_nom,
                        sup.prenom as superieur_prenom
                     FROM {$this->table} o
                     INNER JOIN users u ON o.id_utilisateur = u.id
                     LEFT JOIN users sup ON o.superieur_id = sup.id
                     WHERE o.id_utilisateur = :user_id
                     LIMIT 1";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch();
        } catch(PDOException $e) {
            error_log("Erreur getByUserId: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Vérifier si un utilisateur existe déjà dans l'organigramme
     */
    public function userExists($userId) {
        try {
            $query = "SELECT COUNT(*) as count FROM {$this->table} WHERE id_utilisateur = :user_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch();
            return $result['count'] > 0;
        } catch(PDOException $e) {
            error_log("Erreur userExists: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Récupérer les postes disponibles
     */
    public function getAvailablePositions() {
        return [
            'Directeur du laboratoire',
            'Directeur adjoint',
            'Chef d\'équipe',
            'Responsable administratif',
            'Responsable technique',
            'Responsable des partenariats',
            'Coordinateur de projets',
            'Secrétaire général'
        ];
    }
    
    /**
     * Récupérer les statistiques de l'organigramme
     */
    public function getStats() {
        try {
            $query = "SELECT 
                        COUNT(*) as total_membres,
                        COUNT(DISTINCT niveau_hierarchique) as niveaux,
                        COUNT(CASE WHEN poste LIKE '%directeur%' THEN 1 END) as directeurs,
                        COUNT(CASE WHEN poste LIKE '%chef%' THEN 1 END) as chefs_equipe
                     FROM {$this->table}";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetch();
        } catch(PDOException $e) {
            error_log("Erreur getStats: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mettre à jour le poste d'un utilisateur
     */
    public function updatePosition($userId, $newPoste, $newLevel = null) {
        try {
            if ($newLevel !== null) {
                $query = "UPDATE {$this->table} 
                         SET poste = :poste, niveau_hierarchique = :niveau
                         WHERE id_utilisateur = :user_id";
                $stmt = $this->db->prepare($query);
                $stmt->bindParam(':niveau', $newLevel, PDO::PARAM_INT);
            } else {
                $query = "UPDATE {$this->table} 
                         SET poste = :poste
                         WHERE id_utilisateur = :user_id";
                $stmt = $this->db->prepare($query);
            }
            
            $stmt->bindParam(':poste', $newPoste);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("Erreur updatePosition: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtenir le chemin hiérarchique complet d'un utilisateur
     * (du directeur jusqu'à l'utilisateur)
     */
    public function getHierarchyPath($userId) {
        try {
            $path = [];
            $currentUserId = $userId;
            $maxDepth = 10; // Éviter les boucles infinies
            $depth = 0;
            
            while ($currentUserId && $depth < $maxDepth) {
                $member = $this->getByUserId($currentUserId);
                
                if ($member) {
                    array_unshift($path, $member); // Ajouter au début
                    $currentUserId = $member['superieur_id'];
                } else {
                    break;
                }
                
                $depth++;
            }
            
            return $path;
        } catch(Exception $e) {
            error_log("Erreur getHierarchyPath: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Vérifier si un utilisateur est supérieur d'un autre
     */
    public function isSuperiorOf($superiorId, $subordinateId) {
        try {
            $path = $this->getHierarchyPath($subordinateId);
            
            foreach ($path as $member) {
                if ($member['id_utilisateur'] == $superiorId) {
                    return true;
                }
            }
            
            return false;
        } catch(Exception $e) {
            error_log("Erreur isSuperiorOf: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Récupérer tous les membres sous un supérieur (récursif)
     */
    public function getAllSubordinatesRecursive($userId) {
        try {
            $allSubordinates = [];
            $directSubordinates = $this->getSubordinates($userId);
            
            foreach ($directSubordinates as $subordinate) {
                $allSubordinates[] = $subordinate;
                
                // Récursion pour les subordonnés des subordonnés
                $subSubordinates = $this->getAllSubordinatesRecursive($subordinate['id_utilisateur']);
                $allSubordinates = array_merge($allSubordinates, $subSubordinates);
            }
            
            return $allSubordinates;
        } catch(Exception $e) {
            error_log("Erreur getAllSubordinatesRecursive: " . $e->getMessage());
            return [];
        }
    }
}
?>
