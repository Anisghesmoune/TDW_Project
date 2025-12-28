<?php
require_once 'config/Database.php';
require_once 'models/UserModel.php';
require_once 'controllers/AuthController.php';

$authController = new AuthController();
$authController->logout();
?>