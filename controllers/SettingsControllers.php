<?php
require_once __DIR__ . '/../models/Settings.php';
require_once __DIR__ . '/../models/Menu.php';


class SettingsController {
    private $settingsModel;
    private $menuModel;

    public function __construct() {
        $this->settingsModel = new Settings();
        $this->menuModel = new Menu(); // Instanciation
    }

    // --- API GET SETTINGS ---
    public function apiGetSettings() {
        $data = $this->settingsModel->getAllSettings();
        return ['success' => true, 'data' => $data];
    }

    // --- MISE À JOUR CONFIG ---
   public function updateConfig($postData, $filesData) {
        try {
            // Liste des champs autorisés à être sauvegardés
           $fieldsToSave = [
                'site_name', 'primary_color', 'sidebar_color', // Apparence
                'lab_description', 'lab_email', 'lab_phone', 'lab_address', // Contact
                'social_facebook', 'social_instagram', 'social_linkedin', 'univ_website' // Réseaux sociaux (Nouveaux)
            ];

            // 1. Sauvegarde des champs textes (Boucle automatique)
            foreach ($fieldsToSave as $key) {
                if (isset($postData[$key])) {
                    $this->settingsModel->updateSetting($key, trim($postData[$key]));
                }
            }

            // 2. Gestion du Logo
            if (isset($filesData['logo']) && $filesData['logo']['error'] === UPLOAD_ERR_OK) {
                // Utilisation de __DIR__ pour sécuriser le chemin
                $uploadDir = __DIR__ . '/../assets/img/';
                
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $ext = pathinfo($filesData['logo']['name'], PATHINFO_EXTENSION);
                // On donne un nom unique pour éviter les problèmes de cache navigateur
                $filename = 'logo_' . time() . '.' . $ext;
                
                if(move_uploaded_file($filesData['logo']['tmp_name'], $uploadDir . $filename)) {
                    // On enregistre le chemin relatif pour la BDD
                    $this->settingsModel->updateSetting('logo_path', 'assets/img/' . $filename);
                }
            }

            return ['success' => true, 'message' => 'Paramètres mis à jour avec succès'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erreur : ' . $e->getMessage()];
        }
    }

    // --- ACTIONS DATABASE ---

    public function downloadBackup() {
        $sqlContent = $this->settingsModel->generateBackup();
        $filename = 'backup_db_' . date('Y-m-d_H-i') . '.sql';

        header('Content-Type: application/octet-stream');
        header("Content-Transfer-Encoding: Binary"); 
        header("Content-disposition: attachment; filename=\"" . $filename . "\""); 
        echo $sqlContent;
        exit;
    }

    public function restoreBackup($filesData) {
        if (!isset($filesData['backup_file']) || $filesData['backup_file']['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Erreur de fichier'];
        }

        $file = $filesData['backup_file']['tmp_name'];
        
        try {
            $this->settingsModel->restoreBackup($file);
            return ['success' => true, 'message' => 'Base de données restaurée avec succès !'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erreur SQL : ' . $e->getMessage()];
        }
    }
    // Dans SettingsController.php
    
    // Pour charger la liste
    public function apiGetMenu() {
        require_once __DIR__ . '/../models/Menu.php';
        $menuModel = new Menu();
        $items = $menuModel->getAll(); // Assurez-vous que cette méthode existe et fait un SELECT * ORDER BY ordre
        
        // On retourne du JSON
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $items]);
        exit;
    }

    // Pour sauvegarder la liste
    public function apiUpdateMenu() {
        // Lecture du JSON envoyé par JS
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['menu']) || !is_array($input['menu'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Données invalides']);
            exit;
        }

        require_once __DIR__ . '/../models/Menu.php';
        $menuModel = new Menu();

        
        // Exemple simple : Mise à jour ligne par ligne
        foreach ($input['menu'] as $item) {
            if (isset($item['id'])) {
                // Update existant
                $menuModel->update($item['id'], $item['title'], $item['url'], $item['ordre']);
            } else {
                // Create nouveau
                $menuModel->create($item['title'], $item['url'], $item['ordre']);
            }
        }
        
      

        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }
    // --- SUPPRESSION ELEMENT MENU ---
    public function apiDeleteMenuItem() {
        // On s'attend à recevoir du JSON (ex: { "id": 5 })
        $input = json_decode(file_get_contents('php://input'), true);

        header('Content-Type: application/json');

        if (!isset($input['id'])) {
            echo json_encode(['success' => false, 'message' => 'ID manquant']);
            exit;
        }

        try {
            
            $this->menuModel->delete($input['id']);
            
            echo json_encode(['success' => true, 'message' => 'Élément supprimé']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erreur : ' . $e->getMessage()]);
        }
        exit;
    }

    public function indexAdmin() {
    // 1. Récupération des données existantes
    $settings = $this->settingsModel->getAllSettings();
    $menuItems = $this->menuModel->getAll();
    
    // 2. Liste des backups disponibles
    $backupsList = $this->getBackupsList();
    
    // 3. Récupération des données globales (Header/Footer)
    $config = $settings; // Les settings sont déjà les configs
    $menu = $menuItems;
    
    // 4. Préparation des données
    $data = [
        'title' => 'Paramètres du Site',
        'settings' => $settings,
        'menuItems' => $menuItems,
        'backupsList' => $backupsList,
        // Données ajoutées pour la Vue
        'config' => $config,
        'menu' => $menu
    ];
    
    // 5. Appel de la Vue Classe
    require_once __DIR__ . '/../views/Settings.php';
    $view = new SettingsAdminView($data);
    $view->render();
    
    return $data;
}

/**
 * Récupérer la liste des backups disponibles
 */
private function getBackupsList() {
    $backupDir = __DIR__ . '/../backups/';
    $backups = [];
    
    if (!is_dir($backupDir)) {
        return $backups;
    }
    
    $files = scandir($backupDir);
    
    foreach ($files as $file) {
        if ($file != '.' && $file != '..' && pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
            $filepath = $backupDir . $file;
            $backups[] = [
                'name' => $file,
                'date' => date('d/m/Y H:i:s', filemtime($filepath)),
                'size' => $this->formatBytes(filesize($filepath))
            ];
        }
    }
    
    // Trier par date (plus récent en premier)
    usort($backups, function($a, $b) {
        return strcmp($b['name'], $a['name']);
    });
    
    return $backups;
}

/**
 * Formater la taille en octets
 */
private function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

    
}
?>