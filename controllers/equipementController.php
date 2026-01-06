<?php
require_once __DIR__ . "/../models/reservationModel.php";
require_once __DIR__ . "/../models/equipementModel.php";
require_once __DIR__ . "/../models/equipementType.php";


class EquipmentController {
    private $equipmentModel;
    private $equipmentTypeModel;
    private $reservationModel;
    
    public function __construct() {
        $this->equipmentModel = new Equipment();
        $this->equipmentTypeModel = new EquipmentType();
        $this->reservationModel = new Reservation();
    }
    
    /**
     * Display equipment dashboard
     */
    public function index() {
        // Auto-update reservation statuses
        $this->reservationModel->autoUpdateStatuses();         
        // Get statistics
        $statusStats = $this->equipmentModel->getStatsByStatus();
        $typeStats = $this->equipmentModel->getStatsByType();
        $maintenanceNeeded = $this->equipmentModel->getMaintenanceNeeded(30);
        
        // Get recent equipment
        $recentEquipment = $this->equipmentModel->getAll('id', 'DESC', 10);
        
        // Get reservation stats
        $reservationStats = $this->reservationModel->getStats();
        
        $data = [
            'title' => 'Tableau de Bord - Équipements',
            'statusStats' => $statusStats,
            'typeStats' => $typeStats,
            'maintenanceNeeded' => $maintenanceNeeded,
            'recentEquipment' => $recentEquipment,
            'reservationStats' => $reservationStats
        ];
        
        
        require_once __DIR__ . '/../views/public/EquipementView.php';
return $data;
    }
    
    /**
     * List all equipment with reservation status
     */
    public function list() {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = 20;
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $filterType = isset($_GET['type']) ? trim($_GET['type']) : '';
        $filterStatus = isset($_GET['status']) ? trim($_GET['status']) : '';
        $equipmentStatus=isset($_GET['equipment']) ? (int)$_GET['equipment'] : "";
        
        // Apply filters
        if (!empty($search)) {
            $equipment = $this->equipmentModel->search($search);
            $total = count($equipment);
        } elseif (!empty($filterType)) {
            $equipment = $this->equipmentModel->getByType($filterType);
            $total = count($equipment);
        } elseif (!empty($filterStatus)) {
            $equipment = $this->equipmentModel->getByStatus($filterStatus);
            $total = count($equipment);
        } 
         else {
            // Get equipment with reservation status
            $equipment = $this->equipmentModel->getAllWithReservationStatus('id', 'DESC');
            $total = count($equipment);
            
            // Apply pagination
            $equipment = array_slice($equipment, ($page - 1) * $perPage, $perPage);
        }
        
        $types = $this->equipmentTypeModel->getAll('nom', 'ASC');
        
        $data = [
            'title' => 'Liste des Équipements',
            'equipment' => $equipment,
            'types' => $types,
            'currentPage' => $page,
            'totalPages' => ceil($total / $perPage),
            'search' => $search,
            'filterType' => $filterType,
            'filterStatus' => $filterStatus
        ];
        return $data;
    }
    
    /**
     * Show create form
     */
    public function create() {
        $types = $this->equipmentTypeModel->getAll('nom', 'ASC');
        
        $data = [
            'title' => 'Ajouter un Équipement',
            'types' => $types
        ];
        
        require_once __DIR__ . 'views/equipment/create.php';
    }
    
