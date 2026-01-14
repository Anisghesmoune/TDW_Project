<?php
abstract class View {
    protected $data;
    protected $layout;

    public function __construct($data = []) {
        $this->data = $data;
    }

    public function __get($key) {
        return $this->data[$key] ?? null;
    }

    abstract protected function content();

    public function render() {
        require_once __DIR__ . '/components/UIHeader.php';
        require_once __DIR__ . '/components/UIFooter.php';

        $header = new UIHeader($this->pageTitle ?? 'Accueil', $this->config, $this->menu);
        echo $header->render();

        echo $this->content();

        $footer = new UIFooter($this->config,$this->menu);
        echo $footer->render();
    }
}
?>