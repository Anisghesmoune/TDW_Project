<?php
class AuthController {
    private $userModel;

    public function __construct(){
        $this->userModel = new UserModel();

        if (session_status() === PHP_SESSION_NONE) {
            $sessionPath = __DIR__ . '/../sessions';
            
            if (!file_exists($sessionPath)) {
                mkdir($sessionPath, 0777, true);
            }
            
            session_save_path($sessionPath);

            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_secure', 0);
            ini_set('session.cookie_samesite', 'Strict');
            
            session_start();
            
            if (!isset($_SESSION['initiated'])) {
                session_regenerate_id(true);
                $_SESSION['initiated'] = true;
            }
        }
    }

    public function login(){
        if ($_SERVER['REQUEST_METHOD'] === 'POST'){
             if(!$this->validateCsrfToken($_POST['csrf_token'] ?? '')){
               return $this->sendResponse(false, 'Token CSRF invalide');
             }

            $username = $this->sanitize($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';   
            
            if (empty($username) || empty($password)){
                return $this->sendResponse(false, 'Nom d\'utilisateur et mot de passe requis');
            }
        
            
            // if ($this->isRateLimited($username)) {
            //     return $this->sendResponse(false, 'Trop de tentatives. Réessayez dans 15 minutes');
            // }
            
            $user = $this->userModel->authentificate($username, $password);
            
            if ($user) {
                session_regenerate_id(true);
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['nom'] = $user['nom'];
                $_SESSION['prenom'] = $user['prenom'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['is_admin'] = ($user['is_admin'] == 1); 
                $_SESSION['nom_complet'] = $user['nom'] . ' ' . $user['prenom'];
                $_SESSION['photo_profil'] = $user['photo_profil'];
                $_SESSION['logged_in'] = true;
                $_SESSION['login_time'] = time();
                
                $this->resetLoginAttempts($username);
                
                return $this->sendResponse(true, 'Connexion réussie', [
                    'redirect' => $this->getRedirectUrl($user)
                ]);
            } else {
                $this->incrementLoginAttempts($username);
                
                return $this->sendResponse(false, 'Identifiants incorrects');
            }
        }
        $this->renderLoginForm();
    }

    public function logout() {
        $_SESSION = array();
        
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        session_destroy();
        
        header('Location:index.php?route=login');
        exit();
    }

    public static function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    public static function hasRole($role) {
        return self::isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === $role;
    }

    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header('Location: login.php');
            exit();
        }
        
        if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > 1800)) {
            session_destroy();
            header('Location: login.php?timeout=1');
            exit();
        }
        
        $_SESSION['login_time'] = time();
    }
    
    public static function requireAdmin() {
        self::requireLogin();
        if (!self::hasRole('admin')) {
            header('Location: dashboard.php');
            exit();
        }
    }
    
    public function generateCsrfToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    private function validateCsrfToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    private function sanitize($data) {
        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
    }

    private function isRateLimited($username) {
        $key = 'login_attempts_' . md5($username);
        if (!isset($_SESSION[$key])) {
            return false;
        }
        
        $attempts = $_SESSION[$key];
        return $attempts['count'] >= 5 && (time() - $attempts['time']) < 900; // 15 minutes
    }
    
    private function incrementLoginAttempts($username) {
        $key = 'login_attempts_' . md5($username);
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 0, 'time' => time()];
        }
        $_SESSION[$key]['count']++;
        $_SESSION[$key]['time'] = time();
    }

    private function resetLoginAttempts($username) {
        $key = 'login_attempts_' . md5($username);
        unset($_SESSION[$key]);
    }

    private function getRedirectUrl($user) {
        if ((is_array($user) && isset($user['is_admin']) && $user['is_admin'] == 1) || $user === 'admin') {
            return 'index.php?route=admin-dashboard';
        }
        return 'index.php?route=dashboard-user';
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
    
    private function renderLoginForm() {
        $csrfToken = $this->generateCsrfToken();
        include 'views/login.php';
        require_once __DIR__ . '/../views/login.php'; 
        
        $data = ['csrfToken' => $csrfToken];
        
        $view = new LoginView($data);
        $view->render();
    }

    private function renderRegisterForm() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $csrfToken = $this->generateCsrfToken();
        $data = ['csrfToken' => $csrfToken];
        include 'views/register.php';

        $view = new RegisterView($data);
        $view->render();
    }

    public function register(){
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            
            if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                echo json_encode(['success' => false, 'message' => 'Token CSRF invalide ou session expirée']);
                exit;
            }
            
            $requiredFields = ['username', 'nom', 'prenom', 'email', 'password', 'role'];
            foreach ($requiredFields as $field) {
                if (empty($_POST[$field])) {
                    echo json_encode(['success' => false, 'message' => 'Tous les champs obligatoires doivent être remplis']);
                    exit;
                }
            }
            
            if (strlen($_POST['password']) < 4) {
                echo json_encode(['success' => false, 'message' => 'Le mot de passe doit contenir au moins 8 caractères']);
                exit;
            }
            
            if ($_POST['password'] !== $_POST['confirm_password']) {
                echo json_encode(['success' => false, 'message' => 'Les mots de passe ne correspondent pas']);
                exit;
            }
            
            if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'message' => 'Email invalide']);
                exit;
            }

            if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $_POST['username'])) {
                echo json_encode(['success' => false, 'message' => 'Le nom d\'utilisateur doit contenir 3 à 20 caractères alphanumériques']);
                exit;
            }
            
            $userModel = new UserModel();
            $data = [
                'username' => htmlspecialchars(trim($_POST['username'])),
                'nom' => htmlspecialchars(trim($_POST['nom'])),
                'prenom' => htmlspecialchars(trim($_POST['prenom'])),
                'email' => htmlspecialchars(trim($_POST['email'])),
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
?>