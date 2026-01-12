<?php
session_start();

// Contrôleurs existants
require_once __DIR__ . '/UserController.php';
require_once __DIR__ . '/TeamController.php';
require_once __DIR__ . '/ProjectController.php';
require_once __DIR__ . '/equipementController.php';
require_once __DIR__ . '/../models/equipementModel.php';
require_once __DIR__ . '/../models/equipementType.php';
require_once __DIR__ . '/SettingsControllers.php';
require_once __DIR__ . '/OpportunityController.php';
require_once __DIR__ . '/PartnerController.php';



// NOUVEAU : Réservations
require_once __DIR__ . '/../controllers/reservationController.php';
require_once __DIR__ . '/../models/reservationModel.php';

// Publications & Events
require_once __DIR__ . '/../models/Publications.php';
require_once __DIR__ . '/../controllers/PublicationController.php';
require_once __DIR__ . '/../controllers/EventController.php';
require_once __DIR__ . '/../models/Event.php';
require_once __DIR__ . '/../controllers/EventTypeController.php';
require_once __DIR__ . '/../models/EventType.php';
require_once __DIR__. '/../controllers/memberController.php';
// ============================================
// FONCTIONS HELPERS ULTRA-GÉNÉRIQUES
// ============================================
$memberController = new MemberController();

function sendJson($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function sendError($message, $statusCode = 400) {
    sendJson(['success' => false, 'message' => $message], $statusCode);
}

function sendSuccess($data = null, $message = null) {
    $response = ['success' => true];
    if ($message) $response['message'] = $message;
    if ($data !== null) $response['data'] = $data;
    sendJson($response);
}

function getJsonInput() {
    $jsonData = file_get_contents('php://input');
    $data = json_decode($jsonData, true);
    if (!$data) {
        // Fallback si le body est vide mais qu'on a des données POST classiques
        if (!empty($_POST)) return $_POST;
        sendError('Données JSON invalides');
    }
    return $data;
}

function getParam($name, $source = 'GET', $default = null, $required = false) {
    $data = $source === 'GET' ? $_GET : $_POST;
    $value = $data[$name] ?? $default;
    
    if ($required && ($value === null || $value === '')) {
        sendError(ucfirst($name) . ' est requis');
    }
    
    return $value;
}

function requireParams(array $params, $source = 'GET') {
    $values = [];
    foreach ($params as $param) {
        $values[$param] = getParam($param, $source, null, true);
    }
    return $values;
}

function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        sendError('Non autorisé', 401);
    }
}

function requireAdmin() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    
    // On vérifie le flag booléen
    if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
        // Redirection ou Erreur JSON
        header('HTTP/1.1 403 Forbidden');
        echo json_encode(['success' => false, 'message' => 'Accès réservé aux administrateurs']);
        exit;
    }
}
function requireId($name = 'id') {
    if (isset($_GET[$name])) {
        $id = $_GET[$name];
    } elseif (isset($_POST[$name])) {
        $id = $_POST[$name];
    } else {
        static $json = null;
        if ($json === null) {
            $json = json_decode(file_get_contents('php://input'), true);
        }
        $id = $json[$name] ?? null;
    }

    if (!is_numeric($id) || $id <= 0) {
        sendError("$name invalide ou manquant");
    }

    return (int)$id;
}

function validate($data, $rules) {
    $errors = [];
    foreach ($rules as $field => $rule) {
        $value = $data[$field] ?? null;
        if (isset($rule['required']) && $rule['required'] && empty($value)) {
            $errors[] = ($rule['label'] ?? $field) . ' est requis';
            continue;
        }
        if (isset($rule['min']) && strlen($value) < $rule['min']) {
            $errors[] = ($rule['label'] ?? $field) . " doit contenir au moins {$rule['min']} caractères";
        }
        if (isset($rule['max']) && strlen($value) > $rule['max']) {
            $errors[] = ($rule['label'] ?? $field) . " ne peut pas dépasser {$rule['max']} caractères";
        }
        if (isset($rule['in']) && !in_array($value, $rule['in'])) {
            $errors[] = ($rule['label'] ?? $field) . ' invalide';
        }
        if (isset($rule['positive']) && $rule['positive'] && (!is_numeric($value) || $value <= 0)) {
            $errors[] = ($rule['label'] ?? $field) . ' doit être un nombre positif';
        }
    }
    return empty($errors) ? true : $errors;
}

