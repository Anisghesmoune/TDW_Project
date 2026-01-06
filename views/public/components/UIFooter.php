<?php
require_once 'Component.php';
require_once 'UIMenu.php'; // Assurez-vous d'inclure UIMenu

class UIFooter extends Component {
    private $config;
    private $menu;

    public function __construct($config, $menuData) {
        $this->config = $config;
        $this->menu = $menuData;
    }

    public function render() {
        $year = date('Y');
        $siteName = htmlspecialchars($this->config['site_name'] ?? 'Laboratoire');
        
        // Génération du menu
        $menuComponent = new UIMenu($this->menu);
        $menuHtml = $menuComponent->render();

        return <<<HTML
<footer>
    <div class="container-footer footer-grid">
        <!-- Colonne 1 : Infos -->
        <div>
            <h3>{$siteName}</h3>
            <p>Laboratoire d'Excellence.</p>
            <p><i class="fas fa-envelope"></i> contact@labo.dz</p>
        </div>

        <!-- Colonne 2 : Navigation Dynamique -->
        <div class="footer-nav">
            <h3>Navigation</h3>
            {$menuHtml}
        </div>

        <!-- Colonne 3 : Boutons d'accès (Corrigé) -->
        <div class="footer-auth">
            <h3>Espace Membre</h3>
            <ul class="auth-links">
                <li><a href="../login.php" class="btn-login">Se connecter</a></li>
                <li><a href="../register.php" class="btn-login">Créer un compte</a></li>
            </ul>
        </div>
    </div>

    <div class="footer-bottom">
        &copy; {$year} {$siteName}. Tous droits réservés.
    </div>
</footer>

<script>
    // Script du slider (si présent sur la page)
    const slides = document.querySelectorAll('.slide');
    if(slides.length > 0) {
        let current = 0;
        setInterval(() => {
            slides[current].classList.remove('active');
            current = (current + 1) % slides.length;
            slides[current].classList.add('active');
        }, 5000);
    }
</script>
</body>
</html>
HTML;
    }
}
?>