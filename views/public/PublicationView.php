<?php
require_once __DIR__ . '/../../views/Sidebar.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Publications - Laboratoire</title>
    <link rel="stylesheet" href="../admin_dashboard.css">
    <link rel="stylesheet" href="../modelAddUser.css">
    <link rel="stylesheet" href="../teamManagement.css">
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>📚 Administration</h2>
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
                <h1>Gestion des Publications</h1>
                <p style="color: #666;">Base documentaire et validation des publications scientifiques</p>
            </div>
            <a href="../logout.php" class="logout-btn">Déconnexion</a>
        </div>
        
        <!-- Statistiques -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Publications</h3>
                <div class="number" id="totalPublications">0</div>
            </div>
            
            <div class="stat-card">
                <h3>Validées</h3>
                <div class="number" style="color: #10b981;" id="validatedCount">0</div>
            </div>
            
            <div class="stat-card">
                <h3>En Attente</h3>
                <div class="number" style="color: #f59e0b;" id="pendingCount">0</div>
            </div>
            
            <div class="stat-card">
                <h3>Cette Année</h3>
                <div class="number" style="color: #3b82f6;" id="currentYearCount">0</div>
            </div>
        </div>

        <!-- Filtres et recherche -->
        <div class="content-section">
            <div style="display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap;">
                <input type="text" id="searchInput" placeholder="🔍 Rechercher un titre, auteur, domaine..." 
                       style="flex: 1; min-width: 250px; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                
                <select id="filterType" onchange="applyFilters()" 
                        style="padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                    <option value="">📄 Tous les types</option>
                    <option value="article">📰 Article</option>
                    <option value="rapport">📊 Rapport</option>
                    <option value="these">🎓 Thèse</option>
                    <option value="communication">🎤 Communication</option>
                </select>

                <select id="filterStatus" onchange="applyFilters()" 
                        style="padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                    <option value="">📊 Tous les statuts</option>
                    <option value="en_attente">⏳ En attente</option>
                    <option value="valide">✅ Validée</option>
                    <option value="rejete">❌ Rejetée</option>
                </select>

                <select id="filterDomain" onchange="applyFilters()" 
                        style="padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                    <option value="">🔬 Tous les domaines</option>
                </select>

                <select id="filterYear" onchange="applyFilters()" 
                        style="padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                    <option value="">📅 Toutes les années</option>
                </select>

                <button class="btn btn-secondary" onclick="resetFilters()" style="padding: 10px 15px;">
                    🔄 Réinitialiser
                </button>

                
            </div>
        </div>
        
        <!-- Tableau des publications -->
        <div class="content-section">
            <h2>
                <span>Liste des Publications</span>
                <button class="btn btn-primary" onclick="openModal()">
                    ➕ Ajouter une publication
                </button>
            </h2>
            
            <div id="loadingSpinner" style="text-align: center; padding: 40px;">
                <p>⏳ Chargement des publications...</p>
            </div>
            
            <div id="publicationTableContainer" style="display: none;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Titre</th>
                            <th>Type</th>
                            <th>Auteur(s)</th>
                            <th>Domaine</th>
                            <th>Date Publication</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="publicationTableBody">
                    </tbody>
                </table>
                
                <!-- Pagination -->
                <div id="paginationContainer" style="margin-top: 20px; text-align: center;">
                </div>
            </div>
        </div>

       
   

  <script>// ============================================
// VARIABLES GLOBALES
// ============================================
let allPublications = [];
let currentPage = 1;
let perPage = 10;
let totalPages = 1;
let currentFilters = {};
let typeChart = null;
let domainChart = null;

// ============================================
// CHARGEMENT INITIAL
// ============================================

document.addEventListener('DOMContentLoaded', () => {
    loadInitialData();
    setupSearchDebounce();
});

async function loadInitialData() {
    try {
        await loadStats();
        await loadPublications();
        await loadFilterOptions();
        
        console.log('✅ Données chargées avec succès');
    } catch (error) {
        console.error('❌ Erreur lors du chargement initial:', error);
        showAlert('❌ Erreur lors du chargement des données', 'error');
    }
}

async function loadStats() {
    try {
        const response = await fetch('../../controllers/api.php?action=getPublicationStats');
        const result = await response.json();
        
        if (result.success) {
            document.getElementById('totalPublications').textContent = result.total || 0;
            document.getElementById('validatedCount').textContent = result.valide || 0;
            document.getElementById('pendingCount').textContent = result.en_attente || 0;
            
            const currentYear = new Date().getFullYear();
            const currentYearCount = result.par_annee?.[currentYear] || 0;
            document.getElementById('currentYearCount').textContent = currentYearCount;
        }
    } catch (error) {
        console.error('❌ Erreur chargement stats:', error);
    }
}

