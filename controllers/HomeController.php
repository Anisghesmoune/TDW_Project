<?php
// ... inclusions des modèles

require_once __DIR__ .  '/../models/Publications.php';
require_once __DIR__ .  '/../models/ProjectModel.php';
require_once __DIR__ .  '/../models/News.php';
require_once __DIR__ .  '/../models/Event.php';
require_once __DIR__ .  '/../models/Partner.php';
require_once __DIR__ .  '/../models/organigrame.php'; 
require_once __DIR__ .  '/../models/Menu.php';
require_once __DIR__ . '/../models/Settings.php';

// Inclusion de la Vue (Même logique)
require_once __DIR__ .  '/../views/public/HomeView.php';





class HomeController {


    private $newsModel;
    private $eventModel;
    private $partnerModel;
    private $organigrammeModel;
    private $menuModel;
    private $settingsModel; 
    private $publicationModel;
    private $projectModel;
    

    
    public function __construct() {
        $this->newsModel = new News();
        $this->eventModel = new Event();
        $this->partnerModel = new Partner();
        $this->settingsModel = new Settings();
        $this->organigrammeModel = new OrganigrammeModel();
        $this->menuModel = new Menu();
        $this->publicationModel =new Publication();
        $this->projectModel=new Project();
    }
    
 /**
     * Méthode helper pour combiner Projets et Publications
     * et les trier par date décroissante.
     */
    private function getCombinedNews($limit = 6) {
        $combined = [];

        // 1. Récupérer les Projets récents
        // (Vérifiez si votre ProjectModel renvoie un tableau direct ou ['data' => ...])
        $projectsResult = $this->projectModel->getRecentProjects($limit); 
        $projects = $projectsResult['data'] ?? $projectsResult; // Sécurité format

        if (is_array($projects)) {
            foreach ($projects as $p) {
                $combined[] = [
                    'id'          => $p['id'],
                    'titre'       => $p['titre'],
                    'description' => $p['description'] ?? '',
                    // On mappe la date de début du projet vers 'date_debut' pour UINews
                    'date_debut'  => $p['date_debut'], 
                    'type'        => 'projet', // Pour l'icône
                    'lieu'        => 'Laboratoire', // Optionnel
                    'source'      => 'project' // Pour savoir où faire le lien (détails projet)
                ];
            }
        }

        // 2. Récupérer les Publications récentes
        $pubs = $this->publicationModel->getRecent($limit);
        
        if (is_array($pubs)) {
            foreach ($pubs as $pub) {
                $combined[] = [
                    'id'          => $pub['id'],
                    'titre'       => $pub['titre'],
                    'description' => $pub['resume'] ?? '',
                    // On mappe la date de publi vers 'date_debut' pour que UINews comprenne
                    'date_debut'  => $pub['date_publication'], 
                    'type'        => $pub['type'] ?? 'publication', // article, thèse...
                    'lieu'        => 'Bibliothèque', // Optionnel
                    'source'      => 'publication' // Pour savoir où faire le lien
                ];
            }
        }

        // 3. Trier le tout par date (du plus récent au plus vieux)
        usort($combined, function($a, $b) {
            return strtotime($b['date_debut']) - strtotime($a['date_debut']);
        });

        // 4. Retourner seulement le nombre demandé
        return array_slice($combined, 0, $limit);
    }
    public function index() {
        // Récupérer les données pour le slider
        $sliderNews = $this->newsModel->getForSlider();

     $config = $this->settingsModel->getAllSettings(); // Logo, Couleurs
        
        // Récupérer les actualités récentes (3 dernières)
        $recentNews = $this->getCombinedNews(6);         
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
        
     
        
      $data = [
            'config' => $config,       
            'menu' => $menu,           
            'slides' => $sliderNews,
            'news' => $recentNews,
            'events' => $upcomingEvents,
            'partners' => $partners,
            'organigramme' => $organigramme
        ];
        
        // Charger la vue
// require_once __DIR__ . 'views/landingPage.php';
//         $view = new LandingPageView($data);
//         $view->render();
require_once __DIR__ .  '/../views/public/HomeView.php';
 $view = new HomeView($data);
        $view->render();
    }
}

