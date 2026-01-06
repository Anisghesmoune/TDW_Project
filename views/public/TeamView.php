<?php
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../models/Model.php';
require_once __DIR__ . '/../../models/UserModel.php';
require_once __DIR__ . '/../../models/TeamsModel.php';

require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../controllers/TeamController.php';

require_once __DIR__ . '/../../controllers/ProjectController.php';
require_once __DIR__ . '/../../controllers/PublicationController.php';
require_once __DIR__ . '/../../controllers/EventController.php';
require_once __DIR__ . '/../../views/public/components/UIOrganigramme.php'; 

require_once __DIR__ . '/../../views/Sidebar.php';
require_once __DIR__ . '/../../views/Table.php';

// AuthController::requireAdmin();
$controller = new ProjectController();
$data = $controller->index(); 
$eventController = new EventController();
$eventData = $eventController->index();
$publicationController = new PublicationController();
$publicationData = $publicationController->stats();
$teamController= new TeamController();
// Récupérer les équipes et les utilisateurs
$teamModel = new TeamsModel();
$teams = $teamModel->getAllTeamsWithDetails();
$dataOrg=$teamController->index();

$userModel = new UserModel();
$users = $userModel->getAll(); // Pour le select du chef d'équipe
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Équipes - Laboratoire</title>
    <link rel="stylesheet" href="../admin_dashboard.css">
    <link rel="stylesheet" href='../../views/organigramme.css'>
    <link rel="stylesheet" href="../teamManagement.css">
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>⚙️ Administration</h2>
            <span class="admin-badge">ADMINISTRATEUR</span>
        </div>
       <?php 
       $sidebar = new Sidebar("admin");
       $sidebar->render(); 
       ?>
    </div>
    
    <div class="main-content">
        <div class="top-bar">
            <div>
                <h1>Gestion des Équipes de Recherche</h1>
                <p style="color: #666;">Organisation et structure des équipes</p>
            </div>
            <a href="../logout.php" class="logout-btn">Déconnexion</a>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Équipes</h3>
                <div class="number"><?php echo count($teams); ?></div>
            </div>
            
           
            
            <div class="stat-card">
                <h3>Publications</h3>
                <div class="number"><?php echo $publicationData['total']; ?></div>
            </div>
            
            <div class="stat-card">
                <h3>Événements à venir</h3>
                <div class="number"><?php echo $eventData['total']; ?></div>
            </div>
        </div>
       <section class="container" style="text-align:center; padding: 60px 20px;">
            <h1 class="page-title" style="color:var(--primary-color); font-size:2.5em; margin-bottom:20px;">Le Laboratoire</h1>
            <div style="max-width:800px; margin:0 auto; font-size:1.1em; line-height:1.6; color:#555;">
                <p>Notre laboratoire d'informatique est un centre d'excellence dédié à la recherche fondamentale et appliquée.
                Nous explorons des thématiques variées telles que l'Intelligence Artificielle, le Génie Logiciel, 
                les Systèmes Distribués et la Sécurité.</p>
            </div>
        </section>

  <?php
        // On vérifie si les données existent dans $dataOrg (pas $this->dataOrg)
        if (!empty($dataOrg['organigramme'])) {
            // Pas besoin de ob_start ici car on est directement dans le HTML
            $orgData = $dataOrg['organigramme'];
            
            // Instanciation de la Vue Organigramme
            
                $orgView = new OrganigrammeSectionView(
                    $orgData['director'] ?? null, 
                    $orgData['tree'] ?? [], 
                    $orgData['stats'] ?? null 
                );
                $orgView->render(); // Affiche le HTML de l'arbre
            }
     
        ?>
          <?php
            $teamTable = new Table([
                'id' => 'TeamsTable',
                'headers' => ['ID', 'Nom de l\'équipe', 'Chef d\'équipe', 'Domaine', 'Description'],
                'data' => $teams,
                'columns' => [
                    ['key' => 'id'],
                    ['key' => 'nom'],
                    ['key' => function($row) { 
                        if ($row['chef_nom']) {
                            return $row['resp_name'] . ' ' . $row['chef_nom'];
                        }
                        return 'Non défini';
                    }],
                    ['key' => 'domaine_recherche'],
                  


                    ['key' => function($row) {
                        $desc = $row['description'] ?? '';
                        return strlen($desc) > 50 ? substr($desc, 0, 50) . '...' : $desc;
                    }]
                ],
                'actions' => [
                    [
                        'icon' => '👁️',
                        'class' => 'btn-sm btn-view',
                        'onclick' => 'viewTeam({id})',
                        'label' => ' Voir'
                    ]
                    
                ]
            ]);

            $teamTable->display();
            ?>
        </div>
    </div>
    
   

    <script>
        // Fonction helper
        function ucfirst(str) {
            if (!str) return '';
            return str.charAt(0).toUpperCase() + str.slice(1);
        }

        // Voir une équipe
        function viewTeam(id) {
            window.location.href = `../team-details.php?id=${id}`;
        }

     
        // Charger les données d'une équipe
        async function loadTeamData(id) {
            try {
                const response = await fetch(`../controllers/api.php?action=getTeam&id=${id}`);
                const result = await response.json();

                if (result.success) {
                    const team = result.team;
                    document.getElementById('teamId').value = team.id;
                    document.getElementById('name').value = team.name;
                    document.getElementById('chef_equipe_id').value = team.chef_equipe_id;
                    document.getElementById('domaine_recherche').value = team.domaine_recherche || '';
                    document.getElementById('description').value = team.description || '';
                } else {
                    showAlert('❌ ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Erreur:', error);
                showAlert('❌ Impossible de charger les données.', 'error');
            }
        }


    </script>
</body>
</html><?php
