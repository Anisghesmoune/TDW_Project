<?php
class EquipmentController {
    private $equipmentModel;
    private $equipmentTypeModel;
    
    public function __construct() {
        $this->equipmentModel = new Equipment();
        $this->equipmentTypeModel = new EquipmentType();
    }
    
    /**
     * Display equipment dashboard
     */
    public function index() {
        // Get statistics
        $statusStats = $this->equipmentModel->getStatsByStatus();
        $typeStats = $this->equipmentModel->getStatsByType();
        $maintenanceNeeded = $this->equipmentModel->getMaintenanceNeeded(30);
        
        // Get recent equipment
        $recentEquipment = $this->equipmentModel->getAll('id', 'DESC', 10);
        
        $data = [
            'title' => 'Tableau de Bord - Équipements',
            'statusStats' => $statusStats,
            'typeStats' => $typeStats,
            'maintenanceNeeded' => $maintenanceNeeded,
            'recentEquipment' => $recentEquipment
        ];
        
      
    }
    
    /**
     * List all equipment
     */
    public function list() {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = 20;
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $filterType = isset($_GET['type']) ? trim($_GET['type']) : '';
        $filterStatus = isset($_GET['status']) ? trim($_GET['status']) : '';
        
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
        } else {
            $equipment = $this->equipmentModel->getPaginated($page, $perPage, 'id', 'DESC');
            $total = $this->equipmentModel->count();
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
        
        require_once 'views/equipment/create.php';
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
        
        require_once 'views/equipment/edit.php';
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
        if ($this->equipmentModel->delete($id)) {
            $_SESSION['success'] = 'Équipement supprimé avec succès.';
        } else {
            $_SESSION['error'] = 'Erreur lors de la suppression de l\'équipement.';
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
        
        if (!in_array($etat, ['libre', 'réserve', 'en_maintenance'])) {
            echo json_encode(['success' => false, 'message' => 'État invalide']);
            exit;
        }
        
        if ($this->equipmentModel->updateStatus($id, $etat)) {
            echo json_encode(['success' => true, 'message' => 'Statut mis à jour']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour']);
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
        
        require_once 'views/equipment/maintenance.php';
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
     * View equipment details
     */
    public function view($id) {
        $equipment = $this->equipmentModel->getById($id);
        
        if (!$equipment) {
            $_SESSION['error'] = 'Équipement introuvable.';
            header('Location: /equipment/list');
            exit;
        }
        
        // Get type information
        $type = $this->equipmentTypeModel->getById($equipment['id_type']);
        
        $data = [
            'title' => 'Détails de l\'Équipement',
            'equipment' => $equipment,
            'type' => $type
        ];
        
        require_once 'views/equipment/view.php';
    }
}
?>