<?php
// session_start();
require_once __DIR__. '/../../controllers/equipementController.php';
(new EquipmentController())->index();
?>