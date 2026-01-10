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

    /**
     * Afficher la liste de toutes les équipes + données complémentaires
     */
    public function index() {
        try {
            // Config et menu
            $config = $this->settingsModel->getAllSettings();
            $menu   = $this->menuModel->getMenuTree();

            // Organigramme
            $organigramme = [
                'director' => $this->orgModel->getDirector(),
                'tree'     => $this->orgModel->getHierarchyTree()
            ];

            // Équipes avec chef et membres
            $teamsList = $this->teamModel->getAllTeams();
            $teamsData = [];

            foreach ($teamsList as $team) {
                $teamsData[] = [
                    'info'    => $team,
                    'leader'  => $this->teamModel->getTeamLeader($team['id']),
                    'members' => $this->teamModel->getTeamMembers($team['id'])
                ];
            }

            // Tous les membres pour les filtres
            $allMembers = $this->teamModel->getAllMembersFlat();

            // Tous les utilisateurs pour select (chef d'équipe)
            $users = $this->userModel->getAll();

            // Projets, événements et publications
            $projects = $this->projectModel->getAll();
            $event    = $this->eventModel->getAll();
            // $publications = $this->publicationModel->stat();

            // Préparer les données pour la vue
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

          

        // 5. Chargement de la Vue
        require_once __DIR__ . '/../views/public/TeamView.php';
        $view = new TeamView($data);
        $view->render();
    } catch (Exception $e) {
            // Gérer les erreurs
            $_SESSION['error'] = 'Erreur lors du chargement des équipes : ' . $e->getMessage();
            header('Location: /');
            exit;
        }
    }

    /**
     * Afficher le formulaire de création
     */
    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->store();
        }
        
        // Récupérer la liste des utilisateurs potentiels chefs d'équipe
        $users = $this->getEligibleChefs();
        
        // Charger la vue
    }
    
    /**
     * Enregistrer une nouvelle équipe
     */
    private function store() {
        // Validation
        $errors = $this->validateTeamData($_POST);
        
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = $_POST;
            exit;
        }
        
        // Créer l'équipe
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
    
    /**
     * Afficher les détails d'une équipe
     */
    public function show($team_id) {
        $team = $this->teamModel->getTeamWithDetails($team_id);
        
        if (!$team) {
            $_SESSION['error'] = 'Équipe introuvable.';
            exit;
        }
        
        // Récupérer les membres
        $members = $this->teamModel->getTeamMembers($team_id);
        
        // Récupérer les utilisateurs disponibles pour ajout
        $availableUsers = $this->teamModel->getAvailableUsers($team_id);
        
        // Charger la vue
    }
    
    /**
     * Afficher le formulaire de modification
     */
    public function edit($team_id) {
        // 1. Récupération des données (Supporte JSON et POST classique)
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            $data = $_POST;
        }

        // 2. Si des données sont présentes, on lance la mise à jour
        if (!empty($data)) {
            
            return $this->update($team_id, $data);
        }
        
        // 3. Si aucune donnée (GET), on pourrait charger les infos pour l'affichage (optionnel en API)
        // Dans une architecture API pure, on retourne souvent une erreur ici ou les données de l'équipe
        $team = $this->teamModel->getTeamById($team_id);
        
        if (!$team) {
            return [
                'success' => false,
                'message' => 'Équipe introuvable'
            ];
        }
        
        // Si c'est pour pré-remplir un formulaire
        return [
            'success' => true,
            'data' => $team
        ];
    }
    
   

    
    /**
     * Récupérer les publications d'une équipe
     */
    public function getTeamPublications($teamId) {
        return $this->teamModel->getTeamPublications($teamId);
    }
    
    /**
     * Récupérer les équipements d'une équipe
     */
    public function getTeamEquipement($team_id) {
        return $this->teamModel->getTeamEquipments($team_id);
    }
    
    /**
     * Mettre à jour une équipe
     */
    private function update($team_id, $data) {
        // 1. Validation des données
        // Assurez-vous que validateTeamData accepte un tableau
        $errors = $this->validateTeamData($data); 
        
        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $errors
            ];
        }
        
        // 2. Utilisation des Setters du Modèle
        $this->teamModel->setName($data['name']);
        $this->teamModel->setDescription($data['description'] ?? '');
        $this->teamModel->setDomaineRecherche($data['domaine_recherche'] ?? '');
        $this->teamModel->setChefEquipeId($data['chef_equipe_id']);
        
        // 3. Appel de la mise à jour en BDD
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
    
    /**
     * Supprimer une équipe
     */
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
    
    /**
     * Ajouter un membre à une équipe
     */
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

    /**
     * Retirer un membre d'une équipe
     */
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
    
    /**
     * Valider les données d'équipe
     */
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
    
    /**
     * Récupérer les utilisateurs éligibles comme chefs d'équipe
     */
    private function getEligibleChefs() {
        // Ici vous pouvez filtrer par rôle si nécessaire
        return $this->userModel->getAll(['statut' => 'actif']);
    }

    /**
     * Récupérer une équipe avec tous ses détails
     */
    public function getall($id) {
        $team = $this->teamModel->getTeamWithDetails($id);
        return $team;
    }

    /**
     * Récupérer les membres d'une équipe
     */
    public function getTeamMembers($id) {
        $members = $this->teamModel->getTeamMembers($id);
        return $members;
    }
    
    /**
     * Récupérer les utilisateurs disponibles pour une équipe
     */
    public function getAvailableUsers($id) {
        $availableUsers = $this->teamModel->getAvailableUsers($id);
        return $availableUsers;
    }
    
    /**
     * Récupérer toutes les équipes avec leurs détails
     */
    public function getAllTeamsWithDetails() {
        $teams = $this->teamModel->getAllTeamsWithDetails();
        return $teams;
    }
    
    /**
     * Récupérer les équipements d'une équipe
     */
    public function getTeamEquipments($team_id) {
        return $this->teamModel->getTeamEquipments($team_id);
    }
    
    /**
     * Récupérer les équipements disponibles pour une équipe
     */
    public function getAvailableForTeam($team_id) {
        return $this->teamModel->getAvailableForTeam($team_id);
    }
    
    /**
     * Assigner un équipement à une équipe
     */
    public function assignEquipment($team_id, $equipment_id) {
        return $this->teamModel->assignEquipment($team_id, $equipment_id);      
    }
    
    /**
     * Retirer un équipement d'une équipe
     */
    public function removeEquipment($team_id, $equipment_id) {
        return $this->teamModel->unassignEquipment($team_id, $equipment_id);      
    }
    
    /**
     * Afficher les détails d'une équipe avec toutes les informations
     */
   public function indexWithTeamDetails($teamId) {
        try {
            // 1. Validation de session (Sécurité)
            if (session_status() === PHP_SESSION_NONE) session_start();
            
            $team = $this->getall($teamId);
            $members = $this->getTeamMembers($teamId);
            $publications = $this->getTeamPublications($teamId);
            $equipments = $this->getTeamEquipments($teamId);
            
            if (!$team) {
                exit;
            }

            // 3. AJOUT : Récupération des données globales pour le Header/Footer
            $config = $this->settingsModel->getAllSettings();
            $menu = $this->menuModel->getMenuTree();
            
            // 4. Préparer les données pour la vue
            $data = [
                'title' => 'Détails de l\'équipe - ' . ($team['nom'] ?? ''), // Titre pour le header
                'team' => $team,
                'members' => $members,
                'publications' => $publications,
                'equipments' => $equipments,
                'config' => $config, // Ajouté
                'menu' => $menu      // Ajouté
            ];
            
            // 5. MODIFICATION : Appel de la Classe Vue au lieu du require simple
            require_once __DIR__ . '/../views/team-details.php';
            $view = new TeamDetailsView($data);
            $view->render();
            
            return $data; // On peut garder le return si utilisé ailleurs
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'errors'  => ["Erreur lors de la récupération des détails : " . $e->getMessage()]
            ];
        }
    }
   public function indexAdmin() {
        // 1. Récupération des données existantes
        $teams = $this->teamsModel->getAllTeamsWithDetails();
        $users = $this->userModel->getAll();
        
        // Récupération des stats (Projets/Pubs)
        $projects = $this->projectModel->getAll(); 
        $publicationData = $this->publicationModel->getAll();
        
    

        // 2. AJOUT : Récupération des données globales (Header/Footer)
        $config = $this->settingsModel->getAllSettings();
        $menu = $this->menuModel->getMenuTree();

        // 3. Préparation des données
        $data = [
            'title' => 'Gestion des Équipes', // Titre pour le Header
            'teams' => $teams,
            'users' => $users,
            'projects' => $projects,
            'publicationData' => $publicationData,
            // Données ajoutées pour la Vue
            'config' => $config,
            'menu' => $menu
        ];

        // 4. Appel de la Vue Classe
        require_once __DIR__ . '/../views/TeamManagement.php';
        $view = new TeamAdminView($data);
        $view->render();

        return $data;
    }
}
