<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../controllers/AuthController.php';

AuthController::requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['photo'])) {
    $file = $_FILES['photo'];
    
    // Validation du fichier
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $maxSize = 2 * 1024 * 1024; // 2MB
    
    if (!in_array($file['type'], $allowedTypes)) {
        echo json_encode(['success' => false, 'message' => 'Format de fichier non autorisé. Utilisez JPG, PNG ou GIF']);
        exit;
    }
    
    if ($file['size'] > $maxSize) {
        echo json_encode(['success' => false, 'message' => 'Le fichier est trop volumineux (max 2MB)']);
        exit;
    }
    
    // Créer le dossier uploads s'il n'existe pas
    $uploadDir = '../uploads/profiles/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Générer un nom unique
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'profile_' . $_SESSION['user_id'] . '_' . time() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    // Déplacer le fichier
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // Mettre à jour la base de données
        $userModel = new UserModel();
        $relativePath = 'uploads/profiles/' . $filename;
        
        if ($userModel->updatePhoto($_SESSION['user_id'], $relativePath)) {
            $_SESSION['photo_profil'] = $relativePath;
            echo json_encode(['success' => true, 'message' => 'Photo mise à jour avec succès']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors du téléchargement']);
    }
}
?>