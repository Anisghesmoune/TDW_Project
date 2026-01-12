<?php
require_once __DIR__ . '/Model.php';

class OpportunityModel extends Model {

    /**
     * Récupère toutes les opportunités avec les infos de l'auteur
     */
    public function getAllOpportunities() {
        $sql = "SELECT o.*, u.nom as auteur_nom, u.prenom as auteur_prenom 
                FROM opportunities o 
                LEFT JOIN users u ON o.publie_par = u.id 
                ORDER BY o.date_publication DESC";
        
      
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $sql = "SELECT * FROM opportunities WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createOpportunity($data) {
        $sql = "INSERT INTO opportunities (titre, type, description, date_publication, date_expiration, contact, publie_par, statut) 
                VALUES (:titre, :type, :description, NOW(), :date_expiration, :contact, :publie_par, :statut)";
        
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':titre' => $data['titre'],
            ':type' => $data['type'],
            ':description' => $data['description'],
            ':date_expiration' => !empty($data['date_expiration']) ? $data['date_expiration'] : null,
            ':contact' => $data['contact'],
            ':publie_par' => $data['publie_par'],
            ':statut' => $data['statut'] ?? 'active'
        ]);
    }

    public function updateOpportunity($id, $data) {
        $sql = "UPDATE opportunities SET 
                titre = :titre, 
                type = :type, 
                description = :description, 
                date_expiration = :date_expiration, 
                contact = :contact, 
                statut = :statut 
                WHERE id = :id";
        
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':titre' => $data['titre'],
            ':type' => $data['type'],
            ':description' => $data['description'],
            ':date_expiration' => !empty($data['date_expiration']) ? $data['date_expiration'] : null,
            ':contact' => $data['contact'],
            ':statut' => $data['statut']
        ]);
    }

    public function deleteOpportunity($id) {
        $sql = "DELETE FROM opportunities WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    // Statistiques pour le dashboard
    public function getStats() {
        $stats = [
            'total' => 0,
            'active' => 0,
            'stage' => 0,
            'these' => 0
        ];

        try {
            // Total
            $stmt = $this->conn->query("SELECT COUNT(*) as c FROM opportunities");
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['total'] = $res ? $res['c'] : 0;

            // Actives
            $stmt = $this->conn->query("SELECT COUNT(*) as c FROM opportunities WHERE statut = 'active'");
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['active'] = $res ? $res['c'] : 0;
            
            // Par type
            $stmt = $this->conn->query("SELECT type, COUNT(*) as c FROM opportunities GROUP BY type");
            $types = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if($types) {
                foreach($types as $t) {
                    // Mapping simple pour stage et thèse (attention aux accents selon votre BDD)
                    if(isset($t['type'])) {
                        $key = strtolower($t['type']);
                        if(strpos($key, 'stage') !== false) $stats['stage'] += $t['c'];
                        if(strpos($key, 'thèse') !== false || strpos($key, 'these') !== false) $stats['these'] += $t['c'];
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Erreur lors de la récupération des stats des opportunités : " . $e->getMessage());
        }

        return $stats;
    }
}
?>