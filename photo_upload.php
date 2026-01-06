<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../controllers/AuthController.php';

AuthController::requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['id'])) {
    $userId = intval($_GET['id']);
    
    // Empêcher l'admin de se suspendre lui-même
    if ($userId === $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'Vous ne pouvez pas vous suspendre vous-même']);
        exit;
    }
    
    $userModel = new UserModel();
    $result = $userModel->suspend($userId);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Utilisateur suspendu']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la suspension']);
    }
}
?>
