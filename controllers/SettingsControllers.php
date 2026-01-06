<?php
require_once __DIR__ . '/../models/Settings.php';

class SettingsController {
    private $settingsModel;

    public function __construct() {
        $this->settingsModel = new Settings();
    }

    // --- API GET SETTINGS ---
    public function apiGetSettings() {
        $data = $this->settingsModel->getAllSettings();
        return ['success' => true, 'data' => $data];
    }

    // --- MISE À JOUR CONFIG ---
    public function updateConfig($postData, $filesData) {
        try {
            // 1. Sauvegarde des champs textes
            if (isset($postData['site_name'])) 
                $this->settingsModel->updateSetting('site_name', $postData['site_name']);
            
            if (isset($postData['primary_color'])) 
                $this->settingsModel->updateSetting('primary_color', $postData['primary_color']);
                
            if (isset($postData['sidebar_color'])) 
                $this->settingsModel->updateSetting('sidebar_color', $postData['sidebar_color']);

            // 2. Gestion du Logo
            if (isset($filesData['logo']) && $filesData['logo']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = '../assets/img/';
                if (!file_exists($uploadDir)) mkdir($uploadDir, 0755, true);
                
                $ext = pathinfo($filesData['logo']['name'], PATHINFO_EXTENSION);
                $filename = 'logo_custom.' . $ext;
                
                if(move_uploaded_file($filesData['logo']['tmp_name'], $uploadDir . $filename)) {
                    $this->settingsModel->updateSetting('logo_path', 'assets/img/' . $filename);
                }
            }

            return ['success' => true, 'message' => 'Paramètres mis à jour'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
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
}
?>