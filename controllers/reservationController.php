<?php
class ReservationController {
    private $reservationModel;
    private $equipmentModel;
    private $userModel;
    
    public function __construct() {
        $this->reservationModel = new Reservation();
        $this->equipmentModel = new Equipment();
        $this->userModel = new UserModel(); 
    }
    
    
    public function index() {
        $this->reservationModel->autoUpdateStatuses();
        
        $stats = $this->reservationModel->getStats();
        
        $currentReservations = $this->reservationModel->getCurrent();
        
        $upcomingReservations = $this->reservationModel->getUpcoming(7);
        
        $pendingReservations = $this->reservationModel->getPending();
        
        $data = [
            'title' => 'Gestion des Réservations',
            'stats' => $stats,
            'currentReservations' => $currentReservations,
            'upcomingReservations' => $upcomingReservations,
            'pendingReservations' => $pendingReservations
        ];
        
        return $data;
    }
    
   
    public function list() {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = 20;
        $filterStatus = isset($_GET['status']) ? trim($_GET['status']) : '';
        $filterEquipment = isset($_GET['equipment']) ? (int)$_GET['equipment'] : 0;
        $filterUser = isset($_GET['user']) ? (int)$_GET['user'] : 0;
        
        // Apply filters
        if (!empty($filterStatus)) {
            $reservations = $this->reservationModel->getByStatus($filterStatus);
        } elseif ($filterEquipment > 0) {
            $reservations = $this->reservationModel->getByEquipment($filterEquipment);
        } elseif ($filterUser > 0) {
            $reservations = $this->reservationModel->getByUser($filterUser);
        } else {
            $reservations = $this->reservationModel->getAllWithDetails();
        }
        
        $total = count($reservations);
        $reservations = array_slice($reservations, ($page - 1) * $perPage, $perPage);
        
        $data = [
            'title' => 'Liste des Réservations',
            'reservations' => $reservations,
            'currentPage' => $page,
            'totalPages' => ceil($total / $perPage),
            'filterStatus' => $filterStatus,
            'filterEquipment' => $filterEquipment,
            'filterUser' => $filterUser
        ];
        
        return $data;
    }
    
   
    public function create() {
        $equipmentId = isset($_GET['equipment_id']) ? (int)$_GET['equipment_id'] : 0;
        
        $availableEquipment = $this->equipmentModel->getAvailable();
        
        $users = $this->userModel->getAll('nom', 'ASC');
        
        $selectedEquipment = null;
        if ($equipmentId > 0) {
            $selectedEquipment = $this->equipmentModel->getById($equipmentId);
        }
        
        $data = [
            'title' => 'Nouvelle Réservation',
            'equipment' => $availableEquipment,
            'users' => $users,
            'selectedEquipment' => $selectedEquipment
        ];
        
        require_once __DIR__ . 'views/reservation/create.php';
    }
    
   
  public function store() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
            exit;
        }

        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Vous devez être connecté pour réserver.']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        $id_equipement = (int)($input['id_equipement'] ?? 0);
        $id_utilisateur = $_SESSION['user_id']; 
        $date_debut = $input['date_debut'] ?? '';
        $date_fin = $input['date_fin'] ?? '';
        $notes = $input['notes'] ?? null;
        $forceRequest = $input['force_request'] ?? false; 

        $errors = [];
        
        if ($id_equipement <= 0) $errors[] = 'Équipement requis';
        if (empty($date_debut)) $errors[] = 'Date de début requise';
        if (empty($date_fin)) $errors[] = 'Date de fin requise';
        
        if (!empty($date_debut) && !empty($date_fin)) {
            if ($date_debut > $date_fin) {
                $errors[] = 'La date de fin doit être après la date de début';
            }
            if ($date_debut < date('Y-m-d')) {
                $errors[] = 'La date de début ne peut pas être dans le passé';
            }
        }
        
        if (!empty($errors)) {
            echo json_encode(['success' => false, 'message' => 'Erreur de validation', 'errors' => $errors]);
            exit;
        }

        
        $equipment = $this->equipmentModel->getById($id_equipement);
        if (!$equipment) {
            echo json_encode(['success' => false, 'message' => 'Équipement introuvable']);
            exit;
        }

        $availability = $this->equipmentModel->isAvailableForPeriod($id_equipement, $date_debut, $date_fin);
        
        $statutReservation = 'confirmé'; 
        $messageSuccess = 'Réservation confirmée avec succès.';
        $isOccupied = false;
        $conflictReason = "";

        if ($equipment['etat'] !== 'libre') {
            $isOccupied = true;
            $conflictReason = "L'équipement est actuellement marqué comme '" . ucfirst($equipment['etat']) . "'.";
        }
        elseif (!$availability['available']) {
            $isOccupied = true;
            $conflictReason = "Ce créneau horaire est déjà réservé par un autre membre.";
        }

        if ($isOccupied) {
            if (!$forceRequest) {
                echo json_encode([
                    'success' => false,
                    'conflict' => true, 
                    'message' => $conflictReason . "\n\nVoulez-vous envoyer une demande prioritaire à l'administrateur ?"
                ]);
                exit;
            } else {
                $statutReservation = 'en_conflit';
                $messageSuccess = 'Demande envoyée à l\'administration pour arbitrage.';
            }
        }

        $dataToSave = [
            'id_equipement' => $id_equipement,
            'id_utilisateur' => $id_utilisateur,
            'date_debut' => $date_debut,
            'date_fin' => $date_fin,
            'notes' => $notes,
            'statut' => $statutReservation
        ];

        if ($this->reservationModel->create($dataToSave)) {
            
           
            if ($statutReservation === 'confirmé') {
               
                $this->equipmentModel->updateStatus($id_equipement, 'réservé');
                $messageSuccess .= ' L\'équipement est maintenant marqué comme réservé.';
            }

            echo json_encode([
                'success' => true, 
                'message' => $messageSuccess,
                'statut' => $statutReservation
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur SQL lors de l\'enregistrement']);
        }
        exit;
    }
    
    public function view($id) {
        $reservation = $this->reservationModel->getByIdWithDetails($id);
        
        if (!$reservation) {
            $_SESSION['error'] = 'Réservation introuvable.';
            header('Location: /reservation/list');
            exit;
        }
        
        $data = [
            'title' => 'Détails de la Réservation',
            'reservation' => $reservation
        ];
        
        require_once __DIR__ . 'views/reservation/view.php';
    }
    
   
    public function edit($id) {
        $reservation = $this->reservationModel->getByIdWithDetails($id);
        
        if (!$reservation) {
            $_SESSION['error'] = 'Réservation introuvable.';
            header('Location: /reservation/list');
            exit;
        }
        
        if ($reservation['statut'] === 'terminé' || $reservation['statut'] === 'annulé') {
            $_SESSION['error'] = 'Impossible de modifier une réservation terminée ou annulée.';
            header('Location: /reservation/view/' . $id);
            exit;
        }
        
        $equipment = $this->equipmentModel->getAvailable();
        $users = $this->userModel->getAll('nom', 'ASC');
        
        $data = [
            'title' => 'Modifier la Réservation',
            'reservation' => $reservation,
            'equipment' => $equipment,
            'users' => $users
        ];
        
    }
    
  
    public function update($id) {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
            exit;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $data = [
            'id_equipement' => (int)($input['id_equipement'] ?? 0),
            'id_utilisateur' => (int)($input['id_utilisateur'] ?? 0),
            'date_debut' => $input['date_debut'] ?? '',
            'date_fin' => $input['date_fin'] ?? '',
            'statut' => $input['statut'] ?? 'en_attente',
            'notes' => $input['notes'] ?? null
        ];
        
        if (empty($data['date_debut']) || empty($data['date_fin'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Dates requises'
            ]);
            exit;
        }
        
        if ($data['date_debut'] > $data['date_fin']) {
            echo json_encode([
                'success' => false,
                'message' => 'Date de fin invalide'
            ]);
            exit;
        }
        
        if ($this->reservationModel->hasConflict(
            $data['id_equipement'],
            $data['date_debut'],
            $data['date_fin'],
            $id
        )) {
            $conflicts = $this->reservationModel->getConflicts(
                $data['id_equipement'],
                $data['date_debut'],
                $data['date_fin'],
                $id
            );
            
            echo json_encode([
                'success' => false,
                'message' => 'Conflit de réservation',
                'conflicts' => $conflicts
            ]);
            exit;
        }
        
        if ($this->reservationModel->update($id, $data)) {
            echo json_encode([
                'success' => true,
                'message' => 'Réservation mise à jour avec succès'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour'
            ]);
        }
        exit;
    }
    
   
    public function confirm($id) {
        header('Content-Type: application/json');
        
        if ($this->reservationModel->confirm($id)) {
            $reservation = $this->reservationModel->getById($id);
            if ($reservation) {
                $this->equipmentModel->updateStatus($reservation['id_equipement'], 'réserve');
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Réservation confirmé'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors de la confirmation'
            ]);
        }
        exit;
    }
    
   
    public function cancel($id) {
        header('Content-Type: application/json');
        
        if ($this->equipmentModel->cancelReservation($id)) {
            echo json_encode([
                'success' => true,
                'message' => 'Réservation annulée avec succès'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors de l\'annulation'
            ]);
        }
        exit;
    }
  
    public function delete($id) {
        header('Content-Type: application/json');
        
        $reservation = $this->reservationModel->getById($id);
        
        if (!$reservation) {
            echo json_encode([
                'success' => false,
                'message' => 'Réservation introuvable'
            ]);
            exit;
        }
        
        if (!in_array($reservation['statut'], ['annulée', 'en_attente'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Seules les réservations annulées ou en attente peuvent être supprimées'
            ]);
            exit;
        }
        
        if ($this->reservationModel->delete($id)) {
            echo json_encode([
                'success' => true,
                'message' => 'Réservation supprimée'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors de la suppression'
            ]);
        }
        exit;
    }
    
  
    public function getByEquipment() {
        header('Content-Type: application/json');
        
        $id = (int)($_GET['id'] ?? 0);
        
        if (!$id) {
            echo json_encode([
                'success' => false,
                'message' => 'ID requis'
            ]);
            exit;
        }
        
        $reservations = $this->reservationModel->getByEquipment($id);
        
        echo json_encode([
            'success' => true,
            'data' => $reservations
        ]);
        exit;
    }
    
  
  
    public function getByUser() {
        header('Content-Type: application/json');
        
        $id = (int)($_GET['id'] ?? 0);
        
        if (!$id) {
            echo json_encode([
                'success' => false,
                'message' => 'ID requis'
            ]);
            exit;
        }
        
        $reservations = $this->reservationModel->getByUser($id);
        
        echo json_encode([
            'success' => true,
            'data' => $reservations
        ]);
        exit;
    }
    
 
    public function checkConflicts() {
        header('Content-Type: application/json');
        
        $id_equipement = (int)($_GET['id_equipement'] ?? 0);
        $date_debut = $_GET['date_debut'] ?? '';
        $date_fin = $_GET['date_fin'] ?? '';
        $exclude_id = isset($_GET['exclude_id']) ? (int)$_GET['exclude_id'] : null;
        
        if (!$id_equipement || !$date_debut || !$date_fin) {
            echo json_encode([
                'success' => false,
                'message' => 'Paramètres manquants'
            ]);
            exit;
        }
        
        $hasConflict = $this->reservationModel->hasConflict(
            $id_equipement,
            $date_debut,
            $date_fin,
            $exclude_id
        );
        
        $conflicts = [];
        if ($hasConflict) {
            $conflicts = $this->reservationModel->getConflicts(
                $id_equipement,
                $date_debut,
                $date_fin,
                $exclude_id
            );
        }
        
        echo json_encode([
            'success' => true,
            'hasConflict' => $hasConflict,
            'conflicts' => $conflicts
        ]);
        exit;
    }
    
   
    public function getCurrent() {
        header('Content-Type: application/json');
        
        $current = $this->reservationModel->getCurrent();
        
        echo json_encode([
            'success' => true,
            'data' => $current
        ]);
        exit;
    }
 
    public function getUpcoming() {
        header('Content-Type: application/json');
        
        $days = isset($_GET['days']) ? (int)$_GET['days'] : 7;
        $upcoming = $this->reservationModel->getUpcoming($days);
        
        echo json_encode([
            'success' => true,
            'data' => $upcoming
        ]);
        exit;
    }
    
   
    public function getStats() {
        header('Content-Type: application/json');
        
        $stats = $this->reservationModel->getStats();
        
        echo json_encode([
            'success' => true,
            'data' => $stats
        ]);
        exit;
    }
    
   
    public function updateStatus() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode([
                'success' => false,
                'message' => 'Méthode non autorisée'
            ]);
            exit;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $id = (int)($input['id'] ?? 0);
        $statut = $input['statut'] ?? '';
        
        if (!in_array($statut, ['en_attente', 'confirmé', 'annulé'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Statut invalide'
            ]);
            exit;
        }
        
        if ($this->reservationModel->updateStatus($id, $statut)) {
            $reservation = $this->reservationModel->getById($id);
            if ($reservation) {
                if ($statut === 'confirmé' ) {
                    $this->equipmentModel->updateStatus($reservation['id_equipement'], 'réservé');
                } elseif ($statut === 'annulé') {
                    $others = $this->reservationModel->getByEquipment($reservation['id_equipement']);
                    $hasOthers = false;
                    foreach ($others as $other) {
                        if ($other['id'] != $id && in_array($other['statut'], ['confirmé'])) {
                            $hasOthers = true;
                            break;
                        }
                    }
                    if (!$hasOthers) {
                        $this->equipmentModel->updateStatus($reservation['id_equipement'], 'libre');
                    }
                }
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Statut mis à jour'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour'
            ]);
        }
        exit;
    }
  
    public function getReportStats() {
        $startDate = $_GET['start'] ?? date('Y-m-01'); 
        $endDate = $_GET['end'] ?? date('Y-m-t');     

        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        $interval = $start->diff($end);
        $totalDaysInPeriod = $interval->days + 1;

        $occupancyData = $this->reservationModel->getEquipmentOccupancyStats($startDate, $endDate);
        $userData = $this->reservationModel->getUserRequestStats($startDate, $endDate);

        foreach ($occupancyData as &$item) {
            $percent = ($item['jours_occupes'] / $totalDaysInPeriod) * 100;
            $item['taux_occupation'] = round($percent, 2);
        }

        return [
            'success' => true,
            'data' => [
                'occupancy' => $occupancyData,
                'users' => $userData,
                'period_days' => $totalDaysInPeriod
            ]
        ];
    }

   
    public function downloadReport() {
        require_once __DIR__ . '/../libs/PDFReport.php';
        
        $startDate = $_POST['date_debut'] ?? date('Y-m-01');
        $endDate = $_POST['date_fin'] ?? date('Y-m-t');

        $occupancyData = $this->reservationModel->getEquipmentOccupancyStats($startDate, $endDate);
        $userData = $this->reservationModel->getUserRequestStats($startDate, $endDate);

        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        $daysInPeriod = $start->diff($end)->days + 1;

        $pdf = new PDFReport();
        $pdf->AliasNbPages();
        $pdf->AddPage();
        $pdf->setReportTitle("Rapport d'Utilisation des Équipements");
        $pdf->setFilterInfo("Période : $startDate au $endDate");

        $pdf->SetFont('Arial','B',12);
        $pdf->Cell(0,10, $pdf->convert('1. Taux d\'Occupation par Équipement'),0,1);
        
        $headerEquip = ['Équipement', 'Nb Réservations', 'Jours Occupés', 'Taux (%)'];
        $dataEquip = [];
        foreach($occupancyData as $row) {
            $rate = round(($row['jours_occupes'] / $daysInPeriod) * 100, 1);
            $dataEquip[] = [
                $row['equipement_nom'],
                $row['total_reservations'],
                $row['jours_occupes'],
                $rate . ' %'
            ];
        }
        $pdf->EquipmentReportTable($headerEquip, $dataEquip, [80, 35, 35, 30]);

        $pdf->SetFont('Arial','B',12);
        $pdf->Cell(0,10, $pdf->convert('2. Activité par Utilisateur'),0,1);

        $headerUser = ['Utilisateur', 'Total', 'Approuvées', 'Annulées'];
        $dataUser = [];
        foreach($userData as $row) {
            $dataUser[] = [
                $row['nom'] . ' ' . $row['prenom'],
                $row['total_demandes'],
                $row['approuvees'],
                $row['annulees']
            ];
        }
        $pdf->EquipmentReportTable($headerUser, $dataUser, [80, 35, 35, 30]);

        $pdf->Output('D', 'Rapport_Equipements.pdf');
        exit;
    }
   
    public function resolveConflict() {
        // requireAdmin(); 
        header('Content-Type: application/json');
        
        $input = json_decode(file_get_contents('php://input'), true);
        $reservationId = $input['id'];
        $decision = $input['decision']; 

        if ($decision === 'reject') {
            if ($this->reservationModel->updateStatus($reservationId, 'annulé')) {
                echo json_encode(['success' => true, 'message' => 'Demande rejetée avec succès.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erreur lors du rejet.']);
            }
        } 
        elseif ($decision === 'accept') {
            $res = $this->reservationModel->getById($reservationId);
            
            if (!$res) {
                echo json_encode(['success' => false, 'message' => 'Réservation introuvable.']);
                exit;
            }

            $conflicts = $this->reservationModel->getConflicts(
                $res['id_equipement'], 
                $res['date_debut'], 
                $res['date_fin'], 
                $reservationId 
            );

            foreach($conflicts as $c) {
                $this->reservationModel->updateStatus($c['id'], 'annulé');
            }

            $this->reservationModel->updateStatus($reservationId, 'confirmé');
            
            $this->equipmentModel->updateStatus($res['id_equipement'], 'réservé');
            
            echo json_encode(['success' => true, 'message' => 'Conflit résolu : Nouvelle demande acceptée, anciennes annulées.']);
        }
        exit;
    }
  
    public function getConflictReservations() {
        // requireAdmin(); 

        $conflicts = $this->reservationModel->getByStatus('en_conflit');
        
        

        echo json_encode([
            'success' => true,
            'data' => $conflicts
        ]);
        exit;
    }
}
?>