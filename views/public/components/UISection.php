<?php
class UISection extends Component {
    private $title;
    private $components = []; // Tableau de composants (cartes)
    private $bgColor;

    public function __construct($title, $bgColor = 'transparent') {
        $this->title = $title;
        $this->bgColor = $bgColor;
    }

    public function addComponent(Component $component) {
        $this->components[] = $component;
    }

    public function render() {
        $content = "";
        foreach ($this->components as $comp) {
            $content .= $comp->render();
        }

        return <<<HTML
        <section class="container" style="background-color: {$this->bgColor};">
            <h2 class="section-title">{$this->title}</h2>
            <div class="grid">
                {$content}
            </div>
        </section>
HTML;
    }
}
?>