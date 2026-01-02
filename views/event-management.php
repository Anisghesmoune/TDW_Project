<?php
// views/event-management.php
session_start();
// Vérification session (décommentez si nécessaire)
// if (!isset($_SESSION['user_id'])) header('Location: ../login.php');
require_once '../views/Sidebar.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Événements</title>
    <link rel="stylesheet" href="admin_dashboard.css">
    <style>
        .badge { padding: 4px 8px; border-radius: 12px; font-size: 0.85em; font-weight: bold; }
        .badge-publie { background: #d1fae5; color: #065f46; } /* programmé */
        .badge-programme { background: #dbeafe; color: #1e40af; }
        .badge-termine { background: #f3f4f6; color: #374151; }
        .badge-annule { background: #fee2e2; color: #b91c1c; }
        
        /* Style pour les selects */
        select.form-control { background-color: white; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header"><h2>📅 Événements</h2></div>
        <?php (new Sidebar("admin"))->render(); ?>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <h1>Événements & Communications</h1>
            <div>
                <button class="btn btn-secondary" onclick="triggerReminders()">🔔 Envoyer Rappels</button>
                <a href="../logout.php" class="logout-btn">Déconnexion</a>
            </div>
        </div>

        <div class="content-section">
            <div style="display:flex; gap:10px; margin-bottom:20px;">
                <input type="text" id="search" placeholder="Rechercher..." oninput="loadEvents()" class="form-control">
                <select id="filterStatut" onchange="loadEvents()" class="form-control">
                    <option value="">Tous les statuts</option>
                    <option value="programmé">Programmé</option>
                    <option value="terminé">Terminé</option>
                    <option value="annulé">Annulé</option>
                </select>
                <button class="btn btn-primary" onclick="openModal()">➕ Créer Événement</button>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>Titre</th>
                        <th>Type</th>
                        <th>Date Début</th>
                        <th>localisation</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="eventTable"></tbody>
            </table>
        </div>
    </div>

    <!-- MODAL ÉVÉNEMENT -->
    <div id="eventModal" class="modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000;">
        <div class="modal-content" style="background:white; width:600px; margin:50px auto; padding:20px; border-radius:8px;">
            <h2 id="modalTitle">Nouvel Événement</h2>
            <form id="eventForm">
                <input type="hidden" name="id" id="eventId">
                
                <div class="form-group">
                    <label>Titre</label>
                    <input type="text" name="titre" id="titre" required class="form-control" style="width:100%;">
                </div>
                
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="description" class="form-control" style="width:100%;"></textarea>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                    <div>
                        <label>Date Début</label>
                        <input type="datetime-local" name="date_debut" id="date_debut" required class="form-control" style="width:100%;">
                    </div>
                    <div>
                        <label>Date Fin</label>
                        <input type="datetime-local" name="date_fin" id="date_fin" class="form-control" style="width:100%;">
                    </div>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-top:10px;">
                    <div>
                        <label>localisation</label>
                        <input type="text" name="localisation" id="localisation" required class="form-control" style="width:100%;">
                    </div>
                    <div>
                        <label>Capacité Max</label>
                        <input type="number" name="capacite_max" id="capacite_max" class="form-control" style="width:100%;">
                    </div>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-top:10px;">
                    <div>
                        <label>Type</label>
                        <!-- Ce select est maintenant rempli dynamiquement par JS -->
                        <select name="id_type" id="id_type" class="form-control" style="width:100%;" required>
                            <option value="">Chargement...</option>
                        </select>
                    </div>
                    <div>
                        <label>Statut</label>
                        <select name="statut" id="statut" class="form-control" style="width:100%;">
                            <option value="programmé">Programmé</option>
                            <option value="terminé">Terminé</option>
                            <option value="annulé">Annulé</option>
                        </select>
                    </div>
                </div>

                <div style="margin-top:20px; text-align:right;">
                    <button type="button" onclick="closeModal()" class="btn btn-secondary">Annuler</button>
                    <button type="button" onclick="saveEvent()" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL PARTICIPANTS -->
    <div id="participantsModal" class="modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000;">
        <div class="modal-content" style="background:white; width:700px; margin:50px auto; padding:20px; border-radius:8px;">
            <h2>Gestion des Inscriptions</h2>
            
            <div style="background:#f8f9fa; padding:15px; margin-bottom:15px; border-radius:5px;">
                <h4>Ajouter un participant</h4>
                <div style="display:flex; gap:10px;">
                    <select id="userSelect" class="form-control" style="flex:1;">
                        <option value="">-- Choisir un membre --</option>
                    </select>
                    <button onclick="addParticipant()" class="btn btn-primary">Inscrire</button>
                </div>
            </div>
            
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Date Inscription</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="participantsTable"></tbody>
            </table>
            
            <div style="margin-top:20px; text-align:right;">
                <button onclick="document.getElementById('participantsModal').style.display='none'" class="btn btn-secondary">Fermer</button>
            </div>
        </div>
    </div>

    <script>
        let currentEventId = null;

        document.addEventListener('DOMContentLoaded', () => {
            loadEventTypes(); // Charger les types d'abord
            loadEvents();     // Puis les événements
            loadUsers();      // Charger les utilisateurs pour le modal participants
        });

        // 1. Charger les types d'événements pour le SELECT
        async function loadEventTypes() {
            try {
                const res = await fetch('../controllers/api.php?action=getEventTypes');
                const json = await res.json();
                const select = document.getElementById('id_type');
                
                select.innerHTML = '<option value="">-- Sélectionner un type --</option>';
                
                if(json.success && json.data) {
                    json.data.forEach(type => {
                        select.innerHTML += `<option value="${type.id}">${type.nom}</option>`;
                    });
                }
            } catch(e) {
                console.error("Erreur chargement types", e);
            }
        }

        // 2. Charger les événements (CRUD)
        async function loadEvents() {
            try {
                const res = await fetch('../controllers/api.php?action=getEvents');
                const json = await res.json();
                const tbody = document.getElementById('eventTable');
                tbody.innerHTML = '';

                // Filtres JS simples (barre recherche et statut)
                const searchTerm = document.getElementById('search').value.toLowerCase();
                const filterStatut = document.getElementById('filterStatut').value;

                if(json.success && json.data) {
                    json.data.forEach(evt => {
                        // Application des filtres
                        if (filterStatut && evt.statut !== filterStatut) return;
                        if (searchTerm && !evt.titre.toLowerCase().includes(searchTerm)) return;

                        let badgeClass = 'badge-programme';
                        if(evt.statut === 'terminé') badgeClass = 'badge-termine';
                        if(evt.statut === 'annulé') badgeClass = 'badge-annule';

                        // AFFICHAGE DU NOM DU TYPE (evt.type_nom grâce au JOIN)
                        const typeNom = evt.type_nom || 'Type Inconnu (' + evt.id_type + ')';

                        tbody.innerHTML += `
                            <tr>
                                <td><strong>${evt.titre}</strong></td>
                                <td>${typeNom}</td>
                                <td>${new Date(evt.date_debut).toLocaleString('fr-FR')}</td>
                                <td>${evt.localisation || '-'}</td>
                                <td><span class="badge ${badgeClass}">${evt.statut}</span></td>
                                <td>
                                    <button onclick="editEvent(${evt.id})" class="btn-sm" title="Modifier">✏️</button>
                                    <button onclick="manageParticipants(${evt.id})" class="btn-sm" style="background:#4e73df; color:white;" title="Inscrits">👥</button>
                                    <button onclick="deleteEvent(${evt.id})" class="btn-sm" style="color:red; background:none; border:none;" title="Supprimer">🗑️</button>
                                </td>
                            </tr>
                        `;
                    });
                } else {
                    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center">Aucun événement trouvé</td></tr>';
                }
            } catch(e) {
                console.error("Erreur chargement événements", e);
            }
        }

        // 3. Charger les utilisateurs pour le Select du Modal Participants
        async function loadUsers() {
            try {
                const res = await fetch('../controllers/api.php?action=getUsers');
                const json = await res.json();
                const select = document.getElementById('userSelect');
                
                select.innerHTML = '<option value="">-- Choisir un membre --</option>';
                
                // Gestion du format de réponse (data ou racine)
                const users = json.users || json;
                
                if(Array.isArray(users)) {
                    users.forEach(u => {
                        select.innerHTML += `<option value="${u.id}">${u.nom.toUpperCase()} ${u.prenom} (${u.email})</option>`;
                    });
                }
            } catch(e) {
                console.error("Erreur chargement utilisateurs", e);
            }
        }

        // --- FONCTIONS CRUD ---

        async function saveEvent() {
            const form = document.getElementById('eventForm');
            // Hack pour transformer FormData en JSON car l'API attend du JSON
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());
            
            const id = document.getElementById('eventId').value;
            const action = id ? 'updateEvent&id='+id : 'createEvent';

            try {
                const res = await fetch(`../controllers/api.php?action=${action}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                
                const json = await res.json();
                if(json.success) {
                    closeModal();
                    loadEvents();
                    alert("Enregistré avec succès !");
                } else {
                    alert("Erreur: " + json.message);
                }
            } catch(e) {
                console.error(e);
                alert("Erreur serveur lors de l'enregistrement");
            }
        }

        async function editEvent(id) {
            try {
                const res = await fetch(`../controllers/api.php?action=getEvent&id=${id}`);
                const json = await res.json();
                if(json.success) {
                    const e = json.data;
                    document.getElementById('eventId').value = e.id;
                    document.getElementById('titre').value = e.titre;
                    document.getElementById('description').value = e.description;
                    // Formatage date pour input datetime-local (YYYY-MM-DDTHH:MM)
                    document.getElementById('date_debut').value = e.date_debut ? e.date_debut.replace(' ', 'T').substring(0, 16) : '';
                    document.getElementById('date_fin').value = e.date_fin ? e.date_fin.replace(' ', 'T').substring(0, 16) : '';
                    document.getElementById('localisation').value = e.localisation;
                    document.getElementById('capacite_max').value = e.capacite_max;
                    document.getElementById('id_type').value = e.id_type;
                    document.getElementById('statut').value = e.statut;
                    
                    document.getElementById('modalTitle').innerText = "Modifier Événement";
                    openModal();
                }
            } catch(e) { console.error(e); }
        }

        async function deleteEvent(id) {
            if(!confirm("Supprimer cet événement ?")) return;
            await fetch(`../controllers/api.php?action=deleteEvent&id=${id}`);
            loadEvents();
        }

        // --- GESTION PARTICIPANTS ---

        async function manageParticipants(id) {
            currentEventId = id;
            document.getElementById('participantsModal').style.display = 'block';
            loadParticipants(id);
        }

        async function loadParticipants(eventId) {
            const res = await fetch(`../controllers/api.php?action=getEventParticipants&id=${eventId}`);
            const json = await res.json();
            const tbody = document.getElementById('participantsTable');
            tbody.innerHTML = '';

            if(json.success && json.data && json.data.length > 0) {
                json.data.forEach(p => {
                    tbody.innerHTML += `
                        <tr>
                            <td>${p.nom} ${p.prenom}</td>
                            <td>${p.email}</td>
                            <td>${new Date(p.date_inscription).toLocaleDateString()}</td>
                            <td><button onclick="removeParticipant(${p.id})" style="color:red; border:none; background:none; cursor:pointer;">❌ Retirer</button></td>
                        </tr>
                    `;
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; color:#666;">Aucun inscrit pour le moment.</td></tr>';
            }
        }

        async function addParticipant() {
            const userId = document.getElementById('userSelect').value;
            if(!userId || !currentEventId) {
                alert("Veuillez sélectionner un utilisateur.");
                return;
            }

            const res = await fetch('../controllers/api.php?action=registerParticipant', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ event_id: currentEventId, user_id: userId })
            });
            const json = await res.json();
            
            if(json.success) {
                loadParticipants(currentEventId);
            } else {
                alert("Erreur : " + json.message);
            }
        }

        async function removeParticipant(userId) {
            if(!confirm("Désinscrire cet utilisateur ?")) return;
            const res = await fetch('../controllers/api.php?action=unregisterParticipant', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ event_id: currentEventId, user_id: userId })
            });
            loadParticipants(currentEventId);
        }

        // --- UTILITAIRES ---

        async function triggerReminders() {
            const res = await fetch('../controllers/api.php?action=sendEventReminders');
            const json = await res.json();
            alert(json.message);
        }

        function openModal() { document.getElementById('eventModal').style.display = 'block'; }
        function closeModal() { 
            document.getElementById('eventModal').style.display = 'none'; 
            document.getElementById('eventForm').reset();
            document.getElementById('eventId').value = '';
            document.getElementById('modalTitle').innerText = "Nouvel Événement";
        }
        
        // Fermeture si clic en dehors du modal
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = "none";
            }
        }
    </script>
</body>
</html>