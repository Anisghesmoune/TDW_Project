<?php
class AuthController {
    private $userModel;
    public function __construct(){
        $this->userModel = new UserModel();
          // Configuration de session sécurisée
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_secure', 0); // Mettre à 1 si HTTPS
            ini_set('session.cookie_samesite', 'Strict');
            session_start();
            
            // Régénérer l'ID de session pour éviter la fixation
            if (!isset($_SESSION['initiated'])) {
                session_regenerate_id(true);
                $_SESSION['initiated'] = true;
            }

    }
}

    public function login(){
        if ($_SERVER['REQUEST_METHOD']==='POST'){
             if(!$this->validateCsrfToken($_POST['csrf_token'] ?? '')){
               return $this->sendResponse(false,'Token CSRF invalide');
             }

            $email = $this->sanitize($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';   
            if (empty($email) || empty($password)){
                return $this->sendResponse(false,'Email et mot de passe requis');
            }
        
        //  if ($this->isRateLimited($email)) {
        //         return $this->sendResponse(false, 'Trop de tentatives. Réessayez dans 15 minutes');
        //     }
            
            // Authentification
            $user = $this->userModel->authentificate($email, $password);
            
            if ($user) {
                // Régénérer l'ID de session après connexion réussie
                session_regenerate_id(true);
                
                // Stocker les informations utilisateur en session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['nom_complet'] = $user['nom'] . ' ' . $user['prenom'];
                $_SESSION['photo_profil'] = $user['photo_profil'];
                $_SESSION['logged_in'] = true;
                $_SESSION['login_time'] = time();
                
                // Réinitialiser le compteur de tentatives
                $this->resetLoginAttempts($email);
                
                return $this->sendResponse(true, 'Connexion réussie', [
                    'redirect' => $this->getRedirectUrl($user['role'])
                ]);
            } else {
                // Incrémenter le compteur de tentatives
                $this->incrementLoginAttempts($email);
                
                return $this->sendResponse(false, 'Identifiants incorrects');
            }
        }
            // Afficher le formulaire de login
        $this->renderLoginForm();
        }
        
        
    

    public function logout() {
        // Détruire toutes les données de session
        $_SESSION = array();
        
        // Détruire le cookie de session
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        // Détruire la session
        session_destroy();
        
        // Redirection
        header('Location: login.php');
        exit();
    }

    public static function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    /**
     * Vérifier si l'utilisateur a un rôle spécifique
     */
    public static function hasRole($role) {
        return self::isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === $role;
    }

    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header('Location: login.php');
            exit();
        }
        
        // Vérifier le timeout de session (30 minutes)
        if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > 1800)) {
            session_destroy();
            header('Location: login.php?timeout=1');
            exit();
        }
        
        // Mettre à jour le timestamp
        $_SESSION['login_time'] = time();
    }
    
    /**
     * Middleware admin uniquement
     */
    public static function requireAdmin() {
        self::requireLogin();
        if (!self::hasRole('admin')) {
            header('Location: dashboard.php');
            exit();
        }
    }
    
    /**
     * Générer un token CSRF
     */
    public function generateCsrfToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Valider le token CSRF
     */
    private function validateCsrfToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Sanitiser les entrées
     */
    private function sanitize($data) {
        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
    }
     private function isRateLimited($email) {
        $key = 'login_attempts_' . md5($email);
        if (!isset($_SESSION[$key])) {
            return false;
        }
        
        $attempts = $_SESSION[$key];
        return $attempts['count'] >= 5 && (time() - $attempts['time']) < 900; // 15 minutes
    }
    
     private function incrementLoginAttempts($email) {
        $key = 'login_attempts_' . md5($email);
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 0, 'time' => time()];
        }
        $_SESSION[$key]['count']++;
        $_SESSION[$key]['time'] = time();
    }
     private function resetLoginAttempts($email) {
        $key = 'login_attempts_' . md5($email);
        unset($_SESSION[$key]);
    }

    private function getRedirectUrl($role) {
        switch ($role) {
            case 'admin':
                return 'admin/dashboard.php';
            case 'enseignant':
            case 'doctorant':
            case 'etudiant':
            case 'invite':
                return 'user/dashboard.php';
            default:
                return 'index.php';
        }
    }

      private function sendResponse($success, $message, $data = []) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'data' => $data
        ]);
        exit();
    }
    
    /**
     * Afficher le formulaire de login
     */
   private function renderLoginForm() {
        $csrfToken = $this->generateCsrfToken();
        include 'views/login.php';
    }

    private function renderRegisterForm() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $csrfToken = $this->generateCsrfToken();
        include 'views/register.php';
    }
    public function register(){
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validation CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['success' => false, 'message' => 'Token CSRF invalide']);
        exit;
    }
    
    // Validation des données
    $requiredFields = ['nom', 'prenom', 'email', 'username', 'password', 'role'];
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => 'Tous les champs obligatoires doivent être remplis']);
            exit;
        }
    }
    
    // Validation du mot de passe
    if (strlen($_POST['password']) < 8) {
        echo json_encode(['success' => false, 'message' => 'Le mot de passe doit contenir au moins 8 caractères']);
        exit;
    }
    
    if ($_POST['password'] !== $_POST['confirm_password']) {
        echo json_encode(['success' => false, 'message' => 'Les mots de passe ne correspondent pas']);
        exit;
    }
    
    // Validation de l'email
    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Email invalide']);
        exit;
    }
    
    // Créer l'utilisateur
    $userModel = new UserModel();
    $data = [
        'nom' => htmlspecialchars(trim($_POST['nom'])),
        'prenom' => htmlspecialchars(trim($_POST['prenom'])),
        'email' => htmlspecialchars(trim($_POST['email'])),
        'username' => htmlspecialchars(trim($_POST['username'])),
        'password' => $_POST['password'],
        'role' => $_POST['role'],
        'grade' => htmlspecialchars(trim($_POST['grade'] ?? '')),
        'domaine_recherche' => htmlspecialchars(trim($_POST['domaine_recherche'] ?? ''))
    ];
    
    $result = $userModel->create($data);
    echo json_encode($result);
    return;
}
$this->renderRegisterForm();
}

}


