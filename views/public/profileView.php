<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Sécurité : Vérifier connexion
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}
echo($_SESSION['user_id']);
// 2. Importations
require_once __DIR__ . '/../../views/Sidebar.php';
require_once __DIR__ . '/../../models/UserModel.php'; // Nécessaire pour charger les données

// 3. Chargement des données de l'utilisateur
$userModel = new UserModel();
$user = $userModel->getById($_SESSION['user_id']);

if (!$user) {
    die("Erreur : Impossible de charger les informations du profil.");
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mon Profil</title>
    <link rel="stylesheet" href="../admin_dashboard.css">
    <link rel="stylesheet" href="../landingPage.css">
    <link rel="stylesheet" href="../../assets/css/profile.css">
    <link rel="stylesheet" href="../../assets/css/public.css">
    
    <style>
        .profile-container { display: flex; gap: 30px; }
        .profile-card { flex: 1; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .avatar-preview { width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 5px solid #f8f9fa; display: block; margin: 0 auto 20px; }
        .error-text { color: #e74a3b; font-size: 0.85em; margin-top: 5px; display: none; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header"><h2>👤 Mon Profil</h2></div>
        <?php (new Sidebar($_SESSION['role']))->render(); ?>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <h1>Modifier mon profil</h1>
            <a href="../../logout.php" class="logout-btn">Déconnexion</a>
        </div>

        <div class="profile-container">
            <div class="profile-card">
                <form id="profileForm" enctype="multipart/form-data">
                    
                    <!-- PHOTO DE PROFIL -->
                    <div style="text-align:center;">
                        <img src="../../<?= !empty($user['photo_profil']) ? $user['photo_profil'] : 'assets/img/default-avatar.png' ?>" class="avatar-preview" id="previewImg">
                        <input type="file" name="photo" id="photoInput" accept="image/*" style="display:none;" onchange="previewFile()">
                        <button type="button" onclick="document.getElementById('photoInput').click()" class="btn-secondary" style="margin-bottom:20px;">📷 Changer la photo</button>
                    </div>

                    <!-- INFOS PRINCIPALES -->
                    <div class="form-group">
                        <label>Nom & Prénom</label>
                        <input type="text" value="<?= htmlspecialchars($user['nom'] . ' ' . $user['prenom']) ?>" class="form-control" disabled style="background:#f0f0f0; cursor:not-allowed;">
                        <small>Contactez l'admin pour changer votre nom.</small>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label>Téléphone</label>
                            <input type="text" name="telephone" value="<?= htmlspecialchars($user['telephone'] ?? '') ?>" class="form-control">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Domaine de Recherche / Spécialité</label>
                        <input type="text" name="domaine_recherche" value="<?= htmlspecialchars($user['domaine_recherche'] ?? '') ?>" class="form-control">
                    </div>

                    <div class="form-group">
                        <label>Biographie</label>
                        <textarea name="bio" rows="4" class="form-control"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                    </div>

                    <hr style="margin:20px 0; border:0; border-top:1px solid #eee;">

                    <!-- CHANGEMENT DE MOT DE PASSE -->
                    <h3 style="font-size:1.1em; margin-bottom:15px; color:#4e73df;">Sécurité</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Nouveau mot de passe <small>(Laisser vide si inchangé)</small></label>
                            <input type="password" name="password" id="password" class="form-control" placeholder="••••••">
                        </div>

                        <div class="form-group">
                            <label>Confirmer le mot de passe</label>
                            <input type="password" id="password_confirm" class="form-control" placeholder="••••••">
                            <span id="passError" class="error-text">Les mots de passe ne correspondent pas.</span>
                        </div>
                    </div>

                    <button type="button" onclick="saveProfile()" class="btn-primary" style="width:100%; padding:15px; font-size:1.1em;">💾 Enregistrer les modifications</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Prévisualisation photo
        function previewFile() {
            const preview = document.getElementById('previewImg');
            const file = document.getElementById('photoInput').files[0];
            const reader = new FileReader();
            reader.onloadend = function () { preview.src = reader.result; }
            if (file) reader.readAsDataURL(file);
        }

        // Sauvegarde
        async function saveProfile() {
            const form = document.getElementById('profileForm');
            const pass1 = document.getElementById('password').value;
            const pass2 = document.getElementById('password_confirm').value;
            const errorSpan = document.getElementById('passError');

            // 1. Validation Mot de passe côté client
            if (pass1 !== "" || pass2 !== "") {
                if (pass1 !== pass2) {
                    errorSpan.style.display = 'block';
                    document.getElementById('password_confirm').style.borderColor = '#e74a3b';
                    alert("❌ Les mots de passe ne correspondent pas !");
                    return; // On arrête tout ici
                } else {
                    errorSpan.style.display = 'none';
                    document.getElementById('password_confirm').style.borderColor = '#ddd';
                }
            }

            const formData = new FormData(form);

            // Désactivation bouton
            const btn = document.querySelector('.btn-primary');
            const originalText = btn.innerText;
            btn.disabled = true;
            btn.innerText = "⏳ Enregistrement...";

            try {
                const res = await fetch('../../controllers/api.php?action=updateProfile', {
                    method: 'POST',
                    body: formData
                });
                const json = await res.json();
                
                if(json.success) {
                    alert('✅ Profil mis à jour avec succès !');
                    location.reload();
                } else {
                    alert('❌ Erreur : ' + json.message);
                }
            } catch(e) {
                console.error(e);
                alert('Erreur serveur');
            } finally {
                btn.disabled = false;
                btn.innerText = originalText;
            }
        }
    </script>
</body>
</html>