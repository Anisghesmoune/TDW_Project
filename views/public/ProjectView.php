<?php
require_once __DIR__ . '/../../views/public/View.php';
require_once __DIR__ . '/../../views/public/components/UIHeader.php';
require_once __DIR__ . '/../../views/public/components/UIFooter.php';
require_once __DIR__ . '/../../views/public/components/UIProjectCards.php';

class ProjectsView extends View {

    public function render() {
        $config = $this->data['config'] ?? [];
        $menuData = $this->data['menu'] ?? [];

        $customCss = [
            '../../views/landingPage.css',
            '../../assets/css/project.css'
        ];

        $header = new UIHeader("Catalogue des Projets", $config, $menuData, $customCss);
        echo $header->render();

        echo '<main class="main-content">';
        echo $this->content();
        echo '</main>';

        $footer = new UIFooter($config, $menuData);
        echo $footer->render();
    }

    protected function content() {
        $html = '';
        $projects = $this->data['projects'] ?? [];
        $lists = $this->data['filters_data'];
        $canCreate = $this->data['can_create'] ?? false; 

        $html .= <<<HTML
        <section class="container" style="text-align:center; padding: 40px 20px;">
            <h1 class="page-title" style="color:var(--primary-color);">Catalogue des Projets</h1>
            <p>Découvrez nos projets de recherche classés par thématiques</p>
HTML;

        if ($canCreate) {
            $html .= <<<HTML
            <div style="margin-top:20px;">
                <button onclick="openModal()" class="btn-filter" style="padding:10px 20px; font-size:1em; background-color: #28a745;">
                    ➕ Proposer un nouveau projet
                </button>
            </div>
HTML;
        }
        $html .= '</section>';

        $optThemes = '<option value="">Toutes les thématiques</option>';
        if (!empty($lists['thematics'])) {
            foreach($lists['thematics'] as $t) {
                $optThemes .= "<option value='{$t['id']}'>{$t['nom']}</option>";
            }
        }

        $optLeaders = '<option value="">Tous les responsables</option>';
        if (!empty($lists['leaders'])) {
            foreach($lists['leaders'] as $l) {
                $nom = htmlspecialchars($l['nom'] . ' ' . $l['prenom']);
                $optLeaders .= "<option value='{$l['id']}'>$nom</option>";
            }
        }

      
        $html .= <<<HTML
        <section class="container filter-container">
            <form onsubmit="event.preventDefault(); loadProjects();" class="project-filters">
                <input type="text" id="filterSearch" placeholder="Mot-clé..." class="form-control">
                
                <select id="filterTheme" class="form-control" onchange="loadProjects()">$optThemes</select>
                <select id="filterLeader" class="form-control" onchange="loadProjects()">$optLeaders</select>
                
                <input type="hidden" id="filterStatut" value="">

                <button type="button" onclick="loadProjects()" class="btn-filter">Filtrer</button>
                <button type="button" onclick="resetFilters()" class="btn-reset">Réinitialiser</button>
            </form>
        </section>
HTML;

        $html .= '<section class="container">';
        
        $html .= '<div id="projectsLoader" style="display:none; text-align:center; padding:20px;">⏳ Chargement...</div>';
        
        $html .= '<div id="projectsGrid" class="grid-layout project-grid">';
        
        if (empty($projects)) {
            $html .= '<div class="alert alert-info" style="grid-column: 1/-1; text-align:center;">Aucun projet trouvé.</div>';
        } else {
            foreach ($projects as $proj) {
                $card = new UIProjectCard($proj);
                $html .= $card->render();
            }
        }
        $html .= '</div>';
        $html .= '</section>';

        if ($canCreate) {
            $html .= $this->renderCreateModal();
        }

        $html .= $this->renderFilterScript();

        return $html;
    }

