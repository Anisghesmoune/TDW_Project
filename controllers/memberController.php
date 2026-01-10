<?php
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/ProjectModel.php';
require_once __DIR__ . '/../models/Publications.php';
require_once __DIR__ . '/../models/reservationModel.php';
require_once __DIR__ .  '/../models/Menu.php';
require_once __DIR__ . '/../models/Settings.php';
class MemberController {
    private $userModel;
    private $projectModel;
    private $pubModel;
    private $resModel;
    private $settings;
    private $menuModel;

    public function __construct() {
        $this->userModel = new UserModel();
        $this->projectModel = new Project();
        $this->pubModel = new Publication();
        $this->resModel = new Reservation();
        $this->settings = new Settings();
        $this->menuModel = new Menu();

    }
 
    // --- DASHBOARD ---
    public function dashboard() {
        // 0. Vérification de session (Indispensable)
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?route=login');
            exit();
        }

        $userId = $_SESSION['user_id'];

        // 1. Récupération de l'utilisateur avec sécurité
        $user = $this->userModel->getById($userId);

        // --- CORRECTION CRITIQUE ---
        // Si l'utilisateur a été supprimé de la BDD mais a encore une session active,
        // $user sera 'false', ce qui cause votre erreur précédente.
        if (!$user) {
            session_destroy();
            header('Location: index.php?route=login');
            exit();
        }

        // 2. Récupération des données globales (Config & Menu)
        $config = $this->settings->getAllSettings(); 
        $menu = $this->menuModel->getMenuTree();

        // 3. Récupération des données métier
        // J'utilise des tableaux vides [] par défaut si la méthode retourne null ou false
        $myProjects = $this->projectModel->getProjectsByUserId($userId) ?: [];
        
        // Adaptation selon vos méthodes réelles (ex: getUserPublications ou getByUser)
        $myPubs = method_exists($this->pubModel, 'getByUser') 
            ? ($this->pubModel->getByUser($userId) ?: []) 
            : [];

        $myRes = method_exists($this->resModel, 'getByUser') 
            ? ($this->resModel->getByUser($userId) ?: []) 
            : [];

        // 4. Calcul des statistiques
        $stats = [
            'projets' => count($myProjects),
            'pubs'    => count($myPubs),
            'reservations' => count($myRes)
        ];

        // 5. Préparation des données pour la vue
        $data = [
            // Données globales pour Header/Footer
            'title' => 'Mon Tableau de Bord',
            'config' => $config,       
            'menu' => $menu,
            
            // Données spécifiques à la page
            'user' => $user, // Ici, on est sûr que c'est un tableau valide
            'myProjects' => $myProjects,
            'myPubs' => $myPubs,
            'myRes' => $myRes,
            'stats' => $stats
        ];

        // 6. Appel de la Vue
        // Note : Assurez-vous que le nom du fichier correspond bien à la classe (DashboardUserView.php)
        require_once __DIR__ . '/../views/public/dashboardView.php';
        
        $view = new DashboardUserView($data);
        $view->render();
    }



    // --- PROFIL ---
   public function profile() {
        $this->checkAuth();
        $userId = $_SESSION['user_id'];
        
        // 1. Charger l'utilisateur
        $user = $this->userModel->getById($userId);
        if (!$user) { session_destroy(); header('Location: index.php?route=login'); exit; }
        
        // 2. Charger Config & Menu pour le Header
        $config = $this->settings->getAllSettings();
        $menu = $this->menuModel->getMenuTree();

        // 3. Préparer les données
        $data = [
            'title' => 'Mon Profil',
            'config' => $config,
            'menu' => $menu,
            'user' => $user
        ];
        
        // 4. Charger la vue ProfileView
        require_once __DIR__ . '/../views/public/profileView.php';
        $view = new ProfileView($data);
        $view->render();
    }
      // --- API : MISE À JOUR ---
    public function apiUpdateProfile($postData, $filesData) {
        $this->checkAuth();
        $userId = $_SESSION['user_id'];
        
        $updateData = [
            'email' => $postData['email'],
            'bio' => $postData['bio'],
            'domaine_recherche' => $postData['domaine_recherche'],
            'telephone' => $postData['telephone'] ?? null,
            'password' => $postData['password'] ?? null
        ];

        // Gestion Upload Photo
        if (isset($filesData['photo']) && $filesData['photo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../assets/img/avatars/';
            if (!file_exists($uploadDir)) mkdir($uploadDir, 0755, true);
            
            $ext = pathinfo($filesData['photo']['name'], PATHINFO_EXTENSION);
            $filename = 'user_' . $userId . '_' . time() . '.' . $ext;
            
            if (move_uploaded_file($filesData['photo']['tmp_name'], $uploadDir . $filename)) {
                $updateData['photo_profil'] = 'assets/img/avatars/' . $filename;
                // Mettre à jour la session pour l'affichage immédiat
                $_SESSION['photo_profil'] = $updateData['photo_profil'];
            }
        }

        if ($this->userModel->updateProfile($userId, $updateData)) {
            return ['success' => true, 'message' => 'Profil mis à jour avec succès'];
        }
        return ['success' => false, 'message' => 'Erreur lors de la mise à jour'];
    }

    private function checkAuth() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ../../login.php');
            exit;
        }
    }
}
?>