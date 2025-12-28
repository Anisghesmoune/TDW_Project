<?php
require_once '../config/Database.php';
require_once '../models/UserModel.php';
require_once '../controllers/AuthController.php';

AuthController::requireLogin();

$userModel = new UserModel();
$user = $userModel->getById($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil</title>
    <link rel="stylesheet" href="login.css">
</head>
<body>
      <div class="container">
        <a href="dashboard.php" class="back-btn">← Retour au dashboard</a>
        
        <div class="profile-header">
            <div class="profile-avatar">
                <?php echo strtoupper(substr($user['prenom'], 0, 1) . substr($user['nom'], 0, 1)); ?>
            </div>
            <div class="profile-info">
                <h1><?php echo htmlspecialchars($user['nom'] . ' ' . $user['prenom']); ?></h1>
                <p><strong>Rôle:</strong> <?php echo htmlspecialchars(ucfirst($user['role'])); ?></p>
                <?php if ($user['grade']): ?>
                    <p><strong>Grade:</strong> <?php echo htmlspecialchars($user['grade']); ?></p>
                <?php endif; ?>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                <p><strong>Membre depuis:</strong> <?php echo date('d/m/Y', strtotime($user['date_creation'])); ?></p>
            </div>
        </div>
        
        <div class="profile-content">
            <div class="tabs">
                <button class="tab active" onclick="showTab('info')">📝 Informations</button>
                <button class="tab" onclick="showTab('password')">🔒 Mot de passe</button>
                <button class="tab" onclick="showTab('photo')">📷 Photo</button>
            </div>
            
            <div id="alert" class="alert"></div>
            
            <!-- Onglet Informations -->
            <div id="info" class="tab-content active">
                <h2 style="margin-bottom: 20px;">Modifier mes informations</h2>
                <form id="profileForm" method="POST" action="profile_update.php">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nom">Nom</label>
                            <input type="text" id="nom" name="nom" value="<?php echo htmlspecialchars($user['nom']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="prenom">Prénom</label>
                            <input type="text" id="prenom" name="prenom" value="<?php echo htmlspecialchars($user['prenom']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="grade">Grade</label>
                        <input type="text" id="grade" name="grade" value="<?php echo htmlspecialchars($user['grade'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="specialite">Spécialité</label>
                        <input type="text" id="specialite" name="specialite" value="<?php echo htmlspecialchars($user['specialite'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="domaine_recherche">Domaine de recherche</label>
                        <textarea id="domaine_recherche" name="domaine_recherche" rows="4"><?php echo htmlspecialchars($user['domaine_recherche'] ?? ''); ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn">Enregistrer les modifications</button>
                </form>
            </div>
            
            <!-- Onglet Mot de passe -->
            <div id="password" class="tab-content">
                <h2 style="margin-bottom: 20px;">Changer mon mot de passe</h2>
                <form id="passwordForm" method="POST" action="password_change.php">
                    <div class="form-group">
                        <label for="old_password">Ancien mot de passe</label>
                        <input type="password" id="old_password" name="old_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">Nouveau mot de passe</label>
                        <input type="password" id="new_password" name="new_password" required minlength="8">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirmer le nouveau mot de passe</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <button type="submit" class="btn">Changer le mot de passe</button>
                </form>
            </div>
            
            <!-- Onglet Photo -->
            <div id="photo" class="tab-content">
                <h2 style="margin-bottom: 20px;">Changer ma photo de profil</h2>
                <form id="photoForm" method="POST" action="photo_upload.php" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="photo">Choisir une photo</label>
                        <input type="file" id="photo" name="photo" accept="image/*" required>
                    </div>
                    
                    <button type="submit" class="btn">Télécharger</button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function showTab(tabName) {
            // Masquer tous les contenus
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Désactiver tous les onglets
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Activer l'onglet sélectionné
            document.getElementById(tabName).classList.add('active');
            event.target.classList.add('active');
        }
        
        // Gestion du formulaire de profil
        document.getElementById('profileForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const alert = document.getElementById('alert');
            
            try {
                const response = await fetch('profile_update.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert.className = 'alert alert-success show';
                    alert.textContent = data.message;
                } else {
                    alert.className = 'alert alert-error show';
                    alert.textContent = data.message;
                }
            } catch (error) {
                alert.className = 'alert alert-error show';
                alert.textContent = 'Erreur lors de la mise à jour';
            }
        });
        
        // Gestion du changement de mot de passe
        document.getElementById('passwordForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const alert = document.getElementById('alert');
            
            if (newPassword !== confirmPassword) {
                alert.className = 'alert alert-error show';
                alert.textContent = 'Les mots de passe ne correspondent pas';
                return;
            }
            
            const formData = new FormData(e.target);
            
            try {
                const response = await fetch('password_change.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert.className = 'alert alert-success show';
                    alert.textContent = data.message;
                    e.target.reset();
                } else {
                    alert.className = 'alert alert-error show';
                    alert.textContent = data.message;
                }
            } catch (error) {
                alert.className = 'alert alert-error show';
                alert.textContent = 'Erreur lors du changement de mot de passe';
            }
        });
    </script>
</body>
</html>