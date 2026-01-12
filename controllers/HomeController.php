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
require_once __DIR__ . '/../models/OpportunityModel.php'; 

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
    private $opportunityModel;
    

    
    public function __construct() {
        $this->newsModel = new News();
        $this->eventModel = new Event();
        $this->partnerModel = new Partner();
        $this->settingsModel = new Settings();
        $this->organigrammeModel = new OrganigrammeModel();
        $this->menuModel = new Menu();
        $this->publicationModel =new Publication();
        $this->projectModel=new Project();
        $this->opportunityModel = new OpportunityModel(); 
    }
    
 /**
     * Méthode helper pour combiner Projets et Publications
     * et les trier par date décroissante.
     */
    /**
     * Méthode helper pour combiner Projets et Publications
     * et les trier par date décroissante.
     */
    private function getCombinedNews($limit = 6) {
        $combined = [];

        // 1. Récupérer les Projets récents
        $projectsResult = $this->projectModel->getRecentProjects($limit); 
        $projects = $projectsResult['data'] ?? $projectsResult; 

        // CORRECTION 1 : Vérification stricte du format
        if (is_array($projects)) {
            // Si c'est un tableau associatif simple (un seul projet), on le met dans un tableau
            if (isset($projects['id'])) {
                $projects = [$projects];
            }

            foreach ($projects as $p) {
                // Sécurité : On s'assure que $p est bien un tableau (un projet)
                if (!is_array($p)) {
                    continue; 
                }

                $combined[] = [
                    'id'          => $p['id'] ?? 0,
                    'titre'       => $p['titre'] ?? 'Sans titre',
                    'description' => $p['description'] ?? '',
                    // CORRECTION 2 : Gestion des dates nulles
                    'date_debut'  => !empty($p['date_debut']) ? $p['date_debut'] : date('Y-m-d'),
                    'type'        => 'projet', 
                    'lieu'        => 'Laboratoire',
                    'source'      => 'project' 
                ];
            }
        }

        // 2. Récupérer les Publications récentes
        $pubs = $this->publicationModel->getRecent($limit);
        
        if (is_array($pubs)) {
            // Même vérification pour les publications si nécessaire
            if (isset($pubs['id'])) {
                $pubs = [$pubs];
            }

            foreach ($pubs as $pub) {
                if (!is_array($pub)) {
                    continue;
                }

                $combined[] = [
                    'id'          => $pub['id'] ?? 0,
                    'titre'       => $pub['titre'] ?? 'Sans titre',
                    'description' => $pub['resume'] ?? '',
                    // CORRECTION 2 : Gestion des dates nulles
                    'date_debut'  => !empty($pub['date_publication']) ? $pub['date_publication'] : date('Y-m-d'),
                    'type'        => $pub['type'] ?? 'publication', 
                    'lieu'        => 'Bibliothèque', 
                    'source'      => 'publication'
                ];
            }
        }

        // 3. Trier le tout par date (du plus récent au plus vieux)
        usort($combined, function($a, $b) {
            // CORRECTION 3 : Éviter l'erreur Deprecated strtotime(null)
            $dateA = $a['date_debut'] ?? null;
            $dateB = $b['date_debut'] ?? null;

            $timeA = $dateA ? strtotime($dateA) : 0;
            $timeB = $dateB ? strtotime($dateB) : 0;

            return $timeB - $timeA;
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
        $opportunities = $this->opportunityModel->getAllOpportunities(); 
         $opportunities = array_slice($opportunities, 0, 4);
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
            'organigramme' => $organigramme,
            'opportunities' => $opportunities 

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

