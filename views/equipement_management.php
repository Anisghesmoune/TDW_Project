<?php
require_once '../config/Database.php';
require_once '../models/Model.php';
require_once '../models/equipementModel.php';
require_once '../models/equipementType.php';
require_once '../controllers/equipementController.php';
require_once '../views/Sidebar.php';

// Juste instancier le contrôleur et appeler index
$controller = new EquipmentController();
$controller->index();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Équipements - Laboratoire</title>
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
                <button class="btn btn-primary" onclick="openModal()">
                    ➕ Ajouter un équipement
                </button>
                <button class="btn btn-primary" onclick="openTypeModal()">
                    ➕ Ajouter un type
                </button>
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
    
    <!-- Modal Ajouter/Modifier Équipement -->
    <div id="equipmentModal" class="modal">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h2 id="modalTitle">➕ Ajouter un équipement</h2>
                <button class="close-btn" onclick="closeEquipmentModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="equipmentForm">
                    <div id="alertContainer"></div>

                    <input type="hidden" id="equipmentId" name="id">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nom">Nom de l'équipement <span class="required">*</span></label>
                            <input type="text" class="form-control" id="nom" name="nom" required 
                                   placeholder="Ex: Ordinateur portable Dell XPS 15">
                        </div>
                    </div>

                    <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label for="id_type">Type d'équipement <span class="required">*</span></label>
                            <select class="form-control" id="id_type" name="id_type" required>
                                <option value="">-- Sélectionner un type --</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="etat">Statut <span class="required">*</span></label>
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
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeEquipmentModal()">Annuler</button>
                <button type="button" class="btn btn-primary" onclick="saveEquipment()">💾 Enregistrer</button>
            </div>
        </div>
    </div>
    
    <!-- Modal Ajouter Type d'Équipement -->
    <div id="typeEquipmentModal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h2 id="modalTitleType">➕ Ajouter un type d'équipement</h2>
                <button class="close-btn" onclick="closeTypeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="typeEquipmentForm">
                    <div id="typeAlertContainer"></div>
                    
                    <div class="form-group">
                        <label for="type_nom">Nom du type <span class="required">*</span></label>
                        <input type="text" class="form-control" id="type_nom" name="nom" required 
                               placeholder="Ex: Ordinateur, Caméra, Projecteur">
                    </div>

                    <div class="form-group">
                        <label for="type_description">Description</label>
                        <textarea class="form-control" id="type_description" name="description" rows="3" 
                                  placeholder="Description du type d'équipement..."></textarea>
                    </div>

                    <div class="form-group">
                        <label for="type_icone">Icône (emoji) <span class="required">*</span></label>
                        <input type="text" class="form-control" id="type_icone" name="icone" required
                               placeholder="Ex: 💻, 📷, 📊" maxlength="10">
                        <small style="color: #666;">Utilisez un emoji pour représenter ce type</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeTypeModal()">Annuler</button>
                <button type="button" class="btn btn-primary" onclick="saveEquipmentType()">💾 Enregistrer</button>
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
                <div class="form-group">
                    <label for="newStatus">Nouveau statut <span class="required">*</span></label>
                    <select class="form-control" id="newStatus" required>
                        <option value="libre">✅ Disponible</option>
                        <option value="réserve">📌 Réservé</option>
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

    <script>
// ============================================
// VARIABLES GLOBALES
// ============================================
let allEquipments = [];
let allTypes = [];

// ============================================
// CHARGEMENT INITIAL
// ============================================

