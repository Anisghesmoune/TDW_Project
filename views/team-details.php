<?php
// Imports des dépendances
require_once __DIR__ . '/../views/public/View.php';
require_once __DIR__ . '/../views/public/components/UIHeader.php';
require_once __DIR__ . '/../views/public/components/UIFooter.php';

class TeamDetailsView extends View {

    /**
     * Méthode principale pour structurer la page
     */
    public function render() {
        // Extraction des données globales
        $config = $this->data['config'] ?? [];
        $menuData = $this->data['menu'] ?? [];
        $pageTitle = $this->data['title'] ?? 'Détails de l\'équipe';

        // CSS spécifiques
        $customCss = [
            'views/admin_dashboard.css',
            'views/teamManagement.css',
            'views/landingPage.css'
        ];

        // 1. Rendu du Header
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
     * Contenu spécifique : Détails, Membres, Pubs, Équipements, Modales, JS
     */
    protected function content() {
        // Extraction des données métier
        $team = $this->data['team'];
        $members = $this->data['members'] ?? [];
        $publications = $this->data['publications'] ?? [];
        $equipments = $this->data['equipments'] ?? [];
        
        $teamId = $team['id']; // ID pour le JS

        ob_start();
        ?>
        
        <!-- Styles internes -->
        <style>
            /* Ajustements Layout */
            .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 1px solid #eee; padding-bottom: 20px; }
            .top-left { display: flex; align-items: center; gap: 15px; }
            
            /* Cartes et Sections */
            .details-section { background:white; padding:20px; border-radius:8px; margin-bottom:20px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
            
            /* Grilles */
            .grid-container { display:grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap:20px; }
            .card-item { background:white; padding:15px; border-radius:8px; border:1px solid #eee; position:relative; transition: transform 0.2s; }
            .card-item:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
            
            /* Modales */
            .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); backdrop-filter: blur(2px); }
            .modal.active { display: flex; align-items: center; justify-content: center; }
            .modal-dialog { background: white; padding: 20px; border-radius: 8px; width: 400px; max-width: 90%; max-height: 90vh; overflow-y: auto; box-shadow: 0 10px 25px rgba(0,0,0,0.2); }
            .modal-header { display: flex; justify-content: space-between; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
            .close-btn { cursor: pointer; border: none; background: none; font-size: 1.5rem; color: #aaa; }
            
            /* Alertes */
            .alert { padding: 10px; margin-top: 10px; border-radius: 4px; display: none; }
            .alert.show { display: block; }
            .alert-success { background: #d4edda; color: #155724; }
            .alert-error { background: #f8d7da; color: #721c24; }
            
            /* Boutons & Formulaires */
            .btn { padding: 8px 15px; border-radius: 4px; cursor: pointer; border: 1px solid transparent; font-weight: 500; }
            .btn-primary { background: #4e73df; color: white; }
            .btn-secondary { background: #858796; color: white; }
            .btn-danger { background: #e74a3b; color: white; }
            .form-group { margin-bottom: 15px; }
            .form-control { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
            
            /* Table */
            .table { width:100%; background:white; border-collapse:collapse; border-radius:8px; overflow:hidden; }
            .table th, .table td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
            .table th { background: #f8f9fa; font-weight: 600; color: #4e73df; }
        </style>

        <div class="container" style="max-width: 1400px; margin: 0 auto;">
            
            <!-- Barre supérieure -->
            <div class="top-bar">
                <div class="top-left">
                    <a href="index.php?route=teams-admin" class="btn-secondary" style="text-decoration:none;">⬅ Retour</a>
                    <h1 style="margin:0; font-size: 1.8rem; color: #2c3e50;">Détails de l'équipe</h1>
                </div>
            </div>

            <!-- Carte principale de l'équipe -->
            <div class="details-section">
                <div style="display:flex; justify-content:space-between; align-items:start;">
                    <div>
                        <h2 style="margin-top:0; color:#2c3e50; font-size: 2rem;"><?php echo htmlspecialchars($team['name'] ?? $team['nom']); ?></h2>
                        <span style="background:#e3f2fd; color:#0d47a1; padding:5px 12px; border-radius:15px; font-size:0.9rem; font-weight: 500;">
                            <?php echo htmlspecialchars($team['domaine_recherche'] ?? 'Non défini'); ?>
                        </span>
                    </div>
                    <div style="text-align:right;">
                        <small style="color: #666; text-transform: uppercase; font-size: 0.8em;">Chef d'équipe</small><br>
                        <strong style="color: #4e73df; font-size: 1.1em;"><?php echo !empty($team['chef_nom']) ? htmlspecialchars(($team['chef_prenom'] ?? '') . ' ' . $team['chef_nom']) : 'Non défini'; ?></strong>
                    </div>
                </div>
                <hr style="margin:20px 0; border:0; border-top:1px solid #eee;">
                <p style="line-height: 1.6; color: #444;">
                    <strong>Description :</strong><br>
                    <?php echo nl2br(htmlspecialchars($team['description'] ?? 'Aucune description disponible.')); ?>
                </p>
            </div>

            <!-- Membres de l'équipe -->
            <div class="details-section">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px;">
                    <h3 style="margin:0; color: #2c3e50;">👥 Membres de l'équipe (<?php echo count($members); ?>)</h3>
                    <button class="btn btn-primary" onclick="openAddMemberModal(<?php echo $teamId; ?>)">
                        ➕ Ajouter un membre
                    </button>
                </div>
                
                <?php if (empty($members)): ?>
                    <div style="text-align:center; padding:30px; color: #888;">Aucun membre dans cette équipe.</div>
                <?php else: ?>
                    <div class="grid-container">
                        <?php foreach ($members as $member): ?>
                            <div class="card-item member-card">
                                <div style="display:flex; align-items:center; gap:15px;">
                                    <!-- Avatar -->
                                    <div style="width:50px; height:50px; background:#f0f2f5; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:bold; overflow:hidden; border: 2px solid #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                                        <?php if (!empty($member['photo_profil'])): ?>
                                            <img src="<?php echo htmlspecialchars($member['photo_profil']); ?>" alt="Photo" style="width:100%; height:100%; object-fit:cover;">
                                        <?php else: ?>
                                            <span style="color: #4e73df;"><?php echo strtoupper(substr($member['prenom'], 0, 1) . substr($member['nom'], 0, 1)); ?></span>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Infos principales -->
                                    <div>
                                        <h4 style="margin:0; font-size:1rem; color: #333;"><?php echo htmlspecialchars($member['prenom'] . ' ' . $member['nom']); ?></h4>
                                        <small style="color:#666;"><?php echo htmlspecialchars($member['role'] ?? 'Membre'); ?></small>
                                        <?php if (!empty($member['grade'])): ?>
                                            <br><small style="color:#888; font-size: 0.8em;"><?php echo htmlspecialchars($member['grade']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Bouton supprimer (sauf pour le chef) -->
                                <?php if (($member['role'] ?? '') !== 'Chef' && $member['id'] != ($team['chef_equipe_id'] ?? 0)): ?>
                                    <button onclick="removeMember(<?php echo $member['id']; ?>, <?php echo $teamId; ?>)"
                                            style="position:absolute; top:10px; right:10px; background:none; border:none; color:#e74a3b; cursor:pointer; font-size:1.2rem; opacity: 0.7;"
                                            title="Retirer de l'équipe">
                                        ×
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Publications -->
            <div class="details-section">
                <h3 style="margin-top: 0; color: #2c3e50; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 20px;">📚 Publications (<?php echo count($publications); ?>)</h3>
                <?php if (empty($publications)): ?>
                    <p style="text-align:center; padding:30px; color: #888;">Aucune publication associée.</p>
                <?php else: ?>
                    <div class="grid-container">
                        <?php foreach ($publications as $pub): ?>
                            <div class="card-item" style="border-left: 4px solid #36b9cc;">
                                <h4 style="margin:0 0 8px 0; color: #333;"><?php echo htmlspecialchars($pub['titre']); ?></h4>
                                <small style="display: block; color: #666; margin-bottom: 5px;">📅 <?php echo !empty($pub['date_publication']) ? date('d/m/Y', strtotime($pub['date_publication'])) : 'Date inconnue'; ?></small>
                                <span style="background: #f8f9fa; padding: 2px 8px; border-radius: 4px; font-size: 0.8em; color: #555;">
                                    <?php echo htmlspecialchars($pub['type'] ?? 'Article'); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Équipements -->
            <div class="details-section">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px;">
                    <h3 style="margin:0; color: #2c3e50;">🖥️ Équipements (<?php echo count($equipments); ?>)</h3>
                    <button class="btn btn-primary" onclick="openAddEquipmentModal(<?php echo $teamId; ?>)">
                        ➕ Assigner un équipement
                    </button>
                </div>
                
                <?php if (empty($equipments)): ?>
                    <p style="text-align:center; padding:30px; color: #888;">Aucun équipement assigné.</p>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Type</th>
                                <th>État</th>
                                <th>Date Attribution</th>
                                <th style="text-align:center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($equipments as $eq): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($eq['nom']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($eq['type'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span style="padding:4px 10px; border-radius:12px; font-size:0.85rem; font-weight:600;
                                            background:<?php 
                                                echo $eq['etat']=='libre' ? '#d4edda' : 
                                                    ($eq['etat']=='réserve' ? '#fff3cd' : '#f8d7da'); 
                                            ?>;
                                            color:<?php 
                                                echo $eq['etat']=='libre' ? '#155724' : 
                                                    ($eq['etat']=='réserve' ? '#856404' : '#721c24'); 
                                            ?>;">
                                            <?php echo ucfirst(htmlspecialchars($eq['etat'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo !empty($eq['date_attribution']) ? date('d/m/Y', strtotime($eq['date_attribution'])) : '-'; ?></td>
                                    <td style="text-align:center;">
                                        <button onclick="removeEquipment(<?php echo $eq['id']; ?>, <?php echo $teamId; ?>)" 
                                                class="btn btn-danger" style="padding:5px 10px; font-size:0.8rem;">
                                            🗑️ Retirer
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Modal Ajouter Membre -->
        <div id="addMemberModal" class="modal">
            <div class="modal-dialog">
                <div class="modal-header">
                    <h3 style="margin:0; color: #4e73df;">Ajouter un membre</h3>
                    <button class="close-btn" onclick="closeAddMemberModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="addMemberForm" onsubmit="submitAddMember(event)">
                        <input type="hidden" id="modalTeamId">
                        <div class="form-group">
                            <label style="display:block; margin-bottom:8px; font-weight:600;">Sélectionner un utilisateur :</label>
                            <select id="userSelect" class="form-control" required>
                                <option value="">Chargement...</option>
                            </select>
                        </div>
                        <div id="memberAlert" class="alert"></div>
                        <div class="modal-footer" style="text-align:right; margin-top:20px; padding-top:15px; border-top:1px solid #eee;">
                            <button type="button" class="btn btn-secondary" onclick="closeAddMemberModal()" style="margin-right:10px;">Annuler</button>
                            <button type="submit" class="btn btn-primary">Ajouter</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Assigner Équipement -->
        <div id="addEquipmentModal" class="modal">
            <div class="modal-dialog">
                <div class="modal-header">
                    <h3 style="margin:0; color: #36b9cc;">Assigner un équipement</h3>
                    <button class="close-btn" onclick="closeAddEquipmentModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="addEquipmentForm" onsubmit="submitAddEquipment(event)">
                        <input type="hidden" id="modalTeamIdEquipment">
                        <div class="form-group">
                            <label style="display:block; margin-bottom:8px; font-weight:600;">Sélectionner un équipement :</label>
                            <select id="equipmentSelect" class="form-control" required>
                                <option value="">Chargement...</option>
                            </select>
                            <small style="color: #666;">Seuls les équipements libres sont affichés.</small>
                        </div>
                        <div id="equipmentAlert" class="alert"></div>
                        <div class="modal-footer" style="text-align:right; margin-top:20px; padding-top:15px; border-top:1px solid #eee;">
                            <button type="button" class="btn btn-secondary" onclick="closeAddEquipmentModal()" style="margin-right:10px;">Annuler</button>
                            <button type="submit" class="btn btn-primary">Assigner</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
        // --- GESTION DU MODAL MEMBRES ---

        function openAddMemberModal(teamId) {
            const modal = document.getElementById('addMemberModal');
            const select = document.getElementById('userSelect');
            document.getElementById('modalTeamId').value = teamId;
            
            select.innerHTML = '<option value="">Chargement...</option>';
            modal.classList.add('active');
            document.getElementById('memberAlert').className = 'alert';

            // API: getAvailableUsers
            fetch(`../controllers/api.php?action=getAvailableUsers&teamid=${teamId}`)
                .then(res => res.json())
                .then(data => {
                    select.innerHTML = '';
                    if (data.success && data.users && data.users.length > 0) {
                        let defaultOpt = document.createElement('option');
                        defaultOpt.text = "-- Choisir un utilisateur --";
                        defaultOpt.value = "";
                        select.add(defaultOpt);

                        data.users.forEach(user => {
                            let option = document.createElement('option');
                            option.value = user.id;
                            option.text = `${user.prenom} ${user.nom} (${user.role || 'N/A'})`;
                            select.add(option);
                        });
                    } else {
                        let option = document.createElement('option');
                        option.text = "Aucun utilisateur disponible";
                        option.value = "";
                        select.add(option);
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    select.innerHTML = '<option value="">Erreur de chargement</option>';
                    showMemberAlert('Erreur de chargement des utilisateurs', 'error');
                });
        }

        function closeAddMemberModal() {
            document.getElementById('addMemberModal').classList.remove('active');
        }

        function submitAddMember(event) {
            if (event) event.preventDefault();
            
            const teamId = document.getElementById('modalTeamId').value;
            const userId = document.getElementById('userSelect').value;

            if(!userId) {
                showMemberAlert('Veuillez sélectionner un utilisateur.', 'error');
                return;
            }

            fetch('../controllers/api.php?action=addMember', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ teamid: parseInt(teamId), user_id: parseInt(userId) })
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    showMemberAlert('✅ Membre ajouté avec succès !', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showMemberAlert('❌ Erreur : ' + (data.message || 'Inconnue'), 'error');
                }
            })
            .catch(err => {
                console.error(err);
                showMemberAlert('❌ Erreur de connexion', 'error');
            });
        }

        function removeMember(userId, teamId) {
            if(!confirm('Êtes-vous sûr de vouloir retirer ce membre de l\'équipe ?')) return;

            fetch('../controllers/api.php?action=removeMember', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ team_id: parseInt(teamId), user_id: parseInt(userId) })
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    // Petit feedback visuel avant reload
                    // alert('✅ Membre retiré avec succès');
                    location.reload();
                } else {
                    alert('❌ Erreur: ' + (data.message || 'Inconnue'));
                }
            })
            .catch(err => {
                console.error(err);
                alert('❌ Erreur de connexion');
            });
        }

        function showMemberAlert(msg, type) {
            const div = document.getElementById('memberAlert');
            div.textContent = msg;
            div.className = 'alert show ' + (type === 'success' ? 'alert-success' : 'alert-error');
        }

        // --- GESTION DU MODAL ÉQUIPEMENTS ---

        function openAddEquipmentModal(teamId) {
            const modal = document.getElementById('addEquipmentModal');
            const select = document.getElementById('equipmentSelect');
            document.getElementById('modalTeamIdEquipment').value = teamId;
            
            select.innerHTML = '<option value="">Chargement...</option>';
            modal.classList.add('active');
            document.getElementById('equipmentAlert').className = 'alert';

            fetch(`../controllers/api.php?action=getAvailaibleForTeam&teamid=${teamId}`)
                .then(res => res.json())
                .then(data => {
                    select.innerHTML = '';
                    if (data.success && data.equipments && data.equipments.length > 0) {
                        let defaultOpt = document.createElement('option');
                        defaultOpt.text = "-- Choisir un équipement --";
                        defaultOpt.value = "";
                        select.add(defaultOpt);

                        data.equipments.forEach(equip => {
                            let option = document.createElement('option');
                            option.value = equip.id;
                            option.text = `${equip.nom} - ${equip.type || 'N/A'} (${equip.etat})`;
                            select.add(option);
                        });
                    } else {
                        let option = document.createElement('option');
                        option.text = "Aucun équipement disponible";
                        option.value = "";
                        select.add(option);
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    select.innerHTML = '<option value="">Erreur de chargement</option>';
                    showEquipmentAlert('Erreur de chargement des équipements', 'error');
                });
        }

        function closeAddEquipmentModal() {
            document.getElementById('addEquipmentModal').classList.remove('active');
        }

        function submitAddEquipment(event) {
            if (event) event.preventDefault();
            
            const teamId = document.getElementById('modalTeamIdEquipment').value;
            const equipmentId = document.getElementById('equipmentSelect').value;

            if(!equipmentId) {
                showEquipmentAlert('Veuillez sélectionner un équipement.', 'error');
                return;
            }

            fetch('../controllers/api.php?action=assignEquipment', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ team_id: parseInt(teamId), equipment_id: parseInt(equipmentId) })
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    showEquipmentAlert('✅ Équipement assigné avec succès !', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showEquipmentAlert('❌ Erreur : ' + (data.message || 'Inconnue'), 'error');
                }
            })
            .catch(err => {
                console.error(err);
                showEquipmentAlert('❌ Erreur de connexion', 'error');
            });
        }

        function removeEquipment(equipmentId, teamId) {
            if(!confirm('Êtes-vous sûr de vouloir retirer cet équipement de l\'équipe ?')) return;

            fetch('../controllers/api.php?action=removeEquipment', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ team_id: parseInt(teamId), equipment_id: parseInt(equipmentId) })
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    location.reload();
                } else {
                    alert('❌ Erreur: ' + (data.message || 'Inconnue'));
                }
            })
            .catch(err => {
                console.error(err);
                alert('❌ Erreur de connexion');
            });
        }

        function showEquipmentAlert(msg, type) {
            const div = document.getElementById('equipmentAlert');
            div.textContent = msg;
            div.className = 'alert show ' + (type === 'success' ? 'alert-success' : 'alert-error');
        }

        // Fermeture modals au clic extérieur
        window.onclick = function(event) {
            const memberModal = document.getElementById('addMemberModal');
            const equipmentModal = document.getElementById('addEquipmentModal');
            
            if (event.target == memberModal) {
                closeAddMemberModal();
            }
            if (event.target == equipmentModal) {
                closeAddEquipmentModal();
            }
        }
        </script>
        <?php
        return ob_get_clean();
    }
}
?>