function executeController($callback) {
    try {
        $result = call_user_func($callback);
        if (is_array($result)) {
            sendJson($result);
        }
    } catch (Exception $e) {
        sendError('Erreur serveur: ' . $e->getMessage(), 500);
    }
}

function simpleAction($controller, $method, $idParam = 'id') {
    $id = requireId($idParam);
    executeController(function() use ($controller, $method, $id) {
        return $controller->$method($id);
    });
}

function crudAction($action, $controller, $id = null) {
    switch ($action) {
        case 'get':
            if ($id) {
                simpleAction($controller, 'show', 'id');
            } else {
                executeController(fn() => $controller->index());
            }
            break;
        case 'create':
            $data = getJsonInput();
            executeController(fn() => $controller->create($data));
            break;
        case 'update':
            $id = requireId();
            $data = getJsonInput();
            executeController(fn() => $controller->update($id, $data));
            break;
        case 'delete':
            $id = requireId();
            executeController(fn() => $controller->delete($id));
            break;
    }
}

// ============================================
// INSTANCIATION DES CONTRÔLEURS ET MODÈLES
// ============================================

$controllers = [
    'user' => new UserController(),
    'team' => new TeamController(),
    'project' => new ProjectController(),
    'equipment' => new EquipmentController(),
    'reservation' => new ReservationController(), // Ajouté
    'publication' => new PublicationController(),
    'event' => new EventController(),
    'eventType' => new EventTypeController(),
    'settings'=> new SettingsController(),
    'partner'=> new PartnerController(),
    'opportunity'=> new OpportunityController(),
];

$models = [
    'equipment' => new Equipment(),
    'equipmentType' => new EquipmentType(),
    'publication' => new Publication()
];

$action = getParam('action', 'GET', '');
$id = getParam('id');
$teamid = getParam('teamid');

// ============================================
// ROUTES UTILISATEURS
// ============================================

if ($action === 'activateUser' && $id) {
    $controllers['user']->activate($id);
}
elseif ($action === 'getUsers') {
    $controllers['user']->getUsers();
}
elseif ($action === 'deleteUser' && $id) {
    $controllers['user']->delete($id);
} 
elseif ($action === 'suspendUser' && $id) {
    $controllers['user']->suspend($id);
} 
elseif ($action === 'createUser') {
    $controllers['user']->create();
} 
elseif ($action === 'updatePhoto' && $id) {
    $controllers['user']->updatePhoto($id);
} 
elseif ($action === 'getUser' && $id) {
    $controllers['user']->getUser($id);
} 
elseif ($action === 'updateUser' && $id) {
    $controllers['user']->updateUser($id);
    sendSuccess(null, 'Utilisateur mis à jour');
} 

// ============================================
// ROUTES ÉQUIPES
// ============================================

