<?php
require_once __DIR__ . '/../models/Partner.php';
require_once __DIR__ . '/../models/Settings.php';
require_once __DIR__ . '/../models/Menu.php';

class PartnerController {
    
    private $partnerModel;
    private $settingsModel;
    private $menuModel;

    public function __construct() {
        $this->partnerModel = new Partner();
        $this->settingsModel = new Settings();
        $this->menuModel = new Menu();
    }

    // --- PAGE ADMIN ---
    public function indexAdmin() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        // // Vérification Admin
        // $isAdmin = isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'directeur');
        // if (!$isAdmin) {
        //     header('Location: index.php?route=login'); 
        //     exit;
        // }

        $config = $this->settingsModel->getAllSettings();
        $menu = $this->menuModel->getMenuTree();
        $stats = $this->partnerModel->getStats();

        $data = [
            'title' => 'Gestion des Partenaires',
            'config' => $config,
            'menu' => $menu,
            'stats' => $stats
        ];

        require_once __DIR__ . '/../views/PartnerManagement.php';
        $view = new PartnerAdminView($data);
        $view->render();
    }

    // --- PAGE PUBLIQUE ---
    public function indexPublic() {
        $config = $this->settingsModel->getAllSettings();
        $menu = $this->menuModel->getMenuTree();
        $partners = $this->partnerModel->getAllPartners();

        $data = [
            'config' => $config,
            'menu' => $menu,
            'partners' => $partners
        ];

        require_once __DIR__ . '/../views/public/PartnerListView.php';
        $view = new PartnerListView($data);
        $view->render();
    }

    // --- API CRUD ---

    public function apiGetAll() {
        $data = $this->partnerModel->getAllPartners();
        return ['success' => true, 'data' => $data];
    }

    public function apiGetById($id) {
        $data = $this->partnerModel->getById($id);
        return $data ? ['success' => true, 'data' => $data] : ['success' => false, 'message' => 'Introuvable'];
    }

    public function apiCreate($postData, $filesData) {
        $data = $postData;
        
        // Gestion Upload Logo
        $data['logo'] = ''; // Par défaut
        if (isset($filesData['logo']) && $filesData['logo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../assets/img/partners/';
            if (!file_exists($uploadDir)) mkdir($uploadDir, 0755, true);
            
            $ext = pathinfo($filesData['logo']['name'], PATHINFO_EXTENSION);
            $filename = 'partner_' . time() . '.' . $ext;
            
            if (move_uploaded_file($filesData['logo']['tmp_name'], $uploadDir . $filename)) {
                $data['logo'] = 'assets/img/partners/' . $filename;
            }
        }

        if ($this->partnerModel->createPartner($data)) {
            return ['success' => true, 'message' => 'Partenaire ajouté'];
        }
        return ['success' => false, 'message' => 'Erreur création'];
    }

    public function apiUpdate($id, $postData, $filesData) {
        $data = $postData;
        
        // Gestion Upload Logo (seulement si nouveau fichier)
        if (isset($filesData['logo']) && $filesData['logo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../assets/img/partners/';
            if (!file_exists($uploadDir)) mkdir($uploadDir, 0755, true);
            
            $ext = pathinfo($filesData['logo']['name'], PATHINFO_EXTENSION);
            $filename = 'partner_' . time() . '.' . $ext;
            
            if (move_uploaded_file($filesData['logo']['tmp_name'], $uploadDir . $filename)) {
                $data['logo'] = 'assets/img/partners/' . $filename;
            }
        }

        if ($this->partnerModel->updatePartner($id, $data)) {
            return ['success' => true, 'message' => 'Partenaire mis à jour'];
        }
        return ['success' => false, 'message' => 'Erreur mise à jour'];
    }

    public function apiDelete($id) {
        if ($this->partnerModel->deletePartner($id)) {
            return ['success' => true, 'message' => 'Partenaire supprimé'];
        }
        return ['success' => false, 'message' => 'Erreur suppression'];
    }
}
?>