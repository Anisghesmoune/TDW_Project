<?php
class SlideController {
    private $slideModel;
    
    public function __construct() {
        $this->slideModel = new SlideModel();
    }
    
    /**
     * Afficher le diaporama (pour la page publique)
     */
    public function index() {
        $slides = $this->slideModel->getActiveSlides();
        
        $diaporamaView = new DiaporamaView($slides);
        $diaporamaView->render();
    }
    
    /**
     * Afficher la gestion des slides (admin)
     */
    public function admin() {
        AuthController::requireAdmin();
        
        $slides = $this->slideModel->getAll();
        
        include 'views/admin/slides_manage.php';
    }
    
    /**
     * Ajouter un slide
     */
    public function add() {
        AuthController::requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Gérer l'upload d'image
            $imagePath = '';
            if (!empty($_FILES['image']['name'])) {
                $uploadResult = $this->slideModel->uploadImage($_FILES['image']);
                if ($uploadResult['success']) {
                    $imagePath = $uploadResult['filepath'];
                } else {
                    echo json_encode($uploadResult);
                    exit;
                }
            }
            
            $data = [
                'titre' => htmlspecialchars(trim($_POST['titre'])),
                'description' => htmlspecialchars(trim($_POST['description'])),
                'image' => $imagePath,
                'lien_detail' => htmlspecialchars(trim($_POST['lien_detail'])),
                'ordre' => intval($_POST['ordre'] ?? 0),
                'actif' => isset($_POST['actif']) ? 1 : 0,
                'date_debut' => $_POST['date_debut'] ?? null,
                'date_fin' => $_POST['date_fin'] ?? null
            ];
            
            $result = $this->slideModel->create($data);
            echo json_encode($result);
            exit;
        }
    }
    
    /**
     * Modifier un slide
     */
    public function update() {
        AuthController::requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = intval($_POST['id']);
            
            // Gérer l'upload d'image
            $imagePath = $_POST['image_actuelle'] ?? '';
            if (!empty($_FILES['image']['name'])) {
                $uploadResult = $this->slideModel->uploadImage($_FILES['image']);
                if ($uploadResult['success']) {
                    // Supprimer l'ancienne image
                    if (!empty($imagePath) && file_exists($imagePath)) {
                        unlink($imagePath);
                    }
                    $imagePath = $uploadResult['filepath'];
                }
            }
            
            $data = [
                'titre' => htmlspecialchars(trim($_POST['titre'])),
                'description' => htmlspecialchars(trim($_POST['description'])),
                'image' => $imagePath,
                'lien_detail' => htmlspecialchars(trim($_POST['lien_detail'])),
                'ordre' => intval($_POST['ordre'] ?? 0),
                'actif' => isset($_POST['actif']) ? 1 : 0,
                'date_debut' => $_POST['date_debut'] ?? null,
                'date_fin' => $_POST['date_fin'] ?? null
            ];
            
            $result = $this->slideModel->update($id, $data);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Slide mis à jour']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erreur']);
            }
            exit;
        }
    }
    
    /**
     * Supprimer un slide
     */
    public function delete() {
        AuthController::requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = intval($_POST['id']);
            $result = $this->slideModel->delete($id);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Slide supprimé']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erreur']);
            }
            exit;
        }
    }
    
    /**
     * Activer/Désactiver un slide
     */
    public function toggle() {
        AuthController::requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = intval($_POST['id']);
            $result = $this->slideModel->toggleActive($id);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Statut modifié']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erreur']);
            }
            exit;
        }
    }
}