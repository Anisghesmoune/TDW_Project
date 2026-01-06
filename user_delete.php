<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../controllers/AuthController.php';

AuthController::requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['id'])) {
    $userId = intval($_GET['id']);
    
    // Empêcher l'admin de se supprimer lui-même
    if ($userId === $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'Vous ne pouvez pas vous supprimer vous-même']);
        exit;
    }
    
    try {
        $db = Database::getInstance()->getConnection();
        $query = "DELETE FROM users WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Utilisateur supprimé']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression']);
        }
    } catch (PDOException $e) {
        error_log("Erreur suppression: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erreur système']);
    }
}
?>