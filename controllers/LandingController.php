<?php
require_once __DIR__ . 'models/News.php';
require_once __DIR__ . 'models/Event.php';
require_once __DIR__ . 'models/Partner.php';
require_once __DIR__ . 'models/organigrame.php';
require_once __DIR__ . 'models/Menu.php';

class LandingController {
    private $newsModel;
    private $eventModel;
    private $partnerModel;
    private $organigrammeModel;
    private $menuModel;
    
    public function __construct() {
        $this->newsModel = new News();
        $this->eventModel = new Event();
        $this->partnerModel = new Partner();
        
        $this->organigrammeModel = new OrganigrammeModel();
        $this->menuModel = new Menu();
    }
    
    /**
     * Afficher la page d'accueil
     */
    public function index() {
        // Récupérer les données pour le slider
        $sliderNews = $this->newsModel->getForSlider();
        
        // Récupérer les actualités récentes (3 dernières)
        $recentNews = $this->newsModel->getRecent(3);
        
        // Récupérer les événements à venir
        $upcomingEvents = $this->eventModel->getAll(5);
        
        // Récupérer les partenaires
        $partners = $this->partnerModel->getAll();
        
        // Organigramme
        $organigramme = [
            'director' => $this->organigrammeModel->getDirector(),
            'hierarchyTree' => $this->organigrammeModel->getHierarchyTree(),
            'stats' => $this->organigrammeModel->getStats()
        ];
        
        // Menu complet en arbre
        $menu = $this->menuModel->getMenuTree();
        
        // Statistiques générales
        $stats = [
            
            'publications' => 48,
            'membres' => 25,
            'partenaires' => count($partners)
        ];
        
        // Préparer les données pour la vue
       $data = [
            'slides' => $sliderNews,
            'news' => $recentNews,
            'events' => $upcomingEvents,
            'partners' => $partners,
            'organigramme' => $organigramme,
            'menu' => $menu,
            'stats' => $stats
        ];
        
        // Charger la vue
require_once __DIR__ . 'views/landingPage.php';
        $view = new LandingPageView($data);
        $view->render();
    }
}
?>
