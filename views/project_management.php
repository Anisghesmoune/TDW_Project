<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Model.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/TeamsModel.php';
require_once __DIR__ . '/../models/ProjectModel.php';

require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/ProjectController.php';
require_once __DIR__ . '/../controllers/PublicationController.php';
require_once __DIR__ . '/../controllers/EventController.php';

require_once __DIR__ . '/../views/Sidebar.php';
require_once __DIR__ . '/../views/Table.php';

// AuthController::requireAdmin();
$controller = new ProjectController();
$data = $controller->index();
$projects = $data['data'] ?? [];

$eventController = new EventController();
$eventData = $eventController->getAll();
$publicationController = new PublicationController();
$publicationData = $publicationController->stats();

// Compter les projets actifs
$activeProjectsData = $controller->countActive();
$nbProjetsActifs = $activeProjectsData['data']['count'] ?? 0;

// Récupérer les équipes et les utilisateurs pour les selects
$teamModel = new TeamsModel();
$teams = $teamModel->getAllTeamsWithDetails();
$userModel = new UserModel();
$users = $userModel->getAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Projets - Laboratoire</title>
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
                <h1>Gestion des Projets de Recherche</h1>
                <p style="color: #666;">Suivi et organisation des projets</p>
            </div>
            <a href="../logout.php" class="logout-btn">Déconnexion</a>
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
                <div class="number"><?php echo $publicationData['total']; ?></div>
            </div>
            
            <div class="stat-card">
                <h3>Événements à venir</h3>
                <div class="number"><?php echo $eventData['total']; ?></div>
            </div>
        </div>
        
        <div class="content-section">
            <h2>
                <span>Gestion des Projets</span>
                <button class="btn btn-primary" onclick="openModal()">
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
                        
                        return $row['type_financement'] ;
                    }],
                    ['key' => function($row) {
                        $statuts = [
                            'soumis' => '<span class="badge badge-info">📋 soumis</span>',
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
    </div>
    
    <!-- Modal Ajouter/Modifier Projet -->
    <div id="projectModal" class="modal">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h2 id="modalTitle">➕ Ajouter un projet</h2>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="projectForm">
                    <div id="alertContainer"></div>

                    <input type="hidden" id="projectId" name="id">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="titre">Titre du projet <span class="required">*</span></label>
                            <input type="text" class="form-control" id="titre" name="titre" required placeholder="Ex: Développement d'un système IA">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description">Description <span class="required">*</span></label>
                        <textarea class="form-control" id="description" name="description" rows="4" required placeholder="Décrivez les objectifs et la portée du projet..."></textarea>
                    </div>

                    <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label for="type_financement">Type de financement <span class="required">*</span></label>
                            <input type="text" class="form-control" id="type_financement" name="type_financement" required placeholder="Ex: public, privé, mixte">
                           
                        </div>

                        <div class="form-group">
                            <label for="statut">Statut <span class="required">*</span></label>
                            <select class="form-control" id="statut" name="statut" required>
                                <option value="">-- Sélectionner --</option>
                                <option value="en_cours">▶️ En cours</option>
                                <option value="termine">✅ Terminé</option>
                                <option value="suspendu">📋soumis</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label for="date_debut">Date de début <span class="required">*</span></label>
                            <input type="date" class="form-control" id="date_debut" name="date_debut" required>
                        </div>

                        <div class="form-group">
                            <label for="date_fin">Date de fin</label>
                            <input type="date" class="form-control" id="date_fin" name="date_fin">
                        </div>
                    </div>

                    <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label for="responsable_id">Responsable <span class="required">*</span></label>
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
                            <label for="id_equipe">Équipe</label>
                            <select class="form-control" id="id_equipe" name="id_equipe">
                                <option value="">-- Aucune équipe --</option>
                                <?php foreach ($teams as $team): ?>
                                    <option value="<?php echo $team['id']; ?>">
                                        <?php echo htmlspecialchars($team['nom']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Annuler</button>
                <button type="button" class="btn btn-primary" onclick="saveProject()">💾 Enregistrer</button>
            </div>
        </div>
    </div>

    <script>
        // ============================================
// GESTION DU MODAL
// ============================================

// Ouvrir le modal
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

// Fermer le modal
function closeModal() {
    const modal = document.getElementById('projectModal');
    modal.classList.remove('active');
    document.body.style.overflow = 'auto';
    document.getElementById('projectForm').reset();
    // Nettoyer les alertes
    const alertContainer = document.getElementById('alertContainer');
    if (alertContainer) {
        alertContainer.innerHTML = '';
    }
}

// Fermer avec clic extérieur
document.getElementById('projectModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});

// Fermer avec la touche Échap
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('projectModal');
        if (modal && modal.classList.contains('active')) {
            closeModal();
        }
    }
});

// ============================================
// CRUD PROJETS
// ============================================

// Sauvegarder un projet (Créer ou Modifier)
function saveProject() {
    const form = document.getElementById('projectForm');
    const formData = new FormData(form);
    
    // Validation du formulaire
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    // Convertir FormData en objet
    const data = Object.fromEntries(formData);
    
    // Nettoyer les valeurs vides pour id_equipe (optionnel)
    if (data.id_equipe === '') {
        data.id_equipe = null;
    }
    if (data.date_fin === '') {
        data.date_fin = null;
    }
    
    const projectId = document.getElementById('projectId').value;
    const action = projectId ? 'updateProject' : 'createProject';
    const url = projectId 
        ? `../controllers/api.php?action=${action}&id=${projectId}`
        : `../controllers/api.php?action=${action}`;
    
    console.log('📤 Envoi des données:', data);
    console.log('🔗 URL:', url);
    
    // Désactiver le bouton pour éviter les doubles clics
    const saveBtn = event.target;
    saveBtn.disabled = true;
    saveBtn.textContent = '⏳ Enregistrement...';
    
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        console.log('📥 Statut réponse:', response.status);
        return response.json();
    })
    .then(result => {
        console.log('📦 Résultat:', result);
        
        // Réactiver le bouton
        saveBtn.disabled = false;
        saveBtn.textContent = '💾 Enregistrer';
        
        if (result.success) {
            showAlert('✅ ' + result.message, 'success');
            closeModal();
            setTimeout(() => location.reload(), 1200);
        } else {
            // Afficher les erreurs
            let errorMsg = '';
            if (result.errors && Array.isArray(result.errors)) {
                errorMsg = result.errors.join('<br>');
            } else if (result.message) {
                errorMsg = result.message;
            } else {
                errorMsg = 'Erreur inconnue';
            }
            showAlert('❌ ' + errorMsg, 'error');
        }
    })
    .catch(error => {
        console.error('❌ Erreur:', error);
        
        // Réactiver le bouton
        saveBtn.disabled = false;
        saveBtn.textContent = '💾 Enregistrer';
        
        showAlert('❌ Erreur de connexion au serveur', 'error');
    });
}

