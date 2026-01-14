<?php
// Imports des dépendances
require_once __DIR__ . '/../views/public/View.php';
require_once __DIR__ . '/../views/public/components/UIHeader.php';
require_once __DIR__ . '/../views/public/components/UIFooter.php';

class SettingsAdminView extends View {

  
    public function render() {
        $config = $this->data['config'] ?? [];
        $menuData = $this->data['menu'] ?? [];
        $pageTitle = $this->data['title'] ?? 'Paramètres du Site';

        $customCss = [
            'views/admin_dashboard.css',
            'views/settings.css',
            'views/landingPage.css'
        ];

        $header = new UIHeader($pageTitle, $config, $menuData, $customCss);
        echo $header->render();

        echo '<main style="width: 100%; padding: 40px 20px; box-sizing: border-box; background-color: #f8f9fc; min-height: 80vh;">';
        echo $this->content();
        echo '</main>';

       
    }

   
    protected function content() {
        $settings = $this->data['settings'] ?? [];
        $menuItems = $this->data['menuItems'] ?? [];

        ob_start();
        ?>
        
        <!-- Styles internes -->
        <style>
            .settings-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 30px;
            }
            @media (max-width: 1024px) {
                .settings-grid { grid-template-columns: 1fr; }
            }
            .card {
                background: white;
                padding: 25px;
                border-radius: 10px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.05);
                margin-bottom: 30px;
            }
            h2 { 
                border-bottom: 2px solid #f0f0f0; 
                padding-bottom: 10px; 
                margin-bottom: 20px; 
                color: #333; 
                font-size: 1.4em; 
            }
            
            .form-group { margin-bottom: 15px; }
            .form-group label { 
                display: block; 
                margin-bottom: 5px; 
                font-weight: bold; 
                color: #555; 
            }
            .form-control { 
                width: 100%; 
                padding: 10px; 
                border: 1px solid #ddd; 
                border-radius: 5px; 
                font-size: 1em; 
            }
            input[type="color"] { 
                height: 45px; 
                padding: 2px; 
                cursor: pointer; 
            }
            
            .preview-box { 
                margin-top: 15px; 
                padding: 15px; 
                border: 1px dashed #ccc; 
                text-align: center; 
                background: #f9f9f9; 
                border-radius: 5px; 
            }
            .preview-logo { max-height: 80px; }

            .btn-backup { 
                background: #1cc88a; 
                color: white; 
                width: 100%; 
                padding: 15px; 
                font-size: 1.1em; 
                border:none; 
                border-radius:5px; 
                cursor:pointer;
            }
            .btn-restore { 
                background: #e74a3b; 
                color: white; 
                width: 100%; 
                padding: 10px; 
                border:none; 
                border-radius:5px; 
                cursor:pointer;
            }
            .btn-save-all { 
                background: var(--primary-color, #4e73df); 
                color: white; 
                width: 100%; 
                padding: 15px; 
                font-size: 1.2em; 
                border:none; 
                border-radius:5px; 
                cursor:pointer; 
                font-weight: bold; 
                position: sticky; 
                bottom: 20px; 
                z-index: 100; 
                box-shadow: 0 4px 10px rgba(0,0,0,0.2); 
            }
            
            .divider { 
                margin: 20px 0; 
                border-top: 1px solid #eee; 
            }

            /* Table Menu */
            .menu-table { 
                width: 100%; 
                border-collapse: collapse; 
                margin-top: 10px; 
            }
            .menu-table th { 
                text-align: left; 
                padding: 8px; 
                background: #f8f9fa; 
                font-size: 0.9em; 
            }
            .menu-table td { 
                padding: 8px; 
                border-bottom: 1px solid #eee; 
            }
            .btn-icon { 
                background: none; 
                border: none; 
                cursor: pointer; 
                font-size: 1.1em; 
            }
            .btn-add { 
                background: #4e73df; 
                color: white; 
                border: none; 
                padding: 5px 10px; 
                border-radius: 4px; 
                cursor: pointer; 
                font-size: 0.9em; 
                margin-top: 10px; 
            }
        </style>

        <div class="top-bar" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 1px solid #eee; padding-bottom: 20px;">
            <div>
                <h1 style="margin: 0; color: #2c3e50;">Configuration de l'Application</h1>
                <p style="color: #666; margin-top: 5px;">Apparence, informations et gestion du menu</p>
            </div>
        </div>

        <form id="styleForm" enctype="multipart/form-data"> 
            <div class="settings-grid">
                
                <div>
                    <div class="card">
                        <h2>🎨 Apparence & Graphisme</h2>
                        <div class="form-group">
                            <label>Nom du Site / Application</label>
                            <input type="text" name="site_name" id="site_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($settings['site_name'] ?? ''); ?>" 
                                   placeholder="Mon Labo">
                        </div>
                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                            <div class="form-group">
                                <label>Couleur Principale</label>
                                <input type="color" name="primary_color" id="primary_color" class="form-control" 
                                       value="<?php echo htmlspecialchars($settings['primary_color'] ?? '#4e73df'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Couleur Sidebar</label>
                                <input type="color" name="sidebar_color" id="sidebar_color" class="form-control" 
                                       value="<?php echo htmlspecialchars($settings['sidebar_color'] ?? '#224abe'); ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Logo de l'application</label>
                            <input type="file" name="logo" id="logo" accept="image/*" class="form-control">
                            <div class="preview-box">
                                <img src="<?php echo isset($settings['logo_path']) ? '../' . htmlspecialchars($settings['logo_path']) : ''; ?>" 
                                     id="logoPreview" class="preview-logo" alt="Aperçu logo">
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <h2> Informations & Contact</h2>
                        <div class="form-group">
                            <label>Description du Laboratoire</label>
                            <textarea name="lab_description" id="lab_description" rows="4" class="form-control" 
                                      placeholder="Présentation courte..."><?php echo htmlspecialchars($settings['lab_description'] ?? ''); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>Adresse Email de Contact</label>
                            <input type="email" name="lab_email" id="lab_email" class="form-control" 
                                   value="<?php echo htmlspecialchars($settings['lab_email'] ?? ''); ?>" 
                                   placeholder="contact@labo.dz">
                        </div>
                        <div class="form-group">
                            <label>Numéro de Téléphone</label>
                            <input type="text" name="lab_phone" id="lab_phone" class="form-control" 
                                   value="<?php echo htmlspecialchars($settings['lab_phone'] ?? ''); ?>" 
                                   placeholder="+213...">
                        </div>
                        <div class="form-group">
                            <label>Adresse Physique</label>
                            <input type="text" name="lab_address" id="lab_address" class="form-control" 
                                   value="<?php echo htmlspecialchars($settings['lab_address'] ?? ''); ?>" 
                                   placeholder="Adresse complète">
                        </div>
                    </div>
                </div>

                <div>
                    <div class="card">
                        <h2>🌐 Réseaux Sociaux & Liens</h2>
                        <div class="form-group">
                            <label><i class="fas fa-globe"></i> Site Web Université</label>
                            <input type="url" id="univ_website" name="univ_website" class="form-control" 
                                   value="<?php echo htmlspecialchars($settings['univ_website'] ?? ''); ?>" 
                                   placeholder="https://www.univ.dz">
                        </div>
                        <div class="form-group">
                            <label><i class="fab fa-facebook"></i> Facebook</label>
                            <input type="url" id="social_facebook" name="social_facebook" class="form-control" 
                                   value="<?php echo htmlspecialchars($settings['social_facebook'] ?? ''); ?>" 
                                   placeholder="https://facebook.com/...">
                        </div>
                        <div class="form-group">
                            <label><i class="fab fa-instagram"></i> Instagram</label>
                            <input type="url" id="social_instagram" name="social_instagram" class="form-control" 
                                   value="<?php echo htmlspecialchars($settings['social_instagram'] ?? ''); ?>" 
                                   placeholder="https://instagram.com/...">
                        </div>
                        <div class="form-group">
                            <label><i class="fab fa-linkedin"></i> LinkedIn</label>
                            <input type="url" id="social_linkedin" name="social_linkedin" class="form-control" 
                                   value="<?php echo htmlspecialchars($settings['social_linkedin'] ?? ''); ?>" 
                                   placeholder="https://linkedin.com/...">
                        </div>
                    </div>

                    <div class="card">
                        <h2> Gestion du Menu Principal</h2>
                        <p style="font-size:0.85em; color:#666; margin-bottom:10px;">
                            Format Route : <code>Controller/method</code> (ex: <em>Projects/index</em>)
                        </p>
                        
                        <table class="menu-table">
                            <thead>
                                <tr>
                                    <th width="30%">Titre</th>
                                    <th width="40%">Route</th>
                                    <th width="15%">Ordre</th>
                                    <th width="15%"></th>
                                </tr>
                            </thead>
                            <tbody id="menuListBody">
                            </tbody>
                        </table>
                        <button type="button" class="btn-add" onclick="addMenuItem()">+ Ajouter un lien</button>
                    </div>

                    <div class="card">
                        <h2>Sauvegarde & Restauration</h2>
                        <div style="text-align: center; margin-bottom: 20px;">
                            <button type="button" onclick="downloadBackup()" class="btn-backup">
                                <i class="fas fa-download"></i> Télécharger une Sauvegarde
                            </button>
                        </div>
                        <div class="divider"></div>
                        <div style="background: #fff3cd; padding: 10px; border-left: 4px solid #ffc107; margin-bottom: 15px; font-size:0.9em;">
                            <strong> Zone de danger :</strong> Restauration BDD.
                        </div>
                        <div class="form-group">
                            <input type="file" id="backupFileVisible" accept=".sql" class="form-control">
                        </div>
                        <button type="button" onclick="prepareRestore()" class="btn-restore" style="width:100%;">
                            <i class="fas fa-upload"></i> Restaurer
                        </button>
                    </div>
                </div>
            </div>
            
            <button type="button" onclick="saveAll()" class="btn-save-all">
                💾 ENREGISTRER TOUTES LES MODIFICATIONS
            </button>
        </form>

        <script>
            let menuItems = <?php echo json_encode($menuItems); ?>;

            document.addEventListener('DOMContentLoaded', () => {
                loadSettings();
                renderMenuTable();
            });

            async function loadSettings() {
                try {
                    const res = await fetch('../controllers/api.php?action=getSettings');
                    const json = await res.json();
                    if(json.success) {
                        const s = json.data;
                        if(s.site_name) document.getElementById('site_name').value = s.site_name;
                        if(s.primary_color) document.getElementById('primary_color').value = s.primary_color;
                        if(s.sidebar_color) document.getElementById('sidebar_color').value = s.sidebar_color;
                        if(s.logo_path) document.getElementById('logoPreview').src = '../' + s.logo_path;
                        if(s.lab_description) document.getElementById('lab_description').value = s.lab_description;
                        if(s.lab_email) document.getElementById('lab_email').value = s.lab_email;
                        if(s.lab_phone) document.getElementById('lab_phone').value = s.lab_phone;
                        if(s.lab_address) document.getElementById('lab_address').value = s.lab_address;
                        if(s.social_facebook) document.getElementById('social_facebook').value = s.social_facebook;
                        if(s.social_instagram) document.getElementById('social_instagram').value = s.social_instagram;
                        if(s.social_linkedin) document.getElementById('social_linkedin').value = s.social_linkedin;
                        if(s.univ_website) document.getElementById('univ_website').value = s.univ_website;
                    }
                } catch(e) { console.error("Erreur settings", e); }
            }

            function renderMenuTable() {
                const tbody = document.getElementById('menuListBody');
                tbody.innerHTML = '';
                menuItems.forEach((item, index) => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td><input type="text" class="form-control" value="${item.title}" onchange="updateMenuItem(${index}, 'title', this.value)" style="padding:5px;"></td>
                        <td><input type="text" class="form-control" value="${item.url}" onchange="updateMenuItem(${index}, 'url', this.value)" style="padding:5px;"></td>
                        <td><input type="number" class="form-control" value="${item.ordre || item.order || 0}" onchange="updateMenuItem(${index}, 'ordre', this.value)" style="padding:5px;"></td>
                        <td><button type="button" class="btn-icon" onclick="removeMenuItem(${index})" style="color:red;">🗑️</button></td>
                    `;
                    tbody.appendChild(tr);
                });
            }

            function addMenuItem() {
                menuItems.push({ title: 'Nouveau', url: 'Home/index', ordre: menuItems.length + 1 });
                renderMenuTable();
            }

            async function deleteItem(id) {
                const res = await fetch('../index.php?route=api-delete-menu', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ id: id })
                });
                
                return await res.json();
            }

            async function removeMenuItem(index) {
                if(!confirm('Voulez-vous vraiment supprimer ce lien ?')) return;

                const item = menuItems[index];

                if (item.id) {
                    try {
                        const result = await deleteItem(item.id);
                        if (result.success) {
                            menuItems.splice(index, 1);
                            renderMenuTable();
                        } else {
                            alert("Erreur lors de la suppression : " + result.message);
                        }
                    } catch (error) {
                        console.error(error);
                        alert("Erreur de communication avec le serveur.");
                    }
                } else {
                    menuItems.splice(index, 1);
                    renderMenuTable();
                }
            }

            function updateMenuItem(index, field, value) {
                menuItems[index][field] = value;
            }

            async function saveAll() {
                const btn = document.querySelector('.btn-save-all');
                btn.disabled = true;
                btn.innerHTML = "⏳ Enregistrement...";

                try {
                    const form = document.getElementById('styleForm');
                    const formData = new FormData(form);
                    const resSettings = await fetch('../controllers/api.php?action=updateSettings', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const resMenu = await fetch('../controllers/api.php?action=updateMenu', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({ menu: menuItems })
                    });

                    const jsonS = await resSettings.json();
                    const jsonM = await resMenu.json();

                    if(jsonS.success && jsonM.success) {
                        alert(" Tout a été enregistré avec succès !");
                        location.reload();
                    } else {
                        alert("Erreur partielle : " + (jsonS.message || jsonM.message));
                    }
                } catch(e) {
                    console.error(e);
                    alert("Erreur serveur lors de la sauvegarde.");
                } finally {
                    btn.disabled = false;
                    btn.innerHTML = "💾 ENREGISTRER TOUTES LES MODIFICATIONS";
                }
            }

            function downloadBackup() {
                window.location.href = '../controllers/api.php?action=downloadBackup';
            }

            function prepareRestore() {
                const fileInput = document.getElementById('backupFileVisible');
                if(!fileInput.files.length) { alert("Veuillez sélectionner un fichier SQL."); return; }
                restoreBackup(fileInput.files[0]);
            }

            async function restoreBackup(file) {
                if(!confirm("⚠️ ATTENTION : Écrasement total de la BDD. Continuer ?")) return;
                const formData = new FormData();
                formData.append('backup_file', file);
                try {
                    const res = await fetch('../controllers/api.php?action=restoreBackup', { method: 'POST', body: formData });
                    const json = await res.json();
                    alert(json.message);
                    if(json.success) location.reload();
                } catch(e) { console.error(e); }
            }
        </script>
        <?php
        return ob_get_clean();
    }
}
?>