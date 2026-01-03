<?php
class UIFooter extends Component {
    private $config;

    public function __construct($config) {
        $this->config = $config;
    }

    public function render() {
        $year = date('Y');
        $siteName = htmlspecialchars($this->config['site_name'] ?? 'Laboratoire');

        return <<<HTML
<footer>
    <div class="container footer-grid">
        <div>
            <h3>{$siteName}</h3>
            <p>Laboratoire d'Excellence.</p>
            <p><i class="fas fa-envelope"></i> contact@labo.dz</p>
        </div>
        <div>
            <h3>Navigation</h3>
            <ul>
                <li><a href="index.php">Accueil</a></li>
                <li><a href="projects.php">Projets</a></li>
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