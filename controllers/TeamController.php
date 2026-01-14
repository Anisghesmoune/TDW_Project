<?php
require_once __DIR__ .  '/../models/Publications.php';
require_once __DIR__ .  '/../models/ProjectModel.php';
require_once __DIR__ .  '/../models/News.php';
require_once __DIR__ .  '/../models/Event.php';
require_once __DIR__ .  '/../models/Partner.php';
require_once __DIR__ .  '/../models/organigrame.php'; 
require_once __DIR__ .  '/../models/Menu.php';
require_once __DIR__ . '/../models/Settings.php';
require_once __DIR__ . '/../models/TeamsModel.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../controllers/PublicationController.php';

class TeamController {

    protected $teamModel;
    protected $orgModel;
    protected $settingsModel;
    protected $menuModel;
    protected $userModel;
    protected $projectModel;
    protected $eventModel;
    protected $publicationModel;
    protected $teamsModel;

    public function __construct() {
        $this->teamModel        = new TeamsModel();
        $this->orgModel         = new OrganigrammeModel();
        $this->settingsModel    = new Settings();
        $this->menuModel        = new Menu();
        $this->userModel        = new UserModel();
        $this->projectModel     = new Project();
        $this->eventModel       = new Event();
        $this->publicationModel = new Publication();
        $this->teamsModel = new TeamsModel();
    }

 
    public function index() {
        try {
            $config = $this->settingsModel->getAllSettings();
            $menu   = $this->menuModel->getMenuTree();

            $organigramme = [
                'director' => $this->orgModel->getDirector(),
                'tree'     => $this->orgModel->getHierarchyTree()
            ];

            $teamsList = $this->teamModel->getAllTeams();
            $teamsData = [];

            foreach ($teamsList as $team) {
                $teamsData[] = [
                    'info'    => $team,
                    'leader'  => $this->teamModel->getTeamLeader($team['id']),
                    'members' => $this->teamModel->getTeamMembers($team['id'])
                ];
            }

            $allMembers = $this->teamModel->getAllMembersFlat();

            $users = $this->userModel->getAll();

            $projects = $this->projectModel->getAll();
            $event    = $this->eventModel->getAll();

            $data = [
                'config'        => $config,
                'menu'          => $menu,
                'organigramme'  => $organigramme,
                'teams'         => $teamsData,
                'allMembers'    => $allMembers,
                'users'         => $users,
                'projects'      => $projects,
                'eventdata'     => $event,
                'title' => 'Gestion des Équipes',

            ];

          

        require_once __DIR__ . '/../views/public/TeamView.php';
        $view = new TeamView($data);
        $view->render();
    } catch (Exception $e) {
            $_SESSION['error'] = 'Erreur lors du chargement des équipes : ' . $e->getMessage();
            header('Location: /');
            exit;
        }
    }

   
    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->store();
        }
        
        $users = $this->getEligibleChefs();
        
    }
    
   
    private function store() {
        $errors = $this->validateTeamData($_POST);
        
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = $_POST;
            exit;
        }
        
        $this->teamModel->setName($_POST['name']);
        $this->teamModel->setDescription($_POST['description']);
        $this->teamModel->setDomaineRecherche($_POST['domaine_recherche']);
        $this->teamModel->setChefEquipeId($_POST['chef_equipe_id']);
        
        if ($this->teamModel->create()) {
            $_SESSION['success'] = 'Équipe créée avec succès.';
             return [
                'success' => true,
                'message' => 'creation avec succes'
            ];
            exit;
        } else {
            $_SESSION['error'] = 'Erreur lors de la création de l\'équipe.';
             return [
                'success' => false,
                'message' => 'Données manquantes'
            ];
            exit;
        }
    }
    
 
    public function show($team_id) {
        $team = $this->teamModel->getTeamWithDetails($team_id);
        
        if (!$team) {
            $_SESSION['error'] = 'Équipe introuvable.';
            exit;
        }
        
        $members = $this->teamModel->getTeamMembers($team_id);
        
        $availableUsers = $this->teamModel->getAvailableUsers($team_id);
        
    }
    
  
    public function edit($team_id) {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            $data = $_POST;
        }

        if (!empty($data)) {
            
            return $this->update($team_id, $data);
        }
        
       $team = $this->teamModel->getTeamById($team_id);
        
        if (!$team) {
            return [
                'success' => false,
                'message' => 'Équipe introuvable'
            ];
        }
        
        return [
            'success' => true,
            'data' => $team
        ];
    }
    
   

    
 
    public function getTeamPublications($teamId) {
        return $this->teamModel->getTeamPublications($teamId);
    }
    
    
    public function getTeamEquipement($team_id) {
        return $this->teamModel->getTeamEquipments($team_id);
    }
    
   
    private function update($team_id, $data) {
         $errors = $this->validateTeamData($data); 
        
        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $errors
            ];
        }
        
        $this->teamModel->setName($data['name']);
        $this->teamModel->setDescription($data['description'] ?? '');
        $this->teamModel->setDomaineRecherche($data['domaine_recherche'] ?? '');
        $this->teamModel->setChefEquipeId($data['chef_equipe_id']);
        
        if ($this->teamModel->update($team_id)) {
             return [
                'success' => true,
                'message' => 'Équipe mise à jour avec succès'
            ];
        } else {
             return [
                'success' => false,
                'message' => 'Erreur lors de la mise à jour en base de données'
            ];
        }
    }
    
   
    public function delete($team_id) {
        if ($this->teamModel->delete($team_id)) {
             return [
                'success' => true,
                'message' => 'delete avec succes'
            ];
        } else {
             return [
                'success' => false,
                'message' => 'Données manquantes'
            ];
        }
        
        exit;
    }
    
   
    public function addMember($team_id, $user_id, $role = 'membre') {
        if (!$team_id || !$user_id) {
            return [
                'success' => false,
                'message' => 'Données manquantes'
            ];
        }
        
        $result = $this->teamModel->addMember($team_id, $user_id, $role);
        
        return [
            'success' => $result,
            'message' => $result 
                ? 'Membre ajouté avec succès' 
                : 'Impossible d\'ajouter le membre (peut-être déjà membre)'
        ];
    }

  
  
    public function removeMember($team_id, $user_id) {
        if (!$team_id || !$user_id) {
            return [
                'success' => false,
                'message' => 'Données manquantes'
            ];
        }
        
        $result = $this->teamModel->removeMember($team_id, $user_id);
        
        return [
            'success' => $result,
            'message' => $result 
                ? 'Membre retiré avec succès' 
                : 'Erreur lors du retrait du membre'
        ];
    }
    
   
    private function validateTeamData($data) {
        $errors = [];
        
        if (empty($data['name'])) {
            $errors['name'] = 'Le nom est obligatoire.';
        }
        
        if (empty($data['chef_equipe_id'])) {
            $errors['chef_equipe_id'] = 'Le chef d\'équipe est obligatoire.';
        }
        
        return $errors;
    }
    
 
    private function getEligibleChefs() {
        return $this->userModel->getAll(['statut' => 'actif']);
    }


    public function getall($id) {
        $team = $this->teamModel->getTeamWithDetails($id);
        return $team;
    }

 
    public function getTeamMembers($id) {
        $members = $this->teamModel->getTeamMembers($id);
        return $members;
    }
 
    public function getAvailableUsers($id) {
        $availableUsers = $this->teamModel->getAvailableUsers($id);
        return $availableUsers;
    }
    
  
    public function getAllTeamsWithDetails() {
        $teams = $this->teamModel->getAllTeamsWithDetails();
        return $teams;
    }
 
    public function getTeamEquipments($team_id) {
        return $this->teamModel->getTeamEquipments($team_id);
    }
  
    public function getAvailableForTeam($team_id) {
        return $this->teamModel->getAvailableForTeam($team_id);
    }
 
    public function assignEquipment($team_id, $equipment_id) {
        return $this->teamModel->assignEquipment($team_id, $equipment_id);      
    }
    

    public function removeEquipment($team_id, $equipment_id) {
        return $this->teamModel->unassignEquipment($team_id, $equipment_id);      
    }
    
  
   public function indexWithTeamDetails($teamId) {
        try {
            if (session_status() === PHP_SESSION_NONE) session_start();
            
            $team = $this->getall($teamId);
            $members = $this->getTeamMembers($teamId);
            $publications = $this->getTeamPublications($teamId);
            $equipments = $this->getTeamEquipments($teamId);
            
            if (!$team) {
                exit;
            }

            $config = $this->settingsModel->getAllSettings();
            $menu = $this->menuModel->getMenuTree();
            
            $data = [
                'title' => 'Détails de l\'équipe - ' . ($team['nom'] ?? ''),
                'team' => $team,
                'members' => $members,
                'publications' => $publications,
                'equipments' => $equipments,
                'config' => $config,
                'menu' => $menu      
            ];
            
            require_once __DIR__ . '/../views/team-details.php';
            $view = new TeamDetailsView($data);
            $view->render();
            
            return $data; 
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'errors'  => ["Erreur lors de la récupération des détails : " . $e->getMessage()]
            ];
        }
    }
   public function indexAdmin() {
        $teams = $this->teamsModel->getAllTeamsWithDetails();
        $users = $this->userModel->getAll();
        
        $projects = $this->projectModel->getAll(); 
        $publicationData = $this->publicationModel->getAll();
        
    

        $config = $this->settingsModel->getAllSettings();
        $menu = $this->menuModel->getMenuTree();

        $data = [
            'title' => 'Gestion des Équipes', 
            'teams' => $teams,
            'users' => $users,
            'projects' => $projects,
            'publicationData' => $publicationData,
            'config' => $config,
            'menu' => $menu
        ];

        require_once __DIR__ . '/../views/TeamManagement.php';
        $view = new TeamAdminView($data);
        $view->render();

        return $data;
    }
}
