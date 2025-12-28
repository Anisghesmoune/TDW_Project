<?php
require_once '../views/Sidebar.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Publications - Laboratoire</title>
    <link rel="stylesheet" href="admin_dashboard.css">
    <link rel="stylesheet" href="modelAddUser.css">
    <link rel="stylesheet" href="teamManagement.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
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

                <button class="btn btn-primary" onclick="generateReport()" style="padding: 10px 15px;">
                    📑 Rapport Bibliographique
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

        <!-- Statistiques par type et domaine -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
            <div class="content-section">
                <h2>📊 Publications par Type</h2>
                <canvas id="typeChart" style="max-height: 300px;"></canvas>
            </div>
            
            <div class="content-section">
                <h2>🔬 Publications par Domaine</h2>
                <canvas id="domainChart" style="max-height: 300px;"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Modal Ajouter/Modifier Publication -->
    <div id="publicationModal" class="modal">
        <div class="modal-content" style="max-width: 900px;">
            <div class="modal-header">
                <h2 id="modalTitle">➕ Ajouter une publication</h2>
                <button class="close-btn" onclick="closePublicationModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="publicationForm" enctype="multipart/form-data">
                    <div id="alertContainer"></div>

                    <input type="hidden" id="publicationId" name="id">
                    
                    <div class="form-group">
                        <label for="titre">Titre de la publication <span class="required">*</span></label>
                        <input type="text" class="form-control" id="titre" name="titre" required 
                               placeholder="Ex: Deep Learning for Medical Image Analysis">
                    </div>

                    <div class="form-group">
                        <label for="resume">Résumé</label>
                        <textarea class="form-control" id="resume" name="resume" rows="4" 
                                  placeholder="Résumé de la publication..."></textarea>
                    </div>

                    <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label for="type">Type de publication <span class="required">*</span></label>
                            <select class="form-control" id="type" name="type" required>
                                <option value="">-- Sélectionner --</option>
                                <option value="article">📰 Article</option>
                                <option value="rapport">📊 Rapport</option>
                                <option value="these">🎓 Thèse</option>
                                <option value="communication">🎤 Communication</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="domaine">Domaine</label>
                            <input type="text" class="form-control" id="domaine" name="domaine" 
                                   placeholder="Ex: Intelligence Artificielle">
                        </div>
                    </div>

                    <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label for="date_publication">Date de publication</label>
                            <input type="date" class="form-control" id="date_publication" name="date_publication">
                        </div>

                        <div class="form-group">
                            <label for="doi">DOI</label>
                            <input type="text" class="form-control" id="doi" name="doi" 
                                   placeholder="Ex: 10.1234/example.2024">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="fichier">Fichier PDF</label>
                        <input type="file" class="form-control" id="fichier" name="fichier" accept=".pdf">
                        <small style="color: #666;">Format PDF uniquement, max 10MB</small>
                    </div>

                    <div class="form-group">
                        <label for="auteurs">Auteurs (à développer)</label>
                        <input type="text" class="form-control" id="auteurs" name="auteurs" 
                               placeholder="Nom des auteurs séparés par des virgules">
                    </div>

                    <div class="form-group">
                        <label for="projet">Projet associé (à développer)</label>
                        <select class="form-control" id="projet" name="projet">
                            <option value="">-- Aucun projet --</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closePublicationModal()">Annuler</button>
                <button type="button" class="btn btn-primary" onclick="savePublication()">💾 Enregistrer</button>
            </div>
        </div>
    </div>

    <!-- Modal Validation -->
    <div id="validationModal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h2>✅ Validation de la publication</h2>
                <button class="close-btn" onclick="closeValidationModal()">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="validationPublicationId">
                
                <div id="publicationDetails" style="background: #f3f4f6; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <!-- Les détails seront chargés dynamiquement -->
                </div>

                <div class="form-group">
                    <label>Action <span class="required">*</span></label>
                    <div style="display: flex; gap: 10px; margin-top: 10px;">
                        <button class="btn btn-primary" onclick="validatePublication()" style="flex: 1;">
                            ✅ Valider
                        </button>
                        <button class="btn btn-secondary" onclick="rejectPublication()" style="flex: 1; background: #ef4444;">
                            ❌ Rejeter
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeValidationModal()">Fermer</button>
            </div>
        </div>
    </div>

    <!-- Modal Rapport Bibliographique -->
    <div id="reportModal" class="modal">
        <div class="modal-content" style="max-width: 700px;">
            <div class="modal-header">
                <h2>📑 Génération de rapport bibliographique</h2>
                <button class="close-btn" onclick="closeReportModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Type de rapport</label>
                    <select class="form-control" id="reportType">
                        <option value="year">Par année</option>
                        <option value="author">Par auteur</option>
                        <option value="domain">Par domaine</option>
                        <option value="type">Par type</option>
                    </select>
                </div>

                <div class="form-group" id="yearSelection">
                    <label>Année</label>
                    <select class="form-control" id="reportYear">
                        <option value="">Toutes les années</option>
                    </select>
                </div>

                <div class="form-group" id="authorSelection" style="display: none;">
                    <label>Auteur</label>
                    <select class="form-control" id="reportAuthor">
                        <option value="">Tous les auteurs</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Format</label>
                    <select class="form-control" id="reportFormat">
                        <option value="pdf">PDF</option>
                        <option value="excel">Excel</option>
                        <option value="html">HTML</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeReportModal()">Annuler</button>
                <button type="button" class="btn btn-primary" onclick="downloadReport()">📥 Générer</button>
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
    setupReportTypeChange();
});