elseif ($action === 'createTeam') {
    executeController(function() use ($controllers) {
        return $controllers['team']->create();
    });
} 
elseif ($action === 'updateTeam' && $teamid) {
    executeController(function() use ($controllers, $teamid) {
        return $controllers['team']->edit($teamid);
    });             
} 
elseif ($action === 'deleteTeam' && $teamid) {
    executeController(function() use ($controllers, $teamid) {
    return $controllers['team']->delete($teamid);
    });
} 
elseif ($action === 'addMember') {
    $teamid=requireId('teamid');
    executeController(function() use ($controllers, $teamid) {
        $user_id = requireId('user_id');
        return $controllers['team']->addMember($teamid, $user_id);
    });
}
elseif ($action === 'getTeamWithDetails' && $teamid) {
    executeController(function() use ($controllers, $teamid) {
        $team = $controllers['team']->getall($teamid);
        if ($team) {
            $team['members'] = $controllers['team']->getTeamMembers($teamid);
            return ['success' => true, 'team' => $team];
        }
        return ['success' => false, 'message' => 'Équipe introuvable'];
    });
}
elseif ($action === 'getTeam' && $teamid) {
    executeController(function() use ($controllers, $teamid) {
        $team = $controllers['team']->getall($teamid);
        return $team 
            ? ['success' => true, 'team' => $team]
            : ['success' => false, 'message' => 'Équipe introuvable'];
    });
}
elseif ($action === 'getTeams') {
    executeController(function() use ($controllers) {
        return ['success' => true, 'teams' => $controllers['team']->getAllTeamsWithDetails()];
    });
}
elseif ($action === 'getAvailableUsers' && $teamid) {
    executeController(function() use ($controllers, $teamid) {
        return ['success' => true, 'users' => $controllers['team']->getAvailableUsers($teamid)];
    });
}
elseif ($action === 'removeMember' && $teamid) {
    $user_id = requireId('user_id');
    $controllers['team']->removeMember($teamid, $user_id);
} 

// ============================================
// ROUTES PROJETS
// ============================================

elseif ($action === 'createProject') {
    crudAction('create', $controllers['project']);
}
elseif ($action === 'updateProject' && $id) {
    crudAction('update', $controllers['project'], $id);
}
elseif ($action === 'deleteProject' && $id) {
    crudAction('delete', $controllers['project'], $id);
}
elseif ($action === 'getProject' && $id) {
    simpleAction($controllers['project'], 'show');
}
elseif ($action === 'getProjects') {
    executeController(fn() => $controllers['project']->index());
}
elseif ($action === 'getProjectsWithUsers') {
    executeController(fn() => $controllers['project']->indexWithUsers());
}
elseif ($action === 'countActiveProjects') {
    executeController(fn() => $controllers['project']->countActive());
}
elseif ($action === 'searchProjects') {
    $keyword = getParam('keyword', 'GET', '');
    executeController(fn() => $controllers['project']->search($keyword));
}
elseif ($action === 'getProjectsByStatut') {
    $statut = getParam('statut', 'GET', '', true);
    executeController(fn() => $controllers['project']->getByStatut($statut));
}
elseif ($action === 'getProjectsByResponsable') {
    $responsable_id = requireId('responsable_id');
    executeController(fn() => $controllers['project']->getByResponsable($responsable_id));
}
elseif ($action === 'getProjectsByEquipe') {
    $id_equipe = requireId('id_equipe');
    executeController(fn() => $controllers['project']->getByEquipe($id_equipe));
}
elseif ($action === 'addUserToProject') {
    $params = requireParams(['project_id', 'user_id']);
    executeController(fn() => $controllers['project']->addUser($params['project_id'], $params['user_id']));
}
elseif ($action === 'removeUserFromProject') {
    $params = requireParams(['project_id', 'user_id']);
    executeController(fn() => $controllers['project']->removeUser($params['project_id'], $params['user_id']));
}
elseif ($action === 'getProjectUsers') {
    $project_id = requireId('project_id');
    executeController(fn() => $controllers['project']->getProjectUsers($project_id));
}
elseif ($action === 'addPublicationToProject') {
    $params = requireParams(['project_id', 'publication_id']);
    executeController(fn() => $controllers['project']->addPublication($params['project_id'], $params['publication_id']));
}
elseif ($action === 'addThematicToProject') {
    $params = requireParams(['project_id', 'thematic_id']);
    executeController(fn() => $controllers['project']->addThematic($params['project_id'], $params['thematic_id']));
}
elseif ($action === 'generateProjectReport') {
   
    $controllers['project']->generatePDF();
}

// ============================================
// ROUTES ÉQUIPEMENTS
// ============================================

