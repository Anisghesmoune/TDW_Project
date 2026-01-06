<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Laboratoire</title>
    <link rel="stylesheet" href="/views/register.css">
</head>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <h1>Créer un compte</h1>
            <p>Rejoignez le laboratoire de recherche</p>
        </div>
        
        <div id="alert" class="alert"></div>
        
        <form id="registerForm" method="POST" action="register_process.php">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken ?? ''; ?>">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="nom">Nom *</label>
                    <input type="text" id="nom" name="nom" required>
                </div>
                
                <div class="form-group">
                    <label for="prenom">Prénom *</label>
                    <input type="text" id="prenom" name="prenom" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="username">Nom d'utilisateur *</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="role">Rôle *</label>
                    <select id="role" name="role" required>
                        <option value="">Sélectionnez un rôle</option>
                        <option value="enseignant">Enseignant</option>
                        <option value="doctorant">Doctorant</option>
                        <option value="etudiant">Étudiant</option>
                        <option value="invite">Invité</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="password">Mot de passe *</label>
                    <input type="password" id="password" name="password" required minlength="8">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirmer mot de passe *</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="grade">Grade</label>
                <input type="text" id="grade" name="grade" placeholder="Ex: Professeur, Maître de conférences">
            </div>
            
            <div class="form-group">
                <label for="domaine_recherche">Domaine de recherche</label>
                <textarea id="domaine_recherche" name="domaine_recherche" placeholder="Décrivez votre domaine de recherche"></textarea>
            </div>
            
            <button type="submit" class="btn">S'inscrire</button>
            
            <div class="login-link">
                Déjà un compte ? <a href="login.php">Se connecter</a>
            </div>
        </form>
    </div>

    <script>
        document.getElementById('registerForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const alert = document.getElementById('alert');
            
            if (password !== confirmPassword) {
                alert.className = 'alert alert-error show';
                alert.textContent = 'Les mots de passe ne correspondent pas';
                return;
            }
            
            const formData = new FormData(e.target);
            
            try {
                const response = await fetch('../register_process.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert.className = 'alert alert-success show';
                    alert.textContent = data.message;
                    setTimeout(() => {
                        window.location.href = 'login.php';
                    }, 2000);
                } else {
                    alert.className = 'alert alert-error show';
                    alert.textContent = data.message;
                }
            } catch (error) {
                alert.className = 'alert alert-error show';
                alert.textContent = 'Erreur lors de l\'inscription';
            }
        });
    </script>
</body>
</html>