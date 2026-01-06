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
}
?>