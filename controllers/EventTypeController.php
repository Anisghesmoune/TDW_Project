<?php
class EventTypeController {

    private $model;

    public function __construct() {
        $this->model = new EventType();
    }

    public function index() {
        return [
            'success' => true,
            'data' => $this->model->getAll()
        ];
    }

    public function create($data) {
        if (empty($data['nom'])) {
            return ['success' => false, 'message' => 'Le nom est requis'];
        }

        $this->model->create($data);
        return ['success' => true, 'message' => 'Type ajouté avec succès'];
    }

    public function delete($id) {
        $this->model->delete($id);
        return ['success' => true, 'message' => 'Type supprimé'];
    }
}