async function loadInitialData() {
    try {
        await loadStats();
        await loadPublications();
        await loadFilterOptions();
        await loadCharts();
        
        console.log('✅ Données chargées avec succès');
    } catch (error) {
        console.error('❌ Erreur lors du chargement initial:', error);
        showAlert('❌ Erreur lors du chargement des données', 'error');
    }
}

async function loadStats() {
    try {
        const response = await fetch('../controllers/api.php?action=getPublicationStats');
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
        
        let url = `../controllers/api.php?action=getPublications&page=${page}&perPage=${perPage}`;
        
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
        const domainsResponse = await fetch('../controllers/api.php?action=getPublicationsByDomain');
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
        const yearsResponse = await fetch('../controllers/api.php?action=getDistinctYears');
        const yearsResult = await yearsResponse.json();
        
        if (yearsResult.success && yearsResult.data) {
            const filterYear = document.getElementById('filterYear');
            const reportYear = document.getElementById('reportYear');
            
            filterYear.innerHTML = '<option value="">📅 Toutes les années</option>';
            reportYear.innerHTML = '<option value="">Toutes les années</option>';
            
            yearsResult.data.forEach(year => {
                if (year.year) {
                    const option1 = document.createElement('option');
                    option1.value = year.year;
                    option1.textContent = year.year;
                    filterYear.appendChild(option1);
                    
                    const option2 = document.createElement('option');
                    option2.value = year.year;
                    option2.textContent = year.year;
                    reportYear.appendChild(option2);
                }
            });
        }
    } catch (error) {
        console.error('❌ Erreur chargement options filtres:', error);
    }
}