elseif ($action === 'createEquipment') {
    $data = getJsonInput();
    // Validation déjà faite dans EquipmentController::store, mais pour l'API pure:
    $validation = validate($data, [
        'nom' => ['required' => true, 'label' => 'Le nom'],
        'id_type' => ['required' => true, 'positive' => true, 'label' => 'Le type']
    ]);
    if ($validation !== true) sendJson(['success' => false, 'errors' => $validation]);
    
    // Si vous utilisez la méthode du contrôleur qui attend $_POST, assurez-vous de l'adapter.
    // Ici on appelle le modèle directement pour l'API pure comme dans l'ancien code, 
    // ou la méthode create du controller si elle est adaptée API.
    sendJson($models['equipment']->create($data)
        ? ['success' => true, 'message' => 'Équipement créé avec succès']
        : ['success' => false, 'message' => 'Erreur lors de la création']);
}
elseif ($action === 'updateEquipment' && $id) {
    $data = getJsonInput();
    $validation = validate($data, [
        'nom' => ['required' => true, 'label' => 'Le nom'],
        'id_type' => ['required' => true, 'positive' => true, 'label' => 'Le type']
    ]);
    if ($validation !== true) sendJson(['success' => false, 'errors' => $validation]);
    
    sendJson($models['equipment']->update($id, $data)
        ? ['success' => true, 'message' => 'Équipement mis à jour avec succès']
        : ['success' => false, 'message' => 'Erreur lors de la mise à jour']);
}
elseif ($action === 'deleteEquipment' && $id) {
    sendJson($models['equipment']->delete($id)
        ? ['success' => true, 'message' => 'Équipement supprimé avec succès']
        : ['success' => false, 'message' => 'Erreur lors de la suppression']);
}
elseif ($action === 'getEquipment' && $id) {
    $equipment = $models['equipment']->getById($id);
    sendJson($equipment 
        ? ['success' => true, 'data' => $equipment]
        : ['success' => false, 'message' => 'Équipement introuvable']);
}
elseif ($action === 'getEquipments') {
    sendSuccess($models['equipment']->getAllWithTypes('id', 'DESC'));
}
elseif ($action === 'updateEquipmentStatus') {
    $data = getJsonInput();
    $equipmentId = $data['id'] ?? $id; // Peut venir du body ou de l'URL
    $etat = $data['etat'] ?? null;
    
    if (!$etat || !in_array($etat, ['libre', 'réserve', 'en_maintenance'])) {
        sendError('Statut invalide');
    }
    
    // Appel via le controller pour gérer la logique métier (vérif réservations actives)
    // Astuce : On simule les données POST pour le contrôleur s'il les lit via $_POST
    $_POST['id'] = $equipmentId;
    $_POST['etat'] = $etat;
    executeController(fn() => $controllers['equipment']->updateStatus());
}
elseif ($action === 'searchEquipments') {
    $keyword = getParam('keyword', 'GET', '', true);
    if (strlen($keyword) < 2) sendError('Mot-clé trop court');
    sendSuccess($models['equipment']->search($keyword));
}
elseif ($action === 'getEquipmentsByType') {
    $typeId = requireId('type_id');
    sendSuccess($models['equipment']->getByType($typeId));
}
elseif ($action === 'getEquipmentStats') {
    sendJson([
        'success' => true, 
        'statusStats' => $models['equipment']->getStatsByStatus(),
        'typeStats' => $models['equipment']->getStatsByType()
    ]);
}

// ============================================
// ROUTES TYPES D'ÉQUIPEMENTS
// ============================================

elseif ($action === 'createEquipmentType') {
    $data = getJsonInput();
    if (empty($data['nom'])) sendError('Le nom est requis');
    if ($models['equipmentType']->nameExists($data['nom'])) sendError('Ce nom existe déjà', 409);
    
    sendJson($models['equipmentType']->create($data)
        ? ['success' => true, 'message' => 'Type créé avec succès']
        : ['success' => false, 'message' => 'Erreur lors de la création']);
}
elseif ($action === 'updateEquipmentType' && $id) {
    $data = getJsonInput();
    if (empty($data['nom'])) sendError('Le nom est requis');
    
    sendJson($models['equipmentType']->update($id, $data)
        ? ['success' => true, 'message' => 'Type mis à jour avec succès']
        : ['success' => false, 'message' => 'Erreur lors de la mise à jour']);
}
elseif ($action === 'deleteEquipmentType' && $id) {
    if (!$models['equipmentType']->canDelete($id)) sendError('Type utilisé par des équipements', 409);
    
    sendJson($models['equipmentType']->delete($id)
        ? ['success' => true, 'message' => 'Type supprimé']
        : ['success' => false, 'message' => 'Erreur suppression']);
}
elseif ($action === 'getEquipmentTypes') {
    sendSuccess($models['equipmentType']->getAllWithCounts('nom', 'ASC'));
}

