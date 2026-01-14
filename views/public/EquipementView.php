<?php
require_once __DIR__ . '/../../views/public/View.php';
require_once __DIR__ . '/../../views/public/components/UIHeader.php';
require_once __DIR__ . '/../../views/public/components/UIFooter.php';

class EquipementView extends View {

  
    public function render() {
        $config = $this->data['config'] ?? [];
        $menuData = $this->data['menu'] ?? [];
        $pageTitle = $this->data['title'] ?? 'Gestion des Équipements';

     
          $customCss = [
            '../../views/landingPage.css',
            '../../views/organigramme.css',
            '../../views/diaporama.css',
            
        ];

        $header = new UIHeader($pageTitle, $config, $menuData, $customCss);
        echo $header->render();

        echo '<main class="main-content" style="margin-left: 0; width: 100%; padding: 20px; box-sizing: border-box;">';
        echo $this->content();
        echo '</main>';

        $footer = new UIFooter($config, $menuData);
        echo $footer->render();
    }

 
    protected function content() {
        extract($this->data);

        ob_start();
        ?>
        
        <style>
            .reservation-badge {
                display: inline-block;
                padding: 3px 8px;
                border-radius: 12px;
                font-size: 0.75em;
                margin-left: 5px;
            }
            .reserved-equipment {
                background-color: #fef3c7 !important;
            }
            .conflict-warning {
                background: #fee2e2;
                border-left: 4px solid #ef4444;
                padding: 10px;
                margin: 10px 0;
                border-radius: 4px;
            }
            .conflict-info {
                background: #fef3c7;
                border-left: 4px solid #f59e0b;
                padding: 10px;
                margin: 10px 0;
                border-radius: 4px;
            }
            .tooltip-info {
                position: relative;
                cursor: help;
            }
            .tooltip-info:hover::after {
                content: attr(data-tooltip);
                position: absolute;
                background: #1f2937;
                color: white;
                padding: 5px 10px;
                border-radius: 4px;
                white-space: nowrap;
                z-index: 1000;
                bottom: 100%;
                left: 50%;
                transform: translateX(-50%);
                font-size: 0.85em;
            }
            .badge-conflict {
                background: #f59e0b;
                color: white;
                padding: 2px 8px;
                border-radius: 12px;
                font-size: 0.75em;
            }
            /* Utilitaires badges */
            .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
            .badge-success { background-color: #d1fae5; color: #065f46; }
            .badge-warning { background-color: #fef3c7; color: #92400e; }
            .badge-danger { background-color: #fee2e2; color: #b91c1c; }
            .badge-info { background-color: #dbeafe; color: #1e40af; }
            
            /* Layout des Modals */
            .modal { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); }
            .modal.active { display: flex; align-items: center; justify-content: center; }
            .modal-content { background-color: #fefefe; padding: 20px; border-radius: 8px; width: 90%; max-height: 90vh; overflow-y: auto; box-shadow: 0 5px 15px rgba(0,0,0,0.3); }
            .modal-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #ddd; padding-bottom: 10px; margin-bottom: 20px; }
            .close-btn { background: none; border: none; font-size: 1.5rem; cursor: pointer; }
            .required { color: red; }
            .form-group { margin-bottom: 15px; }
            .form-control { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
            .modal-footer { border-top: 1px solid #ddd; padding-top: 15px; display: flex; justify-content: flex-end; gap: 10px; }
            
            /* Ajustement Dashboard sans Sidebar */
            .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
            .stat-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); text-align: center; border: 1px solid #eee; }
            .stat-card .number { font-size: 2em; font-weight: bold; margin-top: 10px; }
            .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        </style>

        <div class="equipment-dashboard-container">
            <div class="top-bar">
                <div>
                    <h1>Gestion des Équipements</h1>
                    <p style="color: #666;">Suivi et gestion des ressources matérielles</p>
                </div>
                 </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Équipements</h3>
                    <div class="number" id="totalEquipment">
                        <?php 
                        $total = 0;
                        if(isset($statusStats)) {
                            foreach($statusStats as $s) $total += $s['count'];
                        }
                        echo $total; 
                        ?>
                    </div>
                </div>
                
                <div class="stat-card">
                    <h3>Disponibles</h3>
                    <div class="number" style="color: #10b981;" id="availableCount">0</div>
                </div>
                
                <div class="stat-card">
                    <h3>Réservés</h3>
                    <div class="number" style="color: #f59e0b;" id="reservedCount">0</div>
                </div>
                
                <div class="stat-card">
                    <h3>En Maintenance</h3>
                    <div class="number" style="color: #ef4444;" id="maintenanceCount">0</div>
                </div>
            </div>

            <div class="content-section" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 20px;">
                <div style="display: flex; gap: 15px; margin-bottom: 0; flex-wrap: wrap;">
                    <input type="text" id="searchInput" placeholder="🔍 Rechercher un équipement..." 
                           style="flex: 1; min-width: 250px; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                    
                    <select id="filterType" onchange="filterByType(this.value)" 
                            style="padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                        <option value="">🏷️ Tous les types</option>
                    </select>

                    <select id="filterStatus" onchange="filterByStatus(this.value)" 
                            style="padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                        <option value="">📊 Tous les statuts</option>
                        <option value="libre">✅ Disponible</option>
                        <option value="réservé">📌 Réservé</option>
                        <option value="en_maintenance">🔧 En maintenance</option>
                    </select>

                    <button class="btn btn-secondary" onclick="resetFilters()" style="padding: 10px 15px; cursor: pointer;">
                        🔄 Réinitialiser
                    </button>
                </div>
            </div>
            
            <div class="content-section" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                <h2 style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <span>Liste des Équipements</span>
                    <div style="display: inline-flex; gap: 10px;">
                        <button class="btn btn-secondary" onclick="window.location.href='index.php?route=reservation-history'" style="padding: 8px 15px; cursor: pointer;">
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
                            <tr style="text-align: left; background: #f8f9fa;">
                                <th style="padding: 12px;">ID</th>
                                <th style="padding: 12px;">Nom</th>
                                <th style="padding: 12px;">Type</th>
                                <th style="padding: 12px;">Statut</th>
                                <th style="padding: 12px;">Localisation</th>
                                <th style="padding: 12px;">Prochaine Maintenance</th>
                                <th style="padding: 12px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="equipmentTableBody">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal Réservation avec Gestion de Conflit -->
        <div id="reservationModal" class="modal">
            <div class="modal-content" style="max-width: 600px;">
                <div class="modal-header">
                    <h2>📅 Réserver cet équipement</h2>
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
                            <label for="reservationUserDisplay">Utilisateur bénéficiaire</label>
                            <input type="text" id="reservationUserDisplay" class="form-control" readonly 
                                   style="background-color: #f3f4f6; cursor: not-allowed;"
                                   value="Chargement...">
                            <input type="hidden" id="reservationUserId">
                            <small style="color: #666;">Cette réservation sera faite pour votre compte.</small>
                        </div>

                        <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div class="form-group">
                                <label for="reservation_date_debut">Date début <span class="required">*</span></label>
                                <input type="date" class="form-control" id="reservation_date_debut" required>
                            </div>

                            <div class="form-group">
                                <label for="reservation_date_fin">Date fin <span class="required">*</span></label>
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
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeReservationModal()">Annuler</button>
                    <button type="button" class="btn btn-primary" id="btnConfirmReservation" onclick="saveReservation()">
                        📅 Confirmer la réservation
                    </button>
                </div>
            </div>
        </div>

        <div id="viewReservationsModal" class="modal">
            <div class="modal-content" style="max-width: 900px;">
                <div class="modal-header">
                    <h2>📋 Réservations de l'équipement</h2>
                    <button class="close-btn" onclick="closeViewReservationsModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <div id="reservationsList"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeViewReservationsModal()">Fermer</button>
                </div>
            </div>
        </div>

        <div id="statusModal" class="modal">
            <div class="modal-content" style="max-width: 500px;">
                <div class="modal-header">
                    <h2>Changer le statut</h2>
                    <button class="close-btn" onclick="closeStatusModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="statusEquipmentId">
                    <div id="statusWarning" class="alert alert-warning" style="display:none; color: #856404; background-color: #fff3cd; border-color: #ffeeba; padding: 10px; margin-bottom: 10px;">
                        ⚠️ Attention : Cet équipement a des réservations actives.
                    </div>
                    <div class="form-group">
                        <label for="newStatus">Nouveau Statut</label>
                        <select id="newStatus" class="form-control">
                            <option value="libre">✅ Disponible</option>
                            <option value="en_maintenance">🔧 En maintenance</option>
                            <option value="hors_service">🚫 Hors service</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" onclick="closeStatusModal()">Annuler</button>
                    <button class="btn btn-primary" onclick="saveStatus()">Enregistrer</button>
                </div>
            </div>
        </div>

        <script>
        let allEquipments = [];
        let allTypes = [];
        let currentEquipmentReservations = [];
        let conflictDetected = false;
        
        const CURRENT_USER_ID = <?php echo isset($_SESSION['user_id']) ? json_encode($_SESSION['user_id']) : 'null'; ?>;
        const CURRENT_USER_NAME = "<?php echo isset($_SESSION['nom']) && isset($_SESSION['prenom']) ? htmlspecialchars($_SESSION['nom'] . ' ' . $_SESSION['prenom']) : 'Utilisateur'; ?>";

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
            
            const userDisplay = document.getElementById('reservationUserDisplay');
            const userId = document.getElementById('reservationUserId');
            
            if (userDisplay && CURRENT_USER_ID) {
                userDisplay.value = CURRENT_USER_NAME;
                userId.value = CURRENT_USER_ID;
            }
        });

        async function loadInitialData() {
            try {
                await loadTypes();
                await loadStats();
                await loadEquipments();
                console.log('✅ Données chargées avec succès');
            } catch (error) {
                console.error('❌ Erreur lors du chargement initial:', error);
                showAlert('❌ Erreur lors du chargement des données', 'error');
            }
        }

        async function loadTypes() {
            try {
                const response = await fetch('../../controllers/api.php?action=getEquipmentTypes');
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
                }
            } catch (error) {
                console.error('❌ Erreur chargement types:', error);
            }
        }

        async function loadStats() {
            try {
                const response = await fetch('../../controllers/api.php?action=getEquipmentStats');
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
                            case 'libre': available = parseInt(stat.count) || 0; break;
                            case 'réservé': reserved = parseInt(stat.count) || 0; break;
                            case 'en_maintenance': en_maintenance = parseInt(stat.count) || 0; break;
                        }
                    });
                    
                    const elTotal = document.getElementById('totalEquipment');
                    const elAvail = document.getElementById('availableCount');
                    const elRes = document.getElementById('reservedCount');
                    const elMaint = document.getElementById('maintenanceCount');

                    if(elTotal) elTotal.textContent = total;
                    if(elAvail) elAvail.textContent = available;
                    if(elRes) elRes.textContent = reserved;
                    if(elMaint) elMaint.textContent = en_maintenance;
                }
            } catch (error) {
                console.error('❌ Erreur chargement stats:', error);
            }
        }

        async function loadEquipments() {
            try {
                const response = await fetch('../../controllers/api.php?action=getEquipments');
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
                tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 20px;">Aucun équipement trouvé</td></tr>';
                return;
            }
            
            equipments.forEach(equipment => {
                const tr = document.createElement('tr');
                tr.style.borderBottom = '1px solid #eee';
                
                if (equipment.reservation_statut) {
                    tr.classList.add('reserved-equipment');
                }
                
                const tdId = document.createElement('td'); tdId.style.padding = '10px'; tdId.textContent = equipment.id; tr.appendChild(tdId);
                const tdNom = document.createElement('td'); tdNom.style.padding = '10px'; const nom = equipment.nom || ''; tdNom.textContent = nom.length > 30 ? nom.substring(0, 30) + '...' : nom; tr.appendChild(tdNom);
                const tdType = document.createElement('td'); tdType.style.padding = '10px'; tdType.textContent = equipment.type_nom || 'Non défini'; tr.appendChild(tdType);
                
                const tdStatus = document.createElement('td'); tdStatus.style.padding = '10px';
                const statuts = {
                    'libre': '<span class="badge badge-success">✅ Disponible</span>',
                    'réservé': '<span class="badge badge-warning">📌 Réservé</span>',
                    'en_maintenance': '<span class="badge badge-danger">🔧 En maintenance</span>',
                    'hors_service': '<span class="badge badge-danger">🚫 Hors service</span>'
                };
                tdStatus.innerHTML = statuts[equipment.etat] || equipment.etat;
                tr.appendChild(tdStatus);
                
                const tdLoc = document.createElement('td'); tdLoc.style.padding = '10px'; const loc = equipment.localisation || 'Non définie'; tdLoc.textContent = loc.length > 25 ? loc.substring(0, 25) + '...' : loc; tr.appendChild(tdLoc);
                
                const tdMaint = document.createElement('td'); tdMaint.style.padding = '10px';
                if (equipment.prochaine_maintenance) {
                    const date = new Date(equipment.prochaine_maintenance);
                    const dateStr = date.toLocaleDateString('fr-FR');
                    const diff = (date.getTime() - new Date().getTime()) / (1000 * 60 * 60 * 24);
                    if (diff < 0) tdMaint.innerHTML = `<span style="color: #ef4444; font-weight: bold;">⚠️ ${dateStr}</span>`;
                    else if (diff < 30) tdMaint.innerHTML = `<span style="color: #f59e0b;">🔔 ${dateStr}</span>`;
                    else tdMaint.textContent = dateStr;
                } else tdMaint.textContent = 'Non planifiée';
                tr.appendChild(tdMaint);
                
                const tdActions = document.createElement('td'); tdActions.style.padding = '10px';
                tdActions.innerHTML = `
                    <button class="btn btn-sm btn-primary" style="margin-right:5px; cursor:pointer; padding: 4px 8px;" onclick="openReservationModal(${equipment.id}, '${equipment.nom.replace(/'/g, "\\'")}')" title="Réserver">📅</button>
                    <button class="btn btn-sm btn-info" style="cursor:pointer; padding: 4px 8px;" onclick="viewReservations(${equipment.id})" title="Voir réservations">📋</button>`;
                tr.appendChild(tdActions);
                
                tbody.appendChild(tr);
            });
        }

      

        function openStatusModal(equipmentId) {
            document.getElementById('statusEquipmentId').value = equipmentId;
            const equipment = allEquipments.find(eq => eq.id == equipmentId);
            const warning = document.getElementById('statusWarning');
            if (equipment && equipment.current_reservation_id) warning.style.display = 'block';
            else warning.style.display = 'none';
            document.getElementById('statusModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeStatusModal() {
            document.getElementById('statusModal').classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        function openReservationModal(equipmentId, equipmentName) {
            const idInput = document.getElementById('reservationEquipmentId');
            const nameDisplay = document.getElementById('reservationEquipmentNameDisplay');
            
            if (idInput) idInput.value = equipmentId;
            if (nameDisplay) nameDisplay.textContent = equipmentName;
            
            document.getElementById('conflictInfo').style.display = 'none';
            document.getElementById('reservationForm').reset();
            document.getElementById('forceRequest').value = 'false';
            conflictDetected = false;
            
            const btnConfirm = document.getElementById('btnConfirmReservation');
            btnConfirm.textContent = '📅 Confirmer la réservation';
            btnConfirm.className = 'btn btn-primary';
            
            const userDisplay = document.getElementById('reservationUserDisplay');
            const userId = document.getElementById('reservationUserId');
            if (userDisplay && CURRENT_USER_ID) {
                userDisplay.value = CURRENT_USER_NAME;
                userId.value = CURRENT_USER_ID;
            }
            
            document.getElementById('reservationModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeReservationModal() {
            document.getElementById('reservationModal').classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        async function viewReservations(equipmentId) {
            try {
                const response = await fetch(`../../controllers/api.php?action=getEquipmentReservations&id=${equipmentId}`);
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
                    <tr style="background:#f8f9fa;">
                        <th style="padding:8px;">Utilisateur</th>
                        <th style="padding:8px;">Début</th>
                        <th style="padding:8px;">Fin</th>
                        <th style="padding:8px;">Statut</th>
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
                    case 'confirmé': statusBadge = '<span class="badge badge-success">Confirmée</span>'; break;
                    case 'en_conflit': statusBadge = '<span class="badge-conflict">⚠️ En conflit</span>'; break;
                    case 'annulé': statusBadge = '<span class="badge badge-danger">Annulée</span>'; break;
                    default: statusBadge = `<span class="badge badge-info">${res.statut}</span>`;
                }
                tr.innerHTML = `
                    <td style="padding:8px;">${res.user_nom || 'Inconnu'} ${res.user_prenom || ''}</td>
                    <td style="padding:8px;">${new Date(res.date_debut).toLocaleDateString('fr-FR')}</td>
                    <td style="padding:8px;">${new Date(res.date_fin).toLocaleDateString('fr-FR')}</td>
                    <td style="padding:8px;">${statusBadge}</td>
                `;
                tbody.appendChild(tr);
            });
            container.appendChild(table);
        }

      

        function updateStatus(id) {
            openStatusModal(id);
        }

        async function saveStatus() {
            const id = document.getElementById('statusEquipmentId').value;
            const newStatus = document.getElementById('newStatus').value;
            try {
                const response = await fetch('../../controllers/api.php?action=updateEquipmentStatus', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id, etat: newStatus })
                });
                const result = await response.json();
                if (result.success) {
                    closeStatusModal();
                    showAlert('✅ Statut mis à jour', 'success');
                    loadInitialData();
                } else alert(result.message);
            } catch (error) {
                console.error(error);
                alert('Erreur lors de la mise à jour');
            }
        }

     

        const inputDateDebut = document.getElementById('reservation_date_debut');
        const inputDateFin = document.getElementById('reservation_date_fin');
        if(inputDateDebut) inputDateDebut.addEventListener('change', checkConflicts);
        if(inputDateFin) inputDateFin.addEventListener('change', checkConflicts);

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
                const response = await fetch(`../../controllers/api.php?action=checkConflicts&id_equipement=${id_equipement}&date_debut=${date_debut}&date_fin=${date_fin}`);
                const result = await response.json();
                if (result.success && result.hasConflict) {
                    conflictDiv.style.display = 'block';
                    conflictDetails.innerHTML = 'Cet équipement est déjà réservé sur cette période.';
                    conflictDetected = true;
                } else {
                    conflictDiv.style.display = 'none';
                    conflictDetected = false;
                }
            } catch (error) { console.error(error); }
        }

        async function saveReservation() {
            const id_equipement = document.getElementById('reservationEquipmentId').value;
            const id_utilisateur = document.getElementById('reservationUserId').value;
            const date_debut = document.getElementById('reservation_date_debut').value;
            const date_fin = document.getElementById('reservation_date_fin').value;
            const notes = document.getElementById('reservation_notes').value;
            const forceRequest = document.getElementById('forceRequest').value === 'true';

            if (!id_utilisateur) { showAlert('Erreur session utilisateur', 'error', 'reservationAlertContainer'); return; }
            if (!date_debut || !date_fin) { showAlert('Veuillez sélectionner les dates', 'error', 'reservationAlertContainer'); return; }

            try {
                const response = await fetch('../../controllers/api.php?action=createReservation', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        id_equipement, id_utilisateur, date_debut, date_fin, notes, force_request: forceRequest
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
                    if (result.statut === 'en_conflit') showAlert('⚠️ Demande prioritaire envoyée à l\'administration pour arbitrage', 'warning');
                    else showAlert('✅ ' + result.message, 'success');
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

        

        const searchInput = document.getElementById('searchInput');
        if(searchInput) {
            searchInput.addEventListener('input', function(e) {
                const term = e.target.value.toLowerCase();
                filterData(term, document.getElementById('filterType').value, document.getElementById('filterStatus').value);
            });
        }

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


        function showAlert(message, type = 'success', containerId = null) {
            if (containerId) {
                const container = document.getElementById(containerId);
                const alertClass = type === 'warning' ? 'conflict-info' : `alert alert-${type}`;
                container.innerHTML = `<div class="${alertClass}" style="padding:10px; margin-bottom:10px; border-radius:4px; ${type=='error'?'background:#fee2e2;color:#b91c1c;':'background:#d1fae5;color:#065f46;'}">${message}</div>`;
                setTimeout(() => { container.innerHTML = ''; }, 5000);
                return;
            }

            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.style.position = 'fixed'; toast.style.bottom = '20px'; toast.style.right = '20px';
            toast.style.padding = '15px 25px'; toast.style.borderRadius = '5px'; toast.style.color = 'white';
            toast.style.zIndex = '9999'; toast.style.boxShadow = '0 4px 6px rgba(0,0,0,0.1)';
            
            if (type === 'success') toast.style.backgroundColor = '#10b981';
            else if (type === 'warning') toast.style.backgroundColor = '#f59e0b';
            else toast.style.backgroundColor = '#ef4444';
            
            toast.textContent = message;
            document.body.appendChild(toast);
            setTimeout(() => { toast.remove(); }, 3000);
        }
        </script>
        <?php
        return ob_get_clean();
    }
}
?>