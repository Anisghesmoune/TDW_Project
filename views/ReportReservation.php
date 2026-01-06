<?php
// session_start();
// if (!isset($_SESSION['user_id'])) header('Location: ../login.php');
require_once __DIR__ . 'Sidebar.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapports d'Utilisation</title>
    <link rel="stylesheet" href="admin_dashboard.css">
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .report-filters {
            background: white; padding: 20px; border-radius: 8px;
            display: flex; gap: 15px; align-items: flex-end; margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .charts-grid {
            display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;
        }
        .chart-container {
            background: white; padding: 20px; border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); height: 400px;
        }
        .form-group { margin-bottom: 0; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="date"] { padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header"><h2>📊 Rapports</h2></div>
        <?php (new Sidebar("admin"))->render(); ?>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <h1>Rapports d'Activité</h1>
            <a href="equipement_management.php" class="btn btn-secondary">Retour</a>
        </div>

        <!-- Filtres -->
        <div class="report-filters">
            <form id="filterForm" style="display:flex; gap:15px; width:100%;">
                <div class="form-group">
                    <label>Date Début</label>
                    <input type="date" id="startDate" name="date_debut" required>
                </div>
                <div class="form-group">
                    <label>Date Fin</label>
                    <input type="date" id="endDate" name="date_fin" required>
                </div>
                <div class="form-group">
                    <button type="button" class="btn btn-primary" onclick="loadCharts()">Actualiser les graphiques</button>
                </div>
                <div class="form-group" style="margin-left: auto;">
                    <button type="button" class="btn btn-secondary" style="background:#e74a3b;" onclick="downloadPDF()">
                        📥 Télécharger PDF
                    </button>
                </div>
            </form>
        </div>

        <!-- Graphiques -->
        <div class="charts-grid">
            <div class="chart-container">
                <h3>Taux d'Occupation (%)</h3>
                <canvas id="occupancyChart"></canvas>
            </div>
            <div class="chart-container">
                <h3>Top 10 Utilisateurs (Nb Demandes)</h3>
                <canvas id="userChart"></canvas>
            </div>
        </div>
    </div>

    <script>
        // Initialisation dates (Mois courant)
        const today = new Date();
        const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
        
        document.getElementById('startDate').valueAsDate = firstDay;
        document.getElementById('endDate').valueAsDate = lastDay;

        let occupancyChartInstance = null;
        let userChartInstance = null;

        document.addEventListener('DOMContentLoaded', loadCharts);

        async function loadCharts() {
            const start = document.getElementById('startDate').value;
            const end = document.getElementById('endDate').value;

            try {
                const response = await fetch(`../controllers/api.php?action=getReportStats&start=${start}&end=${end}`);
                const result = await response.json();

                if(result.success) {
                    updateOccupancyChart(result.data.occupancy);
                    updateUserChart(result.data.users);
                }
            } catch (e) {
                console.error("Erreur chargement stats", e);
            }
        }

        function updateOccupancyChart(data) {
            const ctx = document.getElementById('occupancyChart').getContext('2d');
            const labels = data.map(item => item.equipement_nom);
            const values = data.map(item => item.taux_occupation);

            if(occupancyChartInstance) occupancyChartInstance.destroy();

            occupancyChartInstance = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Taux d\'occupation (%)',
                        data: values,
                        backgroundColor: 'rgba(54, 162, 235, 0.6)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: { y: { beginAtZero: true, max: 100 } }
                }
            });
        }

        function updateUserChart(data) {
            const ctx = document.getElementById('userChart').getContext('2d');
            // Top 10 users
            const topUsers = data.slice(0, 10);
            const labels = topUsers.map(item => item.nom + ' ' + item.prenom);
            const values = topUsers.map(item => item.total_demandes);
            const approved = topUsers.map(item => item.approuvees);

            if(userChartInstance) userChartInstance.destroy();

            userChartInstance = new Chart(ctx, {
                type: 'bar', // ou 'pie'
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Total Demandes',
                        data: values,
                        backgroundColor: '#4e73df'
                    }, {
                        label: 'Approuvées',
                        data: approved,
                        backgroundColor: '#1cc88a'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y' // Barres horizontales pour lire les noms facilement
                }
            });
        }

        function downloadPDF() {
            const form = document.getElementById('filterForm');
            // Création dynamique d'un formulaire pour soumettre en POST vers le téléchargement
            const tempForm = document.createElement('form');
            tempForm.action = '../controllers/api.php?action=downloadReservationReport';
            tempForm.method = 'POST';
            tempForm.target = '_blank';

            const start = document.createElement('input');
            start.name = 'date_debut';
            start.value = document.getElementById('startDate').value;
            
            const end = document.createElement('input');
            end.name = 'date_fin';
            end.value = document.getElementById('endDate').value;

            tempForm.appendChild(start);
            tempForm.appendChild(end);
            document.body.appendChild(tempForm);
            tempForm.submit();
            document.body.removeChild(tempForm);
        }
    </script>
</body>
</html>