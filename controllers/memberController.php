<?php
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/ProjectModel.php';
require_once __DIR__ . '/../models/Publications.php';
require_once __DIR__ . '/../models/reservationModel.php';
require_once __DIR__ . '/../models/Settings.php';

class MemberController {
    private $userModel;
    private $projectModel;
    private $pubModel;
    private $resModel;
    private $settings;

    public function __construct() {
        $this->userModel = new UserModel();
        $this->projectModel = new Project();
        $this->pubModel = new Publication();
        $this->resModel = new Reservation();
        $this->settings = new Settings();
    }

    // --- DASHBOARD ---
    public function dashboard() {
        $this->checkAuth();
        $userId = $_SESSION['user_id'];

        // Récupération des données PERSONNALISÉES
        $user = $this->userModel->getById($userId);
        $myProjects = $this->projectModel->getProjectsByUserId($userId);
        $myPubs = $this->pubModel->getByUser($userId); // Assurez-vous que cette méthode existe dans Publication
        $myRes = $this->resModel->getByUser($userId); // Assurez-vous que cette méthode existe

        // Stats
        $stats = [
            'projets' => count($myProjects),
            'pubs' => count($myPubs),
            'reservations' => count($myRes)
        ];

        // Config pour le layout
        $config = $this->settings->getAllSettings();

        // Chargement de la vue
        require_once __DIR__ . '/../views/public/dashboardView.php';
    }

    // --- PROFIL ---
    public function profile() {
        $userId = $_SESSION['user_id'];
        
        // 1. On charge l'utilisateur ICI
        $user = $this->userModel->getById($userId);
        
        // 2. On charge les stats ICI
        $myProjects = $this->projectModel->getProjectsByUserId($userId);
        $myPubs = $this->pubModel->getByUser($userId);
        $myRes = $this->resModel->getByUser($userId);
        
        // 3. On envoie les données à la vue
        // ATTENTION : Mettez le bon chemin vers votre fichier vue
        // Puisque vous l'avez mis dans views/public/ :
        require_once __DIR__ . '/../views/public/profileView.php';
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