async function loadPublications(page = 1) {
    try {
        console.log('📥 Chargement des publications, page:', page);
        
        let url = `../../controllers/api.php?action=getPublications&page=${page}&perPage=${perPage}`;
        
        // Ajouter les filtres
        if (currentFilters.type) url += `&type=${currentFilters.type}`;
        if (currentFilters.statut) url += `&statut=${currentFilters.statut}`;
        if (currentFilters.domaine) url += `&domaine=${currentFilters.domaine}`;
        if (currentFilters.year) url += `&year=${currentFilters.year}`;
        if (currentFilters.search) url += `&q=${encodeURIComponent(currentFilters.search)}`;
        
        const response = await fetch(url);
        const result = await response.json();
        
        console.log('📦 Résultat:', result);

        if (result.success && result.data) {
            allPublications = result.data;
            totalPages = result.totalPages || 1;
            currentPage = page;
            
            renderPublicationTable(allPublications);
            renderPagination();
            
            document.getElementById('loadingSpinner').style.display = 'none';
            document.getElementById('publicationTableContainer').style.display = 'block';
            
            console.log('✅ Tableau rempli avec', allPublications.length, 'publications');
        } else {
            throw new Error(result.message || 'Erreur lors du chargement');
        }
    } catch (error) {
        console.error('❌ Erreur:', error);
        document.getElementById('loadingSpinner').innerHTML = 
            '<p style="color: red;">❌ Erreur lors du chargement des publications</p>';
    }
}

async function loadFilterOptions() {
    try {
        // Charger les domaines distincts
        const domainsResponse = await fetch('../../controllers/api.php?action=getPublicationsByDomain');
        const domainsResult = await domainsResponse.json();
        
        if (domainsResult.success && domainsResult.data) {
            const filterDomain = document.getElementById('filterDomain');
            filterDomain.innerHTML = '<option value="">🔬 Tous les domaines</option>';
            
            domainsResult.data.forEach(domain => {
                if (domain.domaine) {
                    const option = document.createElement('option');
                    option.value = domain.domaine;
                    option.textContent = domain.domaine;
                    filterDomain.appendChild(option);
                }
            });
        }
        
        // Charger les années distinctes
        const yearsResponse = await fetch('../../controllers/api.php?action=getDistinctYears');
        const yearsResult = await yearsResponse.json();
        
        if (yearsResult.success && yearsResult.data) {
            const filterYear = document.getElementById('filterYear');
            
            filterYear.innerHTML = '<option value="">📅 Toutes les années</option>';
            
            yearsResult.data.forEach(year => {
                if (year.year) {
                    const option1 = document.createElement('option');
                    option1.value = year.year;
                    option1.textContent = year.year;
                    filterYear.appendChild(option1);
                    
                    const option2 = document.createElement('option');
                    option2.value = year.year;
                    option2.textContent = year.year;
                }
            });
        }
    } catch (error) {
        console.error('❌ Erreur chargement options filtres:', error);
    }
}

function renderPublicationTable(publications) {
    const tbody = document.getElementById('publicationTableBody');
    tbody.innerHTML = '';
    
    if (!publications || publications.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 20px;">Aucune publication trouvée</td></tr>';
        return;
    }
    
    publications.forEach(pub => {
        const tr = document.createElement('tr');
        
        // ID
        const tdId = document.createElement('td');
        tdId.textContent = pub.id;
        tr.appendChild(tdId);
        
        // Titre
        const tdTitre = document.createElement('td');
        const titre = pub.titre || '';
        tdTitre.textContent = titre.length > 50 ? titre.substring(0, 50) + '...' : titre;
        tdTitre.title = titre;
        tr.appendChild(tdTitre);
        
        // Type
        const tdType = document.createElement('td');
        const types = {
            'article': '📰 Article',
            'rapport': '📊 Rapport',
            'these': '🎓 Thèse',
            'communication': '🎤 Communication'
        };
        tdType.textContent = types[pub.type] || pub.type;
        tr.appendChild(tdType);
        
        // Auteur(s) - À implémenter avec table auteurs
        const tdAuteurs = document.createElement('td');
        tdAuteurs.textContent = pub.auteurs || 'Non renseigné';
        tr.appendChild(tdAuteurs);
        
        // Domaine
        const tdDomaine = document.createElement('td');
        tdDomaine.textContent = pub.domaine || 'Non défini';
        tr.appendChild(tdDomaine);
        
        // Date publication
        const tdDate = document.createElement('td');
        if (pub.date_publication) {
            const date = new Date(pub.date_publication);
            tdDate.textContent = date.toLocaleDateString('fr-FR');
        } else {
            tdDate.textContent = 'Non définie';
        }
        tr.appendChild(tdDate);
        
        // Statut
        const tdStatut = document.createElement('td');
        const statuts = {
            'en_attente': '<span class="badge badge-warning">⏳ En attente</span>',
            'valide': '<span class="badge badge-success">✅ Validée</span>',
            'rejete': '<span class="badge badge-danger">❌ Rejetée</span>'
        };
        tdStatut.innerHTML = statuts[pub.statut_validation] || pub.statut_validation;
        tr.appendChild(tdStatut);
        
        // Actions
        const tdActions = document.createElement('td');
        let actionsHTML = `
            <button class="btn-sm btn-view" onclick="viewPublication(${pub.id})" title="Voir">👁️</button>
        `;
        
      
        
        tdActions.innerHTML = actionsHTML;
        tr.appendChild(tdActions);
        
        tbody.appendChild(tr);
    });
}

