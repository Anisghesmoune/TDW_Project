<?php
require_once __DIR__ . '/Model.php';  

class Reservation extends Model {
    protected $table = 'reservations';
    
    /**
     * Create a new reservation
     */
    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                  (id_equipement, id_utilisateur, date_debut, date_fin, statut, notes) 
                  VALUES (:id_equipement, :id_utilisateur, :date_debut, :date_fin, :statut, :notes)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_equipement', $data['id_equipement'], PDO::PARAM_INT);
        $stmt->bindParam(':id_utilisateur', $data['id_utilisateur'], PDO::PARAM_INT);
        $stmt->bindParam(':date_debut', $data['date_debut']);
        $stmt->bindParam(':date_fin', $data['date_fin']);
        $stmt->bindParam(':statut', $data['statut']);
        $stmt->bindParam(':notes', $data['notes']);
        
        return $stmt->execute();
    }
    
    /**
     * Update reservation
     */
    public function update($id, $data) {
        $query = "UPDATE " . $this->table . " 
                  SET id_equipement = :id_equipement,
                      id_utilisateur = :id_utilisateur,
                      date_debut = :date_debut,
                      date_fin = :date_fin,
                      statut = :statut,
                      notes = :notes
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':id_equipement', $data['id_equipement'], PDO::PARAM_INT);
        $stmt->bindParam(':id_utilisateur', $data['id_utilisateur'], PDO::PARAM_INT);
        $stmt->bindParam(':date_debut', $data['date_debut']);
        $stmt->bindParam(':date_fin', $data['date_fin']);
        $stmt->bindParam(':statut', $data['statut']);
        $stmt->bindParam(':notes', $data['notes']);
        
        return $stmt->execute();
    }
    
    /**
     * Update reservation status
     */
    public function updateStatus($id, $statut) {
        $query = "UPDATE " . $this->table . " SET statut = :statut WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':statut', $statut);
        return $stmt->execute();
    }
    
    /**
     * Check if equipment has conflicting reservations
     * Returns true if there's a conflict, false if available
     */
    public function hasConflict($id_equipement, $date_debut, $date_fin, $excludeReservationId = null) {
    // Logique simplifiée : (StartA <= EndB) and (EndA >= StartB)
    // Cette logique couvre tous les cas (inclusion, chevauchement partiel, enveloppement)
    // Et elle n'utilise chaque paramètre qu'une seule fois !
    
    $query = "SELECT COUNT(*) as count 
              FROM " . $this->table . " 
              WHERE id_equipement = :id_equipement 
              AND statut IN ('confirmé') 
              AND date_debut <= :date_fin 
              AND date_fin >= :date_debut";
    
    // Note : J'ai ajouté 'en_attente' car généralement une réservation en attente bloque aussi le créneau.
    // Si ce n'est pas le cas, retirez-le de la liste IN.

    if ($excludeReservationId) {
        $query .= " AND id != :exclude_id";
    }
    
    $stmt = $this->conn->prepare($query);
    
    // Liaison des paramètres
    $stmt->bindValue(':id_equipement', $id_equipement, PDO::PARAM_INT);
    $stmt->bindValue(':date_debut', $date_debut);
    $stmt->bindValue(':date_fin', $date_fin);
    
    if ($excludeReservationId) {
        $stmt->bindValue(':exclude_id', $excludeReservationId, PDO::PARAM_INT);
    }
    
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result['count'] > 0;
}
    
    /**
     * Get conflicting reservations for an equipment in a date range
     */
    public function getConflicts($id_equipement, $date_debut, $date_fin, $excludeReservationId = null) {
        $query = "SELECT r.*, u.nom as user_nom, u.prenom as user_prenom 
                  FROM " . $this->table . " r
                  JOIN users u ON r.id_utilisateur = u.id
                  WHERE r.id_equipement = :id_equipement
                  -- On cherche ce qui gêne : confirmé ou en cours (orthographe BDD)
                  AND r.statut IN ('confirme', 'en_cours') 
                  AND r.date_debut < :date_fin 
                  AND r.date_fin > :date_debut";

        if ($excludeReservationId) {
            $query .= " AND r.id != :exclude_id";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':id_equipement', $id_equipement, PDO::PARAM_INT);
        $stmt->bindValue(':date_debut', $date_debut);
        $stmt->bindValue(':date_fin', $date_fin);

        if ($excludeReservationId) {
            $stmt->bindValue(':exclude_id', $excludeReservationId, PDO::PARAM_INT);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    /**
     * Get all reservations with equipment and user details
     */
    public function getAllWithDetails($orderBy = 'date_reservation', $order = 'DESC') {
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
        
        $query = "SELECT r.*, 
                         e.nom as equipment_nom,
                         et.nom as type_nom,
                         et.icone as type_icone,
                         u.nom as user_nom,
                         u.prenom as user_prenom,
                         u.email as user_email
                  FROM " . $this->table . " r
                  INNER JOIN equipment e ON r.id_equipement = e.id
                  INNER JOIN equipment_types et ON e.id_type = et.id
                  INNER JOIN users u ON r.id_utilisateur = u.id
                  ORDER BY r." . $orderBy . " " . $order;
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get reservations by equipment
     */
   public function getByEquipment($id_equipement, $orderBy = 'date_debut', $order = 'DESC') {
    // 1. Sécurisation du tri (Direction)
    $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
    
    // 2. Sécurisation de la colonne de tri (Liste blanche)
    $allowedColumns = ['date_debut', 'date_fin', 'statut', 'id', 'created_at'];
    if (!in_array($orderBy, $allowedColumns)) {
        $orderBy = 'date_debut';
    }
    
    // 3. Requête corrigée
    
    $query = "SELECT r.*, 
                     u.nom as user_nom,
                     u.prenom as user_prenom,
                     u.email as user_email,
                     e.nom as equipment_nom
              FROM " . $this->table . " r
              INNER JOIN users u ON r.id_utilisateur = u.id
              INNER JOIN equipment e ON r.id_equipement = e.id
              WHERE r.id_equipement = :id_equipement
              ORDER BY r." . $orderBy . " " . $order;
    
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':id_equipement', $id_equipement, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
    
    /**
     * Get reservations by user
     */
    public function getByUser($id_utilisateur, $orderBy = 'date_debut', $order = 'DESC') {
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
        
        $query = "SELECT r.*, 
                         e.nom as equipment_nom,
                         et.nom as type_nom,
                         et.icone as type_icone
                  FROM " . $this->table . " r
                  INNER JOIN equipment e ON r.id_equipement = e.id
                  INNER JOIN equipment_types et ON e.id_type = et.id
                  WHERE r.id_utilisateur = :id_utilisateur
                  ORDER BY r." . $orderBy . " " . $order;
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_utilisateur', $id_utilisateur, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get reservations by status
     */
    public function getByStatus($statut, $orderBy = 'date_debut', $order = 'ASC') {
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
        
        $query = "SELECT r.*, 
                         e.nom as equipment_nom,
                         et.nom as type_nom,
                         et.icone as type_icone,
                         u.nom as user_nom,
                         u.prenom as user_prenom,
                         u.email as user_email
                  FROM " . $this->table . " r
                  INNER JOIN equipment e ON r.id_equipement = e.id
                  INNER JOIN equipment_types et ON e.id_type = et.id
                  INNER JOIN users u ON r.id_utilisateur = u.id
                  WHERE r.statut = :statut
                  ORDER BY r." . $orderBy . " " . $order;
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':statut', $statut);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get current reservations (en_cours)
     */
    public function getCurrent() {
        $query = "SELECT r.*, 
                         e.nom as equipment_nom,
                         et.nom as type_nom,
                         et.icone as type_icone,
                         u.nom as user_nom,
                         u.prenom as user_prenom
                  FROM " . $this->table . " r
                  INNER JOIN equipment e ON r.id_equipement = e.id
                  INNER JOIN equipment_types et ON e.id_type = et.id
                  INNER JOIN users u ON r.id_utilisateur = u.id
                  WHERE r.statut = 'en_cours'
                  AND CURDATE() BETWEEN r.date_debut AND r.date_fin
                  ORDER BY r.date_fin ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get upcoming reservations
     */
    public function getUpcoming($days = 7) {
        $query = "SELECT r.*, 
                         e.nom as equipment_nom,
                         et.nom as type_nom,
                         et.icone as type_icone,
                         u.nom as user_nom,
                         u.prenom as user_prenom
                  FROM " . $this->table . " r
                  INNER JOIN equipment e ON r.id_equipement = e.id
                  INNER JOIN equipment_types et ON e.id_type = et.id
                  INNER JOIN users u ON r.id_utilisateur = u.id
                  WHERE r.statut IN ('confirmée', 'en_attente')
                  AND r.date_debut BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days DAY)
                  ORDER BY r.date_debut ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':days', $days, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get pending reservations (en_attente)
     */
    public function getPending() {
        return $this->getByStatus('en_attente', 'date_reservation', 'ASC');
    }
    
    /**
     * Cancel reservation
     */
    public function cancel($id) {
        return $this->updateStatus($id, 'annulé');
    }
    
    /**
     * Confirm reservation
     */
    public function confirm($id) {
        return $this->updateStatus($id, 'confirmé');
    }
    
    /**
     * Auto-update statuses based on dates
     * Call this periodically (e.g., via cron job or at page load)
     */
    // public function autoUpdateStatuses() {
    //     // Start reservations that should be in progress
    //     $query1 = "UPDATE " . $this->table . " 
    //                SET statut = 'en_cours' 
    //                WHERE statut = 'confirmée' 
    //                AND CURDATE() >= date_debut 
    //                AND CURDATE() <= date_fin";
        
    //     $stmt1 = $this->conn->prepare($query1);
    //     $stmt1->execute();
        
    //     // End reservations that are past their end date
    //     $query2 = "UPDATE " . $this->table . " 
    //                SET statut = 'terminée' 
    //                WHERE statut = 'en_cours' 
    //                AND CURDATE() > date_fin";
        
    //     $stmt2 = $this->conn->prepare($query2);
    //     $stmt2->execute();
        
    //     return true;
    // }
    
    /**
     * Get reservation statistics
     */
    public function getStats() {
        $query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN statut = 'confirmée' THEN 1 ELSE 0 END) as confirmees,
                    SUM(CASE WHEN statut = 'en_attente' THEN 1 ELSE 0 END) as en_attente,
                    SUM(CASE WHEN statut = 'en_cours' THEN 1 ELSE 0 END) as en_cours,
                    SUM(CASE WHEN statut = 'terminée' THEN 1 ELSE 0 END) as terminees,
                    SUM(CASE WHEN statut = 'annulée' THEN 1 ELSE 0 END) as annulees
                  FROM " . $this->table;
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get reservation with full details by ID
     */
    public function getByIdWithDetails($id) {
        $query = "SELECT r.*, 
                         e.nom as equipment_nom,
                         e.etat as equipment_etat,
                         e.localisation as equipment_localisation,
                         et.nom as type_nom,
                         et.icone as type_icone,
                         u.nom as user_nom,
                         u.prenom as user_prenom,
                         u.email as user_email,
                         u.telephone as user_telephone
                  FROM " . $this->table . " r
                  INNER JOIN equipment e ON r.id_equipement = e.id
                  INNER JOIN equipment_types et ON e.id_type = et.id
                  INNER JOIN users u ON r.id_utilisateur = u.id
                  WHERE r.id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Check if user can make a reservation (no pending or current reservations for same equipment)
     */
    public function canUserReserve($id_utilisateur, $id_equipement) {
        $query = "SELECT COUNT(*) as count 
                  FROM " . $this->table . " 
                  WHERE id_utilisateur = :id_utilisateur 
                  AND id_equipement = :id_equipement
                  AND statut IN ('en_attente', 'confirmée', 'en_cours')";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_utilisateur', $id_utilisateur, PDO::PARAM_INT);
        $stmt->bindParam(':id_equipement', $id_equipement, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] == 0;
    }
    /**
     * Statistiques d'occupation par équipement sur une période
     */
public function getEquipmentOccupancyStats($startDate, $endDate) {
        // Nous devons utiliser des noms de paramètres uniques pour chaque utilisation
        // :start_calc et :end_calc pour le calcul DATEDIFF
        // :start_where et :end_where pour la condition WHERE
        
        $query = "SELECT 
                    e.nom as equipement_nom,
                    e.id as equipement_id,
                    COUNT(r.id) as total_reservations,
                    SUM(
                        DATEDIFF(
                            LEAST(:end_calc, r.date_fin), 
                            GREATEST(:start_calc, r.date_debut)
                        ) + 1
                    ) as jours_occupes
                  FROM " . $this->table . " r
                  JOIN equipment e ON r.id_equipement = e.id 
                  WHERE r.statut IN ('confirmée', 'en_cours', 'terminée')
                  AND r.date_debut <= :end_where 
                  AND r.date_fin >= :start_where
                  GROUP BY e.id, e.nom
                  ORDER BY jours_occupes DESC";

        $stmt = $this->conn->prepare($query);
        
        // Liaison des paramètres (4 liaisons pour 2 valeurs)
        $stmt->bindValue(':start_calc', $startDate);
        $stmt->bindValue(':end_calc', $endDate);
        $stmt->bindValue(':start_where', $startDate);
        $stmt->bindValue(':end_where', $endDate);
        
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Statistiques des demandes par utilisateur
     */
    public function getUserRequestStats($startDate, $endDate) {
        // Cette fonction était correcte, mais j'utilise bindValue pour la cohérence
        $query = "SELECT 
                    u.nom, 
                    u.prenom, 
                    u.email,
                    COUNT(r.id) as total_demandes,
                    SUM(CASE WHEN r.statut IN ('confirmée', 'terminée', 'en_cours') THEN 1 ELSE 0 END) as approuvees,
                    SUM(CASE WHEN r.statut = 'annulée' THEN 1 ELSE 0 END) as annulees,
                    SUM(CASE WHEN r.statut = 'en_attente' THEN 1 ELSE 0 END) as en_attente
                  FROM " . $this->table . " r
                  JOIN users u ON r.id_utilisateur = u.id
                  WHERE r.date_debut BETWEEN :date_debut AND :date_fin
                  GROUP BY u.id
                  ORDER BY total_demandes DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':date_debut', $startDate);
        $stmt->bindValue(':date_fin', $endDate);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    /**
     * Vérifie s'il y a une réservation active MAINTENANT pour un équipement
     */
    public function getCurrentReservationForEquipment($equipmentId) {
        $now = date('Y-m-d H:i:s');
        
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE id_equipement = :eid 
                  AND statut IN ('confirmée', 'en_cours')
                  AND date_debut <= :now 
                  AND date_fin >= :now 
                  LIMIT 1";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':eid', $equipmentId);
        $stmt->bindParam(':now', $now);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * NOUVEAU : Méthode magique pour mettre à jour automatiquement les statuts
     * À appeler régulièrement (ex: au chargement du dashboard ou via Cron)
     */
  public function autoUpdateStatuses() {
        $now = date('Y-m-d H:i:s');

        // Note : On ne touche pas au statut de la réservation car il n'y a pas 'en_cours' ou 'terminé'
        // On modifie seulement l'état de l'équipement.

        // 1. METTRE L'ÉQUIPEMENT EN 'RÉSERVÉ'
        // Si une réservation 'confirmé' est active MAINTENANT (date_debut < now < date_fin)
        $sqlEquipReserved = "UPDATE equipment e 
                             JOIN reservations r ON e.id = r.id_equipement
                             SET e.etat = 'réservé'
                             WHERE r.statut = 'confirmé'
                             AND r.date_debut <= :now1
                             AND r.date_fin > :now2";
                             
        $stmtStart = $this->conn->prepare($sqlEquipReserved);
        $stmtStart->bindValue(':now1', $now);
        $stmtStart->bindValue(':now2', $now);
        $stmtStart->execute();

        // 2. METTRE L'ÉQUIPEMENT EN 'LIBRE'
        // Si l'équipement est 'réservé' MAIS qu'aucune réservation 'confirmé' n'est active en ce moment
        $sqlFree = "UPDATE equipment e 
                    SET e.etat = 'libre' 
                    WHERE e.etat = 'réservé' 
                    AND NOT EXISTS (
                        SELECT 1 FROM reservations r 
                        WHERE r.id_equipement = e.id 
                        AND r.statut = 'confirmé'
                        AND r.date_debut <= :now3
                        AND r.date_fin > :now4
                    )";
                    
        $stmtEnd = $this->conn->prepare($sqlFree);
        $stmtEnd->bindValue(':now3', $now);
        $stmtEnd->bindValue(':now4', $now);
        $stmtEnd->execute();
    }
}
?>