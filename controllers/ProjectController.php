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

    
    /**
     * Vérifier le rôle admin (commenté pour le moment)
     */
    private function checkAdminRole() {
        // TODO: Implémenter la vérification du rôle admin
        // if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        //     $this->errors[] = "Accès refusé. Vous devez être administrateur.";
        //     return false;
        // }
        return true;
    }

    /**
     * Valider les données du projet
     */
    private function validateProjectData($data, $isUpdate = false) {
        $this->errors = [];

        // Validation du titre
        if (empty($data['titre'])) {
            $this->errors[] = "Le titre du projet est obligatoire.";
        } elseif (strlen($data['titre']) < 3) {
            $this->errors[] = "Le titre doit contenir au moins 3 caractères.";
        } elseif (strlen($data['titre']) > 255) {
            $this->errors[] = "Le titre ne peut pas dépasser 255 caractères.";
        }

        // Validation du responsable
        if (empty($data['responsable_id'])) {
            $this->errors[] = "Le responsable du projet est obligatoire.";
        } elseif (!is_numeric($data['responsable_id']) || $data['responsable_id'] <= 0) {
            $this->errors[] = "ID du responsable invalide.";
        }

        // Validation de l'équipe (optionnel)
      
        return empty($this->errors);
    }

    /**
     * Vérifier si une date est valide
     */
    private function isValidDate($date, $format = 'Y-m-d') {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    /**
     * Afficher tous les projets
     */
   public function index() {
    try {

        // 1. Récupération des filtres
        $filters = [
             'search' => isset($_GET['search']) ? trim($_GET['search']) : null,
            'statut' => isset($_GET['statut']) ? trim($_GET['statut']) : null,
            
            'thematique' => !empty($_GET['thematique']) ? $_GET['thematique'] : null,
            'responsable' => !empty($_GET['responsable']) ? $_GET['responsable'] : null
        ];

        // 2. Récupération des projets filtrés
        $projects = $this->project->getPublicProjects($filters);

        // 3. Données pour les filtres
        $thematics = $this->project->getAllThematics();
        $leaders   = $this->userModel->getAll();

        // 4. Configuration globale
        $config = $this->settingsModel->getAllSettings();
        $menu   = $this->menuModel->getMenuTree();

        // 5. Préparation des données pour la vue
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

        // 6. Rendu de la vue
        $view = new ProjectsView($data);
        $view->render();

        // 7. Retour optionnel (si API ou besoin logique)
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

    /**
     * Afficher tous les projets avec leurs utilisateurs
     */
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

    /**
     * Afficher un projet par ID
     */
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

    /**
     * Créer un nouveau projet
     */
    /**
     * Vérifie si l'utilisateur a le droit de créer un projet
     */
    private function canCreateProject() {
        if (session_status() === PHP_SESSION_NONE) session_start();

        // 1. Si pas connecté -> Non
        if (!isset($_SESSION['user_id'])) return false;

        // 2. Si Admin -> Oui
        if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1) return true;

        // 3. Récupération des infos fraîches de l'utilisateur (pour le grade)
        $user = $this->userModel->getById($_SESSION['user_id']);
        if (!$user) return false;

        $role = $user['role']; 
        $grade = $user['grade']; // Assurez-vous que la colonne 'grade' existe dans la BDD

        // 4. Règles métier
        $rolesAutorises = ['enseignant', 'doctorant'];
        $gradesAutorises = ['Professeur', 'MCA']; // MCA = Maître de Conférences A

        if (in_array($role, $rolesAutorises) && in_array($grade, $gradesAutorises)) {
            return true;
        }

        return false;
    }

    /**
     * Créer un nouveau projet
     */
   /**
     * Créer un nouveau projet
     */
    public function create($data) {
        try {
            if (!$this->canCreateProject()) {
                return ['success' => false, 'message' => "Accès refusé."];
            }

            if (!$this->validateProjectData($data)) {
                return ['success' => false, 'errors' => $this->errors];
            }

            // Définir les propriétés
            $this->project->setTitre($data['titre']);
            $this->project->setDescription($data['description']);
            $this->project->setTypeFinancement($data['type_financement']);
            $this->project->setStatut($data['statut'] ?? 'soumis');
            $this->project->setDateDebut($data['date_debut']);
            $this->project->setDateFin(!empty($data['date_fin']) ? $data['date_fin'] : null);
            
            // Responsable
            $responsableId = !empty($data['responsable_id']) ? $data['responsable_id'] : $_SESSION['user_id'];
            $this->project->setResponsableId($responsableId);
            
            $this->project->setIdEquipe(!empty($data['id_equipe']) ? $data['id_equipe'] : null);

            // 1. CRÉATION DU PROJET
            if ($this->project->create()) {
                
                // Récupérer l'ID du projet créé
                // ATTENTION : create() dans votre modèle actuel retourne true/false, pas l'ID.
                // Il faut modifier votre modèle pour retourner l'ID (voir étape 3)
                // OU récupérer le dernier ID inséré :
                $newProjectId = $this->project->getLastInsertedId(); 

                // 2. AJOUTER LE RESPONSABLE COMME MEMBRE DU PROJET
                // Table 'user_project' : user_id, project_id, role
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

    /**
     * Mettre à jour un projet
     */
    public function update($id, $data) {
        try {
            // Vérification du rôle admin (commenté)
            // if (!$this->checkAdminRole()) {
            //     return [
            //         'success' => false,
            //         'errors' => $this->errors
            //     ];
            // }

            // Vérifier l'ID
            if (!is_numeric($id) || $id <= 0) {
                $this->errors[] = "ID de projet invalide.";
                return [
                    'success' => false,
                    'errors' => $this->errors
                ];
            }

            // Vérifier si le projet existe
            $existingProject = $this->project->getById($id);
            if (empty($existingProject)) {
                $this->errors[] = "Projet introuvable.";
                return [
                    'success' => false,
                    'errors' => $this->errors
                ];
            }

            // Validation des données
            if (!$this->validateProjectData($data, true)) {
                return [
                    'success' => false,
                    'errors' => $this->errors
                ];
            }

            // Définir les propriétés
            $this->project->setId($id);
            $this->project->setTitre($data['titre']);
            $this->project->setDescription($data['description']);
            $this->project->setTypeFinancement($data['type_financement']);
            $this->project->setStatut($data['statut']);
            $this->project->setDateDebut($data['date_debut']);
            $this->project->setDateFin($data['date_fin'] ?? null);
            $this->project->setResponsableId($data['responsable_id']);
            $this->project->setIdEquipe($data['id_equipe'] ?? null);

            // Mettre à jour le projet
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

    /**
     * Supprimer un projet
     */
    public function delete($id) {
        try {
            // Vérification du rôle admin (commenté)
            // if (!$this->checkAdminRole()) {
            //     return [
            //         'success' => false,
            //         'errors' => $this->errors
            //     ];
            // }

            // Vérifier l'ID
            if (!is_numeric($id) || $id <= 0) {
                $this->errors[] = "ID de projet invalide.";
                return [
                    'success' => false,
                    'errors' => $this->errors
                ];
            }

            // Vérifier si le projet existe
            $existingProject = $this->project->getById($id);
            if (empty($existingProject)) {
                $this->errors[] = "Projet introuvable.";
                return [
                    'success' => false,
                    'errors' => $this->errors
                ];
            }

            // Supprimer le projet
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

    /**
     * Compter les projets actifs
     */
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

    /**
     * Récupérer les projets par statut
     */
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

    /**
     * Récupérer les projets par responsable
     */
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

    /**
     * Récupérer les projets par équipe
     */
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

    /**
     * Ajouter un utilisateur à un projet
     */
    public function addUser($projectId, $userId) {
        try {
            // Vérification du rôle admin (commenté)
            // if (!$this->checkAdminRole()) {
            //     return [
            //         'success' => false,
            //         'errors' => $this->errors
            //     ];
            // }

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

    /**
     * Retirer un utilisateur d'un projet
     */
    public function removeUser($projectId, $userId) {
        try {
            // Vérification du rôle admin (commenté)
            // if (!$this->checkAdminRole()) {
            //     return [
            //         'success' => false,
            //         'errors' => $this->errors
            //     ];
            // }

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

    /**
     * Obtenir les utilisateurs d'un projet
     */
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

    /**
     * Ajouter une publication à un projet
     */
    public function addPublication($projectId, $publicationId) {
        try {
            // Vérification du rôle admin (commenté)
            // if (!$this->checkAdminRole()) {
            //     return [
            //         'success' => false,
            //         'errors' => $this->errors
            //     ];
            // }

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

    /**
     * Ajouter une thématique à un projet
     */
    public function addThematic($projectId, $thematicId) {
        try {
            // Vérification du rôle admin (commenté)
            // if (!$this->checkAdminRole()) {
            //     return [
            //         'success' => false,
            //         'errors' => $this->errors
            //     ];
            // }

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

    /**
     * Obtenir les erreurs
     */
    public function getErrors() {
        return $this->errors;
    }

    /**
     * Obtenir le message de succès
     */
    public function getSuccessMessage() {
        return $this->successMessage;
    }
    public function getAll(){
        return $this->project->getAll();
    }
    // ... [Le reste de votre code Controller existant] ...

    /**
     * Récupérer les données filtrées pour le rapport
     */
  private function getProjectsForReport($filterType, $filterValue) {
    $projects = [];
    $allProjects = $this->project->getAll();
    
    switch ($filterType) {
        case 'year':
            foreach ($allProjects as $p) {
                $year = date('Y', strtotime($p['date_debut']));
                if ($year == $filterValue) {
                    $projects[] = $p;
                }
            }
            break;
            
        case 'responsable':
            // Filter by responsable ID
            if (is_numeric($filterValue)) {
                foreach ($allProjects as $p) {
                    if ($p['responsable_id'] == $filterValue) {
                        $projects[] = $p;
                    }
                }
            }
            break;
            
        case 'thematique':
            // Get projects for specific thematic
            if (is_numeric($filterValue)) {
                $projects = $this->project->getProjectsByThematicId($filterValue);
            }
            break;
            
        case 'all':
        default:
            $projects = $allProjects;
            break;
    }
    
    return $projects;
}

public function generatePDF($filterType, $filterValue = null) {
    require_once __DIR__ . '/../libs/PDFReport.php';
    
    // 1. Get data
    $data = $this->getProjectsForReport($filterType, $filterValue);
    
    if (empty($data)) {
        // Better error handling
        header('Content-Type: text/html; charset=UTF-8');
        die("Aucun projet trouvé pour ces critères.");
    }
    
    // 2. Define title
    $title = "Liste complète des projets";
    
    if ($filterType === 'year') {
        $title = "Rapport des projets - Année " . $filterValue;
    }
    
    if ($filterType === 'responsable') {
        $respName = $data[0]['responsable_name'] ?? 'Responsable #' . $filterValue;
        $title = "Projets dirigés par : " . $respName;
    }
    
    if ($filterType === 'thematique') {
        $themName = $data[0]['thematic_name'] ?? 'Thématique #' . $filterValue;
        $title = "Projets de la thématique : " . $themName;
    }
    
    // 3. Create PDF
    $pdf = new PDFReport();
    $pdf->AliasNbPages();
    $pdf->AddPage();
    $pdf->setReportTitle($title);
    
    // 4. Generate table
    $pdf->ProjectTable($data);
    
    // 5. Output
    $filename = 'Rapport_Projets_' . date('Y-m-d') . '.pdf';
    $pdf->Output('D', $filename);
    exit;
}
 public function details($id) {
        if (!$id) {
            header('Location: projects.php'); // Redirection si pas d'ID
            exit;
        }

        // 1. Récupération des données
        $project = $this->project->getProjectDetails($id);
        
        if (!$project) {
            die("Projet introuvable.");
        }

        $members = $this->project->getProjectMembers($id);
        $publications = $this->project->getProjectPublications($id);
        
        // 2. Config globale (Header/Footer)
        $config = $this->settingsModel->getAllSettings();
        $menu = $this->menuModel->getMenuTree();

        // 3. Envoi à la vue (Note: on ne crée pas de classe View spécifique pour aller plus vite, on include direct)
        // Mais on prépare les données
        $data = [
            'project' => $project,
            'members' => $members,
            'publications' => $publications,
            'config' => $config,
            'menu' => $menu
        ];

        // On inclut directement le fichier de vue
        require_once __DIR__ . '/../views/public/project-detailsView.php';
    }
   public function indexProject() {
        // 1. Vérification Session & Admin
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?route=login');
            exit;
        }

        // Vérification rôle (Admin ou Directeur)
        // $isAdmin = isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'directeur');
        // if (!$isAdmin) {
        //     header('Location: index.php?route=dashboard-user'); 
        //     exit;
        // }

        try {
            // 2. Récupération des données métier
            $projects = $this->project->getAll();
            $nbProjetsActifs = $this->project->countActive();
            $teams = $this->teamModel->getAllTeamsWithDetails();
            $users = $this->userModel->getAll();

            // Récupération des événements et publications via leurs contrôleurs
            $eventController = new EventController();
            $events = $eventController->getAll();
            
            $publicationController = new PublicationController();
            $publications = $publicationController->stats();

            // 3. Récupération des données globales (Header/Footer)
            $config = $this->settingsModel->getAllSettings();
            $menu = $this->menuModel->getMenuTree();

            // 4. Préparation des données pour la vue
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

            // 5. Chargement de la Vue Classe
            require_once __DIR__ . '/../views/project_management.php';
            $view = new ProjectAdminView($data);
            $view->render();

        } catch (Exception $e) {
            echo "Erreur lors du chargement des projets : " . $e->getMessage();
        }
    }
 public function projectDetails($id) {
        // Validation de l'ID
        if (!$id || !is_numeric($id) || $id <= 0) {
            header('Location: project_management.php'); // Ou index.php?route=project-admin
            exit;
        }

        // Récupération des données statistiques
        $statsByThematic = $this->project->getProjectsByThematic();
        $statsByResponsable = $this->project->getProjectsByResponsable();
        $statsByYear = $this->project->getProjectsByYear();
        $statsByFinancement = $this->project->getProjectsByFinancement();
        // Attention : vérifiez si getByEquipeId attend un ID équipe ou projet. 
        // Je garde votre code tel quel :
        $statsByTeam = $this->project->getByEquipeId($id); 
        $advancedStats = $this->project->getAdvancedStats();
        $topProjects = $this->project->getTopProjectsByMembers();
        $recentProjects = $this->project->getRecentProjects(30);
        
        // Récupération du projet spécifique
        $project = $this->project->getProjectDetails($id);
        
        if (!$project) {
            die("Projet introuvable.");
        }
        
        // Récupération des membres et publications
        $members = $this->project->getProjectMembers($id);
        $publications = $this->project->getProjectPublications($id);
        
        // Configuration globale
        $config = $this->settingsModel->getAllSettings();
        $menu = $this->menuModel->getMenuTree();
        
        // Préparation des données pour la vue
        $data = [
            'title' => 'Détails du Projet', // Ajout pour le Header
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
        
        // CHARGEMENT DE LA VUE CLASSE
        require_once __DIR__ . '/../views/project-details.php';
        $view = new ProjectDetailsView($data);
        $view->render();
    }
}