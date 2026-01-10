<?php
// Imports des dépendances
require_once __DIR__ . '/../../views/public/View.php';
require_once __DIR__ . '/../../views/public/components/UIHeader.php';
require_once __DIR__ . '/../../views/public/components/UIFooter.php';

class PublicationView extends View {

    /**
     * Méthode principale pour structurer la page
     */
    public function render() {
        // Extraction des données
        $config = $this->data['config'] ?? [];
        $menuData = $this->data['menu'] ?? [];
        $pageTitle = $this->data['title'] ?? 'Gestion des Publications';
        
      
        $currentUser = $this->data['currentUser'] ?? ['nom' => 'Utilisateur', 'prenom' => 'Inconnu'];

        // CSS spécifiques
        $customCss = [
            'views/admin_dashboard.css',
            'views/landingPage.css',
            'views/modelAddUser.css',
            'views/teamManagement.css',
            'assets/css/public.css'
        ];

        // 1. Rendu du Header
        $header = new UIHeader($pageTitle, $config, $menuData, $customCss);
        echo $header->render();

        // 2. Contenu Principal
        echo '<main style="width: 100%; padding: 40px 20px; box-sizing: border-box; background-color: #f8f9fc; min-height: 80vh;">';
        
        // On passe les infos utilisateur à la méthode content
        echo $this->content($currentUser);
        
        echo '</main>';

        // 3. Rendu du Footer
        $footer = new UIFooter($config, $menuData);
        echo $footer->render();
    }

