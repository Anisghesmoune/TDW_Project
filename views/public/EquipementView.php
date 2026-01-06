<?php
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../models/Model.php';
require_once __DIR__ . '/../../models/equipementModel.php';
require_once __DIR__ . '/../../models/equipementType.php';
require_once __DIR__ . '/../../models/reservationModel.php';
require_once __DIR__ . '/../../controllers/equipementController.php';
require_once __DIR__ . '/../../views/Sidebar.php';

$controller = new EquipmentController();
$controller->index();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Équipements - Laboratoire</title>
    <link rel="stylesheet" href="../admin_dashboard.css">
    <link rel="stylesheet" href="../modelAddUser.css">
    <link rel="stylesheet" href="../teamManagement.css">
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
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>⚙️ Administration</h2>
            <span class="admin-badge">utilisateur</span>
        </div>
       <?php 
       $sidebar = new Sidebar("admin");
       $sidebar->render(); 
       ?>
    </div>
    
    <div class="main-content">
        <div class="top-bar">
            <div>
                <h1>Gestion des Équipements</h1>
                <p style="color: #666;">Suivi et gestion des ressources matérielles</p>
            </div>
            <a href="../logout.php" class="logout-btn">Déconnexion</a>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Équipements</h3>
                <div class="number" id="totalEquipment">0</div>
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

        <!-- Filtres et recherche -->
        <div class="content-section">
            <div style="display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap;">
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
                    <option value="réserve">📌 Réservé</option>
                    <option value="en_maintenance">🔧 En maintenance</option>
                </select>

                <button class="btn btn-secondary" onclick="resetFilters()" style="padding: 10px 15px;">
                    🔄 Réinitialiser
                </button>
            </div>
        </div>
        
        <div class="content-section">
            <h2>
                <span>Liste des Équipements</span>
                <div style="display: inline-flex; gap: 10px;">
                   
                    <button class="btn btn-secondary" onclick="window.location.href='../reservation-history.php'">
                        📜 Historique
                    </button>
                </div>
            </h2>
            
            <div id="loadingSpinner" style="text-align: center; padding: 40px;">
                <p>⏳ Chargement des équipements...</p>
            </div>
            
            <div id="equipmentTableContainer" style="display: none;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Type</th>
                            <th>Statut</th>
                            <th>Réservation Actuelle</th>
                            <th>Localisation</th>
                            <th>Prochaine Maintenance</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="equipmentTableBody">
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
  
   

    <!-- Modal Changement de Statut -->
    <div id="statusModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h2>🔧 Modifier le statut</h2>
                <button class="close-btn" onclick="closeStatusModal()">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="statusEquipmentId">
                <div id="statusWarning" style="display: none;" class="conflict-warning">
                    ⚠️ <strong>Attention :</strong> Cet équipement est actuellement réservé.
                </div>
                <div class="form-group">
                    <label for="newStatus">Nouveau statut <span class="required">*</span></label>
                    <select class="form-control" id="newStatus" required>
                        <option value="libre">✅ Disponible</option>
                        <option value="en_maintenance">🔧 En maintenance</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeStatusModal()">Annuler</button>
                <button type="button" class="btn btn-primary" onclick="saveStatus()">💾 Enregistrer</button>
            </div>
        </div>
    </div>

    <!-- Modal Réservation Rapide -->
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
                    
                    <!-- Info Équipement -->
                    <div class="form-group" style="margin-bottom: 20px; background: #f3f4f6; padding: 10px; border-radius: 5px;">
                        <label style="font-weight: bold; color: #4b5563;">Équipement concerné :</label>
                        <div id="reservationEquipmentNameDisplay" style="font-size: 1.1em; color: #1f2937;"></div>
                    </div>

                    <!-- NOUVEAU : Sélection de l'utilisateur avec recherche -->
                    <div class="form-group">
                        <label for="reservationUserId">Utilisateur bénéficiaire <span class="required">*</span></label>
                        <div style="display: flex; gap: 10px;">
                            <!-- Barre de recherche pour filtrer le select -->
                            <input type="text" id="userSearchFilter" placeholder="🔍 Filtrer par nom..." 
                                   class="form-control" style="width: 40%;">
                            
                            <!-- Select contenant les utilisateurs -->
                            <select id="reservationUserId" class="form-control" required style="width: 60%;">
                                <option value="">-- Chargement... --</option>
                            </select>
                        </div>
                        <small style="color: #666;">Sélectionnez la personne pour qui vous faites la réservation.</small>
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

                    <div id="conflictInfo" style="display: none;" class="conflict-warning">
                        <strong>⚠️ Conflit détecté</strong>
                        <div id="conflictDetails"></div>
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
                <button type="button" class="btn btn-primary" onclick="saveReservation()">📅 Confirmer la réservation</button>
            </div>
        </div>
    </div>

    <!-- Modal Voir Réservations -->
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

    <script>
