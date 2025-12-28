<?php
require_once 'config/Database.php';
require_once 'models/OrganigrammeModel.php';
require_once 'models/UserModel.php';
require_once 'controllers/OrganigrammeController.php';

session_start();

$controller = new OrganigrammeController();
$controller->index();
?>