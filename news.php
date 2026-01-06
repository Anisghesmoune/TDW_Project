<?php
require_once __DIR__ . 'config/Database.php';
require_once __DIR__ . 'models/News.php';
require_once __DIR__ . 'controllers/NewsController.php';

session_start();

$controller = new NewsController();
$controller->index();
?>
