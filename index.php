<?php
require_once 'config/Database.php';
require_once 'models/Model.php';

require_once 'models/News.php';
require_once 'models/Event.php';
require_once 'models/Partner.php';


require_once 'views/header.php';
require_once 'views/navbar.php';
require_once 'views/diaporama.php';
require_once 'views/newsSection.php';
require_once 'views/aboutsection.php';
require_once 'views/eventSection.php';
require_once 'views/partnerSection.php';
require_once 'views/footer.php';
require_once 'views/navbar.php';
require_once 'views/Cards.php';
require_once 'views/Sidebar.php';


require_once 'views/organigramme.php';
require_once 'views/landingPage.php';

require_once 'controllers/LandingController.php';

session_start();

$controller = new LandingController();
$controller->index();
?>