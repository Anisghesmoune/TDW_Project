<?php
// session_start();
// // Seul l'admin a accès
// if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
//     header('Location: ../login.php');
//     exit;
// }
require_once '../views/Sidebar.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Paramètres Généraux</title>
    <link rel="stylesheet" href="admin_dashboard.css">
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

        <div class="settings-grid">
            
            <!-- 1. APPARENCE -->
            <div class="card">
                <h2>🎨 Apparence & Graphisme</h2>
                <form id="styleForm" enctype="multipart/form-data">
                    
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
                            <p style="font-size:0.8em; color:#888;">Format recommandé : PNG transparent</p>
                        </div>
                    </div>

                    <button type="button" onclick="saveAppearance()" class="btn btn-primary" style="width:100%;">
                        💾 Enregistrer les modifications
                    </button>
                </form>
            </div>

            <!-- 2. BASE DE DONNÉES -->
            <div class="card">
                <h2>💾 Sauvegarde & Restauration</h2>
                
                <div style="text-align: center; margin-bottom: 30px;">
                    <p style="color:#666; margin-bottom:15px;">
                        Générez un fichier <code>.sql</code> complet contenant toutes les données et la structure.
                    </p>
                    <button onclick="downloadBackup()" class="btn-backup">
                        📥 Télécharger une Sauvegarde
                    </button>
                </div>

                <div class="divider"></div>

                <div style="background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin-bottom: 20px;">
                    <strong>⚠️ Zone de danger :</strong> La restauration écrasera toutes les données actuelles.
                </div>

                <form id="restoreForm">
                    <div class="form-group">
                        <label>Restaurer depuis un fichier (.sql)</label>
                        <input type="file" name="backup_file" accept=".sql" class="form-control" required>
                    </div>
                    <button type="button" onclick="restoreBackup()" class="btn-restore">
                        🔄 Restaurer la Base de Données
                    </button>
                </form>
            </div>

        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', loadSettings);

        async function loadSettings() {
            const res = await fetch('../controllers/api.php?action=getSettings');
            const json = await res.json();
            
            if(json.success) {
                const s = json.data;
                if(s.site_name) document.getElementById('site_name').value = s.site_name;
                if(s.primary_color) document.getElementById('primary_color').value = s.primary_color;
                if(s.sidebar_color) document.getElementById('sidebar_color').value = s.sidebar_color;
                if(s.logo_path) document.getElementById('logoPreview').src = '../' + s.logo_path;
            }
        }

        async function saveAppearance() {
            const form = document.getElementById('styleForm');
            const formData = new FormData(form);

            const res = await fetch('../controllers/api.php?action=updateSettings', {
                method: 'POST',
                body: formData
            });
            const json = await res.json();
            
            if(json.success) {
                alert("Paramètres enregistrés ! Rechargez la page pour voir les changements.");
                // Optionnel : Appliquer les couleurs en live
                // document.documentElement.style.setProperty('--primary-color', document.getElementById('primary_color').value);
                location.reload();
            } else {
                alert("Erreur : " + json.message);
            }
        }

        function downloadBackup() {
            // Redirection directe pour déclencher le téléchargement du fichier
            window.location.href = '../controllers/api.php?action=downloadBackup';
        }

        async function restoreBackup() {
            if(!confirm("⚠️ ATTENTION ⚠️\n\nVous êtes sur le point d'écraser toute la base de données !\nCette action est irréversible.\n\nVoulez-vous continuer ?")) {
                return;
            }

            const form = document.getElementById('restoreForm');
            const formData = new FormData(form);

            if(!formData.get('backup_file').name) {
                alert("Veuillez sélectionner un fichier SQL.");
                return;
            }

            // Afficher un chargement
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