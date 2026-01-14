<?php
require_once __DIR__ . '/../models/OpportunityModel.php';
require_once __DIR__ . '/../models/Settings.php';
require_once __DIR__ . '/../models/Menu.php';

class OpportunityController {
    
    private $opportunityModel;
    private $settingsModel;
    private $menuModel;

    public function __construct() {
        $this->opportunityModel = new OpportunityModel();
        $this->settingsModel = new Settings();
        $this->menuModel = new Menu();
    }

 
    public function indexAdmin() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?route=login');
            exit;
        }

        $config = $this->settingsModel->getAllSettings();
        $menu = $this->menuModel->getMenuTree();
        
        $stats = $this->opportunityModel->getStats();

        $data = [
            'title' => 'Gestion des Opportunités',
            'config' => $config,
            'menu' => $menu,
            'stats' => $stats
        ];

        require_once __DIR__ . '/../views/OpportunityManagement.php';
        $view = new OpportunityAdminView($data);
        $view->render();
    }


    public function apiGetAll() {
        $data = $this->opportunityModel->getAllOpportunities();
        return ['success' => true, 'data' => $data];
    }

    public function apiGetById($id) {
        $data = $this->opportunityModel->getById($id);
        return $data ? ['success' => true, 'data' => $data] : ['success' => false, 'message' => 'Introuvable'];
    }

    public function apiCreate($data) {
        if (empty($data['titre']) || empty($data['type'])) {
            return ['success' => false, 'message' => 'Champs obligatoires manquants'];
        }

        if (session_status() === PHP_SESSION_NONE) session_start();
        $data['publie_par'] = $_SESSION['user_id'] ?? 1; 

        if ($this->opportunityModel->createOpportunity($data)) {
            return ['success' => true, 'message' => 'Offre créée avec succès'];
        }
        return ['success' => false, 'message' => 'Erreur lors de la création'];
    }

    public function apiUpdate($id, $data) {
        if ($this->opportunityModel->updateOpportunity($id, $data)) {
            return ['success' => true, 'message' => 'Offre mise à jour'];
        }
        return ['success' => false, 'message' => 'Erreur lors de la mise à jour'];
    }

    public function apiDelete($id) {
        if ($this->opportunityModel->deleteOpportunity($id)) {
            return ['success' => true, 'message' => 'Offre supprimée'];
        }
        return ['success' => false, 'message' => 'Erreur lors de la suppression'];
    }
     public function indexPublic() {
        $config = $this->settingsModel->getAllSettings();
        $menu = $this->menuModel->getMenuTree();
        
        $opportunities = $this->opportunityModel->getAllOpportunities();
        
        $data = [
            'config' => $config,
            'menu' => $menu,
            'opportunities' => $opportunities
        ];

        require_once __DIR__ . '/../views/public/OpportunityListView.php';
        $view = new OpportunityListView($data);
        $view->render();
    }
}
?>