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
            '../../assets/css/public.css',
            '../../assets/css/components.css',
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
        $current = $this->data['active_filters'];
        $canCreate = $this->data['can_create'] ?? false; 

        // --- EN-TÊTE ---
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

        // --- OPTIONS FILTRES ---
        $optThemes = '<option value="">Toutes les thématiques</option>';
        if (!empty($lists['thematics'])) {
            foreach($lists['thematics'] as $t) {
                $sel = ($current['thematique'] == $t['id']) ? 'selected' : '';
                $optThemes .= "<option value='{$t['id']}' $sel>{$t['nom']}</option>";
            }
        }

        $optLeaders = '<option value="">Tous les responsables</option>';
        if (!empty($lists['leaders'])) {
            foreach($lists['leaders'] as $l) {
                $sel = ($current['responsable'] == $l['id']) ? 'selected' : '';
                $nom = htmlspecialchars($l['nom'] . ' ' . $l['prenom']);
                $optLeaders .= "<option value='{$l['id']}' $sel>$nom</option>";
            }
        }

        // --- BARRE DE FILTRES (Statut caché et forcé à 'en_cours') ---
        $selStatut = !empty($current['statut']) ? $current['statut'] : 'en_cours';

        $html .= <<<HTML
        <section class="container filter-container">
            <form action="projects.php" method="GET" class="project-filters">
                <input type="text" name="search" placeholder="Mot-clé..." value="{$current['search']}" class="form-control">
                
                <select name="thematique" class="form-control">$optThemes</select>
                <select name="responsable" class="form-control">$optLeaders</select>
                
                <!-- Statut caché : Filtre par défaut -->
                <input type="hidden" name="statut" value="$selStatut">

                <button type="submit" class="btn-filter">Filtrer</button>
                <a href="projects.php" class="btn-reset">Réinitialiser</a>
            </form>
        </section>
