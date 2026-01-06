<?php
require_once __DIR__ . '/Model.php';

class Settings extends Model {
    
    public function __construct() {
        $this->table = 'site_settings';
        parent::__construct();
    }

    /**
     * Récupère tous les paramètres sous forme de tableau associatif
     * [ 'site_name' => 'Mon Labo', 'primary_color' => '#...', ... ]
     */
    public function getAllSettings() {
        $query = "SELECT * FROM " . $this->table;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $settings = [];
        foreach ($results as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    }

    /**
     * Met à jour ou insère un paramètre
     */
    public function updateSetting($key, $value) {
       
        
        $query = "INSERT INTO " . $this->table . " (setting_key, setting_value) 
                  VALUES (:key, :val_insert) 
                  ON DUPLICATE KEY UPDATE setting_value = :val_update";
                  
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindValue(':key', $key);
        $stmt->bindValue(':val_insert', $value); 
        $stmt->bindValue(':val_update', $value); 
        
        return $stmt->execute();
    }

    /**
     * GÉNÉRATION DU BACKUP (DUMP SQL)
     */
    public function generateBackup() {
        $tables = [];
        $query = $this->conn->query("SHOW TABLES");
        while ($row = $query->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }

        $sqlScript = "-- BACKUP DATABASE " . date('Y-m-d H:i:s') . "\n\n";
        $sqlScript .= "SET FOREIGN_KEY_CHECKS=0;\n";

        foreach ($tables as $table) {
            // Structure de la table
            $row2 = $this->conn->query("SHOW CREATE TABLE $table")->fetch(PDO::FETCH_NUM);
            $sqlScript .= "\n\n" . $row2[1] . ";\n\n";

            // Données de la table
            $query = $this->conn->query("SELECT * FROM $table");
            while ($row = $query->fetch(PDO::FETCH_NUM)) {
                $sqlScript .= "INSERT INTO $table VALUES(";
                for ($j = 0; $j < count($row); $j++) {
                    $row[$j] = addslashes($row[$j]);
                    $row[$j] = str_replace("\n", "\\n", $row[$j]);
                    if (isset($row[$j])) {
                        $sqlScript .= '"' . $row[$j] . '"';
                    } else {
                        $sqlScript .= '""';
                    }
                    if ($j < (count($row) - 1)) {
                        $sqlScript .= ',';
                    }
                }
                $sqlScript .= ");\n";
            }
        }
        
        $sqlScript .= "\nSET FOREIGN_KEY_CHECKS=1;";
        return $sqlScript;
    }

    /**
     * RESTAURATION DU BACKUP
     */
    public function restoreBackup($filePath) {
        if (!file_exists($filePath)) return false;
        
        // Lire le fichier complet
        $sql = file_get_contents($filePath);
        
        try {
            $this->conn->beginTransaction();
            
            // Désactiver les clés étrangères temporairement
            $this->conn->exec("SET FOREIGN_KEY_CHECKS=0");
            
            // Exécution multiple
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            
            $this->conn->exec("SET FOREIGN_KEY_CHECKS=1");
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }
}
?>