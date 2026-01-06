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

class TeamController {
   
     private $teamModel;
    private $orgModel;
    private $settingsModel;
    private $menuModel;

    
    public function __construct() {
        $this->teamModel = new TeamsModel();
        $this->orgModel = new OrganigrammeModel();
        $this->settingsModel = new Settings();
        $this->menuModel = new Menu();
    }
    
    /**
     * Afficher la liste de toutes les équipes
     */
    public function index() {
        // Récupérer toutes les équipes avec détails
        $teams = $this->teamModel->getAllTeamsWithDetails();
        $config = $this->settingsModel->getAllSettings();
        $menu = $this->menuModel->getMenuTree();

        // 2. Données Organigramme (Directeur + Arbre)
        $organigramme = [
            'director' => $this->orgModel->getDirector(),
            'tree' => $this->orgModel->getHierarchyTree()
        ];

        // 3. Données Équipes (Structure complexe : Equipe -> Chef -> Membres)
        $teamsList = $this->teamModel->getAllTeams();
        $teamsData = [];
        
        foreach ($teamsList as $team) {
            $teamsData[] = [
                'info' => $team,
                'leader' => $this->teamModel->getTeamLeader($team['id']),
                'members' => $this->teamModel->getTeamMembers($team['id'])
            ];
        }

        // 4. Tous les membres pour le filtre (Optionnel)
        $allMembers = $this->teamModel->getAllMembersFlat();

        $data = [
            'config' => $config,
            'menu' => $menu,
            'organigramme' => $organigramme,
            'teams' => $teamsData,
            'allMembers' => $allMembers
        ];

        return $data;
    
        
      
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
        require_once __DIR__ . 'views/teams/create.php';
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
            header('Location: /teams/create');
            exit;
        }
        
        // Créer l'équipe
        $this->teamModel->setName($_POST['name']);
        $this->teamModel->setDescription($_POST['description']);
        $this->teamModel->setDomaineRecherche($_POST['domaine_recherche']);
        $this->teamModel->setChefEquipeId($_POST['chef_equipe_id']);
        
        if ($this->teamModel->create()) {
            $_SESSION['success'] = 'Équipe créée avec succès.';
            header('Location: /teams');
            exit;
        } else {
            $_SESSION['error'] = 'Erreur lors de la création de l\'équipe.';
            header('Location: /teams/create');
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
            header('Location: /teams');
            exit;
        }
        
        // Récupérer les membres
        $members = $this->teamModel->getTeamMembers($team_id);
        
        // Récupérer les utilisateurs disponibles pour ajout
        $availableUsers = $this->teamModel->getAvailableUsers($team_id);
        
        // Charger la vue
        require_once __DIR__ . 'views/teams/show.php';
    }
    
    /**
     * Afficher le formulaire de modification
     */
    public function edit($team_id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->update($team_id);
        }
        
        $team = $this->teamModel->getTeamById($team_id);
        
        if (!$team) {
            $_SESSION['error'] = 'Équipe introuvable.';
            header('Location: /teams');
            exit;
        }
        
        $users = $this->getEligibleChefs();
        
        // Charger la vue
    }
    public function getTeamPublications($teamId) {
        return $this->teamModel->getTeamPublications($teamId);
    }
    public function getTeamEquipement($team_id) {
        return $this->teamModel->getTeamEquipments($team_id);
    }
    
    /**
     * Mettre à jour une équipe
     */
    private function update($team_id) {
        // Validation
        $errors = $this->validateTeamData($_POST);
        
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = $_POST;
            header('Location: /teams/edit/' . $team_id);
            exit;
        }
        
        // Mettre à jour
        $this->teamModel->setName($_POST['name']);
        $this->teamModel->setDescription($_POST['description']);
        $this->teamModel->setDomaineRecherche($_POST['domaine_recherche']);
        $this->teamModel->setChefEquipeId($_POST['chef_equipe_id']);
        
        if ($this->teamModel->update($team_id)) {
            $_SESSION['success'] = 'Équipe mise à jour avec succès.';
            header('Location: /teams/' . $team_id);
            exit;
        } else {
            $_SESSION['error'] = 'Erreur lors de la mise à jour.';
            header('Location: /teams/edit/' . $team_id);
            exit;
        }
    }
    
    /**
     * Supprimer une équipe
     */
    public function delete($team_id) {
        if ($this->teamModel->delete($team_id)) {
            $_SESSION['success'] = 'Équipe supprimée avec succès.';
        } else {
            $_SESSION['error'] = 'Erreur lors de la suppression.';
        }
        
        header('Location: /teams');
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
        $userModel = new UserModel();
        return $userModel->getAll(['statut' => 'actif']);
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
public function getAvailableForTeam($team_id){
    return $this->teamModel->getAvailableForTeam($team_id);
}
// public function getAvailableTeamEquipments($team_id) {
//     return $this->teamModel->getAvailableTeamEquipments($team_id);
// }
public function assignEquipment($team_id, $equipment_id) {
    return $this->teamModel->assignEquipment($team_id, $equipment_id);      
}
public function removeEquipment($team_id, $equipment_id) {
    return $this->teamModel->unassignEquipment($team_id, $equipment_id);      

}

}