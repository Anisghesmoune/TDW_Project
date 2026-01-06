<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    // Redirection vers le login si non connecté
    header('Location: ../../login.php'); // Ajustez le chemin selon l'emplacement du fichier
    exit;
}

require_once __DIR__ . '/../../views/Sidebar.php';

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mon Espace - Laboratoire</title>
    <link rel="stylesheet" href="../admin_dashboard.css">
    <link rel="stylesheet" href="../landingPage.css">
    <link rel="stylesheet" href="../../assets/css/public.css">
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>👤 Espace Membre</h2>
        </div>
        <?php (new Sidebar($_SESSION['role']))->render(); ?>
    </div>
<div class="main-content">
        <div class="top-bar">
        <!-- Avatar -->
        <?php 
            $avatar = !empty($user['photo_profil']) ? '../../' . $user['photo_profil'] : '../../assets/img/default-avatar.png'; 
        ?>
        <img src="<?= htmlspecialchars($avatar) ?>" alt="Profil" 
             style="width:50px; height:50px; border-radius:50%; object-fit:cover; border:2px solid #ddd;">
        
        <div>
           
        </div>
    


    <div style="display:flex; gap:10px;">
        <a href="../public/profile.php" class="btn-secondary" style="text-decoration:none; padding:8px 15px; border-radius:5px;">👤 Profile</a>
        <a href="../../logout.php" class="logout-btn">Déconnexion</a>
    </div>
</div>
        <!-- STATISTIQUES PERSONNELLES -->
        <div class="stats-grid ">
            <div class="stat-card">
                <h3>Mes Projets</h3>
                <div class="number"><?= $stats['projets'] ?></div>
            </div>
            <div class="stat-card">
                <h3>Mes Publications</h3>
                <div class="number"><?= $stats['pubs'] ?></div>
            </div>
            <div class="stat-card">
                <h3>Réservations actives</h3>
                <div class="number"><?= $stats['reservations'] ?></div>
            </div>
        </div>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
            
            <!-- LISTE MES PROJETS -->
            <div class="content-section">
                <h2 style="border-bottom:2px solid #4e73df; padding-bottom:10px;">📁 Mes Projets</h2>
                <?php if(empty($myProjects)): ?>
                    <p class="text-muted">Vous ne participez à aucun projet pour le moment.</p>
                <?php else: ?>
                    <ul class="list-group">
                    <?php foreach($myProjects as $p): ?>
                        <li style="padding:15px 0; border-bottom:1px solid #eee; display:flex; justify-content:space-between; align-items:center;">
                            <div>
                                <strong><?= htmlspecialchars($p['titre']) ?></strong><br>
                                <small class="text-muted">Rôle : <?= htmlspecialchars($p['role_dans_projet'] ?? 'Participant') ?></small>
                            </div>
                            <a href="../../public/project-details.php?id=<?= $p['id'] ?>" class="btn-sm" style="background:#4e73df; color:white; text-decoration:none;">Voir</a>
                        </li>
                    <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <!-- LISTE MES RÉSERVATIONS -->
            <div class="content-section">
                <h2 style="border-bottom:2px solid #1cc88a; padding-bottom:10px;">📅 Mes Équipements</h2>
                <?php if(empty($myRes)): ?>
                    <p class="text-muted">Aucune réservation active.</p>
                    <a href="../../public/equipments.php" class="btn-link">Réserver un équipement</a>
                <?php else: ?>
                    <table style="width:100%; border-collapse:collapse;">
                        <thead><tr style="background:#f8f9fa;"><th style="padding:10px;">Équipement</th><th style="padding:10px;">Date</th><th style="padding:10px;">Statut</th></tr></thead>
                        <tbody>
                        <?php foreach($myRes as $r): ?>
                            <tr>
                                <td style="padding:10px;"><?= htmlspecialchars($r['equipement_nom'] ?? $r['nom_equipement']) ?></td>
                                <td style="padding:10px;"><?= date('d/m H:i', strtotime($r['date_debut'])) ?></td>
                                <td style="padding:10px;">
                                    <?php 
                                        $color = $r['statut']=='confirmée'?'green': ($r['statut']=='en_attente'?'orange':'red');
                                        echo "<span style='color:$color; font-weight:bold;'>{$r['statut']}</span>";
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

        </div>
    </div>
</body>
</html>