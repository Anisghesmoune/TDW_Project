<?php
require_once __DIR__ . '/Model.php';

class Settings extends Model {
    
    public function __construct() {
        $this->table = 'site_settings';
        parent::__construct();
    }

  
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

  
    public function generateBackup() {
        $tables = [];
        $query = $this->conn->query("SHOW TABLES");
        while ($row = $query->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }

        $sqlScript = "-- BACKUP DATABASE " . date('Y-m-d H:i:s') . "\n\n";
        $sqlScript .= "SET FOREIGN_KEY_CHECKS=0;\n";

        foreach ($tables as $table) {
            $row2 = $this->conn->query("SHOW CREATE TABLE $table")->fetch(PDO::FETCH_NUM);
            $sqlScript .= "\n\n" . $row2[1] . ";\n\n";

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

  
    public function restoreBackup($filePath) {
        if (!file_exists($filePath)) return false;
        
        $sql = file_get_contents($filePath);
        
        try {
            $this->conn->beginTransaction();
            
            $this->conn->exec("SET FOREIGN_KEY_CHECKS=0");
            
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

public function replaceAll($items) {
    try {
        $this->conn->beginTransaction();
        
        $this->conn->exec("TRUNCATE TABLE menu_items"); // Ou DELETE FROM
        
        $sql = "INSERT INTO menu_items (title, url, ordre) VALUES (:title, :url, :ordre)";
        $stmt = $this->conn->prepare($sql);
        
        foreach ($items as $item) {
            $stmt->bindValue(':title', $item['title']);
            $stmt->bindValue(':url', $item['url']);
            $stmt->bindValue(':ordre', $item['ordre']);
            $stmt->execute();
        }
        
        $this->conn->commit();
        return true;
    } catch (Exception $e) {
        $this->conn->rollBack();
        return false;
    }
}

public function delete($id) {
    $stmt = $this->conn->prepare("DELETE FROM menu WHERE id = :id");
    $stmt->execute(['id' => $id]);
    return $stmt->rowCount() > 0;
}
}
?>