async function loadCharts() {
    try {
        const response = await fetch('../controllers/api.php?action=getPublicationStats');
        const result = await response.json();
        
        if (result.success) {
            // Chart par type
            if (result.par_type && result.par_type.length > 0) {
                const typeCtx = document.getElementById('typeChart').getContext('2d');
                
                if (typeChart) typeChart.destroy();
                
                typeChart = new Chart(typeCtx, {
                    type: 'pie',
                    data: {
                        labels: result.par_type.map(t => {
                            const labels = {
                                'article': '📰 Article',
                                'rapport': '📊 Rapport',
                                'these': '🎓 Thèse',
                                'communication': '🎤 Communication'
                            };
                            return labels[t.type] || t.type;
                        }),
                        datasets: [{
                            data: result.par_type.map(t => t.total),
                            backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444']
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            }
            
            // Chart par domaine
            if (result.par_domaine && result.par_domaine.length > 0) {
                const domainCtx = document.getElementById('domainChart').getContext('2d');
                
                if (domainChart) domainChart.destroy();
                
                domainChart = new Chart(domainCtx, {
                    type: 'bar',
                    data: {
                        labels: result.par_domaine.map(d => d.domaine || 'Non défini'),
                        datasets: [{
                            label: 'Nombre de publications',
                            data: result.par_domaine.map(d => d.total),
                            backgroundColor: '#3b82f6'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            }
                        }
                    }
                });
            }
        }
    } catch (error) {
        console.error('❌ Erreur chargement charts:', error);
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
            <button class="btn-sm btn-edit" onclick="editPublication(${pub.id})" title="Modifier">✏️</button>
        `;
        
        // Actions admin
        if (pub.statut_validation === 'en_attente') {
            actionsHTML += `
                <button class="btn-sm btn-primary" onclick="openValidationModal(${pub.id})" title="Valider">✅</button>
            `;
        }
        
        if (pub.lien_telechargement) {
            actionsHTML += `
                <button class="btn-sm btn-secondary" onclick="downloadPublication(${pub.id})" title="Télécharger">📥</button>
            `;
        }
        
        actionsHTML += `
            <button class="btn-sm btn-delete" onclick="deletePublication(${pub.id})" title="Supprimer">🗑️</button>
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

function openModal(editMode = false, publicationId = null) {
    const modal = document.getElementById('publicationModal');
    const modalTitle = document.getElementById('modalTitle');
    const form = document.getElementById('publicationForm');
    
    if (editMode && publicationId) {
        modalTitle.textContent = '✏️ Modifier la publication';
        loadPublicationData(publicationId);
    } else {
        modalTitle.textContent = '➕ Ajouter une publication';
        form.reset();
        document.getElementById('publicationId').value = '';
    }
    
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closePublicationModal() {
    const modal = document.getElementById('publicationModal');
    modal.classList.remove('active');
    document.body.style.overflow = 'auto';
    document.getElementById('publicationForm').reset();
    clearAlerts();
}

function openValidationModal(publicationId) {
    const modal = document.getElementById('validationModal');
    document.getElementById('validationPublicationId').value = publicationId;
    
    // Charger les détails de la publication
    const pub = allPublications.find(p => p.id == publicationId);
    if (pub) {
        const detailsDiv = document.getElementById('publicationDetails');
        detailsDiv.innerHTML = `
            <h3 style="margin-top: 0;">${pub.titre}</h3>
            <p><strong>Type:</strong> ${pub.type}</p>
            <p><strong>Domaine:</strong> ${pub.domaine || 'Non défini'}</p>
            <p><strong>Date de publication:</strong> ${pub.date_publication || 'Non définie'}</p>
            <p><strong>Résumé:</strong> ${pub.resume || 'Aucun résumé'}</p>
            <p><strong>Soumis le:</strong> ${new Date(pub.date_soumission).toLocaleString('fr-FR')}</p>
        `;
    }
    
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeValidationModal() {
    const modal = document.getElementById('validationModal');
    modal.classList.remove('active');
    document.body.style.overflow = 'auto';
}

function openReportModal() {
    const modal = document.getElementById('reportModal');
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeReportModal() {
    const modal = document.getElementById('reportModal');
    modal.classList.remove('active');
    document.body.style.overflow = 'auto';
}

// Fermeture modals sur clic extérieur
document.querySelectorAll('.modal').forEach(modal => {
    modal.addEventListener('click', function(e) {
        if (e.target === this) {
            closePublicationModal();
            closeValidationModal();
            closeReportModal();
        }
    });
});

// Fermeture modals sur Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closePublicationModal();
        closeValidationModal();
        closeReportModal();
    }
});

// ============================================
// CRUD PUBLICATIONS
// ============================================

async function savePublication() {
    const form = document.getElementById('publicationForm');
    const formData = new FormData(form);
    
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const publicationId = document.getElementById('publicationId').value;
    const action = publicationId ? 'updatePublication' : 'createPublication';
    const url = `../controllers/api.php?action=${action}${publicationId ? '&id=' + publicationId : ''}`;
    
    const saveBtn = event.target;
    saveBtn.disabled = true;
    saveBtn.textContent = '⏳ Enregistrement...';
    
    try {
        const response = await fetch(url, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        saveBtn.disabled = false;
        saveBtn.textContent = '💾 Enregistrer';
        
        if (result.success) {
            showAlert('✅ ' + result.message, 'success');
            closePublicationModal();
            setTimeout(() => {
                loadPublications(currentPage);
                loadStats();
                loadCharts();
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
    } catch (error) {
        console.error('❌ Erreur:', error);
        saveBtn.disabled = false;
        saveBtn.textContent = '💾 Enregistrer';
        showAlert('❌ Erreur de connexion au serveur', 'error');
    }
}

async function loadPublicationData(id) {
    try {
        const response = await fetch(`../controllers/api.php?action=getPublication&id=${id}`);
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

async function validatePublication() {
    const id = document.getElementById('validationPublicationId').value;
    
    if (!confirm('Confirmer la validation de cette publication ?')) {
        return;
    }
    
    try {
        const response = await fetch(`../controllers/api.php?action=validatePublication&id=${id}`, {
            method: 'POST'
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert('✅ Publication validée avec succès', 'success');
            closeValidationModal();
            setTimeout(() => {
                loadPublications(currentPage);
                loadStats();
            }, 1200);
        } else {
            showAlert('❌ ' + result.message, 'error');
        }
    } catch (error) {
        console.error('❌ Erreur:', error);
        showAlert('❌ Erreur de connexion', 'error');
    }
}

async function rejectPublication() {
    const id = document.getElementById('validationPublicationId').value;
    
    if (!confirm('⚠️ Confirmer le rejet de cette publication ?')) {
        return;
    }
    
    try {
        const response = await fetch(`../controllers/api.php?action=rejectPublication&id=${id}`, {
            method: 'POST'
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert('✅ Publication rejetée', 'success');
            closeValidationModal();
            setTimeout(() => {
                loadPublications(currentPage);
                loadStats();
            }, 1200);
        } else {
            showAlert('❌ ' + result.message, 'error');
        }
    } catch (error) {
        console.error('❌ Erreur:', error);
        showAlert('❌ Erreur de connexion', 'error');
    }
}

async function deletePublication(id) {
    if (!confirm('⚠️ Êtes-vous sûr de vouloir supprimer cette publication ?\n\nCette action est irréversible.')) {
        return;
    }
    
    try {
        const response = await fetch(`../controllers/api.php?action=deletePublication&id=${id}`, {
            method: 'POST'
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert('✅ ' + result.message, 'success');
            setTimeout(() => {
                loadPublications(currentPage);
                loadStats();
            }, 1200);
        } else {
            showAlert('❌ ' + (result.message || 'Erreur lors de la suppression'), 'error');
        }
    } catch (error) {
        console.error('❌ Erreur:', error);
        showAlert('❌ Erreur de connexion', 'error');
    }
}

function viewPublication(id) {
    window.location.href = `publication-details.php?id=${id}`;
}

function editPublication(id) {
    openModal(true, id);
}

function downloadPublication(id) {
    window.location.href = `../controllers/api.php?action=downloadPublication&id=${id}`;
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

// ============================================
// RAPPORT BIBLIOGRAPHIQUE
// ============================================

function setupReportTypeChange() {
    document.getElementById('reportType')?.addEventListener('change', function(e) {
        const yearSel = document.getElementById('yearSelection');
        const authorSel = document.getElementById('authorSelection');
        
        yearSel.style.display = 'none';
        authorSel.style.display = 'none';
        
        if (e.target.value === 'year') {
            yearSel.style.display = 'block';
        } else if (e.target.value === 'author') {
            authorSel.style.display = 'block';
        }
    });
}

function generateReport() {
    openReportModal();
}

async function downloadReport() {
    const type = document.getElementById('reportType').value;
    const year = document.getElementById('reportYear').value;
    const author = document.getElementById('reportAuthor')?.value || '';
    const format = document.getElementById('reportFormat').value;
    
    let url = `../controllers/api.php?action=generateReport&type=${type}&format=${format}`;
    if (year) url += `&year=${year}`;
    if (author) url += `&author=${author}`;
    
    try {
        showAlert('⏳ Génération du rapport en cours...', 'info');
        
        const response = await fetch(url);
        const blob = await response.blob();
        
        // Télécharger le fichier
        const downloadUrl = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = downloadUrl;
        a.download = `rapport_${type}_${Date.now()}.${format}`;
        document.body.appendChild(a);
        a.click();
        a.remove();
        window.URL.revokeObjectURL(downloadUrl);
        
        showAlert('✅ Rapport généré avec succès', 'success');
        closeReportModal();
    } catch (error) {
        console.error('❌ Erreur:', error);
        showAlert('❌ Erreur lors de la génération du rapport', 'error');
    }
}

// ============================================
// ALERTES
// ============================================

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