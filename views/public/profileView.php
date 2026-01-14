<?php
// Imports des dépendances
require_once __DIR__ . '/../../views/public/View.php';
require_once __DIR__ . '/../../views/public/components/UIHeader.php';
require_once __DIR__ . '/../../views/public/components/UIFooter.php';

class ProfileView extends View {

    /**
     * Méthode principale pour structurer la page
     */
    public function render() {
        // Récupération des données de configuration et de menu
        $config = $this->data['config'] ?? [];
        $menuData = $this->data['menu'] ?? [];
        $pageTitle = 'Mon Profil - ' . ($this->data['user']['prenom'] ?? 'Utilisateur');

        // CSS spécifiques
        $customCss = [
            'views/admin_dashboard.css',
            'views/landingPage.css',
            'assets/css/profile.css',
        ];

        $header = new UIHeader($pageTitle, $config, $menuData, $customCss);
        echo $header->render();

        echo '<main class="main-content" style="margin-left: 0; width: 100%; padding: 40px; box-sizing: border-box; background-color: #f8f9fc;">';
        echo $this->content();
        echo '</main>';

        $footer = new UIFooter($config, $menuData);
        echo $footer->render();
    }

 
    protected function content() {
        $user = $this->data['user'] ?? [];
        $userPhoto = !empty($user['photo_profil']) ? $user['photo_profil'] : 'assets/img/default-avatar.png';
         
        if (strpos($userPhoto, 'http') === false && strpos($userPhoto, '/') !== 0) {
          }

        ob_start();
        ?>

        <style>
            .profile-container { display: flex; gap: 30px; max-width: 900px; margin: 0 auto; }
            .profile-card { flex: 1; background: white; padding: 40px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
            .avatar-preview { width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 5px solid #f8f9fa; display: block; margin: 0 auto 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
            .error-text { color: #e74a3b; font-size: 0.85em; margin-top: 5px; display: none; }
            .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
            .form-group { margin-bottom: 20px; }
            .form-control { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 1rem; box-sizing: border-box; transition: border-color 0.3s; }
            .form-control:focus { border-color: #4e73df; outline: none; }
            label { display: block; margin-bottom: 8px; color: #4b5563; font-weight: 500; }
            
            @media (max-width: 768px) {
                .form-row { grid-template-columns: 1fr; }
                .profile-container { padding: 0 10px; }
            }
        </style>

        <div class="top-bar-user" style="max-width: 900px; margin: 0 auto 30px auto; display: flex; justify-content: space-between; align-items: center; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
            <h1 style="margin:0; font-size:1.5em; color: #2e384d;">✏️ Modifier mon profil</h1>
            <a href="index.php?route=logout" class="btn btn-danger" style="text-decoration:none; padding:10px 20px; border-radius:5px; background:#e74a3b; color: white; border: none;">Déconnexion</a>
        </div>

        <div class="profile-container">
            <div class="profile-card">
                <form id="profileForm" enctype="multipart/form-data">
                    
                    <div style="text-align:center; margin-bottom: 30px;">
                        <div style="position: relative; width: 150px; margin: 0 auto;">
                            <img src="<?= htmlspecialchars($userPhoto) ?>" class="avatar-preview" id="previewImg" alt="Avatar">
                            <button type="button" onclick="document.getElementById('photoInput').click()" 
                                    style="position: absolute; bottom: 10px; right: 0; background: #4e73df; color: white; border: none; border-radius: 50%; width: 40px; height: 40px; cursor: pointer; box-shadow: 0 2px 5px rgba(0,0,0,0.2);">
                                📷
                            </button>
                        </div>
                        <input type="file" name="photo" id="photoInput" accept="image/*" style="display:none;" onchange="previewFile()">
                        <p style="color: #888; font-size: 0.9em; margin-top: 10px;">Cliquez sur l'icône caméra pour changer</p>
                    </div>

                    <div class="form-group">
                        <label>Nom & Prénom</label>
                        <input type="text" value="<?= htmlspecialchars(($user['nom'] ?? '') . ' ' . ($user['prenom'] ?? '')) ?>" class="form-control" disabled style="background:#f0f0f0; cursor:not-allowed; color: #666;">
                        <small style="color: #888;">Contactez l'administrateur pour modifier ces informations.</small>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Email <span style="color:red">*</span></label>
                            <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label>Téléphone</label>
                            <input type="text" name="telephone" value="<?= htmlspecialchars($user['telephone'] ?? '') ?>" class="form-control">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Domaine de Recherche / Spécialité</label>
                        <input type="text" name="domaine_recherche" value="<?= htmlspecialchars($user['domaine_recherche'] ?? '') ?>" class="form-control" placeholder="Ex: Intelligence Artificielle, Biologie...">
                    </div>

                    <div class="form-group">
                        <label>Biographie</label>
                        <textarea name="bio" rows="4" class="form-control" placeholder="Quelques mots à propos de vous..."><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                    </div>

                    <hr style="margin:30px 0; border:0; border-top:1px solid #eee;">

                    <h3 style="font-size:1.2em; margin-bottom:20px; color:#4e73df; display:flex; align-items:center;">
                        <i class="fas fa-lock" style="margin-right:10px;"></i> Sécurité
                    </h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Nouveau mot de passe <small style="font-weight:normal; color:#888;">(Laisser vide si inchangé)</small></label>
                            <input type="password" name="password" id="password" class="form-control" placeholder="••••••" autocomplete="new-password">
                        </div>

                        <div class="form-group">
                            <label>Confirmer le mot de passe</label>
                            <input type="password" id="password_confirm" class="form-control" placeholder="••••••" autocomplete="new-password">
                            <span id="passError" class="error-text">❌ Les mots de passe ne correspondent pas.</span>
                        </div>
                    </div>

                    <button type="button" onclick="saveProfile()" class="btn btn-primary" style="width:100%; padding:15px; font-size:1.1em; margin-top: 10px; background-color: #4e73df; color: white; border: none; border-radius: 5px; cursor: pointer; transition: background 0.3s;">
                        💾 Enregistrer les modifications
                    </button>
                </form>
            </div>
        </div>

        <script>
            function previewFile() {
                const preview = document.getElementById('previewImg');
                const file = document.getElementById('photoInput').files[0];
                const reader = new FileReader();
                reader.onloadend = function () { preview.src = reader.result; }
                if (file) reader.readAsDataURL(file);
            }

            async function saveProfile() {
                const form = document.getElementById('profileForm');
                const pass1 = document.getElementById('password').value;
                const pass2 = document.getElementById('password_confirm').value;
                const errorSpan = document.getElementById('passError');

                if (pass1 !== "" || pass2 !== "") {
                    if (pass1 !== pass2) {
                        errorSpan.style.display = 'block';
                        document.getElementById('password_confirm').style.borderColor = '#e74a3b';
                        alert("❌ Les mots de passe ne correspondent pas !");
                        return; 
                    } else {
                        errorSpan.style.display = 'none';
                        document.getElementById('password_confirm').style.borderColor = '#ddd';
                    }
                }

                const formData = new FormData(form);

                const btn = document.querySelector('.btn-primary');
                const originalText = btn.innerText;
                btn.disabled = true;
                btn.innerText = "⏳ Enregistrement en cours...";
                btn.style.opacity = "0.7";

                try {
                    const response = await fetch('../../controllers/api.php?action=updateProfile', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const contentType = response.headers.get("content-type");
                    if (!contentType || !contentType.includes("application/json")) {
                        throw new Error("Réponse serveur invalide (pas de JSON)");
                    }

                    const json = await response.json();
                    
                    if(json.success) {
                        setTimeout(() => {
                            alert('✅ Profil mis à jour avec succès !');
                            location.reload();
                        }, 500);
                    } else {
                        alert('❌ Erreur : ' + (json.message || "Erreur inconnue"));
                    }
                } catch(e) {
                    console.error("Erreur Fetch:", e);
                    alert('❌ Erreur de communication avec le serveur. Vérifiez la console.');
                } finally {
                    btn.disabled = false;
                    btn.innerText = originalText;
                    btn.style.opacity = "1";
                }
            }
        </script>

        <?php
        return ob_get_clean();
    }
}
?>