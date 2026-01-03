<?php
// views/View.php
abstract class View {
    protected $data;
    protected $layout;

    public function __construct($data = []) {
        $this->data = $data;
    }

    // Méthode magique pour accéder aux données ($this->data['titre'] devient $this->titre)
    public function __get($key) {
        return $this->data[$key] ?? null;
    }

    // Chaque vue doit définir son contenu
    abstract protected function content();

    // Méthode principale pour afficher la page complète
    public function render() {
        // On inclut les composants nécessaires
        require_once 'components/UIHeader.php';
        require_once 'components/UIFooter.php';

        // 1. Rendu du Header
        $header = new UIHeader($this->pageTitle ?? 'Accueil', $this->config);
        echo $header->render();

        // 2. Rendu du Contenu (défini dans les classes filles)
        echo $this->content();

        // 3. Rendu du Footer
        $footer = new UIFooter($this->config);
        echo $footer->render();
    }
}
?>