<?php
require_once __DIR__ .  '/../models/Publications.php';
require_once __DIR__ .  '/../models/ProjectModel.php';
require_once __DIR__ .  '/../models/News.php';
require_once __DIR__ .  '/../models/Event.php';
require_once __DIR__ .  '/../models/Partner.php';
require_once __DIR__ .  '/../models/organigrame.php'; 
require_once __DIR__ .  '/../models/Menu.php';
require_once __DIR__ . '/../models/Settings.php';
require_once __DIR__ . '/../models/OpportunityModel.php'; 
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
        $this->publicationModel = new Publication();
        $this->projectModel = new Project();
        $this->opportunityModel = new OpportunityModel(); 
    }

    
    private function getMixedSliderContent($limit = 5) {
        $slides = [];

        $events = $this->eventModel->getAll($limit); 
        if (is_array($events)) {
            foreach ($events as $e) {
                if (!isset($e['id'])) continue;

                $slides[] = [
                    'titre'       => $e['titre'] ?? 'Événement',
                    'description' => $e['description'] ?? '',
                    'image'       => !empty($e['image']) ? $e['image'] : 'assets/images/defaults/event-default.jpg',
                    'date_sort'   => $e['date_debut'] ?? date('Y-m-d'),
                    'lien_detail' => "index.php?route=eventsLists" 
                ];
            }
        }

        $projectsResult = $this->projectModel->getRecentProjects($limit);
        $projects = $projectsResult['data'] ?? ($projectsResult ?: []); 
        if (isset($projects['id'])) $projects = [$projects];

        foreach ($projects as $p) {
            if (!isset($p['id'])) continue;

            $slides[] = [
                'titre'       => $p['titre'] ?? 'Projet de Recherche',
                'description' => $p['description'] ?? '',
                'image'       => 'assets/images/defaults/project-default.jpg', // Image par défaut pour projets
                'date_sort'   => $p['date_debut'] ?? date('Y-m-d'),
                'lien_detail' => "index.php?route=project-details&id=" . $p['id']
            ];
        }

        $pubs = $this->publicationModel->getRecent($limit);
        if (isset($pubs['id'])) $pubs = [$pubs];

        foreach ($pubs as $pub) {
            if (!isset($pub['id'])) continue;

            $slides[] = [
                'titre'       => $pub['titre'] ?? 'Publication',
                'description' => $pub['resume'] ?? 'Consultez cette publication...',
                'image'       => 'assets/images/defaults/publication-default.jpg',
                'date_sort'   => $pub['date_publication'] ?? date('Y-m-d'),
                'lien_detail' => "index.php?route=publication-details&id=" . $pub['id']
            ];
        }

        usort($slides, function($a, $b) {
            return strtotime($b['date_sort']) - strtotime($a['date_sort']);
        });

        return array_slice($slides, 0, $limit);
    }

    private function getCombinedNews($limit = 6) {
        $combined = [];

        $projectsResult = $this->projectModel->getRecentProjects($limit); 
        
        $projects = $projectsResult['data'] ?? $projectsResult; 

        if (!is_array($projects)) {
            $projects = [];
        } elseif (isset($projects['id'])) { 
            $projects = [$projects];
        }

        foreach ($projects as $p) {
            $p = (array) $p;

            if (empty($p['id'])) continue;

            $combined[] = [
                'id'          => $p['id'],
                'titre'       => $p['titre'] ?? 'Projet sans titre',
                'description' => $p['description'] ?? '', 
                'date_debut'  => $p['date_debut'] ?? date('Y-m-d'),
                'type'        => 'projet', 
                'lieu'        => 'Laboratoire',
                'source'      => 'project',
                'image'       => 'assets/images/defaults/project-default.jpg' 
            ];
        }

        $pubs = $this->publicationModel->getRecent($limit);
        
        if (!is_array($pubs)) {
            $pubs = [];
        } elseif (isset($pubs['id'])) {
            $pubs = [$pubs];
        }

        foreach ($pubs as $pub) {
            $pub = (array) $pub;

            if (empty($pub['id'])) continue;

            $combined[] = [
                'id'          => $pub['id'],
                'titre'       => $pub['titre'] ?? 'Publication',
                'description' => $pub['resume'] ?? '', 
                'date_debut'  => $pub['date_publication'] ?? date('Y-m-d'),
                'type'        => $pub['type'] ?? 'publication', 
                'lieu'        => 'Bibliothèque', 
                'source'      => 'publication',
                'image'       => 'assets/images/defaults/publication-default.jpg'
            ];
        }

        usort($combined, function($a, $b) {
            $timeA = strtotime($a['date_debut']);
            $timeB = strtotime($b['date_debut']);
            return $timeB - $timeA;
        });

        if (empty($combined)) {
            error_log("Attention : getCombinedNews ne trouve aucune donnée dans Projets ou Publications.");
        }

        return array_slice($combined, 0, $limit);
    }

    public function index() {
       
        $sliderData = $this->getMixedSliderContent(5);
        
       

        $config = $this->settingsModel->getAllSettings(); 
        $recentNews = $this->getCombinedNews(6);       
        $upcomingEvents = $this->eventModel->getAll(5);
        $partners = $this->partnerModel->getAll();
        $opportunities = $this->opportunityModel->getAllOpportunities(); 
        $opportunities = array_slice($opportunities, 0, 4);
        
        $organigramme = [
            'director' => $this->organigrammeModel->getDirector(),
            'hierarchyTree' => $this->organigrammeModel->getHierarchyTree(),
            'stats' => $this->organigrammeModel->getStats()
        ];
        
        $menu = $this->menuModel->getMenuTree();
        
        $data = [
            'config' => $config,       
            'menu' => $menu,           
            'slides' => $sliderData, 
            'news' => $recentNews,
            'events' => $upcomingEvents,
            'partners' => $partners,
            'organigramme' => $organigramme,
            'opportunities' => $opportunities 
        ];
        
        $view = new HomeView($data);
        $view->render();
    }
}
?>