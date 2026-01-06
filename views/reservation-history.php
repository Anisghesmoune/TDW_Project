<?php
// // views/reservation-history.php
// session_start();

// Vérification de l'authentification
// if (!isset($_SESSION['user_id'])) {
//     header('Location: ../login.php');
//     exit;
// }

require_once __DIR__ . '/../views/Sidebar.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historique des Réservations</title>
    <link rel="stylesheet" href="admin_dashboard.css">
    <style>
        /* Styles spécifiques repris de equipement_management.php pour cohérence */
        .content-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .filters-bar {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            align-items: center;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
        }

        .form-control {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            min-width: 150px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th, .data-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .data-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #4b5563;
        }

        .badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.85em;
            font-weight: 500;
        }
        .badge-success { background: #d1fae5; color: #065f46; } /* Confirmée */
        .badge-warning { background: #fef3c7; color: #92400e; } /* En attente */
        .badge-danger { background: #fee2e2; color: #b91c1c; }  /* Annulée */
        .badge-info { background: #dbeafe; color: #1e40af; }    /* En cours */
        .badge-secondary { background: #f3f4f6; color: #374151; } /* Terminée */

        .pagination {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin-top: 20px;
        }
        .pagination button {
            padding: 5px 10px;
            border: 1px solid #ddd;
            background: white;
            cursor: pointer;
            border-radius: 4px;
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
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>⚙️ Administration</h2>
            <span class="admin-badge">ADMINISTRATEUR</span>
        </div>
        <?php (new Sidebar("admin"))->render(); ?>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <div>
                <h1>Historique des Réservations</h1>
                <p style="color: #666;">Consultez et gérez toutes les réservations passées et futures.</p>
            </div>
            <div>
                <a href="equipement_management.php" class="btn btn-secondary">← Retour aux équipements</a>
                <a href="../logout.php" class="logout-btn">Déconnexion</a>
            </div>
        </div>

        <!-- Filtres -->
        <div class="content-section">
            <div class="filters-bar">
                <div>
                    <label style="font-size: 0.9em; font-weight: bold; display: block; margin-bottom: 5px;">Statut :</label>
                    <select id="filterStatus" class="form-control" onchange="loadReservations(1)">
                        <option value="">Tous les statuts</option>
                        <option value="en_conflit">⏳ En attente</option>
                        <option value="confirmé">✅ Confirmée</option>
                      
                        <option value="annulé">❌ Annulée</option>
                    </select>
                </div>

                <div>
                    <label style="font-size: 0.9em; font-weight: bold; display: block; margin-bottom: 5px;">Équipement :</label>
                    <select id="filterEquipment" class="form-control" onchange="loadReservations(1)">
                        <option value="0">Tous les équipements</option>
                        <!-- Rempli par JS -->
                    </select>
                </div>

                <div>
                    <label style="font-size: 0.9em; font-weight: bold; display: block; margin-bottom: 5px;">Utilisateur :</label>
                    <select id="filterUser" class="form-control" onchange="loadReservations(1)">
                        <option value="0">Tous les utilisateurs</option>
                        <!-- Rempli par JS -->
                    </select>
                </div>

                <div style="margin-top: 24px;">
                    <button class="btn btn-secondary" onclick="resetFilters()">🔄 Réinitialiser</button>
                </div>
            </div>

            <!-- Tableau -->
            <div id="loading" style="text-align: center; padding: 20px;">⏳ Chargement...</div>
            
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

            <!-- Pagination -->
            <div class="pagination" id="pagination"></div>
        </div>
    </div>

    <script>
        let currentPage = 1;
        
        document.addEventListener('DOMContentLoaded', () => {
            loadFiltersData(); // Charge les listes déroulantes
            loadReservations(1); // Charge la table
        });

        // 1. Charger les données pour les filtres (Equipements et Users)
        async function loadFiltersData() {
            try {
                // Charger Équipements
                const respEq = await fetch('../controllers/api.php?action=getEquipments');
                const dataEq = await respEq.json();
                if(dataEq.success) {
                    const select = document.getElementById('filterEquipment');
                    dataEq.data.forEach(eq => {
                        select.innerHTML += `<option value="${eq.id}">${eq.nom}</option>`;
                    });
                }

                // Charger Utilisateurs
                const respUser = await fetch('../controllers/api.php?action=getUsers');
                const dataUser = await respUser.json();
                const users = dataUser.data || dataUser; // Gestion structure API
                
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

        // 2. Charger les réservations avec filtres
        async function loadReservations(page) {
            currentPage = page;
            const status = document.getElementById('filterStatus').value;
            const equipment = document.getElementById('filterEquipment').value;
            const user = document.getElementById('filterUser').value;

            document.getElementById('loading').style.display = 'block';
            document.getElementById('reservationsTable').style.display = 'none';

            // Construction de l'URL
            let url = `../controllers/api.php?action=getAllReservations&page=${page}`;
            if(status) url += `&status=${status}`;
            if(equipment != 0) url += `&equipment=${equipment}`;
            if(user != 0) url += `&user=${user}`;

            try {
                const response = await fetch(url);
                const result = await response.json();

                if(result) {

                    renderTable(result.reservations); // Note: le controller renvoie 'reservations' dans data
                    renderPagination(result.totalPages, result.currentPage);
                } else {
                    document.getElementById('tableBody').innerHTML = '<tr><td colspan="7" style="text-align:center">Erreur chargement</td></tr>';
                }
            } catch (error) {
                console.error(error);
                document.getElementById('tableBody').innerHTML = '<tr><td colspan="7" style="text-align:center">Erreur serveur</td></tr>';
            } finally {
                document.getElementById('loading').style.display = 'none';
                document.getElementById('reservationsTable').style.display = 'table';
            }
        }

        function renderTable(reservations) {
            const tbody = document.getElementById('tableBody');
            tbody.innerHTML = '';

            if(reservations.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding: 20px;">Aucune réservation trouvée</td></tr>';
                return;
            }

            reservations.forEach(res => {
                const debut = new Date(res.date_debut).toLocaleDateString('fr-FR');
                const fin = new Date(res.date_fin).toLocaleDateString('fr-FR');
                
                let badgeClass = 'badge-secondary';
                if(res.statut === 'confirmé') badgeClass = 'badge-success';
                if(res.statut === 'annulé') badgeClass = 'badge-danger';
                if(res.statut === 'en_conflit') badgeClass = 'badge-info';

                // Bouton annuler seulement si actif
                let actions = '';
                if(res.statut === 'confirmé' || res.statut === 'en_attente') {
                    actions = `<button class="btn btn-sm" style="background:#fee2e2; color:#b91c1c; border:none; padding:5px 10px; border-radius:4px; cursor:pointer;" onclick="cancelReservation(${res.id})">Annuler</button>`;
                } else if (res.statut === 'annulé' || res.statut === 'terminée') {
                    
                     actions = `<button onclick="deleteReservation(${res.id})">🗑️</button>`;
                     actions = '-';
                }

                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>#${res.id}</td>
                    <td><strong>${res.equipment_nom || 'Inconnu'}</strong></td>
                    <td>${res.user_nom || ''} ${res.user_prenom || ''}</td>
                    <td>${debut} au ${fin}</td>
                    <td><span class="badge ${badgeClass}">${res.statut.toUpperCase()}</span></td>
                    <td><small>${res.notes || '-'}</small></td>
                    <td>${actions}</td>
                `;
                tbody.appendChild(tr);
            });
        }

        function renderPagination(totalPages, current) {
            const container = document.getElementById('pagination');
            container.innerHTML = '';
            
            if(totalPages <= 1) return;

            for(let i = 1; i <= totalPages; i++) {
                const btn = document.createElement('button');
                btn.textContent = i;
                if(i == current) btn.classList.add('active');
                btn.onclick = () => loadReservations(i);
                container.appendChild(btn);
            }
        }

        async function cancelReservation(id) {
            if(!confirm("Confirmer l'annulation de la réservation #" + id + " ?")) return;

            try {
                const response = await fetch(`../controllers/api.php?action=cancelReservation&id=${id}`, {method: 'POST'});
                const result = await response.json();
                if(result.success) {
                    loadReservations(currentPage); // Recharger la page courante
                    alert("Réservation annulée.");
                } else {
                    alert("Erreur: " + result.message);
                }
            } catch (e) {
                console.error(e);
            }
        }

        function resetFilters() {
            document.getElementById('filterStatus').value = "";
            document.getElementById('filterEquipment').value = "0";
            document.getElementById('filterUser').value = "0";
            loadReservations(1);
        }
    </script>
</body>
</html>