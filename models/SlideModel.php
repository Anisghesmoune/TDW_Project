<?php


class SlideModel {
    private $db;
    private $table = 'slides';
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Récupérer tous les slides actifs pour le diaporama public
     */
    public function getActiveSlides() {
        try {
            $today = date('Y-m-d');
            
            $query = "SELECT * FROM {$this->table} 
                     WHERE actif = 1
                     AND (date_debut IS NULL OR date_debut <= :today)
                     AND (date_fin IS NULL OR date_fin >= :today)
                     ORDER BY ordre ASC, id ASC";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':today', $today);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Erreur getActiveSlides: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupérer tous les slides (pour l'admin)
     */
    public function getAll() {
        try {
            $query = "SELECT * FROM {$this->table} 
                     ORDER BY ordre ASC, id DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Erreur getAll slides: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupérer un slide par ID
     */
    public function getById($id) {
        try {
            $query = "SELECT * FROM {$this->table} WHERE id = :id LIMIT 1";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Erreur getById slide: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Créer un nouveau slide
     */
    public function create($data) {
        try {
            $query = "INSERT INTO {$this->table} 
                     (titre, description, image, lien_detail, ordre, actif, date_debut, date_fin) 
                     VALUES (:titre, :description, :image, :lien_detail, :ordre, :actif, :date_debut, :date_fin)";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':titre', $data['titre']);
            $stmt->bindParam(':description', $data['description']);
            $stmt->bindParam(':image', $data['image']);
            $stmt->bindParam(':lien_detail', $data['lien_detail']);
            $stmt->bindParam(':ordre', $data['ordre'], PDO::PARAM_INT);
            $stmt->bindParam(':actif', $data['actif'], PDO::PARAM_INT);
            $stmt->bindParam(':date_debut', $data['date_debut']);
            $stmt->bindParam(':date_fin', $data['date_fin']);
            
            if ($stmt->execute()) {
                return [
                    'success' => true, 
                    'message' => 'Slide créé avec succès',
                    'id' => $this->db->lastInsertId()
                ];
            }
            
            return ['success' => false, 'message' => 'Erreur lors de la création'];
        } catch(PDOException $e) {
            error_log("Erreur create slide: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur système'];
        }
    }
    
    /**
     * Mettre à jour un slide
     */
    public function update($id, $data) {
        try {
            $query = "UPDATE {$this->table} 
                     SET titre = :titre,
                         description = :description,
                         image = :image,
                         lien_detail = :lien_detail,
                         ordre = :ordre,
                         actif = :actif,
                         date_debut = :date_debut,
                         date_fin = :date_fin
                     WHERE id = :id";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':titre', $data['titre']);
            $stmt->bindParam(':description', $data['description']);
            $stmt->bindParam(':image', $data['image']);
            $stmt->bindParam(':lien_detail', $data['lien_detail']);
            $stmt->bindParam(':ordre', $data['ordre'], PDO::PARAM_INT);
            $stmt->bindParam(':actif', $data['actif'], PDO::PARAM_INT);
            $stmt->bindParam(':date_debut', $data['date_debut']);
            $stmt->bindParam(':date_fin', $data['date_fin']);
            
            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("Erreur update slide: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Supprimer un slide
     */
    public function delete($id) {
        try {
            // Supprimer d'abord l'image si elle existe
            $slide = $this->getById($id);
            if ($slide && !empty($slide['image']) && file_exists($slide['image'])) {
                unlink($slide['image']);
            }
            
            $query = "DELETE FROM {$this->table} WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("Erreur delete slide: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Activer/Désactiver un slide
     */
    public function toggleActive($id) {
        try {
            $query = "UPDATE {$this->table} 
                     SET actif = NOT actif 
                     WHERE id = :id";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("Erreur toggleActive slide: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Réorganiser l'ordre des slides
     */
    public function updateOrder($slidesOrder) {
        try {
            $this->db->beginTransaction();
            
            $query = "UPDATE {$this->table} SET ordre = :ordre WHERE id = :id";
            $stmt = $this->db->prepare($query);
            
            foreach ($slidesOrder as $ordre => $id) {
                $stmt->bindParam(':ordre', $ordre, PDO::PARAM_INT);
                $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                $stmt->execute();
            }
            
            $this->db->commit();
            return true;
        } catch(PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur updateOrder slides: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Uploader une image
     */
    public function uploadImage($file) {
        try {
            $uploadDir = 'assets/slides/';
            
            // Créer le dossier s'il n'existe pas
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Valider le fichier
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($file['type'], $allowedTypes)) {
                return ['success' => false, 'message' => 'Type de fichier non autorisé'];
            }
            
            // Vérifier la taille (max 5MB)
            if ($file['size'] > 5 * 1024 * 1024) {
                return ['success' => false, 'message' => 'Fichier trop volumineux (max 5MB)'];
            }
            
            // Générer un nom unique
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'slide_' . time() . '_' . uniqid() . '.' . $extension;
            $filepath = $uploadDir . $filename;
            
            // Déplacer le fichier
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                return [
                    'success' => true, 
                    'message' => 'Image uploadée avec succès',
                    'filepath' => $filepath
                ];
            }
            
            return ['success' => false, 'message' => 'Erreur lors de l\'upload'];
        } catch(Exception $e) {
            error_log("Erreur uploadImage: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur système'];
        }
    }
}