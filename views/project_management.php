<?php
require_once __DIR__ . '/../views/public/View.php';
require_once __DIR__ . '/../views/public/components/UIHeader.php';
require_once __DIR__ . '/../views/public/components/UIFooter.php';
require_once __DIR__ . '/../views/Table.php';

class ProjectAdminView extends View {

  
    public function render() {
        $config = $this->data['config'] ?? [];
        $menuData = $this->data['menu'] ?? [];
        $pageTitle = $this->data['title'] ?? 'Gestion des Projets';

        // CSS spécifiques
        $customCss = [
            'views/admin_dashboard.css',
            'views/modelAddUser.css',
            'views/teamManagement.css',
            'views/landingPage.css',
        ];

        $header = new UIHeader($pageTitle, $config, $menuData, $customCss);
        echo $header->render();

        echo '<main style="width: 100%; padding: 40px 20px; box-sizing: border-box; background-color: #f8f9fc; min-height: 80vh;">';
        echo $this->content();
        echo '</main>';

     
    }

   
    protected function content() {
        $projects = $this->data['projects'] ?? [];
        $nbProjetsActifs = $this->data['nbProjetsActifs'] ?? 0;
        $publications = $this->data['publications'] ?? [];
        $teams = $this->data['teams'] ?? [];
        $users = $this->data['users'] ?? [];

        ob_start();
        ?>
        
        <style>
            .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px; }
            .stat-card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); text-align: center; border-bottom: 4px solid #ddd; }
            .stat-card:nth-child(1) { border-color: #4e73df; }
            .stat-card:nth-child(2) { border-color: #1cc88a; }
            .stat-card:nth-child(3) { border-color: #36b9cc; }
            .stat-card .number { font-size: 2.2em; font-weight: bold; margin-top: 10px; color: #333; }
            
            .content-section { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
            .content-section h2 { margin-top: 0; color: #2e384d; border-bottom: 2px solid #f8f9fc; padding-bottom: 20px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
            
            .modal { display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background-color:rgba(0,0,0,0.5); backdrop-filter: blur(2px); }
            .modal.active { display: flex; align-items: center; justify-content: center; }
            .modal-content { background:white; padding:30px; border-radius:10px; max-height:90vh; overflow-y:auto; box-shadow: 0 10px 30px rgba(0,0,0,0.2); width: 90%; max-width: 800px; }
            .modal-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 20px; }
            .close-btn { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #aaa; }
            
            .form-group { margin-bottom: 15px; }
            .form-control { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
            .btn-primary { background: #4e73df; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; transition: 0.2s; }
            .btn-primary:hover { background: #2e59d9; }
            .btn-secondary { background: #858796; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; transition: 0.2s; }
            .btn-secondary:hover { background: #6c757d; }
            .required { color: #e74a3b; }
            
            .badge { padding: 4px 8px; border-radius: 12px; font-size: 0.8em; font-weight: bold; }
            .badge-info { background: #dbeafe; color: #1e40af; }
            .badge-success { background: #d1fae5; color: #065f46; }
            .badge-secondary { background: #f3f4f6; color: #374151; }
        </style>

        <div class="top-bar" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 1px solid #eee; padding-bottom: 20px;">
            <div>
                <h1 style="margin: 0; color: #2c3e50;">Gestion des Projets de Recherche</h1>
                <p style="color: #666; margin-top: 5px;">Suivi et organisation des projets</p>
            </div>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Projets</h3>
                <div class="number"><?php echo count($projects); ?></div>
            </div>
            
            <div class="stat-card">
                <h3>Projets actifs</h3>
                <div class="number"><?php echo $nbProjetsActifs; ?></div>
            </div>
            
            <div class="stat-card">
                <h3>Publications</h3>
                <div class="number"><?php echo is_array($publications) ? ($publications['total'] ?? 0) : $publications; ?></div>
            </div>
        </div>
        
        <div class="content-section">
            <h2>
                <span>Liste des Projets</span>
                <button class="btn-primary" onclick="openModal()">
                    ➕ Ajouter un projet
                </button>
            </h2>
            <?php
            $projectTable = new Table([
                'id' => 'ProjectsTable',
                'headers' => ['ID', 'Titre', 'Responsable', 'Équipe', 'Type', 'Statut', 'Début', 'Fin'],
                'data' => $projects,
                'columns' => [
                    ['key' => 'id'],
                    ['key' => function($row) {
                        $titre = $row['titre'] ?? '';
                        return strlen($titre) > 40 ? substr($titre, 0, 40) . '...' : $titre;
                    }],
                    ['key' => function($row) { 
                        return $row['responsable_nom'] ?? 'Non défini';
                    }],
                    ['key' => function($row) { 
                        return $row['equipe_nom'] ?? 'Aucune';
                    }],
                    ['key' => function($row) {
                        return $row['type_financement'];
                    }],
                    ['key' => function($row) {
                        $statuts = [
                            'soumis' => '<span class="badge badge-info">📋 Soumis</span>',
                            'en_cours' => '<span class="badge badge-success">▶️ En cours</span>',
                            'termine' => '<span class="badge badge-secondary">✅ Terminé</span>',
                        ];
                        return $statuts[$row['statut']] ?? $row['statut'];
                    }],
                    ['key' => function($row) {
                        return date('d/m/Y', strtotime($row['date_debut']));
                    }],
                    ['key' => function($row) {
                        return $row['date_fin'] ? date('d/m/Y', strtotime($row['date_fin'])) : 'Non définie';
                    }]
                ],
                'actions' => [
                    [
                        'icon' => '👁️',
                        'class' => 'btn-sm btn-view',
                        'onclick' => 'viewProject({id})',
                        'label' => ' Voir'
                    ],
                    [
                        'icon' => '✏️',
                        'class' => 'btn-sm btn-edit',
                        'onclick' => 'editProject({id})',
                        'label' => ' Modifier'
                    ],
                    [
                        'icon' => '🗑️',
                        'class' => 'btn-sm btn-delete',
                        'onclick' => 'deleteProject({id})',
                        'label' => ' Supprimer'
                    ]
                ]
            ]);

            $projectTable->display();
            ?>
        </div>
    
        <div id="projectModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 id="modalTitle" style="color: #4e73df; margin: 0;">➕ Ajouter un projet</h2>
                    <button class="close-btn" onclick="closeModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="projectForm">
                        <div id="alertContainer"></div>

                        <input type="hidden" id="projectId" name="id">
                        
                        <div class="form-row" style="margin-bottom: 15px;">
                            <div class="form-group">
                                <label for="titre" style="display:block; margin-bottom:5px; font-weight:bold;">Titre du projet <span class="required">*</span></label>
                                <input type="text" class="form-control" id="titre" name="titre" required placeholder="Ex: Développement d'un système IA">
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 15px;">
                            <label for="description" style="display:block; margin-bottom:5px; font-weight:bold;">Description <span class="required">*</span></label>
                            <textarea class="form-control" id="description" name="description" rows="4" required placeholder="Décrivez les objectifs et la portée du projet..."></textarea>
                        </div>

                        <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                            <div class="form-group">
                                <label for="type_financement" style="display:block; margin-bottom:5px; font-weight:bold;">Type de financement <span class="required">*</span></label>
                                <input type="text" class="form-control" id="type_financement" name="type_financement" required placeholder="Ex: public, privé, mixte">
                            </div>

                            <div class="form-group">
                                <label for="statut" style="display:block; margin-bottom:5px; font-weight:bold;">Statut <span class="required">*</span></label>
                                <select class="form-control" id="statut" name="statut" required>
                                    <option value="">-- Sélectionner --</option>
                                    <option value="en_cours">▶️ En cours</option>
                                    <option value="termine">✅ Terminé</option>
                                    <option value="soumis">📋 Soumis</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                            <div class="form-group">
                                <label for="date_debut" style="display:block; margin-bottom:5px; font-weight:bold;">Date de début <span class="required">*</span></label>
                                <input type="date" class="form-control" id="date_debut" name="date_debut" required>
                            </div>

                            <div class="form-group">
                                <label for="date_fin" style="display:block; margin-bottom:5px; font-weight:bold;">Date de fin</label>
                                <input type="date" class="form-control" id="date_fin" name="date_fin">
                            </div>
                        </div>

                        <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div class="form-group">
                                <label for="responsable_id" style="display:block; margin-bottom:5px; font-weight:bold;">Responsable <span class="required">*</span></label>
                                <select class="form-control" id="responsable_id" name="responsable_id" required>
                                    <option value="">-- Sélectionner un responsable --</option>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?php echo $user['id']; ?>">
                                            <?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom'] . ' (' . $user['role'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="id_equipe" style="display:block; margin-bottom:5px; font-weight:bold;">Équipe</label>
                                <select class="form-control" id="id_equipe" name="id_equipe">
                                    <option value="">-- Aucune équipe --</option>
                                    <?php foreach ($teams as $team): ?>
                                        <option value="<?php echo $team['info']['id'] ?? $team['id']; ?>">
                                            <?php echo htmlspecialchars($team['info']['nom'] ?? $team['nom']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer" style="border-top:1px solid #eee; padding-top:15px; text-align:right;">
                    <button type="button" class="btn-secondary" onclick="closeModal()" style="margin-right:10px;">Annuler</button>
                    <button type="button" class="btn-primary" onclick="saveProject()">💾 Enregistrer</button>
                </div>
            </div>
        </div>

        <script>
     
        function openModal(editMode = false, projectId = null) {
            const modal = document.getElementById('projectModal');
            const modalTitle = document.getElementById('modalTitle');
            const form = document.getElementById('projectForm');
            
            if (editMode && projectId) {
                modalTitle.textContent = '✏️ Modifier le projet';
                loadProjectData(projectId);
            } else {
                modalTitle.textContent = '➕ Ajouter un projet';
                form.reset();
                document.getElementById('projectId').value = '';
            }
            
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            const modal = document.getElementById('projectModal');
            modal.classList.remove('active');
            document.body.style.overflow = 'auto';
            document.getElementById('projectForm').reset();
            const alertContainer = document.getElementById('alertContainer');
            if (alertContainer) { alertContainer.innerHTML = ''; }
        }

        window.onclick = function(event) {
            const modal = document.getElementById('projectModal');
            if (event.target == modal) closeModal();
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const modal = document.getElementById('projectModal');
                if (modal && modal.classList.contains('active')) {
                    closeModal();
                }
            }
        });

     
        function saveProject() {
            const form = document.getElementById('projectForm');
            const formData = new FormData(form);
            
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            
            const data = Object.fromEntries(formData);
            if (data.id_equipe === '') data.id_equipe = null;
            if (data.date_fin === '') data.date_fin = null;
            
            const projectId = document.getElementById('projectId').value;
            const action = projectId ? 'updateProject' : 'createProject';
            const url = projectId 
                ? `../controllers/api.php?action=${action}&id=${projectId}`
                : `../controllers/api.php?action=${action}`;
            
            const saveBtn = event.target;
            const originalText = saveBtn.textContent;
            saveBtn.disabled = true;
            saveBtn.textContent = '⏳ Enregistrement...';
            
            fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                saveBtn.disabled = false;
                saveBtn.textContent = originalText;
                
                if (result.success) {
                    showAlert('✅ ' + result.message, 'success');
                    closeModal();
                    setTimeout(() => location.reload(), 1200);
                } else {
                    const errorMsg = result.message || (result.errors ? result.errors.join('<br>') : 'Erreur inconnue');
                    showAlert('❌ ' + errorMsg, 'error');
                }
            })
            .catch(error => {
                console.error(error);
                saveBtn.disabled = false;
                saveBtn.textContent = originalText;
                showAlert('❌ Erreur de connexion', 'error');
            });
        }

        async function loadProjectData(id) {
            try {
                const response = await fetch(`../controllers/api.php?action=getProject&id=${id}`);
                const result = await response.json();

                if (result.success && result.data) {
                    const project = result.data;
                    document.getElementById('projectId').value = project.id || '';
                    document.getElementById('titre').value = project.titre || '';
                    document.getElementById('description').value = project.description || '';
                    document.getElementById('type_financement').value = project.type_financement || '';
                    document.getElementById('statut').value = project.statut || '';
                    document.getElementById('date_debut').value = project.date_debut || '';
                    document.getElementById('date_fin').value = project.date_fin || '';
                    document.getElementById('responsable_id').value = project.responsable_id || '';
                    document.getElementById('id_equipe').value = project.id_equipe || '';
                } else {
                    showAlert('❌ ' + result.message, 'error');
                }
            } catch (error) {
                console.error(error);
                showAlert('❌ Erreur de chargement', 'error');
            }
        }

        function deleteProject(id) {
            if (!confirm('⚠️ Êtes-vous sûr de vouloir supprimer ce projet ?\n\nCette action est irréversible et supprimera également toutes les associations.')) {
                return;
            }
            
            fetch(`../controllers/api.php?action=deleteProject&id=${id}`, { method: 'POST' })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showAlert('✅ Projet supprimé', 'success');
                    setTimeout(() => location.reload(), 1200);
                } else {
                    showAlert('❌ ' + result.message, 'error');
                }
            })
            .catch(error => {
                console.error(error);
                showAlert('❌ Erreur serveur', 'error');
            });
        }

        function viewProject(id) {
            window.location.href = `index.php?route=project-admin-details&id=${id}`;
        }

        function editProject(id) {
            openModal(true, id);
        }


        function showAlert(message, type = 'info') {
            const container = document.getElementById('alertContainer');
            if (!container) { alert(message); return; }
            
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
            
            alertDiv.innerHTML = message;
            container.appendChild(alertDiv);
            
            setTimeout(() => {
                alertDiv.remove();
            }, 5000);
        }

        // Validation dates
        document.getElementById('date_fin')?.addEventListener('change', function() {
            const d1 = document.getElementById('date_debut').value;
            if (d1 && this.value && this.value < d1) {
                showAlert('⚠️ La date de fin ne peut pas être antérieure à la date de début', 'error');
                this.value = '';
            }
        });
        
        document.getElementById('date_debut')?.addEventListener('change', function() {
            const d2 = document.getElementById('date_fin').value;
            if (this.value && d2 && this.value > d2) {
                showAlert('⚠️ La date de début ne peut pas être postérieure à la date de fin', 'error');
                document.getElementById('date_fin').value = '';
            }
        });
        </script>
        <?php
        return ob_get_clean();
    }
}
?>