// ============================================
// ROUTES RÉSERVATIONS (NOUVEAU)
// ============================================

elseif ($action === 'createReservation') {
    // requireAuth();
    executeController(fn() => $controllers['reservation']->store());
}
elseif ($action === 'cancelReservation' && $id) {
    requireAuth();
    executeController(fn() => $controllers['reservation']->cancel($id));
}
elseif ($action === 'confirmReservation' && $id) {
   // requireAdmin();
    executeController(fn() => $controllers['reservation']->confirm($id));
}
elseif ($action === 'updateReservation' && $id) {
    requireAuth();
    executeController(fn() => $controllers['reservation']->update($id));
}
elseif ($action === 'deleteReservation' && $id) {
   // requireAdmin();
    executeController(fn() => $controllers['reservation']->delete($id));
}
elseif ($action === 'getEquipmentReservations') {
    // Le JS envoie ?action=getEquipmentReservations&id=XXX
    executeController(fn() => $controllers['reservation']->getByEquipment());
}
elseif ($action === 'getUserReservations') {
    executeController(fn() => $controllers['reservation']->getByUser());
}
elseif ($action === 'checkConflicts') {
    executeController(fn() => $controllers['reservation']->checkConflicts());
}
elseif ($action === 'getReservationStats') {
    executeController(fn() => $controllers['reservation']->getStats());
}
elseif ($action === 'getAllReservations') {
    executeController(fn() => $controllers['reservation']->list());
}elseif ($action === 'getConflictReservations') {
    // Appel à la nouvelle méthode
    executeController(fn() => $controllers['reservation']->getConflictReservations());
}
elseif ($action === 'resolveConflict') {
    // Pour l'action "Accepter/Rejeter"
    executeController(fn() => $controllers['reservation']->resolveConflict());
}

// ============================================
// ROUTES PUBLICATIONS
// ============================================

elseif ($action === 'getPublications') {
    executeController(fn() => $controllers['publication']->apiGetPublications());
}
elseif ($action === 'getPublication') {
    $id = requireId();
    executeController(fn() => $controllers['publication']->apiGetPublicationById($id));
}
elseif ($action === 'createPublication') {
    requireAuth();
    executeController(fn() => $controllers['publication']->apiCreatePublication($_POST, $_FILES));
}
elseif ($action === 'updatePublication') {
    requireAuth();
    $id = requireId();
    executeController(fn() => $controllers['publication']->apiUpdatePublication($id, $_POST, $_FILES));
}
elseif ($action === 'validatePublication') {
   // requireAdmin();
    $id = requireId();
    executeController(fn() => $controllers['publication']->apiValidatePublication($id));
}
elseif ($action === 'rejectPublication') {
   // requireAdmin();
    $id = requireId();
    executeController(fn() => $controllers['publication']->apiRejectPublication($id));
}
elseif ($action === 'deletePublication') {
   // requireAdmin();
    $id = requireId();
    executeController(fn() => $controllers['publication']->apiDeletePublication($id));
}
elseif ($action === 'getPublicationStats') {
    executeController(function() use ($controllers) {
        $result = $controllers['publication']->stats();
        if (is_array($result) && !isset($result['success'])) {
            $result = array_merge(['success' => true], $result);
        }
        return $result;
    });
}
elseif ($action === 'searchPublications') {
    $keyword = getParam('q', 'GET', '');
    $limit = getParam('limit', 'GET', 20);
    executeController(fn() => $controllers['publication']->apiSearchPublications($keyword, $limit));
}
elseif ($action === 'getPublicationsByDomain') {
    executeController(fn() => $controllers['publication']->apiGetDomains());
}
elseif ($action === 'getDistinctYears') {
    executeController(fn() => $controllers['publication']->apiGetYears());
}
elseif ($action === 'getMyPublications') {
    requireAuth();
    executeController(fn() => $controllers['publication']->apiGetMyPublications($_SESSION['user_id']));
}
elseif ($action === 'getRecentPublications') {
    $limit = getParam('limit', 'GET', 5);
    executeController(fn() => $controllers['publication']->apiGetRecentPublications($limit));
}
elseif ($action === 'getPendingPublications') {
   // requireAdmin();
    executeController(fn() => $controllers['publication']->apiGetPendingPublications());
}
elseif ($action === 'downloadPublication') {
    $id = requireId();
    $controllers['publication']->download($id);
    exit;
}
elseif ($action === 'getPublicationsByType') {
    $type = getParam('type', 'GET', '', true);
    $limit = getParam('limit', 'GET', null);
    executeController(fn() => $controllers['publication']->apiGetPublicationsByType($type, $limit));
}
elseif ($action === 'generateReport') {
    $controllers['publication']->generateReport();
}
// ============================================
// ROUTES ÉVÉNEMENTS
// ============================================

