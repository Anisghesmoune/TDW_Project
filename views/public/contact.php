<?php
require_once __DIR__ . '/../../views/public/View.php';
require_once __DIR__ . '/../../views/public/components/UIHeader.php';
require_once __DIR__ . '/../../views/public/components/UIFooter.php';

class ContactView extends View {

    public function render() {
        $config = $this->data['config'] ?? [];
        $menuData = $this->data['menu'] ?? [];

      $customCss = [
            '../../views/landingPage.css',
            
        ];
        $header = new UIHeader("Contact", $config, $menuData, $customCss);
        echo $header->render();

        echo '<main class="main-content">';
        echo $this->content();
        echo '</main>';

        $footer = new UIFooter($config, $menuData);
        echo $footer->render();
    }

    protected function content() {
        $config = $this->data['config'] ?? [];
        
        $html = <<<HTML
        <section class="container" style="text-align:center; padding: 40px 20px;">
            <h1 class="page-title" style="color:var(--primary-color);">Nous Contacter</h1>
            <p>Une question ? Une proposition de collaboration ? N'hésitez pas à nous écrire.</p>
        </section>

        <div class="container" style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 60px;">
HTML;

        $adresse = htmlspecialchars($config['lab_address'] ?? 'Adresse non définie');
        $tel = htmlspecialchars($config['lab_phone'] ?? 'Non défini');
        $email = htmlspecialchars($config['lab_email'] ?? 'Non défini');
        
        $socialLinks = '';
        if(!empty($config['social_facebook'])) {
            $socialLinks .= '<a href="'.htmlspecialchars($config['social_facebook']).'" target="_blank" style="color:#3b5998;"><i class="fab fa-facebook"></i></a>';
        }
        if(!empty($config['social_linkedin'])) {
            $socialLinks .= '<a href="'.htmlspecialchars($config['social_linkedin']).'" target="_blank" style="color:#0077b5;"><i class="fab fa-linkedin"></i></a>';
        }
        if(!empty($config['univ_website'])) {
            $socialLinks .= '<a href="'.htmlspecialchars($config['univ_website']).'" target="_blank" style="color:#333;"><i class="fas fa-university"></i></a>';
        }

        $html .= <<<HTML
            <div class="coordonnees-section">
                <div class="generic-card" style="padding:30px; background:white; border-radius:10px; border:1px solid #eee;">
                    <h3 style="margin-top:0; color:#333; border-bottom:2px solid var(--primary-color); padding-bottom:10px; display:inline-block;">Coordonnées</h3>
                    
                    <div style="margin-top:20px; font-size:1.1em; line-height:2;">
                        <p><i class="fas fa-map-marker-alt" style="color:var(--primary-color); width:25px;"></i> $adresse</p>
                        <p><i class="fas fa-phone" style="color:var(--primary-color); width:25px;"></i> $tel</p>
                        <p><i class="fas fa-envelope" style="color:var(--primary-color); width:25px;"></i> 
                           <a href="mailto:$email" style="color:inherit; text-decoration:none;">$email</a>
                        </p>
                    </div>

                    <div style="margin-top:30px;">
                        <h4>Suivez-nous</h4>
                        <div style="display:flex; gap:15px; font-size:1.5em; margin-top:10px;">
                            $socialLinks
                        </div>
                    </div>
                </div>

               <!-- map -->
                <div style="margin-top:30px; border-radius:10px; overflow:hidden; box-shadow:0 4px 10px rgba(0,0,0,0.1);">
                    <iframe 
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3197.669146200276!2d3.170884315609439!3d36.70502997996818!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x128e522f3f7283eb%3A0x6296b02672533036!2sEcole%20nationale%20Sup%C3%A9rieure%20d&#39;Informatique%20(ESI%20ex.%20INI)!5e0!3m2!1sfr!2sdz!4v1625582745821!5m2!1sfr!2sdz" 
                        width="100%" height="300" style="border:0;" allowfullscreen="" loading="lazy">
                    </iframe>
                </div>
            </div>
HTML;

        $html .= <<<HTML
            <div class="contact-form">
                <div class="generic-card" style="padding:30px; background:white; border-radius:10px; border:1px solid #eee;">
                    <h3 style="margin-top:0; color:#333;">Envoyer un message</h3>
                    <p style="color:#666; font-size:0.9em; margin-bottom:20px;">Remplissez ce formulaire, nous vous répondrons dans les plus brefs délais.</p>
                    
                    <form id="contactForm">
                        <div id="alertMsg" style="display:none; padding:15px; border-radius:5px; margin-bottom:20px; font-weight:bold;"></div>

                        <div style="margin-bottom:15px;">
                            <label style="display:block; font-weight:bold; margin-bottom:5px;">Nom complet</label>
                            <input type="text" name="nom" required class="form-control" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:5px;">
                        </div>

                        <div style="margin-bottom:15px;">
                            <label style="display:block; font-weight:bold; margin-bottom:5px;">Email</label>
                            <input type="email" name="email" required class="form-control" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:5px;">
                        </div>

                        <div style="margin-bottom:15px;">
                            <label style="display:block; font-weight:bold; margin-bottom:5px;">Sujet</label>
                            <select name="sujet" class="form-control" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:5px;">
                                <option value="Renseignement">Demande de renseignement</option>
                                <option value="Partenariat">Proposition de partenariat</option>
                                <option value="Candidature">Candidature (Stage/Thèse)</option>
                                <option value="Autre">Autre</option>
                            </select>
                        </div>

                        <div style="margin-bottom:20px;">
                            <label style="display:block; font-weight:bold; margin-bottom:5px;">Message</label>
                            <textarea name="message" required rows="5" class="form-control" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:5px;"></textarea>
                        </div>

                        <button type="submit" class="btn-primary" style="width:100%; padding:12px; background:var(--primary-color); color:white; border:none; border-radius:5px; font-weight:bold; cursor:pointer;">
                            Envoyer le message
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <script>
        document.getElementById('contactForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = this.querySelector('button[type="submit"]');
            const alertBox = document.getElementById('alertMsg');
            
            const originalText = btn.innerHTML;
            btn.innerHTML = "Envoi en cours...";
            btn.disabled = true;
            alertBox.style.display = 'none';

            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());

            try {
                const response = await fetch('index.php?route=send-contact', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                alertBox.style.display = 'block';
                if (result.success) {
                    alertBox.style.backgroundColor = '#d1fae5';
                    alertBox.style.color = '#065f46';
                    alertBox.innerHTML = '✅ ' + result.message;
                    this.reset();
                } else {
                    alertBox.style.backgroundColor = '#fee2e2';
                    alertBox.style.color = '#b91c1c';
                    alertBox.innerHTML = '❌ ' + result.message;
                }

            } catch (error) {
                console.error(error);
                alertBox.style.display = 'block';
                alertBox.style.backgroundColor = '#fee2e2';
                alertBox.innerHTML = '❌ Erreur technique lors de l\'envoi.';
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        });
        </script>
HTML;

        return $html;
    }
}
?>