    /**
     * Store new equipment
     */
    public function store() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /equipment/create');
            exit;
        }
        
        $data = [
            'nom' => htmlspecialchars(trim($_POST['nom'] ?? ''), ENT_QUOTES, 'UTF-8'),
            'id_type' => (int)($_POST['id_type'] ?? 0),
            'etat' => trim($_POST['etat'] ?? 'libre'),
            'description' => htmlspecialchars(trim($_POST['description'] ?? ''), ENT_QUOTES, 'UTF-8'),
            'localisation' => htmlspecialchars(trim($_POST['localisation'] ?? ''), ENT_QUOTES, 'UTF-8'),
            'derniere_maintenance' => !empty($_POST['derniere_maintenance']) ? trim($_POST['derniere_maintenance']) : null,
            'prochaine_maintenance' => !empty($_POST['prochaine_maintenance']) ? trim($_POST['prochaine_maintenance']) : null
        ];
        
        // Validation
        if (empty($data['nom'])) {
            $_SESSION['error'] = 'Le nom de l\'équipement est requis.';
            header('Location: /equipment/create');
            exit;
        }
        
        if ($data['id_type'] <= 0) {
            $_SESSION['error'] = 'Veuillez sélectionner un type d\'équipement.';
            header('Location: /equipment/create');
            exit;
        }
        
        if ($this->equipmentModel->create($data)) {
            $_SESSION['success'] = 'Équipement créé avec succès.';
            header('Location: /equipment/list');
            exit;
        } else {
            $_SESSION['error'] = 'Erreur lors de la création de l\'équipement.';
            header('Location: /equipment/create');
            exit;
        }
    }
    
    /**
     * Show edit form
     */
    public function edit($id) {
        $equipment = $this->equipmentModel->getById($id);
        
        if (!$equipment) {
            $_SESSION['error'] = 'Équipement introuvable.';
            header('Location: /equipment/list');
            exit;
        }
        
        $types = $this->equipmentTypeModel->getAll('nom', 'ASC');
        
        $data = [
            'title' => 'Modifier l\'Équipement',
            'equipment' => $equipment,
            'types' => $types
        ];
        
        require_once __DIR__ . 'views/equipment/edit.php';
    }
    
    /**
     * Update equipment
     */
    public function update($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /equipment/edit/' . $id);
            exit;
        }
        
        $data = [
            'nom' => htmlspecialchars(trim($_POST['nom'] ?? ''), ENT_QUOTES, 'UTF-8'),
            'id_type' => (int)($_POST['id_type'] ?? 0),
            'etat' => trim($_POST['etat'] ?? 'libre'),
            'description' => htmlspecialchars(trim($_POST['description'] ?? ''), ENT_QUOTES, 'UTF-8'),
            'localisation' => htmlspecialchars(trim($_POST['localisation'] ?? ''), ENT_QUOTES, 'UTF-8'),
            'derniere_maintenance' => !empty($_POST['derniere_maintenance']) ? trim($_POST['derniere_maintenance']) : null,
            'prochaine_maintenance' => !empty($_POST['prochaine_maintenance']) ? trim($_POST['prochaine_maintenance']) : null
        ];
        
        // Validation
        if (empty($data['nom'])) {
            $_SESSION['error'] = 'Le nom de l\'équipement est requis.';
            header('Location: /equipment/edit/' . $id);
            exit;
        }
        
        // Check if equipment is reserved before changing status
        if ($data['etat'] === 'en_maintenance') {
            $activeReservations = $this->reservationModel->getByEquipment($id);
            $hasActive = false;
            foreach ($activeReservations as $res) {
                if (in_array($res['statut'], ['confirmée', 'en_cours'])) {
                    $hasActive = true;
                    break;
                }
            }
            
            if ($hasActive) {
                $_SESSION['error'] = 'Impossible de mettre en maintenance : équipement actuellement réservé.';
                header('Location: /equipment/edit/' . $id);
                exit;
            }
        }
        
        if ($this->equipmentModel->update($id, $data)) {
            $_SESSION['success'] = 'Équipement modifié avec succès.';
            header('Location: /equipment/list');
            exit;
        } else {
            $_SESSION['error'] = 'Erreur lors de la modification de l\'équipement.';
            header('Location: /equipment/edit/' . $id);
            exit;
        }
    }
    
    /**
     * Delete equipment
     */
    public function delete($id) {
        // Check if equipment has active reservations
        $activeReservations = $this->reservationModel->getByEquipment($id);
        $hasActive = false;
        foreach ($activeReservations as $res) {
            if (in_array($res['statut'], ['confirmée', 'en_cours', 'en_attente'])) {
                $hasActive = true;
                break;
            }
        }
        
        if ($hasActive) {
            $_SESSION['error'] = 'Impossible de supprimer : équipement a des réservations actives.';
        } else {
            if ($this->equipmentModel->delete($id)) {
                $_SESSION['success'] = 'Équipement supprimé avec succès.';
            } else {
                $_SESSION['error'] = 'Erreur lors de la suppression de l\'équipement.';
            }
        }
        
        header('Location: /equipment/list');
        exit;
    }
    
    /**
     * Update equipment status (AJAX)
     */
   public function updateStatus() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
            exit;
        }
        
        $id = (int)($_POST['id'] ?? 0);
        $etat = trim($_POST['etat'] ?? '');
        
        // 1. INTERDIRE LE PASSAGE MANUEL À "RÉSERVÉ"
        if ($etat === 'réserve') {
            echo json_encode([
                'success' => false, 
                'message' => "⛔ Action interdite : Le statut 'Réservé' est géré automatiquement. Veuillez créer une réservation officielle pour bloquer cet équipement."
            ]);
            exit;
        }
        
        // 2. GESTION DE LA MAINTENANCE
        if ($etat === 'en_maintenance') {
            // On vérifie s'il y a une réservation en cours
            $activeRes = $this->reservationModel->getCurrentReservationForEquipment($id);
            if ($activeRes) {
                echo json_encode([
                    'success' => false, 
                    'message' => "Impossible de mettre en maintenance : Une réservation est en cours actuellement."
                ]);
                exit;
            }
        }

        // 3. GESTION DU RETOUR À "LIBRE" (Fin de maintenance)
        if ($etat === 'libre') {
            // On revérifie au cas où une réservation aurait commencé entre temps
            $activeRes = $this->reservationModel->getCurrentReservationForEquipment($id);
            if ($activeRes) {
                echo json_encode([
                    'success' => false, 
                    'message' => "Impossible de libérer : Une réservation a commencé automatiquement."
                ]);
                exit;
            }
        }
        
        // Exécution de la mise à jour
        if ($this->equipmentModel->updateStatus($id, $etat)) {
            echo json_encode(['success' => true, 'message' => 'Statut mis à jour avec succès']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur technique lors de la mise à jour']);
        }
        exit;
    }
    /**
     * Maintenance page
     */
    public function maintenance() {
        $maintenanceNeeded = $this->equipmentModel->getMaintenanceNeeded(30);
        $inMaintenance = $this->equipmentModel->getByStatus('en_maintenance');
        
        $data = [
            'title' => 'Gestion de la Maintenance',
            'maintenanceNeeded' => $maintenanceNeeded,
            'inMaintenance' => $inMaintenance
        ];
        
        require_once __DIR__ . 'views/equipment/maintenance.php';
    }
    
    /**
     * Update maintenance dates
     */
    public function updateMaintenance($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /equipment/maintenance');
            exit;
        }
        
        $derniere = !empty($_POST['derniere_maintenance']) ? trim($_POST['derniere_maintenance']) : null;
        $prochaine = !empty($_POST['prochaine_maintenance']) ? trim($_POST['prochaine_maintenance']) : null;
        
        if ($this->equipmentModel->updateMaintenance($id, $derniere, $prochaine)) {
            // Also update status to libre if it was in maintenance
            $this->equipmentModel->updateStatus($id, 'libre');
            $_SESSION['success'] = 'Maintenance mise à jour avec succès.';
        } else {
            $_SESSION['error'] = 'Erreur lors de la mise à jour de la maintenance.';
        }
        
        header('Location: /equipment/maintenance');
        exit;
    }
    
    /**
     * View equipment details with reservations
     */
    public function view($id) {
        $equipment = $this->equipmentModel->getWithReservationStatus($id);
        
        if (!$equipment) {
            $_SESSION['error'] = 'Équipement introuvable.';
            header('Location: /equipment/list');
            exit;
        }
        
        // Get type information
        $type = $this->equipmentTypeModel->getById($equipment['id_type']);
        
        // Get all reservations for this equipment
        $reservations = $this->reservationModel->getByEquipment($id);
        
        // Get upcoming reservations
        $upcomingReservations = array_filter($reservations, function($res) {
            return in_array($res['statut'], ['confirmée', 'en_attente']) && 
                   $res['date_debut'] >= date('Y-m-d');
        });
        
        $data = [
            'title' => 'Détails de l\'Équipement',
            'equipment' => $equipment,
            'type' => $type,
            'reservations' => $reservations,
            'upcomingReservations' => $upcomingReservations
        ];
        
        require_once __DIR__ . 'views/equipment/view.php';
    }
    
    /**
     * Check equipment availability for a period (AJAX)
     */
    public function checkAvailability() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
            exit;
        }
        
        $id = (int)($_GET['id'] ?? 0);
        $date_debut = $_GET['date_debut'] ?? '';
        $date_fin = $_GET['date_fin'] ?? '';
        
        if (!$id || !$date_debut || !$date_fin) {
            echo json_encode(['success' => false, 'message' => 'Paramètres manquants']);
            exit;
        }
        
        $result = $this->equipmentModel->isAvailableForPeriod($id, $date_debut, $date_fin);
        
        echo json_encode([
            'success' => true,
            'available' => $result['available'],
            'conflicts' => $result['conflicts']
        ]);
        exit;
    }
    
    /**
     * Get equipment availability calendar (AJAX)
     */
    public function getAvailabilityCalendar() {
        header('Content-Type: application/json');
        
        $id = (int)($_GET['id'] ?? 0);
        $start_date = $_GET['start_date'] ?? date('Y-m-01');
        $end_date = $_GET['end_date'] ?? date('Y-m-t');
        
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID requis']);
            exit;
        }
        
        $calendar = $this->equipmentModel->getAvailabilityCalendar($id, $start_date, $end_date);
        
        echo json_encode([
            'success' => true,
            'data' => $calendar
        ]);
        exit;
    }
}
?>