// Charger les données d'un projet pour modification
async function loadProjectData(id) {
    try {
        console.log('📥 Chargement du projet ID:', id);
        
        const response = await fetch(`../controllers/api.php?action=getProject&id=${id}`);
        const result = await response.json();
        
        console.log('📦 Données reçues:', result);

        if (result.success && result.data) {
            const project = result.data;
            
            // Remplir le formulaire
            document.getElementById('projectId').value = project.id || '';
            document.getElementById('titre').value = project.titre || '';
            document.getElementById('description').value = project.description || '';
            document.getElementById('type_financement').value = project.type_financement || '';
            document.getElementById('statut').value = project.statut || '';
            document.getElementById('date_debut').value = project.date_debut || '';
            document.getElementById('date_fin').value = project.date_fin || '';
            document.getElementById('responsable_id').value = project.responsable_id || '';
            document.getElementById('id_equipe').value = project.id_equipe || '';
            
            console.log('✅ Formulaire rempli avec succès');
        } else {
            const errorMsg = result.message || 'Impossible de charger les données';
            showAlert('❌ ' + errorMsg, 'error');
            console.error('❌ Erreur:', result);
        }
    } catch (error) {
        console.error('❌ Erreur lors du chargement:', error);
        showAlert('❌ Impossible de charger les données du projet', 'error');
    }
}

// Supprimer un projet
function deleteProject(id) {
    if (!confirm('⚠️ Êtes-vous sûr de vouloir supprimer ce projet ?\n\nCette action est irréversible et supprimera également toutes les associations (utilisateurs, publications, thématiques).')) {
        return;
    }
    
    console.log('🗑️ Suppression du projet ID:', id);
    
    fetch(`../controllers/api.php?action=deleteProject&id=${id}`, {
        method: 'POST'
    })
    .then(response => {
        console.log('📥 Statut réponse:', response.status);
        return response.json();
    })
    .then(result => {
        console.log('📦 Résultat:', result);
        
        if (result.success) {
            showAlert('✅ ' + result.message, 'success');
            setTimeout(() => location.reload(), 1200);
        } else {
            const errorMsg = result.message || 'Erreur lors de la suppression';
            showAlert('❌ ' + errorMsg, 'error');
        }
    })
    .catch(error => {
        console.error('❌ Erreur:', error);
        showAlert('❌ Erreur de connexion au serveur', 'error');
    });
}

