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
    private function requireAdmin() {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
            exit; 
        }
    }
    
   
    public function index() {
        
        
        $filters = [];
        if (isset($_GET['role'])) {
            $filters['role'] = $_GET['role'];
        }
        if (isset($_GET['statut'])) {
            $filters['statut'] = $_GET['statut'];
        }
        
        $users = $this->userModel->getAll($filters);
        
        $data = [
            'users' => $users,
            'filters' => $filters
        ];
        
        
        return $data;
    }
    
   
    public function create() {
            $this->requireAdmin(); 
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->store();
        }
        
    }
    
    
    private function store() {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            echo json_encode(['success' => false, 'message' => 'Données invalides']);
            exit;
        }

        if (empty($input['username'])) {
            return $this->jsonResponse(['success' => false, 'message' => 'Le nom d\'utilisateur est requis']);
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
    
  
    public function edit($id) {
       
        $user = $this->userModel->getById($id);
        
        if (!$user) {
            $_SESSION['error'] = 'Utilisateur introuvable.';
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->update($id);
        }
        
    }
    
  
    private function update($id) {
        $data = [
            'username' => $_POST['username'] ?? null, 
            'nom' => $_POST['nom'] ?? '',
            'prenom' => $_POST['prenom'] ?? '',
            'email' => $_POST['email'] ?? '',
            'role' => $_POST['role'] ?? '',
            'grade' => $_POST['grade'] ?? null,
            'domaine_recherche' => $_POST['domaine_recherche'] ?? null,
            'specialite' => $_POST['specialite'] ?? null
        ];

        if (empty($data['username'])) {
            unset($data['username']);
        }
        
        $result = $this->userModel->updateProfile($id, $data);
        
        if (!empty($_POST['password'])) {
            $query = "UPDATE users SET password = :password WHERE id = :id";
            $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
        }
        
        if ($result) {
            $_SESSION['success'] = 'Utilisateur mis à jour avec succès.';
        } else {
            $_SESSION['error'] = 'Erreur lors de la mise à jour.';
        }
        exit;
    }
    
    
    public function delete($id) {
           $this->requireAdmin(); 
        if ($id == $_SESSION['user_id']) {
            $this->jsonResponse(['success' => false, 'message' => 'Vous ne pouvez pas supprimer votre propre compte']);
        }
        
        if ($this->userModel->delete($id)) {
            $this->jsonResponse(['success' => true, 'message' => 'Utilisateur supprimé avec succès']);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Erreur lors de la suppression']);
        }
    }
    
   
    public function suspend($id) {
           $this->requireAdmin(); 
        if ($this->userModel->suspend($id)) {
            $this->jsonResponse(['success' => true, 'message' => 'Utilisateur suspendu']);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Erreur lors de la suspension']);
        }
    }
    
  
    public function activate($id) {
            $this->requireAdmin(); 
        if ($this->userModel->activate($id)) {
            $this->jsonResponse(['success' => true, 'message' => 'Utilisateur activé']);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Erreur lors de l\'activation']);
        }
    }
    
    
    public function getUser($id) {
        
        $user = $this->userModel->getById($id);
        
        if (!$user) {
            $this->jsonResponse(['success' => false, 'message' => 'Utilisateur introuvable']);
        }
        
        $this->jsonResponse(['success' => true, 'user' => $user]);
    }
    
    
    public function updateUser() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['id']) || empty($data['id'])) {
            $this->jsonResponse(['success' => false, 'message' => 'ID utilisateur manquant']);
        }
        
        $id = $data['id'];
        $photo_profil = $data['photo_profil'] ?? null;
        
        $user = $this->userModel->getById($id);
        if (!$user) {
            $this->jsonResponse(['success' => false, 'message' => 'Utilisateur introuvable']);
        }
        
        $profileData = [
            'username' => $data['username'] ?? null, 
            'nom' => $data['nom'],
            'prenom' => $data['prenom'],
            'email' => $data['email'],
            'role'=> $data['role'] ?? '',
            'grade' => $data['grade'] ?? null,
            'domaine_recherche' => $data['domaine_recherche'] ?? null,
            'specialite' => $data['specialite'] ?? null
        ];

        if (empty($profileData['username'])) {
            unset($profileData['username']);
        }
        
        $result = $this->userModel->updateProfile($id, $profileData);
        
        if ($photo_profil) {
            $result = $this->userModel->updatePhoto($id, $photo_profil);
        }
        
        if (isset($data['password']) && !empty($data['password'])) {
           
        }
        
        if ($result) {
            $this->jsonResponse(['success' => true, 'message' => 'Utilisateur mis à jour avec succès']);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Erreur lors de la mise à jour']);
        }
    }
    

    public function updatePhoto($id) {
       
    
        if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            $this->jsonResponse(['success' => false, 'message' => 'Aucun fichier uploadé']);
        }
        
        $file = $_FILES['photo'];
        
        $user = $this->userModel->getById($id);
        if (!$user) {
            $this->jsonResponse(['success' => false, 'message' => 'Utilisateur introuvable']);
        }
        
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($file['type'], $allowedTypes)) {
            $this->jsonResponse(['success' => false, 'message' => 'Type de fichier non autorisé. Utilisez JPG, PNG ou GIF']);
        }
        
        if ($file['size'] > $maxSize) {
            $this->jsonResponse(['success' => false, 'message' => 'Fichier trop volumineux. Maximum 5MB']);
        }
        
        $uploadDir = '../uploads/profiles/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'profile_' . $id . '_' . time() . '.' . $extension;
        $destination = $uploadDir . $filename;
        
        if ($user['photo_profil'] && file_exists($user['photo_profil'])) {
            unlink($user['photo_profil']);
        }
        
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            $result = $this->userModel->updatePhoto($id, $destination);
            
            if ($result) {
                $this->jsonResponse([
                    'success' => true, 
                    'message' => 'Photo de profil mise à jour avec succès',
                    'photo_url' => $destination
                ]);
            } else {
                unlink($destination);
                $this->jsonResponse(['success' => false, 'message' => 'Erreur lors de la mise à jour de la base de données']);
            }
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Erreur lors de l\'upload du fichier']);
        }
    }
    
    
    public function withPublications() {
       
        
        $filters = [];
        if (isset($_GET['role'])) {
            $filters['role'] = $_GET['role'];
        }
        
        $users = $this->userModel->getAllWithPublicationCount($filters);
        
    }
    
    
    public function getUsers() {
       
        $filters = [];
        if (isset($_GET['role'])) {
            $filters['role'] = $_GET['role'];
        }
        if (isset($_GET['statut'])) {
            $filters['statut'] = $_GET['statut'];
        }
        
        $users = $this->userModel->getAll($filters);
        
        $this->jsonResponse(['success' => true, 'users' => $users]);
    }
    
   
   public function indexDashboard() {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?route=login');
            exit;
        }

        

        try {
            $config = $this->settingsModel->getAllSettings();
            $menu = $this->menuModel->getMenuTree();

            
            $projectController = new ProjectController();
            $projects = $projectController->getAll(); 
            $eventController = new EventController();
            $events = $eventController->getAll(); 

            $publicationController = new PublicationController();
            $publicationData = $publicationController->stats(); 

            $users = $this->userModel->getAll();

            $data = [
                'title' => 'Administration - Laboratoire',
                'config' => $config,
                'menu' => $menu,
                'users' => $users,
                'projects' => $projects,
                'events' => $events,
                'publications' => $publicationData
            ];

            require_once __DIR__ . '/../views/admin_dashboard.php';
            $view = new DashboardAdminView($data);
            $view->render();

        } catch (Exception $e) {
            echo "Erreur lors du chargement du dashboard : " . $e->getMessage();
        }
    }
     public function indexUpdateUserAdmin() {
        $users = $this->userModel->getAll();
        $teams = $this->teamsModel->getAllTeamsWithDetails();
        
        
        $config = $this->settingsModel->getAllSettings();
        $menu = $this->menuModel->getMenuTree();
        
        $data = [
            'title' => 'Gestion des Utilisateurs', // Titre pour le Header
            'users' => $users,
            'teams' => $teams,
            'config' => $config,
            'menu' => $menu
        ];
        
        require_once __DIR__ . '/../views/updateUser.php';
        $view = new UpdateUserView($data);
        $view->render();
        
        return $data;
    }

    
   
    private function jsonResponse($data) {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}