<?php
require_once __DIR__ . '/../views/public/View.php';
require_once __DIR__ . '/../views/public/components/UIHeader.php';
require_once __DIR__ . '/../views/public/components/UIFooter.php';

class ReservationHistoryView extends View {

   
    public function render() {
        $config = $this->data['config'] ?? [];
        $menuData = $this->data['menu'] ?? [];
        $pageTitle = $this->data['title'] ?? 'Historique des Réservations';

        $customCss = [
            'views/admin_dashboard.css',
            'views/landingPage.css'    
        ];

        $header = new UIHeader($pageTitle, $config, $menuData, $customCss);
        echo $header->render();

        echo '<main style="width: 100%; padding: 40px 20px; box-sizing: border-box; background-color: #f8f9fc; min-height: 80vh;">';
        echo $this->content();
        echo '</main>';

      
    }

   
    protected function content() {
        ob_start();
        ?>
        
        <style>
            .content-section {
                background: white;
                padding: 30px;
                border-radius: 10px;
                box-shadow: 0 4px 15px rgba(0,0,0,0.05);
                margin-bottom: 20px;
                max-width: 1400px;
                margin: 0 auto;
            }
            
            .filters-bar {
                display: flex;
                gap: 20px;
                margin-bottom: 30px;
                flex-wrap: wrap;
                align-items: flex-end;
                background: #f8f9fa;
                padding: 20px;
                border-radius: 8px;
                border: 1px solid #e3e6f0;
            }

            .filter-group { flex: 1; min-width: 200px; }
            .filter-group label { font-size: 0.9em; font-weight: bold; display: block; margin-bottom: 8px; color: #4e73df; }
            
            .form-control {
                padding: 10px 12px;
                border: 1px solid #ddd;
                border-radius: 5px;
                width: 100%;
                box-sizing: border-box;
                font-size: 14px;
            }

            .data-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
            }

            .data-table th, .data-table td {
                padding: 15px;
                text-align: left;
                border-bottom: 1px solid #eee;
            }

            .data-table th {
                background-color: #f8f9fa;
                font-weight: 600;
                color: #4b5563;
                text-transform: uppercase;
                font-size: 0.85em;
            }

            .badge { padding: 5px 10px; border-radius: 15px; font-size: 0.8em; font-weight: 600; }
            .badge-success { background: #d1fae5; color: #065f46; } /* Confirmée */
            .badge-warning { background: #fef3c7; color: #92400e; } /* En attente */
            .badge-danger { background: #fee2e2; color: #b91c1c; }  /* Annulée */
            .badge-info { background: #dbeafe; color: #1e40af; }    /* En conflit */
            .badge-secondary { background: #f3f4f6; color: #374151; } /* Terminée */

            .pagination {
                display: flex;
                justify-content: center;
                gap: 5px;
                margin-top: 30px;
            }
            .pagination button {
                padding: 8px 12px;
                border: 1px solid #ddd;
                background: white;
                cursor: pointer;
                border-radius: 4px;
                font-weight: 500;
                color: #4e73df;
            }
            .pagination button.active {
                background: #4e73df;
                color: white;
                border-color: #4e73df;
            }
            .pagination button:disabled {
                opacity: 0.5;
                cursor: not-allowed;
            }
            .pagination button:hover:not(:disabled):not(.active) {
                background-color: #f8f9fa;
            }

            .btn-secondary { background: #858796; color: white; padding: 10px 20px; border-radius: 5px; border: none; cursor: pointer; text-decoration: none; display: inline-block; }
            .btn-secondary:hover { background: #6c757d; }
        </style>

        <div class="top-bar" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; max-width: 1400px; margin: 0 auto 30px auto;">
            <div>
                <h1 style="margin: 0; color: #2c3e50;">Historique des Réservations</h1>
                <p style="color: #666; margin-top: 5px;">Consultez et gérez toutes les réservations passées et futures.</p>
            </div>
            <div>
                <a href="index.php?route=equipements" class="btn-secondary">← Retour aux équipements</a>
            </div>
        </div>

        <div class="content-section">
            
            <div class="filters-bar">
                <div class="filter-group">
                    <label>Statut :</label>
                    <select id="filterStatus" class="form-control" onchange="loadReservations(1)">
                        <option value="">Tous les statuts</option>
                        <option value="en_conflit">⏳ En attente / Conflit</option>
                        <option value="confirmé">✅ Confirmée</option>
                        <option value="annulé">❌ Annulée</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label>Équipement :</label>
                    <select id="filterEquipment" class="form-control" onchange="loadReservations(1)">
                        <option value="0">Tous les équipements</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label>Utilisateur :</label>
                    <select id="filterUser" class="form-control" onchange="loadReservations(1)">
                        <option value="0">Tous les utilisateurs</option>
                    </select>
                </div>

                <div style="margin-bottom: 2px;">
                    <button class="btn-secondary" onclick="resetFilters()" style="padding: 10px 15px;">
                        🔄 Réinitialiser
                    </button>
                </div>
            </div>

            <!-- Tableau -->
            <div id="loading" style="text-align: center; padding: 40px; font-size: 1.2em; color: #666;">
                ⏳ Chargement des données...
            </div>
            
            <div style="overflow-x: auto;">
                <table class="data-table" id="reservationsTable" style="display: none;">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Équipement</th>
                            <th>Utilisateur</th>
                            <th>Dates</th>
                            <th>Statut</th>
                            <th>Notes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody"></tbody>
                </table>
            </div>

            <div class="pagination" id="pagination"></div>
        </div>

        <script>
            let currentPage = 1;
            
            document.addEventListener('DOMContentLoaded', () => {
                loadFiltersData(); 
                loadReservations(1); 
            });

            async function loadFiltersData() {
                try {
                    const respEq = await fetch('../controllers/api.php?action=getEquipments');
                    const dataEq = await respEq.json();
                    if(dataEq.success) {
                        const select = document.getElementById('filterEquipment');
                        dataEq.data.forEach(eq => {
                            select.innerHTML += `<option value="${eq.id}">${eq.nom}</option>`;
                        });
                    }

                    const respUser = await fetch('../controllers/api.php?action=getUsers');
                    const dataUser = await respUser.json();
                    const users = dataUser.users || dataUser.data || (Array.isArray(dataUser) ? dataUser : []);
                    
                    if(Array.isArray(users)) {
                        const select = document.getElementById('filterUser');
                        users.forEach(u => {
                            select.innerHTML += `<option value="${u.id}">${u.nom.toUpperCase()} ${u.prenom}</option>`;
                        });
                    }
                } catch (e) {
                    console.error("Erreur chargement filtres", e);
                }
            }

            async function loadReservations(page) {
                currentPage = page;
                const status = document.getElementById('filterStatus').value;
                const equipment = document.getElementById('filterEquipment').value;
                const user = document.getElementById('filterUser').value;

                document.getElementById('loading').style.display = 'block';
                document.getElementById('reservationsTable').style.display = 'none';
                document.getElementById('pagination').innerHTML = '';

                let url = `../controllers/api.php?action=getAllReservations&page=${page}`;
                if(status) url += `&status=${status}`;
                if(equipment != 0) url += `&equipment=${equipment}`;
                if(user != 0) url += `&user=${user}`;

                try {
                    const response = await fetch(url);
                    const result = await response.json();

                    if(result) {
                        const list = result.reservations || result.data || [];
                        const total = result.totalPages || 1;
                        const current = result.currentPage || 1;

                        renderTable(list);
                        renderPagination(total, current);
                    } else {
                        document.getElementById('tableBody').innerHTML = '<tr><td colspan="7" style="text-align:center">Erreur chargement (données vides)</td></tr>';
                    }
                } catch (error) {
                    console.error(error);
                    document.getElementById('tableBody').innerHTML = '<tr><td colspan="7" style="text-align:center; color:red;">Erreur serveur</td></tr>';
                } finally {
                    document.getElementById('loading').style.display = 'none';
                    document.getElementById('reservationsTable').style.display = 'table';
                }
            }

            function renderTable(reservations) {
                const tbody = document.getElementById('tableBody');
                tbody.innerHTML = '';

                if(reservations.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding: 20px; color: #888;">Aucune réservation trouvée</td></tr>';
                    return;
                }

                reservations.forEach(res => {
                    const debut = new Date(res.date_debut).toLocaleDateString('fr-FR');
                    const fin = new Date(res.date_fin).toLocaleDateString('fr-FR');
                    
                    let badgeClass = 'badge-secondary';
                    if(res.statut === 'confirmé') badgeClass = 'badge-success';
                    if(res.statut === 'annulé') badgeClass = 'badge-danger';
                    if(res.statut === 'en_conflit') badgeClass = 'badge-warning'; 

                    let actions = '-';
                    if(res.statut === 'confirmé' || res.statut === 'en_conflit') {
                        actions = `<button class="btn-sm" style="background:none; border:none; cursor:pointer;" title="Annuler" onclick="cancelReservation(${res.id})">❌</button>`;
                    }

                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>#${res.id}</td>
                        <td style="font-weight:500;">${res.equipment_nom || 'Inconnu'}</td>
                        <td>${res.user_nom || ''} ${res.user_prenom || ''}</td>
                        <td>${debut} → ${fin}</td>
                        <td><span class="badge ${badgeClass}">${res.statut.toUpperCase()}</span></td>
                        <td style="font-size:0.9em; color:#666;">${res.notes || '-'}</td>
                        <td style="text-align:center;">${actions}</td>
                    `;
                    tbody.appendChild(tr);
                });
            }

            function renderPagination(totalPages, current) {
                const container = document.getElementById('pagination');
                container.innerHTML = '';
                
                if(totalPages <= 1) return;

                if(current > 1) {
                    const prev = document.createElement('button');
                    prev.textContent = '«';
                    prev.onclick = () => loadReservations(current - 1);
                    container.appendChild(prev);
                }

                for(let i = 1; i <= totalPages; i++) {
                    if (i === 1 || i === totalPages || (i >= current - 2 && i <= current + 2)) {
                        const btn = document.createElement('button');
                        btn.textContent = i;
                        if(i == current) btn.classList.add('active');
                        btn.onclick = () => loadReservations(i);
                        container.appendChild(btn);
                    } else if (i === current - 3 || i === current + 3) {
                        const span = document.createElement('span');
                        span.textContent = '...';
                        span.style.padding = '5px';
                        container.appendChild(span);
                    }
                }

                if(current < totalPages) {
                    const next = document.createElement('button');
                    next.textContent = '»';
                    next.onclick = () => loadReservations(current + 1);
                    container.appendChild(next);
                }
            }

            async function cancelReservation(id) {
                if(!confirm("Confirmer l'annulation de la réservation #" + id + " ?")) return;

                try {
                    const response = await fetch(`../controllers/api.php?action=cancelReservation&id=${id}`, {method: 'POST'});
                    const result = await response.json();
                    if(result.success) {
                        loadReservations(currentPage); 
                    } else {
                        alert("Erreur: " + result.message);
                    }
                } catch (e) {
                    console.error(e);
                    alert("Erreur serveur lors de l'annulation");
                }
            }

            function resetFilters() {
                document.getElementById('filterStatus').value = "";
                document.getElementById('filterEquipment').value = "0";
                document.getElementById('filterUser').value = "0";
                loadReservations(1);
            }
        </script>
        <?php
        return ob_get_clean();
    }
}
?>