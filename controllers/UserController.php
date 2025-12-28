<?php

require_once '../models/UserModel.php';
class UserController {
    private $userModel;
    
    public function __construct() {
        $this->userModel = new UserModel();
    }
    
    /**
     * Liste de tous les utilisateurs (Admin)
     */
    public function index() {
        // Vérifier que l'utilisateur est admin
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            header('Location: /');
            exit;
        }
        
        // Récupérer les filtres depuis l'URL
        $filters = [];
        if (isset($_GET['role'])) {
            $filters['role'] = $_GET['role'];
        }
        if (isset($_GET['statut'])) {
            $filters['statut'] = $_GET['statut'];
        }
        
        // Utiliser directement la méthode du model
        $users = $this->userModel->getAll($filters);
        
        require_once 'views/admin/users/index.php';
    }
    
    /**
     * Afficher la page de création d'utilisateur
     */
    public function create() {
        // Vérifier que l'utilisateur est admin
        // if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        //     header('Location: /');
        //     exit;
        // }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->store();
        }
        
        require_once 'views/admin/users/create.php';
    }
    
    /**
     * Enregistrer un nouvel utilisateur
     */
    private function store() {
        // Préparer les données
        $input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Données invalides']);
    exit;
}

$data = [
    'username' => $input['username'] ?? '',
    'password' => $input['password'] ?? '',
    'nom' => $input['nom'] ?? '',
    'prenom' => $input['prenom'] ?? '',
    'email' => $input['email'] ?? '',
    'role' => $input['role'] ?? 'etudiant',
    'grade' => $input['grade'] ?? null,
    'domaine_recherche' => $input['domaine_recherche'] ?? null
];

        // Appeler directement la méthode create du model
        $result = $this->userModel->create($data);
        
        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
            header('Location: /admin/users');
        } else {
            $_SESSION['error'] = $result['message'];
            $_SESSION['old'] = $_POST;
            header('Location: /admin/users/create');
        }
        exit;
    }
    
    /**
     * Afficher la page de modification
     */
    public function edit($id) {
        // Vérifier que l'utilisateur est admin
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            header('Location: /');
            exit;
        }
        
        // Utiliser directement getById du model
        $user = $this->userModel->getById($id);
        
        if (!$user) {
            $_SESSION['error'] = 'Utilisateur introuvable.';
            header('Location: /admin/users');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->update($id);
        }
        
        require_once 'views/admin/users/edit.php';
    }
    
    /**
     * Mettre à jour un utilisateur
     */
    private function update($id) {
        // Préparer les données
        $data = [
            'nom' => $_POST['nom'] ?? '',
            'prenom' => $_POST['prenom'] ?? '',
            'email' => $_POST['email'] ?? '',
            'grade' => $_POST['grade'] ?? null,
            'domaine_recherche' => $_POST['domaine_recherche'] ?? null,
            'specialite' => $_POST['specialite'] ?? null
        ];
        
        // Utiliser directement updateProfile du model
        $result = $this->userModel->updateProfile($id, $data);
        
        // Si un nouveau mot de passe est fourni
        if (!empty($_POST['password'])) {
            // Pour l'admin, on permet de changer sans l'ancien mot de passe
            $query = "UPDATE users SET password = :password WHERE id = :id";
            $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
            // Vous pourriez ajouter une méthode dans le model : updatePasswordByAdmin()
        }
        
        if ($result) {
            $_SESSION['success'] = 'Utilisateur mis à jour avec succès.';
            header('Location: /admin/users');
        } else {
            $_SESSION['error'] = 'Erreur lors de la mise à jour.';
            header('Location: /admin/users/edit/' . $id);
        }
        exit;
    }
    
    /**
     * Supprimer un utilisateur (soft delete)
     */
    public function delete($id) {
        // Vérifier que l'utilisateur est admin
        // if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        //     $this->jsonResponse(['success' => false, 'message' => 'Accès non autorisé']);
        // }
        
        // Ne pas permettre la suppression de son propre compte
        if ($id == $_SESSION['user_id']) {
            $this->jsonResponse(['success' => false, 'message' => 'Vous ne pouvez pas supprimer votre propre compte']);
        }
        
        // Utiliser directement delete du model
        if ($this->userModel->delete($id)) {
            $this->jsonResponse(['success' => true, 'message' => 'Utilisateur supprimé avec succès']);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Erreur lors de la suppression']);
        }
    }
    
    /**
     * Suspendre un utilisateur
     */
    public function suspend($id) {
        // // Vérifier que l'utilisateur est admin
        // if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        //     $this->jsonResponse(['success' => false, 'message' => 'Accès non autorisé']);
        // }
        
        // Utiliser directement suspend du model
        if ($this->userModel->suspend($id)) {
            $this->jsonResponse(['success' => true, 'message' => 'Utilisateur suspendu']);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Erreur lors de la suspension']);
        }
    }
    
    /**
     * Activer un utilisateur
     */
    public function activate($id) {
        // Vérifier que l'utilisateur est admin
        // if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        //     $this->jsonResponse(['success' => false, 'message' => 'Accès non autorisé']);
        // }
        
        // Utiliser directement activate du model
        if ($this->userModel->activate($id)) {
            $this->jsonResponse(['success' => true, 'message' => 'Utilisateur activé']);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Erreur lors de l\'activation']);
        }
    }
     public function getUser($id) {
        // Vérifier que l'utilisateur est admin
        // if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        //     $this->jsonResponse(['success' => false, 'message' => 'Accès non autorisé']);
        // }
        
        // Utiliser directement getById du model
        $user = $this->userModel->getById($id);
        
        if (!$user) {
            $this->jsonResponse(['success' => false, 'message' => 'Utilisateur introuvable']);
        }
        
        $this->jsonResponse(['success' => true, 'user' => $user]);
    }
    
    /**
     * API: Mettre à jour un utilisateur (via JSON)
     */
    public function updateUser() {
        // Vérifier que l'utilisateur est admin
        // if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        //     $this->jsonResponse(['success' => false, 'message' => 'Accès non autorisé']);
        // }
        
        // Récupérer les données JSON
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['id']) || empty($data['id'])) {
            $this->jsonResponse(['success' => false, 'message' => 'ID utilisateur manquant']);
        }
        
        $id = $data['id'];
        $photo_profil = $data['photo_profil'] ?? null;
        
        // Vérifier que l'utilisateur existe
        $user = $this->userModel->getById($id);
        if (!$user) {
            $this->jsonResponse(['success' => false, 'message' => 'Utilisateur introuvable']);
        }
        
        // Préparer les données pour updateProfile
        $profileData = [
            'nom' => $data['nom'],
            'prenom' => $data['prenom'],
            'email' => $data['email'],
            'grade' => $data['grade'] ?? null,
            'domaine_recherche' => $data['domaine_recherche'] ?? null,
            'specialite' => $data['specialite'] ?? null
            
        ];
        
        // Utiliser directement updateProfile du model
        $result = $this->userModel->updateProfile($id, $profileData);
        $result = $this->userModel->updatePhoto($id, $photo_profil);
        
        // Gérer le mot de passe si fourni (ajouter cette méthode au model si nécessaire)
        if (isset($data['password']) && !empty($data['password'])) {
            // Vous devriez ajouter une méthode updatePasswordByAdmin() dans le model
            // Pour l'instant, on ignore le mot de passe ou on fait un query direct
        }
        
        if ($result) {
            $this->jsonResponse(['success' => true, 'message' => 'Utilisateur mis à jour avec succès']);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Erreur lors de la mise à jour']);
        }
    }
    
    public function updatePhoto($id) {
    // Vérifier que l'utilisateur est admin (décommenter si nécessaire)
    // if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    //     $this->jsonResponse(['success' => false, 'message' => 'Accès non autorisé']);
    // }
    
    // Vérifier qu'un fichier a été uploadé
    if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        $this->jsonResponse(['success' => false, 'message' => 'Aucun fichier uploadé']);
    }
    
    $file = $_FILES['photo'];
    
    // Vérifier que l'utilisateur existe
    $user = $this->userModel->getById($id);
    if (!$user) {
        $this->jsonResponse(['success' => false, 'message' => 'Utilisateur introuvable']);
    }
    
    // Validation du fichier
    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowedTypes)) {
        $this->jsonResponse(['success' => false, 'message' => 'Type de fichier non autorisé. Utilisez JPG, PNG ou GIF']);
    }
    
    if ($file['size'] > $maxSize) {
        $this->jsonResponse(['success' => false, 'message' => 'Fichier trop volumineux. Maximum 5MB']);
    }
    
    // Créer le dossier uploads si nécessaire
    $uploadDir = '../uploads/profiles/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Générer un nom de fichier unique
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'profile_' . $id . '_' . time() . '.' . $extension;
    $destination = $uploadDir . $filename;
    
    // Supprimer l'ancienne photo si elle existe
    if ($user['photo_profil'] && file_exists($user['photo_profil'])) {
        unlink($user['photo_profil']);
    }
    
    // Déplacer le fichier uploadé
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        // Mettre à jour la base de données
        $result = $this->userModel->updatePhoto($id, $destination);
        
        if ($result) {
            $this->jsonResponse([
                'success' => true, 
                'message' => 'Photo de profil mise à jour avec succès',
                'photo_url' => $destination
            ]);
        } else {
            // Supprimer le fichier si la BDD échoue
            unlink($destination);
            $this->jsonResponse(['success' => false, 'message' => 'Erreur lors de la mise à jour de la base de données']);
        }
    } else {
        $this->jsonResponse(['success' => false, 'message' => 'Erreur lors de l\'upload du fichier']);
    }
}
    /**
     * Afficher les utilisateurs avec nombre de publications
     */
    public function withPublications() {
        // Vérifier que l'utilisateur est admin
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            header('Location: /');
            exit;
        }
        
        $filters = [];
        if (isset($_GET['role'])) {
            $filters['role'] = $_GET['role'];
        }
        
        // Utiliser directement getAllWithPublicationCount du model
        $users = $this->userModel->getAllWithPublicationCount($filters);
        
        require_once 'views/admin/users/publications.php';
    }
    
    /**
     * Envoyer une réponse JSON
     */
    private function jsonResponse($data) {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    public function getUsers() {
        // Vérifier que l'utilisateur est admin
        // if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        //     $this->jsonResponse(['success' => false, 'message' => 'Accès non autorisé']);
        // }
        
        // Récupérer les filtres depuis l'URL
        $filters = [];
        if (isset($_GET['role'])) {
            $filters['role'] = $_GET['role'];
        }
        if (isset($_GET['statut'])) {
            $filters['statut'] = $_GET['statut'];
        }
        
        // Utiliser directement la méthode du model
        $users = $this->userModel->getAll($filters);
        
        $this->jsonResponse(['success' => true, 'users' => $users]);
    }
}