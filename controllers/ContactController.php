<?php
require_once __DIR__ . '/../models/ContactModel.php';
require_once __DIR__ . '/../models/Settings.php';
require_once __DIR__ . '/../models/Menu.php';

class ContactController {
    private $contactModel;
    private $settingsModel;
    private $menuModel;

    public function __construct() {
        $this->contactModel = new ContactModel();
        $this->settingsModel = new Settings();
        $this->menuModel = new Menu();
    }

    public function index() {
        $config = $this->settingsModel->getAllSettings();
        
        $menu = $this->menuModel->getMenuTree();

        $data = [
            'config' => $config,
            'menu' => $menu
        ];

        require_once __DIR__ . '/../views/public/contact.php'; 
        
        if (class_exists('ContactView')) {
            $view = new ContactView($data);
            $view->render();
        } else {
            die("Erreur : La classe ContactView est introuvable.");
        }
    }

    public function sendMessage() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        if (empty($input['nom']) || empty($input['email']) || empty($input['message'])) {
            echo json_encode(['success' => false, 'message' => 'Veuillez remplir tous les champs obligatoires.']);
            exit;
        }

        if ($this->contactModel->create($input)) {
            echo json_encode(['success' => true, 'message' => 'Votre message a bien été envoyé. Nous vous répondrons bientôt.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'envoi du message.']);
        }
        exit;
    }
}
?>