// ============================================
// VARIABLES GLOBALES
// ============================================
let allEquipments = [];
let allTypes = [];
let currentEquipmentReservations = [];


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
    loadUsersForReservation() ;
});

// Variable pour stocker les utilisateurs en cache
let cachedUsers = [];



// Fonction pour charger les utilisateurs
async function loadUsersForReservation() {
    const userSelect = document.getElementById('reservationUserId');
    if (!userSelect) return;

    // Si on a déjà chargé les utilisateurs, on ne refait pas l'appel
    if (cachedUsers.length > 0) {
        populateUserSelect(cachedUsers);
        return;
    }

    try {
        userSelect.innerHTML = '<option value="">Chargement...</option>';
        
        const response = await fetch('../../controllers/api.php?action=getUsers');
        const result = await response.json();

        console.log("Réponse API Users :", result); // Regardez la console (F12) pour voir la structure !

        // Logique pour trouver le tableau, peu importe la structure de l'API
        let usersArray = [];

        if (Array.isArray(result)) {
            // Cas 1 : L'API renvoie directement [ {...}, {...} ]
            usersArray = result;
        } else if (result.data && Array.isArray(result.data)) {
            // Cas 2 : L'API renvoie { success: true, data: [ ... ] }
            usersArray = result.data;
        } else if (result.users && Array.isArray(result.users)) {
             // Cas 3 : L'API renvoie { success: true, users: [ ... ] }
            usersArray = result.users;
        }

        // Vérification finale avant d'appeler populateUserSelect
        if (Array.isArray(usersArray) && usersArray.length > 0) {
            cachedUsers = usersArray;
            populateUserSelect(cachedUsers);
        } else {
            console.warn("Aucun tableau d'utilisateurs trouvé dans la réponse API");
            userSelect.innerHTML = '<option value="">Aucun utilisateur trouvé</option>';
        }

    } catch (error) {
        console.error('Erreur chargement utilisateurs:', error);
        userSelect.innerHTML = '<option value="">Erreur connexion</option>';
    }
}

// Fonction pour remplir le select
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

// --- INITIALISATION SÉCURISÉE ---
document.addEventListener('DOMContentLoaded', () => {
    
    // 1. Gestionnaire de recherche (Filtre)
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
            
            // Sélection auto du premier élément visible
            const select = document.getElementById('reservationUserId');
            if (select.selectedOptions[0].style.display === 'none') {
                select.value = "";
            }
        });
    }

    // 2. Initialiser les dates min
    setMinDates();
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
        console.log('📥 Chargement des équipements');
        
        const response = await fetch('../../controllers/api.php?action=getEquipments');
        const result = await response.json();

        if (result.success && result.data) {
            allEquipments = result.data;
            renderEquipmentTable(allEquipments);
            
            document.getElementById('loadingSpinner').style.display = 'none';
            document.getElementById('equipmentTableContainer').style.display = 'block';
            
            console.log('✅ Tableau rempli avec', allEquipments.length, 'équipements');
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
        
        // Highlight if reserved
        if (equipment.reservation_statut) {
            tr.classList.add('reserved-equipment');
        }
        
        // ID
        const tdId = document.createElement('td');
        tdId.textContent = equipment.id;
        tr.appendChild(tdId);
        
        // Nom
        const tdNom = document.createElement('td');
        const nom = equipment.nom || '';
        tdNom.textContent = nom.length > 30 ? nom.substring(0, 30) + '...' : nom;
        tr.appendChild(tdNom);
        
        // Type
        const tdType = document.createElement('td');
        tdType.textContent = equipment.type_nom || 'Non défini';
        tr.appendChild(tdType);
        
        // Statut
        const tdStatus = document.createElement('td');
        const statuts = {
            'libre': '<span class="badge badge-success">✅ Disponible</span>',
            'réserve': '<span class="badge badge-warning">📌 Réservé</span>',
            'en_maintenance': '<span class="badge badge-danger">🔧 En maintenance</span>'
        };
        tdStatus.innerHTML = statuts[equipment.etat] || equipment.etat;
        tr.appendChild(tdStatus);
        
        // Réservation actuelle
        const tdReservation = document.createElement('td');
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
        
        // Localisation
        const tdLoc = document.createElement('td');
        const loc = equipment.localisation || 'Non définie';
        tdLoc.textContent = loc.length > 25 ? loc.substring(0, 25) + '...' : loc;
        tr.appendChild(tdLoc);
        
        // Prochaine maintenance
        const tdMaint = document.createElement('td');
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
        
        // Actions
        const tdActions = document.createElement('td');
        tdActions.innerHTML = `
            <button class="btn-sm btn-view" onclick="viewEquipment(${equipment.id})" title="Voir">👁️</button>
            <button class="btn-sm btn-warning" onclick="updateStatus(${equipment.id})" title="Changer statut">🔧</button>
            <button class="btn-sm btn-primary" onclick="openReservationModal(${equipment.id}, '${equipment.nom}')" title="Réserver">📅</button>
            <button class="btn-sm btn-info" onclick="viewReservations(${equipment.id})" title="Voir réservations">📋</button>`;
        tr.appendChild(tdActions);
        
        tbody.appendChild(tr);
    });
}

