<?php
require_once '../config/Database.php';
require_once '../models/UserModel.php';
require_once '../controllers/AuthController.php';

// AuthController::requireLogin();

$userModel = new UserModel();
$user = $userModel->getById($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo htmlspecialchars($_SESSION['nom_complet']); ?></title>
    <link rel="stylesheet" href="user_dashboard.css">
</head>
<div class="main-content">
        <div class="top-bar">
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($user['prenom'], 0, 1) . substr($user['nom'], 0, 1)); ?>
                </div>
                <div>
                    <h3><?php echo htmlspecialchars($user['nom'] . ' ' . $user['prenom']); ?></h3>
                    <p style="color: #666; font-size: 0.9em;">
                        <?php echo htmlspecialchars(ucfirst($user['role'])); ?> 
                        <?php if ($user['grade']): ?>
                            - <?php echo htmlspecialchars($user['grade']); ?>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            <a href="../logout.php" class="logout-btn">Déconnexion</a>
        </div>
        
        <div class="dashboard-grid">
            <div class="stat-card">
                <h3>Mes Projets</h3>
                <div class="number">5</div>
            </div>
            
            <div class="stat-card">
                <h3>Publications</h3>
                <div class="number">12</div>
            </div>
            
            <div class="stat-card">
                <h3>Réservations</h3>
                <div class="number">3</div>
            </div>
            
            <div class="stat-card">
                <h3>Événements</h3>
                <div class="number">7</div>
            </div>
        </div>
        
        <div class="content-section">
            <h2>Projets en cours</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>Titre</th>
                        <th>Thématique</th>
                        <th>Rôle</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Système de reconnaissance faciale</td>
                        <td>Intelligence Artificielle</td>
                        <td>Responsable</td>
                        <td><span class="badge badge-success">En cours</span></td>
                    </tr>
                    <tr>
                        <td>Plateforme blockchain</td>
                        <td>Sécurité</td>
                        <td>Membre</td>
                        <td><span class="badge badge-warning">Soumis</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="content-section">
            <h2>Publications récentes</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>Titre</th>
                        <th>Type</th>
                        <th>Date</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Deep Learning for Facial Recognition</td>
                        <td>Article</td>
                        <td>15/06/2024</td>
                        <td><span class="badge badge-success">Validé</span></td>
                    </tr>
                    <tr>
                        <td>Blockchain Security Framework</td>
                        <td>Communication</td>
                        <td>20/03/2024</td>
                        <td><span class="badge badge-info">En attente</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
