<?php
// ... inclusions des modèles
require_once '../models/Settings';
require_once '../models/Publications.php';
require_once '../models/Event.php';
require_once '../models/ProjectModel.php';


require_once '../views/public/HomeView.php'; 

class HomeController {

    private $settingsModel;
    private $projectModel;
    private $eventModel;
    private $pubModel;

    
public function __construct()
{
$this->settingsModel=new Settings();
$this->settingsModel=new Project();
$this->settingsModel=new Event();
$this->settingsModel=new Publication();

}
    public function index() {
        // 1. Récupération des données via les Modèles (Data)
        $config = $this->settingsModel->getAll();
        $projects = $this->projectModel->getRecentProjects(3);
        $events = $this->eventModel->getUpcomingEvents(3);
        $news = $this->pubModel->getRecent(3);

        // 2. Préparation des données pour la vue
        $data = [
            'pageTitle' => 'Accueil',
            'config' => $config,
            'slides' => $this->prepareSlides($projects, $events), // Méthode privée pour formater les slides
            'news' => $news,
            'upcomingEvents' => $events
        ];

        // 3. Appel de la Vue (View)
        $view = new HomeView($data);
        $view->render(); // Affiche tout le HTML
    }

    private function prepareSlides($projects, $events) {
        // Logique de formatage des slides (similaire à ce qu'on a fait avant)
        $slides = [];
        // ... boucle sur projects et events ...
        return $slides;
    }
}
?>