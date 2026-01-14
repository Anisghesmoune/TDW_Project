<?php
require_once __DIR__ . '/../models/Event.php';
require_once __DIR__ . '/../models/Settings.php';
require_once __DIR__ . '/../models/Menu.php';

class EventController {
    private $eventModel;
     private $settingsModel;
    private $menuModel;
    
    public function __construct() {
        $this->eventModel = new Event();
        $this->settingsModel = new Settings();
        $this->menuModel = new Menu();
    }
    
public function index() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?route=login');
            exit;
        }

       

        try {
            $orderBy = $_GET['orderBy'] ?? 'date_debut';
            $order = $_GET['order'] ?? 'DESC';
            
            $events = $this->eventModel->getAllEvents($orderBy, $order);
            $this->eventModel->autoUpdateStatuses();
            $config = $this->settingsModel->getAllSettings();
            $menu = $this->menuModel->getMenuTree();

            $data = [
                'title' => 'Gestion des Événements',
                'config' => $config,
                'menu' => $menu,
                'events' => $events,
                'total' => count($events)
            ];

            require_once __DIR__ . '/../views/event-management.php';
            $view = new EventAdminView($data);
            $view->render();

        } catch (Exception $e) {
            echo "Erreur lors du chargement des événements : " . $e->getMessage();
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

            $this->validateEventData($_POST);

            $this->eventModel->titre = $this->sanitize($_POST['titre']);
            $this->eventModel->description = $this->sanitize($_POST['description']);
            $this->eventModel->id_type = (int)$_POST['id_type'];
            $this->eventModel->date_debut = $_POST['date_debut'];
            $this->eventModel->date_fin = $_POST['date_fin'] ?? null;
            $this->eventModel->localisation = $this->sanitize($_POST['localisation']);
            $this->eventModel->organisateur_id = $_POST['organisateur_id'] ?? null;
            $this->eventModel->statut = $_POST['statut'] ?? 'programmé';
            $this->eventModel->capacite_max = $_POST['capacite_max'] ?? null;

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

            if (!$this->eventModel->exists($id)) {
                throw new Exception("Événement non trouvé");
            }

            $this->validateEventData($_POST);

            $this->eventModel->id = $id;
            $this->eventModel->titre = $this->sanitize($_POST['titre']);
            $this->eventModel->description = $this->sanitize($_POST['description']);
            $this->eventModel->id_type = (int)$_POST['id_type'];
            $this->eventModel->date_debut = $_POST['date_debut'];
            $this->eventModel->date_fin = $_POST['date_fin'] ?? null;
            $this->eventModel->localisation = $this->sanitize($_POST['localisation']);
            $this->eventModel->organisateur_id = $_POST['organisateur_id'] ?? null;
            $this->eventModel->statut = $_POST['statut'] ?? 'programmé';
            $this->eventModel->capacite_max = $_POST['capacite_max'] ?? null;

            if ($this->eventModel->update()) {
                $_SESSION['success'] = "Événement mis à jour avec succès";
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

   
    public function getByType($id_type) {
        try {
            $events = $this->eventModel->getByType($id_type);
            require_once __DIR__ . __DIR__ . '/../views/events/list.php';
        } catch (Exception $e) {
            $this->handleError($e->getMessage());
        }
    }

    
    public function getByStatut($statut) {
        try {
            $events = $this->eventModel->getByStatut($statut);
            require_once __DIR__ . __DIR__ . '/../views/events/list.php';
        } catch (Exception $e) {
            $this->handleError($e->getMessage());
        }
    }

    
    public function getByOrganisateur($organisateur_id) {
        try {
            $events = $this->eventModel->getByOrganisateur($organisateur_id);
        } catch (Exception $e) {
            $this->handleError($e->getMessage());
        }
    }

   
    public function getByLieu($localisation) {
        try {
            $events = $this->eventModel->getByLieu($localisation);
        } catch (Exception $e) {
            $this->handleError($e->getMessage());
        }
    }

    public function upcoming() {
        try {
            $limit = $_GET['limit'] ?? null;
            $events = $this->eventModel->getUpcomingEvents($limit);
        } catch (Exception $e) {
            $this->handleError($e->getMessage());
        }
    }

   
    public function past() {
        try {
            $limit = $_GET['limit'] ?? null;
            $events = $this->eventModel->getPastEvents($limit);
        } catch (Exception $e) {
            $this->handleError($e->getMessage());
        }
    }

  
    public function ongoing() {
        try {
            $events = $this->eventModel->getOngoingEvents();
        } catch (Exception $e) {
            $this->handleError($e->getMessage());
        }
    }

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
            
            require_once __DIR__ . __DIR__ . '/../views/events/statistics.php';
        } catch (Exception $e) {
            $this->handleError($e->getMessage());
        }
    }

    
    public function search() {
        try {
            $filters = [
                'titre' => $_GET['titre'] ?? '',
                'id_type' => $_GET['id_type'] ?? '',
                'statut' => $_GET['statut'] ?? '',
                'localisation' => $_GET['localisation'] ?? '',
                'organisateur_id' => $_GET['organisateur_id'] ?? '',
                'date_debut_min' => $_GET['date_debut_min'] ?? '',
                'date_debut_max' => $_GET['date_debut_max'] ?? ''
            ];
            
            $events = $this->eventModel->searchEvents($filters);
        } catch (Exception $e) {
            $this->handleError($e->getMessage());
        }
    }

 
    public function paginate() {
        try {
            $page = $_GET['page'] ?? 1;
            $perPage = $_GET['perPage'] ?? 10;
            $orderBy = $_GET['orderBy'] ?? 'date_debut';
            $order = $_GET['order'] ?? 'DESC';
            
            $events = $this->eventModel->getPaginated($page, $perPage, $orderBy, $order);
            $total = $this->eventModel->count();
            $totalPages = ceil($total / $perPage);
            
            require_once __DIR__ . __DIR__ . '/../views/events/paginate.php';
        } catch (Exception $e) {
            $this->handleError($e->getMessage());
        }
    }

   
    public function apiGetAll() {
        try {
            header('Content-Type: application/json');
            $events = $this->eventModel->getAllEvents();
            echo json_encode([
                'success' => true,
                'data' => $events
            ]);
            exit;
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
            exit;
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
        exit();
    }

   
   public function apiCreate() {
        if (ob_get_length()) ob_clean();
        
        header('Content-Type: application/json');

        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405); 
                echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
                exit;
            }

            $json = file_get_contents('php://input');
            $data = json_decode($json, true);
            
            if (!$data) {
                $data = $_POST;
            }
            
            if (empty($data)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Aucune donnée reçue']);
                exit;
            }
