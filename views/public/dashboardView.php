<?php
// Imports des dépendances
require_once __DIR__ . '/../../views/public/View.php';
require_once __DIR__ . '/../../views/public/components/UIHeader.php';
require_once __DIR__ . '/../../views/public/components/UIFooter.php';

class DashboardUserView extends View {

    /**
     * Méthode principale pour structurer la page
     */
    public function render() {
        // Récupération des données de configuration et de menu passées par le contrôleur
        $config = $this->data['config'] ?? [];
        $menuData = $this->data['menu'] ?? [];
        $pageTitle = $this->data['title'] ?? 'Mon Espace - Laboratoire';

        // Définition des CSS spécifiques à cette vue
        // Ces fichiers seront inclus dans le <head> via UIHeader
        $customCss = [
            'views/admin_dashboard.css', // Pour les styles de cartes et de grille
            'views/landingPage.css',
            'assets/css/public.css',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'
        ];

        // 1. Rendu du Header
        $header = new UIHeader($pageTitle, $config, $menuData, $customCss);
        echo $header->render();

        // 2. Contenu principal
        // On utilise un padding pour espacer le contenu du header/footer
        echo '<main class="main-content" style="margin-left: 0; width: 100%; padding: 40px; box-sizing: border-box; background-color: #f8f9fc;">';
        echo $this->content();
        echo '</main>';

        // 3. Rendu du Footer
        $footer = new UIFooter($config, $menuData);
        echo $footer->render();
    }

