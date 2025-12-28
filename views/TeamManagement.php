<?php
require_once '../config/Database.php';
require_once '../models/Model.php';
require_once '../models/UserModel.php';
require_once '../models/TeamsModel.php';

require_once '../controllers/AuthController.php';
require_once '../controllers/ProjectController.php';
require_once '../controllers/PublicationController.php';
require_once '../controllers/EventController.php';

require_once '../views/Sidebar.php';
require_once '../views/Table.php';

// AuthController::requireAdmin();
$controller = new ProjectController();
$data = $controller->index(); 
$eventController = new EventController();
$eventData = $eventController->getEvents();
$publicationController = new PublicationController();
$publicationData = $publicationController->stats();

// Récupérer les équipes et les utilisateurs
$teamModel = new TeamsModel();
$teams = $teamModel->getAllTeamsWithDetails();
$userModel = new UserModel();
$users = $userModel->getAll(); // Pour le select du chef d'équipe
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Équipes - Laboratoire</title>
    <link rel="stylesheet" href="admin_dashboard.css">
    <link rel="stylesheet" href="modelAddUser.css">
    <link rel="stylesheet" href="teamManagement.css">
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
                <h3>Projets actifs</h3>
                <div class="number"><?php echo $data['nbProjetsActifs']; ?></div>
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
        
        <div class="content-section">
            <h2>
                <span>Gestion des Équipes</span>
                <button class="btn btn-primary" onclick="openModal()">
                    ➕ Ajouter une équipe
                </button>
            </h2>
            <?php
            $teamTable = new Table([
                'id' => 'TeamsTable',
                'headers' => ['ID', 'Nom de l\'équipe', 'Chef d\'équipe', 'Domaine', 'Description'],
                'data' => $teams,
                'columns' => [
                    ['key' => 'id'],
                    ['key' => 'name'],
                    ['key' => function($row) { 
                        if ($row['chef_nom']) {
                            return $row['chef_prenom'] . ' ' . $row['chef_nom'];
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
                    ],
                    [
                        'icon' => '✏️',
                        'class' => 'btn-sm btn-edit',
                        'onclick' => 'editTeam({id})',
                        'label' => ' Modifier'
                    ],
                    [
                        'icon' => '🗑️',
                        'class' => 'btn-sm btn-delete',
                        'onclick' => 'deleteTeam({id})',
                        'label' => ' Supprimer'
                    ]
                ]
            ]);

            $teamTable->display();
            ?>
        </div>
    </div>
    
    <!-- Modal Ajouter/Modifier Équipe -->
    <div id="teamModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">➕ Ajouter une équipe</h2>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="teamForm">
                    <div id="alertContainer"></div>

                    <input type="hidden" id="teamId" name="id">
                    
                    <div class="form-group">
                        <label for="name">Nom de l'équipe <span class="required">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" required placeholder="Ex: Équipe IA">
                    </div>

                    <div class="form-group">
                        <label for="chef_equipe_id">Chef d'équipe <span class="required">*</span></label>
                        <select class="form-control" id="chef_equipe_id" name="chef_equipe_id" required>
                            <option value="">-- Sélectionner un chef d'équipe --</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['id']; ?>">
                                    <?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom'] . ' (' . $user['role'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="domaine_recherche">Domaine de recherche <span class="required">*</span></label>
                        <input type="text" class="form-control" id="domaine_recherche" name="domaine_recherche" required placeholder="Ex: Intelligence Artificielle">
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="4" placeholder="Décrivez les objectifs et activités de l'équipe..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Annuler</button>
                <button type="button" class="btn btn-primary" onclick="saveTeam()">💾 Enregistrer</button>
            </div>
        </div>
    </div>

    <script>
        // Fonction helper
        function ucfirst(str) {
            if (!str) return '';
            return str.charAt(0).toUpperCase() + str.slice(1);
        }

        // Ouvrir le modal
        function openModal(editMode = false, teamId = null) {
            const modal = document.getElementById('teamModal');
            const modalTitle = document.getElementById('modalTitle');
            const form = document.getElementById('teamForm');
            
            if (editMode && teamId) {
                modalTitle.textContent = '✏️ Modifier l\'équipe';
                loadTeamData(teamId);
            } else {
                modalTitle.textContent = '➕ Ajouter une équipe';
                form.reset();
                document.getElementById('teamId').value = '';
            }
            
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        // Fermer le modal
        function closeModal() {
            const modal = document.getElementById('teamModal');
            modal.classList.remove('active');
            document.body.style.overflow = 'auto';
            document.getElementById('teamForm').reset();
        }

        // Fermer avec clic extérieur
        document.getElementById('teamModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // Sauvegarder une équipe
        function saveTeam() {
            const form = document.getElementById('teamForm');
            const formData = new FormData(form);
            
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            
            const data = Object.fromEntries(formData);
            const teamId = document.getElementById('teamId').value;
            const action = teamId ? 'updateTeam' : 'createTeam';
            
            console.log('Données à envoyer:', data);
            
            fetch(`../controllers/api.php?action=${action}${teamId ? '&id=' + teamId : ''}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showAlert('✅ Équipe enregistrée avec succès!', 'success');
                    closeModal();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showAlert('❌ Erreur: ' + result.message, 'error');
                }
            })
            .catch(error => {
                showAlert('❌ Erreur de connexion', 'error');
                console.error(error);
            });
        }

        // Afficher une alerte
        function showAlert(message, type) {
            const container = document.getElementById('alertContainer');
            
            if (!container) {
                alert(message);
                return;
            }
            
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} show`;
            alertDiv.textContent = message;
            container.appendChild(alertDiv);
            
            setTimeout(() => {
                alertDiv.remove();
            }, 3000);
        }

        // Voir une équipe
        function viewTeam(id) {
            window.location.href = `team-details.php?id=${id}`;
        }

        // Éditer une équipe
        function editTeam(id) {
            openModal(true, id);
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

        // Supprimer une équipe
        function deleteTeam(id) {
            if (confirm('Êtes-vous sûr de vouloir supprimer cette équipe ? Tous les membres seront retirés.')) {
                fetch(`../controllers/api.php?action=deleteTeam&id=${id}`, {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        showAlert('✅ Équipe supprimée', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showAlert('❌ Erreur: ' + result.message, 'error');
                    }
                })
                .catch(error => {
                    showAlert('❌ Erreur de connexion', 'error');
                });
            }
        }

        // Fermer avec Échap
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
    </script>
</body>
</html><?php
// views/Table.php