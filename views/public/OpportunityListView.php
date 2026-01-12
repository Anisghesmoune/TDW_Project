<?php
require_once __DIR__ . '/View.php';
require_once __DIR__ . '/components/UIHeader.php';
require_once __DIR__ . '/components/UIFooter.php';

class OpportunityListView extends View {

    public function render() {
        $config = $this->data['config'] ?? [];
        $menuData = $this->data['menu'] ?? [];
        $pageTitle = "Offres et Opportunités";

        $customCss = [
            'assets/css/public.css', // Assurez-vous d'avoir un CSS de base
            'views/landingPage.css'  // Pour réutiliser les styles de grille
        ];

        $header = new UIHeader($pageTitle, $config, $menuData, $customCss);
        echo $header->render();

        echo '<main style="background-color:#f8f9fc; min-height:80vh; padding:40px 0;">';
        echo $this->content();
        echo '</main>';

        $footer = new UIFooter($config, $menuData);
        echo $footer->render();
    }

    protected function content() {
        $opportunities = $this->data['opportunities'] ?? [];
        
        ob_start();
        ?>
        <div class="container" style="max-width:1200px; margin:0 auto; padding:0 20px;">
            <div style="text-align:center; margin-bottom:50px;">
                <h1 style="color:#2c3e50; font-size:2.5em; margin-bottom:15px;">Offres & Opportunités</h1>
                <p style="color:#666; font-size:1.1em; max-width:800px; margin:0 auto;">
                    Découvrez les dernières offres de stages, thèses, bourses et collaborations proposées par notre laboratoire.
                </p>
            </div>

            <?php if (empty($opportunities)): ?>
                <div style="text-align:center; padding:50px; background:white; border-radius:10px; box-shadow:0 2px 10px rgba(0,0,0,0.05);">
                    <p style="font-size:1.2em; color:#888;">Aucune offre disponible pour le moment.</p>
                </div>
            <?php else: ?>
                <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap:30px;">
                    <?php foreach ($opportunities as $opp): ?>
                        <?php 
                        $badgeColor = '#6c757d';
                        if ($opp['type'] == 'stage') $badgeColor = '#1cc88a';
                        if ($opp['type'] == 'thèse') $badgeColor = '#4e73df';
                        if ($opp['type'] == 'bourse') $badgeColor = '#f6c23e';
                        
                        $isExpired = strtotime($opp['date_expiration']) < time();
                        $opacity = $isExpired ? '0.6' : '1';
                        ?>
                        
                        <div class="opp-card" style="background:white; border-radius:10px; overflow:hidden; box-shadow:0 4px 15px rgba(0,0,0,0.05); transition:transform 0.3s; opacity:<?= $opacity ?>;">
                            <div style="padding:25px;">
                                <div style="display:flex; justify-content:space-between; margin-bottom:15px;">
                                    <span style="background:<?= $badgeColor ?>; color:white; padding:4px 12px; border-radius:20px; font-size:0.8em; font-weight:bold; text-transform:uppercase;">
                                        <?= htmlspecialchars($opp['type']) ?>
                                    </span>
                                    <?php if($isExpired): ?>
                                        <span style="color:#e74a3b; font-weight:bold; font-size:0.9em;">Expirée</span>
                                    <?php endif; ?>
                                </div>
                                
                                <h3 style="margin:0 0 15px; color:#2c3e50; font-size:1.3em;">
                                    <?= htmlspecialchars($opp['titre']) ?>
                                </h3>
                                
                                <div style="color:#555; line-height:1.6; margin-bottom:20px; min-height:80px;">
                                    <?= nl2br(htmlspecialchars(substr($opp['description'], 0, 150))) . (strlen($opp['description']) > 150 ? '...' : '') ?>
                                </div>
                                
                                <div style="border-top:1px solid #f0f0f0; padding-top:15px; margin-top:15px;">
                                    <p style="margin:0 0 5px; font-size:0.9em; color:#666;">
                                        <strong>📅 Date limite :</strong> <?= date('d/m/Y', strtotime($opp['date_expiration'])) ?>
                                    </p>
                                    <?php if(!empty($opp['contact'])): ?>
                                        <p style="margin:0; font-size:0.9em; color:#666;">
                                            <strong>📧 Contact :</strong> <a href="mailto:<?= htmlspecialchars($opp['contact']) ?>" style="color:#4e73df;"><?= htmlspecialchars($opp['contact']) ?></a>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <style>
            .opp-card:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(0,0,0,0.1); }
        </style>
        <?php
        return ob_get_clean();
    }
}
?>