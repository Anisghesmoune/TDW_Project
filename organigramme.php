<?php
require_once __DIR__ . 'config/Database.php';
require_once __DIR__ . 'models/OrganigrammeModel.php';
require_once __DIR__ . 'models/UserModel.php';
require_once __DIR__ . 'controllers/OrganigrammeController.php';

session_start();

$controller = new OrganigrammeController();
$controller->index();
?>