elseif ($action === 'getEvents') {
    executeController(fn() => $controllers['event']->apiGetAll());
}
elseif ($action === 'getEvent') {
    $id = requireId();
    executeController(fn() => $controllers['event']->apiGetById($id));
}
elseif ($action === 'createEvent') {
    // requireAuth();
    executeController(fn() => $controllers['event']->apiCreate($_POST, $_FILES));
}
elseif ($action === 'updateEvent') {
    // requireAuth();
    $id = requireId();
    executeController(fn() => $controllers['event']->apiUpdate($id, $_POST, $_FILES));
}
elseif ($action === 'deleteEvent') {
    //// requireAdmin();     
    $id = requireId();
    executeController(fn() => $controllers['event']->apiDelete($id));
}
elseif ($action ==='getEventStats') {
    $limit = getParam('limit', 'GET', 5);
    executeController(fn() => $controllers['event']->apiGetStatistics());
}
elseif ($action === 'getEventTypes') {
    executeController(fn() => $controllers['eventType']->index());
}
elseif ($action === 'createEventType') {
    $data = getJsonInput();
    executeController(fn() => $controllers['eventType']->create($data));
}
elseif ($action === 'deleteEventType') {
    $id = requireId();
    executeController(fn() => $controllers['eventType']->delete($id));
}elseif ($action === 'registerParticipant') {
    // requireAuth();
    executeController(fn() => $controllers['event']->apiRegisterParticipant());
}
elseif ($action === 'unregisterParticipant') {
    // requireAuth();
    executeController(fn() => $controllers['event']->apiUnregisterParticipant());
}
elseif ($action === 'getEventParticipants' && $id) {
    executeController(fn() => $controllers['event']->apiGetParticipants($id));
}
// Système d'alerte
elseif ($action === 'sendEventReminders') {
    // Peut être appelé par un Cron Job ou manuellement par l'admin
    executeController(fn() => $controllers['event']->apiSendReminders());
}

// ============================================
// ROUTES ÉQUIPES DÉTAILS
// ============================================

elseif ($action === 'getAvailaibleForTeam' && $teamid) {
    executeController(function() use ($controllers, $teamid) {
        return ['success' => true, 'equipments' => $controllers['team']->getAvailableForTeam($teamid)];
    });
}
elseif ($action==='assignEquipment') {
    $team_id = requireId('team_id');
    $equipment_id = requireId('equipment_id');
    executeController(fn() => $controllers['team']->assignEquipment($team_id, $equipment_id));
}
elseif ($action==='removeEquipment') {
    $team_id = requireId('team_id');
    $equipment_id = requireId('equipment_id');
    executeController(fn() => $controllers['team']->removeEquipment($team_id,  $equipment_id));
}

