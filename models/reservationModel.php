<?php
require_once __DIR__ . '/Model.php';  

class Reservation extends Model {
    protected $table = 'reservations';
    
  
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
    
    
    public function updateStatus($id, $statut) {
        $query = "UPDATE " . $this->table . " SET statut = :statut WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':statut', $statut);
        return $stmt->execute();
    }
    

    public function hasConflict($id_equipement, $date_debut, $date_fin, $excludeReservationId = null) {
   
    $query = "SELECT COUNT(*) as count 
              FROM " . $this->table . " 
              WHERE id_equipement = :id_equipement 
              AND statut IN ('confirmé') 
              AND date_debut <= :date_fin 
              AND date_fin >= :date_debut";

    if ($excludeReservationId) {
        $query .= " AND id != :exclude_id";
    }
    
    $stmt = $this->conn->prepare($query);
    
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
    

   public function getByEquipment($id_equipement, $orderBy = 'date_debut', $order = 'DESC') {
    $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
    
    $allowedColumns = ['date_debut', 'date_fin', 'statut', 'id', 'created_at'];
    if (!in_array($orderBy, $allowedColumns)) {
        $orderBy = 'date_debut';
    }
    
    
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
    
  
    public function getPending() {
        return $this->getByStatus('en_attente', 'date_reservation', 'ASC');
    }
    
   
    public function cancel($id) {
        return $this->updateStatus($id, 'annulé');
    }
    
 
    public function confirm($id) {
        return $this->updateStatus($id, 'confirmé');
    }
   
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
  
public function getEquipmentOccupancyStats($startDate, $endDate) {
      
        
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
        
        $stmt->bindValue(':start_calc', $startDate);
        $stmt->bindValue(':end_calc', $endDate);
        $stmt->bindValue(':start_where', $startDate);
        $stmt->bindValue(':end_where', $endDate);
        
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

   
    public function getUserRequestStats($startDate, $endDate) {
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

  
  public function autoUpdateStatuses() {
        $now = date('Y-m-d H:i:s');
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