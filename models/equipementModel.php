<?php
class Equipment extends Model {
    protected $table = 'equipment';
    
    /**
     * Create a new equipment
     */
    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                  (nom, id_type, etat, description, localisation, derniere_maintenance, prochaine_maintenance) 
                  VALUES (:nom, :id_type, :etat, :description, :localisation, :derniere_maintenance, :prochaine_maintenance)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':nom', $data['nom']);
        $stmt->bindParam(':id_type', $data['id_type'], PDO::PARAM_INT);
        $stmt->bindParam(':etat', $data['etat']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':localisation', $data['localisation']);
        $stmt->bindParam(':derniere_maintenance', $data['derniere_maintenance']);
        $stmt->bindParam(':prochaine_maintenance', $data['prochaine_maintenance']);
        
        return $stmt->execute();
    }
    
    /**
     * Update equipment
     */
    public function update($id, $data) {
        $query = "UPDATE " . $this->table . " 
                  SET nom = :nom, 
                      id_type = :id_type, 
                      etat = :etat, 
                      description = :description, 
                      localisation = :localisation, 
                      derniere_maintenance = :derniere_maintenance, 
                      prochaine_maintenance = :prochaine_maintenance 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':nom', $data['nom']);
        $stmt->bindParam(':id_type', $data['id_type'], PDO::PARAM_INT);
        $stmt->bindParam(':etat', $data['etat']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':localisation', $data['localisation']);
        $stmt->bindParam(':derniere_maintenance', $data['derniere_maintenance']);
        $stmt->bindParam(':prochaine_maintenance', $data['prochaine_maintenance']);
        
        return $stmt->execute();
    }
    
    /**
     * Get equipment with type information (JOIN)
     */
    public function getAllWithTypes($orderBy = 'id', $order = 'DESC') {
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
        $query = "SELECT e.*, et.nom as type_nom, et.description as type_description, et.icone 
                  FROM " . $this->table . " e 
                  LEFT JOIN equipment_types et ON e.id_type = et.id 
                  ORDER BY e." . $orderBy . " " . $order;
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get equipment by status
     */
    public function getByStatus($etat, $orderBy = 'id', $order = 'DESC') {
        return $this->getByColumn('etat', $etat, $orderBy, $order);
    }
    
    /**
     * Get equipment by type
     */
    public function getByType($id_type, $orderBy = 'id', $order = 'DESC') {
        return $this->getByColumn('id_type', $id_type, $orderBy, $order);
    }
    
    /**
     * Get equipment needing maintenance (upcoming or overdue)
     */
    public function getMaintenanceNeeded($daysAhead = 30) {
        $query = "SELECT e.*, et.nom as type_nom 
                  FROM " . $this->table . " e 
                  LEFT JOIN equipment_types et ON e.id_type = et.id 
                  WHERE e.prochaine_maintenance IS NOT NULL 
                  AND e.prochaine_maintenance <= DATE_ADD(NOW(), INTERVAL :days DAY) 
                  ORDER BY e.prochaine_maintenance ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':days', $daysAhead, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Update equipment status
     */
    public function updateStatus($id, $etat) {
        $query = "UPDATE " . $this->table . " SET etat = :etat WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':etat', $etat);
        return $stmt->execute();
    }
    
    /**
     * Update maintenance dates
     */
    public function updateMaintenance($id, $derniere_maintenance, $prochaine_maintenance) {
        $query = "UPDATE " . $this->table . " 
                  SET derniere_maintenance = :derniere_maintenance, 
                      prochaine_maintenance = :prochaine_maintenance 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':derniere_maintenance', $derniere_maintenance);
        $stmt->bindParam(':prochaine_maintenance', $prochaine_maintenance);
        return $stmt->execute();
    }
    
    /**
     * Get equipment statistics by status
     */
    public function getStatsByStatus() {
        $query = "SELECT etat, COUNT(*) as count 
                  FROM " . $this->table . " 
                  GROUP BY etat";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get equipment statistics by type
     */
    public function getStatsByType() {
        $query = "SELECT et.nom as type_nom, et.icone, COUNT(e.id) as count 
                  FROM equipment_types et 
                  LEFT JOIN " . $this->table . " e ON et.id = e.id_type 
                  GROUP BY et.id, et.nom, et.icone 
                  ORDER BY count DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Search equipment by name or location
     */
    public function search($searchTerm, $orderBy = 'id', $order = 'DESC') {
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
        $query = "SELECT e.*, et.nom as type_nom, et.icone 
                  FROM " . $this->table . " e 
                  LEFT JOIN equipment_types et ON e.id_type = et.id 
                  WHERE e.nom LIKE :search 
                  OR e.localisation LIKE :search 
                  OR e.description LIKE :search 
                  ORDER BY e." . $orderBy . " " . $order;
        
        $stmt = $this->conn->prepare($query);
        $searchParam = '%' . $searchTerm . '%';
        $stmt->bindParam(':search', $searchParam);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get available equipment (libre status)
     */
    public function getAvailable($orderBy = 'nom', $order = 'ASC') {
        return $this->getByStatus('libre', $orderBy, $order);
    }
    
    /**
     * Check if equipment is available (no status check, only reservation check)
     */
    public function isAvailable($id) {
        $query = "SELECT etat FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result && $result['etat'] === 'libre';
    }
    
    /**
     * Check if equipment is available for a specific date range
     * Returns array with 'available' boolean and 'conflicts' array if any
     */
    public function isAvailableForPeriod($id, $date_debut, $date_fin) {
        // 1. Vérifier si l'équipement existe et n'est pas en maintenance
        $equipment = $this->getById($id);
        
        if (!$equipment || $equipment['etat'] === 'en_maintenance') {
            return [
                'available' => false,
                'reason' => 'Équipement en maintenance ou introuvable',
                'conflicts' => []
            ];
        }
        
        // 2. Vérifier les conflits de dates
        // Note : J'ai simplifié la logique de chevauchement. 
        // Cette formule couvre tous les cas (dedans, autour, chevauchement partiel)
        // et n'utilise les paramètres qu'une seule fois, ce qui corrige le bug PDO.
        
        $query = "SELECT r.*, 
                         u.nom as user_nom, 
                         u.prenom as user_prenom,
                         u.email as user_email
                  FROM reservations r
                  INNER JOIN users u ON r.id_utilisateur = u.id
                  WHERE r.id_equipement = :id_equipement 
                 
                  AND r.statut IN ('confirmé', 'annulé', 'en_conflit') 
                  AND r.date_debut < :date_fin 
                  AND r.date_fin > :date_debut
                  ORDER BY r.date_debut ASC";
        
        $stmt = $this->conn->prepare($query);
        
        // Utilisation de bindValue (plus sûr ici)
        $stmt->bindValue(':id_equipement', $id, PDO::PARAM_INT);
        $stmt->bindValue(':date_debut', $date_debut);
        $stmt->bindValue(':date_fin', $date_fin);
        
        $stmt->execute();
        
        $conflicts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'available' => count($conflicts) === 0,
            'conflicts' => $conflicts,
            'equipment' => $equipment
        ];
    }
    /**
     * Get equipment with current reservation status
     */
    public function getWithReservationStatus($id) {
        $query = "SELECT e.*, 
                         et.nom as type_nom,
                         et.icone as type_icone,
                         r.id as current_reservation_id,
                         r.date_debut as reservation_debut,
                         r.date_fin as reservation_fin,
                         r.statut as reservation_statut,
                         u.nom as reserved_by_nom,
                         u.prenom as reserved_by_prenom,
                         u.email as reserved_by_email
                  FROM " . $this->table . " e
                  LEFT JOIN equipment_types et ON e.id_type = et.id
                  LEFT JOIN reservations r ON e.id = r.id_equipement 
                      AND r.statut IN ('confirmée', 'en_cours')
                      AND CURDATE() BETWEEN r.date_debut AND r.date_fin
                  LEFT JOIN users u ON r.id_utilisateur = u.id
                  WHERE e.id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all equipment with their current reservation status
     */
    public function getAllWithReservationStatus($orderBy = 'id', $order = 'DESC') {
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
        
        $query = "SELECT e.*, 
                         et.nom as type_nom,
                         et.icone as type_icone,
                         r.id as current_reservation_id,
                         r.date_debut as reservation_debut,
                         r.date_fin as reservation_fin,
                         r.statut as reservation_statut,
                         u.nom as reserved_by_nom,
                         u.prenom as reserved_by_prenom
                  FROM " . $this->table . " e
                  LEFT JOIN equipment_types et ON e.id_type = et.id
                  LEFT JOIN reservations r ON e.id = r.id_equipement 
                      AND r.statut IN ('confirmée', 'en_cours')
                      AND CURDATE() BETWEEN r.date_debut AND r.date_fin
                  LEFT JOIN users u ON r.id_utilisateur = u.id
                  ORDER BY e." . $orderBy . " " . $order;
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Reserve equipment (create reservation and update status)
     * Returns array with success status and message
     */
    public function reserve($id_equipement, $id_utilisateur, $date_debut, $date_fin, $notes = null) {
        try {
            // Start transaction
            $this->conn->beginTransaction();
            
            // Check availability
            $availability = $this->isAvailableForPeriod($id_equipement, $date_debut, $date_fin);
            
            if (!$availability['available']) {
                $this->conn->rollBack();
                return [
                    'success' => false,
                    'message' => 'Équipement non disponible pour cette période',
                    'conflicts' => $availability['conflicts']
                ];
            }
            
            // Create reservation
            $query = "INSERT INTO reservations 
                      (id_equipement, id_utilisateur, date_debut, date_fin, statut, notes) 
                      VALUES (:id_equipement, :id_utilisateur, :date_debut, :date_fin, 'en_attente', :notes)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id_equipement', $id_equipement, PDO::PARAM_INT);
            $stmt->bindParam(':id_utilisateur', $id_utilisateur, PDO::PARAM_INT);
            $stmt->bindParam(':date_debut', $date_debut);
            $stmt->bindParam(':date_fin', $date_fin);
            $stmt->bindParam(':notes', $notes);
            
            if (!$stmt->execute()) {
                $this->conn->rollBack();
                return [
                    'success' => false,
                    'message' => 'Erreur lors de la création de la réservation'
                ];
            }
            
            $reservation_id = $this->conn->lastInsertId();
            
            // Update equipment status to 'réserve'
            $this->updateStatus($id_equipement, 'réservé');
            
            $this->conn->commit();
            
            return [
                'success' => true,
                'message' => 'Réservation créée avec succès',
                'reservation_id' => $reservation_id
            ];
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            return [
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Cancel reservation and update equipment status
     */
    public function cancelReservation($reservation_id) {
        try {
            $this->conn->beginTransaction();
            
            // Get reservation details
            $query = "SELECT id_equipement FROM reservations WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $reservation_id, PDO::PARAM_INT);
            $stmt->execute();
            $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$reservation) {
                $this->conn->rollBack();
                return false;
            }
            
            // Update reservation status
            $query = "UPDATE reservations SET statut = 'annulé' WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $reservation_id, PDO::PARAM_INT);
            $stmt->execute();
            
            // Check if equipment has other active reservations
            $query = "SELECT COUNT(*) as count FROM reservations 
                      WHERE id_equipement = :id_equipement 
                      AND statut IN ('confirmé') 
                      AND id != :reservation_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id_equipement', $reservation['id_equipement'], PDO::PARAM_INT);
            $stmt->bindParam(':reservation_id', $reservation_id, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // If no other reservations, set equipment to libre
            if ($result['count'] == 0) {
                $this->updateStatus($reservation['id_equipement'], 'libre');
            }
            
            $this->conn->commit();
            return true;
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }
    
    /**
     * Get equipment availability calendar for a date range
     */
    public function getAvailabilityCalendar($id, $start_date, $end_date) {
        $query = "SELECT 
                    date_debut,
                    date_fin,
                    statut,
                    u.nom as user_nom,
                    u.prenom as user_prenom
                  FROM reservations r
                  INNER JOIN users u ON r.id_utilisateur = u.id
                  WHERE r.id_equipement = :id
                  AND r.statut IN ('confirmée', 'en_cours')
                  AND (
                      (r.date_debut BETWEEN :start_date AND :end_date)
                      OR (r.date_fin BETWEEN :start_date AND :end_date)
                      OR (r.date_debut <= :start_date AND r.date_fin >= :end_date)
                  )
                  ORDER BY r.date_debut ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':end_date', $end_date);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>