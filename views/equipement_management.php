<?php
// Imports des dépendances de la structure de vue
require_once __DIR__ . '/../views/public/View.php';
require_once __DIR__ . '/../views/public/components/UIHeader.php';
require_once __DIR__ . '/../views/public/components/UIFooter.php';

class EquipementAdminView extends View {

    /**
     * Méthode principale pour structurer la page
     */
    public function render() {
        // Extraction des données
        $config = $this->data['config'] ?? [];
        $menuData = $this->data['menu'] ?? [];
        $pageTitle = $this->data['title'] ?? 'Gestion des Équipements';

        // CSS spécifiques (ceux que vous aviez dans le <head>)
        $customCss = [
            'views/admin_dashboard.css',
            'views/modelAddUser.css',
            'views/teamManagement.css',
            'views/landingPage.css',
        ];

        // 1. Rendu du Header (Remplace la Sidebar)
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
     * Contenu spécifique : C'est ici que se trouve TOUT votre code HTML/JS original
     */
    protected function content() {
        ob_start();
        ?>
        
        <!-- Styles internes spécifiques -->
        <style>
            .reservation-badge { display: inline-block; padding: 3px 8px; border-radius: 12px; font-size: 0.75em; margin-left: 5px; }
            .reserved-equipment { background-color: #fef3c7 !important; }
            .conflict-warning { background: #fee2e2; border-left: 4px solid #ef4444; padding: 10px; margin: 10px 0; border-radius: 4px; }
            .conflict-info { background: #fef3c7; border-left: 4px solid #f59e0b; padding: 10px; margin: 10px 0; border-radius: 4px; }
            .tooltip-info { position: relative; cursor: help; }
            .tooltip-info:hover::after { content: attr(data-tooltip); position: absolute; background: #1f2937; color: white; padding: 5px 10px; border-radius: 4px; white-space: nowrap; z-index: 1000; bottom: 100%; left: 50%; transform: translateX(-50%); font-size: 0.85em; }
            .badge-conflict { background: #f59e0b; color: white; padding: 2px 8px; border-radius: 12px; font-size: 0.75em; }
            
            .notification-badge { position: absolute; top: -8px; right: -8px; background: #ef4444; color: white; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 0.75em; font-weight: bold; }
            .conflict-item { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #f59e0b; }
            .conflict-actions { display: flex; gap: 10px; margin-top: 10px; }

            /* Ajustements Layout sans Sidebar */
            .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
            .stat-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); text-align: center; border-bottom: 4px solid #ddd; }
            .stat-card .number { font-size: 2em; font-weight: bold; margin-top: 10px; }
            
            /* Modale */
            .modal { display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background-color:rgba(0,0,0,0.5); backdrop-filter: blur(2px); }
            .modal.active { display: flex; align-items: center; justify-content: center; }
            .modal-content { background:white; padding:30px; border-radius:10px; max-height:90vh; overflow-y:auto; box-shadow: 0 10px 25px rgba(0,0,0,0.2); width: 90%; }
            .modal-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 20px; }
            .close-btn { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #aaa; }
            
            /* Formulaires */
            .form-group { margin-bottom: 15px; }
            .form-control { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
            .btn { padding: 10px 20px; border-radius: 5px; border: none; cursor: pointer; font-weight: bold; }
            .btn-primary { background: #4e73df; color: white; }
            .btn-secondary { background: #858796; color: white; }
            .btn-warning { background: #f6c23e; color: white; }
            .btn-danger { background: #e74a3b; color: white; }
            .btn-success { background: #1cc88a; color: white; }
        </style>

        <!-- Top Bar Interne -->
        <div class="top-bar" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 1px solid #eee; padding-bottom: 20px;">
            <div>
                <h1 style="margin: 0; color: #2c3e50;">Gestion des Équipements</h1>
                <p style="color: #666; margin-top: 5px;">Suivi et gestion des ressources matérielles</p>
            </div>
        </div>
        
        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card" style="border-bottom-color: #4e73df;">
                <h3>Total Équipements</h3>
                <div class="number" id="totalEquipment">0</div>
            </div>
            
            <div class="stat-card" style="border-bottom-color: #10b981;">
                <h3>Disponibles</h3>
                <div class="number" style="color: #10b981;" id="availableCount">0</div>
            </div>
            
            <div class="stat-card" style="border-bottom-color: #f59e0b;">
                <h3>Réservés</h3>
                <div class="number" style="color: #f59e0b;" id="reservedCount">0</div>
            </div>
            
            <div class="stat-card" style="border-bottom-color: #ef4444;">
                <h3>En Maintenance</h3>
                <div class="number" style="color: #ef4444;" id="maintenanceCount">0</div>
            </div>
            
            <div class="stat-card" style="cursor: pointer; position: relative; border-bottom-color: #f59e0b;" onclick="openConflictsModal()">
                <h3>⚠️ Conflits</h3>
                <div class="number" style="color: #f59e0b;" id="conflictCount">0</div>
                <span id="conflictBadge" class="notification-badge" style="display: none;"></span>
            </div>
        </div>

        <!-- Filtres et recherche -->
        <div class="content-section" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); margin-bottom: 30px;">
            <div style="display: flex; gap: 15px; flex-wrap: wrap; align-items: center;">
                <input type="text" id="searchInput" placeholder="🔍 Rechercher un équipement..." 
                       class="form-control" style="flex: 1; min-width: 250px;">
                
                <select id="filterType" onchange="filterByType(this.value)" 
                        class="form-control" style="width: auto;">
                    <option value="">🏷️ Tous les types</option>
                </select>

                <select id="filterStatus" onchange="filterByStatus(this.value)" 
                        class="form-control" style="width: auto;">
                    <option value="">📊 Tous les statuts</option>
                    <option value="libre">✅ Disponible</option>
                    <option value="réserve">📌 Réservé</option>
                    <option value="en_maintenance">🔧 En maintenance</option>
                </select>

                <button class="btn btn-secondary" onclick="resetFilters()">
                    🔄 Réinitialiser
                </button>
            </div>
        </div>
        
        <!-- Tableau des Équipements -->
        <div class="content-section" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
            <h2 style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #f8f9fa; padding-bottom: 20px; margin-bottom: 20px;">
                <span>Liste des Équipements</span>
                <div style="display: inline-flex; gap: 10px;">
                    <button class="btn btn-primary" onclick="openModal()">
                        ➕ Équipement
                    </button>
                    <button class="btn btn-primary" onclick="openTypeModal()" style="background-color: #36b9cc;">
                        ➕ Type
                    </button>
                    <button class="btn btn-warning" onclick="openConflictsModal()" style="position: relative;">
                        ⚠️ Conflits
                        <span id="conflictBadgeBtn" class="notification-badge" style="display: none;"></span>
                    </button>
                    <button class="btn btn-secondary" onclick="window.location.href='index.php?route=reservation-history'">
                        📜 Historique
                    </button>
                </div>
            </h2>
            
            <div id="loadingSpinner" style="text-align: center; padding: 40px;">
                <p>⏳ Chargement des équipements...</p>
            </div>
            
            <div id="equipmentTableContainer" style="display: none;">
                <table class="data-table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f8f9fa; border-bottom: 2px solid #e3e6f0;">
                            <th style="padding: 12px; text-align: left;">ID</th>
                            <th style="padding: 12px; text-align: left;">Nom</th>
                            <th style="padding: 12px; text-align: left;">Type</th>
                            <th style="padding: 12px; text-align: left;">Statut</th>
                            <th style="padding: 12px; text-align: left;">Réservation Actuelle</th>
                            <th style="padding: 12px; text-align: left;">Localisation</th>
                            <th style="padding: 12px; text-align: left;">Maintenance</th>
                            <th style="padding: 12px; text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="equipmentTableBody">
                    </tbody>
                </table>
            </div>
        </div>
    
        <!-- Modal Ajouter/Modifier Équipement -->
        <div id="equipmentModal" class="modal">
            <div class="modal-content" style="max-width: 800px;">
                <div class="modal-header">
                    <h2 id="modalTitle" style="margin:0; color:#4e73df;">➕ Ajouter un équipement</h2>
                    <button class="close-btn" onclick="closeEquipmentModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="equipmentForm">
                        <div id="alertContainer"></div>
                        <input type="hidden" id="equipmentId" name="id">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="nom">Nom de l'équipement <span class="required" style="color:red">*</span></label>
                                <input type="text" class="form-control" id="nom" name="nom" required 
                                       placeholder="Ex: Ordinateur portable Dell XPS 15">
                            </div>
                        </div>

                        <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div class="form-group">
                                <label for="id_type">Type d'équipement <span class="required" style="color:red">*</span></label>
                                <select class="form-control" id="id_type" name="id_type" required>
                                    <option value="">-- Sélectionner un type --</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="etat">Statut <span class="required" style="color:red">*</span></label>
                                <select class="form-control" id="etat" name="etat" required>
                                    <option value="libre">✅ Disponible</option>
                                    <option value="réserve">📌 Réservé</option>
                                    <option value="en_maintenance">🔧 En maintenance</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3" 
                                      placeholder="Caractéristiques, spécifications techniques..."></textarea>
                        </div>

                        <div class="form-group">
                            <label for="localisation">Localisation</label>
                            <input type="text" class="form-control" id="localisation" name="localisation" 
                                   placeholder="Ex: Bureau 205, Bâtiment A">
                        </div>

                        <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div class="form-group">
                                <label for="derniere_maintenance">Dernière maintenance</label>
                                <input type="date" class="form-control" id="derniere_maintenance" name="derniere_maintenance">
                            </div>

                            <div class="form-group">
                                <label for="prochaine_maintenance">Prochaine maintenance</label>
                                <input type="date" class="form-control" id="prochaine_maintenance" name="prochaine_maintenance">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer" style="border-top:1px solid #eee; padding-top:15px; text-align:right;">
                    <button type="button" class="btn btn-secondary" onclick="closeEquipmentModal()" style="margin-right:10px;">Annuler</button>
                    <button type="button" class="btn btn-primary" onclick="saveEquipment()">💾 Enregistrer</button>
                </div>
            </div>
        </div>
        
        <!-- Modal Ajouter Type d'Équipement -->
        <div id="typeEquipmentModal" class="modal">
            <div class="modal-content" style="max-width: 600px;">
                <div class="modal-header">
                    <h2 id="modalTitleType" style="margin:0; color:#36b9cc;">➕ Ajouter un type d'équipement</h2>
                    <button class="close-btn" onclick="closeTypeModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="typeEquipmentForm">
                        <div id="typeAlertContainer"></div>
                        
                        <div class="form-group">
                            <label for="type_nom">Nom du type <span class="required" style="color:red">*</span></label>
                            <input type="text" class="form-control" id="type_nom" name="nom" required 
                                   placeholder="Ex: Ordinateur, Caméra, Projecteur">
                        </div>

                        <div class="form-group">
                            <label for="type_description">Description</label>
                            <textarea class="form-control" id="type_description" name="description" rows="3" 
                                      placeholder="Description du type d'équipement..."></textarea>
                        </div>

                        <div class="form-group">
                            <label for="type_icone">Icône (emoji) <span class="required" style="color:red">*</span></label>
                            <input type="text" class="form-control" id="type_icone" name="icone" required
                                   placeholder="Ex: 💻, 📷, 📊" maxlength="10">
                            <small style="color: #666;">Utilisez un emoji pour représenter ce type</small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer" style="border-top:1px solid #eee; padding-top:15px; text-align:right;">
                    <button type="button" class="btn btn-secondary" onclick="closeTypeModal()" style="margin-right:10px;">Annuler</button>
                    <button type="button" class="btn btn-primary" onclick="saveEquipmentType()" style="background-color:#36b9cc;">💾 Enregistrer</button>
                </div>
            </div>
        </div>

        <!-- Modal Réservation avec gestion de conflit -->
        <div id="reservationModal" class="modal">
            <div class="modal-content" style="max-width: 600px;">
                <div class="modal-header">
                    <h2 style="margin:0;">📅 Réserver cet équipement</h2>
                    <button class="close-btn" onclick="closeReservationModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="reservationForm">
                        <div id="reservationAlertContainer"></div>
                        
                        <input type="hidden" id="reservationEquipmentId">
                        <input type="hidden" id="forceRequest" value="false">
                        
                        <div class="form-group" style="margin-bottom: 20px; background: #f3f4f6; padding: 10px; border-radius: 5px;">
                            <label style="font-weight: bold; color: #4b5563;">Équipement concerné :</label>
                            <div id="reservationEquipmentNameDisplay" style="font-size: 1.1em; color: #1f2937;"></div>
                        </div>

                        <div class="form-group">
                            <label for="reservationUserId">Utilisateur bénéficiaire <span class="required" style="color:red">*</span></label>
                            <div style="display: flex; gap: 10px;">
                                <input type="text" id="userSearchFilter" placeholder="🔍 Filtrer par nom..." 
                                       class="form-control" style="width: 40%;">
                                
                                <select id="reservationUserId" class="form-control" required style="width: 60%;">
                                    <option value="">-- Chargement... --</option>
                                </select>
                            </div>
                            <small style="color: #666;">Sélectionnez la personne pour qui vous faites la réservation.</small>
                        </div>

                        <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div class="form-group">
                                <label for="reservation_date_debut">Date début <span class="required" style="color:red">*</span></label>
                                <input type="date" class="form-control" id="reservation_date_debut" required>
                            </div>

                            <div class="form-group">
                                <label for="reservation_date_fin">Date fin <span class="required" style="color:red">*</span></label>
                                <input type="date" class="form-control" id="reservation_date_fin" required>
                            </div>
                        </div>

                        <div id="conflictInfo" style="display: none;" class="conflict-info">
                            <strong>⚠️ Conflit détecté</strong>
                            <div id="conflictDetails"></div>
                            <p style="margin-top: 10px; font-size: 0.9em;">
                                Voulez-vous envoyer une <strong>demande prioritaire</strong> à l'administrateur pour arbitrage ?
                            </p>
                        </div>

                        <div class="form-group">
                            <label for="reservation_notes">Notes</label>
                            <textarea class="form-control" id="reservation_notes" rows="2" 
                                      placeholder="Motif de la réservation..."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer" style="border-top:1px solid #eee; padding-top:15px; text-align:right;">
                    <button type="button" class="btn btn-secondary" onclick="closeReservationModal()" style="margin-right:10px;">Annuler</button>
                    <button type="button" class="btn btn-primary" id="btnConfirmReservation" onclick="saveReservation()">
                        📅 Confirmer la réservation
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal Gestion des Conflits -->
        <div id="conflictsModal" class="modal">
            <div class="modal-content" style="max-width: 1000px;">
                <div class="modal-header">
                    <h2 style="margin:0; color:#f6c23e;">⚠️ Gestion des Conflits de Réservation</h2>
                    <button class="close-btn" onclick="closeConflictsModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <div id="conflictsList"></div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #eee; padding-top:15px; text-align:right;">
                    <button type="button" class="btn btn-secondary" onclick="closeConflictsModal()">Fermer</button>
                </div>
            </div>
        </div>

        <!-- Modal Voir Réservations -->
        <div id="viewReservationsModal" class="modal">
            <div class="modal-content" style="max-width: 900px;">
                <div class="modal-header">
                    <h2 style="margin:0; color:#36b9cc;">📋 Réservations de l'équipement</h2>
                    <button class="close-btn" onclick="closeViewReservationsModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <div id="reservationsList"></div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #eee; padding-top:15px; text-align:right;">
                    <button type="button" class="btn btn-secondary" onclick="closeViewReservationsModal()">Fermer</button>
                </div>
            </div>
        </div>

        <script>
        // ============================================
        // VARIABLES GLOBALES
        // ============================================
        let allEquipments = [];
        let allTypes = [];
        let currentEquipmentReservations = [];
        let conflictReservations = [];
        let cachedUsers = [];

        // ============================================
        // CHARGEMENT INITIAL
        // ============================================
        function setMinDates() {
            const today = new Date().toISOString().split('T')[0];
            const dateDebut = document.getElementById('reservation_date_debut');
            const dateFin = document.getElementById('reservation_date_fin');
            
            if (dateDebut) dateDebut.min = today;
            if (dateFin) dateFin.min = today;
        }

        document.addEventListener('DOMContentLoaded', () => {
            loadInitialData();
            setMinDates();
            loadUsersForReservation();
            
            const searchInput = document.getElementById('userSearchFilter');
            if (searchInput) {
                searchInput.addEventListener('input', function(e) {
                    const term = e.target.value.toLowerCase();
                    const options = document.querySelectorAll('#reservationUserId option');
                    
                    options.forEach(opt => {
                        if (opt.value === "") return;
                        const text = opt.dataset.search || "";
                        opt.style.display = text.includes(term) ? '' : 'none';
                    });
                    
                    const select = document.getElementById('reservationUserId');
                    if (select.selectedOptions[0] && select.selectedOptions[0].style.display === 'none') {
                        select.value = "";
                    }
                });
            }
        });

        async function loadUsersForReservation() {
            const userSelect = document.getElementById('reservationUserId');
            if (!userSelect) return;

            if (cachedUsers.length > 0) {
                populateUserSelect(cachedUsers);
                return;
            }

            try {
                userSelect.innerHTML = '<option value="">Chargement...</option>';
                
                const response = await fetch('../controllers/api.php?action=getUsers');
                const result = await response.json();

                let usersArray = [];

                if (Array.isArray(result)) {
                    usersArray = result;
                } else if (result.data && Array.isArray(result.data)) {
                    usersArray = result.data;
                } else if (result.users && Array.isArray(result.users)) {
                    usersArray = result.users;
                }

                if (Array.isArray(usersArray) && usersArray.length > 0) {
                    cachedUsers = usersArray;
                    populateUserSelect(cachedUsers);
                } else {
                    userSelect.innerHTML = '<option value="">Aucun utilisateur trouvé</option>';
                }

            } catch (error) {
                console.error('Erreur chargement utilisateurs:', error);
                userSelect.innerHTML = '<option value="">Erreur connexion</option>';
            }
        }

        function populateUserSelect(users) {
            const userSelect = document.getElementById('reservationUserId');
            if (!userSelect) return;

            userSelect.innerHTML = '<option value="">-- Sélectionner un utilisateur --</option>';
            
            users.forEach(user => {
                const option = document.createElement('option');
                option.value = user.id;
                option.textContent = `${user.nom.toUpperCase()} ${user.prenom} (${user.email})`;
                option.dataset.search = `${user.nom} ${user.prenom} ${user.email}`.toLowerCase();
                userSelect.appendChild(option);
            });
        }

        async function loadInitialData() {
            try {
                await loadTypes();
                await loadStats();
                await loadEquipments();
                await loadConflicts();
                
                console.log('✅ Données chargées avec succès');
            } catch (error) {
                console.error('❌ Erreur lors du chargement initial:', error);
                showAlert('❌ Erreur lors du chargement des données', 'error');
            }
        }

        async function loadConflicts() {
            try {
                const response = await fetch('../controllers/api.php?action=getConflictReservations');
                const result = await response.json();
                
                if (result.success && result.data) {
                    conflictReservations = result.data;
                    const count = conflictReservations.length;
                    
                    document.getElementById('conflictCount').textContent = count;
                    
                    const badge = document.getElementById('conflictBadge');
                    const badgeBtn = document.getElementById('conflictBadgeBtn');
                    
                    if (count > 0) {
                        badge.textContent = count;
                        badge.style.display = 'flex';
                        badgeBtn.textContent = count;
                        badgeBtn.style.display = 'flex';
                    } else {
                        badge.style.display = 'none';
                        badgeBtn.style.display = 'none';
                    }
                }
            } catch (error) {
                console.error('❌ Erreur chargement conflits:', error);
            }
        }

        async function loadTypes() {
            try {
                const response = await fetch('../controllers/api.php?action=getEquipmentTypes');
                const result = await response.json();
                
                if (result.success && result.data) {
                    allTypes = result.data;
                    
                    const filterSelect = document.getElementById('filterType');
                    filterSelect.innerHTML = '<option value="">🏷️ Tous les types</option>';
                    
                    allTypes.forEach(type => {
                        const option = document.createElement('option');
                        option.value = type.id;
                        option.textContent = `${type.icone || ''} ${type.nom} (${type.equipment_count || 0})`;
                        filterSelect.appendChild(option);
                    });
                    
                    const formSelect = document.getElementById('id_type');
                    formSelect.innerHTML = '<option value="">-- Sélectionner un type --</option>';
                    
                    allTypes.forEach(type => {
                        const option = document.createElement('option');
                        option.value = type.id;
                        option.textContent = `${type.icone || ''} ${type.nom}`;
                        formSelect.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('❌ Erreur chargement types:', error);
            }
        }

        async function loadStats() {
            try {
                const response = await fetch('../controllers/api.php?action=getEquipmentStats');
                const result = await response.json();
                
                if (result.success) {
                    const statusStats = result.statusStats || [];
                    
                    let total = 0;
                    let available = 0;
                    let reserved = 0;
                    let en_maintenance = 0;
                    
                    statusStats.forEach(stat => {
                        total += parseInt(stat.count) || 0;
                        
                        switch (stat.etat) {
                            case 'libre':
                                available = parseInt(stat.count) || 0;
                                break;
                            case 'réserve':
                                reserved = parseInt(stat.count) || 0;
                                break;
                            case 'en_maintenance':
                                en_maintenance = parseInt(stat.count) || 0;
                                break;
                        }
                    });
                    
                    document.getElementById('totalEquipment').textContent = total;
                    document.getElementById('availableCount').textContent = available;
                    document.getElementById('reservedCount').textContent = reserved;
                    document.getElementById('maintenanceCount').textContent = en_maintenance;
                }
            } catch (error) {
                console.error('❌ Erreur chargement stats:', error);
            }
        }

        async function loadEquipments() {
            try {
                const response = await fetch('../controllers/api.php?action=getEquipments');
                const result = await response.json();

                if (result.success && result.data) {
                    allEquipments = result.data;
                    renderEquipmentTable(allEquipments);
                    
                    document.getElementById('loadingSpinner').style.display = 'none';
                    document.getElementById('equipmentTableContainer').style.display = 'block';
                } else {
                    throw new Error(result.message || 'Erreur lors du chargement');
                }
            } catch (error) {
                console.error('❌ Erreur:', error);
                document.getElementById('loadingSpinner').innerHTML = '<p style="color: red;">❌ Erreur lors du chargement des équipements</p>';
            }
        }

        function renderEquipmentTable(equipments) {
            const tbody = document.getElementById('equipmentTableBody');
            tbody.innerHTML = '';
            
            if (!equipments || equipments.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 20px;">Aucun équipement trouvé</td></tr>';
                return;
            }
            
            equipments.forEach(equipment => {
                const tr = document.createElement('tr');
                tr.style.borderBottom = "1px solid #eee";
                
                if (equipment.reservation_statut) {
                    tr.classList.add('reserved-equipment');
                }
                
                const tdId = document.createElement('td');
                tdId.textContent = equipment.id;
                tdId.style.padding = "12px";
                tr.appendChild(tdId);
                
                const tdNom = document.createElement('td');
                const nom = equipment.nom || '';
                tdNom.textContent = nom.length > 30 ? nom.substring(0, 30) + '...' : nom;
                tdNom.style.padding = "12px";
                tdNom.style.fontWeight = "500";
                tr.appendChild(tdNom);
                
                const tdType = document.createElement('td');
                tdType.textContent = equipment.type_nom || 'Non défini';
                tdType.style.padding = "12px";
                tr.appendChild(tdType);
                
                const tdStatus = document.createElement('td');
                tdStatus.style.padding = "12px";
                const statuts = {
                    'libre': '<span class="badge badge-success" style="background:#d1fae5; color:#065f46; padding:4px 8px; border-radius:4px;">✅ Disponible</span>',
                    'réserve': '<span class="badge badge-warning" style="background:#fef3c7; color:#92400e; padding:4px 8px; border-radius:4px;">📌 Réservé</span>',
                    'en_maintenance': '<span class="badge badge-danger" style="background:#fee2e2; color:#b91c1c; padding:4px 8px; border-radius:4px;">🔧 En maintenance</span>'
                };
                tdStatus.innerHTML = statuts[equipment.etat] || equipment.etat;
                tr.appendChild(tdStatus);
                
                const tdReservation = document.createElement('td');
                tdReservation.style.padding = "12px";
                if (equipment.current_reservation_id) {
                    const debut = new Date(equipment.reservation_debut).toLocaleDateString('fr-FR');
                    const fin = new Date(equipment.reservation_fin).toLocaleDateString('fr-FR');
                    tdReservation.innerHTML = `
                        <div class="tooltip-info" data-tooltip="${equipment.reserved_by_nom} ${equipment.reserved_by_prenom}">
                            <small>${debut} → ${fin}</small>
                        </div>
                    `;
                } else {
                    tdReservation.innerHTML = '<span style="color: #9ca3af;">-</span>';
                }
                tr.appendChild(tdReservation);
                
                const tdLoc = document.createElement('td');
                tdLoc.style.padding = "12px";
                const loc = equipment.localisation || 'Non définie';
                tdLoc.textContent = loc.length > 25 ? loc.substring(0, 25) + '...' : loc;
                tr.appendChild(tdLoc);
                
                const tdMaint = document.createElement('td');
                tdMaint.style.padding = "12px";
                if (equipment.prochaine_maintenance) {
                    const date = new Date(equipment.prochaine_maintenance);
                    const dateStr = date.toLocaleDateString('fr-FR');
                    const diff = (date.getTime() - new Date().getTime()) / (1000 * 60 * 60 * 24);
                    
                    if (diff < 0) {
                        tdMaint.innerHTML = `<span style="color: #ef4444; font-weight: bold;">⚠️ ${dateStr}</span>`;
                    } else if (diff < 30) {
                        tdMaint.innerHTML = `<span style="color: #f59e0b;">🔔 ${dateStr}</span>`;
                    } else {
                        tdMaint.textContent = dateStr;
                    }
                } else {
                    tdMaint.textContent = 'Non planifiée';
                }
                tr.appendChild(tdMaint);
                
                const tdActions = document.createElement('td');
                tdActions.style.padding = "12px";
                tdActions.innerHTML = `
                    <button class="btn-sm btn-edit" onclick="editEquipment(${equipment.id})" title="Modifier" style="background:none; border:none; cursor:pointer;">✏️</button>
                    <button class="btn-sm btn-warning" onclick="updateStatus(${equipment.id})" title="Changer statut" style="background:none; border:none; cursor:pointer;">🔧</button>
                    <button class="btn-sm btn-primary" onclick="openReservationModal(${equipment.id}, '${equipment.nom}')" title="Réserver" style="background:#4e73df; color:white; border:none; border-radius:4px; padding:5px;">📅</button>
                    <button class="btn-sm btn-info" onclick="viewReservations(${equipment.id})" title="Voir réservations" style="background:#36b9cc; color:white; border:none; border-radius:4px; padding:5px;">📋</button>
                    <button class="btn-sm btn-delete" onclick="deleteEquipment(${equipment.id})" title="Supprimer" style="background:none; border:none; cursor:pointer;">🗑️</button>
                `;
                tr.appendChild(tdActions);
                
                tbody.appendChild(tr);
            });
        }

        // ============================================
        // GESTION DES MODALS
        // ============================================

        function openModal(editMode = false, equipmentId = null) {
            const modal = document.getElementById('equipmentModal');
            const modalTitle = document.getElementById('modalTitle');
            const form = document.getElementById('equipmentForm');
            
            if (editMode && equipmentId) {
                modalTitle.textContent = '✏️ Modifier l\'équipement';
                loadEquipmentData(equipmentId);
            } else {
                modalTitle.textContent = '➕ Ajouter un équipement';
                form.reset();
                document.getElementById('equipmentId').value = '';
            }
            
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeEquipmentModal() {
            const modal = document.getElementById('equipmentModal');
            modal.classList.remove('active');
            document.body.style.overflow = 'auto';
            document.getElementById('equipmentForm').reset();
        }

        function openTypeModal() {
            const modal = document.getElementById('typeEquipmentModal');
            const form = document.getElementById('typeEquipmentForm');
            
            form.reset();
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeTypeModal() {
            const modal = document.getElementById('typeEquipmentModal');
            modal.classList.remove('active');
            document.body.style.overflow = 'auto';
            document.getElementById('typeEquipmentForm').reset();
        }

        function openStatusModal(equipmentId) {
            // Note: Si vous utilisez le modal statut séparé, sinon on peut gérer via le modal edit
            // Pour l'instant, on peut utiliser updateEquipmentStatus via API
            const newStatus = prompt("Nouveau statut (libre, réserve, en_maintenance) ?");
            if(newStatus) {
                // Appel API direct ou modal
                // Ici pour simplifier j'appelle saveStatus si vous avez un modal dédié, 
                // sinon on peut le faire via editEquipment
            }
        }

        function openReservationModal(equipmentId, equipmentName) {
            const idInput = document.getElementById('reservationEquipmentId');
            const nameDisplay = document.getElementById('reservationEquipmentNameDisplay');
            
            if (idInput) idInput.value = equipmentId;
            if (nameDisplay) nameDisplay.textContent = equipmentName;
            
            document.getElementById('conflictInfo').style.display = 'none';
            document.getElementById('reservationForm').reset();
            document.getElementById('forceRequest').value = 'false';
            
            const btnConfirm = document.getElementById('btnConfirmReservation');
            btnConfirm.textContent = '📅 Confirmer la réservation';
            btnConfirm.className = 'btn btn-primary';
            
            const searchFilter = document.getElementById('userSearchFilter');
            if (searchFilter) {
                searchFilter.value = ''; 
            }
            
            loadUsersForReservation();
            
            document.getElementById('reservationModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeReservationModal() {
            document.getElementById('reservationModal').classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        async function openConflictsModal() {
            await loadConflicts();
            renderConflictsList();
            
            document.getElementById('conflictsModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeConflictsModal() {
            document.getElementById('conflictsModal').classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        function renderConflictsList() {
            const container = document.getElementById('conflictsList');
            container.innerHTML = '';

            if (!conflictReservations || conflictReservations.length === 0) {
                container.innerHTML = '<div class="alert alert-info">✅ Aucun conflit en attente</div>';
                return;
            }

            conflictReservations.forEach(conflict => {
                const conflictItem = document.createElement('div');
                conflictItem.className = 'conflict-item';
                
                const debut = new Date(conflict.date_debut).toLocaleDateString('fr-FR');
                const fin = new Date(conflict.date_fin).toLocaleDateString('fr-FR');
                
                conflictItem.innerHTML = `
                    <div style="display: flex; justify-content: space-between; align-items: start;">
                        <div style="flex: 1;">
                            <h3 style="margin: 0 0 10px 0; color: #1f2937;">
                                🔧 ${conflict.equipment_nom}
                            </h3>
                            <p style="margin: 5px 0; color: #4b5563;">
                                <strong>Demandeur:</strong> ${conflict.user_nom} ${conflict.user_prenom}
                            </p>
                            <p style="margin: 5px 0; color: #4b5563;">
                                <strong>Période:</strong> ${debut} → ${fin}
                            </p>
                            <p style="margin: 5px 0; color: #6b7280; font-size: 0.9em;">
                                ${conflict.notes || 'Aucune note'}
                            </p>
                        </div>
                        <div class="conflict-actions">
                            <button class="btn btn-success" onclick="resolveConflict(${conflict.id}, 'accept')" 
                                    title="Accepter la demande">
                                ✅ Accepter
                            </button>
                            <button class="btn btn-danger" onclick="resolveConflict(${conflict.id}, 'reject')" 
                                    title="Rejeter la demande">
                                ❌ Rejeter
                            </button>
                        </div>
                    </div>
                `;
                
                container.appendChild(conflictItem);
            });
        }

        async function resolveConflict(reservationId, decision) {
            const confirmMsg = decision === 'accept' 
                ? 'Accepter cette demande prioritaire ? Les réservations conflictuelles seront annulées.'
                : 'Rejeter cette demande prioritaire ?';
            
            if (!confirm(confirmMsg)) return;

            try {
                const response = await fetch('../controllers/api.php?action=resolveConflict', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        id: reservationId,
                        decision: decision
                    })
                });

                const result = await response.json();

                if (result.success) {
                    showAlert(result.message, 'success');
                    await loadConflicts();
                    renderConflictsList();
                    loadInitialData();
                } else {
                    showAlert(result.message || 'Erreur lors de la résolution', 'error');
                }
            } catch (error) {
                console.error(error);
                showAlert('Erreur serveur', 'error');
            }
        }

        async function viewReservations(equipmentId) {
            try {
                const response = await fetch(`../controllers/api.php?action=getEquipmentReservations&id=${equipmentId}`);
                const result = await response.json();
                
                if (result.success) {
                    currentEquipmentReservations = result.data || [];
                    renderReservationsList(currentEquipmentReservations);
                    
                    document.getElementById('viewReservationsModal').classList.add('active');
                    document.body.style.overflow = 'hidden';
                }
            } catch (error) {
                console.error('Erreur:', error);
                showAlert('Erreur lors du chargement des réservations', 'error');
            }
        }

        function closeViewReservationsModal() {
            document.getElementById('viewReservationsModal').classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        function renderReservationsList(reservations) {
            const container = document.getElementById('reservationsList');
            container.innerHTML = '';

            if (!reservations || reservations.length === 0) {
                container.innerHTML = '<div class="alert alert-info">Aucune réservation pour cet équipement.</div>';
                return;
            }

            const table = document.createElement('table');
            table.className = 'data-table';
            table.style.width = '100%';
            table.innerHTML = `
                <thead>
                    <tr>
                        <th style="padding:10px;">Utilisateur</th>
                        <th style="padding:10px;">Début</th>
                        <th style="padding:10px;">Fin</th>
                        <th style="padding:10px;">Statut</th>
                        <th style="padding:10px;">Action</th>
                    </tr>
                </thead>
                <tbody></tbody>
            `;

            const tbody = table.querySelector('tbody');

            reservations.forEach(res => {
                const tr = document.createElement('tr');
                tr.style.borderBottom = "1px solid #eee";
                
                let statusBadge = '';
                switch(res.statut) {
                    case 'confirmé': statusBadge = '<span class="badge badge-success" style="background:#d1fae5; color:#065f46; padding:4px 8px; border-radius:4px;">Confirmée</span>'; break;
                    case 'en_conflit': statusBadge = '<span class="badge-conflict" style="background:#fef3c7; color:#92400e; padding:4px 8px; border-radius:4px;">⚠️ En conflit</span>'; break;
                    case 'annulé': statusBadge = '<span class="badge badge-danger" style="background:#fee2e2; color:#b91c1c; padding:4px 8px; border-radius:4px;">Annulée</span>'; break;
                    default: statusBadge = res.statut;
                }

                tr.innerHTML = `
                    <td style="padding:10px;">${res.user_nom || 'Inconnu'} ${res.user_prenom || ''}</td>
                    <td style="padding:10px;">${new Date(res.date_debut).toLocaleDateString('fr-FR')}</td>
                    <td style="padding:10px;">${new Date(res.date_fin).toLocaleDateString('fr-FR')}</td>
                    <td style="padding:10px;">${statusBadge}</td>
                    <td style="padding:10px;">
                        ${(res.statut === 'en_attente' || res.statut === 'confirmé') ? 
                        `<button class="btn-sm btn-delete" onclick="cancelReservation(${res.id})" title="Annuler" style="background:none; border:none; cursor:pointer;">❌</button>` : 
                        '-'}
                    </td>
                `;
                tbody.appendChild(tr);
            });

            container.appendChild(table);
        }

        // ============================================
        // ACTIONS CRUD ÉQUIPEMENT
        // ============================================

        function editEquipment(id) {
            openModal(true, id);
        }

        async function saveEquipment() {
            const form = document.getElementById('equipmentForm');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());
            const id = document.getElementById('equipmentId').value;
            
            const action = id ? 'updateEquipment' : 'createEquipment';
            
            if(id) data.id = id;

            try {
                const response = await fetch(`../controllers/api.php?action=${action}${id ? '&id='+id : ''}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    closeEquipmentModal();
                    showAlert('✅ Équipement enregistré avec succès', 'success');
                    loadInitialData();
                } else {
                    showAlert(result.message || 'Erreur lors de l\'enregistrement', 'error', 'alertContainer');
                }
            } catch (error) {
                console.error(error);
                showAlert('Erreur serveur', 'error', 'alertContainer');
            }
        }

        async function loadEquipmentData(id) {
            try {
                const response = await fetch(`../controllers/api.php?action=getEquipment&id=${id}`);
                const result = await response.json();

                if (result.success && result.data) {
                    const eq = result.data;
                    document.getElementById('equipmentId').value = eq.id;
                    document.getElementById('nom').value = eq.nom;
                    document.getElementById('id_type').value = eq.id_type;
                    document.getElementById('etat').value = eq.etat;
                    document.getElementById('description').value = eq.description || '';
                    document.getElementById('localisation').value = eq.localisation || '';
                    document.getElementById('derniere_maintenance').value = eq.derniere_maintenance || '';
                    document.getElementById('prochaine_maintenance').value = eq.prochaine_maintenance || '';
                }
            } catch (error) {
                console.error(error);
                showAlert('Impossible de charger les données', 'error');
            }
        }

        async function deleteEquipment(id) {
            if (!confirm('Êtes-vous sûr de vouloir supprimer cet équipement ?')) return;

            try {
                const response = await fetch(`../controllers/api.php?action=deleteEquipment&id=${id}`, {
                    method: 'POST'
                });
                const result = await response.json();

                if (result.success) {
                    showAlert('🗑️ Équipement supprimé', 'success');
                    loadInitialData();
                } else {
                    showAlert(result.message || 'Impossible de supprimer', 'error');
                }
            } catch (error) {
                console.error(error);
                showAlert('Erreur serveur', 'error');
            }
        }

        // ============================================
        // ACTIONS TYPE D'ÉQUIPEMENT
        // ============================================

        async function saveEquipmentType() {
            const nom = document.getElementById('type_nom').value;
            const icone = document.getElementById('type_icone').value;
            const description = document.getElementById('type_description').value;

            if (!nom || !icone) {
                showAlert('Nom et icône requis', 'error', 'typeAlertContainer');
                return;
            }

            try {
                const response = await fetch('../controllers/api.php?action=createEquipmentType', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ nom, icone, description })
                });

                const result = await response.json();

                if (result.success) {
                    closeTypeModal();
                    showAlert('✅ Type ajouté', 'success');
                    loadTypes();
                } else {
                    showAlert(result.message, 'error', 'typeAlertContainer');
                }
            } catch (error) {
                console.error(error);
                showAlert('Erreur serveur', 'error', 'typeAlertContainer');
            }
        }

        // ============================================
        // ACTIONS STATUT
        // ============================================

        function updateStatus(id) {
            const newStatus = prompt("Nouveau statut (libre, réserve, en_maintenance) ?");
            if (newStatus) {
                // Implémentation rapide via API, ou ouvrir modal dédiée si besoin
                // Ici, assumons que le user entre le texte exact
                fetch('../controllers/api.php?action=updateEquipmentStatus', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id, etat: newStatus })
                })
                .then(r => r.json())
                .then(d => {
                    if(d.success) { showAlert('Statut mis à jour', 'success'); loadInitialData(); }
                    else alert(d.message);
                });
            }
        }

        // ============================================
        // GESTION RÉSERVATIONS
        // ============================================

        document.getElementById('reservation_date_debut').addEventListener('change', checkConflicts);
        document.getElementById('reservation_date_fin').addEventListener('change', checkConflicts);

        async function checkConflicts() {
            const id_equipement = document.getElementById('reservationEquipmentId').value;
            const date_debut = document.getElementById('reservation_date_debut').value;
            const date_fin = document.getElementById('reservation_date_fin').value;
            const conflictDiv = document.getElementById('conflictInfo');
            const conflictDetails = document.getElementById('conflictDetails');

            if (!id_equipement || !date_debut || !date_fin) return;

            if (date_debut > date_fin) {
                conflictDiv.style.display = 'block';
                conflictDetails.innerHTML = 'La date de fin doit être après la date de début.';
                return;
            }

            try {
                const response = await fetch(`../controllers/api.php?action=checkConflicts&id_equipement=${id_equipement}&date_debut=${date_debut}&date_fin=${date_fin}`);
                const result = await response.json();

                if (result.success && result.hasConflict) {
                    conflictDiv.style.display = 'block';
                    conflictDetails.innerHTML = 'Cet équipement est déjà réservé sur cette période.';
                } else {
                    conflictDiv.style.display = 'none';
                }
            } catch (error) {
                console.error(error);
            }
        }

        async function saveReservation() {
            const id_equipement = document.getElementById('reservationEquipmentId').value;
            const id_utilisateur = document.getElementById('reservationUserId').value;
            const date_debut = document.getElementById('reservation_date_debut').value;
            const date_fin = document.getElementById('reservation_date_fin').value;
            const notes = document.getElementById('reservation_notes').value;
            const forceRequest = document.getElementById('forceRequest').value === 'true';

            if (!id_utilisateur) {
                showAlert('Veuillez sélectionner un utilisateur', 'error', 'reservationAlertContainer');
                return;
            }

            if (!date_debut || !date_fin) {
                showAlert('Veuillez sélectionner les dates', 'error', 'reservationAlertContainer');
                return;
            }

            try {
                const response = await fetch('../controllers/api.php?action=createReservation', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        id_equipement,
                        id_utilisateur,
                        date_debut,
                        date_fin,
                        notes,
                        force_request: forceRequest
                    })
                });

                const result = await response.json();

                if (!result.success && result.conflict) {
                    const conflictDiv = document.getElementById('conflictInfo');
                    const conflictDetails = document.getElementById('conflictDetails');
                    
                    conflictDiv.style.display = 'block';
                    conflictDetails.innerHTML = result.message;
                    
                    const btnConfirm = document.getElementById('btnConfirmReservation');
                    btnConfirm.textContent = '⚠️ Envoyer la demande prioritaire';
                    btnConfirm.className = 'btn btn-warning';
                    
                    document.getElementById('forceRequest').value = 'true';
                    
                    showAlert('⚠️ Un conflit a été détecté. Cliquez à nouveau pour envoyer une demande prioritaire.', 'warning', 'reservationAlertContainer');
                    return;
                }

                if (result.success) {
                    closeReservationModal();
                    
                    if (result.statut === 'en_conflit') {
                        showAlert('⚠️ Demande prioritaire envoyée à l\'administration pour arbitrage', 'warning');
                        await loadConflicts();
                    } else {
                        showAlert('✅ ' + result.message, 'success');
                    }
                    
                    loadInitialData();
                } else {
                    const msg = result.errors ? result.errors.join(', ') : result.message;
                    showAlert(msg, 'error', 'reservationAlertContainer');
                }
            } catch (error) {
                console.error(error);
                showAlert('Erreur lors de la réservation', 'error', 'reservationAlertContainer');
            }
        }

        async function cancelReservation(id) {
            if(!confirm("Voulez-vous vraiment annuler cette réservation ?")) return;

            try {
                const response = await fetch(`../controllers/api.php?action=cancelReservation&id=${id}`, { method: 'POST' });
                const result = await response.json();
                
                if (result.success) {
                    // Rafraichir le modal des réservations si ouvert
                    if(document.getElementById('viewReservationsModal').classList.contains('active') && currentEquipmentReservations.length > 0) {
                        viewReservations(currentEquipmentReservations[0].id_equipement); // Hack pour recharger
                    }
                    loadInitialData();
                } else {
                    alert(result.message);
                }
            } catch (e) {
                console.error(e);
            }
        }

        // ============================================
        // FILTRES ET RECHERCHE
        // ============================================

        document.getElementById('searchInput').addEventListener('input', function(e) {
            const term = e.target.value.toLowerCase();
            filterData(term, document.getElementById('filterType').value, document.getElementById('filterStatus').value);
        });

        function filterByType(typeId) {
            filterData(document.getElementById('searchInput').value.toLowerCase(), typeId, document.getElementById('filterStatus').value);
        }

        function filterByStatus(status) {
            filterData(document.getElementById('searchInput').value.toLowerCase(), document.getElementById('filterType').value, status);
        }

        function filterData(searchTerm, typeId, status) {
            const filtered = allEquipments.filter(item => {
                const matchesSearch = item.nom.toLowerCase().includes(searchTerm) || 
                                      (item.localisation && item.localisation.toLowerCase().includes(searchTerm));
                const matchesType = typeId === '' || item.id_type == typeId;
                const matchesStatus = status === '' || item.etat === status;
                
                return matchesSearch && matchesType && matchesStatus;
            });
            
            renderEquipmentTable(filtered);
        }

        function resetFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('filterType').value = '';
            document.getElementById('filterStatus').value = '';
            renderEquipmentTable(allEquipments);
        }

        // ============================================
        // UTILITAIRES
        // ============================================

        function showAlert(message, type = 'success', containerId = null) {
            if (containerId) {
                const container = document.getElementById(containerId);
                const alertClass = type === 'warning' ? 'conflict-info' : `alert alert-${type}`;
                // Style inline pour simuler les classes si bootstrap manquant
                let color = type === 'success' ? '#065f46' : (type === 'error' ? '#b91c1c' : '#92400e');
                let bg = type === 'success' ? '#d1fae5' : (type === 'error' ? '#fee2e2' : '#fef3c7');
                
                container.innerHTML = `<div style="padding:10px; background:${bg}; color:${color}; border-radius:4px; margin-bottom:10px;">${message}</div>`;
                setTimeout(() => { container.innerHTML = ''; }, 5000);
                return;
            }

            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.style.position = 'fixed';
            toast.style.bottom = '20px';
            toast.style.right = '20px';
            toast.style.padding = '15px 25px';
            toast.style.borderRadius = '5px';
            toast.style.color = 'white';
            toast.style.zIndex = '9999';
            toast.style.boxShadow = '0 4px 6px rgba(0,0,0,0.1)';
            
            if (type === 'success') {
                toast.style.backgroundColor = '#10b981';
            } else if (type === 'warning') {
                toast.style.backgroundColor = '#f59e0b';
            } else {
                toast.style.backgroundColor = '#ef4444';
            }
            
            toast.textContent = message;
            document.body.appendChild(toast);

            setTimeout(() => {
                toast.remove();
            }, 3000);
        }
        </script>
        <?php
        return ob_get_clean();
    }
}
?>