    private function renderFilterScript() {
        return <<<HTML
        <script>
        async function loadProjects() {
            const search = document.getElementById('filterSearch').value;
            const theme = document.getElementById('filterTheme').value;
            const leader = document.getElementById('filterLeader').value;
            const statut = document.getElementById('filterStatut').value;

            document.getElementById('projectsLoader').style.display = 'block';
            document.getElementById('projectsGrid').style.opacity = '0.5';

            try {
                let url = `../controllers/api.php?action=getProjects`;
                if(search) url += `&search=\${encodeURIComponent(search)}`;
                if(theme) url += `&thematique=\${theme}`;
                if(leader) url += `&responsable=\${leader}`;
                if(statut) url += `&statut=\${statut}`;

                const response = await fetch(url);
                const result = await response.json();

                if (result.success) {
                    renderGrid(result.data); 
                } else {
                    console.error("Erreur API:", result.message);
                    document.getElementById('projectsGrid').innerHTML = '<p style="text-align:center; color:red">Erreur lors du chargement.</p>';
                }
            } catch (error) {
                console.error("Erreur réseau:", error);
            } finally {
                document.getElementById('projectsLoader').style.display = 'none';
                document.getElementById('projectsGrid').style.opacity = '1';
            }
        }

        function resetFilters() {
            document.getElementById('filterSearch').value = '';
            document.getElementById('filterTheme').value = '';
            document.getElementById('filterLeader').value = '';
            loadProjects(); 
        }

        function renderGrid(projects) {
            const grid = document.getElementById('projectsGrid');
            grid.innerHTML = '';

            if (!projects || projects.length === 0) {
                grid.innerHTML = '<div class="alert alert-info" style="grid-column: 1/-1; text-align:center;">Aucun projet ne correspond à votre recherche.</div>';
                return;
            }

            projects.forEach(p => {
                const img = p.image ? p.image : '../../assets/img/project-default.jpg';
                const desc = p.description ? p.description.substring(0, 100) + '...' : '';
                const dateDebut = new Date(p.date_debut).toLocaleDateString('fr-FR');

                const cardHTML = `
                    <div class="project-card">
                        <div class="card-header">
                            <span class="status-badge status-\${p.statut}">\${p.statut.replace('_', ' ')}</span>
                            <img src="\${img}" alt="Image projet" class="project-img">
                        </div>
                        <div class="card-body">
                            <h3 class="project-title">\${p.titre}</h3>
                            <p class="project-desc">\${desc}</p>
                            <div class="project-meta">
                                <span>📅 \${dateDebut}</span>
                                <span>👤 \${p.responsable_nom || 'N/A'}</span>
                            </div>
                            <a href="index.php?route=project-details&id=\${p.id}" class="btn-details">Voir détails →</a>
                        </div>
                    </div>
                `;
                grid.innerHTML += cardHTML;
            });
        }
        </script>
HTML;
    }

    private function renderCreateModal() {
        $usersList = $this->data['filters_data']['leaders'] ?? [];
        $currentUserId = $_SESSION['user_id'] ?? 0;
        
        $userOptions = '';
        foreach($usersList as $u) {
            $selected = ($u['id'] == $currentUserId) ? 'selected' : '';
            $userOptions .= "<option value='{$u['id']}' $selected>".htmlspecialchars($u['nom'].' '.$u['prenom'])."</option>";
        }

        return <<<HTML
        <div id="projectModal" class="modal" style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; overflow:auto; background-color:rgba(0,0,0,0.5);">
            <div class="modal-content" style="background-color:#fefefe; margin:10% auto; padding:20px; border:1px solid #888; width:50%; border-radius:8px;">
                <div class="modal-header">
                    <span class="close-btn" onclick="closeModal()" style="color:#aaa; float:right; font-size:28px; font-weight:bold; cursor:pointer;">&times;</span>
                    <h2 id="modalTitle" style="margin-top:0;">➕ Ajouter un projet</h2>
                </div>
                <div class="modal-body">
                    <form id="projectForm">
                        <input type="hidden" id="projectId" name="id">
                        
                        <div class="form-group" style="margin-bottom:15px;">
                            <label>Titre <span style="color:red">*</span></label>
                            <input type="text" class="form-control" name="titre" required style="width:100%; padding:8px;">
                        </div>

                        <div class="form-group" style="margin-bottom:15px;">
                            <label>Description <span style="color:red">*</span></label>
                            <textarea class="form-control" name="description" rows="4" required style="width:100%; padding:8px;"></textarea>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom:15px;">
                            <div class="form-group">
                                <label>Financement</label>
                                <input type="text" class="form-control" name="type_financement" style="width:100%; padding:8px;">
                            </div>
                            <div class="form-group">
                                <label>Statut</label>
                                <select class="form-control" name="statut" required style="width:100%; padding:8px;">
                                    <option value="soumis">Soumis</option>
                                    <option value="en_cours">En cours</option>
                                    <option value="termine">Terminé</option>
                                </select>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom:15px;">
                            <div class="form-group">
                                <label>Date début <span style="color:red">*</span></label>
                                <input type="date" class="form-control" name="date_debut" required style="width:100%; padding:8px;">
                            </div>
                            <div class="form-group">
                                <label>Responsable</label>
                                <select class="form-control" name="responsable_id" required style="width:100%; padding:8px;">
                                    {$userOptions}
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer" style="text-align:right; margin-top:20px;">
                    <button type="button" class="btn-reset" onclick="closeModal()">Annuler</button>
                    <button type="button" class="btn-filter" onclick="saveProject()">Enregistrer</button>
                </div>
            </div>
        </div>

        <script>
            function openModal() { document.getElementById('projectModal').style.display = 'block'; }
            function closeModal() { document.getElementById('projectModal').style.display = 'none'; }
            window.onclick = function(e) { if (e.target == document.getElementById('projectModal')) closeModal(); }

            function saveProject() {
                const form = document.getElementById('projectForm');
                if (!form.checkValidity()) { form.reportValidity(); return; }
                
                const data = Object.fromEntries(new FormData(form));
                const action = data.id ? 'updateProject&id='+data.id : 'createProject';

                fetch('../controllers/api.php?action=' + action, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                })
                .then(r => r.json())
                .then(res => {
                    if(res.success) { alert('✅ Enregistré'); closeModal(); loadProjects(); } 
                    else alert('❌ ' + (res.message || 'Erreur'));
                });
            }
        </script>
HTML;
    }
}
?>