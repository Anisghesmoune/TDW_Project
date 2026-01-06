<?php
require_once __DIR__ . '/Component.php';
require_once __DIR__ . '/UIMenu.php'; 

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
        
        // --- 1. Récupération des Coordonnées (avec valeurs par défaut si vide) ---
        $address = htmlspecialchars($this->config['lab_address'] ?? 'Adresse non définie');
        $phone   = htmlspecialchars($this->config['lab_phone'] ?? '');
        $email   = htmlspecialchars($this->config['lab_email'] ?? '');
        
        // --- 2. Récupération des Réseaux Sociaux ---
        $fb   = $this->config['social_facebook'] ?? '';
        $insta = $this->config['social_instagram'] ?? '';
        $in   = $this->config['social_linkedin'] ?? '';
        $univ = $this->config['univ_website'] ?? '';

        // Construction du bloc HTML pour les réseaux sociaux
        $socialHtml = '<div class="social-links-footer" style="margin-top:15px;">';
        
        if(!empty($fb)) {
            $socialHtml .= "<a href='$fb' target='_blank' title='Facebook' style='margin-right:10px; color:white;'><i class='fab fa-facebook fa-lg'></i></a>";
        }
        if(!empty($insta)) {
            $socialHtml .= "<a href='$insta' target='_blank' title='Instagram' style='margin-right:10px; color:white;'><i class='fab fa-instagram fa-lg'></i></a>";
        }
        if(!empty($in)) {
            $socialHtml .= "<a href='$in' target='_blank' title='LinkedIn' style='margin-right:10px; color:white;'><i class='fab fa-linkedin fa-lg'></i></a>";
        }
        if(!empty($univ)) {
            $socialHtml .= "<a href='$univ' target='_blank' title='Site Université' style='margin-right:10px; color:white;'><i class='fas fa-university fa-lg'></i></a>";
        }
        
        $socialHtml .= '</div>';

        // --- 3. Génération du Menu ---
        $menuComponent = new UIMenu($this->menu);
        $menuHtml = $menuComponent->render();

        return <<<HTML
<footer>
    <div class="container footer-grid">
        <!-- Colonne 1 : Infos & Contact -->
        <div class="footer-col-info">
            <h3>{$siteName}</h3>
            <p style="margin-bottom:15px; opacity:0.8;">Pôle d'excellence en recherche.</p>
            
            <div class="contact-info" style="font-size:0.9em; line-height:1.8;">
                <div style="display:flex; align-items:flex-start; gap:10px;">
                    <i class="fas fa-map-marker-alt" style="margin-top:5px;"></i> 
                    <span>{$address}</span>
                </div>
                
                <div style="display:flex; align-items:center; gap:10px;">
                    <i class="fas fa-phone"></i> 
                    <span>{$phone}</span>
                </div>

                <div style="display:flex; align-items:center; gap:10px;">
                    <i class="fas fa-envelope"></i> 
                    <a href="mailto:{$email}" style="color:inherit; text-decoration:none;">{$email}</a>
                </div>
            </div>

            <!-- Icônes Réseaux Sociaux -->
            {$socialHtml}
        </div>

        <!-- Colonne 2 : Navigation Dynamique -->
        <div class="footer-nav">
            <h3>Navigation</h3>
            {$menuHtml}
        </div>

        <!-- Colonne 3 : Espace Membre -->
        <div class="footer-auth">
            <h3>Espace Membre</h3>
            <ul class="auth-links" style="list-style:none; padding:0;">
                <li style="margin-bottom:10px;">
                    <a href="../../../login.php" class="btn-login" style="display:inline-block; background:rgba(255,255,255,0.1); padding:8px 15px; border-radius:5px; text-decoration:none; color:white;">
                        <i class="fas fa-sign-in-alt"></i> Se connecter
                    </a>
                </li>
                
                <li style="margin-bottom:10px;">
                    <a href="../../../register_process.php" class="btn-login" style="display:inline-block; background:rgba(255,255,255,0.1); padding:8px 15px; border-radius:5px; text-decoration:none; color:white;" ><i class="fas fa-sign-in-alt"></i> Se connecterCréer un compte</a>
                </li> 
               
            </ul>
        </div>
    </div>

    <div class="footer-bottom">
        &copy; {$year} {$siteName}. Tous droits réservés.
    </div>
</footer>

<!-- Script Global Slider (si présent sur la page) -->
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const slides = document.querySelectorAll('.slide');
        if(slides.length > 1) {
            let current = 0;
            setInterval(() => {
                slides[current].classList.remove('active');
                current = (current + 1) % slides.length;
                slides[current].classList.add('active');
            }, 5000);
        }
    });
</script>
</body>
</html>
HTML;
    }
}
?>