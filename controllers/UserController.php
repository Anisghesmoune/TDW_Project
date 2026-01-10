<?php

require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../controllers/ProjectController.php';
require_once __DIR__ . '/../controllers/EventController.php';
require_once __DIR__ . '/../controllers/PublicationController.php';
require_once __DIR__ . '/../models/Settings.php';
require_once __DIR__ . '/../models/Menu.php';
require_once __DIR__ . '/../models/TeamsModel.php';

class UserController {
    private $userModel;
    private $settingsModel;
    private $menuModel;
    private $teamsModel;
    
    public function __construct() {
        $this->userModel = new UserModel();
         $this->settingsModel = new Settings();
        $this->menuModel = new Menu();
        $this->teamsModel = new TeamsModel();


    }
    
    /**
     * Liste de tous les utilisateurs (Admin)
     */
    public function index() {
        // Vérifier que l'utilisateur est admin
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            header('Location: /');
            exit;
        }
        
        // Récupérer les filtres depuis l'URL
        $filters = [];
        if (isset($_GET['role'])) {
            $filters['role'] = $_GET['role'];
        }
        if (isset($_GET['statut'])) {
            $filters['statut'] = $_GET['statut'];
        }
        
        // Utiliser directement la méthode du model
        $users = $this->userModel->getAll($filters);
        
        // Préparer les données pour la vue
        $data = [
            'users' => $users,
            'filters' => $filters
        ];
        
        require_once __DIR__ . '/../views/admin/users/index.php';
        
