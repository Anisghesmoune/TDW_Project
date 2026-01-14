<?php
require_once __DIR__ . '/../views/public/View.php';
require_once __DIR__ . '/../views/public/components/UIHeader.php';
require_once __DIR__ . '/../views/public/components/UIFooter.php';
require_once __DIR__ . '/../views/Table.php';

class DashboardAdminView extends View {

    public function render() {
        $config = $this->data['config'] ?? [];
        $menuData = $this->data['menu'] ?? [];
        $pageTitle = $this->data['title'] ?? 'Administration';

        $customCss = [
            'views/admin_dashboard.css',
            'views/modelAddUser.css',
            'views/landingPage.css',
            'assets/css/public.css'
        ];

        $header = new UIHeader($pageTitle, $config, $menuData, $customCss);
        echo $header->render();

        echo '<main style="width: 100%; padding: 40px 20px; box-sizing: border-box; background-color: #f8f9fc; min-height: 80vh;">';
        echo $this->content();
        echo '</main>';

        
    }

    protected function content() {
        $users = $this->data['users'] ?? [];
        $projects = $this->data['projects'] ?? [];
        $events = $this->data['events'] ?? [];
        $publicationData = $this->data['publications'] ?? ['total' => 0];

        ob_start();
        ?>
        
        <style>
            .dashboard-container { max-width: 1400px; margin: 0 auto; }
            .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px; }
            .stat-card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); text-align: center; border-bottom: 4px solid #ddd; }
            .stat-card:nth-child(1) { border-color: #4e73df; }
            .stat-card:nth-child(2) { border-color: #1cc88a; }
            .stat-card:nth-child(3) { border-color: #36b9cc; }
            .stat-card:nth-child(4) { border-color: #f6c23e; }
            .stat-card h3 { font-size: 0.9rem; text-transform: uppercase; color: #888; margin-bottom: 10px; letter-spacing: 1px; }
            .stat-card .number { font-size: 2.2rem; font-weight: 700; color: #333; }
            .filters-section { background: white; padding: 25px; border-radius: 10px; margin-bottom: 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
            .filters-row { display: flex; gap: 20px; align-items: flex-end; flex-wrap: wrap; }
            .filter-group { flex: 1; min-width: 200px; }
            .filter-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #4e73df; font-size: 0.9em; }
            .filter-group input, .filter-group select { width: 100%; padding: 12px; border: 1px solid #e3e6f0; border-radius: 5px; font-size: 14px; background-color: #f8f9fc; }
            .filter-group input:focus, .filter-group select:focus { border-color: #4e73df; outline: none; background-color: #fff; }
            .filter-buttons { display: flex; gap: 10px; }
            .btn-filter { padding: 12px 25px; border: none; border-radius: 5px; cursor: pointer; font-weight: 600; transition: all 0.2s; }
            .btn-reset { background: #858796; color: white; display: flex; align-items: center; gap: 5px; }
            .btn-reset:hover { background: #60616f; transform: translateY(-1px); }
            .content-section { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
            .content-section h2 { margin-top: 0; color: #2e384d; border-bottom: 2px solid #f8f9fc; padding-bottom: 20px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
            .btn-primary { background: #4e73df; color: white; border: none; padding: 10px 20px; border-radius: 5px; font-weight: 600; cursor: pointer; transition: 0.2s; }
            .btn-primary:hover { background: #224abe; }
            .modal { display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background-color:rgba(0,0,0,0.5); backdrop-filter: blur(3px); }
            .modal.active { display: flex; align-items: center; justify-content: center; }
            .modal-content { background:white; width:600px; padding:30px; border-radius:10px; max-height:90vh; overflow-y:auto; box-shadow: 0 15px 30px rgba(0,0,0,0.2); }
            .modal-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 20px; }
            .close-btn { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #aaa; }
            .required { color: #e74a3b; }
        </style>

        <div class="dashboard-container">
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                <div>
                    <h1 style="margin: 0; color: #2c3e50;">Tableau de bord administrateur</h1>
                    <p style="color: #666; margin-top: 5px;">Vue d'ensemble et gestion du laboratoire</p>
                </div>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Utilisateurs</h3>
                    <div class="number"><?php echo count($users); ?></div>
                </div>
                
                <div class="stat-card">
                    <h3>Total Projets</h3>
                    <div class="number"><?php echo count($projects); ?></div>
                </div>
                
                <div class="stat-card">
                    <h3>Publications</h3>
                    <div class="number"><?php echo is_array($publicationData) ? ($publicationData['total'] ?? count($publicationData)) : $publicationData; ?></div>
                </div>
                
                <div class="stat-card">
                    <h3>Événements</h3>
                    <div class="number"><?php echo count($events); ?></div>
                </div>
            </div>

            <div class="filters-section">
                <h3 style="margin-top: 0; margin-bottom: 20px; color: #4e73df; display: flex; align-items: center; gap: 10px;">
                    🔍 Recherche et Filtres
                </h3>
                <div class="filters-row">
                    <div class="filter-group">
                        <label for="searchInput">Recherche rapide</label>
                        <input type="text" id="searchInput" placeholder="Nom, prénom ou email..." onkeyup="filterUsers()">
                    </div>
                    
                    <div class="filter-group">
                        <label for="filterRole">Rôle</label>
                        <select id="filterRole" onchange="filterUsers()">
                            <option value="">-- Tous --</option>
                            <option value="invite">Invité</option>
                            <option value="enseignant">Enseignant</option>
                            <option value="doctorant">Doctorant</option>
                            <option value="etudiant">Étudiant</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="filterStatut">Statut</label>
                        <select id="filterStatut" onchange="filterUsers()">
                            <option value="">-- Tous --</option>
                            <option value="actif">Actif</option>
                            <option value="inactif">Inactif</option>
                            <option value="suspendu">Suspendu</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="filterAdmin">Type de compte</label>
                        <select id="filterAdmin" onchange="filterUsers()">
                            <option value="">-- Tous --</option>
                            <option value="1">Administrateurs</option>
                            <option value="0">Utilisateurs</option>
                        </select>
                    </div>
                    
                    <div class="filter-buttons">
                        <button class="btn-filter btn-reset" onclick="resetFilters()">
                            🔄 Réinitialiser
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="content-section">
                <h2>
                    <span>Gestion des utilisateurs</span>
                    <button class="btn btn-primary" onclick="openModal()">
                        ➕ Ajouter un utilisateur
                    </button>
                </h2>
                <?php
                $userTable = new Table([
                    'id' => 'usersTable',
                    'headers' => ['ID', 'Nom complet', 'Username', 'Admin', 'Email', 'Rôle', 'Statut'],
                    'data' => $users,
                    'columns' => [
                        ['key' => 'id'],
                        ['key' => function($row) { return htmlspecialchars($row['nom'] . ' ' . $row['prenom']); }],
                        ['key' => 'username'], 
                        ['key' => function($row) {
                            return ($row['is_admin'] == 1) 
                                ? '<span class="badge" style="background:#6f42c1; color:white; padding:3px 8px; border-radius:10px; font-size:0.8em;">OUI</span>' 
                                : '<span style="color:#ccc;">Non</span>';
                        }],
                        ['key' => 'email'],
                        ['key' => 'role', 'format' => function($value) { return ucfirst($value); }],
                        ['key' => 'statut', 'format' => [
                            'type' => 'badge',
                            'class' => 'badge',
                            'conditions' => [
                                'actif' => 'badge-success',
                                'inactif' => 'badge-danger',
                                'suspendu' => 'badge-warning'
                            ]
                        ]]
                    ],
                    'actions' => [
                      
                        [
                            'icon' => '✏️',
                            'class' => 'btn-sm btn-edit',
                            'onclick' => 'editUser({id})'
                        ],
                        [
                            'icon' => function ($row) {
                                return $row['statut'] === 'actif' ? '⏸️' : '▶️';
                            },
                            'class' => function ($row) {
                                return $row['statut'] === 'actif' ? 'btn-sm btn-suspend' : 'btn-sm btn-activate';
                            },
                            'onclick' => function ($row) {
                                return $row['statut'] === 'actif' ? "suspendUser({$row['id']})" : "ActivateUser({$row['id']})";
                            }
                        ],
                        [
                            'icon' => '🗑️',
                            'class' => 'btn-sm btn-delete',
                            'onclick' => 'deleteUser({id})'
                        ]
                    ]
                ]);

                $userTable->display();
                ?>
            </div>
            
        </div>
        
        <div id="userModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 id="modalTitle" style="margin:0; color:#4e73df;">➕ Ajouter un utilisateur</h2>
                    <button class="close-btn" onclick="closeModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="userForm">
                        <div id="alertContainer"></div>
                        <input type="hidden" id="userId" name="id">
                        
                        <div class="form-row" style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-bottom:15px;">
                            <div class="form-group">
                                <label for="nom">Nom <span class="required">*</span></label>
                                <input type="text" class="form-control" id="nom" name="nom" required>
                            </div>
                            <div class="form-group">
                                <label for="prenom">Prénom <span class="required">*</span></label>
                                <input type="text" class="form-control" id="prenom" name="prenom" required>
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom:15px;">
                            <label for="username">Nom d'utilisateur <span class="required">*</span></label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>

                        <div class="form-group" style="background:#f8f9fc; padding:10px; border-radius:5px; margin-bottom:15px;">
                            <label style="cursor:pointer; display:flex; align-items:center; gap:10px; margin:0;">
                                <input type="checkbox" id="is_admin" name="is_admin" value="1" style="width:20px; height:20px;">
                                <span style="font-weight:bold; color:#4e73df;">Accès Administrateur</span>
                            </label>
                        </div>

                        <div class="form-group" style="margin-bottom:15px;">
                            <label for="email">Email <span class="required">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>

                        <div class="form-group" style="margin-bottom:15px;">
                            <label for="password">Mot de passe <span class="required">*</span></label>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Laisser vide si inchangé en édition">
                        </div>

                        <div class="form-row" style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-bottom:15px;">
                            <div class="form-group">
                                <label for="role">Rôle <span class="required">*</span></label>
                                <select class="form-control" id="role" name="role" required>
                                    <option value="">-- Sélectionner --</option>
                                    <option value="invite">Invité</option>
                                    <option value="enseignant">Enseignant</option>
                                    <option value="doctorant">Doctorant</option>
                                    <option value="etudiant">Étudiant</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="statut">Statut <span class="required">*</span></label>
                                <select class="form-control" id="statut" name="statut" required>
                                    <option value="actif">Actif</option>
                                    <option value="inactif">Inactif</option>
                                    <option value="suspendu">Suspendu</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom:15px;">
                            <label for="grade">Grade</label>
                            <input type="text" class="form-control" id="grade" name="grade" placeholder="Ex: Professeur, Maître de conférences...">
                        </div>

                        <div class="form-group" style="margin-bottom:15px;">
                            <label for="domaine">Domaine de recherche</label>
                            <input type="text" class="form-control" id="domaine" name="domaine_recherche">
                        </div>

                        <div class="form-group">
                            <label for="specialite">Spécialité</label>
                            <input type="text" class="form-control" id="specialite" name="specialite">
                        </div>
                    </form>
                </div>
                <div class="modal-footer" style="border-top:1px solid #eee; padding-top:15px; text-align:right;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()" style="margin-right:10px;">Annuler</button>
                    <button type="button" class="btn btn-primary" onclick="saveUser()">💾 Enregistrer</button>
                </div>
            </div>
        </div>

        <script>
            let allUsersData = <?php echo json_encode($users); ?>;

            function ucfirst(str) {
                if (!str) return '';
                return str.charAt(0).toUpperCase() + str.slice(1);
            }

            function showAlert(message, type) {
                const container = document.getElementById('alertContainer');
                if (!container) { alert(message); return; }
                
                const div = document.createElement('div');
                div.style.padding = '10px';
                div.style.borderRadius = '5px';
                div.style.marginBottom = '15px';
                div.style.fontWeight = 'bold';
                
                if(type === 'success') { div.style.background = '#d1fae5'; div.style.color = '#065f46'; }
                else { div.style.background = '#fee2e2'; div.style.color = '#b91c1c'; }
                
                div.textContent = message;
                container.appendChild(div);
                setTimeout(() => div.remove(), 3000);
            }

            function filterUsers() {
                const searchTerm = document.getElementById('searchInput').value.toLowerCase();
                const filterRole = document.getElementById('filterRole').value.toLowerCase();
                const filterStatut = document.getElementById('filterStatut').value.toLowerCase();
                const filterAdmin = document.getElementById('filterAdmin').value;

                const filtered = allUsersData.filter(user => {
                    const matchSearch = !searchTerm || 
                        user.nom.toLowerCase().includes(searchTerm) ||
                        user.prenom.toLowerCase().includes(searchTerm) ||
                        user.username.toLowerCase().includes(searchTerm) ||
                        user.email.toLowerCase().includes(searchTerm);
                    
                    const matchRole = !filterRole || user.role.toLowerCase() === filterRole;
                    const matchStatut = !filterStatut || user.statut.toLowerCase() === filterStatut;
                    const matchAdmin = filterAdmin === '' || (user.is_admin == filterAdmin);

                    return matchSearch && matchRole && matchStatut && matchAdmin;
                });

                displayUsers(filtered);
            }

            function resetFilters() {
                document.getElementById('searchInput').value = '';
                document.querySelectorAll('select').forEach(s => s.value = '');
                displayUsers(allUsersData);
            }

            function displayUsers(users) {
                let tbody = document.querySelector('#usersTable tbody');
                if (!tbody) tbody = document.querySelector('table tbody');
                if (!tbody) return;

                tbody.innerHTML = '';

                if (users.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="8" style="text-align:center; padding:20px; color:#999;">Aucun utilisateur trouvé</td></tr>';
                    return;
                }

                users.forEach(user => {
                    const tr = document.createElement('tr');
                    tr.style.borderBottom = '1px solid #eee';
                    
                    const isAdmin = user.is_admin == 1 
                        ? '<span class="badge" style="background:#6f42c1; color:white; padding:3px 8px; border-radius:10px; font-size:0.8em;">OUI</span>' 
                        : '<span style="color:#ccc;">Non</span>';
                    
                    const statutClass = user.statut === 'actif' ? 'badge-success' : (user.statut === 'suspendu' ? 'badge-warning' : 'badge-danger');
                    const statutBadge = `<span class="badge ${statutClass}">${ucfirst(user.statut)}</span>`;
                    
                    const actionBtn = user.statut === 'actif'
                        ? `<button class="btn-sm btn-suspend" onclick="suspendUser(${user.id})" title="Suspendre">⏸️</button>`
                        : `<button class="btn-sm btn-activate" onclick="ActivateUser(${user.id})" title="Activer">▶️</button>`;
                    
                    tr.innerHTML = `
                        <td style="padding:10px;">${user.id}</td>
                        <td style="padding:10px;">${user.nom} ${user.prenom}</td>
                        <td style="padding:10px;">${user.username}</td>
                        <td style="padding:10px;">${isAdmin}</td>
                        <td style="padding:10px;">${user.email}</td>
                        <td style="padding:10px;">${ucfirst(user.role)}</td>
                        <td style="padding:10px;">${statutBadge}</td>
                        <td style="padding:10px;">
                            <div class="action-btns" style="display:flex; gap:5px;">
                                <button class="btn-sm btn-view" onclick="viewUser(${user.id})" title="Voir">👁️</button>
                                <button class="btn-sm btn-edit" onclick="editUser(${user.id})" title="Modifier">✏️</button>
                                ${actionBtn}
                                <button class="btn-sm btn-delete" onclick="deleteUser(${user.id})" title="Supprimer">🗑️</button>
                            </div>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            }

            function openModal(editMode = false, userId = null) {
                const modal = document.getElementById('userModal');
                const title = document.getElementById('modalTitle');
                const form = document.getElementById('userForm');
                
                if (editMode && userId) {
                    title.textContent = '✏️ Modifier un utilisateur';
                    loadUserData(userId);
                } else {
                    title.textContent = '➕ Ajouter un utilisateur';
                    form.reset();
                    document.getElementById('userId').value = '';
                }
                
                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
            }

            function closeModal() {
                document.getElementById('userModal').classList.remove('active');
                document.body.style.overflow = 'auto';
            }

            async function loadUsers() {
                try {
                    const res = await fetch('../controllers/api.php?action=getUsers');
                    const json = await res.json();
                    if(json.success) {
                        allUsersData = json.users;
                        displayUsers(allUsersData);
                    }
                } catch(e) { console.error(e); }
            }

            function saveUser() {
                const form = document.getElementById('userForm');
                if(!form.checkValidity()) { form.reportValidity(); return; }
                
                const formData = new FormData(form);
                formData.set('is_admin', document.getElementById('is_admin').checked ? 1 : 0);
                
                const id = document.getElementById('userId').value;
                const action = id ? 'updateUser&id=' + id : 'createUser';

                fetch('../controllers/api.php?action=' + action, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(Object.fromEntries(formData))
                })
                .then(r => r.json())
                .then(data => {
                    if(data.success) {
                        showAlert('Opération réussie !', 'success');
                        closeModal();
                        loadUsers();
                    } else {
                        showAlert('Erreur: ' + data.message, 'error');
                    }
                });
            }

            async function loadUserData(id) {
                try {
                    const res = await fetch(`../controllers/api.php?action=getUser&id=${id}`);
                    const json = await res.json();
                    if(json.success) {
                        const u = json.user;
                        document.getElementById('userId').value = u.id;
                        document.getElementById('nom').value = u.nom;
                        document.getElementById('prenom').value = u.prenom;
                        document.getElementById('username').value = u.username;
                        document.getElementById('email').value = u.email;
                        document.getElementById('is_admin').checked = (u.is_admin == 1);
                        document.getElementById('role').value = u.role;
                        document.getElementById('statut').value = u.statut;
                        document.getElementById('grade').value = u.grade || '';
                        document.getElementById('domaine').value = u.domaine_recherche || '';
                        document.getElementById('specialite').value = u.specialite || '';
                    }
                } catch(e) { alert('Erreur chargement données'); }
            }

            function editUser(id) { openModal(true, id); }
            function viewUser(id) { openModal(true, id); }

            function ActivateUser(id) {
                if(confirm('Activer cet utilisateur ?')) {
                    fetch(`../controllers/api.php?action=activateUser&id=${id}`, {method:'POST'})
                    .then(r=>r.json()).then(d => { if(d.success) loadUsers(); });
                }
            }

            function suspendUser(id) {
                if(confirm('Suspendre cet utilisateur ?')) {
                    fetch(`../controllers/api.php?action=suspendUser&id=${id}`, {method:'POST'})
                    .then(r=>r.json()).then(d => { if(d.success) loadUsers(); });
                }
            }

            function deleteUser(id) {
                if(confirm('Supprimer définitivement ?')) {
                    fetch(`../controllers/api.php?action=deleteUser&id=${id}`, {method:'POST'})
                    .then(r=>r.json()).then(d => { if(d.success) loadUsers(); });
                }
            }

            window.onclick = function(e) {
                if (e.target == document.getElementById('userModal')) closeModal();
            }
        </script>
        <?php
        return ob_get_clean();
    }
}
?>