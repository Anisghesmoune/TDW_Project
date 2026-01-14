<?php
// Imports des dépendances
require_once __DIR__ . '/../views/public/View.php';
require_once __DIR__ . '/../views/public/components/UIHeader.php';
require_once __DIR__ . '/../views/public/components/UIFooter.php';
// Composants spécifiques
require_once __DIR__ . '/../views/public/components/UIOrganigramme.php';
require_once __DIR__ . '/../views/Table.php';

class TeamAdminView extends View {

    public function render() {
        $config = $this->data['config'] ?? [];
        $menuData = $this->data['menu'] ?? [];
        $pageTitle = $this->data['title'] ?? 'Gestion des Équipes';

        $customCss = [
            'views/admin_dashboard.css',
            'views/organigramme.css',
            'views/teamManagement.css',
            'views/modelAddUser.css',
            'views/landingPage.css'
        ];

        $header = new UIHeader($pageTitle, $config, $menuData, $customCss);
        echo $header->render();

        echo '<main style="width: 100%; padding: 40px 20px; box-sizing: border-box; background-color: #f8f9fc; min-height: 80vh;">';
        echo $this->content();
        echo '</main>';

       
    }

   
    protected function content() {
        $teams = $this->data['teams'] ?? [];
        $users = $this->data['users'] ?? [];
        $projects = $this->data['projects'] ?? [];
        $publicationData = $this->data['publicationData'] ?? ['total' => 0];
        $organigrammeData = $this->data['organigramme'] ?? [];

        $teamsForTable = [];
        foreach ($teams as $team) {
            // Gestion des structures parfois différentes (array plat ou nested 'info')
            $teamid = $team['info']['id'] ?? $team['id'];
            $nom = $team['info']['nom'] ?? $team['nom'];
            $domaine = $team['info']['domaine_recherche'] ?? $team['domaine_recherche'];
            $desc = $team['info']['description'] ?? $team['description'];
            
            $chefNom = '';
            if (isset($team['leader'])) {
                $chefNom = ($team['leader']['prenom'] ?? '') . ' ' . ($team['leader']['nom'] ?? '');
            } elseif (isset($team['chef_nom'])) {
                $chefNom = ($team['chef_prenom'] ?? '') . ' ' . $team['chef_nom'];
            }

            $teamsForTable[] = [
                'id' => $teamid,
                'nom' => $nom,
                'chef_nom' => $chefNom,
                'domaine_recherche' => $domaine,
                'description' => $desc
            ];
        }

        ob_start();
        ?>
        
        <style>
            .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
            .stat-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); text-align: center; border-bottom: 4px solid #ddd; }
            .stat-card:nth-child(1) { border-color: #4e73df; }
            .stat-card:nth-child(2) { border-color: #1cc88a; }
            .stat-card:nth-child(3) { border-color: #36b9cc; }
            .stat-card .number { font-size: 2em; font-weight: bold; margin-top: 10px; }
            
            .content-section { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); margin-top: 30px; }
            .content-section h2 { margin-top: 0; color: #2e384d; border-bottom: 2px solid #f8f9fc; padding-bottom: 20px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
            
            .modal { display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background-color:rgba(0,0,0,0.5); backdrop-filter: blur(2px); }
            .modal.active { display: flex; align-items: center; justify-content: center; }
            .modal-content { background:white; padding:30px; border-radius:10px; max-height:90vh; overflow-y:auto; box-shadow: 0 10px 30px rgba(0,0,0,0.2); width: 90%; max-width: 600px; }
            .modal-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 20px; }
            .close-btn { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #aaa; }
            
            .form-group { margin-bottom: 15px; }
            .form-control { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
            .btn-primary { background: #4e73df; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; }
            .btn-secondary { background: #858796; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; }
            .required { color: #e74a3b; }
        </style>

        <div class="top-bar" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 1px solid #eee; padding-bottom: 20px;">
            <div>
                <h1 style="margin: 0; color: #2c3e50;">Gestion des Équipes de Recherche</h1>
                <p style="color: #666; margin-top: 5px;">Organisation et structure des équipes</p>
            </div>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Équipes</h3>
                <div class="number"><?php echo count($teams); ?></div>
            </div>
            
            <div class="stat-card">
                <h3>Total Projets</h3>
                <div class="number"><?php echo count($projects); ?></div>
            </div>
            
            <div class="stat-card">
                <h3>Publications</h3>
                <div class="number"><?php echo is_array($publicationData) ? ($publicationData['total'] ?? 0) : $publicationData; ?></div>
            </div>
        </div>
        
      
        
        <div class="content-section">
            <h2>
                <span>Gestion des Équipes</span>
                <button class="btn-primary" onclick="openModal()">
                    ➕ Ajouter une équipe
                </button>
            </h2>
            <?php
            $teamTable = new Table([
                'id' => 'TeamsTable',
                'headers' => ['ID', 'Nom de l\'équipe', 'Chef d\'équipe', 'Domaine', 'Description'],
                'data' => $teamsForTable,
                'columns' => [
                    ['key' => 'id'],
                    ['key' => 'nom'],
                    ['key' => function($row) { 
                        return !empty($row['chef_nom']) ? $row['chef_nom'] : 'Non défini';
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
    
        <div id="teamModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 id="modalTitle" style="color:#4e73df; margin:0;">➕ Ajouter une équipe</h2>
                    <button class="close-btn" onclick="closeModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="teamForm">
                        <div id="alertContainer"></div>

                        <input type="hidden" id="teamId" name="id">
                        
                        <div class="form-group">
                            <label for="name" style="display:block; margin-bottom:5px; font-weight:bold;">Nom de l'équipe <span class="required">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" required placeholder="Ex: Équipe IA">
                        </div>

                        <div class="form-group">
                            <label for="chef_equipe_id" style="display:block; margin-bottom:5px; font-weight:bold;">Chef d'équipe <span class="required">*</span></label>
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
                            <label for="domaine_recherche" style="display:block; margin-bottom:5px; font-weight:bold;">Domaine de recherche <span class="required">*</span></label>
                            <input type="text" class="form-control" id="domaine_recherche" name="domaine_recherche" required placeholder="Ex: Intelligence Artificielle">
                        </div>

                        <div class="form-group">
                            <label for="description" style="display:block; margin-bottom:5px; font-weight:bold;">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4" placeholder="Décrivez les objectifs..."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer" style="border-top:1px solid #eee; padding-top:15px; text-align:right;">
                    <button type="button" class="btn-secondary" onclick="closeModal()" style="margin-right:10px;">Annuler</button>
                    <button type="button" class="btn-primary" onclick="saveTeam()">💾 Enregistrer</button>
                </div>
            </div>
        </div>

        <script>

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

        function closeModal() {
            const modal = document.getElementById('teamModal');
            modal.classList.remove('active');
            document.body.style.overflow = 'auto';
            document.getElementById('teamForm').reset();
            const alertContainer = document.getElementById('alertContainer');
            if(alertContainer) alertContainer.innerHTML = '';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('teamModal');
            if (event.target == modal) closeModal();
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeModal();
        });

      

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
            
            fetch(`../controllers/api.php?action=${action}${teamId ? '&teamid=' + teamId : ''}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
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

        async function loadTeamData(teamid) {
            try {
                const response = await fetch(`../controllers/api.php?action=getTeam&teamid=${teamid}`);
                const result = await response.json();

                if (result.success) {
                    const team = result.team;
                    document.getElementById('teamId').value = team.id;
                    document.getElementById('name').value = team.nom;
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

        function deleteTeam(teamid) {
            if (confirm('Êtes-vous sûr de vouloir supprimer cette équipe ? Tous les membres seront retirés.')) {
                fetch(`../controllers/api.php?action=deleteTeam&teamid=${teamid}`, {
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

        function viewTeam(id) {
            window.location.href = `index.php?route=teams-details&id=${id}`;
        }

        function editTeam(id) {
            openModal(true, id);
        }

      

        function ucfirst(str) {
            if (!str) return '';
            return str.charAt(0).toUpperCase() + str.slice(1);
        }

        function showAlert(message, type = 'info') {
            const container = document.getElementById('alertContainer');
            
            if (!container) {
                alert(message);
                return;
            }
            
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} show`;
            alertDiv.style.padding = '10px';
            alertDiv.style.marginBottom = '10px';
            alertDiv.style.borderRadius = '5px';
            
            if(type === 'success') {
                alertDiv.style.background = '#d1fae5';
                alertDiv.style.color = '#065f46';
            } else {
                alertDiv.style.background = '#fee2e2';
                alertDiv.style.color = '#b91c1c';
            }
            
            alertDiv.textContent = message;
            container.appendChild(alertDiv);
            
            setTimeout(() => {
                alertDiv.remove();
            }, 3000);
        }
        </script>
        <?php
        return ob_get_clean();
    }
}
?>