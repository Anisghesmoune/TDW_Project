<?php
abstract class Component {

    protected $data; 

    public function __construct($data = []) {
        $this->data = $data;
    }

    abstract public function render();

    public function __toString() {
        return (string) $this->render();
    }
}
?>