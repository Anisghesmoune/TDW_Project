<?php
require_once __DIR__ . '/../views/public/View.php';
require_once __DIR__ . '/../views/public/components/UIHeader.php';
require_once __DIR__ . '/../views/public/components/UIFooter.php';

class OpportunityAdminView extends View {

    public function render() {
        $config = $this->data['config'] ?? [];
        $menuData = $this->data['menu'] ?? [];
        $pageTitle = $this->data['title'] ?? 'Opportunités';

        $customCss = [
            'views/admin_dashboard.css',
            'views/modelAddUser.css',
            'views/landingPage.css',
        ];

        $header = new UIHeader($pageTitle, $config, $menuData, $customCss);
        echo $header->render();

        echo '<main style="width: 100%; padding: 40px 20px; box-sizing: border-box; background-color: #f8f9fc; min-height: 80vh;">';
        echo $this->content();
        echo '</main>';

        $footer = new UIFooter($config, $menuData);
        echo $footer->render();
    }

    protected function content() {
        $stats = $this->data['stats'] ?? ['total' => 0, 'active' => 0];

        ob_start();
        ?>
        
        <style>
            .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
            .stat-card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); text-align: center; border-bottom: 4px solid #ddd; }
            .stat-card:nth-child(1) { border-color: #4e73df; }
            .stat-card:nth-child(2) { border-color: #1cc88a; }
            .stat-card:nth-child(3) { border-color: #36b9cc; }
            .stat-card:nth-child(4) { border-color: #f6c23e; }
            .stat-card .number { font-size: 2em; font-weight: bold; margin-top: 10px; }

            .modal { display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background-color:rgba(0,0,0,0.5); backdrop-filter: blur(2px); }
            .modal.active { display: flex; align-items: center; justify-content: center; }
            .modal-content { background:white; padding:30px; border-radius:10px; width: 90%; max-width: 600px; max-height:90vh; overflow-y:auto; }
            
            .badge { padding: 4px 10px; border-radius: 15px; font-size: 0.85em; font-weight: 500; }
            .badge-active { background: #d1fae5; color: #065f46; }
            .badge-expirée { background: #fee2e2; color: #b91c1c; }
            .badge-fermée { background: #f3f4f6; color: #374151; }
            
            .type-tag { font-size: 0.8em; text-transform: uppercase; font-weight: bold; color: #4e73df; background: #eef2ff; padding: 3px 8px; border-radius: 4px; }
        </style>

        <div class="top-bar" style="display: flex; justify-content: space-between; margin-bottom: 30px; border-bottom: 1px solid #eee; padding-bottom: 20px;">
            <div>
                <h1 style="margin: 0; color: #2c3e50;">Offres et Opportunités</h1>
                <p style="color: #666; margin-top: 5px;">Stages, Thèses, Bourses et Collaborations</p>
            </div>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Offres</h3>
                <div class="number" id="totalCount"><?= $stats['total'] ?></div>
            </div>
            <div class="stat-card">
                <h3>Actives</h3>
                <div class="number" style="color:#1cc88a;"><?= $stats['active'] ?></div>
            </div>
            <div class="stat-card">
                <h3>Stages</h3>
                <div class="number"><?= $stats['stage'] ?? 0 ?></div> <!-- Ajustez selon vos données réelles -->
            </div>
            <div class="stat-card">
                <h3>Thèses</h3>
                <div class="number"><?= $stats['these'] ?? 0 ?></div>
            </div>
        </div>

        <!-- Filtres et Actions -->
        <div class="content-section" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); margin-bottom: 30px;">
            <div style="display: flex; gap: 15px; flex-wrap: wrap; align-items: center;">
                <input type="text" id="searchInput" placeholder="🔍 Rechercher..." class="form-control" style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                
                <select id="filterType" onchange="loadOpportunities()" class="form-control" style="padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                    <option value="">Tous les types</option>
                    <option value="stage">Stage</option>
                    <option value="thèse">Thèse</option>
                    <option value="bourse">Bourse</option>
                    <option value="collaboration">Collaboration</option>
                </select>

                <select id="filterStatut" onchange="loadOpportunities()" class="form-control" style="padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                    <option value="">Tous les statuts</option>
                    <option value="active">Active</option>
                    <option value="expirée">Expirée</option>
                    <option value="fermée">Fermée</option>
                </select>

                <button class="btn btn-primary" onclick="openModal()" style="padding: 10px 20px; background: #4e73df; color: white; border: none; border-radius: 5px; cursor: pointer;">
                    ➕ Nouvelle Offre
                </button>
            </div>
        </div>

        <!-- Tableau -->
        <div class="content-section" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
            <div id="loadingSpinner" style="text-align: center; padding: 40px;">⏳ Chargement...</div>
            
            <table class="data-table" style="width: 100%; border-collapse: collapse; display: none;" id="oppTable">
                <thead>
                    <tr style="background: #f8f9fa; border-bottom: 2px solid #e3e6f0;">
                        <th style="padding: 12px; text-align: left;">ID</th>
                        <th style="padding: 12px; text-align: left;">Titre</th>
                        <th style="padding: 12px; text-align: left;">Type</th>
                        <th style="padding: 12px; text-align: left;">Publication</th>
                        <th style="padding: 12px; text-align: left;">Expiration</th>
                        <th style="padding: 12px; text-align: left;">Statut</th>
                        <th style="padding: 12px; text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody"></tbody>
            </table>
        </div>

        <!-- Modal Création / Édition -->
        <div id="oppModal" class="modal">
            <div class="modal-content">
                <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 20px;">
                    <h2 id="modalTitle" style="margin: 0; color: #4e73df;">Nouvelle Offre</h2>
                    <span onclick="closeModal()" style="cursor: pointer; font-size: 1.5rem;">&times;</span>
                </div>
                
                <form id="oppForm">
                    <input type="hidden" id="oppId" name="id">
                    
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display:block; margin-bottom:5px; font-weight:bold;">Titre <span style="color:red">*</span></label>
                        <input type="text" name="titre" id="titre" required class="form-control" style="width:100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                        <div>
                            <label style="display:block; margin-bottom:5px; font-weight:bold;">Type <span style="color:red">*</span></label>
                            <select name="type" id="type" required class="form-control" style="width:100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                                <option value="stage">Stage</option>
                                <option value="thèse">Thèse</option>
                                <option value="bourse">Bourse</option>
                                <option value="collaboration">Collaboration</option>
                                <option value="autre">Autre</option>
                            </select>
                        </div>
                        <div>
                            <label style="display:block; margin-bottom:5px; font-weight:bold;">Statut</label>
                            <select name="statut" id="statut" class="form-control" style="width:100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                                <option value="active">Active</option>
                                <option value="expirée">Expirée</option>
                                <option value="fermée">Fermée</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display:block; margin-bottom:5px; font-weight:bold;">Description</label>
                        <textarea name="description" id="description" rows="4" class="form-control" style="width:100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;"></textarea>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                        <div>
                            <label style="display:block; margin-bottom:5px; font-weight:bold;">Date Expiration</label>
                            <input type="date" name="date_expiration" id="date_expiration" class="form-control" style="width:100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                        </div>
                        <div>
                            <label style="display:block; margin-bottom:5px; font-weight:bold;">Contact (Email/Tél)</label>
                            <input type="text" name="contact" id="contact" class="form-control" style="width:100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                        </div>
                    </div>

                    <div style="text-align: right; margin-top: 20px; border-top: 1px solid #eee; padding-top: 15px;">
                        <button type="button" onclick="closeModal()" style="padding: 10px 20px; background: #858796; color: white; border: none; border-radius: 5px; cursor: pointer; margin-right: 10px;">Annuler</button>
                        <button type="submit" style="padding: 10px 20px; background: #4e73df; color: white; border: none; border-radius: 5px; cursor: pointer;">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            let allOpps = [];

            document.addEventListener('DOMContentLoaded', () => {
                loadOpportunities();
                setupSearch();
            });

            async function loadOpportunities() {
                try {
                    const res = await fetch('controllers/api.php?action=getOpportunities');
                    const json = await res.json();
                    
                    if(json.success) {
                        allOpps = json.data;
                        renderTable(allOpps);
                        document.getElementById('loadingSpinner').style.display = 'none';
                        document.getElementById('oppTable').style.display = 'table';
                    }
                } catch(e) { console.error(e); }
            }

            function renderTable(data) {
                const tbody = document.getElementById('tableBody');
                tbody.innerHTML = '';
                
                // Filtres JS
                const search = document.getElementById('searchInput').value.toLowerCase();
                const typeFilter = document.getElementById('filterType').value;
                const statusFilter = document.getElementById('filterStatut').value;

                const filtered = data.filter(item => {
                    const matchSearch = item.titre.toLowerCase().includes(search);
                    const matchType = !typeFilter || item.type === typeFilter;
                    const matchStatut = !statusFilter || item.statut === statusFilter;
                    return matchSearch && matchType && matchStatut;
                });

                if(filtered.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:20px;">Aucune offre trouvée</td></tr>';
                    return;
                }

                filtered.forEach(opp => {
                    const tr = document.createElement('tr');
                    tr.style.borderBottom = '1px solid #eee';
                    
                    const badgeClass = `badge-${opp.statut}`;
                    const datePub = new Date(opp.date_publication).toLocaleDateString();
                    const dateExp = opp.date_expiration ? new Date(opp.date_expiration).toLocaleDateString() : '-';

                    tr.innerHTML = `
                        <td style="padding:12px;">${opp.id}</td>
                        <td style="padding:12px; font-weight:bold; color:#333;">${opp.titre}</td>
                        <td style="padding:12px;"><span class="type-tag">${opp.type}</span></td>
                        <td style="padding:12px;">${datePub}</td>
                        <td style="padding:12px;">${dateExp}</td>
                        <td style="padding:12px;"><span class="badge ${badgeClass}">${opp.statut}</span></td>
                        <td style="padding:12px; text-align:center;">
                            <button onclick="editOpp(${opp.id})" style="background:none; border:none; cursor:pointer; margin-right:5px;">✏️</button>
                            <button onclick="deleteOpp(${opp.id})" style="background:none; border:none; cursor:pointer; color:red;">🗑️</button>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            }

            function openModal(edit = false, id = null) {
                const modal = document.getElementById('oppModal');
                const form = document.getElementById('oppForm');
                const title = document.getElementById('modalTitle');
                
                if(edit && id) {
                    title.textContent = "Modifier l'Offre";
                    const item = allOpps.find(i => i.id == id);
                    if(item) {
                        document.getElementById('oppId').value = item.id;
                        document.getElementById('titre').value = item.titre;
                        document.getElementById('type').value = item.type;
                        document.getElementById('statut').value = item.statut;
                        document.getElementById('description').value = item.description;
                        document.getElementById('date_expiration').value = item.date_expiration;
                        document.getElementById('contact').value = item.contact;
                    }
                } else {
                    title.textContent = "Nouvelle Offre";
                    form.reset();
                    document.getElementById('oppId').value = '';
                    document.getElementById('statut').value = 'active'; // Défaut
                }
                modal.classList.add('active');
            }

            function closeModal() {
                document.getElementById('oppModal').classList.remove('active');
            }

            document.getElementById('oppForm').addEventListener('submit', async function(e) {
                e.preventDefault();
                const data = Object.fromEntries(new FormData(this));
                const id = document.getElementById('oppId').value;
                const action = id ? 'updateOpportunity' : 'createOpportunity';
                const url = id ? `controllers/api.php?action=${action}&id=${id}` : `controllers/api.php?action=${action}`;

                try {
                    const res = await fetch(url, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(data)
                    });
                    const json = await res.json();
                    
                    if(json.success) {
                        closeModal();
                        loadOpportunities();
                        alert('✅ Enregistré avec succès');
                    } else {
                        alert('❌ Erreur: ' + json.message);
                    }
                } catch(err) { console.error(err); }
            });

            function editOpp(id) { openModal(true, id); }

            async function deleteOpp(id) {
                if(!confirm("Supprimer cette offre ?")) return;
                
                try {
                    const res = await fetch(`controllers/api.php?action=deleteOpportunity&id=${id}`, { method: 'POST' });
                    const json = await res.json();
                    if(json.success) {
                        loadOpportunities();
                    } else {
                        alert("Erreur suppression");
                    }
                } catch(err) { console.error(err); }
            }

            function setupSearch() {
                document.getElementById('searchInput').addEventListener('input', () => renderTable(allOpps));
            }
            
            // Close modal on outside click
            window.onclick = function(e) {
                if(e.target == document.getElementById('oppModal')) closeModal();
            }
        </script>
        <?php
        return ob_get_clean();
    }
}
?>