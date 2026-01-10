<?php
require_once __DIR__ . '/Model.php';

class ContactModel extends Model {
    
    public function __construct() {
        $this->table = 'contact_messages';
        parent::__construct();
    }

    // Enregistrer un nouveau message
    public function create($data) {
        $query = "INSERT INTO " . $this->table . " (nom, email, sujet, message) 
                  VALUES (:nom, :email, :sujet, :message)";
        
        $stmt = $this->conn->prepare($query);
        
        // Nettoyage basique
        $nom = htmlspecialchars(strip_tags($data['nom']));
        $email = htmlspecialchars(strip_tags($data['email']));
        $sujet = htmlspecialchars(strip_tags($data['sujet']));
        $message = htmlspecialchars(strip_tags($data['message']));

        $stmt->bindParam(':nom', $nom);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':sujet', $sujet);
        $stmt->bindParam(':message', $message);

        return $stmt->execute();
    }

    // Récupérer les messages (Pour l'admin plus tard)
    public function getAllMessages() {
        return $this->getAll('date_envoi', 'DESC');
    }
}
?>