<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../controllers/AuthController.php';

AuthController::requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userModel = new UserModel();
    
    $data = [
        'nom' => htmlspecialchars(trim($_POST['nom'])),
        'prenom' => htmlspecialchars(trim($_POST['prenom'])),
        'email' => htmlspecialchars(trim($_POST['email'])),
        'grade' => htmlspecialchars(trim($_POST['grade'] ?? '')),
        'domaine_recherche' => htmlspecialchars(trim($_POST['domaine_recherche'] ?? '')),
        'specialite' => htmlspecialchars(trim($_POST['specialite'] ?? ''))
    ];
    
    $result = $userModel->updateProfile($_SESSION['user_id'], $data);
    
    if ($result) {
        // Mettre à jour la session
        $_SESSION['nom_complet'] = $data['nom'] . ' ' . $data['prenom'];
        echo json_encode(['success' => true, 'message' => 'Profil mis à jour avec succès']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour']);
    }
}
?>