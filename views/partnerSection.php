<?php
class PartnerSectionView {
    private $partners;
    
    public function __construct($partners) {
        $this->partners = $partners;
    }
    
    public function render() {
?>
<section class="partners-section">
    <h2 class="section-title">Nos Partenaires</h2>
    <p class="section-subtitle">Collaborations académiques et industrielles</p>
    
    <div class="partners-grid">
        <?php 
        if (!empty($this->partners)) {
            foreach ($this->partners as $partner): 
        ?>
        <div class="partner-logo">
            <img src="<?php echo htmlspecialchars($partner['logo'] ?? 'assets/default-partner.png'); ?>" 
                 alt="<?php echo htmlspecialchars($partner['nom']); ?>"
                 onerror="this.outerHTML='<div class=\'partner-placeholder\'><?php echo htmlspecialchars($partner['nom']); ?></div>'">
            
        </div>
        <?php 
            endforeach;
        } else {
            $this->renderDefaultPartners();
        }
        ?>
    </div>
</section>
<?php
    }
    
    private function renderDefaultPartners() {
?>
<div class="partner-logo">🏛️<br>MIT</div>
<div class="partner-logo">🏢<br>Microsoft</div>
<div class="partner-logo">🎓<br>Sorbonne</div>
<div class="partner-logo">🏭<br>Huawei</div>
<div class="partner-logo">🏫<br>Stanford</div>
<div class="partner-logo">💼<br>IBM</div>
<?php
    }
}
