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
 
    public function dashboard() {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?route=login');
            exit();
        }

        $userId = $_SESSION['user_id'];

        $user = $this->userModel->getById($userId);

       if (!$user) {
            session_destroy();
            header('Location: index.php?route=login');
            exit();
        }

        $config = $this->settings->getAllSettings(); 
        $menu = $this->menuModel->getMenuTree();
$myProjects = $this->projectModel->getProjectsByUserId($userId) ?: [];
        
        $myPubs = method_exists($this->pubModel, 'getByUser') 
            ? ($this->pubModel->getByUser($userId) ?: []) 
            : [];

        $myRes = method_exists($this->resModel, 'getByUser') 
            ? ($this->resModel->getByUser($userId) ?: []) 
            : [];

        $stats = [
            'projets' => count($myProjects),
            'pubs'    => count($myPubs),
            'reservations' => count($myRes)
        ];

        $data = [
            'title' => 'Mon Tableau de Bord',
            'config' => $config,       
            'menu' => $menu,
            
            'user' => $user, 
            'myProjects' => $myProjects,
            'myPubs' => $myPubs,
            'myRes' => $myRes,
            'stats' => $stats
        ];

        require_once __DIR__ . '/../views/public/dashboardView.php';
        
        $view = new DashboardUserView($data);
        $view->render();
    }



   public function profile() {
        $this->checkAuth();
        $userId = $_SESSION['user_id'];
        
        $user = $this->userModel->getById($userId);
        if (!$user) { session_destroy(); header('Location: index.php?route=login'); exit; }
        
        $config = $this->settings->getAllSettings();
        $menu = $this->menuModel->getMenuTree();

        $data = [
            'title' => 'Mon Profil',
            'config' => $config,
            'menu' => $menu,
            'user' => $user
        ];
        
        require_once __DIR__ . '/../views/public/profileView.php';
        $view = new ProfileView($data);
        $view->render();
    }
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

        if (isset($filesData['photo']) && $filesData['photo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../assets/img/avatars/';
            if (!file_exists($uploadDir)) mkdir($uploadDir, 0755, true);
            
            $ext = pathinfo($filesData['photo']['name'], PATHINFO_EXTENSION);
            $filename = 'user_' . $userId . '_' . time() . '.' . $ext;
            
            if (move_uploaded_file($filesData['photo']['tmp_name'], $uploadDir . $filename)) {
                $updateData['photo_profil'] = 'assets/img/avatars/' . $filename;
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