<?php
require_once __DIR__ . '/View.php';
require_once __DIR__ . '/components/UIHeader.php';
require_once __DIR__ . '/components/UIFooter.php';

class PartnerListView extends View {

    public function render() {
        $config = $this->data['config'] ?? [];
        $menuData = $this->data['menu'] ?? [];
        $pageTitle = "Nos Partenaires";

        $customCss = ['assets/css/public.css', 'views/landingPage.css'];

        $header = new UIHeader($pageTitle, $config, $menuData, $customCss);
        echo $header->render();

        echo '<main style="background-color:#f8f9fc; min-height:80vh; padding:60px 0;">';
        echo $this->content();
        echo '</main>';

        $footer = new UIFooter($config, $menuData);
        echo $footer->render();
    }

    protected function content() {
        $partners = $this->data['partners'] ?? [];
        
        ob_start();
        ?>
        <div class="container" style="max-width:1200px; margin:0 auto; padding:0 20px;">
            <div style="text-align:center; margin-bottom:60px;">
                <h1 style="color:#2c3e50; font-size:2.5em; margin-bottom:15px;">Nos Partenaires</h1>
                <p style="color:#666; font-size:1.1em; max-width:800px; margin:0 auto;">
                    Nous collaborons avec des institutions et entreprises de premier plan pour faire avancer la recherche.
                </p>
            </div>

            <?php if (empty($partners)): ?>
                <div style="text-align:center; padding:50px;">Aucun partenaire pour le moment.</div>
            <?php else: ?>
                <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap:30px;">
                    <?php foreach ($partners as $p): ?>
                        <div class="partner-card" style="background:white; border-radius:12px; overflow:hidden; box-shadow:0 5px 15px rgba(0,0,0,0.05); transition:transform 0.3s; display:flex; flex-direction:column;">
                            
                            <div style="height:150px; display:flex; align-items:center; justify-content:center; background:#fff; padding:20px; border-bottom:1px solid #f0f0f0;">
                                <?php if (!empty($p['logo'])): ?>
                                    <img src="<?= htmlspecialchars($p['logo']) ?>" alt="<?= htmlspecialchars($p['nom']) ?>" style="max-height:100%; max-width:100%; object-fit:contain;">
                                <?php else: ?>
                                    <span style="font-size:3em;">🏢</span>
                                <?php endif; ?>
                            </div>

                            <div style="padding:25px; flex:1; display:flex; flex-direction:column;">
                                <div style="margin-bottom:10px;">
                                    <span style="background:#e3f2fd; color:#1565c0; padding:4px 10px; border-radius:15px; font-size:0.75em; font-weight:bold; text-transform:uppercase; letter-spacing:0.5px;">
                                        <?= htmlspecialchars($p['type'] ?? 'Partenaire') ?>
                                    </span>
                                </div>
                                
                                <h3 style="margin:0 0 10px; color:#2c3e50; font-size:1.2em;"><?= htmlspecialchars($p['nom']) ?></h3>
                                
                                <p style="color:#666; font-size:0.9em; line-height:1.6; flex:1; margin-bottom:20px;">
                                    <?= nl2br(htmlspecialchars(substr($p['description'], 0, 100))) . (strlen($p['description']) > 100 ? '...' : '') ?>
                                </p>

                                <div style="margin-top:auto; padding-top:15px; border-top:1px solid #f0f0f0;">
                                    <?php if(!empty($p['url'])): ?>
                                        <a href="<?= htmlspecialchars($p['url']) ?>" target="_blank" style="color:#4e73df; text-decoration:none; font-weight:600; font-size:0.9em;">
                                            Visiter le site →
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <style>
            .partner-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        </style>
        <?php
        return ob_get_clean();
    }
}
?>