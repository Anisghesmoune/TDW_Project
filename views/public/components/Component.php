<?php
abstract class Component {
    abstract public function render();
    
    public function __toString() {
        return $this->render();
    }
}
?>