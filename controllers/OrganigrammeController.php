<?php

class OrganigrammeController {
    private $organigrammeModel;
    private $userModel;
    
    public function __construct() {
        $this->organigrammeModel = new OrganigrammeModel();
        $this->userModel = new UserModel();
    }
    
   
    public function index() {
        $director = $this->organigrammeModel->getDirector();
        $hierarchyTree = $this->organigrammeModel->getHierarchyTree();
        $stats = $this->organigrammeModel->getStats();
        
     
        include 'views/organigramme.php';
    }
    
  
    public function renderSection() {
        $director = $this->organigrammeModel->getDirector();
        $hierarchyTree = $this->organigrammeModel->getHierarchyTree();
        $stats = $this->organigrammeModel->getStats();
        
        $view = new OrganigrammeSectionView($director, $hierarchyTree, $stats);
        $view->render();
    }
    
   
    public function admin() {
        AuthController::requireAdmin();
        
        $fullHierarchy = $this->organigrammeModel->getFullHierarchy();
        $hierarchyTree = $this->organigrammeModel->getHierarchyTree();
        $availablePositions = $this->organigrammeModel->getAvailablePositions();
        $allUsers = $this->userModel->getAll();
        
        include 'views/admin/organigramme_manage.php';
    }
    
 
    public function add() {
        AuthController::requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'id_utilisateur' => intval($_POST['id_utilisateur']),
                'poste' => htmlspecialchars(trim($_POST['poste'])),
                'niveau_hierarchique' => intval($_POST['niveau_hierarchique']),
                'date_nomination' => $_POST['date_nomination'],
                'superieur_id' => !empty($_POST['superieur_id']) ? intval($_POST['superieur_id']) : null
            ];
            
            if ($this->organigrammeModel->userExists($data['id_utilisateur'])) {
                echo json_encode(['success' => false, 'message' => 'Cet utilisateur est déjà dans l\'organigramme']);
                exit;
            }
            
            $result = $this->organigrammeModel->create($data);
            echo json_encode($result);
            exit;
        }
    }
    
 
    public function update() {
        AuthController::requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = intval($_POST['id']);
            $data = [
                'poste' => htmlspecialchars(trim($_POST['poste'])),
                'niveau_hierarchique' => intval($_POST['niveau_hierarchique']),
                'date_nomination' => $_POST['date_nomination'],
                'superieur_id' => !empty($_POST['superieur_id']) ? intval($_POST['superieur_id']) : null
            ];
            
            $result = $this->organigrammeModel->update($id, $data);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Mis à jour avec succès']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour']);
            }
            exit;
        }
    }
    
   
    public function delete() {
        AuthController::requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = intval($_POST['id']);
            $result = $this->organigrammeModel->delete($id);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Supprimé avec succès']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression']);
            }
            exit;
        }
    }
}