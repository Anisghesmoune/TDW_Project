<?php
require_once __DIR__ . '/../models/ProjectModel.php';
require_once __DIR__ .  '/../models/Menu.php';
require_once __DIR__ . '/../models/Settings.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../views/public/ProjectView.php';
require_once __DIR__ . '/../models/TeamsModel.php';
require_once __DIR__ . '/../controllers/EventController.php';
require_once __DIR__ . '/../controllers/PublicationController.php';




class ProjectController {
    private $project;
    private $errors = [];
    private $successMessage = '';
    private $userModel;
    private $settingsModel;
    private $menuModel;
    private $teamModel;

    public function __construct() {
        $this->project = new Project();
        $this->userModel = new UserModel();
        $this->settingsModel = new Settings();
        $this->menuModel = new Menu();
        $this->teamModel = new TeamsModel();
    }

    
   

    private function validateProjectData($data, $isUpdate = false) {
        $this->errors = [];

        if (empty($data['titre'])) {
            $this->errors[] = "Le titre du projet est obligatoire.";
        } elseif (strlen($data['titre']) < 3) {
            $this->errors[] = "Le titre doit contenir au moins 3 caractères.";
        } elseif (strlen($data['titre']) > 255) {
            $this->errors[] = "Le titre ne peut pas dépasser 255 caractères.";
        }

        if (empty($data['responsable_id'])) {
            $this->errors[] = "Le responsable du projet est obligatoire.";
        } elseif (!is_numeric($data['responsable_id']) || $data['responsable_id'] <= 0) {
            $this->errors[] = "ID du responsable invalide.";
        }

      
        return empty($this->errors);
    }

  
    private function isValidDate($date, $format = 'Y-m-d') {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

   
   public function index() {
    try {

        $filters = [
             'search' => isset($_GET['search']) ? trim($_GET['search']) : null,
            'statut' => isset($_GET['statut']) ? trim($_GET['statut']) : null,
            
            'thematique' => !empty($_GET['thematique']) ? $_GET['thematique'] : null,
            'responsable' => !empty($_GET['responsable']) ? $_GET['responsable'] : null
        ];

        $projects = $this->project->getPublicProjects($filters);

        $thematics = $this->project->getAllThematics();
        $leaders   = $this->userModel->getAll();

        $config = $this->settingsModel->getAllSettings();
        $menu   = $this->menuModel->getMenuTree();

        $data = [
            'projects' => $projects,
            'filters_data' => [
                'thematics' => $thematics,
                'leaders' => $leaders
            ],
            'active_filters' => $filters,
            'config' => $config,
            'menu' => $menu,
            'can_create' => $canCreate = $this->canCreateProject()
        ];

        $view = new ProjectsView($data);
        $view->render();

        return [
            'success' => true,
            'data' => $projects,
            'message' => count($projects) . ' projet(s) trouvé(s).'
        ];

    } catch (Exception $e) {
        $this->errors[] = "Erreur lors de la récupération des projets: " . $e->getMessage();

        return [
            'success' => false,
            'errors' => $this->errors
        ];
    }
}
public function apiGetProjects() {
        header('Content-Type: application/json');

        try {
            $filters = [
                'search'      => isset($_GET['search']) ? trim($_GET['search']) : null,
                'statut'      => isset($_GET['statut']) ? trim($_GET['statut']) : null,
                'thematique'  => !empty($_GET['thematique']) ? $_GET['thematique'] : null,
                'responsable' => !empty($_GET['responsable']) ? $_GET['responsable'] : null
            ];

            $projects = $this->project->getPublicProjects($filters);

            echo json_encode([
                'success' => true,
                'data' => $projects,
                'count' => count($projects)
            ]);
            
            exit;

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => "Erreur serveur : " . $e->getMessage()
            ]);
            exit;
        }
    }

   
    public function indexWithUsers() {
        try {
            $projects = $this->project->getAllProjectsWithUsers();
            return [
                'success' => true,
                'data' => $projects,
                'message' => count($projects) . ' projet(s) avec utilisateurs trouvé(s).'
            ];
        } catch (Exception $e) {
            $this->errors[] = "Erreur lors de la récupération des projets: " . $e->getMessage();
            return [
                'success' => false,
                'errors' => $this->errors
            ];
        }
    }

 
    public function show($id) {
        try {
            if (!is_numeric($id) || $id <= 0) {
                $this->errors[] = "ID de projet invalide.";
                return [
                    'success' => false,
                    'errors' => $this->errors
                ];
            }

            $result = $this->project->getById($id);
            
            if (empty($result)) {
                $this->errors[] = "Projet introuvable.";
                return [
                    'success' => false,
                    'errors' => $this->errors
                ];
            }

            return [
                'success' => true,
                'data' => $result[0],
                'message' => 'Projet récupéré avec succès.'
            ];
        } catch (Exception $e) {
            $this->errors[] = "Erreur lors de la récupération du projet: " . $e->getMessage();
            return [
                'success' => false,
                'errors' => $this->errors
            ];
        }
    }

 
    private function canCreateProject() {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_SESSION['user_id'])) return false;

        if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1) return true;

        $user = $this->userModel->getById($_SESSION['user_id']);
        if (!$user) return false;

        $role = $user['role']; 
        $grade = $user['grade']; 
        $rolesAutorises = ['enseignant', 'doctorant'];
        $gradesAutorises = ['Professeur', 'MCA']; 

        if (in_array($role, $rolesAutorises) && in_array($grade, $gradesAutorises)) {
            return true;
        }

        return false;
    }


    public function create($data) {
        try {
            if (!$this->canCreateProject()) {
                return ['success' => false, 'message' => "Accès refusé."];
            }

            if (!$this->validateProjectData($data)) {
                return ['success' => false, 'errors' => $this->errors];
            }

            $this->project->setTitre($data['titre']);
            $this->project->setDescription($data['description']);
            $this->project->setTypeFinancement($data['type_financement']);
            $this->project->setStatut($data['statut'] ?? 'soumis');
            $this->project->setDateDebut($data['date_debut']);
            $this->project->setDateFin(!empty($data['date_fin']) ? $data['date_fin'] : null);
            
            $responsableId = !empty($data['responsable_id']) ? $data['responsable_id'] : $_SESSION['user_id'];
            $this->project->setResponsableId($responsableId);
            
            $this->project->setIdEquipe(!empty($data['id_equipe']) ? $data['id_equipe'] : null);

            if ($this->project->create()) {
                
                
                $newProjectId = $this->project->getLastInsertedId(); 

                if ($newProjectId) {
                    $this->project->addUserToProject($newProjectId, $responsableId);
                }

                return [
                    'success' => true,
                    'message' => 'Projet créé avec succès.'
                ];
            } else {
                return ['success' => false, 'message' => "Erreur technique lors de la création."];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => "Exception: " . $e->getMessage()];
        }
    }

    public function update($id, $data) {
        try {
           
            if (!is_numeric($id) || $id <= 0) {
                $this->errors[] = "ID de projet invalide.";
                return [
                    'success' => false,
                    'errors' => $this->errors
                ];
            }

            $existingProject = $this->project->getById($id);
            if (empty($existingProject)) {
                $this->errors[] = "Projet introuvable.";
                return [
                    'success' => false,
                    'errors' => $this->errors
                ];
            }

            if (!$this->validateProjectData($data, true)) {
                return [
                    'success' => false,
                    'errors' => $this->errors
                ];
            }

            $this->project->setId($id);
            $this->project->setTitre($data['titre']);
            $this->project->setDescription($data['description']);
            $this->project->setTypeFinancement($data['type_financement']);
            $this->project->setStatut($data['statut']);
            $this->project->setDateDebut($data['date_debut']);
            $this->project->setDateFin($data['date_fin'] ?? null);
            $this->project->setResponsableId($data['responsable_id']);
            $this->project->setIdEquipe($data['id_equipe'] ?? null);

            if ($this->project->update()) {
                return [
                    'success' => true,
                    'message' => 'Projet mis à jour avec succès.'
                ];
            } else {
                $this->errors[] = "Erreur lors de la mise à jour du projet.";
                return [
                    'success' => false,
                    'errors' => $this->errors
                ];
            }
        } catch (Exception $e) {
            $this->errors[] = "Erreur lors de la mise à jour du projet: " . $e->getMessage();
            return [
                'success' => false,
                'errors' => $this->errors
            ];
        }
    }

    
    public function delete($id) {
        try {
           
            if (!is_numeric($id) || $id <= 0) {
                $this->errors[] = "ID de projet invalide.";
                return [
                    'success' => false,
                    'errors' => $this->errors
                ];
            }

            $existingProject = $this->project->getById($id);
            if (empty($existingProject)) {
                $this->errors[] = "Projet introuvable.";
                return [
                    'success' => false,
                    'errors' => $this->errors
                ];
            }

            $this->project->setId($id);
            if ($this->project->delete($id)) {
                return [
                    'success' => true,
                    'message' => 'Projet supprimé avec succès.'
                ];
            } else {
                $this->errors[] = "Erreur lors de la suppression du projet.";
                return [
                    'success' => false,
                    'errors' => $this->errors
                ];
            }
        } catch (Exception $e) {
            $this->errors[] = "Erreur lors de la suppression du projet: " . $e->getMessage();
            return [
                'success' => false,
                'errors' => $this->errors
            ];
        }
    }

  
    public function countActive() {
        try {
            $count = $this->project->countActive();
            return [
                'success' => true,
                'data' => ['count' => $count],
                'message' => $count . ' projet(s) actif(s).'
            ];
        } catch (Exception $e) {
            $this->errors[] = "Erreur lors du comptage: " . $e->getMessage();
            return [
                'success' => false,
                'errors' => $this->errors
            ];
        }
    }

   
    public function getByStatut($statut) {
        try {
            $statuts_valides = ['planifie', 'en_cours', 'termine', 'suspendu', 'annule'];
            if (!in_array($statut, $statuts_valides)) {
                $this->errors[] = "Statut invalide.";
                return [
                    'success' => false,
                    'errors' => $this->errors
                ];
            }

            $projects = $this->project->getByStatut($statut);
            return [
                'success' => true,
                'data' => $projects,
                'message' => count($projects) . ' projet(s) avec le statut "' . $statut . '".'
            ];
        } catch (Exception $e) {
            $this->errors[] = "Erreur lors de la récupération: " . $e->getMessage();
            return [
                'success' => false,
                'errors' => $this->errors
            ];
        }
    }

  
    public function getByResponsable($responsable_id) {
        try {
            if (!is_numeric($responsable_id) || $responsable_id <= 0) {
                $this->errors[] = "ID de responsable invalide.";
                return [
                    'success' => false,
                    'errors' => $this->errors
                ];
            }

            $projects = $this->project->getByResponsableId($responsable_id);
            return [
                'success' => true,
                'data' => $projects,
                'message' => count($projects) . ' projet(s) trouvé(s).'
            ];
        } catch (Exception $e) {
            $this->errors[] = "Erreur lors de la récupération: " . $e->getMessage();
            return [
                'success' => false,
                'errors' => $this->errors
            ];
        }
    }

    
    public function getByEquipe($id_equipe) {
        try {
            if (!is_numeric($id_equipe) || $id_equipe <= 0) {
                $this->errors[] = "ID d'équipe invalide.";
                return [
                    'success' => false,
                    'errors' => $this->errors
                ];
            }

            $projects = $this->project->getByEquipeId($id_equipe);
            return [
                'success' => true,
                'data' => $projects,
                'message' => count($projects) . ' projet(s) trouvé(s).'
            ];
        } catch (Exception $e) {
            $this->errors[] = "Erreur lors de la récupération: " . $e->getMessage();
            return [
                'success' => false,
                'errors' => $this->errors
            ];
        }
    }

    /**
     * Rechercher des projets
     */
    public function search($keyword) {
        try {
            if (empty($keyword)) {
                $this->errors[] = "Le mot-clé de recherche est requis.";
                return [
                    'success' => false,
                    'errors' => $this->errors
                ];
            }

            if (strlen($keyword) < 3) {
                $this->errors[] = "Le mot-clé doit contenir au moins 3 caractères.";
                return [
                    'success' => false,
                    'errors' => $this->errors
                ];
            }

            $projects = $this->project->searchProjects($keyword);
            return [
                'success' => true,
                'data' => $projects,
                'message' => count($projects) . ' projet(s) trouvé(s).'
            ];
        } catch (Exception $e) {
            $this->errors[] = "Erreur lors de la recherche: " . $e->getMessage();
            return [
                'success' => false,
                'errors' => $this->errors
            ];
        }
    }

  
    public function addUser($projectId, $userId) {
        try {
          

            if (!is_numeric($projectId) || $projectId <= 0) {
                $this->errors[] = "ID de projet invalide.";
            }
            if (!is_numeric($userId) || $userId <= 0) {
                $this->errors[] = "ID d'utilisateur invalide.";
            }

            if (!empty($this->errors)) {
                return [
                    'success' => false,
                    'errors' => $this->errors
                ];
            }

            if ($this->project->addUserToProject($projectId, $userId)) {
                return [
                    'success' => true,
                    'message' => 'Utilisateur ajouté au projet avec succès.'
                ];
            } else {
                $this->errors[] = "Erreur lors de l'ajout de l'utilisateur (peut-être déjà associé).";
                return [
                    'success' => false,
                    'errors' => $this->errors
                ];
            }
        } catch (Exception $e) {
            $this->errors[] = "Erreur lors de l'ajout de l'utilisateur: " . $e->getMessage();
            return [
                'success' => false,
                'errors' => $this->errors
            ];
        }
    }

   
    public function removeUser($projectId, $userId) {
        try {
          

            if (!is_numeric($projectId) || $projectId <= 0) {
                $this->errors[] = "ID de projet invalide.";
            }
            if (!is_numeric($userId) || $userId <= 0) {
                $this->errors[] = "ID d'utilisateur invalide.";
            }

            if (!empty($this->errors)) {
                return [
                    'success' => false,
                    'errors' => $this->errors
                ];
            }

            if ($this->project->removeUserFromProject($projectId, $userId)) {
                return [
                    'success' => true,
                    'message' => 'Utilisateur retiré du projet avec succès.'
                ];
            } else {
                $this->errors[] = "Erreur lors du retrait de l'utilisateur.";
                return [
                    'success' => false,
                    'errors' => $this->errors
                ];
            }
        } catch (Exception $e) {
            $this->errors[] = "Erreur lors du retrait de l'utilisateur: " . $e->getMessage();
            return [
                'success' => false,
                'errors' => $this->errors
            ];
        }
    }

  
    public function getProjectUsers($projectId) {
        try {
            if (!is_numeric($projectId) || $projectId <= 0) {
                $this->errors[] = "ID de projet invalide.";
                return [
                    'success' => false,
                    'errors' => $this->errors
                ];
            }

            $users = $this->project->getProjectUsers($projectId);
            return [
                'success' => true,
                'data' => $users,
                'message' => count($users) . ' utilisateur(s) trouvé(s).'
            ];
        } catch (Exception $e) {
            $this->errors[] = "Erreur lors de la récupération: " . $e->getMessage();
            return [
                'success' => false,
                'errors' => $this->errors
            ];
        }
    }

    
    public function addPublication($projectId, $publicationId) {
        try {
          
            if (!is_numeric($projectId) || $projectId <= 0) {
                $this->errors[] = "ID de projet invalide.";
            }
            if (!is_numeric($publicationId) || $publicationId <= 0) {
                $this->errors[] = "ID de publication invalide.";
            }

            if (!empty($this->errors)) {
                return [
                    'success' => false,
                    'errors' => $this->errors
                ];
            }

            if ($this->project->addPublicationToProject($projectId, $publicationId)) {
                return [
                    'success' => true,
                    'message' => 'Publication ajoutée au projet avec succès.'
                ];
            } else {
                $this->errors[] = "Erreur lors de l'ajout de la publication.";
                return [
                    'success' => false,
                    'errors' => $this->errors
                ];
            }
        } catch (Exception $e) {
            $this->errors[] = "Erreur lors de l'ajout: " . $e->getMessage();
            return [
                'success' => false,
                'errors' => $this->errors
            ];
        }
    }

   
    public function addThematic($projectId, $thematicId) {
        try {
         
            if (!is_numeric($projectId) || $projectId <= 0) {
                $this->errors[] = "ID de projet invalide.";
            }
            if (!is_numeric($thematicId) || $thematicId <= 0) {
                $this->errors[] = "ID de thématique invalide.";
            }

            if (!empty($this->errors)) {
                return [
                    'success' => false,
                    'errors' => $this->errors
                ];
            }

            if ($this->project->addThematicToProject($projectId, $thematicId)) {
                return [
                    'success' => true,
                    'message' => 'Thématique ajoutée au projet avec succès.'
                ];
            } else {
                $this->errors[] = "Erreur lors de l'ajout de la thématique.";
                return [
                    'success' => false,
                    'errors' => $this->errors
                ];
            }
        } catch (Exception $e) {
            $this->errors[] = "Erreur lors de l'ajout: " . $e->getMessage();
            return [
                'success' => false,
                'errors' => $this->errors
            ];
        }
    }

   
    public function getErrors() {
        return $this->errors;
    }

   
    public function getSuccessMessage() {
        return $this->successMessage;
    }
    public function getAll(){
        return $this->project->getAll();
    }
  
 public function generatePDF() {
        if (ob_get_length()) ob_clean();
        require_once __DIR__ . '/../libs/PDFReport.php';

        $filterType = $_REQUEST['filterType'] ?? 'all';
        $filterValue = $_REQUEST['filterValue'] ?? null;
        
        $filterName = $_REQUEST['filterLabel'] ?? ''; 

        $data = $this->getProjectsForReport($filterType, $filterValue);

        if (empty($data)) {
            die("Aucun projet trouvé.");
        }

        if ($filterType === 'responsable' && !empty($filterName)) {
            foreach ($data as &$row) {
                $row['responsable_nom'] = $filterName; 
                $row['responsable_prenom'] = ''; 
                $row['responsable'] = $filterName; 
            }
        }
        
        $title = "Liste des projets";
        if ($filterType === 'year') {
            $title = "Projets - Année " . $filterName;
        } elseif ($filterType === 'responsable') {
            $title = "Projets dirigés par : " . $filterName;
        } elseif ($filterType === 'thematique') {
            $title = "Thématique : " . $filterName;
        }

        $pdf = new PDFReport();
        $pdf->AliasNbPages();
        $pdf->AddPage();
        
        $pdf->setReportTitle(iconv('UTF-8', 'windows-1252//TRANSLIT', $title));
        $pdf->setFilterInfo(iconv('UTF-8', 'windows-1252', "Généré le " . date('d/m/Y')));
        
        $pdf->ProjectTable($data);
        
        $pdf->Output('D', 'Rapport.pdf');
        exit;
    }

   
   
    private function getProjectsForReport($type, $value) {
        if ($type === 'all' || empty($value)) {
            return $this->project->getAll();
        }

        switch ($type) {
            case 'year':
                $all = $this->project->getAll();
                $filtered = [];
                foreach ($all as $p) {
                    if (!empty($p['date_debut'])) {
                        $y = date('Y', strtotime($p['date_debut']));
                        if ($y == $value) {
                            $filtered[] = $p;
                        }
                    }
                }
                return $filtered;

            case 'responsable':
                return $this->project->getByResponsableId($value);

            case 'thematique':
                return $this->project->getProjectsByThematicId($value);

            default:
                return $this->project->getAll();
        }
    }


 public function details($id) {
        

        $project = $this->project->getProjectDetails($id);
        
        if (!$project) {
            die("Projet introuvable.");
        }

        $members = $this->project->getProjectMembers($id);
        $publications = $this->project->getProjectPublications($id);
        
        $config = $this->settingsModel->getAllSettings();
        $menu = $this->menuModel->getMenuTree();

       
        $data = [
            'project' => $project,
            'members' => $members,
            'publications' => $publications,
            'config' => $config,
            'menu' => $menu
        ];

        require_once __DIR__ . '/../views/public/project-detailsView.php';
         $view = new ProjectDetailsView($data); 
        $view->render();
    }
   public function indexProject() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?route=login');
            exit;
        }

    

        try {
            $projects = $this->project->getAll();
            $nbProjetsActifs = $this->project->countActive();
            $teams = $this->teamModel->getAllTeamsWithDetails();
            $users = $this->userModel->getAll();

            $eventController = new EventController();
            $events = $eventController->getAll();
            
            $publicationController = new PublicationController();
            $publications = $publicationController->stats();

            $config = $this->settingsModel->getAllSettings();
            $menu = $this->menuModel->getMenuTree();

            $data = [
                'title' => 'Gestion des Projets',
                'config' => $config,
                'menu' => $menu,
                'projects' => $projects,
                'nbProjetsActifs' => $nbProjetsActifs,
                'teams' => $teams,
                'users' => $users,
                'events' => $events,
                'publications' => $publications
            ];

            require_once __DIR__ . '/../views/project_management.php';
            $view = new ProjectAdminView($data);
            $view->render();

        } catch (Exception $e) {
            echo "Erreur lors du chargement des projets : " . $e->getMessage();
        }
    }
 public function projectDetails($id) {
        if (!$id || !is_numeric($id) || $id <= 0) {
            header('Location: project_management.php'); 
            exit;
        }

        $statsByThematic = $this->project->getProjectsByThematic();
        $statsByResponsable = $this->project->getProjectsByResponsable();
        $statsByYear = $this->project->getProjectsByYear();
        $statsByFinancement = $this->project->getProjectsByFinancement();
    
        $statsByTeam = $this->project->getByEquipeId($id); 
        $advancedStats = $this->project->getAdvancedStats();
        $topProjects = $this->project->getTopProjectsByMembers();
        $recentProjects = $this->project->getRecentProjects(30);
        
        $project = $this->project->getProjectDetails($id);
        
        if (!$project) {
            die("Projet introuvable.");
        }
        
        $members = $this->project->getProjectMembers($id);
        $publications = $this->project->getProjectPublications($id);
        
        $config = $this->settingsModel->getAllSettings();
        $menu = $this->menuModel->getMenuTree();
        
        $data = [
            'title' => 'Détails du Projet', 
            'project' => $project,
            'members' => $members,
            'publications' => $publications,
            'statsByThematic' => $statsByThematic,
            'statsByResponsable' => $statsByResponsable,
            'statsByYear' => $statsByYear,
            'statsByFinancement' => $statsByFinancement,
            'statsByTeam' => $statsByTeam,
            'advancedStats' => $advancedStats,
            'topProjects' => $topProjects,
            'recentProjects' => $recentProjects,
            'config' => $config,
            'menu' => $menu
        ];
        
        require_once __DIR__ . '/../views/project-details.php';
        $view = new ProjectDetailsView($data);
        $view->render();
    }
}