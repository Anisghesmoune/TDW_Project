<?php
// Imports des dépendances
require_once __DIR__ . '/../views/public/View.php';
require_once __DIR__ . '/../views/public/components/UIHeader.php';
require_once __DIR__ . '/../views/public/components/UIFooter.php';

class EventAdminView extends View {

    /**
     * Méthode principale pour structurer la page
     */
    public function render() {
        // Extraction des données
        $config = $this->data['config'] ?? [];
        $menuData = $this->data['menu'] ?? [];
        $pageTitle = $this->data['title'] ?? 'Gestion des Événements';

        // CSS spécifiques
        $customCss = [
            'views/admin_dashboard.css', 
            'views/modelAddUser.css',    
            'assets/css/public.css',
            'views/landingPage.css'      
        ];

        // 1. Rendu du Header (Remplace la Sidebar)
        $header = new UIHeader($pageTitle, $config, $menuData, $customCss);
        echo $header->render();

        // 2. Contenu Principal
        echo '<main style="width: 100%; padding: 40px 20px; box-sizing: border-box; background-color: #f8f9fc; min-height: 80vh;">';
        echo $this->content();
        echo '</main>';

        // 3. Rendu du Footer
        $footer = new UIFooter($config, $menuData);
        echo $footer->render();
    }