HTML;

        // --- LISTE ---
        $html .= '<section class="container">';
        if (empty($projects)) {
            $html .= '<div class="alert alert-info" style="text-align:center; margin-top:20px;">Aucun projet ne correspond à votre recherche.</div>';
        } else {
            $html .= '<div class="grid-layout project-grid">';
            foreach ($projects as $proj) {
                $card = new UIProjectCard($proj);
                $html .= $card->render();
            }
            $html .= '</div>';
        }
        $html .= '</section>';

        // --- MODAL ---
        if ($canCreate) {
            $html .= $this->renderCreateModal();
        }

        return $html;
    }

    // --- PARTIE MODAL ---
    private function renderCreateModal() {
        $usersList = $this->data['filters_data']['leaders'] ?? [];
        
        // Récupération de l'ID utilisateur connecté
        $currentUserId = $_SESSION['user_id'] ?? 0;
        
        $userOptions = '';
        foreach($usersList as $u) {
            // Si c'est l'utilisateur connecté, on le sélectionne par défaut
            $selected = ($u['id'] == $currentUserId) ? 'selected' : '';
            $userOptions .= "<option value='{$u['id']}' $selected>".htmlspecialchars($u['nom'].' '.$u['prenom'])."</option>";
        }

        return <<<HTML
        <!-- MODAL -->
        <div id="projectModal" class="modal" style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; overflow:auto; background-color:rgba(0,0,0,0.5);">
            <div class="modal-content" style="background-color:#fefefe; margin:10% auto; padding:20px; border:1px solid #888; width:50%; border-radius:8px;">
                <div class="modal-header">
                    <span class="close-btn" onclick="closeModal()" style="color:#aaa; float:right; font-size:28px; font-weight:bold; cursor:pointer;">&times;</span>
                    <h2 id="modalTitle" style="margin-top:0;">➕ Ajouter un projet</h2>
                </div>
                <div class="modal-body">
                    <form id="projectForm">
                        <div id="alertContainer"></div>
                        <input type="hidden" id="projectId" name="id">
                        
                        <div class="form-row">
                            <div class="form-group" style="margin-bottom:15px;">
                                <label for="titre" style="display:block;margin-bottom:5px;">Titre du projet <span class="required" style="color:red">*</span></label>
                                <input type="text" class="form-control" id="titre" name="titre" required placeholder="Ex: Développement d'un système IA" style="width:100%; padding:8px;">
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom:15px;">
                            <label for="description" style="display:block;margin-bottom:5px;">Description <span class="required" style="color:red">*</span></label>
                            <textarea class="form-control" id="description" name="description" rows="4" required placeholder="Décrivez les objectifs..." style="width:100%; padding:8px;"></textarea>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom:15px;">
                            <div class="form-group">
                                <label for="type_financement" style="display:block;margin-bottom:5px;">Type de financement <span class="required" style="color:red">*</span></label>
                                <input type="text" class="form-control" id="type_financement" name="type_financement" required placeholder="Ex: public, privé" style="width:100%; padding:8px;">
                            </div>

                            <div class="form-group">
                                <label for="statut" style="display:block;margin-bottom:5px;">Statut <span class="required" style="color:red">*</span></label>
                                <select class="form-control" id="statut" name="statut" required style="width:100%; padding:8px;">
                                    <option value="soumis">📋 Soumis (En attente)</option>
                                    <option value="en_cours">▶️ En cours</option>
                                    <option value="termine">✅ Terminé</option>
                                </select>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom:15px;">
                            <div class="form-group">
                                <label for="date_debut" style="display:block;margin-bottom:5px;">Date de début <span class="required" style="color:red">*</span></label>
                                <input type="date" class="form-control" id="date_debut" name="date_debut" required style="width:100%; padding:8px;">
                            </div>
                            <div class="form-group">
                                <label for="date_fin" style="display:block;margin-bottom:5px;">Date de fin</label>
                                <input type="date" class="form-control" id="date_fin" name="date_fin" style="width:100%; padding:8px;">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="responsable_id" style="display:block;margin-bottom:5px;">Responsable <span class="required" style="color:red">*</span></label>
                                <!-- Si non admin, on pourrait mettre 'disabled' ici -->
                                <select class="form-control" id="responsable_id" name="responsable_id" required style="width:100%; padding:8px;">
                                    {$userOptions}
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer" style="text-align:right; margin-top:20px;">
                    <button type="button" class="btn-reset" onclick="closeModal()" style="margin-right:10px; background:none; border:none; cursor:pointer;">Annuler</button>
                    <button type="button" class="btn-filter" onclick="saveProject()" style="padding:10px 20px; background:var(--primary-color); color:white; border:none; border-radius:5px; cursor:pointer;">💾 Enregistrer</button>
                </div>
            </div>
        </div>

        <script>
            function openModal(editMode = false, projectId = null) {
                const modal = document.getElementById('projectModal');
                const form = document.getElementById('projectForm');
                
                form.reset();
                document.getElementById('projectId').value = '';
                document.getElementById('modalTitle').textContent = '➕ Ajouter un projet';

                // Pré-sélectionner le responsable (utilisateur connecté) si ajout
                if(!editMode) {
                    const respSelect = document.getElementById('responsable_id');
                    respSelect.value = "{$currentUserId}";
                }

                modal.style.display = 'block';
                document.body.style.overflow = 'hidden';
            }

            function closeModal() {
                const modal = document.getElementById('projectModal');
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
                document.getElementById('projectForm').reset();
            }

            window.onclick = function(event) {
                const modal = document.getElementById('projectModal');
                if (event.target == modal) {
                    closeModal();
                }
            }

            function saveProject() {
                const form = document.getElementById('projectForm');
                
                if (!form.checkValidity()) {
                    form.reportValidity();
                    return;
                }
                
                const formData = new FormData(form);
                const data = Object.fromEntries(formData);
                
                if (!data.date_fin) delete data.date_fin;

                const projectId = document.getElementById('projectId').value;
                const action = projectId ? 'updateProject&id='+projectId : 'createProject';
                const url = '../controllers/api.php?action=' + action;
                
                fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        alert('✅ ' + result.message);
                        closeModal();
                        window.location.reload(); 
                    } else {
                        alert('❌ ' + (result.message || result.errors.join(', ')));
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('❌ Erreur de connexion au serveur');
                });
            }
        </script>
HTML;
    }
}
?>