    /**
     * Contenu spécifique du Dashboard Utilisateur
     */
    protected function content() {
        // Extraction des variables ($user, $stats, $myProjects, $myPubs, $myRes)
        extract($this->data);

        // Gestion de l'avatar par défaut
        $avatar = !empty($user['photo_profil']) ? $user['photo_profil'] : 'assets/img/default-avatar.png';

        // Capture du buffer de sortie
        ob_start();
        ?>
        
        <!-- Styles internes spécifiques pour l'ajustement responsive -->
        <style>
            @media (max-width: 900px) {
                .dashboard-grid { grid-template-columns: 1fr !important; }
            }
            /* Reset styles dashboard admin pour affichage pleine page */
            .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
            .stat-card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); text-align: center; border-left: 4px solid #4e73df; }
            .stat-card:nth-child(2) { border-left-color: #6f42c1; }
            .stat-card:nth-child(3) { border-left-color: #1cc88a; }
            .stat-card h3 { color: #5a5c69; margin-bottom: 10px; font-size: 0.9em; text-transform: uppercase; font-weight: bold; }
            .stat-card .number { font-size: 1.8em; font-weight: bold; color: #5a5c69; }
            
            .btn-sm:hover { opacity: 0.9; }
            .list-group-item:last-child { border-bottom: none !important; }
        </style>

        <div class="container" style="max-width: 1200px; margin: 0 auto;">
            
            <!-- TOP BAR PERSONNALISÉE -->
            <div class="top-bar-user" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                <!-- Profil Avatar + Nom -->
                <div style="display:flex; align-items:center; gap:20px;">
                    <img src="<?= htmlspecialchars($avatar) ?>" alt="Profil" 
                         style="width:60px; height:60px; border-radius:50%; object-fit:cover; border:3px solid #f8f9fc;">
                    
                    <div>
                        <h1 style="margin:0; font-size:1.5em; color: #2e384d;">Bonjour, <?= htmlspecialchars($user['prenom']) ?> 👋</h1>
                        <p style="margin:5px 0 0 0; color: #888; font-size: 0.9em;">Bienvenue sur votre espace membre</p>
                    </div>
                </div>

                <!-- Actions -->
                <div style="display:flex; gap:10px;">
                    <a href="index.php?route=profile-user" class="btn btn-secondary" style="text-decoration:none; padding:10px 20px; border-radius:5px; border:1px solid #e3e6f0; background:#fff; color: #4e73df; transition: all 0.2s;">
                        <i class="fas fa-user-circle"></i> Mon Profil
                    </a>
                    <!-- Le bouton logout est optionnel si déjà présent dans le header, mais pratique ici -->
                    <a href="index.php?route=logout" class="btn btn-danger" style="text-decoration:none; padding:10px 20px; border-radius:5px; background:#e74a3b; color: white; border: none;">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>

            <!-- STATISTIQUES PERSONNELLES -->
            <div class="stats-grid">
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

            <!-- GRILLE DE CONTENU -->
            <div class="dashboard-grid" style="display:grid; grid-template-columns: 1fr 1fr; gap:30px;">
                
                <!-- 1. LISTE MES PROJETS -->
                <div class="content-section card" style="background:white; padding:25px; border-radius:10px; box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);">
                    <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:2px solid #f8f9fc; padding-bottom:15px; margin-bottom:15px;">
                        <h2 style="margin:0; font-size:1.2em; color:#4e73df;"><i class="fas fa-folder-open"></i> Mes Projets</h2>
                        <a href="#" style="font-size:0.85em; color:#4e73df; text-decoration:none;">Tout voir &rarr;</a>
                    </div>
                    
                    <?php if(empty($myProjects)): ?>
                        <div style="text-align:center; padding:20px; color:#858796;">
                            <i class="fas fa-folder" style="font-size:2em; margin-bottom:10px; opacity:0.5;"></i>
                            <p>Vous ne participez à aucun projet pour le moment.</p>
                        </div>
                    <?php else: ?>
                        <ul class="list-group" style="list-style:none; padding:0; margin:0;">
                        <?php foreach($myProjects as $p): ?>
                            <li class="list-group-item" style="padding:15px 0; border-bottom:1px solid #f8f9fc; display:flex; justify-content:space-between; align-items:center;">
                                <div>
                                    <strong style="color:#2e384d;"><?= htmlspecialchars($p['titre']) ?></strong><br>
                                    <small class="text-muted" style="color:#858796;">Rôle : <?= htmlspecialchars($p['role_dans_projet'] ?? 'Participant') ?></small>
                                </div>
                                <a href="index.php?route=project-details&id=<?= $p['id'] ?>" class="btn-sm" style="background:#4e73df; color:white; text-decoration:none; padding:6px 12px; border-radius:4px; font-size:0.85em; transition:0.2s;">Voir</a>
                            </li>
                        <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>

                <!-- 2. LISTE MES PUBLICATIONS -->
                <div class="content-section card" style="background:white; padding:25px; border-radius:10px; box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);">
                    <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:2px solid #f8f9fc; padding-bottom:15px; margin-bottom:15px;">
                        <h2 style="margin:0; font-size:1.2em; color:#6f42c1;"><i class="fas fa-file-alt"></i> Mes Publications</h2>
                        <a href="#" style="font-size:0.85em; color:#6f42c1; text-decoration:none;">Tout voir &rarr;</a>
                    </div>

                    <?php if(empty($myPubs)): ?>
                        <div style="text-align:center; padding:20px; color:#858796;">
                            <i class="fas fa-file-signature" style="font-size:2em; margin-bottom:10px; opacity:0.5;"></i>
                            <p>Aucune publication soumise.</p>
                        </div>
                    <?php else: ?>
                        <ul class="list-group" style="list-style:none; padding:0; margin:0;">
                        <?php foreach($myPubs as $pub): ?>
                            <li class="list-group-item" style="padding:15px 0; border-bottom:1px solid #f8f9fc; display:flex; justify-content:space-between; align-items:center;">
                                <div>
                                    <strong style="color:#2e384d;"><?= htmlspecialchars($pub['titre']) ?></strong><br>
                                    <small class="text-muted" style="color:#858796;">
                                        <i class="far fa-calendar-alt"></i> <?= !empty($pub['date_publication']) ? date('d/m/Y', strtotime($pub['date_publication'])) : '-' ?> 
                                        <?php if(isset($pub['statut_validation']) && $pub['statut_validation'] == 'en_attente'): ?>
                                            <span style="background:#fff3cd; color:#856404; padding:2px 6px; border-radius:4px; font-size:0.8em; margin-left:5px;">En attente</span>
                                        <?php endif; ?>
                                    </small>
                                </div>
                                <a href="index.php?route=publication-details&id=<?= $pub['id'] ?>" class="btn-sm" style="background:#6f42c1; color:white; text-decoration:none; padding:6px 12px; border-radius:4px; font-size:0.85em; transition:0.2s;">Consulter</a>
                            </li>
                        <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>

                <!-- 3. LISTE MES RÉSERVATIONS -->
                <div class="content-section card" style="grid-column: 1 / -1; background:white; padding:25px; border-radius:10px; box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);">
                    <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:2px solid #f8f9fc; padding-bottom:15px; margin-bottom:15px;">
                        <h2 style="margin:0; font-size:1.2em; color:#1cc88a;"><i class="fas fa-calendar-check"></i> Mes Équipements</h2>
                        <a href="index.php?route=equipments" class="btn-link" style="color:#1cc88a; font-weight:bold; text-decoration:none; font-size:0.9em;">+ Nouvelle réservation</a>
                    </div>
                    
                    <?php if(empty($myRes)): ?>
                        <div style="text-align:center; padding:20px; color:#858796;">
                            <i class="fas fa-microscope" style="font-size:2em; margin-bottom:10px; opacity:0.5;"></i>
                            <p>Aucune réservation active.</p>
                        </div>
                    <?php else: ?>
                        <div style="overflow-x:auto;">
                            <table style="width:100%; border-collapse:collapse;">
                                <thead>
                                    <tr style="background:#f8f9fa; text-align:left; color:#5a5c69;">
                                        <th style="padding:12px;">Équipement</th>
                                        <th style="padding:12px;">Début</th>
                                        <th style="padding:12px;">Fin</th>
                                        <th style="padding:12px;">Statut</th>
                                        <th style="padding:12px; text-align:right;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach($myRes as $r): ?>
                                    <tr style="border-bottom:1px solid #f8f9fc;">
                                        <td style="padding:12px; color:#2e384d; font-weight:500;">
                                            <?= htmlspecialchars($r['equipment_nom'] ?? 'Équipement') ?>
                                        </td>
                                        <td style="padding:12px; color:#858796;"><?= date('d/m H:i', strtotime($r['date_debut'])) ?></td>
                                        <td style="padding:12px; color:#858796;"><?= date('d/m H:i', strtotime($r['date_fin'])) ?></td>
                                        <td style="padding:12px;">
                                            <?php 
                                                $s = $r['statut'] ?? '';
                                                $badgeClass = 'secondary';
                                                $badgeText = $s;
                                                
                                                if($s =='confirmé') { $badgeClass = 'success'; $style="background:#d1fae5; color:#065f46;"; }
                                                elseif($s =='en_attente') { $badgeClass = 'warning'; $style="background:#fff3cd; color:#856404;"; }
                                                elseif($s =='annulé') { $badgeClass = 'danger'; $style="background:#fee2e2; color:#b91c1c;"; }
                                                else { $style="background:#e2e3e5; color:#383d41;"; }
                                                
                                                echo "<span style='padding:5px 10px; border-radius:15px; font-size:0.8em; font-weight:600; text-transform:uppercase; $style'>{$s}</span>";
                                            ?>
                                        </td>
                                        <td style="padding:12px; text-align:right;">
                                            <!-- Bouton placeholder pour de futures actions (annuler, voir) -->
                                            <a href="#" style="color:#d1d3e2;"><i class="fas fa-ellipsis-v"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>

        <?php
        return ob_get_clean();
    }
}
?>