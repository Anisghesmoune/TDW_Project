<?php
require_once '../config/Database.php';
require_once '../models/UserModel.php';

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
$userModel = new UserModel();
$users = $userModel->getAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - Laboratoire</title>
    <link rel="stylesheet" href="admin_dashboard.css">
    <link rel="stylesheet" href="modelAddUser.css">
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
                <h1>Tableau de bord administrateur</h1>
                <p style="color: #666;">Vue d'ensemble du laboratoire</p>
            </div>
            <a href="../logout.php" class="logout-btn">Déconnexion</a>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Utilisateurs</h3>
                <div class="number"><?php echo count($users); ?></div>
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
                <span>Gestion des utilisateurs</span>
                <button class="btn btn-primary" onclick="openModal()">
                    ➕ Ajouter un utilisateur
                </button>
            </h2>
            <?php
            $userTable = new Table([
                'id' => 'usersTable',
                'headers' => ['ID', 'Nom complet', 'Username', 'Email', 'Rôle', 'Statut'],
                'data' => $users,
                'columns' => [
                    ['key' => 'id'],
                    ['key' => function($row) { 
                        return $row['nom'] . ' ' . $row['prenom']; 
                    }],
                    ['key' => 'username'],
                    ['key' => 'email'],
                    ['key' => 'role', 'format' => function($value) {
                        return ucfirst($value);
                    }],
                    ['key' => 'statut', 'format' => [
                        'type' => 'badge',
                        'class' => 'badge',
                        'conditions' => [
                            'actif' => 'badge-success',
                            'inactif' => 'badge-danger'
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
        return $row['statut'] === 'actif'
            ? '⏸️ Suspendre'
            : '▶️ Activer';
    },
    

    'class' => function ($row) {
        return $row['statut'] === 'actif'
            ? 'btn-sm btn-suspend'
            : 'btn-sm btn-activate';
    },
    'onclick' => function ($row) {
        if ($row['statut'] === 'actif') {
            return "suspendUser({$row['id']})";
        } else {
            return "ActivateUser({$row['id']})";
        }
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
        
        <div class="content-section">
            <h2>Activité récente</h2>
            <ul style="list-style: none;">
                <li style="padding: 10px; border-bottom: 1px solid #e0e0e0;">
                    📝 <strong>Nouvelle publication</strong> soumise par Kamel Amrani - Il y a 2 heures
                </li>
                <li style="padding: 10px; border-bottom: 1px solid #e0e0e0;">
                    👤 <strong>Nouvel utilisateur</strong> inscrit: Fatima Kaci - Il y a 5 heures
                </li>
                <li style="padding: 10px; border-bottom: 1px solid #e0e0e0;">
                    🗓️ <strong>Réservation</strong> effectuée pour Salle A - Il y a 1 jour
                </li>
            </ul>
        </div>
    </div>
    
    <!-- Modal -->
    <div id="userModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">➕ Ajouter un utilisateur</h2>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="userForm">
                    <div id="alertContainer"></div>

                    <input type="hidden" id="userId" name="id">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nom">Nom <span class="required">*</span></label>
                            <input type="text" class="form-control" id="nom" name="nom" required>
                        </div>
                        <div class="form-group">
                            <label for="prenom">Prénom <span class="required">*</span></label>
                            <input type="text" class="form-control" id="prenom" name="prenom" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="username">Username <span class="required">*</span></label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email <span class="required">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Mot de passe <span class="required">*</span></label>
                        <input type="password" class="form-control" id="password" name="password">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="role">Rôle <span class="required">*</span></label>
                            <select class="form-control" id="role" name="role" required>
                                <option value="">-- Sélectionner --</option>
                                <option value="admin">Admin</option>
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

                    <div class="form-group">
                        <label for="grade">Grade</label>
                        <input type="text" class="form-control" id="grade" name="grade" placeholder="Ex: Professeur, Maître de conférences...">
                    </div>

                    <div class="form-group">
                        <label for="domaine">Domaine de recherche</label>
                        <input type="text" class="form-control" id="domaine" name="domaine_recherche">
                    </div>

                    <div class="form-group">
                        <label for="specialite">Spécialité</label>
                        <input type="text" class="form-control" id="specialite" name="specialite">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Annuler</button>
                <button type="button" class="btn btn-primary" onclick="saveUser()">💾 Enregistrer</button>
            </div>
        </div>
    </div>

    <script>
        // Fonction helper pour capitaliser
        function ucfirst(str) {
            if (!str) return '';
            return str.charAt(0).toUpperCase() + str.slice(1);
        }

        // Ouvrir le modal
        function openModal(editMode = false, userId = null) {
            const modal = document.getElementById('userModal');
            const modalTitle = document.getElementById('modalTitle');
            const form = document.getElementById('userForm');
            
            if (editMode && userId) {
                modalTitle.textContent = '✏️ Modifier un utilisateur';
                loadUserData(userId);
            } else {
                modalTitle.textContent = '➕ Ajouter un utilisateur';
                form.reset();
                document.getElementById('userId').value = '';
            }
            
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        // Charger les utilisateurs
        async function loadUsers() {
            try {
                const response = await fetch('../controllers/api.php?action=getUsers');
                const result = await response.json();

                if (!result.success) {
                    showAlert('Erreur: ' + result.message, 'error');
                    return;
                }

                const users = result.users;

                // ✅ Chercher le tbody dans la table avec l'ID
                const tableBody = document.querySelector('#usersTable tbody');
                
                if (!tableBody) {
                    console.warn('Table non trouvée, rechargement de la page...');
                    location.reload();
                    return;
                }
                
                tableBody.innerHTML = '';

                users.forEach(user => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${user.id}</td>
                        <td>${user.nom} ${user.prenom}</td>
                        <td>${user.username}</td>
                        <td>${user.email}</td>
                        <td>${ucfirst(user.role)}</td>
                        <td>
                            <span class="badge ${user.statut === 'actif' ? 'badge-success' : 'badge-danger'}">
                                ${ucfirst(user.statut)}
                            </span>
                        </td>
                        <td>
                            <div class="action-btns">
                                <button class="btn-sm btn-edit" onclick="editUser(${user.id})">✏️</button>
                                <button class="btn-sm btn-suspend" onclick="suspendUser(${user.id})">⏸️</button>
                                <button class="btn-sm btn-delete" onclick="deleteUser(${user.id})">🗑️</button>
                            </div>
                        </td>
                    `;
                    tableBody.appendChild(tr);
                });
            } catch (error) {
                console.error(error);
                showAlert('Impossible de charger les utilisateurs', 'error');
            }
        }

        // Fermer le modal
        function closeModal() {
            const modal = document.getElementById('userModal');
            modal.classList.remove('active');
            document.body.style.overflow = 'auto';
            document.getElementById('userForm').reset();
        }

        // Fermer le modal en cliquant à l'extérieur
        document.getElementById('userModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // Sauvegarder l'utilisateur
        function saveUser() {
            const form = document.getElementById('userForm');
            const formData = new FormData(form);
            
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            
            const data = Object.fromEntries(formData);
            console.log('Données à envoyer:', data);
            
            fetch('../controllers/api.php?action=createUser', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Utilisateur enregistré avec succès!', 'success');
                    closeModal();
                    loadUsers();
                } else {
                    showAlert('Erreur: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showAlert('Erreur de connexion', 'error');
            });
        }

        // Afficher une alerte
        function showAlert(message, type) {
            const container = document.getElementById('alertContainer');
            
            if (!container) {
                console.warn('alertContainer manquant');
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

        // Éditer un utilisateur
        function editUser(id) {
            window.location.href = `updateUser.php?id=${id}`;
        }

        // Charger les données d'un utilisateur
        async function loadUserData(id) {
            try {
                const response = await fetch(`../controllers/api.php?action=getUser&id=${id}`);
                const result = await response.json();

                if (result.success) {
                    const user = result.user;
                    document.getElementById('userId').value = user.id;
                    document.getElementById('nom').value = user.nom;
                    document.getElementById('prenom').value = user.prenom;
                    document.getElementById('username').value = user.username;
                    document.getElementById('email').value = user.email;
                    document.getElementById('role').value = user.role;
                    document.getElementById('statut').value = user.statut;
                    document.getElementById('grade').value = user.grade || '';
                    document.getElementById('domaine').value = user.domaine_recherche || '';
                    document.getElementById('specialite').value = user.specialite || '';
                } else {
                    alert(result.message);
                }
            } catch (error) {
                console.error('Erreur:', error);
                alert('Impossible de charger les données.');
            }
        }

        // activer un utilisateur
        function ActivateUser(id) {
            if (confirm('Voulez-vous vraiment activer cet utilisateur ?')) {
                fetch(`../controllers/api.php?action=activateUser&id=${id}`, {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('Utilisateur activé', 'success');
                        loadUsers();
                    } else {
                        showAlert('Erreur: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    showAlert('Erreur de connexion', 'error');
                });
            }
        }
         function suspendUser(id) {
            if (confirm('Voulez-vous vraiment suspendre cet utilisateur ?')) {
                fetch(`../controllers/api.php?action=suspendUser&id=${id}`, {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('Utilisateur suspendu', 'success');
                        loadUsers();
                    } else {
                        showAlert('Erreur: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    showAlert('Erreur de connexion', 'error');
                });
            }
        }

        
        // Supprimer un utilisateur
        function deleteUser(id) {
            if (confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ? Cette action est irréversible.')) {
                fetch(`../controllers/api.php?action=deleteUser&id=${id}`, {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('Utilisateur supprimé', 'success');
                        loadUsers();
                    } else {
                        showAlert('Erreur: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    showAlert('Erreur de connexion', 'error');
                });
            }
        }

        // Fermer le modal avec Échap
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
    </script>
</body>
</html>