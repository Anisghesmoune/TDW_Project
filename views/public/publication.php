<?php
require_once __DIR__. '/../../controllers/PublicationController.php';
// On lance le chef d'orchestre
(new PublicationController())->index();
?>