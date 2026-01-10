<?php
// Imports des dépendances
require_once __DIR__ . '/../../views/public/View.php';
require_once __DIR__ . '/../../views/public/components/UIHeader.php';
require_once __DIR__ . '/../../views/public/components/UIFooter.php';
// Composants spécifiques à cette page
require_once __DIR__ . '/../../views/public/components/UIOrganigramme.php';
require_once __DIR__ . '/../../views/Table.php';

class TeamView extends View {

    /**
     * Méthode principale pour structurer la page
     */
    public function render() {
        // Extraction des données globales
        $config = $this->data['config'] ?? [];
        $menuData = $this->data['menu'] ?? [];
        $pageTitle = $this->data['title'] ?? 'Gestion des Équipes';

        // CSS spécifiques
        $customCss = [
            'views/admin_dashboard.css',
            "views/landingPage.css",
            'views/organigramme.css',
            'views/teamManagement.css',
            'assets/css/public.css' // Pour le header
        ];

        // 1. Rendu du Header
        $header = new UIHeader($pageTitle, $config, $menuData, $customCss);
        echo $header->render();

        // 2. Contenu Principal
        echo '<main style="width: 100%; padding: 40px 20px; box-sizing: border-box; background-color: #f8f9fc; min-height: 80vh;">';
        echo $this->content();
        echo '</main>';

        // 3. Rendu du Footer
        $footer = new UIFooter($config, $menuData);
        echo $footer->render();
    }

    /**
     * Contenu spécifique (Stats, Organigramme, Tableau)
     */
    protected function content() {
        // Extraction des données métier
        $teams = $this->data['teams'] ?? [];
        $projects = $this->data['projects'] ?? [];
        $organigrammeData = $this->data['organigramme'] ?? [];

        ob_start();
        ?>
        
        <!-- En-tête interne -->
        <div class="top-bar" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 1px solid #eee; padding-bottom: 20px;">
            <div>
                <h1 style="margin:0; color: #2c3e50;">Gestion des Équipes de Recherche</h1>
                <p style="color: #666; margin-top:5px;">Organisation et structure des équipes</p>
            </div>
        </div>
        
        <!-- Statistiques -->
        <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 40px;">
            <div class="stat-card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); text-align: center; border-bottom: 4px solid #4e73df;">
                <h3 style="color:#888; font-size:0.9em; text-transform:uppercase;">Total Équipes</h3>
                <div class="number" style="font-size:2em; font-weight:bold; color:#333;"><?php echo count($teams); ?></div>
            </div>
            
            <div class="stat-card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); text-align: center; border-bottom: 4px solid #1cc88a;">
                <h3 style="color:#888; font-size:0.9em; text-transform:uppercase;">Total Projets</h3>
                <div class="number" style="font-size:2em; font-weight:bold; color:#333;"><?php echo count($projects); ?></div>
            </div>
            
            <div class="stat-card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); text-align: center; border-bottom: 4px solid #36b9cc;">
                <h3 style="color:#888; font-size:0.9em; text-transform:uppercase;">Publications</h3>
                <div class="number" style="font-size:2em; font-weight:bold; color:#333;">0</div>
            </div>
        </div>
        
        <!-- Description Labo -->
        <section class="container" style="text-align:center; padding: 20px; background: white; border-radius: 10px; margin-bottom: 40px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
            <h1 class="page-title" style="color:#4e73df; font-size:2em; margin-bottom:20px;">Le Laboratoire</h1>
            <div style="max-width:800px; margin:0 auto; font-size:1.1em; line-height:1.6; color:#555;">
                <p>Notre laboratoire d'informatique est un centre d'excellence dédié à la recherche fondamentale et appliquée.
                Nous explorons des thématiques variées telles que l'Intelligence Artificielle, le Génie Logiciel, 
                les Systèmes Distribués et la Sécurité.</p>
            </div>
        </section>

        <!-- Organigramme -->
        <?php
        if (!empty($organigrammeData)) {
            try {
                echo '<div style="background: white; padding: 20px; border-radius: 10px; margin-bottom: 40px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">';
                $orgView = new OrganigrammeSectionView(
                    $organigrammeData['director'] ?? null, 
                    $organigrammeData['tree'] ?? [], 
                    null 
                );
                $orgView->render();
                echo '</div>';
            } catch (Exception $e) {
                echo '<div class="alert alert-error">Erreur lors de l\'affichage de l\'organigramme</div>';
            }
        }
        ?>
        
        <!-- Tableau des Équipes -->
        <div style="background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
            <h2 style="margin-bottom: 20px; color: #2c3e50; border-bottom: 2px solid #eee; padding-bottom: 10px;">Liste des Équipes</h2>
            <?php
            // Préparation des données pour le composant Table
            $teamsForTable = [];
            foreach ($teams as $team) {
                $info = $team['info'] ?? [];
                $leader = $team['leader'] ?? null;
                
                $teamsForTable[] = [
                    'id' => $info['id'] ?? 0,
                    'nom' => $info['nom'] ?? 'Sans nom',
                    'resp_name' => $leader['prenom'] ?? '',
                    'chef_nom' => $leader['nom'] ?? '',
                    'domaine_recherche' => $info['domaine_recherche'] ?? 'Non défini',
                    'description' => $info['description'] ?? ''
                ];
            }

            $teamTable = new Table([
                'id' => 'TeamsTable',
                'headers' => ['ID', 'Nom de l\'équipe', 'Chef d\'équipe', 'Domaine', 'Description'],
                'data' => $teamsForTable,
                'columns' => [
                    ['key' => 'id'],
                    ['key' => 'nom'],
                    ['key' => function($row) { 
                        if (!empty($row['chef_nom'])) {
                            return '<strong>' . trim($row['resp_name'] . ' ' . $row['chef_nom']) . '</strong>';
                        }
                        return '<em style="color: #999;">Non défini</em>';
                    }],
                    ['key' => 'domaine_recherche'],
                    ['key' => function($row) {
                        $desc = $row['description'] ?? '';
                        if (empty($desc)) {
                            return '<em style="color: #999;">Aucune description</em>';
                        }
                        return strlen($desc) > 50 ? substr($desc, 0, 50) . '...' : $desc;
                    }]
                ],
                'actions' => [
                    [
                        'icon' => '👁️',
                        'class' => 'btn-sm btn-view',
                        // Note: Assurez-vous que le style des boutons est chargé ou ajoutez-le inline
                        'onclick' => 'viewTeam({id})',
                        'label' => ' Voir'
                    ]
                ]
            ]);

            $teamTable->display();
            ?>
        </div>

        <script>
            // Fonction helper
            function ucfirst(str) {
                if (!str) return '';
                return str.charAt(0).toUpperCase() + str.slice(1);
            }

            // Voir une équipe (Redirection)
            function viewTeam(id) {
                window.location.href = `index.php?route=teams-details&id=${id}`;
            }

            // Charger les données d'une équipe (si nécessaire pour modal, sinon laisser tel quel)
            async function loadTeamData(id) {
                try {
                    const response = await fetch(`../controllers/api.php?action=getTeam&id=${id}`);
                    const result = await response.json();

                    if (result.success) {
                        const team = result.team;
                        // Logique de remplissage de modal ici si besoin
                        console.log("Team loaded", team);
                    } else {
                        alert('❌ ' + result.message);
                    }
                } catch (error) {
                    console.error('Erreur:', error);
                    alert('❌ Impossible de charger les données.');
                }
            }
        </script>
        <?php
        return ob_get_clean();
    }
}
?>