<?php

class Database {
    private static $instance = null;
    private $conn;

    private $host = 'localhost';
    private $db_name = 'TDW';
    private $username = 'root';
    private $password = 'root';
    private $charset = 'utf8mb4';


    private function __construct(){
        try{
            $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset={$this->charset}";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_PERSISTENT         => true,
            ];
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e){
           die ('Connection failed: ' . $e->getMessage());
        }

    }  

    public static function getInstance(){
        if (self::$instance === null){
            self::$instance = new Database();
        }
        return self::$instance;         

    }
    public function getConnection(){
        return $this->conn;
    }

    // Empêcher le clonage
    private function __clone() {}
    
    // Empêcher la désérialisation
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}