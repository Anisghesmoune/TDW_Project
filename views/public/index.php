<?php

require_once __DIR__ . '/../../controllers/HomeController.php';
// 2. On instancie le Contrôleur
$controller = new HomeController();


$controller->index();
?>