        return $data;
    }
    
    /**
     * Afficher la page de création d'utilisateur
     */
    public function create() {
        // Vérifier que l'utilisateur est admin
        // if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        //     header('Location: /');
        //     exit;
        // }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->store();
        }
        
    }
    
    /**
     * Enregistrer un nouvel utilisateur
     */
    private function store() {
        // Préparer les données
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            echo json_encode(['success' => false, 'message' => 'Données invalides']);
            exit;
        }

        $data = [
            'username' => $input['username'] ?? '',
            'password' => $input['password'] ?? '',
            'nom' => $input['nom'] ?? '',
            'prenom' => $input['prenom'] ?? '',
            'email' => $input['email'] ?? '',
            'role' => $input['role'] ?? 'etudiant',
            'is_admin' => isset($input['is_admin']) && $input['is_admin'] ? 1 : 0,
            'grade' => $input['grade'] ?? null,
            'domaine_recherche' => $input['domaine_recherche'] ?? null
        ];

        // Appeler directement la méthode create du model
        $result = $this->userModel->create($data);
        
        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
            return $this->jsonResponse(['success' => true, 'message' => $result['message']]);
            
        } else {
            $_SESSION['error'] = $result['message'];
            $_SESSION['old'] = $_POST;
            return $this->jsonResponse(['success' => false, 'message' => $result['message']]);
           
        }
        exit;
    }
    
    /**
     * Afficher la page de modification
     */
    public function edit($id) {
        // Vérifier que l'utilisateur est admin
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            header('Location: /');
            exit;
        }
        
        // Utiliser directement getById du model
        $user = $this->userModel->getById($id);
        
        if (!$user) {
            $_SESSION['error'] = 'Utilisateur introuvable.';
            header('Location: /admin/users');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->update($id);
        }
        
        require_once __DIR__ . '/../views/admin/users/edit.php';
    }
    
    /**
     * Mettre à jour un utilisateur
     */
    private function update($id) {
        // Préparer les données
        $data = [
            'nom' => $_POST['nom'] ?? '',
            'prenom' => $_POST['prenom'] ?? '',
            'email' => $_POST['email'] ?? '',
            'grade' => $_POST['grade'] ?? null,
            'domaine_recherche' => $_POST['domaine_recherche'] ?? null,
            'specialite' => $_POST['specialite'] ?? null
        ];
        
        // Utiliser directement updateProfile du model
        $result = $this->userModel->updateProfile($id, $data);
        
        // Si un nouveau mot de passe est fourni
        if (!empty($_POST['password'])) {
            // Pour l'admin, on permet de changer sans l'ancien mot de passe
            $query = "UPDATE users SET password = :password WHERE id = :id";
            $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
            // Vous pourriez ajouter une méthode dans le model : updatePasswordByAdmin()
        }
        
        if ($result) {
            $_SESSION['success'] = 'Utilisateur mis à jour avec succès.';
            header('Location: /admin/users');
        } else {
            $_SESSION['error'] = 'Erreur lors de la mise à jour.';
            header('Location: /admin/users/edit/' . $id);
        }
        exit;
    }
    
    /**
     * Supprimer un utilisateur (soft delete)
     */
    public function delete($id) {
        // Vérifier que l'utilisateur est admin
        // if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        //     $this->jsonResponse(['success' => false, 'message' => 'Accès non autorisé']);
        // }
        
        // Ne pas permettre la suppression de son propre compte
        if ($id == $_SESSION['user_id']) {
            $this->jsonResponse(['success' => false, 'message' => 'Vous ne pouvez pas supprimer votre propre compte']);
        }
        
        // Utiliser directement delete du model
        if ($this->userModel->delete($id)) {
            $this->jsonResponse(['success' => true, 'message' => 'Utilisateur supprimé avec succès']);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Erreur lors de la suppression']);
        }
    }
    
    /**
     * Suspendre un utilisateur
     */
    public function suspend($id) {
        // // Vérifier que l'utilisateur est admin
        // if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        //     $this->jsonResponse(['success' => false, 'message' => 'Accès non autorisé']);
        // }
        
        // Utiliser directement suspend du model
        if ($this->userModel->suspend($id)) {
            $this->jsonResponse(['success' => true, 'message' => 'Utilisateur suspendu']);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Erreur lors de la suspension']);
        }
    }
    
    /**
     * Activer un utilisateur
     */
    public function activate($id) {
        // Vérifier que l'utilisateur est admin
        // if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        //     $this->jsonResponse(['success' => false, 'message' => 'Accès non autorisé']);
        // }
        
        // Utiliser directement activate du model
        if ($this->userModel->activate($id)) {
            $this->jsonResponse(['success' => true, 'message' => 'Utilisateur activé']);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Erreur lors de l\'activation']);
        }
    }
    
    /**
     * API: Récupérer un utilisateur par ID
     */
    public function getUser($id) {
        // Vérifier que l'utilisateur est admin
        // if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        //     $this->jsonResponse(['success' => false, 'message' => 'Accès non autorisé']);
        // }
        
        // Utiliser directement getById du model
        $user = $this->userModel->getById($id);
        
        if (!$user) {
            $this->jsonResponse(['success' => false, 'message' => 'Utilisateur introuvable']);
        }
        
        $this->jsonResponse(['success' => true, 'user' => $user]);
    }
    
    /**
     * API: Mettre à jour un utilisateur (via JSON)
     */
    public function updateUser() {
        // Vérifier que l'utilisateur est admin
        // if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        //     $this->jsonResponse(['success' => false, 'message' => 'Accès non autorisé']);
        // }
        
        // Récupérer les données JSON
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['id']) || empty($data['id'])) {
            $this->jsonResponse(['success' => false, 'message' => 'ID utilisateur manquant']);
        }
        
        $id = $data['id'];
        $photo_profil = $data['photo_profil'] ?? null;
        
        // Vérifier que l'utilisateur existe
        $user = $this->userModel->getById($id);
        if (!$user) {
            $this->jsonResponse(['success' => false, 'message' => 'Utilisateur introuvable']);
        }
        
        // Préparer les données pour updateProfile
        $profileData = [
            'nom' => $data['nom'],
            'prenom' => $data['prenom'],
            'email' => $data['email'],
            'grade' => $data['grade'] ?? null,
            'domaine_recherche' => $data['domaine_recherche'] ?? null,
            'specialite' => $data['specialite'] ?? null
        ];
        
        // Utiliser directement updateProfile du model
        $result = $this->userModel->updateProfile($id, $profileData);
        
        // Mettre à jour la photo si fournie
        if ($photo_profil) {
            $result = $this->userModel->updatePhoto($id, $photo_profil);
        }
        
        // Gérer le mot de passe si fourni (ajouter cette méthode au model si nécessaire)
        if (isset($data['password']) && !empty($data['password'])) {
            // Vous devriez ajouter une méthode updatePasswordByAdmin() dans le model
            // Pour l'instant, on ignore le mot de passe ou on fait un query direct
        }
        
        if ($result) {
            $this->jsonResponse(['success' => true, 'message' => 'Utilisateur mis à jour avec succès']);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Erreur lors de la mise à jour']);
        }
    }
    
    /**
     * Mettre à jour la photo de profil d'un utilisateur
     */
    public function updatePhoto($id) {
        // Vérifier que l'utilisateur est admin (décommenter si nécessaire)
        // if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        //     $this->jsonResponse(['success' => false, 'message' => 'Accès non autorisé']);
        // }
        
        // Vérifier qu'un fichier a été uploadé
        if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            $this->jsonResponse(['success' => false, 'message' => 'Aucun fichier uploadé']);
        }
        
        $file = $_FILES['photo'];
        
        // Vérifier que l'utilisateur existe
        $user = $this->userModel->getById($id);
        if (!$user) {
            $this->jsonResponse(['success' => false, 'message' => 'Utilisateur introuvable']);
        }
        
        // Validation du fichier
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($file['type'], $allowedTypes)) {
            $this->jsonResponse(['success' => false, 'message' => 'Type de fichier non autorisé. Utilisez JPG, PNG ou GIF']);
        }
        
        if ($file['size'] > $maxSize) {
            $this->jsonResponse(['success' => false, 'message' => 'Fichier trop volumineux. Maximum 5MB']);
        }
        
        // Créer le dossier uploads si nécessaire
        $uploadDir = '../uploads/profiles/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Générer un nom de fichier unique
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'profile_' . $id . '_' . time() . '.' . $extension;
        $destination = $uploadDir . $filename;
        
        // Supprimer l'ancienne photo si elle existe
        if ($user['photo_profil'] && file_exists($user['photo_profil'])) {
            unlink($user['photo_profil']);
        }
        
        // Déplacer le fichier uploadé
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            // Mettre à jour la base de données
            $result = $this->userModel->updatePhoto($id, $destination);
            
            if ($result) {
                $this->jsonResponse([
                    'success' => true, 
                    'message' => 'Photo de profil mise à jour avec succès',
                    'photo_url' => $destination
                ]);
            } else {
                // Supprimer le fichier si la BDD échoue
                unlink($destination);
                $this->jsonResponse(['success' => false, 'message' => 'Erreur lors de la mise à jour de la base de données']);
            }
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Erreur lors de l\'upload du fichier']);
        }
    }
    
    /**
     * Afficher les utilisateurs avec nombre de publications
     */
    public function withPublications() {
        // Vérifier que l'utilisateur est admin
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            header('Location: /');
            exit;
        }
        
        $filters = [];
        if (isset($_GET['role'])) {
            $filters['role'] = $_GET['role'];
        }
        
        // Utiliser directement getAllWithPublicationCount du model
        $users = $this->userModel->getAllWithPublicationCount($filters);
        
        require_once __DIR__ . '/../views/admin/users/publications.php';
    }
    
    /**
     * API: Récupérer tous les utilisateurs
     */
    public function getUsers() {
        // Vérifier que l'utilisateur est admin
        // if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        //     $this->jsonResponse(['success' => false, 'message' => 'Accès non autorisé']);
        // }
        
        // Récupérer les filtres depuis l'URL
        $filters = [];
        if (isset($_GET['role'])) {
            $filters['role'] = $_GET['role'];
        }
        if (isset($_GET['statut'])) {
            $filters['statut'] = $_GET['statut'];
        }
        
        // Utiliser directement la méthode du model
        $users = $this->userModel->getAll($filters);
        
        $this->jsonResponse(['success' => true, 'users' => $users]);
    }
    
    /**
     * Dashboard principal avec toutes les statistiques
     */
   public function indexDashboard() {
        // 1. Vérification Session & Admin
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?route=login');
            exit;
        }

        // Vérification rôle admin (à adapter selon votre logique)
        // $isAdmin = isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'directeur');
        // if (!$isAdmin) {
        //     header('Location: index.php?route=dashboard-user');
        //     exit;
        // }

        try {
            // 2. Récupération des données globales (Header/Footer)
            $config = $this->settingsModel->getAllSettings();
            $menu = $this->menuModel->getMenuTree();

            // 3. Récupération des données métier via les autres contrôleurs/modèles
            
            // Projets
            $projectController = new ProjectController();
            $projects = $projectController->getAll(); // Assurez-vous que cette méthode retourne un array

            // Événements
            $eventController = new EventController();
            $events = $eventController->getAll(); // Assurez-vous que cette méthode retourne un array

            // Publications
            $publicationController = new PublicationController();
            // Si stats() retourne un array directement, sinon adaptez
            $publicationData = $publicationController->stats(); 

            // Utilisateurs
            $users = $this->userModel->getAll();

            // 4. Préparation des données pour la vue
            $data = [
                'title' => 'Administration - Laboratoire',
                'config' => $config,
                'menu' => $menu,
                'users' => $users,
                'projects' => $projects,
                'events' => $events,
                'publications' => $publicationData
            ];

            // 5. Chargement de la Vue
            require_once __DIR__ . '/../views/admin_dashboard.php';
            $view = new DashboardAdminView($data);
            $view->render();

        } catch (Exception $e) {
            // Gestion d'erreur basique
            echo "Erreur lors du chargement du dashboard : " . $e->getMessage();
        }
    }
     public function indexUpdateUserAdmin() {
        // 1. Récupération des données existantes
        $users = $this->userModel->getAll();
        $teams = $this->teamsModel->getAllTeamsWithDetails();
        
        
        // 2. AJOUT : Récupération des données globales (Header/Footer)
        $config = $this->settingsModel->getAllSettings();
        $menu = $this->menuModel->getMenuTree();
        
        // 3. Préparation des données
        $data = [
            'title' => 'Gestion des Utilisateurs', // Titre pour le Header
            'users' => $users,
            'teams' => $teams,
            'config' => $config,
            'menu' => $menu
        ];
        
        // 4. Appel de la Vue Classe
        require_once __DIR__ . '/../views/updateUser.php';
        $view = new UpdateUserView($data);
        $view->render();
        
        return $data;
    }

    
    /**
     * Envoyer une réponse JSON
     */
    private function jsonResponse($data) {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}