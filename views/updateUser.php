<?php
// Imports des dépendances
require_once __DIR__ . '/../views/public/View.php';
require_once __DIR__ . '/../views/public/components/UIHeader.php';
require_once __DIR__ . '/../views/public/components/UIFooter.php';
require_once __DIR__ . '/../views/Table.php';

class UpdateUserView extends View {

    /**
     * Méthode principale pour structurer la page
     */
    public function render() {
        // Extraction des données globales
        $config = $this->data['config'] ?? [];
        $menuData = $this->data['menu'] ?? [];
        $pageTitle = $this->data['title'] ?? 'Gestion des Utilisateurs';

        // CSS spécifiques
        $customCss = [
            'views/admin_dashboard.css',
            'views/teamManagement.css',
            'views/modelAddUser.css',
            'views/landingPage.css'
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
     * Contenu spécifique : Stats, Tableau, Modale, JS
     */
    protected function content() {
        // Extraction des données métier
        $users = $this->data['users'] ?? [];
        $teams = $this->data['teams'] ?? [];
        $roles=$users['role'] ?? ''  ;
        // Préparation des données pour le tableau
        $usersForTable = [];
        foreach ($users as $user) {
            $usersForTable[] = [
                'id' => $user['id'] ?? '',
                'prenom' => $user['prenom'] ?? '',
                'nom' => $user['nom'] ?? '',
                'email' => $user['email'] ?? '',
                'role' => $user['role'] ?? '',
                'equipe_nom' => $user['equipe_nom'] ?? 'Non assigné',
                'date_creation' => $user['date_creation'] ?? ''
            ];
        }

        ob_start();
        ?>
        
        <!-- Styles internes -->
        <style>
            /* Ajustements Layout */
            .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
            .stat-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); text-align: center; border-bottom: 4px solid #ddd; }
            .stat-card:nth-child(1) { border-color: #4e73df; }
            .stat-card:nth-child(2) { border-color: #1cc88a; }
            .stat-card:nth-child(3) { border-color: #36b9cc; }
            .stat-card:nth-child(4) { border-color: #f6c23e; }
            .stat-card .number { font-size: 2em; font-weight: bold; margin-top: 10px; color: #2e384d; }
            .stat-card h3 { margin: 0; color: #858796; font-size: 0.9em; text-transform: uppercase; }
            
            /* Table Section */
            .content-section { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); margin-top: 30px; }
            .content-section h2 { margin-top: 0; color: #2e384d; border-bottom: 2px solid #f8f9fc; padding-bottom: 20px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
            
            /* Modale */
            .modal { display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background-color:rgba(0,0,0,0.5); backdrop-filter: blur(2px); }
            .modal.active { display: flex; align-items: center; justify-content: center; }
            .modal-content { background:white; padding:30px; border-radius:10px; max-height:90vh; overflow-y:auto; box-shadow: 0 10px 30px rgba(0,0,0,0.2); width: 90%; max-width: 600px; }
            .modal-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 20px; }
            .close-btn { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #aaa; transition: color 0.2s; }
            .close-btn:hover { color: #333; }
            
            /* Formulaires */
            .form-group { margin-bottom: 15px; }
            .form-group label { display:block; margin-bottom:5px; font-weight:bold; color: #2e384d; }
            .form-control { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; font-size: 0.95em; }
            .form-control:focus { outline: none; border-color: #4e73df; box-shadow: 0 0 0 2px rgba(78,115,223,0.1); }
            .btn-primary { background: #4e73df; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; transition: background 0.2s; }
            .btn-primary:hover { background: #2e59d9; }
            .btn-secondary { background: #858796; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; transition: background 0.2s; }
            .btn-secondary:hover { background: #60616f; }
            .required { color: #e74a3b; }
            
            /* Badges de rôles */
            .role-badge { padding: 4px 10px; border-radius: 12px; font-size: 0.85em; font-weight: 500; }
            .role-admin { background: #e74a3b; color: white; }
            .role-chercheur { background: #4e73df; color: white; }
            .role-doctorant { background: #1cc88a; color: white; }
            .role-visiteur { background: #858796; color: white; }
            
            /* Alertes */
            .alert { padding: 12px 15px; margin-bottom: 15px; border-radius: 5px; font-size: 0.9em; animation: slideIn 0.3s ease; }
            .alert-success { background: #d1fae5; color: #065f46; border-left: 4px solid #10b981; }
            .alert-error { background: #fee2e2; color: #b91c1c; border-left: 4px solid #ef4444; }
            
            @keyframes slideIn {
                from { transform: translateY(-10px); opacity: 0; }
                to { transform: translateY(0); opacity: 1; }
            }
        </style>

        <!-- Top Bar Interne -->
        <div class="top-bar" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 1px solid #eee; padding-bottom: 20px;">
            <div>
                <h1 style="margin: 0; color: #2c3e50;">Gestion des Utilisateurs</h1>
                <p style="color: #666; margin-top: 5px;">Administration et gestion des comptes</p>
            </div>
        </div>
        
        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Utilisateurs</h3>
                <div class="number"><?php echo count($users); ?></div>
            </div>
            
            <div class="stat-card">
                <h3>Administrateurs</h3>
                <div class="number"><?php echo count(array_filter($users, fn($u) => ($u['role'] ?? '') === 'admin')); ?></div>
            </div>
            
            <div class="stat-card">
                <h3>Chercheurs</h3>
                <div class="number"><?php echo count(array_filter($users, fn($u) => ($u['role'] ?? '') === 'chercheur')); ?></div>
            </div>
            
            <div class="stat-card">
                <h3>Doctorants</h3>
                <div class="number"><?php echo count(array_filter($users, fn($u) => ($u['role'] ?? '') === 'doctorant')); ?></div>
            </div>
        </div>
        
        <!-- Tableau des Utilisateurs -->
        <div class="content-section">
            <h2>
                <span>Liste des Utilisateurs</span>
                <button class="btn-primary" onclick="openModal()">
                    ➕ Ajouter un utilisateur
                </button>
            </h2>
            <?php
            // Utilisation du composant Table
            $userTable = new Table([
                'id' => 'UsersTable',
                'headers' => ['ID', 'Prénom', 'Nom', 'Email', 'Rôle', 'Équipe', 'Date création'],
                'data' => $usersForTable,
                'columns' => [
                    ['key' => 'id'],
                    ['key' => 'prenom'],
                    ['key' => 'nom'],
                    ['key' => 'email'],
                    ['key' => function($row) {
                        $role = $row['role'] ?? '';
                        $roleClass = 'role-' . strtolower($role);
                        return "<span class='role-badge {$roleClass}'>" . ucfirst($role) . "</span>";
                    }],
                    ['key' => function($row) {
                        return $row['equipe_nom'] ?: '<em style="color:#999;">Non assigné</em>';
                    }],
                    ['key' => function($row) {
                        $date = $row['date_creation'] ?? '';
                        return $date ? date('d/m/Y', strtotime($date)) : '-';
                    }]
                ],
                'actions' => [
                    [
                        'icon' => '👁️',
                        'class' => 'btn-sm btn-view',
                        'onclick' => 'viewUser({id})',
                        'label' => ' Voir'
                    ],
                    [
                        'icon' => '✏️',
                        'class' => 'btn-sm btn-edit',
                        'onclick' => 'editUser({id})',
                        'label' => ' Modifier'
                    ],
                    [
                        'icon' => '🗑️',
                        'class' => 'btn-sm btn-delete',
                        'onclick' => 'deleteUser({id})',
                        'label' => ' Supprimer'
                    ]
                ]
            ]);

            $userTable->display();
            ?>
        </div>
    
        <!-- Modal Ajouter/Modifier Utilisateur -->
        <div id="userModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 id="modalTitle" style="color:#4e73df; margin:0;">➕ Ajouter un utilisateur</h2>
                    <button class="close-btn" onclick="closeModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="userForm">
                        <div id="alertContainer"></div>

                        <input type="hidden" id="userId" name="id">
                        
                        <div class="form-group">
                            <label for="prenom">Prénom <span class="required">*</span></label>
                            <input type="text" class="form-control" id="prenom" name="prenom" required placeholder="Ex: Jean">
                        </div>

                        <div class="form-group">
                            <label for="nom">Nom <span class="required">*</span></label>
                            <input type="text" class="form-control" id="nom" name="nom" required placeholder="Ex: Dupont">
                        </div>

                        <div class="form-group">
                            <label for="email">Email <span class="required">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" required placeholder="ex: jean.dupont@example.com">
                        </div>

                        <div class="form-group">
                            <label for="role">Rôle <span class="required">*</span></label>
                            <select class="form-control" id="role" name="role" required>
                                <option value="">-- Sélectionner un rôle --</option>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?php echo $role; ?>"><?php echo ucfirst($role); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="equipe_id">Équipe</label>
                            <select class="form-control" id="equipe_id" name="equipe_id">
                                <option value="">-- Aucune équipe --</option>
                                <?php foreach ($teams as $team): ?>
                                    <option value="<?php echo $team['id'] ?? $team['info']['id']; ?>">
                                        <?php echo htmlspecialchars($team['nom'] ?? $team['info']['nom']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group" id="passwordGroup">
                            <label for="password">Mot de passe <span class="required">*</span></label>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Minimum 6 caractères">
                            <small style="color:#666; font-size:0.85em; display:block; margin-top:5px;">
                                Laissez vide pour conserver le mot de passe actuel (en modification)
                            </small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer" style="border-top:1px solid #eee; padding-top:15px; text-align:right;">
                    <button type="button" class="btn-secondary" onclick="closeModal()" style="margin-right:10px;">Annuler</button>
                    <button type="button" class="btn-primary" onclick="saveUser()">💾 Enregistrer</button>
                </div>
            </div>
        </div>

        <script>
        // ============================================
        // GESTION DU MODAL
        // ============================================

        function openModal(editMode = false, userId = null) {
            const modal = document.getElementById('userModal');
            const modalTitle = document.getElementById('modalTitle');
            const form = document.getElementById('userForm');
            const passwordInput = document.getElementById('password');
            
            if (editMode && userId) {
                modalTitle.textContent = '✏️ Modifier l\'utilisateur';
                passwordInput.required = false;
                loadUserData(userId);
            } else {
                modalTitle.textContent = '➕ Ajouter un utilisateur';
                passwordInput.required = true;
                form.reset();
                document.getElementById('userId').value = '';
            }
            
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            const modal = document.getElementById('userModal');
            modal.classList.remove('active');
            document.body.style.overflow = 'auto';
            document.getElementById('userForm').reset();
            const alertContainer = document.getElementById('alertContainer');
            if(alertContainer) alertContainer.innerHTML = '';
        }

        // Fermer avec clic extérieur
        window.onclick = function(event) {
            const modal = document.getElementById('userModal');
            if (event.target == modal) closeModal();
        }

        // Fermer avec Échap
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeModal();
        });

        // ============================================
        // CRUD UTILISATEURS
        // ============================================

        function saveUser() {
            const form = document.getElementById('userForm');
            const formData = new FormData(form);
            
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            
            const data = Object.fromEntries(formData);
            const userId = document.getElementById('userId').value;
            
            // Si modification et mot de passe vide, on le retire
            if (userId && !data.password) {
                delete data.password;
            }
            
            const action = userId ? 'updateUser' : 'createUser';
            
            console.log('Données à envoyer:', data);
            
            fetch(`../controllers/api.php?action=${action}${userId ? '&userid=' + userId : ''}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showAlert('✅ Utilisateur enregistré avec succès!', 'success');
                    closeModal();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showAlert('❌ Erreur: ' + (result.message || 'Erreur inconnue'), 'error');
                }
            })
            .catch(error => {
                showAlert('❌ Erreur de connexion', 'error');
                console.error(error);
            });
        }

        async function loadUserData(userid) {
            try {
                const response = await fetch(`../controllers/api.php?action=getUser&userid=${userid}`);
                const result = await response.json();

                if (result.success) {
                    const user = result.user;
                    document.getElementById('userId').value = user.id;
                    document.getElementById('prenom').value = user.prenom || '';
                    document.getElementById('nom').value = user.nom || '';
                    document.getElementById('email').value = user.email || '';
                    document.getElementById('role').value = user.role || '';
                    document.getElementById('equipe_id').value = user.equipe_id || '';
                    // Ne pas remplir le mot de passe
                    document.getElementById('password').value = '';
                } else {
                    showAlert('❌ ' + (result.message || 'Erreur de chargement'), 'error');
                }
            } catch (error) {
                console.error('Erreur:', error);
                showAlert('❌ Impossible de charger les données.', 'error');
            }
        }

        function deleteUser(userid) {
            if (confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ? Cette action est irréversible.')) {
                fetch(`../controllers/api.php?action=deleteUser&userid=${userid}`, {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        showAlert('✅ Utilisateur supprimé', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showAlert('❌ Erreur: ' + (result.message || 'Erreur inconnue'), 'error');
                    }
                })
                .catch(error => {
                    showAlert('❌ Erreur de connexion', 'error');
                    console.error(error);
                });
            }
        }

        function viewUser(id) {
            window.location.href = `index.php?route=profile-user&id=${id}`;
        }

        function editUser(id) {
            openModal(true, id);
        }

        // ============================================
        // UTILITAIRES
        // ============================================

        function showAlert(message, type = 'info') {
            const container = document.getElementById('alertContainer');
            
            if (!container) {
                alert(message);
                return;
            }
            
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type}`;
            alertDiv.textContent = message;
            container.appendChild(alertDiv);
            
            setTimeout(() => {
                alertDiv.style.opacity = '0';
                setTimeout(() => alertDiv.remove(), 300);
            }, 3000);
        }
        </script>
        <?php
        return ob_get_clean();
    }
}
?>