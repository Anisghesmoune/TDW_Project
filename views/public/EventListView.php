<?php
// Imports des dépendances
require_once __DIR__ . '/View.php';
require_once __DIR__ . '/components/UIHeader.php';
require_once __DIR__ . '/components/UIFooter.php';

class EventListView extends View {

    public function render() {
        $config = $this->data['config'] ?? [];
        $menuData = $this->data['menu'] ?? [];
        $pageTitle = $this->data['title'] ?? 'Agenda';

        // CSS spécifiques
        $customCss = [
            'assets/css/public.css',
            'views/landingPage.css' // Pour réutiliser le style des cartes/grilles
        ];

        // Header
        $header = new UIHeader($pageTitle, $config, $menuData, $customCss);
        echo $header->render();

        // Contenu
        echo '<main style="background-color: #f8f9fc; min-height: 80vh; padding: 60px 0;">';
        echo $this->content();
        echo '</main>';

        // Footer
        $footer = new UIFooter($config, $menuData);
        echo $footer->render();
    }

    protected function content() {
        $events = $this->data['events'] ?? [];

        ob_start();
        ?>
        <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
            
            <!-- En-tête de section -->
            <div style="text-align: center; margin-bottom: 50px;">
                <h1 style="color: #2c3e50; font-size: 2.5em; margin-bottom: 15px;">📅 Agenda du Laboratoire</h1>
                <p style="color: #666; font-size: 1.1em; max-width: 800px; margin: 0 auto;">
                    Conférences, séminaires, soutenances de thèses et workshops. Retrouvez tous les événements scientifiques à venir.
                </p>
            </div>

            <!-- Liste des événements -->
            <?php if (empty($events)): ?>
                <div style="text-align: center; padding: 50px; background: white; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                    <p style="font-size: 1.2em; color: #888;">Aucun événement programmé pour le moment.</p>
                </div>
            <?php else: ?>
                <div class="events-list" style="display: flex; flex-direction: column; gap: 20px;">
                    <?php foreach ($events as $evt): ?>
                        <?php 
                            // Formatage des dates
                            $dateDebut = new DateTime($evt['date_debut']);
                            $jour = $dateDebut->format('d');
                            $mois = $this->getMoisFrancais($dateDebut->format('m'));
                            $heure = $dateDebut->format('H:i');
                            
                            // Badge type
                            $typeColor = '#4e73df'; // Bleu par défaut
                            if (stripos($evt['type_nom'] ?? '', 'conférence') !== false) $typeColor = '#e74a3b'; // Rouge
                            if (stripos($evt['type_nom'] ?? '', 'soutenance') !== false) $typeColor = '#1cc88a'; // Vert
                            
                            // Lien google maps pour le lieu (optionnel)
                            $lieuLink = 'https://www.google.com/maps/search/?api=1&query=' . urlencode($evt['localisation'] ?? '');
                        ?>
                        
                        <div class="event-card" style="display: flex; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.05); transition: transform 0.2s;">
                            
                            <!-- Date (Côté gauche) -->
                            <div class="event-date" style="background: <?= $typeColor ?>; color: white; padding: 20px; display: flex; flex-direction: column; align-items: center; justify-content: center; min-width: 80px;">
                                <span style="font-size: 1.8em; font-weight: bold;"><?= $jour ?></span>
                                <span style="text-transform: uppercase; font-size: 0.9em;"><?= $mois ?></span>
                            </div>

                            <!-- Contenu -->
                            <div class="event-content" style="padding: 20px; flex: 1;">
                                <div style="margin-bottom: 10px;">
                                    <span style="color: <?= $typeColor ?>; font-weight: bold; text-transform: uppercase; font-size: 0.8em; letter-spacing: 1px;">
                                        <?= htmlspecialchars($evt['type_nom'] ?? 'Événement') ?>
                                    </span>
                                </div>
                                
                                <h3 style="margin: 0 0 10px 0; color: #333; font-size: 1.4em;">
                                    <?= htmlspecialchars($evt['titre']) ?>
                                </h3>
                                
                                <p style="color: #666; margin-bottom: 15px; line-height: 1.5;">
                                    <?= nl2br(htmlspecialchars(substr($evt['description'], 0, 150))) . (strlen($evt['description']) > 150 ? '...' : '') ?>
                                </p>
                                
                                <div class="event-meta" style="display: flex; gap: 20px; color: #888; font-size: 0.9em; border-top: 1px solid #f0f0f0; padding-top: 15px;">
                                    <span><i class="far fa-clock"></i> <?= $heure ?></span>
                                    <span>
                                        <i class="fas fa-map-marker-alt"></i> 
                                        <a href="<?= $lieuLink ?>" target="_blank" style="color: inherit; text-decoration: none; border-bottom: 1px dotted #ccc;">
                                            <?= htmlspecialchars($evt['localisation'] ?? 'Non défini') ?>
                                        </a>
                                    </span>
                                    <?php if(!empty($evt['capacite_max'])): ?>
                                        <span><i class="fas fa-users"></i> Max: <?= $evt['capacite_max'] ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Action (Côté droit) -->
                            <div class="event-action" style="padding: 20px; display: flex; align-items: center; border-left: 1px solid #f0f0f0; background: #fafafa;">
                                <button onclick="alert('Détails bientôt disponibles')" class="btn-details" style="padding: 10px 20px; border: 1px solid #ddd; background: white; border-radius: 5px; cursor: pointer; color: #555; transition: 0.2s;">
                                    Détails
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <style>
            .event-card:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
            .btn-details:hover { background: #4e73df; color: white; border-color: #4e73df; }
            @media (max-width: 768px) {
                .event-card { flex-direction: column; }
                .event-date { flex-direction: row; gap: 10px; padding: 10px; }
                .event-action { border-left: none; border-top: 1px solid #f0f0f0; justify-content: center; }
            }
        </style>
        <?php
        return ob_get_clean();
    }

    // Helper pour les mois
    private function getMoisFrancais($num) {
        $mois = [
            '01' => 'Jan', '02' => 'Fév', '03' => 'Mar', '04' => 'Avr',
            '05' => 'Mai', '06' => 'Juin', '07' => 'Juil', '08' => 'Août',
            '09' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Déc'
        ];
        return $mois[$num] ?? '';
    }
}
?>