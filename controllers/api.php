<?php
session_start();

require_once 'UserController.php';
require_once 'TeamController.php';
require_once 'ProjectController.php';
require_once 'equipementController.php';
require_once '../models/equipementModel.php';
require_once '../models/equipementType.php';
require_once '../models/Publications.php';
require_once '../controllers/PublicationController.php';

// ============================================
// FONCTIONS HELPERS ULTRA-GÉNÉRIQUES
// ============================================

/**
 * Envoyer une réponse JSON avec code HTTP
 */
function sendJson($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Envoyer une réponse d'erreur
 */
function sendError($message, $statusCode = 400) {
    sendJson(['success' => false, 'message' => $message], $statusCode);
}

/**
 * Envoyer une réponse de succès
 */
function sendSuccess($data = null, $message = null) {
    $response = ['success' => true];
    if ($message) $response['message'] = $message;
    if ($data !== null) $response['data'] = $data;
    sendJson($response);
}

/**
 * Récupérer les données JSON du body
 */
function getJsonInput() {
    $jsonData = file_get_contents('php://input');
    $data = json_decode($jsonData, true);
    if (!$data) {
        sendError('Données JSON invalides');
    }
    return $data;
}

/**
 * Récupérer un paramètre (GET ou POST) avec valeur par défaut
 */
function getParam($name, $source = 'GET', $default = null, $required = false) {
    $data = $source === 'GET' ? $_GET : $_POST;
    $value = $data[$name] ?? $default;
    
    if ($required && ($value === null || $value === '')) {
        sendError(ucfirst($name) . ' est requis');
    }
    
    return $value;
}

/**
 * Vérifier plusieurs paramètres requis
 */
function requireParams(array $params, $source = 'GET') {
    $values = [];
    foreach ($params as $param) {
        $values[$param] = getParam($param, $source, null, true);
    }
    return $values;
}

/**
 * Vérifier l'authentification
 */
function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        sendError('Non autorisé', 401);
    }
}

/**
 * Vérifier le rôle admin
 */
function requireAdmin() {
    requireAuth();
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        sendError('Accès réservé aux administrateurs', 403);
    }
}

/**
 * Vérifier qu'un ID est fourni et valide
 */
function requireId($name = 'id', $source = 'GET') {
    $id = getParam($name, $source, null, true);
    if (!is_numeric($id) || $id <= 0) {
        sendError("$name invalide");
    }
    return (int)$id;
}

/**
 * Valider des données selon des règles
 */
function validate($data, $rules) {
    $errors = [];
    
    foreach ($rules as $field => $rule) {
        $value = $data[$field] ?? null;
        
        // Champ requis
        if (isset($rule['required']) && $rule['required'] && empty($value)) {
            $errors[] = ($rule['label'] ?? $field) . ' est requis';
            continue;
        }
        
        // Longueur minimale
        if (isset($rule['min']) && strlen($value) < $rule['min']) {
            $errors[] = ($rule['label'] ?? $field) . " doit contenir au moins {$rule['min']} caractères";
        }
        
        // Longueur maximale
        if (isset($rule['max']) && strlen($value) > $rule['max']) {
            $errors[] = ($rule['label'] ?? $field) . " ne peut pas dépasser {$rule['max']} caractères";
        }
        
        // Valeurs autorisées
        if (isset($rule['in']) && !in_array($value, $rule['in'])) {
            $errors[] = ($rule['label'] ?? $field) . ' invalide';
        }
        
        // Valeur numérique positive
        if (isset($rule['positive']) && $rule['positive'] && (!is_numeric($value) || $value <= 0)) {
            $errors[] = ($rule['label'] ?? $field) . ' doit être un nombre positif';
        }
    }
    
    return empty($errors) ? true : $errors;
}

/**
 * Exécuter une action de contrôleur avec gestion d'erreurs
 */
