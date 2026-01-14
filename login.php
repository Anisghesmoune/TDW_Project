<?php
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/models/UserModel.php';
require_once __DIR__ . '/controllers/AuthController.php';

if (AuthController::isLoggedIn()) {
    $role = $_SESSION['is_admin'] == 1 ? 'admin' : 'user';
    if ($role === 'admin') {
        header('Location: index.php?route=dashboard-admin');
    } else {
        header('Location: index.php?route=dashboard-user');
    }
    exit;
}

$authController = new AuthController();
$authController->login();
?>