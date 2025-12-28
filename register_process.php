<?php
require_once 'config/Database.php';
require_once 'models/UserModel.php';
require_once 'controllers/AuthController.php';

$authController = new AuthController();

// Si l'utilisateur est déjà connecté, on le redirige
if (AuthController::isLoggedIn()) {
    $role = $_SESSION['role'];
    if ($role === 'admin') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: user/dashboard.php');
    }
    exit;
}

// Point d'entrée pour l'enregistrement
$authController->register();
