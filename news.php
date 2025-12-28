<?php
require_once 'config/Database.php';
require_once 'models/News.php';
require_once 'controllers/NewsController.php';

session_start();

$controller = new NewsController();
$controller->index();
?>
