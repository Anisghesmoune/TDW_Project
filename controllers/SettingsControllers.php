<?php
require_once __DIR__ . '/../models/Settings.php';
require_once __DIR__ . '/../models/Menu.php';


class SettingsController {
    private $settingsModel;
    private $menuModel;

    public function __construct() {
        $this->settingsModel = new Settings();
        $this->menuModel = new Menu(); 
    }

    public function apiGetSettings() {
        $data = $this->settingsModel->getAllSettings();
        return ['success' => true, 'data' => $data];
    }

   public function updateConfig($postData, $filesData) {
        try {
           $fieldsToSave = [
                'site_name', 'primary_color', 'sidebar_color', 
                'lab_description', 'lab_email', 'lab_phone', 'lab_address', 
                'social_facebook', 'social_instagram', 'social_linkedin', 'univ_website'
            ];

            foreach ($fieldsToSave as $key) {
                if (isset($postData[$key])) {
                    $this->settingsModel->updateSetting($key, trim($postData[$key]));
                }
            }

            if (isset($filesData['logo']) && $filesData['logo']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../assets/img/';
                
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $ext = pathinfo($filesData['logo']['name'], PATHINFO_EXTENSION);
                $filename = 'logo_' . time() . '.' . $ext;
                
                if(move_uploaded_file($filesData['logo']['tmp_name'], $uploadDir . $filename)) {
                    $this->settingsModel->updateSetting('logo_path', 'assets/img/' . $filename);
                }
            }

            return ['success' => true, 'message' => 'Paramètres mis à jour avec succès'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erreur : ' . $e->getMessage()];
        }
    }


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
    
    public function apiGetMenu() {
        require_once __DIR__ . '/../models/Menu.php';
        $menuModel = new Menu();
        $items = $menuModel->getAll();
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $items]);
        exit;
    }

    public function apiUpdateMenu() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['menu']) || !is_array($input['menu'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Données invalides']);
            exit;
        }

        require_once __DIR__ . '/../models/Menu.php';
        $menuModel = new Menu();

        
        foreach ($input['menu'] as $item) {
            if (isset($item['id'])) {
                $menuModel->update($item['id'], $item['title'], $item['url'], $item['ordre']);
            } else {
                $menuModel->create($item['title'], $item['url'], $item['ordre']);
            }
        }
        
      

        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }
    public function apiDeleteMenuItem() {
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
    $settings = $this->settingsModel->getAllSettings();
    $menuItems = $this->menuModel->getAll();
    
    $backupsList = $this->getBackupsList();
    
    $config = $settings; 
    $menu = $menuItems;
    
    $data = [
        'title' => 'Paramètres du Site',
        'settings' => $settings,
        'menuItems' => $menuItems,
        'backupsList' => $backupsList,
        'config' => $config,
        'menu' => $menu
    ];
    
    require_once __DIR__ . '/../views/Settings.php';
    $view = new SettingsAdminView($data);
    $view->render();
    
    return $data;
}


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
    
    usort($backups, function($a, $b) {
        return strcmp($b['name'], $a['name']);
    });
    
    return $backups;
}


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