<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier l'utilisateur</title>
    <link rel="stylesheet" href="updateUser.css">
</head>
<body>
    <div class="container">
        <a href="admin-users.php" class="back-btn">
            ← Retour à la liste
        </a>

        <div class="profile-card">
            <!-- Loading -->
            <div id="loadingContainer" class="loading">
                <div class="spinner"></div>
                <p>Chargement des données...</p>
            </div>

            <!-- Profile Header -->
            <div id="profileHeader" class="profile-header" style="display: none;">
                <div class="profile-avatar-section">
                    <div class="avatar-container">
                        <img src="https://ui-avatars.com/api/?name=User&size=150&background=667eea&color=fff" 
                             alt="Avatar" 
                             class="avatar" 
                             id="avatarImage">
                        <label class="avatar-upload" title="Changer la photo">
                            📷
                            <input type="file" accept="image/*" id="photoUpload">
                        </label>
                    </div>
                    <div class="profile-info">
                        <h1 id="profileName">Chargement...</h1>
                        <div class="meta">
                            <div class="meta-item">
                                <span>📧</span>
                                <span id="profileEmail">email@example.com</span>
                            </div>
                            <div class="meta-item">
                                <span>👤</span>
                                <span id="profileUsername">@username</span>
                            </div>
                            <div class="meta-item" id="profileStatusBadge">
                                <span class="badge badge-success">Actif</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Profile Body -->
            <div class="profile-body">
                <div id="alertContainer"></div>

                <!-- Info Summary -->
                <div id="infoSummary" class="info-grid" style="display: none; margin-bottom: 40px;">
                    <div class="info-item">
                        <label>Rôle</label>
                        <value id="infoRole">-</value>
                    </div>
                    <div class="info-item">
                        <label>Grade</label>
                        <value id="infoGrade">-</value>
                    </div>
                    <div class="info-item">
                        <label>Date de création</label>
                        <value id="infoDateCreation">-</value>
                    </div>
                    <div class="info-item">
                        <label>Dernière connexion</label>
                        <value id="infoDerniereConnexion">-</value>
                    </div>
                </div>

                <!-- Form -->
                <div id="formContainer" class="form-container">
                    <form id="updateUserForm">
                        <input type="hidden" id="userId" name="id">

                        <!-- Section Informations personnelles -->
                        <div class="form-section">
                            <h2 class="section-title">👤 Informations personnelles</h2>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="nom">Nom <span class="required">*</span></label>
                                    <input type="text" class="form-control" id="nom" name="nom" required>
                                </div>
                                <div class="form-group">
                                    <label for="prenom">Prénom <span class="required">*</span></label>
                                    <input type="text" class="form-control" id="prenom" name="prenom" required>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="username">Username <span class="required">*</span></label>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>
                                <div class="form-group">
                                    <label for="email">Email <span class="required">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                            </div>
                        </div>

                        <!-- Section Sécurité -->
                        <div class="form-section">
                            <h2 class="section-title">🔒 Sécurité</h2>
                            
                            <div class="form-group">
                                <label for="password">Nouveau mot de passe</label>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Laisser vide pour ne pas changer">
                                <small style="color: #666; font-size: 12px;">⚠️ Laisser vide si vous ne souhaitez pas modifier le mot de passe</small>
                            </div>
                        </div>

                        <!-- Section Rôle et Statut -->
                        <div class="form-section">
                            <h2 class="section-title">⚙️ Rôle et Statut</h2>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="role">Rôle <span class="required">*</span></label>
                                    <select class="form-control" id="role" name="role" required>
                                        <option value="">-- Sélectionner --</option>
                                        <option value="admin">Admin</option>
                                        <option value="enseignant">Enseignant</option>
                                        <option value="doctorant">Doctorant</option>
                                        <option value="etudiant">Étudiant</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="statut">Statut <span class="required">*</span></label>
                                    <select class="form-control" id="statut" name="statut" required>
                                        <option value="actif">Actif</option>
                                        <option value="inactif">Inactif</option>
                                        <option value="suspendu">Suspendu</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Section Informations académiques -->
                        <div class="form-section">
                            <h2 class="section-title">🎓 Informations académiques</h2>
                            
                            <div class="form-group">
                                <label for="grade">Grade</label>
                                <input type="text" class="form-control" id="grade" name="grade" placeholder="Ex: Professeur, Maître de conférences...">
                            </div>

                            <div class="form-group">
                                <label for="domaine_recherche">Domaine de recherche</label>
                                <input type="text" class="form-control" id="domaine_recherche" name="domaine_recherche" placeholder="Ex: Intelligence Artificielle">
                            </div>

                            <div class="form-group">
                                <label for="specialite">Spécialité</label>
                                <input type="text" class="form-control" id="specialite" name="specialite" placeholder="Ex: Machine Learning">
                            </div>
                        </div>

                        <div class="form-actions">
                            <a href="admin-users.php" class="btn btn-secondary">❌ Annuler</a>
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                💾 Enregistrer les modifications
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        const urlParams = new URLSearchParams(window.location.search);
        const userId = urlParams.get('id');

        const loadingContainer = document.getElementById('loadingContainer');
        const profileHeader = document.getElementById('profileHeader');
        const formContainer = document.getElementById('formContainer');
        const infoSummary = document.getElementById('infoSummary');
        const updateForm = document.getElementById('updateUserForm');
        const submitBtn = document.getElementById('submitBtn');

        if (!userId) {
            showAlert('Aucun utilisateur spécifié', 'error');
            setTimeout(() => window.location.href = 'admin-users.php', 2000);
        } else {
            loadUserData(userId);
        }

        async function loadUserData(id) {
            try {
                const response = await fetch(`../controllers/api.php?action=getUser&id=${id}`);
                
                if (!response.ok) throw new Error('Erreur lors du chargement');
                
                const result = await response.json();
                console.log('Données reçues:', result);
                
                if (result.success && result.user) {
                    populateProfile(result.user);
                    populateForm(result.user);
                    
                    loadingContainer.style.display = 'none';
                    profileHeader.style.display = 'block';
                    infoSummary.style.display = 'grid';
                    formContainer.classList.add('show');
                } else {
                    throw new Error(result.message || 'Utilisateur introuvable');
                }
            } catch (error) {
                console.error('Erreur:', error);
                loadingContainer.style.display = 'none';
                showAlert('Erreur: ' + error.message, 'error');
                setTimeout(() => window.location.href = 'admin-users.php', 2000);
            }
        }

        function populateProfile(user) {
            document.getElementById('profileName').textContent = `${user.prenom} ${user.nom}`;
            document.getElementById('profileEmail').textContent = user.email;
            document.getElementById('profileUsername').textContent = '@' + user.username;
            
            // Avatar
            const avatarUrl = user.photo_profil || `https://ui-avatars.com/api/?name=${user.prenom}+${user.nom}&size=150&background=667eea&color=fff`;
            document.getElementById('avatarImage').src = avatarUrl;
            
            // Status badge
            const statusBadge = document.getElementById('profileStatusBadge');
            let badgeClass = 'badge-success';
            if (user.statut === 'suspendu') badgeClass = 'badge-danger';
            if (user.statut === 'inactif') badgeClass = 'badge-warning';
            statusBadge.innerHTML = `<span class="badge ${badgeClass}">${user.statut.charAt(0).toUpperCase() + user.statut.slice(1)}</span>`;
            
            // Info summary
            document.getElementById('infoRole').textContent = user.role.charAt(0).toUpperCase() + user.role.slice(1);
            document.getElementById('infoGrade').textContent = user.grade || 'Non défini';
            document.getElementById('infoDateCreation').textContent = formatDate(user.date_creation);
            document.getElementById('infoDerniereConnexion').textContent = formatDate(user.derniere_connexion);
        }

        function populateForm(user) {
            document.getElementById('userId').value = user.id || '';
            document.getElementById('nom').value = user.nom || '';
            document.getElementById('prenom').value = user.prenom || '';
            document.getElementById('username').value = user.username || '';
            document.getElementById('email').value = user.email || '';
            document.getElementById('role').value = user.role || '';
            document.getElementById('statut').value = user.statut || 'actif';
            document.getElementById('grade').value = user.grade || '';
            document.getElementById('domaine_recherche').value = user.domaine_recherche || '';
            document.getElementById('specialite').value = user.specialite || '';
        }

      updateForm.addEventListener('submit', async function(e) {
    e.preventDefault();

    if (!updateForm.checkValidity()) {
        updateForm.reportValidity();
        return;
    }

    submitBtn.disabled = true;
    submitBtn.innerHTML = '⏳ Enregistrement...';

    const formData = new FormData(updateForm);
    const data = Object.fromEntries(formData);

    if (!data.password || data.password.trim() === '') {
        delete data.password;
    }

    try {
        const response = await fetch(
            `../controllers/api.php?action=updateUser&id=${data.id}`,
            {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            }
        );

        const result = await response.json();

        if (result.success) {
            showAlert('✅ Utilisateur mis à jour avec succès!', 'success');
            setTimeout(() => window.location.href = 'admin-users.php', 1500);
        } else {
            throw new Error(result.message);
        }

    } catch (error) {
        showAlert('❌ ' + error.message, 'error');
        submitBtn.disabled = false;
        submitBtn.innerHTML = '💾 Enregistrer les modifications';
    }
});
const photoInput = document.getElementById('photoUpload');
const avatarImage = document.getElementById('avatarImage');

