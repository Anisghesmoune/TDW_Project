<?php
class OrganigrammeSectionView {
    private $director;
    private $hierarchyTree;
    private $stats;
    
    public function __construct($director = null, $hierarchyTree = [], $stats = null) {
        $this->director = $director;
        $this->hierarchyTree = is_array($hierarchyTree) ? $hierarchyTree : [];
        $this->stats = $stats;
    }
    
    public function render() {
?>
<section class="organigramme-section">
    <div class="container">
        <h2 class="section-title">🏛️ Notre Organigramme</h2>
        <p class="section-subtitle">Structure hiérarchique et organisation du laboratoire</p>
        
        <?php if ($this->stats): ?>
        <div class="org-stats-grid">
            <div class="org-stat-card">
                <div class="org-icon">👥</div>
                <div class="org-number"><?php echo $this->stats['total_membres'] ?? 0; ?></div>
                <div class="org-label">Membres</div>
            </div>
            <div class="org-stat-card">
                <div class="org-icon">📊</div>
                <div class="org-number"><?php echo $this->stats['niveaux'] ?? 0; ?></div>
                <div class="org-label">Niveaux hiérarchiques</div>
            </div>
            <div class="org-stat-card">
                <div class="org-icon">👔</div>
                <div class="org-number"><?php echo $this->stats['directeurs'] ?? 0; ?></div>
                <div class="org-label">Directeurs</div>
            </div>
            <div class="org-stat-card">
                <div class="org-icon">🎯</div>
                <div class="org-number"><?php echo $this->stats['chefs_equipe'] ?? 0; ?></div>
                <div class="org-label">Chefs d'équipe</div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($this->hierarchyTree) && count($this->hierarchyTree) > 0): ?>
        <div class="org-tree-container">
            <h3 style="text-align: center; margin-bottom: 40px; color: #333; font-size: 1.8rem;">
                Structure Organisationnelle
            </h3>
            <div class="tree">
                <?php $this->renderTreeNode($this->hierarchyTree, 1); ?>
            </div>
        </div>
        <?php else: ?>
        <div class="no-org-container">
            <div class="no-org-icon">📋</div>
            <p class="no-org">Aucune structure organisationnelle disponible pour le moment.</p>
            <p class="no-org-hint">L'organigramme sera bientôt disponible.</p>
        </div>
        <?php endif; ?>
    </div>
</section>


<?php
    }
    
    private function renderTreeNode($members, $level = 1) {
        if (empty($members)) return;
        
        echo '<ul>';
        foreach ($members as $member) {
            echo '<li>';
            
            $nodeClass = 'tree-node level-' . $level;
            $levelLabel = $this->getLevelLabel($level);
            ?>
            <div class="<?php echo $nodeClass; ?>">
                <div class="tree-node-level-badge">N<?php echo $level; ?></div>
                
                <img src="<?php echo htmlspecialchars($member['photo_profil'] ?? 'assets/default-avatar.png'); ?>" 
                     alt="<?php echo htmlspecialchars($member['nom'] ?? ''); ?>" 
                     class="tree-node-photo"
                     onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%22100%22 height=%22100%22><rect fill=%22%23667eea%22 width=%22100%22 height=%22100%22/><text x=%2250%25%22 y=%2250%25%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22 font-size=%2250%22 fill=%22white%22>👤</text></svg>'">
                
                <h4><?php echo htmlspecialchars($member['nom'] . ' ' . $member['prenom']); ?></h4>
                <p class="tree-node-poste"><?php echo htmlspecialchars($member['poste'] ?? $levelLabel); ?></p>
                <?php if (!empty($member['grade'])): ?>
                <p class="tree-node-grade">📚 <?php echo htmlspecialchars($member['grade']); ?></p>
                <?php endif; ?>
            </div>
            
            <?php
            if (!empty($member['subordinates']) && is_array($member['subordinates'])) {
                $this->renderTreeNode($member['subordinates'], $level + 1);
            }
            
            echo '</li>';
        }
        echo '</ul>';
    }
    
    private function getLevelLabel($level) {
        $labels = [
            1 => 'Directeur',
            2 => 'Directeur Adjoint',
            3 => 'Chef d\'équipe',
            4 => 'Responsable',
            5 => 'Membre'
        ];
        
        return $labels[$level] ?? 'Membre';
    }
}
?>