if (method_exists($this, 'validateEventData')) {
                $this->validateEventData($data);
            }

            $this->eventModel->titre = htmlspecialchars(strip_tags($data['titre'] ?? ''));
            $this->eventModel->description = htmlspecialchars(strip_tags($data['description'] ?? ''));
            $this->eventModel->id_type = (int)($data['id_type'] ?? 0);
            $this->eventModel->date_debut = $data['date_debut'];
            $this->eventModel->date_fin = !empty($data['date_fin']) ? $data['date_fin'] : null;
            $this->eventModel->localisation = htmlspecialchars(strip_tags($data['localisation'] ?? ''));
            $this->eventModel->organisateur_id = !empty($data['organisateur_id']) ? (int)$data['organisateur_id'] : null;
            $this->eventModel->statut = $data['statut'] ?? 'programmé';
            $this->eventModel->capacite_max = !empty($data['capacite_max']) ? (int)$data['capacite_max'] : null;

            
            if ($this->eventModel->create()) {
                http_response_code(201);
                echo json_encode([
                    'success' => true,
                    'message' => 'Événement créé avec succès'
                ]);
            } else {
                throw new Exception("Erreur lors de l'insertion en base de données");
            }

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        
        exit;
    }
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
            $this->eventModel->localisation = $this->sanitize($data['localisation']);
            $this->eventModel->organisateur_id = $data['organisateur_id'] ?? null;
            $this->eventModel->statut = $data['statut'] ?? 'programmé';
            $this->eventModel->capacite_max = $data['capacite_max'] ?? null;

            if ($this->eventModel->update()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Événement mis à jour avec succès'
                ]);
                exit;
            } else {
                throw new Exception("Erreur lors de la mise à jour");
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
            exit;
        }
        
    }

    
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

        if (empty($data['localisation'])) {
            $errors[] = "Le localisation est requis";
        }

        if (!empty($data['date_debut']) && !empty($data['date_fin'])) {
            $dateDebut = strtotime($data['date_debut']);
            $dateFin = strtotime($data['date_fin']);
            
            if ($dateFin < $dateDebut) {
                $errors[] = "La date de fin doit être après la date de début";
            }
        }

        if (isset($data['capacite_max']) && $data['capacite_max'] !== '' && $data['capacite_max'] < 0) {
            $errors[] = "La capacité maximale doit être positive";
        }

        if (!empty($errors)) {
            throw new Exception(implode(', ', $errors));
        }
    }
    public function apiRegisterParticipant() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['event_id']) || empty($data['user_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'IDs manquants']);
            return;
        }

        $result = $this->eventModel->addParticipant($data['event_id'], $data['user_id']);
        echo json_encode($result);
                exit; 
    }

    
    public function apiUnregisterParticipant() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if ($this->eventModel->removeParticipant($data['event_id'], $data['user_id'])) {
            echo json_encode(['success' => true, 'message' => 'Désinscription réussie']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la désinscription']);
        }
        exit;
    }

    public function apiGetParticipants($eventId) {
        $participants = $this->eventModel->getParticipants($eventId);
        echo json_encode(['success' => true, 'data' => $participants]);
        exit;
    }

    public function apiSendReminders() {
        $events = $this->eventModel->getEventsStartingSoon(24);
        $log = [];

        foreach ($events as $event) {
            $participants = $this->eventModel->getParticipants($event['id']);
            $count = 0;
            
            foreach ($participants as $p) {
                mail($p['email'], "Rappel : " . $event['titre'], "L'événement commence demain...");
                $count++;
            }
            
            $log[] = "Rappel envoyé pour '{$event['titre']}' à $count participant(s).";
        }

        if (empty($log)) {
            $log[] = "Aucun événement ne nécessite de rappel immédiat.";
        }

        echo json_encode(['success' => true, 'message' => implode(" ", $log)]);
        exit;
    }

  
    private function sanitize($data) {
        return htmlspecialchars(strip_tags(trim($data)));
    }

    
    private function handleError($message) {
        $_SESSION['error'] = $message;
        exit();
    }
     public function indexPublic() {
        if (!$this->settingsModel) $this->settingsModel = new Settings();
        if (!$this->menuModel) $this->menuModel = new Menu();

        $events = $this->eventModel->getAllEvents('date_debut', 'ASC');
        $this->eventModel->autoUpdateStatuses();
        $config = $this->settingsModel->getAllSettings();
        $menu = $this->menuModel->getMenuTree();

        $data = [
            'title' => 'Agenda des Événements',
            'config' => $config,
            'menu' => $menu,
            'events' => $events
        ];

        require_once __DIR__ . '/../views/public/EventListView.php';
        $view = new EventListView($data);
        $view->render();
    }
    
}


