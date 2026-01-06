<?php
require_once __DIR__ . 'config/Database.php';
require_once __DIR__ . 'models/Model.php';

require_once __DIR__ . 'models/News.php';
require_once __DIR__ . 'models/Event.php';
require_once __DIR__ . 'models/Partner.php';


require_once __DIR__ . 'views/header.php';
require_once __DIR__ . 'views/navbar.php';
require_once __DIR__ . 'views/diaporama.php';
require_once __DIR__ . 'views/newsSection.php';
require_once __DIR__ . 'views/aboutsection.php';
require_once __DIR__ . 'views/eventSection.php';
require_once __DIR__ . 'views/partnerSection.php';
require_once __DIR__ . 'views/footer.php';
require_once __DIR__ . 'views/navbar.php';
require_once __DIR__ . 'views/Cards.php';
require_once __DIR__ . 'views/Sidebar.php';


require_once __DIR__ . 'views/organigramme.php';
require_once __DIR__ . 'views/landingPage.php';

require_once __DIR__ . 'controllers/LandingController.php';

session_start();

$controller = new LandingController();
$controller->index();
?>