elseif ($action === 'getAvailableUsersForProject' && isset($_GET['project_id'])) {
    $project_id = requireId('project_id');
    try {
        require_once __DIR__ . '/../models/UserModel.php';
        $userModel = new UserModel();
        $allUsers = $userModel->getAll();
        $membersData = $controllers['project']->getProjectUsers($project_id);
        $currentMembers = $membersData['data'] ?? [];
        $memberIds = array_column($currentMembers, 'id');
        $availableUsers = array_filter($allUsers, function($user) use ($memberIds) {
            return !in_array($user['id'], $memberIds);
        });
        sendJson([
            'success' => true,
            'users' => array_values($availableUsers)
        ]);
    } catch (Exception $e) {
        sendError('Erreur: ' . $e->getMessage(), 500);
    }
}elseif ($action === 'getReportStats') {
    executeController(fn() => $controllers['reservation']->getReportStats());
}
elseif ($action === 'downloadReservationReport') {
    // Note: Utiliser POST pour le téléchargement PDF
    $controllers['reservation']->downloadReport(); 
}
// --- ROUTES PARAMÈTRES ---
elseif ($action === 'getSettings') {
    executeController(fn() => $controllers['settings']->apiGetSettings());
}
elseif ($action === 'updateSettings') {
   // requireAdmin();
    executeController(fn() => $controllers['settings']->updateConfig($_POST, $_FILES));
}
elseif ($action === 'downloadBackup') {
   // requireAdmin();
    $controllers['settings']->downloadBackup(); 
}
elseif ($action === 'restoreBackup') {
   // requireAdmin();
    executeController(fn() => $controllers['settings']->restoreBackup($_FILES));
}elseif ($action === 'updateProfile') {
    requireAuth(); // Vérifie juste si connecté
    executeController(fn() => $memberController->apiUpdateProfile($_POST, $_FILES));
}// 5. Récupérer la liste des menus (JSON pour le tableau JS)
elseif ($action === 'getMenu') {
    // Accessible à tous ou admin seulement selon votre besoin. 
    // Ici on laisse ouvert pour que le Sidebar puisse aussi l'utiliser si besoin.
    executeController(fn() => $controllers['settings']->apiGetMenu());
}

// 6. Mettre à jour le menu (Sauvegarde du tableau JS)
elseif ($action === 'updateMenu') {
    // requireAdmin();
    // Note : Les données JSON sont lues via php://input dans le contrôleur
    executeController(fn() => $controllers['settings']->apiUpdateMenu());
}
// ...

// ============================================
// ROUTES OPPORTUNITÉS
// ============================================

require_once __DIR__ . '/OpportunityController.php';
$controllers['opportunity'] = new OpportunityController();

if ($action === 'getOpportunities') {
    executeController(fn() => $controllers['opportunity']->apiGetAll());
}
elseif ($action === 'getOpportunity' && $id) {
    executeController(fn() => $controllers['opportunity']->apiGetById($id));
}
elseif ($action === 'createOpportunity') {
    $data = getJsonInput();
    executeController(fn() => $controllers['opportunity']->apiCreate($data));
}
elseif ($action === 'updateOpportunity' && $id) {
    $data = getJsonInput();
    executeController(fn() => $controllers['opportunity']->apiUpdate($id, $data));
}
elseif ($action === 'deleteOpportunity' && $id) {
    executeController(fn() => $controllers['opportunity']->apiDelete($id));
}
// ROUTES PARTENAIRES
if ($action === 'getPartners') {
    executeController(fn() => $controllers['partner']->apiGetAll());
}
elseif ($action === 'createPartner') {
    // Note: Utilisation de $_POST et $_FILES pour l'upload
    requireAuth(); // Seulement connecté (ou requireAdmin)
    executeController(fn() => $controllers['partner']->apiCreate($_POST, $_FILES));
}
elseif ($action === 'updatePartner' && $id) {
    requireAuth();
    executeController(fn() => $controllers['partner']->apiUpdate($id, $_POST, $_FILES));
}
elseif ($action === 'deletePartner' && $id) {
    requireAuth();
    executeController(fn() => $controllers['partner']->apiDelete($id));
}

// ============================================
// ROUTE PAR DÉFAUT
// ============================================

else {
    sendError('Action invalide', 404);
}
?>