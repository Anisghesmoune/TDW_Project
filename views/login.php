<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Laboratoire</title>
    <link rel="stylesheet" href="views/login.css">
</head>

<body>
    <div class="login-container">
        <div class="login-left">
            <h1>Laboratoire de Recherche</h1>
            <p>Plateforme de gestion du laboratoire universitaire. Accédez à vos projets, publications et ressources.</p>
        </div>
        
        <div class="login-right">
            <div class="login-header">
                <h2>Connexion</h2>
                <p>Connectez-vous à votre compte</p>
            </div>
            
            <div id="alert" class="alert"></div>
            
            <form id="loginForm" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken ?? ''; ?>">
                
                <div class="form-group">
                    <label for="email">votre email</label>
                    <input type="text" id="email" name="email" required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn">Se connecter</button>
                
                <div class="register-link">
                    Pas encore de compte ? <a href="register.php">S'inscrire</a>
                </div>
            </form>
        </div>
    </div>
    <script>
        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            const alert = document.getElementById('alert');
            
            try {
                const response = await fetch('../login.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert.className = 'alert alert-success show';
                    alert.textContent = data.message;
                    setTimeout(() => {
                        window.location.href = data.data.redirect;
                    }, 1000);
                } else {
                    alert.className = 'alert alert-error show';
                    alert.textContent = data.message;
                }
            } catch (error) {
                alert.className = 'alert alert-error show';
                alert.textContent = 'Erreur de connexion au serveur';
            }
        });
    </script>
</body>
</html>