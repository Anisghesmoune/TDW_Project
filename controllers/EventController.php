<?php
require_once '../models/Event.php';

class EventController {
    private $eventModel;
    
    public function __construct() {
        $this->eventModel = new Event();
    }
    
 public function index() {
        try {
            $orderBy = $_GET['orderBy'] ?? 'date_debut';
            $order = $_GET['order'] ?? 'DESC';
            
            $events = $this->eventModel->getAllEvents($orderBy, $order);
            
                return [
            'total' => count($events),
            'data' => $events
        ];

    } catch (Exception $e) {
        // En cas d'erreur, retourner vide
        return [
            'total' => 0,
            'data' => []
        ];
    }
}
public function getALL(){
    return $this->eventModel->getAll();
}
    public function store() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Méthode non autorisée");
            }

            // Validation des données
            $this->validateEventData($_POST);

            // Assigner les valeurs
            $this->eventModel->titre = $this->sanitize($_POST['titre']);
            $this->eventModel->description = $this->sanitize($_POST['description']);
            $this->eventModel->id_type = (int)$_POST['id_type'];
            $this->eventModel->date_debut = $_POST['date_debut'];
            $this->eventModel->date_fin = $_POST['date_fin'] ?? null;
            $this->eventModel->lieu = $this->sanitize($_POST['lieu']);
            $this->eventModel->organisateur_id = $_POST['organisateur_id'] ?? null;
            $this->eventModel->statut = $_POST['statut'] ?? 'programmé';
            $this->eventModel->capacite_max = $_POST['capacite_max'] ?? null;

            // Créer l'événement
            if ($this->eventModel->create()) {
                $_SESSION['success'] = "Événement créé avec succès";
                exit();
            } else {
                throw new Exception("Erreur lors de la création de l'événement");
            }
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            exit();
        }
    }
 
  
    public function show($id) {
        try {
            $event = $this->eventModel->getById($id);
            
            if (!$event) {
                throw new Exception("Événement non trouvé");
            }
            
        } catch (Exception $e) {
            $this->handleError($e->getMessage());
        }
    }

    /**
     * Afficher le formulaire d'édition
     */
    public function edit($id) {
        try {
            $event = $this->eventModel->getById($id);
            
            if (!$event) {
                throw new Exception("Événement non trouvé");
            }
            
        } catch (Exception $e) {
            $this->handleError($e->getMessage());
        }
    }

    /**
     * Mettre à jour un événement
     */
    public function update($id) {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Méthode non autorisée");
            }

            // Vérifier si l'événement existe
            if (!$this->eventModel->exists($id)) {
                throw new Exception("Événement non trouvé");
            }

            // Validation des données
            $this->validateEventData($_POST);

            // Assigner les valeurs
            $this->eventModel->id = $id;
            $this->eventModel->titre = $this->sanitize($_POST['titre']);
            $this->eventModel->description = $this->sanitize($_POST['description']);
            $this->eventModel->id_type = (int)$_POST['id_type'];
            $this->eventModel->date_debut = $_POST['date_debut'];
            $this->eventModel->date_fin = $_POST['date_fin'] ?? null;
            $this->eventModel->lieu = $this->sanitize($_POST['lieu']);
            $this->eventModel->organisateur_id = $_POST['organisateur_id'] ?? null;
            $this->eventModel->statut = $_POST['statut'] ?? 'programmé';
            $this->eventModel->capacite_max = $_POST['capacite_max'] ?? null;

            // Mettre à jour l'événement
            if ($this->eventModel->update()) {
                $_SESSION['success'] = "Événement mis à jour avec succès";
                header('Location: /events/' . $id);
                exit();
            } else {
                throw new Exception("Erreur lors de la mise à jour de l'événement");
            }
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: /events/' . $id . '/edit');
            exit();
        }
    }

    /**
     * Supprimer un événement
     */
    public function delete($id) {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Méthode non autorisée");
            }

            if (!$this->eventModel->exists($id)) {
                throw new Exception("Événement non trouvé");
            }

            if ($this->eventModel->delete($id)) {
                $_SESSION['success'] = "Événement supprimé avec succès";
            } else {
                throw new Exception("Erreur lors de la suppression de l'événement");
            }
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }
        
        exit();
    }

    /**
     * Rechercher des événements par type
     */
    public function getByType($id_type) {
        try {
            $events = $this->eventModel->getByType($id_type);
            require_once __DIR__ . '/../views/events/list.php';
        } catch (Exception $e) {
            $this->handleError($e->getMessage());
        }
    }

    /**
     * Rechercher des événements par statut
     */
    public function getByStatut($statut) {
        try {
            $events = $this->eventModel->getByStatut($statut);
            require_once __DIR__ . '/../views/events/list.php';
        } catch (Exception $e) {
            $this->handleError($e->getMessage());
        }
    }

    /**
     * Rechercher des événements par organisateur
     */
    public function getByOrganisateur($organisateur_id) {
        try {
            $events = $this->eventModel->getByOrganisateur($organisateur_id);
        } catch (Exception $e) {
            $this->handleError($e->getMessage());
        }
    }

    /**
     * Rechercher des événements par lieu
     */
    public function getByLieu($lieu) {
        try {
            $events = $this->eventModel->getByLieu($lieu);
        } catch (Exception $e) {
            $this->handleError($e->getMessage());
        }
    }

    /**
     * Afficher les événements à venir
     */
    public function upcoming() {
        try {
            $limit = $_GET['limit'] ?? null;
            $events = $this->eventModel->getUpcomingEvents($limit);
        } catch (Exception $e) {
            $this->handleError($e->getMessage());
        }
    }

    /**
     * Afficher les événements passés
     */
    public function past() {
        try {
            $limit = $_GET['limit'] ?? null;
            $events = $this->eventModel->getPastEvents($limit);
        } catch (Exception $e) {
            $this->handleError($e->getMessage());
        }
    }

    /**
     * Afficher les événements en cours
     */
    public function ongoing() {
        try {
            $events = $this->eventModel->getOngoingEvents();
        } catch (Exception $e) {
            $this->handleError($e->getMessage());
        }
    }

    /**
     * Afficher les statistiques des événements
     */
    public function statistics() {
        try {
            $stats = [
                'general' => $this->eventModel->getGeneralStats(),
                'by_statut' => $this->eventModel->countByStatut(),
                'by_type' => $this->eventModel->countByType(),
                'by_organisateur' => $this->eventModel->countByOrganisateur(),
                'by_lieu' => $this->eventModel->getStatsByLieu(),
                'by_month' => $this->eventModel->getEventsByMonth(),
                'top_capacity' => $this->eventModel->getTopEventsByCapacity(5)
            ];
            
            require_once __DIR__ . '/../views/events/statistics.php';
        } catch (Exception $e) {
            $this->handleError($e->getMessage());
        }
    }

    /**
     * Recherche avancée
     */
    public function search() {
        try {
            $filters = [
                'titre' => $_GET['titre'] ?? '',
                'id_type' => $_GET['id_type'] ?? '',
                'statut' => $_GET['statut'] ?? '',
                'lieu' => $_GET['lieu'] ?? '',
                'organisateur_id' => $_GET['organisateur_id'] ?? '',
                'date_debut_min' => $_GET['date_debut_min'] ?? '',
                'date_debut_max' => $_GET['date_debut_max'] ?? ''
            ];
            
            $events = $this->eventModel->searchEvents($filters);
        } catch (Exception $e) {
            $this->handleError($e->getMessage());
        }
    }

    /**
     * Pagination des événements
     */
    public function paginate() {
        try {
            $page = $_GET['page'] ?? 1;
            $perPage = $_GET['perPage'] ?? 10;
            $orderBy = $_GET['orderBy'] ?? 'date_debut';
            $order = $_GET['order'] ?? 'DESC';
            
            $events = $this->eventModel->getPaginated($page, $perPage, $orderBy, $order);
            $total = $this->eventModel->count();
            $totalPages = ceil($total / $perPage);
            
            require_once __DIR__ . '/../views/events/paginate.php';
        } catch (Exception $e) {
            $this->handleError($e->getMessage());
        }
    }

    /**
     * API - Retourner tous les événements en JSON
     */
    public function apiGetAll() {
        try {
            header('Content-Type: application/json');
            $events = $this->eventModel->getAllEvents();
            echo json_encode([
                'success' => true,
                'data' => $events
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * API - Retourner un événement en JSON
     */
    public function apiGetById($id) {
        try {
            header('Content-Type: application/json');
            $event = $this->eventModel->getById($id);
            
            if (!$event) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Événement non trouvé'
                ]);
                return;
            }
            
            echo json_encode([
                'success' => true,
                'data' => $event
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * API - Créer un événement via JSON
     */
    public function apiCreate() {
        try {
            header('Content-Type: application/json');
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Données invalides'
                ]);
                return;
            }

            $this->validateEventData($data);

            $this->eventModel->titre = $this->sanitize($data['titre']);
            $this->eventModel->description = $this->sanitize($data['description']);
            $this->eventModel->id_type = (int)$data['id_type'];
            $this->eventModel->date_debut = $data['date_debut'];
            $this->eventModel->date_fin = $data['date_fin'] ?? null;
            $this->eventModel->lieu = $this->sanitize($data['lieu']);
            $this->eventModel->organisateur_id = $data['organisateur_id'] ?? null;
            $this->eventModel->statut = $data['statut'] ?? 'programmé';
            $this->eventModel->capacite_max = $data['capacite_max'] ?? null;

            if ($this->eventModel->create()) {
                http_response_code(201);
                echo json_encode([
                    'success' => true,
                    'message' => 'Événement créé avec succès'
                ]);
            } else {
                throw new Exception("Erreur lors de la création");
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * API - Mettre à jour un événement via JSON
     */
    public function apiUpdate($id) {
        try {
            header('Content-Type: application/json');
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Données invalides'
                ]);
                return;
            }

            if (!$this->eventModel->exists($id)) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Événement non trouvé'
                ]);
                return;
            }

            $this->validateEventData($data);

            $this->eventModel->id = $id;
            $this->eventModel->titre = $this->sanitize($data['titre']);
            $this->eventModel->description = $this->sanitize($data['description']);
            $this->eventModel->id_type = (int)$data['id_type'];
            $this->eventModel->date_debut = $data['date_debut'];
            $this->eventModel->date_fin = $data['date_fin'] ?? null;
            $this->eventModel->lieu = $this->sanitize($data['lieu']);
            $this->eventModel->organisateur_id = $data['organisateur_id'] ?? null;
            $this->eventModel->statut = $data['statut'] ?? 'programmé';
            $this->eventModel->capacite_max = $data['capacite_max'] ?? null;

            if ($this->eventModel->update()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Événement mis à jour avec succès'
                ]);
            } else {
                throw new Exception("Erreur lors de la mise à jour");
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * API - Supprimer un événement
     */
    public function apiDelete($id) {
        try {
            header('Content-Type: application/json');
            
            if (!$this->eventModel->exists($id)) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Événement non trouvé'
                ]);
                return;
            }

            if ($this->eventModel->delete($id)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Événement supprimé avec succès'
                ]);
            } else {
                throw new Exception("Erreur lors de la suppression");
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * API - Obtenir les statistiques
     */
    public function apiGetStatistics() {
        try {
            header('Content-Type: application/json');
            
            $stats = [
                'general' => $this->eventModel->getGeneralStats(),
                'by_statut' => $this->eventModel->countByStatut(),
                'by_type' => $this->eventModel->countByType(),
                'by_organisateur' => $this->eventModel->countByOrganisateur(),
                'by_lieu' => $this->eventModel->getStatsByLieu(),
                'by_month' => $this->eventModel->getEventsByMonth(),
                'top_capacity' => $this->eventModel->getTopEventsByCapacity(10)
            ];
            
            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Valider les données de l'événement
     */
    private function validateEventData($data) {
        $errors = [];

        if (empty($data['titre'])) {
            $errors[] = "Le titre est requis";
        }

        if (empty($data['id_type'])) {
            $errors[] = "Le type est requis";
        }

        if (empty($data['date_debut'])) {
            $errors[] = "La date de début est requise";
        }

        if (empty($data['lieu'])) {
            $errors[] = "Le lieu est requis";
        }

        // Valider les dates
        if (!empty($data['date_debut']) && !empty($data['date_fin'])) {
            $dateDebut = strtotime($data['date_debut']);
            $dateFin = strtotime($data['date_fin']);
            
            if ($dateFin < $dateDebut) {
                $errors[] = "La date de fin doit être après la date de début";
            }
        }

        // Valider la capacité
        if (isset($data['capacite_max']) && $data['capacite_max'] !== '' && $data['capacite_max'] < 0) {
            $errors[] = "La capacité maximale doit être positive";
        }

        if (!empty($errors)) {
            throw new Exception(implode(', ', $errors));
        }
    }
    public function apiRegisterParticipant() {
        // JSON Input : { "event_id": 1, "user_id": 5 }
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['event_id']) || empty($data['user_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'IDs manquants']);
            return;
        }

        $result = $this->eventModel->addParticipant($data['event_id'], $data['user_id']);
        echo json_encode($result);
    }

    /**
     * API - Désinscrire un utilisateur
     */
    public function apiUnregisterParticipant() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if ($this->eventModel->removeParticipant($data['event_id'], $data['user_id'])) {
            echo json_encode(['success' => true, 'message' => 'Désinscription réussie']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la désinscription']);
        }
    }

    /**
     * API - Liste des participants pour un événement
     */
    public function apiGetParticipants($eventId) {
        $participants = $this->eventModel->getParticipants($eventId);
        echo json_encode(['success' => true, 'data' => $participants]);
    }

    /**
     * API - Envoyer les rappels (Simulation d'envoi d'email)
     */
    public function apiSendReminders() {
        // Récupère les événements qui commencent dans 24h
        $events = $this->eventModel->getEventsStartingSoon(24);
        $log = [];

        foreach ($events as $event) {
            $participants = $this->eventModel->getParticipants($event['id']);
            $count = 0;
            
            // Simulation d'envoi de mail
            foreach ($participants as $p) {
                // mail($p['email'], "Rappel : " . $event['titre'], "L'événement commence demain...");
                $count++;
            }
            
            $log[] = "Rappel envoyé pour '{$event['titre']}' à $count participant(s).";
        }

        if (empty($log)) {
            $log[] = "Aucun événement ne nécessite de rappel immédiat.";
        }

        echo json_encode(['success' => true, 'message' => implode(" ", $log)]);
    }

    /**
     * Sanitize input
     */
    private function sanitize($data) {
        return htmlspecialchars(strip_tags(trim($data)));
    }

    /**
     * Gérer les erreurs
     */
    private function handleError($message) {
        $_SESSION['error'] = $message;
        header('Location: /events');
        exit();
    }
    
}


