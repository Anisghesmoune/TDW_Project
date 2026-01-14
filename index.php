
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
define('ROOT_PATH', __DIR__);

function requireAdmin() {
    requireAuth();

    if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
        header('Location: index.php?route=dashboard-user'); 
        exit; 
    }
}
function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: index.php?route=login');
        exit; 
    }
}

$route = $_GET['route'] ?? 'home';

switch ($route) {

  

    case 'home':
        require_once ROOT_PATH . '/controllers/HomeController.php';
        (new HomeController())->index();
        break;

    case 'projects':
        require_once ROOT_PATH . '/controllers/ProjectController.php';
        (new ProjectController())->index();
        break;

    case 'project-details':
        require_once ROOT_PATH . '/controllers/ProjectController.php';
        $id = $_GET['id'] ?? 0;
        (new ProjectController())->details($id);
        break;

    case 'publications':
        require_once ROOT_PATH . '/controllers/PublicationController.php';
        (new PublicationController())->index();
        break;

     case 'publication-details':
        $id = $_GET['id'] ?? 0;
        require_once ROOT_PATH . '/controllers/PublicationController.php';
        (new PublicationController())->apiGetPublicationDetails($id);
        break;
       
   
    case 'equipments':
        requireAuth();
        require_once ROOT_PATH . '/controllers/equipementController.php';
        (new EquipmentController())->index();
        break;

    case 'teams':
        require_once ROOT_PATH . '/controllers/TeamController.php';
        (new TeamController())->index();
        break;
    case 'teams-details':
        $id = $_GET['id'] ?? 0;
        require_once ROOT_PATH . '/controllers/TeamController.php';
        (new TeamController())->indexWithTeamDetails($id);
        break;    
        

    case 'dashboard-user':
        requireAuth();
        require_once ROOT_PATH . '/controllers/memberController.php';
        (new MemberController())->dashboard();
        break;    
    case 'profile-user':
        requireAuth();
        require_once ROOT_PATH . '/controllers/memberController.php';
        (new MemberController())->profile();
        break;        



  

    case 'login':
        require_once ROOT_PATH . '/login.php';
        break;
    case 'register':
        require_once ROOT_PATH . '/register_process.php';
        break;  
    case 'logout':
        require_once ROOT_PATH . '/logout.php';
        break;    
              
        

    case  'opportunities':
      require_once ROOT_PATH  . '/controllers/OpportunityController.php';
    $controller = new OpportunityController();
    $controller->indexPublic(); 
    break;
 
    case 'logout':
        require_once ROOT_PATH . '/logout.php';
        break;

    

    case 'profile':
        require_once ROOT_PATH . '/controllers/MemberController.php';
        (new MemberController())->profile();
        break;

  case 'contact':
        require_once ROOT_PATH . '/controllers/ContactController.php';
        (new ContactController())->index();
        break;

    case 'send-contact':
        require_once ROOT_PATH . '/controllers/ContactController.php';
        (new ContactController())->sendMessage();
        break;
 
    
    case 'admin-dashboard':
        requireAdmin();
        require_once ROOT_PATH . '/controllers/UserController.php';
        (new UserController())->indexDashboard();
        break;

    case 'admin-projects':
        requireAdmin();
        require_once ROOT_PATH . '/controllers/ProjectController.php';
        (new ProjectController())->indexProject();
        break;

     case 'project-admin-details':
        requireAdmin();
        $id = $_GET['id'] ?? 0;

        require_once ROOT_PATH . '/controllers/ProjectController.php';
        (new ProjectController())->projectDetails($id);
        break;    

    case 'admin-teams':
        requireAdmin();
        require_once ROOT_PATH . '/controllers/TeamController.php';
        (new TeamController())->indexAdmin();
        break;

    case 'admin-equipement':
        requireAdmin();
        require_once ROOT_PATH . '/controllers/equipementController.php';
        (new EquipmentController())->indexEquipementAdmin();
        break;    

     case 'reservation-history':
        require_once ROOT_PATH . '/controllers/equipementController.php';
        (new EquipmentController())->indexReservationHistory();
        break; 
        
      

   
        
    case 'admin-publications':
        requireAdmin();
        require_once ROOT_PATH . '/controllers/PublicationController.php';
        (new PublicationController())->indexAdmin();
        break;

    case 'admin-publications-details':
        requireAdmin();
         $id = $_GET['id'] ?? 0;
        require_once ROOT_PATH . '/controllers/PublicationController.php';
        (new PublicationController())->apiGetPublicationDetails($id);
        break;  

    case 'admin-events':
        requireAdmin();
        require_once ROOT_PATH . '/controllers/EventController.php';
        (new EventController())->index();
        break;     

    case 'admin-updateUser':
        requireAdmin();
        require_once ROOT_PATH . '/controllers/UserController.php';
        (new UserController())->indexUpdateUserAdmin();
        break;        

       

    case 'api-delete-menu':
        requireAdmin();
        require_once ROOT_PATH . '/controllers/SettingsControllers.php';
        (new SettingsController())->apiDeleteMenuItem();
         break;
    

    case 'admin-settings':
        requireAdmin();
        require_once ROOT_PATH . '/controllers/SettingsControllers.php';
        (new SettingsController())->indexAdmin();
         break;
         
         
    case 'opportunities-admin':
        requireAdmin();
    require_once ROOT_PATH . '/controllers/OpportunityController.php';
    $controller = new OpportunityController();
    $controller->indexAdmin();
    break;

    case'admin-partners':
        requireAdmin();
    require_once __DIR__ . '/controllers/PartnerController.php';
    (new PartnerController())->indexAdmin();
    exit;


case'partners':
    require_once __DIR__ . '/controllers/PartnerController.php';
    (new PartnerController())->indexPublic();
    break;

case'eventsLists':
    require_once __DIR__ . '/controllers/EventController.php';
    $controller = new EventController();
    $controller->indexPublic();
    exit;
    

    default:
        http_response_code(404);
        echo "<div style='text-align:center; margin-top:50px;'>";
        echo "<h1>Erreur 404</h1>";
        echo "<p>La page demandée n'existe pas.</p>";
        echo "<a href='index.php?route=home'>Retour à l'accueil</a>";
        echo "</div>";
        break;
}
?>