function renderPagination() {
    const container = document.getElementById('paginationContainer');
    
    if (totalPages <= 1) {
        container.innerHTML = '';
        return;
    }
    
    let html = '<div style="display: flex; gap: 5px; justify-content: center; align-items: center;">';
    
    // Bouton précédent
    if (currentPage > 1) {
        html += `<button class="btn btn-secondary" onclick="loadPublications(${currentPage - 1})">« Précédent</button>`;
    }
    
    // Numéros de page
    for (let i = 1; i <= totalPages; i++) {
        if (i === currentPage) {
            html += `<button class="btn btn-primary" disabled>${i}</button>`;
        } else if (i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
            html += `<button class="btn btn-secondary" onclick="loadPublications(${i})">${i}</button>`;
        } else if (i === currentPage - 3 || i === currentPage + 3) {
            html += `<span>...</span>`;
        }
    }
    
    // Bouton suivant
    if (currentPage < totalPages) {
        html += `<button class="btn btn-secondary" onclick="loadPublications(${currentPage + 1})">Suivant »</button>`;
    }
    
    html += '</div>';
    container.innerHTML = html;
}

// ============================================
// GESTION DES MODALS
// ============================================



async function loadPublicationData(id) {
    try {
        const response = await fetch(`../../controllers/api.php?action=getPublication&id=${id}`);
        const result = await response.json();

        if (result.success && result.data) {
            const pub = result.data;
            
            document.getElementById('publicationId').value = pub.id || '';
            document.getElementById('titre').value = pub.titre || '';
            document.getElementById('resume').value = pub.resume || '';
            document.getElementById('type').value = pub.type || '';
            document.getElementById('domaine').value = pub.domaine || '';
            document.getElementById('date_publication').value = pub.date_publication || '';
            document.getElementById('doi').value = pub.doi || '';
        } else {
            showAlert('❌ ' + (result.message || 'Impossible de charger les données'), 'error');
        }
    } catch (error) {
        console.error('❌ Erreur:', error);
        showAlert('❌ Impossible de charger les données', 'error');
    }
}




function viewPublication(id) {
    window.location.href = `../publication-details.php?id=${id}`;
}



// ============================================
// RECHERCHE ET FILTRAGE
// ============================================

function setupSearchDebounce() {
    let timeout;
    document.getElementById('searchInput')?.addEventListener('input', function(e) {
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            currentFilters.search = e.target.value;
            loadPublications(1);
        }, 500);
    });
}

function applyFilters() {
    currentFilters.type = document.getElementById('filterType').value;
    currentFilters.statut = document.getElementById('filterStatus').value;
    currentFilters.domaine = document.getElementById('filterDomain').value;
    currentFilters.year = document.getElementById('filterYear').value;
    
    loadPublications(1);
}

function resetFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('filterType').value = '';
    document.getElementById('filterStatus').value = '';
    document.getElementById('filterDomain').value = '';
    document.getElementById('filterYear').value = '';
    
    currentFilters = {};
    loadPublications(1);
}
function showAlert(message, type = 'info') {
    const container = document.getElementById('alertContainer');
    
    if (!container) {
        // Créer un container temporaire en haut de la page
        const tempContainer = document.createElement('div');
        tempContainer.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 10000;';
        document.body.appendChild(tempContainer);
        
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type}`;
        alertDiv.style.cssText = 'padding: 15px; border-radius: 8px; margin-bottom: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);';
        
        const colors = {
            'success': '#10b981',
            'error': '#ef4444',
            'warning': '#f59e0b',
            'info': '#3b82f6'
        };
        
        alertDiv.style.background = colors[type] || colors.info;
        alertDiv.style.color = 'white';
        alertDiv.innerHTML = message;
        
        tempContainer.appendChild(alertDiv);
        
        setTimeout(() => {
            alertDiv.remove();
            if (tempContainer.children.length === 0) {
                tempContainer.remove();
            }
        }, 5000);
        
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




    </script>
</body>
</html>