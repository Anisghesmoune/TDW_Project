<?php
class EventType extends Model {

    protected $table = 'event_types';

    public function getAllType() {
        return $this->getAll('nom', 'ASC');
    }

    public function create($data) {
        $stmt = $this->conn->prepare("
            INSERT INTO event_types (nom, description)
            VALUES (:nom, :description)
        ");
        return $stmt->execute([
            ':nom' => $data['nom'],
            ':description' => $data['description']
        ]);
    }

    public function delete($id) {
        $stmt = $this->conn->prepare("DELETE FROM event_types WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
