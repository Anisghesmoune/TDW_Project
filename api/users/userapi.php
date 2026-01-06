<?php
require_once __DIR__ . 'UserController.php';
$controller = new UserController();

$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? null;

if ($action === 'getUser' && $id) {
    $controller->getUserJson($id);
} elseif ($action === 'updateUser' && $id) {
    $controller->updateUserJson($id); // idem pour la MAJ
} else {
    echo json_encode(['success' => false, 'message' => 'Action invalide']);
}
