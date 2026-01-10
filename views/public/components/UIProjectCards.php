<?php
require_once __DIR__ . '/Component.php';

class UIProjectCard extends Component {
    // $data correspond à une ligne de la base de données (un projet)

    public function render() {
        $id = $this->data['id'];
        $titre = htmlspecialchars($this->data['titre']);
        // Description courte
        $desc = htmlspecialchars(substr($this->data['description'] ?? '', 0, 100)) . '...';
        
        // Données jointes
        $chef = htmlspecialchars($this->data['responsable_nom'] ?? 'Non assigné');
        $membres = $this->data['nb_membres'] ?? 0;
        $financement = htmlspecialchars($this->data['type_financement'] ?? 'Non spécifié');
        $themes = htmlspecialchars($this->data['thematiques_list'] ?? 'Général');
        
        // Gestion visuelle du statut (Enum)
        $statut = $this->data['statut'];
        $badgeClass = 'badge-secondary';
        $statutLabel = $statut;

        if($statut == 'en_cours') {
            $badgeClass = 'badge-success'; 
            $statutLabel = 'En cours';
        }
        elseif($statut == 'termine') {
            $badgeClass = 'badge-info';
            $statutLabel = 'Terminé';
        }
        elseif($statut == 'soumis') {
            $badgeClass = 'badge-warning';
            $statutLabel = 'Soumis';
        }

        return <<<HTML
        <article class="event-card generic-card">
            <div class="card-header">
                <span class="theme-tag">📂 {$themes}</span>
                <span class="status-badge {$badgeClass}">{$statutLabel}</span>
            </div>
            
            <div class="card-body">
                <h3>{$titre}</h3>
                <p class="description">{$desc}</p>
                
                <div class="project-meta">
                    <div class="meta-row" title="Responsable">
                        <i class="fas fa-user-tie"></i> <span>{$chef}</span>
                    </div>
                    <div class="meta-row" title="Équipe">
                        <i class="fas fa-users"></i> <span>{$membres} membres</span>
                    </div>
                    <div class="meta-row" title="Financement">
                        <i class="fas fa-coins"></i> <span>{$financement}</span>
                    </div>
                </div>

                <div class="card-footer">
                 <a href="index.php?route=project-details&id=$id " class="btn-detail">Voir la fiche →</a>
                </div>
            </div>
        </article>
HTML;
    }
}
?>