    /**
     * Contenu spécifique : Tableau, Modales, JS
     */
    protected function content() {
        ob_start();
        ?>
        
        <!-- Styles internes -->
        <style>
            .badge { padding: 4px 8px; border-radius: 12px; font-size: 0.85em; font-weight: bold; }
            .badge-publie { background: #d1fae5; color: #065f46; } /* programmé */
            .badge-programme { background: #dbeafe; color: #1e40af; }
            .badge-termine { background: #f3f4f6; color: #374151; }
            .badge-annule { background: #fee2e2; color: #b91c1c; }
            
            /* Style pour les selects */
            select.form-control { background-color: white; }
            
            /* Ajustement Dashboard sans Sidebar */
            .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 1px solid #eee; padding-bottom: 20px; }
            
            /* Modale */
            .modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; backdrop-filter: blur(2px); }
            .modal-content { background:white; width:600px; margin:50px auto; padding:30px; border-radius:10px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); max-height: 90vh; overflow-y: auto; }
            
            .btn-sm { padding: 5px 10px; border-radius: 4px; cursor: pointer; border: 1px solid #ddd; background: white; margin-right: 5px; }
            .btn-sm:hover { background: #f8f9fa; }
        </style>

        <!-- Top Bar Interne -->
        <div class="top-bar">
            <div>
                <h1 style="margin: 0; color: #2c3e50;">Événements & Communications</h1>
                <p style="color: #666; margin-top: 5px;">Gestion du planning et des inscriptions</p>
            </div>
            <div>
                <button class="btn btn-secondary" onclick="triggerReminders()" style="background: #f6c23e; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;">
                    🔔 Envoyer Rappels
                </button>
            </div>
        </div>

        <div class="content-section" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
            <div style="display:flex; gap:10px; margin-bottom:20px; flex-wrap: wrap;">
                <input type="text" id="search" placeholder="Rechercher..." oninput="loadEvents()" class="form-control" style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                
                <select id="filterStatut" onchange="loadEvents()" class="form-control" style="padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                    <option value="">Tous les statuts</option>
                    <option value="programmé">Programmé</option>
                    <option value="terminé">Terminé</option>
                    <option value="annulé">Annulé</option>
                </select>
                
                <button class="btn btn-primary" onclick="openModal()" style="background: #4e73df; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;">
                    ➕ Créer Événement
                </button>
            </div>

            <div style="overflow-x: auto;">
                <table class="data-table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f8f9fa; border-bottom: 2px solid #e3e6f0;">
                            <th style="padding: 12px; text-align: left;">Titre</th>
                            <th style="padding: 12px; text-align: left;">Type</th>
                            <th style="padding: 12px; text-align: left;">Date Début</th>
                            <th style="padding: 12px; text-align: left;">Localisation</th>
                            <th style="padding: 12px; text-align: left;">Statut</th>
                            <th style="padding: 12px; text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="eventTable"></tbody>
                </table>
            </div>
        </div>

        <!-- MODAL ÉVÉNEMENT -->
        <div id="eventModal" class="modal">
            <div class="modal-content">
                <h2 id="modalTitle" style="color: #4e73df; margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 15px;">Nouvel Événement</h2>
                <form id="eventForm">
                    <input type="hidden" name="id" id="eventId">
                    
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display:block; margin-bottom:5px; font-weight:bold;">Titre</label>
                        <input type="text" name="titre" id="titre" required class="form-control" style="width:100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display:block; margin-bottom:5px; font-weight:bold;">Description</label>
                        <textarea name="description" id="description" class="form-control" style="width:100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;" rows="3"></textarea>
                    </div>

                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-bottom: 15px;">
                        <div>
                            <label style="display:block; margin-bottom:5px; font-weight:bold;">Date Début</label>
                            <input type="datetime-local" name="date_debut" id="date_debut" required class="form-control" style="width:100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                        </div>
                        <div>
                            <label style="display:block; margin-bottom:5px; font-weight:bold;">Date Fin</label>
                            <input type="datetime-local" name="date_fin" id="date_fin" class="form-control" style="width:100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-bottom: 15px;">
                        <div>
                            <label style="display:block; margin-bottom:5px; font-weight:bold;">Localisation</label>
                            <input type="text" name="localisation" id="localisation" required class="form-control" style="width:100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                        </div>
                        <div>
                            <label style="display:block; margin-bottom:5px; font-weight:bold;">Capacité Max</label>
                            <input type="number" name="capacite_max" id="capacite_max" class="form-control" style="width:100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-bottom: 15px;">
                        <div>
                            <label style="display:block; margin-bottom:5px; font-weight:bold;">Type</label>
                            <!-- Ce select est rempli dynamiquement par JS -->
                            <select name="id_type" id="id_type" class="form-control" style="width:100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;" required>
                                <option value="">Chargement...</option>
                            </select>
                        </div>
                        <div>
                            <label style="display:block; margin-bottom:5px; font-weight:bold;">Statut</label>
                            <select name="statut" id="statut" class="form-control" style="width:100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                                <option value="programmé">Programmé</option>
                                <option value="terminé">Terminé</option>
                                <option value="annulé">Annulé</option>
                            </select>
                        </div>
                    </div>

                    <div style="margin-top:20px; text-align:right; border-top: 1px solid #eee; padding-top: 20px;">
                        <button type="button" onclick="closeModal()" class="btn btn-secondary" style="margin-right: 10px;">Annuler</button>
                        <button type="button" onclick="saveEvent()" class="btn btn-primary" style="background: #4e73df; color: white; border: none; padding: 10px 20px; border-radius: 5px;">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL PARTICIPANTS -->
        <div id="participantsModal" class="modal">
            <div class="modal-content" style="width: 700px;">
                <h2 style="color: #4e73df; margin-top: 0;">Gestion des Inscriptions</h2>
                
                <div style="background:#f8f9fa; padding:15px; margin-bottom:15px; border-radius:5px; border: 1px solid #e3e6f0;">
                    <h4 style="margin-top: 0;">Ajouter un participant</h4>
                    <div style="display:flex; gap:10px;">
                        <select id="userSelect" class="form-control" style="flex:1; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                            <option value="">-- Choisir un membre --</option>
                        </select>
                        <button onclick="addParticipant()" class="btn btn-primary" style="background: #1cc88a; color: white; border: none; padding: 10px 20px; border-radius: 5px;">Inscrire</button>
                    </div>
                </div>
                
                <div style="max-height: 300px; overflow-y: auto;">
                    <table class="data-table" style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #f8f9fa;">
                                <th style="padding: 10px;">Nom</th>
                                <th style="padding: 10px;">Email</th>
                                <th style="padding: 10px;">Date Inscription</th>
                                <th style="padding: 10px;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="participantsTable"></tbody>
                    </table>
                </div>
                
                <div style="margin-top:20px; text-align:right; border-top: 1px solid #eee; padding-top: 20px;">
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

                            // AFFICHAGE DU NOM DU TYPE
                            const typeNom = evt.type_nom || 'Type Inconnu';

                            tbody.innerHTML += `
                                <tr style="border-bottom: 1px solid #eee;">
                                    <td style="padding: 10px;"><strong>${evt.titre}</strong></td>
                                    <td style="padding: 10px;">${typeNom}</td>
                                    <td style="padding: 10px;">${new Date(evt.date_debut).toLocaleString('fr-FR')}</td>
                                    <td style="padding: 10px;">${evt.localisation || '-'}</td>
                                    <td style="padding: 10px;"><span class="badge ${badgeClass}">${evt.statut}</span></td>
                                    <td style="padding: 10px; text-align: center;">
                                        <button onclick="editEvent(${evt.id})" class="btn-sm" title="Modifier">✏️</button>
                                        <button onclick="manageParticipants(${evt.id})" class="btn-sm" style="background:#4e73df; color:white; border:none;" title="Inscrits">👥</button>
                                        <button onclick="deleteEvent(${evt.id})" class="btn-sm" style="color:red; background:none; border:none;" title="Supprimer">🗑️</button>
                                    </td>
                                </tr>
                            `;
                        });
                    } else {
                        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding: 20px; color: #999;">Aucun événement trouvé</td></tr>';
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
                    
                    const users = json.users || json.data || [];
                    
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
                await fetch(`../controllers/api.php?action=deleteEvent&id=${id}`, { method: 'POST' }); // Assurez-vous que l'API gère le POST ou GET pour delete
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
                            <tr style="border-bottom: 1px solid #eee;">
                                <td style="padding: 10px;">${p.nom} ${p.prenom}</td>
                                <td style="padding: 10px;">${p.email}</td>
                                <td style="padding: 10px;">${new Date(p.date_inscription).toLocaleDateString()}</td>
                                <td style="padding: 10px;"><button onclick="removeParticipant(${p.id})" style="color:red; border:none; background:none; cursor:pointer;">❌ Retirer</button></td>
                            </tr>
                        `;
                    });
                } else {
                    tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; color:#666; padding: 20px;">Aucun inscrit pour le moment.</td></tr>';
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
                if(!confirm("Envoyer les rappels par email maintenant ?")) return;
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
        <?php
        return ob_get_clean();
    }
}
?>