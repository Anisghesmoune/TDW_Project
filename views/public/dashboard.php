<?php

session_start();

require_once __DIR__ . '/../../controllers/memberController.php';

$controller = new MemberController();
$controller->dashboard();
?>