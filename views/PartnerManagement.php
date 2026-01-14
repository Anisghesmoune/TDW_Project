<?php
require_once __DIR__ . '/../views/public/View.php';
require_once __DIR__ . '/../views/public/components/UIHeader.php';
require_once __DIR__ . '/../views/public/components/UIFooter.php';

class PartnerAdminView extends View {

    public function render() {
        $config = $this->data['config'] ?? [];
        $menuData = $this->data['menu'] ?? [];
        $pageTitle = 'Gestion des Partenaires';

        $customCss = [
            'views/admin_dashboard.css',
            'views/modelAddUser.css',
            'views/landingPage.css'
        ];

        $header = new UIHeader($pageTitle, $config, $menuData, $customCss);
        echo $header->render();

        echo '<main style="width: 100%; padding: 40px 20px; box-sizing: border-box; background-color: #f8f9fc; min-height: 80vh;">';
        echo $this->content();
        echo '</main>';

       
    }

    protected function content() {
        $stats = $this->data['stats'] ?? ['total' => 0];

        ob_start();
        ?>
        
        <!-- Stats -->
        <div class="stats-grid" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:20px; margin-bottom:30px;">
            <div class="stat-card" style="background:white; padding:20px; border-radius:10px; text-align:center; border-bottom:4px solid #4e73df; box-shadow:0 2px 5px rgba(0,0,0,0.05);">
                <h3 style="margin:0; color:#888; font-size:0.9em;">Total Partenaires</h3>
                <div class="number" style="font-size:2em; font-weight:bold; color:#333;"><?= $stats['total'] ?></div>
            </div>
        </div>

        <!-- Actions -->
        <div class="content-section" style="background:white; padding:20px; border-radius:8px; margin-bottom:30px; box-shadow:0 2px 5px rgba(0,0,0,0.05);">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <input type="text" id="searchInput" placeholder="🔍 Rechercher un partenaire..." class="form-control" style="padding:10px; width:300px; border:1px solid #ddd; border-radius:5px;">
                <button onclick="openModal()" style="padding:10px 20px; background:#4e73df; color:white; border:none; border-radius:5px; cursor:pointer;">➕ Ajouter Partenaire</button>
            </div>
        </div>

        <!-- Tableau -->
        <div class="content-section" style="background:white; padding:20px; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.05);">
            <div id="loadingSpinner" style="text-align:center; padding:40px;">⏳ Chargement...</div>
            <table class="data-table" style="width:100%; border-collapse:collapse; display:none;" id="partnerTable">
                <thead>
                    <tr style="background:#f8f9fa; border-bottom:2px solid #eee;">
                        <th style="padding:12px; text-align:left;">Logo</th>
                        <th style="padding:12px; text-align:left;">Nom</th>
                        <th style="padding:12px; text-align:left;">Type</th>
                        <th style="padding:12px; text-align:left;">Contact</th>
                        <th style="padding:12px; text-align:left;">Site Web</th>
                        <th style="padding:12px; text-align:center;">Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody"></tbody>
            </table>
        </div>

        <!-- Modal -->
        <div id="partnerModal" class="modal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5);">
            <div class="modal-content" style="background:white; width:600px; margin:50px auto; padding:30px; border-radius:10px; max-height:90vh; overflow-y:auto;">
                <div style="display:flex; justify-content:space-between; margin-bottom:20px; border-bottom:1px solid #eee; padding-bottom:10px;">
                    <h2 id="modalTitle" style="margin:0; color:#4e73df;">Nouveau Partenaire</h2>
                    <span onclick="closeModal()" style="cursor:pointer; font-size:1.5rem;">&times;</span>
                </div>
                
                <form id="partnerForm" enctype="multipart/form-data">
                    <input type="hidden" id="partnerId" name="id">
                    
                    <div class="form-group" style="margin-bottom:15px;">
                        <label style="display:block; font-weight:bold; margin-bottom:5px;">Nom <span style="color:red">*</span></label>
                        <input type="text" name="nom" id="nom" required style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
                    </div>

                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-bottom:15px;">
                        <div>
                            <label style="display:block; font-weight:bold; margin-bottom:5px;">Type</label>
                            <input type="text" name="type" id="type" placeholder="Ex: Entreprise, Université" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
                        </div>
                        <div>
                            <label style="display:block; font-weight:bold; margin-bottom:5px;">Date Partenariat</label>
                            <input type="date" name="date_partenariat" id="date_partenariat" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom:15px;">
                        <label style="display:block; font-weight:bold; margin-bottom:5px;">Logo</label>
                        <input type="file" name="logo" id="logo" accept="image/*" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
                        <small style="color:#666;">Laisser vide pour conserver l'actuel</small>
                    </div>

                    <div class="form-group" style="margin-bottom:15px;">
                        <label style="display:block; font-weight:bold; margin-bottom:5px;">Description</label>
                        <textarea name="description" id="description" rows="3" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;"></textarea>
                    </div>

                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-bottom:15px;">
                        <div>
                            <label style="display:block; font-weight:bold; margin-bottom:5px;">Email Contact</label>
                            <input type="email" name="email_contact" id="email_contact" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
                        </div>
                        <div>
                            <label style="display:block; font-weight:bold; margin-bottom:5px;">Site Web (URL)</label>
                            <input type="url" name="url" id="url" placeholder="https://..." style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
                        </div>
                    </div>

                    <div style="text-align:right; border-top:1px solid #eee; padding-top:15px;">
                        <button type="button" onclick="closeModal()" style="padding:10px 20px; background:#858796; color:white; border:none; border-radius:5px; margin-right:10px;">Annuler</button>
                        <button type="submit" style="padding:10px 20px; background:#4e73df; color:white; border:none; border-radius:5px;">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            let allPartners = [];

            document.addEventListener('DOMContentLoaded', () => {
                loadPartners();
                document.getElementById('searchInput').addEventListener('input', (e) => filterTable(e.target.value));
            });

            async function loadPartners() {
                try {
                    const res = await fetch('controllers/api.php?action=getPartners');
                    const json = await res.json();
                    if(json.success) {
                        allPartners = json.data;
                        renderTable(allPartners);
                        document.getElementById('loadingSpinner').style.display = 'none';
                        document.getElementById('partnerTable').style.display = 'table';
                    }
                } catch(e) { console.error(e); }
            }

            function renderTable(data) {
                const tbody = document.getElementById('tableBody');
                tbody.innerHTML = '';
                
                if(data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:20px;">Aucun partenaire</td></tr>';
                    return;
                }

                data.forEach(p => {
                    const logoHtml = p.logo ? `<img src="${p.logo}" style="height:40px; width:auto; border-radius:4px;">` : '🚫';
                    const linkHtml = p.url ? `<a href="${p.url}" target="_blank" style="color:#4e73df;">Visiter</a>` : '-';
                    
                    const tr = document.createElement('tr');
                    tr.style.borderBottom = '1px solid #eee';
                    tr.innerHTML = `
                        <td style="padding:10px;">${logoHtml}</td>
                        <td style="padding:10px; font-weight:bold;">${p.nom}</td>
                        <td style="padding:10px;">${p.type || '-'}</td>
                        <td style="padding:10px;">${p.email_contact || '-'}</td>
                        <td style="padding:10px;">${linkHtml}</td>
                        <td style="padding:10px; text-align:center;">
                            <button onclick="editPartner(${p.id})" style="background:none; border:none; cursor:pointer; margin-right:5px;">✏️</button>
                            <button onclick="deletePartner(${p.id})" style="background:none; border:none; cursor:pointer; color:red;">🗑️</button>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            }

            function filterTable(term) {
                const lower = term.toLowerCase();
                const filtered = allPartners.filter(p => p.nom.toLowerCase().includes(lower) || (p.type && p.type.toLowerCase().includes(lower)));
                renderTable(filtered);
            }

            function openModal(edit = false, id = null) {
                const modal = document.getElementById('partnerModal');
                const form = document.getElementById('partnerForm');
                const title = document.getElementById('modalTitle');
                
                if(edit && id) {
                    title.textContent = 'Modifier Partenaire';
                    const item = allPartners.find(p => p.id == id);
                    if(item) {
                        document.getElementById('partnerId').value = item.id;
                        document.getElementById('nom').value = item.nom;
                        document.getElementById('type').value = item.type;
                        document.getElementById('date_partenariat').value = item.date_partenariat;
                        document.getElementById('description').value = item.description;
                        document.getElementById('email_contact').value = item.email_contact;
                        document.getElementById('url').value = item.url;
                    }
                } else {
                    title.textContent = 'Nouveau Partenaire';
                    form.reset();
                    document.getElementById('partnerId').value = '';
                }
                modal.style.display = 'flex';
            }

            function closeModal() { document.getElementById('partnerModal').style.display = 'none'; }

            document.getElementById('partnerForm').addEventListener('submit', async function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                const id = document.getElementById('partnerId').value;
                const action = id ? 'updatePartner' : 'createPartner';
                const url = id ? `controllers/api.php?action=${action}&id=${id}` : `controllers/api.php?action=${action}`;

                try {
                    const res = await fetch(url, { method: 'POST', body: formData });
                    const json = await res.json();
                    if(json.success) {
                        closeModal();
                        loadPartners();
                        alert('✅ Enregistré !');
                    } else { alert('Erreur : ' + json.message); }
                } catch(e) { console.error(e); }
            });

            function editPartner(id) { openModal(true, id); }

            async function deletePartner(id) {
                if(!confirm("Supprimer ce partenaire ?")) return;
                try {
                    const res = await fetch(`controllers/api.php?action=deletePartner&id=${id}`, { method: 'POST' });
                    const json = await res.json();
                    if(json.success) loadPartners();
                } catch(e) { console.error(e); }
            }

            window.onclick = function(e) {
                if(e.target == document.getElementById('partnerModal')) closeModal();
            }
        </script>
        <?php
        return ob_get_clean();
    }
}
?>