document.addEventListener('DOMContentLoaded', () => {
    loadInitialData();
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
        const response = await fetch('../controllers/api.php?action=getEquipmentTypes');
        const result = await response.json();
        
        if (result.success && result.data) {
            allTypes = result.data;
            
            // Remplir le select de filtrage
            const filterSelect = document.getElementById('filterType');
            filterSelect.innerHTML = '<option value="">🏷️ Tous les types</option>';
            
            allTypes.forEach(type => {
                const option = document.createElement('option');
                option.value = type.id;
                option.textContent = `${type.icone || ''} ${type.nom} (${type.equipment_count || 0})`;
                filterSelect.appendChild(option);
            });
            
            // Remplir le select du formulaire
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
            const typeStats = result.typeStats || [];   
            const maintenanceNeeded = await getMaintenanceNeeded();
            
            let total = 0;
            let available = 0;
            let reserved = 0;
            
            statusStats.forEach(stat => {
                total += parseInt(stat.count) || 0;
                
                switch (stat.etat) {
                    case 'libre':
                        available = parseInt(stat.count) || 0;
                        break;
                    case 'réservé':
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

async function getMaintenanceNeeded() {
    try {
        const response = await fetch('../controllers/api.php?action=getMaintenanceNeeded&days=30');
        const result = await response.json();
        return result.success ? (result.data || []).length : 0;
    } catch (error) {
        return 0;
    }
}

async function loadEquipments() {
    try {
        console.log('📥 Chargement des équipements');
        
        const response = await fetch('../controllers/api.php?action=getEquipments');
        const result = await response.json();
        
        console.log('📦 Résultat:', result);

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
        tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 20px;">Aucun équipement trouvé</td></tr>';
        return;
    }
    
    equipments.forEach(equipment => {
        const tr = document.createElement('tr');
        
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
            <button class="btn-sm btn-edit" onclick="editEquipment(${equipment.id})" title="Modifier">✏️</button>
            <button class="btn-sm btn-warning" onclick="updateStatus(${equipment.id})" title="Changer statut">🔧</button>
            <button class="btn-sm btn-delete" onclick="deleteEquipment(${equipment.id})" title="Supprimer">🗑️</button>
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
    clearAlerts();
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
    document.getElementById('statusEquipmentId').value = equipmentId;
    document.getElementById('statusModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeStatusModal() {
    document.getElementById('statusModal').classList.remove('active');
    document.body.style.overflow = 'auto';
}

document.querySelectorAll('.modal').forEach(modal => {
    modal.addEventListener('click', function(e) {
        if (e.target === this) {
            closeEquipmentModal();
            closeTypeModal();
            closeStatusModal();
        }
    });
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeEquipmentModal();
        closeTypeModal();
        closeStatusModal();
    }
});

// ============================================
// CRUD ÉQUIPEMENTS
// ============================================

function saveEquipment() {
    const form = document.getElementById('equipmentForm');
    const formData = new FormData(form);
    
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const data = Object.fromEntries(formData);
    
    if (data.description === '') data.description = null;
    if (data.localisation === '') data.localisation = null;
    if (data.derniere_maintenance === '') data.derniere_maintenance = null;
    if (data.prochaine_maintenance === '') data.prochaine_maintenance = null;
    
    const equipmentId = document.getElementById('equipmentId').value;
    const action = equipmentId ? 'updateEquipment' : 'createEquipment';
    const url = equipmentId 
        ? `../controllers/api.php?action=${action}&id=${equipmentId}`
        : `../controllers/api.php?action=${action}`;
    
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
    .then(response => response.json())
    .then(result => {
        saveBtn.disabled = false;
        saveBtn.textContent = '💾 Enregistrer';
        
        if (result.success) {
            showAlert('✅ ' + result.message, 'success');
            closeEquipmentModal();
            setTimeout(() => {
                loadEquipments();
                loadStats();
            }, 1200);
        } else {
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
        saveBtn.disabled = false;
        saveBtn.textContent = '💾 Enregistrer';
        showAlert('❌ Erreur de connexion au serveur', 'error');
    });
}

function saveEquipmentType() {
    const form = document.getElementById('typeEquipmentForm');
    const formData = new FormData(form);
    
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const data = Object.fromEntries(formData);
    
    if (data.description === '') data.description = null;
    
    const saveBtn = event.target;
    saveBtn.disabled = true;
    saveBtn.textContent = '⏳ Enregistrement...';
    
    fetch('../controllers/api.php?action=createEquipmentType', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        saveBtn.disabled = false;
        saveBtn.textContent = '💾 Enregistrer';
        
        if (result.success) {
            showAlert('✅ ' + result.message, 'success');
            closeTypeModal();
            setTimeout(() => {
                loadTypes();
                loadEquipments();
            }, 1200);
        } else {
            showAlert('❌ ' + (result.message || 'Erreur lors de la création'), 'error');
        }
    })
    .catch(error => {
        console.error('❌ Erreur:', error);
        saveBtn.disabled = false;
        saveBtn.textContent = '💾 Enregistrer';
        showAlert('❌ Erreur de connexion au serveur', 'error');
    });
}

async function loadEquipmentData(id) {
    try {
        const response = await fetch(`../controllers/api.php?action=getEquipment&id=${id}`);
        const result = await response.json();

        if (result.success && result.data) {
            const equipment = result.data;
            
            document.getElementById('equipmentId').value = equipment.id || '';
            document.getElementById('nom').value = equipment.nom || '';
            document.getElementById('id_type').value = equipment.id_type || '';
            document.getElementById('etat').value = equipment.etat || 'libre';
            document.getElementById('description').value = equipment.description || '';
            document.getElementById('localisation').value = equipment.localisation || '';
            document.getElementById('derniere_maintenance').value = equipment.derniere_maintenance || '';
            document.getElementById('prochaine_maintenance').value = equipment.prochaine_maintenance || '';
        } else {
            showAlert('❌ ' + (result.message || 'Impossible de charger les données'), 'error');
        }
    } catch (error) {
        console.error('❌ Erreur:', error);
        showAlert('❌ Impossible de charger les données', 'error');
    }
}

function deleteEquipment(id) {
    if (!confirm('⚠️ Êtes-vous sûr de vouloir supprimer cet équipement ?\n\nCette action est irréversible.')) {
        return;
    }
    
    fetch(`../controllers/api.php?action=deleteEquipment&id=${id}`, {
        method: 'POST'
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showAlert('✅ ' + result.message, 'success');
            setTimeout(() => {
                loadEquipments();
                loadStats();
            }, 1200);
        } else {
            showAlert('❌ ' + (result.message || 'Erreur lors de la suppression'), 'error');
        }
    })
    .catch(error => {
        console.error('❌ Erreur:', error);
        showAlert('❌ Erreur de connexion', 'error');
    });
}

function viewEquipment(id) {
    window.location.href = `equipment-details.php?id=${id}`;
}

function editEquipment(id) {
    openModal(true, id);
}

function updateStatus(id) {
    openStatusModal(id);
}

function saveStatus() {
    const id = document.getElementById('statusEquipmentId').value;
    const etat = document.getElementById('newStatus').value;
    
    fetch(`../controllers/api.php?action=updateEquipmentStatus`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ id: id, etat: etat })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showAlert('✅ Statut mis à jour', 'success');
            closeStatusModal();
            setTimeout(() => {
                loadEquipments();
                loadStats();
            }, 1200);
        } else {
            showAlert('❌ ' + result.message, 'error');
        }
    })
    .catch(error => {
        console.error('❌ Erreur:', error);
        showAlert('❌ Erreur de connexion', 'error');
    });
}

// ============================================
// RECHERCHE ET FILTRAGE
// ============================================

document.getElementById('searchInput')?.addEventListener('input', function(e) {
    const keyword = e.target.value.toLowerCase();
    filterEquipments(keyword);
});

function filterEquipments(keyword) {
    const filtered = allEquipments.filter(eq => {
        return (eq.nom && eq.nom.toLowerCase().includes(keyword)) ||
               (eq.type_nom && eq.type_nom.toLowerCase().includes(keyword)) ||
               (eq.localisation && eq.localisation.toLowerCase().includes(keyword));
    });
    renderEquipmentTable(filtered);
}

function filterByType(typeId) {
    if (!typeId) {
        renderEquipmentTable(allEquipments);
        return;
    }
    
    const filtered = allEquipments.filter(eq => eq.id_type == typeId);
    renderEquipmentTable(filtered);
}

function filterByStatus(status) {
    if (!status) {
        renderEquipmentTable(allEquipments);
        return;
    }
    
    const filtered = allEquipments.filter(eq => eq.etat === status);
    renderEquipmentTable(filtered);
}

function resetFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('filterType').value = '';
    document.getElementById('filterStatus').value = '';
    renderEquipmentTable(allEquipments);
}

// ============================================
// ALERTES
// ============================================

function showAlert(message, type = 'info') {
    const container = document.getElementById('alertContainer');
    
    if (!container) {
        alert(message);
        return;
    }
    
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} show`;
    alertDiv.innerHTML = message;
    
    container.appendChild(alertDiv);
    
    setTimeout(() => alertDiv.classList.add('visible'), 10);
    
    setTimeout(() => {
        alertDiv.classList.remove('visible');
        setTimeout(() => alertDiv.remove(), 300);
    }, 5000);
}

function clearAlerts() {
    const container = document.getElementById('alertContainer');
    if (container) {
        container.innerHTML = '';
    }
}

// ============================================
// VALIDATION
// ============================================

document.getElementById('prochaine_maintenance')?.addEventListener('change', function() {
    const derniere = document.getElementById('derniere_maintenance').value;
    const prochaine = this.value;
    
    if (derniere && prochaine && prochaine < derniere) {
        showAlert('⚠️ La prochaine maintenance ne peut pas être avant la dernière', 'error');
        this.value = '';
    }
});
    </script>
</body>
</html>