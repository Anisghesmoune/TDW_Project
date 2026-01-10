<?php
// Imports des dépendances
require_once __DIR__ . '/../views/public/View.php';
require_once __DIR__ . '/../views/public/components/UIHeader.php';
require_once __DIR__ . '/../views/public/components/UIFooter.php';

class PublicationAdminView extends View {

    /**
     * Méthode principale pour structurer la page
     */
    public function render() {
        // Extraction des données globales
        $config = $this->data['config'] ?? [];
        $menuData = $this->data['menu'] ?? [];
        $pageTitle = $this->data['title'] ?? 'Gestion des Publications';

        // CSS spécifiques
        $customCss = [
            'views/admin_dashboard.css',
            'views/modelAddUser.css',
            'views/teamManagement.css',
            'views/landingPage.css',
        ];

        // 1. Rendu du Header (Remplace la Sidebar)
        // Note: Si votre UIHeader ne gère pas les scripts JS dans $customCss, il faudra l'ajouter manuellement dans content()
        $header = new UIHeader($pageTitle, $config, $menuData, $customCss);
        echo $header->render();

        // 2. Contenu Principal
        echo '<main style="width: 100%; padding: 40px 20px; box-sizing: border-box; background-color: #f8f9fc; min-height: 80vh;">';
        
        // Ajout manuel du script Chart.js si le header ne le gère pas comme <script>
        echo '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>';
        
        echo $this->content();
        echo '</main>';

        // 3. Rendu du Footer
        $footer = new UIFooter($config, $menuData);
        echo $footer->render();
    }