function executeController($callback) {
    try {
        $result = call_user_func($callback);
        
        // Si le callback retourne un tableau, l'envoyer en JSON
        if (is_array($result)) {
            sendJson($result);
        }
        
        // Sinon, le callback a déjà géré la réponse (exit, redirect, etc.)
    } catch (Exception $e) {
        sendError('Erreur serveur: ' . $e->getMessage(), 500);
    }
}

/**
 * Wrapper pour les actions simples avec ID
 */
function simpleAction($controller, $method, $idParam = 'id') {
    $id = requireId($idParam);
    executeController(function() use ($controller, $method, $id) {
        return $controller->$method($id);
    });
}

/**
 * Wrapper pour les actions CRUD
 */
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
    'publication' => new PublicationController()
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
    $controllers['team']->create();
} 
elseif ($action === 'updateTeam' && $teamid) {
    $controllers['team']->edit($teamid);
} 
elseif ($action === 'deleteTeam' && $teamid) {
    $controllers['team']->delete($teamid);
} 
elseif ($action === 'addMember' && $teamid) {
    $controllers['team']->addMember($teamid);
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
// ROUTES PROJETS (ULTRA-GÉNÉRIQUES)
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

// ============================================
// GESTION DES UTILISATEURS D'UN PROJET
// ============================================

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

// ============================================
// GESTION DES PUBLICATIONS D'UN PROJET
// ============================================

elseif ($action === 'addPublicationToProject') {
    $params = requireParams(['project_id', 'publication_id']);
    executeController(fn() => $controllers['project']->addPublication($params['project_id'], $params['publication_id']));
}

// ============================================
// GESTION DES THÉMATIQUES D'UN PROJET
// ============================================

elseif ($action === 'addThematicToProject') {
    $params = requireParams(['project_id', 'thematic_id']);
    executeController(fn() => $controllers['project']->addThematic($params['project_id'], $params['thematic_id']));
}

// ============================================
// ROUTES ÉQUIPEMENTS (ULTRA-GÉNÉRIQUES)
// ============================================

elseif ($action === 'createEquipment') {
    $data = getJsonInput();
    $validation = validate($data, [
        'nom' => ['required' => true, 'label' => 'Le nom'],
        'id_type' => ['required' => true, 'positive' => true, 'label' => 'Le type']
    ]);
    
    if ($validation !== true) {
        sendJson(['success' => false, 'errors' => $validation]);
    }
    
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
    
    if ($validation !== true) {
        sendJson(['success' => false, 'errors' => $validation]);
    }
    
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
elseif ($action === 'getEquipmentsPaginated') {
    $page = getParam('page', 'GET', 1);
    $perPage = getParam('perPage', 'GET', 20);
    
    $equipments = $models['equipment']->getPaginated($page, $perPage, 'id', 'DESC');
    $total = $models['equipment']->count();
    
    sendJson([
        'success' => true, 
        'data' => $equipments,
        'total' => $total,
        'page' => $page,
        'perPage' => $perPage,
        'totalPages' => ceil($total / $perPage)
    ]);
}
elseif ($action === 'updateEquipmentStatus') {
    $data = getJsonInput();
    $equipmentId = getParam('id', 'POST', null, true);
    $etat = $data['etat'] ?? null;
    
    if (!$etat || !in_array($etat, ['libre', 'réserve', 'en_maintenance'])) {
        sendError('Statut invalide');
    }
    
    sendJson($models['equipment']->updateStatus($equipmentId, $etat)
        ? ['success' => true, 'message' => 'Statut mis à jour avec succès']
        : ['success' => false, 'message' => 'Erreur lors de la mise à jour']);
}
elseif ($action === 'searchEquipments') {
    $keyword = getParam('keyword', 'GET', '', true);
    
    if (strlen($keyword) < 2) {
        sendError('Le mot-clé doit contenir au moins 2 caractères');
    }
    
    sendSuccess($models['equipment']->search($keyword));
}
elseif ($action === 'getEquipmentsByType') {
    $typeId = requireId('type_id');
    sendSuccess($models['equipment']->getByType($typeId));
}
elseif ($action === 'getEquipmentsByStatus') {
    $status = getParam('status', 'GET', '', true);
    
    if (!in_array($status, ['libre', 'réserve', 'en_maintenance'])) {
        sendError('Statut invalide');
    }
    
    sendSuccess($models['equipment']->getByStatus($status));
}
elseif ($action === 'getAvailableEquipments') {
    sendSuccess($models['equipment']->getAvailable('nom', 'ASC'));
}
elseif ($action === 'getMaintenanceNeeded') {
    $daysAhead = getParam('days', 'GET', 30);
    sendSuccess($models['equipment']->getMaintenanceNeeded($daysAhead));
}
elseif ($action === 'updateEquipmentMaintenance') {
    $data = getJsonInput();
    $equipmentId = $data['id'] ?? null;
    
    if (!$equipmentId) {
        sendError('ID équipement manquant');
    }
    
    sendJson($models['equipment']->updateMaintenance(
        $equipmentId, 
        $data['derniere_maintenance'] ?? null, 
        $data['prochaine_maintenance'] ?? null
    )
        ? ['success' => true, 'message' => 'Maintenance mise à jour avec succès']
        : ['success' => false, 'message' => 'Erreur lors de la mise à jour']);
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
    
    if (empty($data['nom'])) {
        sendError('Le nom est requis');
    }
    
    if ($models['equipmentType']->nameExists($data['nom'])) {
        sendError('Ce nom existe déjà', 409);
    }
    
    sendJson($models['equipmentType']->create($data)
        ? ['success' => true, 'message' => 'Type créé avec succès']
        : ['success' => false, 'message' => 'Erreur lors de la création']);
}
elseif ($action === 'updateEquipmentType' && $id) {
    $data = getJsonInput();
    
    if (empty($data['nom'])) {
        sendError('Le nom est requis');
    }
    
    if ($models['equipmentType']->nameExists($data['nom'], $id)) {
        sendError('Ce nom existe déjà', 409);
    }
    
    sendJson($models['equipmentType']->update($id, $data)
        ? ['success' => true, 'message' => 'Type mis à jour avec succès']
        : ['success' => false, 'message' => 'Erreur lors de la mise à jour']);
}
elseif ($action === 'deleteEquipmentType' && $id) {
    if (!$models['equipmentType']->canDelete($id)) {
        sendError('Impossible de supprimer : des équipements utilisent ce type', 409);
    }
    
    sendJson($models['equipmentType']->delete($id)
        ? ['success' => true, 'message' => 'Type supprimé avec succès']
        : ['success' => false, 'message' => 'Erreur lors de la suppression']);
}
elseif ($action === 'getEquipmentType' && $id) {
    $type = $models['equipmentType']->getWithEquipmentCount($id);
    sendJson($type 
        ? ['success' => true, 'data' => $type]
        : ['success' => false, 'message' => 'Type introuvable']);
}
elseif ($action === 'getEquipmentTypes') {
    sendSuccess($models['equipmentType']->getAllWithCounts('nom', 'ASC'));
}

// ============================================
// ROUTES PUBLICATIONS (ULTRA-GÉNÉRIQUES)
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
    requireAdmin();
    $id = requireId();
    executeController(fn() => $controllers['publication']->apiValidatePublication($id));
}
elseif ($action === 'rejectPublication') {
    requireAdmin();
    $id = requireId();
    executeController(fn() => $controllers['publication']->apiRejectPublication($id));
}
elseif ($action === 'deletePublication') {
    requireAdmin();
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
    requireAdmin();
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
// Obtenir les publications par domaine
// elseif ($action === 'getPublicationsByDomain') {
//     $domaine = $_GET['domaine'] ?? null;
//     $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : null;
    
//     if (!$domaine) {
//         echo json_encode(['success' => false, 'message' => 'Domaine requis']);
//         exit;
//     }
    
//     $result = $controllerPublication->apiGetPublicationsByDomain($domaine, $limit);
//     echo json_encode($result);
//     exit;
// }

// ============================================
// ACTION INVALIDE
// ============================================

else {
    sendError('Action invalide', 404);
}
?>