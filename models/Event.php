<?php
require_once __DIR__."/Model.php";
class Event extends Model {
    public $id;
    public $titre;
    public $description;
    public $id_type;
    public $date_debut;
    public $date_fin;
    public $lieu;
    public $organisateur_id;
    public $statut;
    public $capacite_max;
    public $date_creation;

    public function __construct() {
        $this->table = 'events';
        parent::__construct();
    }

    /**
     * Créer un événement
     */
    public function create() {
        $query = "INSERT INTO " . $this->table . " 
                  (titre, description, id_type, date_debut, date_fin, lieu, organisateur_id, statut, capacite_max) 
                  VALUES (:titre, :description, :id_type, :date_debut, :date_fin, :lieu, :organisateur_id, :statut, :capacite_max)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':titre', $this->titre);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':id_type', $this->id_type, PDO::PARAM_INT);
        $stmt->bindParam(':date_debut', $this->date_debut);
        $stmt->bindParam(':date_fin', $this->date_fin);
        $stmt->bindParam(':lieu', $this->lieu);
        $stmt->bindParam(':organisateur_id', $this->organisateur_id, PDO::PARAM_INT);
        $stmt->bindParam(':statut', $this->statut);
        $stmt->bindParam(':capacite_max', $this->capacite_max, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    /**
     * Mettre à jour un événement
     */
    public function update() {
        $query = "UPDATE " . $this->table . " 
                  SET titre = :titre, 
                      description = :description, 
                      id_type = :id_type,
                      date_debut = :date_debut, 
                      date_fin = :date_fin,
                      lieu = :lieu, 
                      organisateur_id = :organisateur_id,
                      statut = :statut,
                      capacite_max = :capacite_max
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        $stmt->bindParam(':titre', $this->titre);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':id_type', $this->id_type, PDO::PARAM_INT);
        $stmt->bindParam(':date_debut', $this->date_debut);
        $stmt->bindParam(':date_fin', $this->date_fin);
        $stmt->bindParam(':lieu', $this->lieu);
        $stmt->bindParam(':organisateur_id', $this->organisateur_id, PDO::PARAM_INT);
        $stmt->bindParam(':statut', $this->statut);
        $stmt->bindParam(':capacite_max', $this->capacite_max, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    /**
     * Récupérer tous les événements
     */
    

    /**
     * Rechercher des événements par n'importe quelle colonne
     * Utilise la méthode héritée getByColumn
     */
    public function getByField($column, $value, $orderBy = 'date_debut', $order = 'DESC', $limit = null) {
        return $this->getByColumn($column, $value, $orderBy, $order, $limit);
    }

    /**
     * Rechercher des événements par type
     */
    public function getByType($id_type) {
        return $this->getByColumn('id_type', $id_type, 'date_debut', 'ASC');
    }

    /**
     * Rechercher des événements par statut
     */
    public function getByStatut($statut) {
        return $this->getByColumn('statut', $statut, 'date_debut', 'ASC');
    }

    /**
     * Rechercher des événements par organisateur
     */
    public function getByOrganisateur($organisateur_id) {
        return $this->getByColumn('organisateur_id', $organisateur_id, 'date_debut', 'DESC');
    }

    /**
     * Rechercher des événements par lieu
     */
    public function getByLieu($lieu) {
        return $this->getByColumn('lieu', $lieu, 'date_debut', 'ASC');
    }

    /**
     * Récupérer les événements à venir
     */
    public function getUpcomingEvents($limit = null) {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE date_debut >= NOW() 
                  ORDER BY date_debut ASC";
        
        if ($limit !== null) {
            $query .= " LIMIT :limit";
        }
        
        $stmt = $this->conn->prepare($query);
        
        if ($limit !== null) {
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupérer les événements passés
     */
    public function getPastEvents($limit = null) {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE date_fin < NOW() 
                  ORDER BY date_debut DESC";
        
        if ($limit !== null) {
            $query .= " LIMIT :limit";
        }
        
        $stmt = $this->conn->prepare($query);
        
        if ($limit !== null) {
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupérer les événements en cours
     */
    public function getOngoingEvents() {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE date_debut <= NOW() AND date_fin >= NOW() 
                  ORDER BY date_debut ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * STATISTIQUES
     */

    /**
     * Compter les événements par statut
     */
    public function countByStatut() {
        $query = "SELECT statut, COUNT(*) as total 
                  FROM " . $this->table . " 
                  GROUP BY statut";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Compter les événements par type
     */
    public function countByType() {
        $query = "SELECT id_type, COUNT(*) as total 
                  FROM " . $this->table . " 
                  GROUP BY id_type";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Compter les événements par organisateur
     */
    public function countByOrganisateur() {
        $query = "SELECT organisateur_id, COUNT(*) as total 
                  FROM " . $this->table . " 
                  GROUP BY organisateur_id 
                  ORDER BY total DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Statistiques générales des événements
     */
    public function getGeneralStats() {
        $query = "SELECT 
                    COUNT(*) as total_events,
                    COUNT(CASE WHEN date_debut >= NOW() THEN 1 END) as upcoming_events,
                    COUNT(CASE WHEN date_fin < NOW() THEN 1 END) as past_events,
                    COUNT(CASE WHEN date_debut <= NOW() AND date_fin >= NOW() THEN 1 END) as ongoing_events,
                    COUNT(CASE WHEN statut = 'programmé' THEN 1 END) as programme_events,
                    COUNT(CASE WHEN statut = 'terminé' THEN 1 END) as termine_events,
                    COUNT(CASE WHEN statut = 'annulé' THEN 1 END) as annule_events,
                    SUM(capacite_max) as total_capacity,
                    AVG(capacite_max) as avg_capacity
                  FROM " . $this->table;
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Événements par mois
     */
    public function getEventsByMonth($year = null) {
        if ($year === null) {
            $year = date('Y');
        }
        
        $query = "SELECT 
                    MONTH(date_debut) as month,
                    MONTHNAME(date_debut) as month_name,
                    COUNT(*) as total 
                  FROM " . $this->table . " 
                  WHERE YEAR(date_debut) = :year 
                  GROUP BY MONTH(date_debut), MONTHNAME(date_debut)
                  ORDER BY MONTH(date_debut)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':year', $year, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Top événements par capacité
     */
    public function getTopEventsByCapacity($limit = 10) {
        $query = "SELECT * FROM " . $this->table . " 
                  ORDER BY capacite_max DESC 
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Statistiques par lieu
     */
    public function getStatsByLieu() {
        $query = "SELECT 
                    lieu,
                    COUNT(*) as total_events,
                    SUM(capacite_max) as total_capacity,
                    AVG(capacite_max) as avg_capacity
                  FROM " . $this->table . " 
                  GROUP BY lieu 
                  ORDER BY total_events DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Recherche avancée avec plusieurs critères
     */
    public function searchEvents($filters = []) {
        $query = "SELECT * FROM " . $this->table . " WHERE 1=1";
        $params = [];
        
        if (!empty($filters['titre'])) {
            $query .= " AND titre LIKE :titre";
            $params[':titre'] = '%' . $filters['titre'] . '%';
        }
        
        if (!empty($filters['id_type'])) {
            $query .= " AND id_type = :id_type";
            $params[':id_type'] = $filters['id_type'];
        }
        
        if (!empty($filters['statut'])) {
            $query .= " AND statut = :statut";
            $params[':statut'] = $filters['statut'];
        }
        
        if (!empty($filters['lieu'])) {
            $query .= " AND lieu LIKE :lieu";
            $params[':lieu'] = '%' . $filters['lieu'] . '%';
        }
        
        if (!empty($filters['organisateur_id'])) {
            $query .= " AND organisateur_id = :organisateur_id";
            $params[':organisateur_id'] = $filters['organisateur_id'];
        }
        
        if (!empty($filters['date_debut_min'])) {
            $query .= " AND date_debut >= :date_debut_min";
            $params[':date_debut_min'] = $filters['date_debut_min'];
        }
        
        if (!empty($filters['date_debut_max'])) {
            $query .= " AND date_debut <= :date_debut_max";
            $params[':date_debut_max'] = $filters['date_debut_max'];
        }
        
        $query .= " ORDER BY date_debut DESC";
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function addParticipant($eventId, $userId) {
        // Vérifier capacité
        $event = $this->getById($eventId);
        $current = $this->countParticipants($eventId);
        
        if ($event['capacite_max'] > 0 && $current >= $event['capacite_max']) {
            return ['success' => false, 'message' => 'Événement complet'];
        }

        try {
            $query = "INSERT INTO event_participants (event_id, user_id) VALUES (:eid, :uid)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':eid', $eventId);
            $stmt->bindParam(':uid', $userId);
            $stmt->execute();
            return ['success' => true];
        } catch (PDOException $e) {
            // Code 23000 = Violation d'unicité (déjà inscrit)
            if ($e->getCode() == 23000) {
                return ['success' => false, 'message' => 'Utilisateur déjà inscrit'];
            }
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // Désinscrire un utilisateur
    public function removeParticipant($eventId, $userId) {
        $query = "DELETE FROM event_participants WHERE event_id = :eid AND user_id = :uid";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':eid', $eventId);
        $stmt->bindParam(':uid', $userId);
        return $stmt->execute();
    }

    // Liste des participants avec détails User
    public function getParticipants($eventId) {
        $query = "SELECT u.id, u.nom, u.prenom, u.email, ep.date_inscription, ep.statut 
                  FROM event_participants ep
                  JOIN users u ON ep.user_id = u.id
                  WHERE ep.event_id = :eid
                  ORDER BY ep.date_inscription DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':eid', $eventId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Compter les participants
    public function countParticipants($eventId) {
        $query = "SELECT COUNT(*) as total FROM event_participants WHERE event_id = :eid";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':eid', $eventId);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }

    // Récupérer les événements qui commencent bientôt (pour les rappels)
    public function getEventsStartingSoon($hours = 24) {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE date_debut BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL :hours HOUR)
                  AND statut = 'publié'"; // Seules les annonces publiées
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':hours', $hours, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getAllEvents($orderBy = 'date_debut', $order = 'DESC') {
        // Sécurisation du tri
        $allowedColumns = ['date_debut', 'date_fin', 'titre', 'statut'];
        if (!in_array($orderBy, $allowedColumns)) $orderBy = 'date_debut';
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

        // Requête avec JOINTURE sur event_types
        $query = "SELECT e.*, t.nom as type_nom 
                  FROM " . $this->table . " e
                  LEFT JOIN event_types t ON e.id_type = t.id
                  ORDER BY e." . $orderBy . " " . $order;
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupérer un événement par ID avec le nom du type
     */
    public function getById($id) {
        $query = "SELECT e.*, t.nom as type_nom 
                  FROM " . $this->table . " e
                  LEFT JOIN event_types t ON e.id_type = t.id
                  WHERE e.id = :id
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function autoUpdateStatuses() {
        
        $sql = "UPDATE events 
                SET statut = 'terminé' 
                WHERE statut = 'programmé' 
                AND (
                    (date_fin IS NOT NULL AND date_fin < NOW())
                    OR 
                    (date_fin IS NULL AND date_debut < DATE_SUB(NOW(), INTERVAL 1 DAY))
                )";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute();
    }
}

?>