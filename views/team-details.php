<?php
// admin/team-details.php

require_once '../config/Database.php';
require_once '../models/Model.php';
require_once '../models/TeamsModel.php';
require_once '../models/UserModel.php';
require_once '../controllers/TeamController.php';
require_once '../views/Sidebar.php';

// Vérification de l'ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: manage-teams.php');
    exit();
}

$teamId = intval($_GET['id']);
$teamController = new TeamController();

// Récupération des données via le Contrôleur
$team = $teamController->getall($teamId);
$members = $teamController->getTeamMembers($teamId);
$publications = $teamController->getTeamPublications($teamId);
$equipments = $teamController->getTeamEquipments($teamId);

if (!$team) {
    echo "<div style='padding:20px; text-align:center;'>❌ Équipe introuvable. <a href='manage-teams.php'>Retour</a></div>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails de l'équipe - <?php echo htmlspecialchars($team['name'] ?? $team['nom']); ?></title>
    <link rel="stylesheet" href="admin_dashboard.css">
    <link rel="stylesheet" href="teamManagement.css">
    <style>
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); }
        .modal.active { display: flex; align-items: center; justify-content: center; }
        .modal-dialog { background: white; padding: 20px; border-radius: 8px; width: 400px; max-width: 90%; max-height: 90vh; overflow-y: auto; }
        .modal-header { display: flex; justify-content: space-between; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .close-btn { cursor: pointer; border: none; background: none; font-size: 1.5rem; }
        .alert { padding: 10px; margin-top: 10px; border-radius: 4px; display: none; }
        .alert.show { display: block; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-error { background: #f8d7da; color: #721c24; }
        .btn { padding: 8px 15px; border-radius: 4px; cursor: pointer; border: 1px solid transparent; }
        .btn-primary { background: #007bff; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .form-group { margin-bottom: 15px; }
        .form-control { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .equipment-actions { display: flex; gap: 10px; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>⚙️ Administration</h2>
        </div>
       <?php 
       $sidebar = new Sidebar("admin");
       $sidebar->render(); 
       ?>
    </div>
    
    <div class="main-content">
        <!-- Barre supérieure -->
        <div class="top-bar">
            <div class="top-left">
                <a href="manage-teams.php" style="text-decoration:none; margin-right:15px; font-size:1.2rem;">⬅</a>
                <h1>Détails de l'équipe</h1>
            </div>
            <a href="../logout.php" class="logout-btn">Déconnexion</a>
        </div>

        <!-- Carte principale de l'équipe -->
        <div class="details-section" style="background:white; padding:20px; border-radius:8px; margin-bottom:20px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
            <div style="display:flex; justify-content:space-between; align-items:start;">
                <div>
                    <h2 style="margin-top:0; color:#2c3e50;"><?php echo htmlspecialchars($team['name'] ?? $team['nom']); ?></h2>
                    <span style="background:#e1f5fe; color:#0288d1; padding:4px 8px; border-radius:4px; font-size:0.9rem;">
                        <?php echo htmlspecialchars($team['domaine_recherche']); ?>
                    </span>
                </div>
                <div style="text-align:right;">
                    <small>Chef d'équipe</small><br>
                    <strong><?php echo $team['chef_nom'] ? htmlspecialchars($team['chef_prenom'] . ' ' . $team['chef_nom']) : 'Non défini'; ?></strong>
                </div>
            </div>
            <hr style="margin:15px 0; border:0; border-top:1px solid #eee;">
            <p><strong>Description :</strong><br><?php echo nl2br(htmlspecialchars($team['description'])); ?></p>
        </div>

        <!-- Membres de l'équipe -->
        <div class="details-section">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3>👥 Membres de l'équipe (<?php echo count($members); ?>)</h3>
                <button class="btn btn-primary" onclick="openAddMemberModal(<?php echo $teamId; ?>)">
                    ➕ Ajouter un membre
                </button>
            </div>
            
            <?php if (empty($members)): ?>
                <div style="text-align:center; padding:20px; background:white; border-radius:8px;">Aucun membre dans cette équipe.</div>
            <?php else: ?>
                <div class="grid-container" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap:20px;">
                    <?php foreach ($members as $member): ?>
                        <div class="card-item member-card" style="background:white; padding:15px; border-radius:8px; border:1px solid #eee; position:relative;">
                            <div style="display:flex; align-items:center; gap:10px;">
                                <div style="width:40px; height:40px; background:#ddd; border-radius:50%; display:flex; align-items:center; justify-content:center;">
                                    <?php echo strtoupper(substr($member['prenom'], 0, 1) . substr($member['nom'], 0, 1)); ?>
                                </div>
                                <div>
                                    <h4 style="margin:0; font-size:1rem;"><?php echo htmlspecialchars($member['prenom'] . ' ' . $member['nom']); ?></h4>
                                    <small style="color:#666;"><?php echo htmlspecialchars($member['role'] ?? 'Membre'); ?></small>
                                </div>
                            </div>
                            <?php if(($member['role'] ?? '') !== 'Chef' && ($member['id'] != $team['chef_equipe_id'])): ?>
                                <button onclick="removeMember(<?php echo $member['id']; ?>, <?php echo $teamId; ?>)" 
                                        style="position:absolute; top:10px; right:10px; background:none; border:none; color:red; cursor:pointer; font-size:1.5rem;" title="Retirer">×</button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <br>

        <!-- Publications -->
        <div class="details-section">
            <h3>📚 Publications (<?php echo count($publications); ?>)</h3>
            <?php if (empty($publications)): ?>
                <p style="padding:20px; background:white; border-radius:8px;">Aucune publication.</p>
            <?php else: ?>
                <div class="grid-container" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap:20px;">
                    <?php foreach ($publications as $pub): ?>
                        <div class="card-item" style="background:white; padding:15px; border-radius:8px; border-left: 4px solid #3498db;">
                            <h4 style="margin:0 0 5px 0;"><?php echo htmlspecialchars($pub['titre']); ?></h4>
                            <small>📅 <?php echo htmlspecialchars($pub['date_publication']); ?></small>
                            <?php if (!empty($pub['contribution_desc'])): ?>
                                <p style="font-size:0.9rem; color:#555; margin-top:8px;"><?php echo htmlspecialchars($pub['contribution_desc']); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <br>

        <!-- Équipements -->
        <div class="details-section">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3>🖥️ Équipements (<?php echo count($equipments); ?>)</h3>
                <button class="btn btn-primary" onclick="openAddEquipmentModal(<?php echo $teamId; ?>)">
                    ➕ Assigner un équipement
                </button>
            </div>
            
            <?php if (empty($equipments)): ?>
                <p style="padding:20px; background:white; border-radius:8px;">Aucun équipement assigné.</p>
            <?php else: ?>
                <table class="table" style="width:100%; background:white; border-collapse:collapse; border-radius:8px; overflow:hidden;">
                    <thead>
                        <tr style="background:#f8f9fa; text-align:left;">
                            <th style="padding:12px;">Nom</th>
                            <th style="padding:12px;">Type</th>
                            <th style="padding:12px;">État</th>
                            <th style="padding:12px;">Date Attribution</th>
                            <th style="padding:12px; text-align:center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($equipments as $eq): ?>
                            <tr style="border-bottom:1px solid #eee;">
                                <td style="padding:12px;"><?php echo htmlspecialchars($eq['nom']); ?></td>
                                <td style="padding:12px;"><?php echo htmlspecialchars($eq['type'] ?? 'N/A'); ?></td>
                                <td style="padding:12px;">
                                    <span style="padding:4px 10px; border-radius:12px; font-size:0.85rem; font-weight:500;
                                        background:<?php 
                                            echo $eq['etat']=='libre' ? '#d4edda' : 
                                                ($eq['etat']=='réserve' ? '#fff3cd' : '#f8d7da'); 
                                        ?>;
                                        color:<?php 
                                            echo $eq['etat']=='libre' ? '#155724' : 
                                                ($eq['etat']=='réserve' ? '#856404' : '#721c24'); 
                                        ?>;">
                                        <?php echo htmlspecialchars($eq['etat']); ?>
                                    </span>
                                </td>
                                <td style="padding:12px;"><?php echo htmlspecialchars($eq['date_attribution'] ?? 'N/A'); ?></td>
                                <td style="padding:12px; text-align:center;">
                                    <button onclick="removeEquipment(<?php echo $eq['id']; ?>, <?php echo $teamId; ?>)" 
                                            class="btn btn-danger" style="padding:4px 10px; font-size:0.85rem;">
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
                <h3 style="margin:0;">Ajouter un membre</h3>
                <button class="close-btn" onclick="closeAddMemberModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="addMemberForm" onsubmit="submitAddMember(event)">
                    <input type="hidden" id="modalTeamId">
                    <div class="form-group">
                        <label style="display:block; margin-bottom:5px; font-weight:500;">Sélectionner un utilisateur :</label>
                        <select id="userSelect" class="form-control" required>
                            <option value="">Chargement...</option>
                        </select>
                    </div>
                    <div id="memberAlert" class="alert"></div>
                    <div class="modal-footer" style="text-align:right; margin-top:15px; padding-top:15px; border-top:1px solid #eee;">
                        <button type="button" class="btn btn-secondary" onclick="closeAddMemberModal()" style="margin-right:5px;">Annuler</button>
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
                <h3 style="margin:0;">Assigner un équipement</h3>
                <button class="close-btn" onclick="closeAddEquipmentModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="addEquipmentForm" onsubmit="submitAddEquipment(event)">
                    <input type="hidden" id="modalTeamIdEquipment">
                    <div class="form-group">
                        <label style="display:block; margin-bottom:5px; font-weight:500;">Sélectionner un équipement :</label>
                        <select id="equipmentSelect" class="form-control" required>
                            <option value="">Chargement...</option>
                        </select>
                    </div>
                    <div id="equipmentAlert" class="alert"></div>
                    <div class="modal-footer" style="text-align:right; margin-top:15px; padding-top:15px; border-top:1px solid #eee;">
                        <button type="button" class="btn btn-secondary" onclick="closeAddEquipmentModal()" style="margin-right:5px;">Annuler</button>
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

        // API: getAvailableUsers avec teamid (pas team_id)
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

        // API: addMember avec team_id et user_id en JSON
        fetch('../controllers/api.php?action=addMember', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ team_id: parseInt(teamId), user_id: parseInt(userId) })
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

        // API: removeMember avec team_id et user_id en JSON
        fetch('../controllers/api.php?action=removeMember', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ team_id: parseInt(teamId), user_id: parseInt(userId) })
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                alert('✅ Membre retiré avec succès');
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

        // API: getAvailaibleForTeam (attention à la typo dans l'API)
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

        // API: assignEquipment avec team_id et equipment_id en JSON
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

        // API: removeEquipment avec team_id et equipment_id en JSON
        fetch('../controllers/api.php?action=removeEquipment', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ team_id: parseInt(teamId), equipment_id: parseInt(equipmentId) })
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                alert('✅ Équipement retiré avec succès');
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
</body>
</html>