// ============================================
// GESTION DES MODALS
// ============================================






async function openStatusModal(equipmentId) {
    document.getElementById('statusEquipmentId').value = equipmentId;
    
    // Check if equipment has active reservations
    const equipment = allEquipments.find(eq => eq.id == equipmentId);
    const warning = document.getElementById('statusWarning');
    
    if (equipment && equipment.current_reservation_id) {
        warning.style.display = 'block';
    } else {
        warning.style.display = 'none';
    }
    
    document.getElementById('statusModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeStatusModal() {
    document.getElementById('statusModal').classList.remove('active');
    document.body.style.overflow = 'auto';
}

function openReservationModal(equipmentId, equipmentName) {
    // 1. Affectation des valeurs
    const idInput = document.getElementById('reservationEquipmentId');
    const nameDisplay = document.getElementById('reservationEquipmentNameDisplay');
    
    if (idInput) idInput.value = equipmentId;
    if (nameDisplay) nameDisplay.textContent = equipmentName; // Utilise textContent pour le div
    
    // 2. Réinitialisation
    document.getElementById('conflictInfo').style.display = 'none';
    document.getElementById('reservationForm').reset();
    
    // 3. Reset du filtre de recherche (C'est ici que votre erreur se produisait probablement)
    const searchFilter = document.getElementById('userSearchFilter');
    if (searchFilter) {
        searchFilter.value = ''; 
    }
    
    // 4. Chargement des utilisateurs
    loadUsersForReservation();
    
    // 5. Affichage
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
    table.innerHTML = `
        <thead>
            <tr>
                <th>Utilisateur</th>
                <th>Début</th>
                <th>Fin</th>
                <th>Statut</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody></tbody>
    `;

    const tbody = table.querySelector('tbody');

    reservations.forEach(res => {
        const tr = document.createElement('tr');
        
        // Statut avec couleur
        let statusBadge = '';
        switch(res.statut) {
            case 'confirmée': statusBadge = '<span class="badge badge-success">Confirmée</span>'; break;
            case 'en_attente': statusBadge = '<span class="badge badge-warning">En attente</span>'; break;
            case 'annulée': statusBadge = '<span class="badge badge-danger">Annulée</span>'; break;
            case 'terminée': statusBadge = '<span class="badge badge-secondary">Terminée</span>'; break;
            default: statusBadge = res.statut;
        }

        tr.innerHTML = `
            <td>${res.user_nom || 'Inconnu'} ${res.user_prenom || ''}</td>
            <td>${new Date(res.date_debut).toLocaleDateString('fr-FR')}</td>
            <td>${new Date(res.date_fin).toLocaleDateString('fr-FR')}</td>
            <td>${statusBadge}</td>
            <td>
                ${(res.statut === 'en_attente' || res.statut === 'confirmée') ? 
                `<button class="btn-sm btn-delete" onclick="cancelReservation(${res.id})" title="Annuler">❌</button>` : 
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



async function loadEquipmentData(id) {
    try {
        const response = await fetch(`../../controllers/api.php?action=getEquipment&id=${id}`);
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


// ============================================
// ACTIONS STATUT
// ============================================

function updateStatus(id) {
    openStatusModal(id);
}

async function saveStatus() {
    const id = document.getElementById('statusEquipmentId').value;
    const newStatus = document.getElementById('newStatus').value;

    try {
        // Route correspondante dans le controller: updateEquipmentStatus
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
        } else {
            alert(result.message);
        }
    } catch (error) {
        console.error(error);
        alert('Erreur lors de la mise à jour');
    }
}

// ============================================
// GESTION RÉSERVATIONS
// ============================================

// Vérification des conflits lors du changement de date
document.getElementById('reservation_date_debut').addEventListener('change', checkConflicts);
document.getElementById('reservation_date_fin').addEventListener('change', checkConflicts);

async function checkConflicts() {
    const id_equipement = document.getElementById('reservationEquipmentId').value;
    const date_debut = document.getElementById('reservation_date_debut').value;
    const date_fin = document.getElementById('reservation_date_fin').value;
    const conflictDiv = document.getElementById('conflictInfo');
    const conflictDetails = document.getElementById('conflictDetails');

    if (!id_equipement || !date_debut || !date_fin) return;

    // Validation basique date
    if (date_debut > date_fin) {
        conflictDiv.style.display = 'block';
        conflictDiv.innerHTML = 'La date de fin doit être après la date de début.';
        return;
    }

    try {
        const response = await fetch(`../../controllers/api.php?action=checkConflicts&id_equipement=${id_equipement}&date_debut=${date_debut}&date_fin=${date_fin}`);
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
    const date_debut = document.getElementById('reservation_date_debut').value;
    const date_fin = document.getElementById('reservation_date_fin').value;
    const notes = document.getElementById('reservation_notes').value;
    
    // ID Utilisateur courant (injecté via PHP dans le footer ou disponible globalement)
    const id_utilisateur = <?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'null'; ?>;

    if (!id_utilisateur) {
        showAlert('Erreur session utilisateur', 'error', 'reservationAlertContainer');
        return;
    }

    if (!date_debut || !date_fin) {
        showAlert('Veuillez sélectionner les dates', 'error', 'reservationAlertContainer');
        return;
    }

    try {
        // Route du ReservationController
        const response = await fetch('/../../controllers/api.php?action=createReservation', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id_equipement,
                id_utilisateur,
                date_debut,
                date_fin,
                notes
            })
        });

        const result = await response.json();

        if (result.success) {
            closeReservationModal();
            showAlert('📅 Réservation créée avec succès', 'success');
            loadInitialData();
        } else {
            // Afficher les erreurs détaillées si présentes
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
        const response = await fetch(`../../controllers/api.php?action=cancelReservation&id=${id}`, { method: 'POST' });
        const result = await response.json();
        
        if (result.success) {
            // Rafraîchir la liste des réservations dans le modal
            const eqId = currentEquipmentReservations.length > 0 ? currentEquipmentReservations[0].id_equipement : null;
            if(eqId) viewReservations(eqId);
            loadInitialData(); // Mettre à jour le tableau principal
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
    // Si un container spécifique est donné (ex: dans un modal), on l'utilise
    // Sinon on crée une notification toast globale
    
    if (containerId) {
        const container = document.getElementById(containerId);
        container.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
        setTimeout(() => { container.innerHTML = ''; }, 5000);
        return;
    }

    // Toast Notification logic (création dynamique)
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
    toast.style.backgroundColor = type === 'success' ? '#10b981' : '#ef4444';
    toast.textContent = message;

    document.body.appendChild(toast);

    setTimeout(() => {
        toast.remove();
    }, 3000);
}

function clearAlerts() {
    document.getElementById('alertContainer').innerHTML = '';
    document.getElementById('typeAlertContainer').innerHTML = '';
    document.getElementById('reservationAlertContainer').innerHTML = '';
}
    </script>
</body>
</html>