// Voir les détails d'un projet
function viewProject(id) {
    console.log('👁️ Redirection vers les détails du projet ID:', id);
    window.location.href = `project-details.php?id=${id}`;
}

// Éditer un projet
function editProject(id) {
    console.log('✏️ Édition du projet ID:', id);
    openModal(true, id);
}

// ============================================
// ALERTES ET NOTIFICATIONS
// ============================================

// Afficher une alerte
function showAlert(message, type = 'info') {
    const container = document.getElementById('alertContainer');
    
    if (!container) {
        alert(message);
        return;
    }
    
    // Créer l'élément d'alerte
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} show`;
    alertDiv.innerHTML = message;
    
    // Ajouter au conteneur
    container.appendChild(alertDiv);
    
    // Animation d'apparition
    setTimeout(() => {
        alertDiv.classList.add('visible');
    }, 10);
    
    // Retrait automatique après 5 secondes
    setTimeout(() => {
        alertDiv.classList.remove('visible');
        setTimeout(() => {
            alertDiv.remove();
        }, 300);
    }, 5000);
}

// ============================================
// VALIDATION DES FORMULAIRES
// ============================================

// Validation de la date de fin
document.getElementById('date_fin')?.addEventListener('change', function() {
    const dateDebut = document.getElementById('date_debut').value;
    const dateFin = this.value;
    
    if (dateDebut && dateFin && dateFin < dateDebut) {
        showAlert('⚠️ La date de fin ne peut pas être antérieure à la date de début', 'error');
        this.value = '';
        this.focus();
    }
});

// Validation de la date de début
document.getElementById('date_debut')?.addEventListener('change', function() {
    const dateDebut = this.value;
    const dateFin = document.getElementById('date_fin').value;
    
    if (dateDebut && dateFin && dateFin < dateDebut) {
        showAlert('⚠️ La date de début ne peut pas être postérieure à la date de fin', 'error');
        document.getElementById('date_fin').value = '';
    }
});

// ============================================
// RECHERCHE ET FILTRAGE
// ============================================

// Rechercher des projets
function searchProjects(keyword) {
    if (!keyword || keyword.length < 3) {
        showAlert('⚠️ Le mot-clé doit contenir au moins 3 caractères', 'error');
        return;
    }
    
    console.log('🔍 Recherche:', keyword);
    
    fetch(`../controllers/api.php?action=searchProjects&keyword=${encodeURIComponent(keyword)}`)
    .then(response => response.json())
    .then(result => {
        console.log('📦 Résultats:', result);
        
        if (result.success) {
            // Mettre à jour le tableau avec les résultats
            updateProjectTable(result.data);
            showAlert(`✅ ${result.data.length} projet(s) trouvé(s)`, 'success');
        } else {
            showAlert('❌ ' + result.message, 'error');
        }
    })
    .catch(error => {
        console.error('❌ Erreur:', error);
        showAlert('❌ Erreur lors de la recherche', 'error');
    });
}

// Filtrer par statut
function filterByStatut(statut) {
    if (!statut) {
        location.reload();
        return;
    }
    
    console.log('📊 Filtrage par statut:', statut);
    
    fetch(`../controllers/api.php?action=getProjectsByStatut&statut=${statut}`)
    .then(response => response.json())
    .then(result => {
        console.log('📦 Résultats:', result);
        
        if (result.success) {
            updateProjectTable(result.data);
            showAlert(`✅ ${result.data.length} projet(s) trouvé(s)`, 'success');
        } else {
            showAlert('❌ ' + result.message, 'error');
        }
    })
    .catch(error => {
        console.error('❌ Erreur:', error);
        showAlert('❌ Erreur lors du filtrage', 'error');
    });
}



// ============================================
// STATISTIQUES ET COMPTEURS
// ============================================

// Mettre à jour le compteur de projets actifs
function updateActiveProjectsCount() {
    fetch('../controllers/api.php?action=countActiveProjects')
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            const countElement = document.querySelector('.stat-card:nth-child(2) .number');
            if (countElement) {
                countElement.textContent = result.data.count;
            }
        }
    })
    .catch(error => {
        console.error('❌ Erreur lors de la mise à jour du compteur:', error);
    });
}




        
       </script>
</body>
</html>