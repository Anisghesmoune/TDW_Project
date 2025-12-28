<?php
require_once 'config/Database.php';
require_once 'models/UserModel.php';
require_once 'controllers/AuthController.php';

// Si déjà connecté, rediriger
if (AuthController::isLoggedIn()) {
    $role = $_SESSION['role'];
    if ($role === 'admin') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: user/dashboard.php');
    }
    exit;
}

$authController = new AuthController();
$authController->login();
?>