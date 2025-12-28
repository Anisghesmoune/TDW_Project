<?php
require_once '../config/Database.php';
require_once '../models/UserModel.php';
require_once '../controllers/AuthController.php';


AuthController::requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldPassword = $_POST['old_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($oldPassword) || empty($newPassword) || empty($confirmPassword)) {
        echo json_encode(['success' => false, 'message' => 'Tous les champs sont requis']);
        exit;
    }
    
    if (strlen($newPassword) < 8) {
        echo json_encode(['success' => false, 'message' => 'Le nouveau mot de passe doit contenir au moins 8 caractères']);
        exit;
    }
    
    if ($newPassword !== $confirmPassword) {
        echo json_encode(['success' => false, 'message' => 'Les mots de passe ne correspondent pas']);
        exit;
    }
    
    $userModel = new UserModel();
    $result = $userModel->changePassword($_SESSION['user_id'], $oldPassword, $newPassword);
    
    echo json_encode($result);
}
?>