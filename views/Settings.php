<?php
session_start();
// Vérification admin
// if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
//     header('Location: ../login.php');
//     exit;
// }
require_once __DIR__ . '/../views/Sidebar.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Paramètres Généraux</title>
    <link rel="stylesheet" href="admin_dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .settings-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        .card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        h2 { border-bottom: 2px solid #f0f0f0; padding-bottom: 10px; margin-bottom: 20px; color: #333; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        input[type="color"] { height: 45px; padding: 2px; cursor: pointer; }
        
        .preview-box {
            margin-top: 15px;
            padding: 15px;
            border: 1px dashed #ccc;
            text-align: center;
            background: #f9f9f9;
        }
        .preview-logo { max-height: 80px; }

        .btn-backup { background: #1cc88a; color: white; width: 100%; padding: 15px; font-size: 1.1em; border:none; border-radius:5px; cursor:pointer;}
        .btn-restore { background: #e74a3b; color: white; width: 100%; padding: 10px; border:none; border-radius:5px; cursor:pointer;}
        
        .divider { margin: 20px 0; border-top: 1px solid #eee; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>⚙️ Paramètres</h2>
            <span class="admin-badge">ADMINISTRATEUR</span>
        </div>
        <?php (new Sidebar("admin"))->render(); ?>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <h1>Configuration de l'Application</h1>
            <a href="../logout.php" class="logout-btn">Déconnexion</a>
        </div>

        <form id="styleForm" enctype="multipart/form-data"> 
            <div class="settings-grid">
                
                <!-- COLONNE GAUCHE -->
                <div>
                    <!-- 1. APPARENCE -->
                    <div class="card">
                        <h2>🎨 Apparence & Graphisme</h2>
                        
                        <div class="form-group">
                            <label>Nom du Site / Application</label>
                            <input type="text" name="site_name" id="site_name" class="form-control">
                        </div>

                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                            <div class="form-group">
                                <label>Couleur Principale</label>
                                <input type="color" name="primary_color" id="primary_color" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Couleur Sidebar</label>
                                <input type="color" name="sidebar_color" id="sidebar_color" class="form-control">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Logo de l'application</label>
                            <input type="file" name="logo" id="logo" accept="image/*" class="form-control">
                            <div class="preview-box">
                                <img src="" id="logoPreview" class="preview-logo" alt="Aperçu logo">
                            </div>
                        </div>
                    </div>

                    <!-- 3. INFORMATIONS DU LABO -->
                    <div class="card">
                        <h2>ℹ️ Informations & Contact</h2>
                        
                        <div class="form-group">
                            <label>Description du Laboratoire</label>
                            <textarea name="lab_description" id="lab_description" rows="4" class="form-control" placeholder="Présentation courte..."></textarea>
                        </div>

                        <div class="form-group">
                            <label>Adresse Email de Contact</label>
                            <input type="email" name="lab_email" id="lab_email" class="form-control">
                        </div>

                        <div class="form-group">
                            <label>Numéro de Téléphone</label>
                            <input type="text" name="lab_phone" id="lab_phone" class="form-control">
                        </div>

                        <div class="form-group">
                            <label>Adresse Physique</label>
                            <input type="text" name="lab_address" id="lab_address" class="form-control">
                        </div>
                    </div>
                </div>

                <!-- COLONNE DROITE -->
                <div>
                    <!-- 4. RÉSEAUX SOCIAUX (AJOUTÉ ICI) -->
                    <div class="card">
                        <h2>🌐 Réseaux Sociaux & Liens</h2>
                        
                        <div class="form-group">
                            <label><i class="fas fa-globe"></i> Site Web Université</label>
                            <input type="url" id="univ_website" name="univ_website" class="form-control" placeholder="https://...">
                        </div>

                        <div class="form-group">
                            <label><i class="fab fa-facebook"></i> Facebook</label>
                            <input type="url" id="social_facebook" name="social_facebook" class="form-control">
                        </div>

                        <div class="form-group">
                            <label><i class="fab fa-instagram"></i> Instagram</label>
                            <input type="url" id="social_instagram" name="social_instagram" class="form-control">
                        </div>

                        <div class="form-group">
                            <label><i class="fab fa-linkedin"></i> LinkedIn</label>
                            <input type="url" id="social_linkedin" name="social_linkedin" class="form-control">
                        </div>
                    </div>

                    <!-- 2. BASE DE DONNÉES -->
                    <div class="card">
                        <h2>💾 Sauvegarde & Restauration</h2>
                        
                        <div style="text-align: center; margin-bottom: 30px;">
                            <p style="color:#666; margin-bottom:15px;">
                                Générez un fichier <code>.sql</code> complet contenant toutes les données et la structure.
                            </p>
                            <button type="button" onclick="downloadBackup()" class="btn-backup">
                                📥 Télécharger une Sauvegarde
                            </button>
                        </div>

                        <div class="divider"></div>

                        <div style="background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin-bottom: 20px;">
                            <strong>⚠️ Zone de danger :</strong> La restauration écrasera toutes les données actuelles.
                        </div>
                    </div>
                    
                    <!-- Formulaire séparé pour la restauration -->
                    <div class="card">
                        <h2>🔄 Restauration</h2>
                        <!-- Attention : ne pas mettre un form dans un form -->
                    </div>
                </div>

            </div>
            
            <!-- BOUTON SAUVEGARDE FLOTTANT OU FIXE EN BAS -->
            <div style="grid-column: 1 / -1; margin-top: 20px;">
                <button type="button" onclick="saveAppearance()" class="btn btn-primary" style="width:100%; padding: 15px; font-size: 1.2em;">
                    💾 ENREGISTRER TOUS LES PARAMÈTRES
                </button>
            </div>
        </form>

        <!-- Formulaire Restauration (SORTI du form principal pour éviter les conflits) -->
        <form id="restoreForm" style="display:none;">
            <input type="file" name="backup_file" id="hiddenBackupFile" accept=".sql" onchange="triggerRestore()">
        </form>
        
        <!-- Bouton visible pour déclencher le file input caché -->
        <div class="card" style="margin-top:-20px;">
             <div class="form-group">
                <label>Fichier SQL (.sql)</label>
                <input type="file" id="backupFileVisible" accept=".sql" class="form-control">
            </div>
            <button type="button" onclick="prepareRestore()" class="btn-restore" style="width:100%;">
                Restaurer la Base de Données
            </button>
        </div>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', loadSettings);

        async function loadSettings() {
            try {
                const res = await fetch('../controllers/api.php?action=getSettings');
                const json = await res.json();
                
                if(json.success) {
                    const s = json.data;
                    
                    // Apparence
                    if(s.site_name) document.getElementById('site_name').value = s.site_name;
                    if(s.primary_color) document.getElementById('primary_color').value = s.primary_color;
                    if(s.sidebar_color) document.getElementById('sidebar_color').value = s.sidebar_color;
                    if(s.logo_path) document.getElementById('logoPreview').src = '../' + s.logo_path;

                    // Infos Labo
                    if(s.lab_description) document.getElementById('lab_description').value = s.lab_description;
                    if(s.lab_email) document.getElementById('lab_email').value = s.lab_email;
                    if(s.lab_phone) document.getElementById('lab_phone').value = s.lab_phone;
                    if(s.lab_address) document.getElementById('lab_address').value = s.lab_address;

                    // Réseaux Sociaux (NOUVEAU)
                    if(s.social_facebook) document.getElementById('social_facebook').value = s.social_facebook;
                    if(s.social_instagram) document.getElementById('social_instagram').value = s.social_instagram;
                    if(s.social_linkedin) document.getElementById('social_linkedin').value = s.social_linkedin;
                    if(s.univ_website) document.getElementById('univ_website').value = s.univ_website;
                }
            } catch(e) {
                console.error("Erreur chargement settings", e);
            }
        }

        async function saveAppearance() {
            const form = document.getElementById('styleForm');
            const formData = new FormData(form);

            try {
                const res = await fetch('../controllers/api.php?action=updateSettings', {
                    method: 'POST',
                    body: formData
                });
                const json = await res.json();
                
                if(json.success) {
                    alert("✅ Paramètres enregistrés avec succès !");
                    location.reload(); 
                } else {
                    alert("❌ Erreur : " + json.message);
                }
            } catch(e) {
                console.error(e);
                alert("Erreur serveur lors de la sauvegarde.");
            }
        }

        function downloadBackup() {
            window.location.href = '../controllers/api.php?action=downloadBackup';
        }

        // Fonction intermédiaire pour la restauration
        function prepareRestore() {
            const fileInput = document.getElementById('backupFileVisible');
            if(!fileInput.files.length) {
                alert("Veuillez sélectionner un fichier SQL.");
                return;
            }
            restoreBackup(fileInput.files[0]);
        }

        async function restoreBackup(file) {
            if(!confirm("⚠️ ATTENTION ⚠️\n\nVous êtes sur le point d'écraser toute la base de données !\nCette action est irréversible.\n\nVoulez-vous continuer ?")) {
                return;
            }

            const formData = new FormData();
            formData.append('backup_file', file);

            const btn = document.querySelector('.btn-restore');
            const originalText = btn.textContent;
            btn.textContent = "⏳ Restauration en cours...";
            btn.disabled = true;

            try {
                const res = await fetch('../controllers/api.php?action=restoreBackup', {
                    method: 'POST',
                    body: formData
                });
                const json = await res.json();
                
                if(json.success) {
                    alert("✅ Succès : " + json.message);
                    location.reload();
                } else {
                    alert("❌ Erreur : " + json.message);
                }
            } catch(e) {
                console.error(e);
                alert("Erreur serveur lors de la restauration.");
            } finally {
                btn.textContent = originalText;
                btn.disabled = false;
            }
        }
    </script>
</body>
</html>