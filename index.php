
<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('ROOT_PATH', __DIR__);

// Récupération de la route
$route = $_GET['route'] ?? 'home';

switch ($route) {

    /* =======================
       PARTIE PUBLIQUE
    ======================== */

    case 'home':
        require_once ROOT_PATH . '/controllers/HomeController.php';
        (new HomeController())->index();
        break;

    case 'projects':
        require_once ROOT_PATH . '/controllers/ProjectController.php';
        // Correction : Le fichier est ProjectsController, la classe aussi (avec un S)
        (new ProjectController())->index();
        break;

    case 'project-details':
        require_once ROOT_PATH . '/controllers/ProjectController.php';
        $id = $_GET['id'] ?? 0;
        (new ProjectController())->details($id);
        break;

    case 'publications':
        // Utilisation du contrôleur Public spécifique que nous avons créé
        require_once ROOT_PATH . '/controllers/PublicationController.php';
        (new PublicationController())->index();
        break;

     case 'publication-details':
        // Utilisation du contrôleur Public spécifique que nous avons créé
        $id = $_GET['id'] ?? 0;
        require_once ROOT_PATH . '/controllers/PublicationController.php';
        (new PublicationController())->apiGetPublicationDetails($id);
        break;
       
   
    case 'equipments':
        // Utilisation du contrôleur Public spécifique
        require_once ROOT_PATH . '/controllers/equipementController.php';
        (new EquipmentController())->index();
        break;

    case 'teams':
        require_once ROOT_PATH . '/controllers/TeamController.php';
        // Correction : Le fichier est TeamsController, la classe aussi (avec un S)
        (new TeamController())->index();
        break;
    case 'teams-details':
        $id = $_GET['id'] ?? 0;
        require_once ROOT_PATH . '/controllers/TeamController.php';
        // Correction : Le fichier est TeamsController, la classe aussi (avec un S)
        (new TeamController())->indexWithTeamDetails($id);
        break;    
        

    case 'dashboard-user':
        require_once ROOT_PATH . '/controllers/memberController.php';
        // Correction : Le fichier est TeamsController, la classe aussi (avec un S)
        (new MemberController())->dashboard();
        break;    
    case 'profile-user':
        require_once ROOT_PATH . '/controllers/memberController.php';
        // Correction : Le fichier est TeamsController, la classe aussi (avec un S)
        (new MemberController())->profile();
        break;        



    /* =======================
       ESPACE MEMBRE
    ======================== */

    case 'login':
        require_once ROOT_PATH . '/login.php';
        break;
        

    case  'opportunities':
      require_once ROOT_PATH  . '/controllers/OpportunityController.php';
    $controller = new OpportunityController();
    $controller->indexPublic(); 
    break;
 
    case 'logout':
        require_once ROOT_PATH . '/logout.php';
        break;

    case 'dashboard':
        require_once ROOT_PATH . '/controllers/MemberController.php';
        (new MemberController())->dashboard();
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
    /* =======================
       ADMINISTRATION
    ======================== */
    // Note : Pour l'admin, on peut garder les inclusions directes de vues
    // ou passer par un AdminController si vous l'avez créé.
    
    case 'admin-dashboard':
        require_once ROOT_PATH . '/controllers/UserController.php';
        (new UserController())->indexDashboard();
        break;

    case 'admin-projects':
        require_once ROOT_PATH . '/controllers/ProjectController.php';
        (new ProjectController())->indexProject();
        break;

     case 'project-admin-details':
        $id = $_GET['id'] ?? 0;

        require_once ROOT_PATH . '/controllers/ProjectController.php';
        (new ProjectController())->projectDetails($id);
        break;    

    case 'admin-teams':
        require_once ROOT_PATH . '/controllers/TeamController.php';
        (new TeamController())->indexAdmin();
        break;

    case 'admin-equipement':
        require_once ROOT_PATH . '/controllers/equipementController.php';
        (new EquipmentController())->indexEquipementAdmin();
        break;    

     case 'reservation-history':
        require_once ROOT_PATH . '/controllers/equipementController.php';
        (new EquipmentController())->indexReservationHistory();
        break; 
        
     case 'update-user-admin':
        $id = $_GET['id'] ?? 0;
        require_once ROOT_PATH . '/views/updateUser.php';
        
        break;   

   
        
    case 'admin-publications':
        require_once ROOT_PATH . '/controllers/PublicationController.php';
        (new PublicationController())->indexAdmin();
        break;

    case 'admin-publications-details':
         $id = $_GET['id'] ?? 0;
        require_once ROOT_PATH . '/controllers/PublicationController.php';
        (new PublicationController())->apiGetPublicationDetails($id);
        break;  

    case 'admin-events':
        
        require_once ROOT_PATH . '/controllers/EventController.php';
        (new EventController())->index();
        break;     

    case 'admin-updateUser':
        
        require_once ROOT_PATH . '/controllers/UserController.php';
        (new UserController())->indexUpdateUserAdmin();
        break;        

       // Dans index.php

    case 'api-delete-menu':
        require_once ROOT_PATH . '/controllers/SettingsControllers.php';
        (new SettingsController())->apiDeleteMenuItem();
         break;
    

    case 'admin-settings':
        require_once ROOT_PATH . '/controllers/SettingsControllers.php';
        (new SettingsController())->indexAdmin();
         break;
         
         
    case 'opportunities-admin':
    require_once ROOT_PATH . '/controllers/OpportunityController.php';
    $controller = new OpportunityController();
    $controller->indexAdmin();
    break;

    case'admin-partners':
    require_once __DIR__ . '/controllers/PartnerController.php';
    (new PartnerController())->indexAdmin();
    exit;


// Route Publique
case'partners':
    require_once __DIR__ . '/controllers/PartnerController.php';
    (new PartnerController())->indexPublic();
    break;

case'eventsLists':
    require_once __DIR__ . '/controllers/EventController.php';
    $controller = new EventController();
    $controller->indexPublic();
    exit;
    


     
    /* =======================
       404
    ======================== */

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