    /**
     * Contenu spécifique : Stats, Tableau, Modales, JS
     */
    protected function content() {
        ob_start();
        ?>
        
        <!-- Styles internes -->
        <style>
            /* Ajustements Layout sans Sidebar */
            .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
            .stat-card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); text-align: center; }
            .stat-card .number { font-size: 2em; font-weight: bold; margin-top: 10px; }
            
            /* Modale */
            .modal { display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background-color:rgba(0,0,0,0.5); backdrop-filter: blur(2px); }
            .modal.active { display: flex; align-items: center; justify-content: center; }
            .modal-content { background:white; padding:30px; border-radius:10px; max-height:90vh; overflow-y:auto; box-shadow: 0 10px 25px rgba(0,0,0,0.2); width: 90%; max-width: 900px; }
            .modal-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 20px; }
            .close-btn { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #aaa; }
            
            /* Formulaires */
            .form-group { margin-bottom: 15px; }
            .form-control { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
            .btn-primary { background: #4e73df; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; transition: 0.2s; }
            .btn-secondary { background: #858796; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; transition: 0.2s; }
            .required { color: #e74a3b; }
        </style>

        <!-- Top Bar Interne -->
        <div class="top-bar" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 1px solid #eee; padding-bottom: 20px;">
            <div>
                <h1 style="margin: 0; color: #2c3e50;">Gestion des Publications</h1>
                <p style="color: #666; margin-top: 5px;">Base documentaire et validation des publications scientifiques</p>
            </div>
        </div>
        
        <!-- Statistiques -->
        <div class="stats-grid">
            <div class="stat-card" style="border-bottom: 4px solid #4e73df;">
                <h3>Total Publications</h3>
                <div class="number" id="totalPublications">0</div>
            </div>
            
            <div class="stat-card" style="border-bottom: 4px solid #10b981;">
                <h3>Validées</h3>
                <div class="number" style="color: #10b981;" id="validatedCount">0</div>
            </div>
            
            <div class="stat-card" style="border-bottom: 4px solid #f59e0b;">
                <h3>En Attente</h3>
                <div class="number" style="color: #f59e0b;" id="pendingCount">0</div>
            </div>
            
            <div class="stat-card" style="border-bottom: 4px solid #3b82f6;">
                <h3>Cette Année</h3>
                <div class="number" style="color: #3b82f6;" id="currentYearCount">0</div>
            </div>
        </div>

        <!-- Filtres et recherche -->
        <div class="content-section" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); margin-bottom: 30px;">
            <div style="display: flex; gap: 15px; flex-wrap: wrap; align-items: center;">
                <input type="text" id="searchInput" placeholder="🔍 Rechercher un titre, auteur, domaine..." 
                       class="form-control" style="flex: 1; min-width: 250px;">
                
                <select id="filterType" onchange="applyFilters()" 
                        class="form-control" style="width: auto;">
                    <option value="">📄 Tous les types</option>
                    <option value="article">📰 Article</option>
                    <option value="rapport">📊 Rapport</option>
                    <option value="these">🎓 Thèse</option>
                    <option value="communication">🎤 Communication</option>
                </select>

                <select id="filterStatus" onchange="applyFilters()" 
                        class="form-control" style="width: auto;">
                    <option value="">📊 Tous les statuts</option>
                    <option value="en_attente">⏳ En attente</option>
                    <option value="valide">✅ Validée</option>
                    <option value="rejete">❌ Rejetée</option>
                </select>

                <select id="filterDomain" onchange="applyFilters()" 
                        class="form-control" style="width: auto;">
                    <option value="">🔬 Tous les domaines</option>
                </select>

                <select id="filterYear" onchange="applyFilters()" 
                        class="form-control" style="width: auto;">
                    <option value="">📅 Toutes les années</option>
                </select>

                <button class="btn-secondary" onclick="resetFilters()">
                    🔄 Réinitialiser
                </button>

                <button class="btn-primary" onclick="generateReport()" style="background: #36b9cc;">
                    📑 Rapport Bibliographique
                </button>
            </div>
        </div>
        
        <!-- Tableau des publications -->
        <div class="content-section" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
            <h2 style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #f8f9fa; padding-bottom: 20px; margin-bottom: 20px;">
                <span>Liste des Publications</span>
                <button class="btn-primary" onclick="openModal()">
                    ➕ Ajouter une publication
                </button>
            </h2>
            
            <div id="loadingSpinner" style="text-align: center; padding: 40px;">
                <p>⏳ Chargement des publications...</p>
            </div>
            
            <div id="publicationTableContainer" style="display: none;">
                <table class="data-table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f8f9fa; border-bottom: 2px solid #e3e6f0;">
                            <th style="padding: 12px; text-align: left;">ID</th>
                            <th style="padding: 12px; text-align: left;">Titre</th>
                            <th style="padding: 12px; text-align: left;">Type</th>
                            <th style="padding: 12px; text-align: left;">Auteur(s)</th>
                            <th style="padding: 12px; text-align: left;">Domaine</th>
                            <th style="padding: 12px; text-align: left;">Date</th>
                            <th style="padding: 12px; text-align: left;">Statut</th>
                            <th style="padding: 12px; text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="publicationTableBody">
                    </tbody>
                </table>
                
                <!-- Pagination -->
                <div id="paginationContainer" style="margin-top: 20px; text-align: center; display: flex; justify-content: center; gap: 5px;">
                </div>
            </div>
        </div>

        <!-- Statistiques par type et domaine -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 30px;">
            <div class="content-section" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                <h2 style="margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px;">📊 Publications par Type</h2>
                <div style="height: 300px; position: relative;">
                    <canvas id="typeChart"></canvas>
                </div>
            </div>
            
            <div class="content-section" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                <h2 style="margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px;">🔬 Publications par Domaine</h2>
                <div style="height: 300px; position: relative;">
                    <canvas id="domainChart"></canvas>
                </div>
            </div>
        </div>
    
        <!-- Modal Ajouter/Modifier Publication -->
        <div id="publicationModal" class="modal">
            <div class="modal-content" style="max-width: 900px;">
                <div class="modal-header">
                    <h2 id="modalTitle" style="margin: 0; color: #4e73df;">➕ Ajouter une publication</h2>
                    <button class="close-btn" onclick="closePublicationModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="publicationForm" enctype="multipart/form-data">
                        <div id="alertContainer"></div>

                        <input type="hidden" id="publicationId" name="id">
                        
                        <div class="form-group">
                            <label for="titre" style="display:block; margin-bottom:5px; font-weight:bold;">Titre <span class="required">*</span></label>
                            <input type="text" class="form-control" id="titre" name="titre" required placeholder="Titre complet...">
                        </div>

                        <div class="form-group">
                            <label for="resume" style="display:block; margin-bottom:5px; font-weight:bold;">Résumé</label>
                            <textarea class="form-control" id="resume" name="resume" rows="4" placeholder="Résumé..."></textarea>
                        </div>

                        <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                            <div class="form-group">
                                <label for="type" style="display:block; margin-bottom:5px; font-weight:bold;">Type <span class="required">*</span></label>
                                <select class="form-control" id="type" name="type" required>
                                    <option value="">-- Sélectionner --</option>
                                    <option value="article">📰 Article</option>
                                    <option value="rapport">📊 Rapport</option>
                                    <option value="these">🎓 Thèse</option>
                                    <option value="communication">🎤 Communication</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="domaine" style="display:block; margin-bottom:5px; font-weight:bold;">Domaine</label>
                                <input type="text" class="form-control" id="domaine" name="domaine" placeholder="Ex: IA">
                            </div>
                        </div>

                        <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                            <div class="form-group">
                                <label for="date_publication" style="display:block; margin-bottom:5px; font-weight:bold;">Date de publication</label>
                                <input type="date" class="form-control" id="date_publication" name="date_publication">
                            </div>

                            <div class="form-group">
                                <label for="doi" style="display:block; margin-bottom:5px; font-weight:bold;">DOI</label>
                                <input type="text" class="form-control" id="doi" name="doi" placeholder="Ex: 10.1234/example">
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 15px;">
                            <label for="fichier" style="display:block; margin-bottom:5px; font-weight:bold;">Fichier PDF</label>
                            <input type="file" class="form-control" id="fichier" name="fichier" accept=".pdf">
                            <small style="color: #666;">Format PDF uniquement, max 10MB</small>
                        </div>

                        <div class="form-group" style="margin-bottom: 15px;">
                            <label for="auteurs" style="display:block; margin-bottom:5px; font-weight:bold;">Auteurs</label>
                            <input type="text" class="form-control" id="auteurs" name="auteurs" placeholder="Noms séparés par virgules">
                        </div>

                        <div class="form-group">
                            <label for="projet" style="display:block; margin-bottom:5px; font-weight:bold;">Projet associé</label>
                            <select class="form-control" id="projet" name="projet">
                                <option value="">-- Aucun projet --</option>
                                <!-- Rempli dynamiquement si besoin -->
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer" style="border-top:1px solid #eee; padding-top:15px; text-align:right;">
                    <button type="button" class="btn-secondary" onclick="closePublicationModal()" style="margin-right:10px;">Annuler</button>
                    <button type="button" class="btn-primary" onclick="savePublication()">💾 Enregistrer</button>
                </div>
            </div>
        </div>

        <!-- Modal Validation -->
        <div id="validationModal" class="modal">
            <div class="modal-content" style="max-width: 600px;">
                <div class="modal-header">
                    <h2 style="margin: 0;">✅ Validation de la publication</h2>
                    <button class="close-btn" onclick="closeValidationModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="validationPublicationId">
                    
                    <div id="publicationDetails" style="background: #f3f4f6; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                        <!-- Détails chargés dynamiquement -->
                    </div>

                    <div class="form-group">
                        <label style="display:block; margin-bottom:5px; font-weight:bold;">Action <span class="required">*</span></label>
                        <div style="display: flex; gap: 10px; margin-top: 10px;">
                            <button class="btn-primary" onclick="validatePublication()" style="flex: 1; background: #10b981;">✅ Valider</button>
                            <button class="btn-secondary" onclick="rejectPublication()" style="flex: 1; background: #ef4444; color: white;">❌ Rejeter</button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #eee; padding-top:15px; text-align:right;">
                    <button type="button" class="btn-secondary" onclick="closeValidationModal()">Fermer</button>
                </div>
            </div>
        </div>

        <!-- Modal Rapport Bibliographique -->
        <div id="reportModal" class="modal">
            <div class="modal-content" style="max-width: 700px;">
                <div class="modal-header">
                    <h2 style="margin: 0; color: #36b9cc;">📑 Rapport bibliographique</h2>
                    <button class="close-btn" onclick="closeReportModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label style="display:block; margin-bottom:5px; font-weight:bold;">Type de rapport</label>
                        <select class="form-control" id="reportType">
                            <option value="year">Par année</option>
                            <option value="author">Par auteur</option>
                            <option value="domain">Par domaine</option>
                            <option value="type">Par type</option>
                        </select>
                    </div>

                    <div class="form-group" id="yearSelection" style="margin-top:15px;">
                        <label style="display:block; margin-bottom:5px; font-weight:bold;">Année</label>
                        <select class="form-control" id="reportYear">
                            <option value="">Toutes les années</option>
                        </select>
                    </div>

                    <div class="form-group" id="authorSelection" style="display: none; margin-top:15px;">
                        <label style="display:block; margin-bottom:5px; font-weight:bold;">Auteur</label>
                        <select class="form-control" id="reportAuthor">
                            <option value="">Tous les auteurs</option>
                        </select>
                    </div>

                    <div class="form-group" style="margin-top:15px;">
                        <label style="display:block; margin-bottom:5px; font-weight:bold;">Format</label>
                        <select class="form-control" id="reportFormat">
                            <option value="pdf">PDF</option>
                            <option value="excel">Excel</option>
                            <option value="html">HTML</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #eee; padding-top:15px; text-align:right;">
                    <button type="button" class="btn-secondary" onclick="closeReportModal()" style="margin-right:10px;">Annuler</button>
                    <button type="button" class="btn-primary" onclick="downloadReport()">📥 Générer</button>
                </div>
            </div>
        </div>

        <script>
        // ============================================
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
                console.log('✅ Données chargées');
            } catch (error) {
                console.error(error);
                showAlert('❌ Erreur chargement données', 'error');
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
                    document.getElementById('currentYearCount').textContent = result.par_annee?.[currentYear] || 0;
                }
            } catch (error) { console.error(error); }
        }

        async function loadPublications(page = 1) {
            try {
                let url = `../controllers/api.php?action=getPublications&page=${page}&perPage=${perPage}`;
                
                if (currentFilters.type) url += `&type=${currentFilters.type}`;
                if (currentFilters.statut) url += `&statut=${currentFilters.statut}`;
                if (currentFilters.domaine) url += `&domaine=${currentFilters.domaine}`;
                if (currentFilters.year) url += `&year=${currentFilters.year}`;
                if (currentFilters.search) url += `&q=${encodeURIComponent(currentFilters.search)}`;
                
                const response = await fetch(url);
                const result = await response.json();

                if (result.success && result.data) {
                    allPublications = result.data;
                    totalPages = result.totalPages || 1;
                    currentPage = page;
                    
                    renderPublicationTable(allPublications);
                    renderPagination();
                    
                    document.getElementById('loadingSpinner').style.display = 'none';
                    document.getElementById('publicationTableContainer').style.display = 'block';
                }
            } catch (error) {
                console.error(error);
                document.getElementById('loadingSpinner').innerHTML = '<p style="color:red">Erreur chargement</p>';
            }
        }

        async function loadFilterOptions() {
            try {
                const [domRes, yearRes] = await Promise.all([
                    fetch('../controllers/api.php?action=getPublicationsByDomain'),
                    fetch('../controllers/api.php?action=getDistinctYears')
                ]);
                
                const domData = await domRes.json();
                const yearData = await yearRes.json();
                
                if (domData.success) {
                    const sel = document.getElementById('filterDomain');
                    sel.innerHTML = '<option value="">🔬 Tous les domaines</option>';
                    domData.data.forEach(d => { if(d.domaine) sel.innerHTML += `<option value="${d.domaine}">${d.domaine}</option>`; });
                }
                
                if (yearData.success) {
                    const sel = document.getElementById('filterYear');
                    const repSel = document.getElementById('reportYear');
                    sel.innerHTML = '<option value="">📅 Toutes les années</option>';
                    repSel.innerHTML = '<option value="">Toutes les années</option>';
                    yearData.data.forEach(y => { 
                        if(y.year) {
                            sel.innerHTML += `<option value="${y.year}">${y.year}</option>`;
                            repSel.innerHTML += `<option value="${y.year}">${y.year}</option>`;
                        }
                    });
                }
            } catch (e) { console.error(e); }
        }

        async function loadCharts() {
            try {
                const response = await fetch('../controllers/api.php?action=getPublicationStats');
                const result = await response.json();
                
                if (result.success) {
                    if (result.par_type && result.par_type.length > 0) {
                        const ctx = document.getElementById('typeChart').getContext('2d');
                        if (typeChart) typeChart.destroy();
                        typeChart = new Chart(ctx, {
                            type: 'pie',
                            data: {
                                labels: result.par_type.map(t => t.type),
                                datasets: [{ data: result.par_type.map(t => t.total), backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444'] }]
                            },
                            options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
                        });
                    }
                    
                    if (result.par_domaine && result.par_domaine.length > 0) {
                        const ctx = document.getElementById('domainChart').getContext('2d');
                        if (domainChart) domainChart.destroy();
                        domainChart = new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: result.par_domaine.map(d => d.domaine || 'Autre'),
                                datasets: [{ label: 'Publications', data: result.par_domaine.map(d => d.total), backgroundColor: '#3b82f6' }]
                            },
                            options: { responsive: true, scales: { y: { beginAtZero: true } }, plugins: { legend: { display: false } } }
                        });
                    }
                }
            } catch (e) { console.error(e); }
        }

        // --- TABLEAU ---
        function renderPublicationTable(publications) {
            const tbody = document.getElementById('publicationTableBody');
            tbody.innerHTML = '';
            
            if (!publications.length) {
                tbody.innerHTML = '<tr><td colspan="8" style="text-align:center; padding:20px;">Aucune publication</td></tr>';
                return;
            }
            
            publications.forEach(pub => {
                const tr = document.createElement('tr');
                tr.style.borderBottom = "1px solid #eee";
                
                const statuts = {
                    'en_attente': '<span class="badge badge-warning" style="background:#fef3c7; color:#92400e; padding:4px 8px; border-radius:4px;">⏳ En attente</span>',
                    'valide': '<span class="badge badge-success" style="background:#d1fae5; color:#065f46; padding:4px 8px; border-radius:4px;">✅ Validée</span>',
                    'rejete': '<span class="badge badge-danger" style="background:#fee2e2; color:#b91c1c; padding:4px 8px; border-radius:4px;">❌ Rejetée</span>'
                };

                let actionsHTML = `
                    <button class="btn-sm btn-view" onclick="viewPublication(${pub.id})" style="background:none; border:none; cursor:pointer;" title="Voir">👁️</button>
                    <button class="btn-sm btn-edit" onclick="editPublication(${pub.id})" style="background:none; border:none; cursor:pointer;" title="Modifier">✏️</button>
                `;
                
                if (pub.statut_validation === 'en_attente') {
                    actionsHTML += `<button class="btn-sm btn-primary" onclick="openValidationModal(${pub.id})" style="background:#3b82f6; color:white; border:none; border-radius:4px; padding:2px 5px;" title="Valider">✅</button>`;
                }
                
                if (pub.lien_telechargement) {
                    actionsHTML += `<button class="btn-sm btn-secondary" onclick="downloadPublication(${pub.id})" style="background:none; border:none; cursor:pointer;" title="Télécharger">📥</button>`;
                }
                
                actionsHTML += `<button class="btn-sm btn-delete" onclick="deletePublication(${pub.id})" style="background:none; border:none; cursor:pointer;" title="Supprimer">🗑️</button>`;

                tr.innerHTML = `
                    <td style="padding:10px;">${pub.id}</td>
                    <td style="padding:10px; font-weight:500;">${pub.titre}</td>
                    <td style="padding:10px;">${pub.type}</td>
                    <td style="padding:10px; color:#666;">${pub.auteurs || '-'}</td>
                    <td style="padding:10px;">${pub.domaine || '-'}</td>
                    <td style="padding:10px;">${pub.date_publication ? new Date(pub.date_publication).toLocaleDateString() : '-'}</td>
                    <td style="padding:10px;">${statuts[pub.statut_validation] || pub.statut_validation}</td>
                    <td style="padding:10px; text-align:center;">${actionsHTML}</td>
                `;
                tbody.appendChild(tr);
            });
        }

        function renderPagination() {
            const container = document.getElementById('paginationContainer');
            if (totalPages <= 1) { container.innerHTML = ''; return; }
            
            let html = '<div style="display:flex; gap:5px; justify-content:center;">';
            if (currentPage > 1) html += `<button class="btn-secondary" onclick="loadPublications(${currentPage - 1})">«</button>`;
            
            for (let i = 1; i <= totalPages; i++) {
                if (i === currentPage) html += `<button class="btn-primary" disabled style="background:#4e73df; color:white;">${i}</button>`;
                else html += `<button class="btn-secondary" onclick="loadPublications(${i})">${i}</button>`;
            }
            if (currentPage < totalPages) html += `<button class="btn-secondary" onclick="loadPublications(${currentPage + 1})">»</button>`;
            html += '</div>';
            container.innerHTML = html;
        }

        // --- MODALS & CRUD ---
        function openModal(edit=false, id=null) {
            const m = document.getElementById('publicationModal');
            if(edit && id) {
                document.getElementById('modalTitle').textContent = '✏️ Modifier';
                loadPublicationData(id);
            } else {
                document.getElementById('modalTitle').textContent = '➕ Ajouter';
                document.getElementById('publicationForm').reset();
                document.getElementById('publicationId').value = '';
            }
            m.classList.add('active');
            document.body.style.overflow='hidden';
        }
        function closePublicationModal() {
            document.getElementById('publicationModal').classList.remove('active');
            document.body.style.overflow='auto';
        }

        function openValidationModal(id) {
            document.getElementById('validationPublicationId').value = id;
            const pub = allPublications.find(p => p.id == id);
            if(pub) {
                document.getElementById('publicationDetails').innerHTML = `<h3>${pub.titre}</h3><p>Auteurs: ${pub.auteurs}</p>`;
            }
            document.getElementById('validationModal').classList.add('active');
        }
        function closeValidationModal() { document.getElementById('validationModal').classList.remove('active'); }

        function openReportModal() { document.getElementById('reportModal').classList.add('active'); }
        function closeReportModal() { document.getElementById('reportModal').classList.remove('active'); }

        async function savePublication() {
            const form = document.getElementById('publicationForm');
            if(!form.checkValidity()) { form.reportValidity(); return; }
            
            const id = document.getElementById('publicationId').value;
            const action = id ? 'updatePublication' : 'createPublication';
            const url = `../controllers/api.php?action=${action}${id ? '&id='+id : ''}`;
            
            try {
                const res = await fetch(url, { method: 'POST', body: new FormData(form) });
                const json = await res.json();
                if(json.success) {
                    showAlert('Enregistré !', 'success');
                    closePublicationModal();
                    loadPublications(currentPage);
                    loadStats();
                } else showAlert(json.message, 'error');
            } catch(e) { console.error(e); }
        }

        async function loadPublicationData(id) {
            try {
                const res = await fetch(`../controllers/api.php?action=getPublication&id=${id}`);
                const json = await res.json();
                if(json.success) {
                    const d = json.data;
                    document.getElementById('publicationId').value = d.id;
                    document.getElementById('titre').value = d.titre;
                    document.getElementById('type').value = d.type;
                    document.getElementById('domaine').value = d.domaine;
                    document.getElementById('resume').value = d.resume;
                    document.getElementById('date_publication').value = d.date_publication;
                    document.getElementById('doi').value = d.doi;
                    document.getElementById('auteurs').value = d.auteurs;
                }
            } catch(e){ console.error(e); }
        }

        async function validatePublication() {
            const id = document.getElementById('validationPublicationId').value;
            if(!confirm("Valider ?")) return;
            await fetch(`../controllers/api.php?action=validatePublication&id=${id}`, {method:'POST'});
            closeValidationModal();
            loadPublications(currentPage);
            loadStats();
        }

        async function rejectPublication() {
            const id = document.getElementById('validationPublicationId').value;
            if(!confirm("Rejeter ?")) return;
            await fetch(`../controllers/api.php?action=rejectPublication&id=${id}`, {method:'POST'});
            closeValidationModal();
            loadPublications(currentPage);
        }

        async function deletePublication(id) {
            if(!confirm("Supprimer ?")) return;
            await fetch(`../controllers/api.php?action=deletePublication&id=${id}`, {method:'POST'});
            loadPublications(currentPage);
            loadStats();
        }

        // --- FILTRES ---
        function setupSearchDebounce() {
            let timeout;
            document.getElementById('searchInput').addEventListener('input', (e) => {
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
            document.querySelectorAll('select').forEach(s => s.value = '');
            document.getElementById('searchInput').value = '';
            currentFilters = {};
            loadPublications(1);
        }

        // --- RAPPORT ---
        function setupReportTypeChange() {
            document.getElementById('reportType').addEventListener('change', (e) => {
                document.getElementById('yearSelection').style.display = e.target.value === 'year' ? 'block' : 'none';
                document.getElementById('authorSelection').style.display = e.target.value === 'author' ? 'block' : 'none';
            });
        }

        function generateReport() { openReportModal(); }
        
        async function downloadReport() {
            const type = document.getElementById('reportType').value;
            const format = document.getElementById('reportFormat').value;
            let url = `../controllers/api.php?action=generateReport&type=${type}&format=${format}`;
            
            if(type === 'year') url += `&year=${document.getElementById('reportYear').value}`;
            
            window.open(url, '_blank');
            closeReportModal();
        }

        function editPublication(id) { openModal(true, id); }
        function viewPublication(id) { window.location.href=`index.php?route=admin-publications-details&id=${id}`; }
        function downloadPublication(id) { window.open(`../controllers/api.php?action=downloadPublication&id=${id}`); }

        function showAlert(msg, type) {
            const div = document.createElement('div');
            div.style.cssText = `position:fixed; top:20px; right:20px; padding:15px; border-radius:5px; background:${type==='success'?'#10b981':'#ef4444'}; color:white; z-index:10000;`;
            div.textContent = msg;
            document.body.appendChild(div);
            setTimeout(() => div.remove(), 3000);
        }
        </script>
        <?php
        return ob_get_clean();
    }
}
?>