    /**
     * Contenu spécifique
     */
    protected function content($currentUser = []) {
        // Sécurisation du nom de l'auteur pour le formulaire
        $auteurNomComplet = htmlspecialchars(($currentUser['nom'] ?? '') . ' ' . ($currentUser['prenom'] ?? ''));
        
        ob_start();
        ?>
        
        <!-- En-tête Interne -->
        <div class="top-bar" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 1px solid #eee; padding-bottom: 20px;">
            <div>
                <h1 style="margin:0; color: #2c3e50;">Gestion des Publications</h1>
                <p style="color: #666; margin-top:5px;">Base documentaire et validation des publications scientifiques</p>
            </div>
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
                <input type="text" id="searchInput" placeholder="🔍 Rechercher un titre,  domaine..." 
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
            <h2 style="display: flex; justify-content: space-between; align-items: center;">
                <span>Liste des Publications</span>
                <button class="btn btn-primary" onclick="openPubModal()" style="padding: 10px 20px; background-color: #4e73df; color: white; border: none; border-radius: 5px; cursor: pointer;">
                    ➕ Ajouter une publication
                </button>
            </h2>
            
            <div id="loadingSpinner" style="display: flex; justify-content:center;">
                <p>⏳ Chargement des publications...</p>
            </div>
            
            <div id="publicationTableContainer" style="display: flex; justify-content:center;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Titre</th>
                            <th>Type</th>
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

        <!-- MODAL AJOUT PUBLICATION -->
        <div id="pubModal" class="modal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background-color:rgba(0,0,0,0.5);">
            <div class="modal-content" style="background:white; width:600px; margin:50px auto; padding:30px; border-radius:10px; max-height:90vh; overflow-y:auto; box-shadow: 0 4px 20px rgba(0,0,0,0.2);">
                <div class="modal-header" style="border-bottom:1px solid #eee; padding-bottom:10px; margin-bottom:20px; display:flex; justify-content:space-between; align-items:center;">
                    <h2 style="color:#4e73df; margin:0;">📄 Soumettre une Publication</h2>
                    <span onclick="closePubModal()" style="font-size:24px; cursor:pointer; color:#aaa;">&times;</span>
                </div>

                <form id="pubForm" enctype="multipart/form-data">
                    
                    <!-- Champ Auteur Bloqué -->
                    <div class="form-group" style="margin-bottom:15px;">
                        <label style="display:block; font-weight:bold; margin-bottom:5px;">Auteur principal <span style="color:red">*</span></label>
                        <input type="text" value="<?= $auteurNomComplet ?>" class="form-control" style="width:100%; padding:8px; background-color: #e9ecef; cursor: not-allowed;" readonly>
                        <small style="color:#888;">Vous êtes identifié comme l'auteur principal.</small>
                    </div>

                    <div class="form-group" style="margin-bottom:15px;">
                        <label style="display:block; font-weight:bold; margin-bottom:5px;">Titre <span style="color:red">*</span></label>
                        <input type="text" name="titre" required class="form-control" style="width:100%; padding:8px;">
                    </div>
                    
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                        <div class="form-group">
                            <label style="display:block; font-weight:bold; margin-bottom:5px;">Type <span style="color:red">*</span></label>
                            <select name="type" required class="form-control" style="width:100%; padding:8px;">
                                <option value="article">Article</option>
                                <option value="rapport">Rapport</option>
                                <option value="these">Thèse</option>
                                <option value="communication">Communication</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label style="display:block; font-weight:bold; margin-bottom:5px;">Date</label>
                            <input type="date" name="date_publication" class="form-control" style="width:100%; padding:8px;">
                        </div>
                    </div>

                    <div class="form-group" style="margin-top:15px;">
                        <label style="display:block; font-weight:bold; margin-bottom:5px;">Domaine</label>
                        <input type="text" name="domaine" placeholder="Ex: IA, Réseaux..." class="form-control" style="width:100%; padding:8px;">
                    </div>

                    <div class="form-group" style="margin-top:15px;">
                        <label style="display:block; font-weight:bold; margin-bottom:5px;">Résumé</label>
                        <textarea name="resume" rows="3" class="form-control" style="width:100%; padding:8px;"></textarea>
                    </div>

                    <div class="form-group" style="margin-top:15px;">
                        <label style="display:block; font-weight:bold; margin-bottom:5px;">Fichier PDF</label>
                        <input type="file" name="fichier" accept=".pdf" class="form-control">
                        <small style="color:#888;">Format PDF uniquement (Max 10Mo)</small>
                    </div>

                    <div style="text-align:right; margin-top:20px;">
                        <button type="button" onclick="closePubModal()" class="btn-reset" style="margin-right:10px; border:none; background:none; cursor:pointer; text-decoration:underline;">Annuler</button>
                        <button type="submit" class="btn-filter" style="padding:10px 20px; background:#4e73df; color:white; border:none; border-radius:5px; cursor:pointer;">Soumettre</button>
                    </div>
                </form>
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
                let url = `../../controllers/api.php?action=getPublications&page=${page}&perPage=${perPage}`;
                
                if (currentFilters.type) url += `&type=${currentFilters.type}`;
                if (currentFilters.statut) url += `&statut=${currentFilters.statut}`;
                if (currentFilters.domaine) url += `&domaine=${encodeURIComponent(currentFilters.domaine)}`;
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
                console.error('❌ Erreur:', error);
                document.getElementById('loadingSpinner').innerHTML = '<p style="color: red;">❌ Erreur de chargement</p>';
            }
        }

        async function loadFilterOptions() {
            try {
                // Chargement des domaines
                const domainsResponse = await fetch('../../controllers/api.php?action=getPublicationsByDomain');
                const domainsResult = await domainsResponse.json();
                
                if (domainsResult.success && domainsResult.data) {
                    const filterDomain = document.getElementById('filterDomain');
                    filterDomain.innerHTML = '<option value="">🔬 Tous les domaines</option>';
                    domainsResult.data.forEach(domain => {
                        if (domain.domaine) {
                            filterDomain.innerHTML += `<option value="${domain.domaine}">${domain.domaine}</option>`;
                        }
                    });
                }
                
                // Chargement des années
                const yearsResponse = await fetch('../../controllers/api.php?action=getDistinctYears');
                const yearsResult = await yearsResponse.json();
                
                if (yearsResult.success && yearsResult.data) {
                    const filterYear = document.getElementById('filterYear');
                    filterYear.innerHTML = '<option value="">📅 Toutes les années</option>';
                    yearsResult.data.forEach(year => {
                        if (year.year) {
                            filterYear.innerHTML += `<option value="${year.year}">${year.year}</option>`;
                        }
                    });
                }
            } catch (error) {
                console.error('❌ Erreur chargement filtres:', error);
            }
        }

        // --- GESTION MODALE ---
        function openPubModal() {
            document.getElementById('pubModal').style.display = 'block';
        }

        function closePubModal() {
            document.getElementById('pubModal').style.display = 'none';
            document.getElementById('pubForm').reset();
        }

        window.onclick = function(event) {
            if (event.target == document.getElementById('pubModal')) {
                closePubModal();
            }
        }

        // Soumission Formulaire
        document.getElementById('pubForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const btn = this.querySelector('button[type="submit"]');
            
            btn.disabled = true;
            btn.textContent = "Envoi...";

            try {
                const res = await fetch('../../controllers/api.php?action=createPublication', {
                    method: 'POST',
                    body: formData
                });
                const json = await res.json();

                if(json.success) {
                    showAlert("✅ Publication soumise avec succès !", 'success');
                    closePubModal();
                    // Rafraichir la liste
                    setTimeout(() => loadPublications(1), 500);
                    loadStats();
                } else {
                    showAlert("❌ " + json.message, 'error');
                }
            } catch(e) {
                console.error(e);
                showAlert("❌ Erreur serveur", 'error');
            } finally {
                btn.disabled = false;
                btn.textContent = "Soumettre";
            }
        });

        function renderPublicationTable(publications) {
            const tbody = document.getElementById('publicationTableBody');
            tbody.innerHTML = '';
            
            if (!publications || publications.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 20px;">Aucune publication trouvée</td></tr>';
                return;
            }
            
            publications.forEach(pub => {
                const tr = document.createElement('tr');
                tr.style.borderBottom = "1px solid #eee";
                
                // ID
                const tdId = document.createElement('td'); tdId.textContent = pub.id; tdId.style.padding="10px"; tr.appendChild(tdId);
                
                // Titre
                const tdTitre = document.createElement('td'); 
                const titre = pub.titre || ''; 
                tdTitre.textContent = titre.length > 40 ? titre.substring(0, 40) + '...' : titre; 
                tdTitre.title = titre;
                tdTitre.style.fontWeight = "500";
                tr.appendChild(tdTitre);
                
                // Type
                const tdType = document.createElement('td'); 
                const types = { 'article': '📰 Article', 'rapport': '📊 Rapport', 'these': '🎓 Thèse', 'communication': '🎤 Communication' };
                tdType.textContent = types[pub.type] || pub.type; 
                tr.appendChild(tdType);
                
                // Auteurs (Correction ici : on affiche le nom et prénom)
               
                // Domaine
                const tdDomaine = document.createElement('td'); tdDomaine.textContent = pub.domaine || '-'; tr.appendChild(tdDomaine);
                
                // Date
                const tdDate = document.createElement('td'); 
                tdDate.textContent = pub.date_publication ? new Date(pub.date_publication).toLocaleDateString('fr-FR') : '-';
                tr.appendChild(tdDate);
                
                // Statut
                const tdStatut = document.createElement('td'); 
                const statuts = {
                    'en_attente': '<span class="badge badge-warning" style="background:#fef3c7; color:#92400e; padding:4px 8px; border-radius:4px; font-size:12px;">⏳ En attente</span>',
                    'valide': '<span class="badge badge-success" style="background:#d1fae5; color:#065f46; padding:4px 8px; border-radius:4px; font-size:12px;">✅ Validée</span>',
                    'rejete': '<span class="badge badge-danger" style="background:#fee2e2; color:#b91c1c; padding:4px 8px; border-radius:4px; font-size:12px;">❌ Rejetée</span>'
                };
                tdStatut.innerHTML = statuts[pub.statut_validation] || pub.statut_validation; 
                tr.appendChild(tdStatut);
                
                // Actions
                const tdActions = document.createElement('td');
                tdActions.innerHTML = `<button onclick="viewPublication(${pub.id})" style="background:#4e73df; color:white; border:none; padding:5px 10px; border-radius:4px; cursor:pointer;" title="Voir">👁️</button>`;
                tr.appendChild(tdActions);
                
                tbody.appendChild(tr);
            });
        }

        function renderPagination() {
            const container = document.getElementById('paginationContainer');
            if (totalPages <= 1) { container.innerHTML = ''; return; }
            
            let html = '<div style="display: flex; gap: 5px; justify-content: center; align-items: center;">';
            if (currentPage > 1) html += `<button class="btn-secondary" onclick="loadPublications(${currentPage - 1})">«</button>`;
            
            for (let i = 1; i <= totalPages; i++) {
                if (i === currentPage) html += `<button class="btn-primary" disabled style="background:#4e73df; color:white;">${i}</button>`;
                else html += `<button class="btn-secondary" onclick="loadPublications(${i})">${i}</button>`;
            }
            if (currentPage < totalPages) html += `<button class="btn-secondary" onclick="loadPublications(${currentPage + 1})">»</button>`;
            html += '</div>';
            container.innerHTML = html;
        }

        function viewPublication(id) {
            window.location.href = `index.php?route=publication-details&id=${id}`;
        }

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
            const tempContainer = document.createElement('div');
            tempContainer.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 10000;';
            document.body.appendChild(tempContainer);
            
            const alertDiv = document.createElement('div');
            alertDiv.style.cssText = 'padding: 15px; border-radius: 8px; margin-bottom: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);';
            const colors = { 'success': '#10b981', 'error': '#ef4444', 'warning': '#f59e0b', 'info': '#3b82f6' };
            alertDiv.style.background = colors[type] || colors.info;
            alertDiv.style.color = 'white';
            alertDiv.innerHTML = message;
            
            tempContainer.appendChild(alertDiv);
            setTimeout(() => {
                alertDiv.remove();
                if (tempContainer.children.length === 0) tempContainer.remove();
            }, 5000);
        }
        </script>
        <?php
        return ob_get_clean();
    }
}
?>