<?php
require_once __DIR__ . '/Component.php';

class UIPartners extends Component {
    public function render() {
        if (empty($this->data)) return '';

        $logosHtml = '';
        foreach ($this->data as $p) {
            $img = htmlspecialchars($p['logo']);
            $nom = htmlspecialchars($p['nom']);
            $logosHtml .= "<div class='partner-logo'><img src='$img' alt='$nom' title='$nom'></div>";
        }

        return <<<HTML
        <section class="container" style="background:#fff; text-align:center; padding: 40px 0;">
            <h2 class="section-title">Nos Partenaires</h2>
            <div class="partners-grid" style="display:flex; justify-content:center; gap:30px; flex-wrap:wrap;">
                $logosHtml
            </div>
        </section>
HTML;
    }
}
?>