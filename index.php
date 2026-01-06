<?php
// ==========================================
// POINT D'ENTRÉE UNIQUE DE L'APPLICATION
// ==========================================

// 1. Démarrage de la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


define('ROOT_PATH', __DIR__);

$route = $_GET['route'] ?? 'home';

// 4. Routage (Switch Case)
switch ($route) {
    
    // --- PARTIE PUBLIQUE ---
    
    case 'home':
        require_once 'controllers/HomeController.php';
        (new HomeController())->index();
        break;

    case 'projects':
        require_once 'controllers/ProjectsController.php';
        (new ProjectsController())->index();
        break;
        
    case 'project-details':
        require_once 'controllers/ProjectsController.php';
        $id = $_GET['id'] ?? 0;
        (new ProjectsController())->details($id);
        break;

    case 'publications':
        require_once 'controllers/PublicPublicationController.php';
        (new PublicPublicationController())->index();
        break;
        
    case 'publication-details':
        // Si vous avez une méthode details dans PublicPublicationController
        // Sinon, redirigez vers l'ancien fichier ou créez le contrôleur
        require_once 'controllers/PublicationController.php'; 
        // Note: Vous devrez peut-être adapter si vous n'avez pas de méthode 'publicDetails'
        break;

    case 'equipments':
        require_once 'controllers/PublicEquipmentController.php';
        (new PublicEquipmentController())->index();
        break;

    case 'teams':
        require_once 'controllers/TeamsController.php';
        (new TeamsController())->index();
        break;
        
    case 'contact':
        // Si vous avez un ContactController
        // require_once 'controllers/ContactController.php';
        // (new ContactController())->index();
        // Sinon, include direct de la vue (moins propre mais marche)
        require_once 'views/public/contact.php'; 
        break;


    // --- ESPACE MEMBRE ---

    case 'login':
        require_once 'views/login.php'; 
        break;
        
    case 'logout':
        require_once 'logout.php'; 
        break;

    case 'dashboard':
        require_once 'controllers/MemberController.php';
        (new MemberController())->dashboard(); // Appel de la méthode dashboard
        break;

    case 'profile':
        require_once 'controllers/MemberController.php';
        (new MemberController())->profile();
        break;


    // --- ADMINISTRATION ---
    // (Accessible uniquement aux admins - vérification faite dans les contrôleurs ou ici)

    case 'admin':
    case 'admin-dashboard':
        require_once 'views/dashboard.php'; // Votre dashboard admin principal
        break;
        
    case 'admin-users':
        require_once 'views/users.php'; // Votre gestion utilisateurs
        break;
        
    case 'admin-teams':
        require_once 'views/team-management.php';
        break;

    // ... ajoutez les autres routes admin ici ...


    // --- CAS PAR DÉFAUT (404) ---
    default:
        http_response_code(404);
        echo "<h1>404 - Page non trouvée</h1>";
        echo "<p>La route '{$route}' n'existe pas.</p>";
        echo "<a href='index.php'>Retour à l'accueil</a>";
        break;
}
?>