photoInput.addEventListener('change', async function () {
    if (!this.files.length) return;

    const file = this.files[0];
    
    // Validation côté client
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    if (!allowedTypes.includes(file.type)) {
        showAlert('❌ Type de fichier non autorisé. Utilisez JPG, PNG ou GIF', 'error');
        this.value = '';
        return;
    }
    
    const maxSize = 5 * 1024 * 1024; // 5MB
    if (file.size > maxSize) {
        showAlert('❌ Fichier trop volumineux. Maximum 5MB', 'error');
        this.value = '';
        return;
    }

    // Prévisualisation instantanée
    const reader = new FileReader();
    reader.onload = function(e) {
        avatarImage.src = e.target.result;
    };
    reader.readAsDataURL(file);

    // Créer FormData pour l'upload
    const formData = new FormData();
    formData.append('photo', file);

    try {
        // Envoyer le fichier au serveur
        const response = await fetch(
            `../controllers/api.php?action=updatePhoto&id=${userId}`,
            {
                method: 'POST',
                body: formData // Ne pas ajouter Content-Type, le navigateur le fait automatiquement
            }
        );

        const result = await response.json();
        console.log('Résultat upload:', result);

        if (result.success) {
            // Mettre à jour l'image avec l'URL du serveur
            avatarImage.src = result.photo_url;
            showAlert('✅ Photo mise à jour avec succès', 'success');
        } else {
            throw new Error(result.message || 'Erreur lors de l\'upload');
        }

    } catch (error) {
        console.error('Erreur upload:', error);
        showAlert('❌ ' + error.message, 'error');
        
        
    }
});


        function showAlert(message, type) {
            const container = document.getElementById('alertContainer');
            const alert = document.createElement('div');
            alert.className = `alert alert-${type} show`;
            alert.textContent = message;
            container.appendChild(alert);
            setTimeout(() => alert.remove(), 5000);
        }

        function formatDate(dateString) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            return date.toLocaleString('fr-FR', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        
    </script>
</body>
</html>