<?php
require_once __DIR__ . '/../config/Database.php'; 
class UserModel {
    private $db;
 private $table = 'users';
 public function __construct(){
            $this->db = Database::getInstance()->getConnection(); // <-- Ici
 }


//Authentifier un utilisateur

 public function authentificate($email, $password){

    try{
        $querry = "SELECT id ,password ,nom ,prenom ,email ,photo_profil,role,grade,domaine_recherche,statut FROM " . $this->table . " WHERE email = :email And statut = 'actif' limit 1 ";
    
    $stmt=$this->db->prepare($querry);
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->execute();
    $user = $stmt->fetch();

   if ($user && password_verify($password, $user['password'])) {
    $this->updateLastLogin($user['id']);
    unset($user['password']); // Supprimer le mot de passe avant de retourner les données
    return $user;
} 
 return false;



}catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    return false;
}
 }
public function create($data){
    try{
       if($this->emailExists($data['email'])){
    return ['success'=>false,'message'=> "Cet utilisateur existe déjà"];
}
            $hashedPassword=password_hash($data['password'],PASSWORD_DEFAULT);
            $query = "INSERT INTO {$this->table} 
            (password, nom, prenom, email, role,is_admin,grade, domaine_recherche, statut) 
             VALUES (:password, :nom, :prenom, :email, :role, :is_admin, :grade, :domaine_recherche, 'actif')";
             $stmt = $this->db->prepare($query);
            // $stmt->bindParam(':username', $data['username']);
            $stmt->bindParam(':password', $hashedPassword);
            $stmt->bindParam(':nom', $data['nom']);
            $stmt->bindParam(':prenom', $data['prenom']);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':role', $data['role']);
             $isAdmin = !empty($data['is_admin']) ? 1 : 0;
           $stmt->bindParam(':is_admin', $isAdmin, PDO::PARAM_INT);
            $stmt->bindParam(':grade', $data['grade']);
            $stmt->bindParam(':domaine_recherche', $data['domaine_recherche']);
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Utilisateur créé avec succès', 'id' => $this->db->lastInsertId()];
            }
            
            return ['success' => false, 'message' => 'Erreur lors de la création'];
        } catch(PDOException $e) {
            error_log("Erreur création utilisateur : " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur système'];
        }
}
        
  
        public function getById($id) {
        try {
            $query = "SELECT id,nom, prenom, email, photo_profil, role, grade, 
                            domaine_recherche, specialite, statut, date_creation, derniere_connexion
                     FROM {$this->table} 
                     WHERE id = :id LIMIT 1";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch();
        } catch(PDOException $e) {
            error_log("Erreur récupération utilisateur : " . $e->getMessage());
            return false;
        }
    }
    public function delete($id) {
    try {
        $query = "UPDATE {$this->table} SET statut = 'supprimé' WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    } catch(PDOException $e) {
        error_log("Erreur suppression utilisateur : " . $e->getMessage());
        return false;
    }
}
public function getAllWithPublicationCount($filters = []) {
    try {
        $query = "SELECT u.id, u.username, u.nom, u.prenom, u.email, u.role, u.statut,
                         COUNT(up.publication_id) AS nb_publications
                  FROM {$this->table} u
                  LEFT JOIN user_publication up ON u.id = up.user_id
                  WHERE u.statut != 'supprimé'";

        // filtres dynamiques
        if (!empty($filters['role'])) {
            $query .= " AND u.role = :role";
        }

        $query .= " GROUP BY u.id ORDER BY nb_publications DESC";

        $stmt = $this->db->prepare($query);

        if (!empty($filters['role'])) {
            $stmt->bindParam(':role', $filters['role']);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log("Erreur récupération utilisateurs avec publications : " . $e->getMessage());
        return [];
    }
}

    
    /**
     * Mettre à jour le profil utilisateur
     */
   public function updateProfile($id, $data) {
        try {
            // 1. Construction dynamique de la requête
            // On ne met à jour que les champs qui sont envoyés
            $fields = [];
            $params = [':id' => $id];

            // Champs standards
            if (isset($data['nom'])) { 
                $fields[] = "nom = :nom"; 
                $params[':nom'] = $data['nom']; 
            }
            if (isset($data['prenom'])) { 
                $fields[] = "prenom = :prenom"; 
                $params[':prenom'] = $data['prenom']; 
            }
            if (isset($data['email'])) { 
                $fields[] = "email = :email"; 
                $params[':email'] = $data['email']; 
            }
            
            // --- LES NOUVEAUX CHAMPS ---
            if (isset($data['telephone'])) { 
                $fields[] = "telephone = :telephone"; 
                $params[':telephone'] = $data['telephone']; 
            }
            if (isset($data['bio'])) { 
                $fields[] = "bio = :bio"; 
                $params[':bio'] = $data['bio']; 
            }
            if (isset($data['domaine_recherche'])) { 
                $fields[] = "domaine_recherche = :domaine"; 
                $params[':domaine'] = $data['domaine_recherche']; 
            }
            if (isset($data['photo_profil'])) { 
                $fields[] = "photo_profil = :photo"; 
                $params[':photo'] = $data['photo_profil']; 
            }

            // Gestion sécurisée du mot de passe
            // On ne le met à jour QUE s'il n'est pas vide
            if (!empty($data['password'])) {
                $fields[] = "password = :password";
                $params[':password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }

            // Si aucun champ à mettre à jour, on arrête
            if (empty($fields)) {
                return true; 
            }

            // 2. Exécution de la requête
            $query = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = :id";
            
            // Attention : utilisez $this->conn ou $this->db selon votre classe Model parente
            $stmt = $this->db->prepare($query); 
            
            return $stmt->execute($params);

        } catch(PDOException $e) {
            error_log("Erreur mise à jour profil : " . $e->getMessage());
            return false;
        }
    }

    public function changePassword($id, $oldPassword, $newPassword) {
        try {
            // Vérifier l'ancien mot de passe
            $query = "SELECT password FROM {$this->table} WHERE id = :id LIMIT 1";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $user = $stmt->fetch();
            
            if (!$user || !password_verify($oldPassword, $user['password'])) {
                return ['success' => false, 'message' => 'Ancien mot de passe incorrect'];
            }
            
            // Mettre à jour avec le nouveau mot de passe
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $query = "UPDATE {$this->table} SET password = :password WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':password', $hashedPassword);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Mot de passe modifié avec succès'];
            }
            
            return ['success' => false, 'message' => 'Erreur lors de la modification'];
        } catch(PDOException $e) {
            error_log("Erreur changement mot de passe : " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur système'];
        }
    }
    public function updatePhoto($id, $photoPath) {
        try {
            $query = "UPDATE {$this->table} SET photo_profil = :photo WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':photo', $photoPath);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("Erreur mise à jour photo : " . $e->getMessage());
            return false;
        }
    }

     private function usernameExists($username) {
        $query = "SELECT id FROM {$this->table} WHERE username = :username LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        return $stmt->fetch() !== false;
    }
    
    /**
     * Vérifier si l'email existe
     */
    private function emailExists($email) {
        $query = "SELECT id FROM {$this->table} WHERE email = :email LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->fetch() !== false;
    }

    private function updateLastLogin($id) {
        try {
            $query = "UPDATE {$this->table} SET derniere_connexion = NOW() WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
        } catch(PDOException $e) {
            error_log("Erreur mise à jour dernière connexion : " . $e->getMessage());
        }
    }
    
    /**
     * Suspendre un utilisateur (Admin)
     */
    public function suspend($id) {
        try {
            $query = "UPDATE {$this->table} SET statut = 'suspendu' WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("Erreur suspension utilisateur : " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Activer un utilisateur (Admin)
     */
    public function activate($id) {
        try {
            $query = "UPDATE {$this->table} SET statut = 'actif' WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("Erreur activation utilisateur : " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Récupérer tous les utilisateurs (Admin)
     */
    public function getAll($filters = []) {
        try {
            $query = "SELECT id, username, nom, prenom, email, role, grade, statut, derniere_connexion 
                     FROM {$this->table} WHERE 1=1";
            
            if (!empty($filters['role'])) {
                $query .= " AND role = :role";
            }
            if (!empty($filters['statut'])) {
                $query .= " AND statut = :statut";
            }
            
            $query .= " ORDER BY date_creation DESC";
            
            $stmt = $this->db->prepare($query);
            
            if (!empty($filters['role'])) {
                $stmt->bindParam(':role', $filters['role']);
            }
            if (!empty($filters['statut'])) {
                $stmt->bindParam(':statut', $filters['statut']);
            }
            
            $stmt->execute();
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            error_log("Erreur récupération utilisateurs : " . $e->getMessage());
            return [];
        }
    }
}?>
