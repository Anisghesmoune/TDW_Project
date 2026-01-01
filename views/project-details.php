<?php
require_once '../config/Database.php';
require_once '../models/Model.php';
require_once '../models/ProjectModel.php';
require_once '../views/Sidebar.php';

// Initialisation
$projectModel = new Project();

// Récupération des données
$statsByThematic = $projectModel->getProjectsByThematic();
$statsByResponsable = $projectModel->getProjectsByResponsable();
$statsByYear = $projectModel->getProjectsByYear();
$statsByFinancement = $projectModel->getProjectsByFinancement();
$statsByTeam = $projectModel->getByEquipeId($teamId);
$advancedStats = $projectModel->getAdvancedStats();
$topProjects = $projectModel->getTopProjectsByMembers();
$recentProjects = $projectModel->getRecentProjects(30);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiques des Projets</title>
    <link rel="stylesheet" href="admin_dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); text-align: center; }
        .stat-card h4 { margin: 0 0 10px 0; color: #666; font-size: 0.9rem; text-transform: uppercase; }
        .stat-card .value { font-size: 2.2rem; font-weight: bold; color: #333; }
        
        .charts-wrapper { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 30px; }
        .chart-box { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .chart-box h3 { margin-top: 0; border-bottom: 2px solid #f4f6f9; padding-bottom: 15px; margin-bottom: 20px; font-size: 1.1rem; color: #444; }
        
        .list-group { list-style: none; padding: 0; }
        .list-item { padding: 12px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
        .list-item:last-child { border-bottom: none; }
        .badge { background: #4e73df; color: white; padding: 5px 10px; border-radius: 15px; font-size: 0.8rem; }
        
        .form-control { padding: 8px; border-radius: 5px; border: 1px solid #ddd; }
        .btn { background-color: #e74a3b; color: white; padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; }
        .btn:hover { background-color: #c92a1a; }
        
        @media (max-width: 900px) { .charts-wrapper { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>📊 Statistiques</h2>
            <span class="admin-badge">ADMINISTRATEUR</span>
        </div>
        <?php (new Sidebar("admin"))->render(); ?>
    </div>
    
    <div class="main-content">
        <div class="top-bar">
            <div>
                <h1>Tableau de bord statistique</h1>
                <p style="color: #666;">Vue d'ensemble des activités de recherche</p>
            </div>
            <div>
                <a href="manage-projects.php" class="btn btn-secondary">← Retour aux projets</a>
                <a href="../logout.php" class="logout-btn">Déconnexion</a>
            </div>
        </div>

        <!-- Formulaire Export PDF -->
        <div class="chart-box" style="margin-bottom: 20px; padding: 15px;">
            <h3 style="margin-bottom: 15px;">📄 Exporter un Rapport PDF</h3>
            
            <form action="../controllers/api.php?action=generateProjectReport" method="POST" target="_blank" style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap;">
                
                <!-- Sélection du type de filtre -->
                <div>
                    <label for="filter_type" style="display:block; margin-bottom:5px; font-weight:bold;">Type de rapport :</label>
                    <select name="filterType" id="filter_type" class="form-control" onchange="toggleInputs(this.value)">
                        <option value="all">Tous les projets</option>
                        <option value="year">Par Année de début</option>
                        <option value="responsable">Par Responsable</option>
                        <option value="thematique">Par Thématique</option>
                    </select>
                </div>

                <!-- Input Année (caché par défaut) -->
                <div id="year_input" style="display:none;">
                    <label for="year_val" style="display:block; margin-bottom:5px; font-weight:bold;">Année :</label>
                    <input type="number" name="filterValue" id="year_val" value="<?php echo date('Y'); ?>" min="2000" max="2030" class="form-control" disabled>
                </div>

                <!-- Input Responsable (caché par défaut) -->
                <div id="resp_input" style="display:none;">
                    <label for="resp_val" style="display:block; margin-bottom:5px; font-weight:bold;">Choisir Encadrant :</label>
                    <select name="filterValue" id="resp_val" class="form-control" disabled>
                        <option value="">-- Sélectionner --</option>
                        <?php 
                        foreach ($statsByResponsable as $resp) {
                            echo "<option value='" . $resp['id'] . "'>" . htmlspecialchars($resp['responsable_name']) . "</option>";
                        }
                        ?>
                    </select>
                </div>

                <!-- Input Thématique (caché par défaut) -->
                <div id="them_input" style="display:none;">
                    <label for="them_val" style="display:block; margin-bottom:5px; font-weight:bold;">Choisir Thématique :</label>
                    <select name="filterValue" id="them_val" class="form-control" disabled>
                        <option value="">-- Sélectionner --</option>
                        <?php 
                        foreach ($statsByThematic as $them) {
                            echo "<option value='" . $them['id'] . "'>" . htmlspecialchars($them['thematic_name']) . "</option>";
                        }
                        ?>
                    </select>
                </div>

                <button type="submit" class="btn">
                    📥 Télécharger PDF
                </button>
            </form>
        </div>

        <!-- Cartes statistiques -->
        <div class="stats-grid">
            <div class="stat-card">
                <h4>Total Projets</h4>
                <div class="value"><?php echo $advancedStats['total_projects'] ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <h4>Actifs</h4>
                <div class="value" style="color: #1cc88a;"><?php echo $advancedStats['active_projects'] ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <h4>Terminés</h4>
                <div class="value" style="color: #858796;"><?php echo $advancedStats['completed_projects'] ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <h4>Encadrants</h4>
                <div class="value" style="color: #4e73df;"><?php echo $advancedStats['unique_responsables'] ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <h4>Nouveaux (30j)</h4>
                <div class="value" style="color: #f6c23e;"><?php echo $recentProjects['count'] ?? 0; ?></div>
            </div>
        </div>

        <!-- Rangée 1 : Thématiques & Années -->
        <div class="charts-wrapper">
            <div class="chart-box">
                <h3>📈 Répartition par Thématique</h3>
                <div style="height: 300px;"><canvas id="thematicChart"></canvas></div>
            </div>
            <div class="chart-box">
                <h3>📅 Évolution Annuelle</h3>
                <div style="height: 300px;"><canvas id="yearChart"></canvas></div>
            </div>
        </div>

        <!-- Rangée 2 : Responsables -->
        <div class="chart-box" style="margin-bottom: 30px;">
            <h3>👥 Top Encadrants (Projets totaux vs Actifs)</h3>
            <div style="height: 350px;"><canvas id="responsableChart"></canvas></div>
        </div>

        <!-- Rangée 3 : Financement & Équipes -->
        <div class="charts-wrapper">
            <div class="chart-box">
                <h3>💰 Sources de Financement</h3>
                <div style="height: 300px;"><canvas id="financementChart"></canvas></div>
            </div>
            <div class="chart-box">
                <h3>🏢 Répartition par Équipe</h3>
                <div style="height: 300px;"><canvas id="teamChart"></canvas></div>
            </div>
        </div>

        <!-- Top Projets -->
        <div class="chart-box">
            <h3>🏆 Projets avec le plus grand nombre de membres</h3>
            <ul class="list-group">
                <?php foreach ($topProjects as $idx => $proj): ?>
                    <li class="list-item">
                        <div>
                            <strong>#<?php echo $idx + 1; ?> <?php echo htmlspecialchars($proj['titre']); ?></strong><br>
                            <small class="text-muted">Resp: <?php echo htmlspecialchars($proj['responsable']); ?></small>
                        </div>
                        <span class="badge"><?php echo $proj['member_count']; ?> membres</span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    
    <script>
        // Fonction pour afficher/cacher les inputs selon le type de filtre
        function toggleInputs(val) {
            // Cacher tous les inputs
            document.getElementById('year_input').style.display = 'none';
            document.getElementById('resp_input').style.display = 'none';
            document.getElementById('them_input').style.display = 'none';
            
            // Désactiver tous les inputs
            document.getElementById('year_val').disabled = true;
            document.getElementById('resp_val').disabled = true;
            document.getElementById('them_val').disabled = true;
            
            // Afficher et activer le bon input
            if(val === 'year') {
                document.getElementById('year_input').style.display = 'block';
                document.getElementById('year_val').disabled = false;
            }
            if(val === 'responsable') {
                document.getElementById('resp_input').style.display = 'block';
                document.getElementById('resp_val').disabled = false;
            }
            if(val === 'thematique') {
                document.getElementById('them_input').style.display = 'block';
                document.getElementById('them_val').disabled = false;
            }
        }

        // Palette de couleurs
        const colors = ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796', '#5a5c69'];

        // 1. Graphique Thématiques
        const themData = <?php echo json_encode($statsByThematic); ?>;
        new Chart(document.getElementById('thematicChart'), {
            type: 'bar',
            data: {
                labels: themData.map(d => d.thematic_name),
                datasets: [{
                    label: 'Nombre de projets',
                    data: themData.map(d => d.project_count),
                    backgroundColor: 'rgba(78, 115, 223, 0.7)',
                    borderColor: 'rgba(78, 115, 223, 1)',
                    borderWidth: 1
                }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });

        // 2. Graphique Années (Line)
        const yearData = <?php echo json_encode($statsByYear); ?>;
        new Chart(document.getElementById('yearChart'), {
            type: 'line',
            data: {
                labels: yearData.map(d => d.year),
                datasets: [
                    {
                        label: 'Total',
                        data: yearData.map(d => d.project_count),
                        borderColor: '#4e73df',
                        tension: 0.3,
                        fill: false
                    },
                    {
                        label: 'Actifs',
                        data: yearData.map(d => d.active_count),
                        borderColor: '#1cc88a',
                        borderDash: [5, 5],
                        tension: 0.3,
                        fill: false
                    }
                ]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });

        // 3. Graphique Responsables (Barre Horizontale)
        const respData = <?php echo json_encode(array_slice($statsByResponsable, 0, 10)); ?>;
        new Chart(document.getElementById('responsableChart'), {
            type: 'bar',
            data: {
                labels: respData.map(d => d.responsable_name),
                datasets: [
                    {
                        label: 'Total',
                        data: respData.map(d => d.project_count),
                        backgroundColor: '#4e73df'
                    },
                    {
                        label: 'Actifs',
                        data: respData.map(d => d.active_count),
                        backgroundColor: '#1cc88a'
                    }
                ]
            },
            options: {
                indexAxis: 'y',
                responsive: true, 
                maintainAspectRatio: false
            }
        });

        // 4. Financement (Doughnut)
        const finData = <?php echo json_encode($statsByFinancement); ?>;
        new Chart(document.getElementById('financementChart'), {
            type: 'doughnut',
            data: {
                labels: finData.map(d => d.type_financement),
                datasets: [{
                    data: finData.map(d => d.project_count),
                    backgroundColor: colors
                }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });

        // 5. Équipes (Pie)
        const teamData = <?php echo json_encode($statsByTeam); ?>;
        new Chart(document.getElementById('teamChart'), {
            type: 'pie',
            data: {
                labels: teamData.map(d => d.team_name),
                datasets: [{
                    data: teamData.map(d => d.project_count),
                    backgroundColor: colors
                }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });
    </script>
</body>
</html>