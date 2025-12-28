<?php
class AboutSectionView {
    public function render() {
?>
<section class="about-section">
    <h2 class="section-title">À propos du Laboratoire</h2>
    <div class="about-content">
        <div class="about-text">
            <h3>🎯 Notre Mission</h3>
            <p>Le Laboratoire d'Informatique de l'ESI est un centre d'excellence dédié à la recherche 
            et l'innovation dans les domaines de l'intelligence artificielle, la cybersécurité, 
            le cloud computing et les systèmes distribués.</p>
            
            <h3>🔬 Nos Domaines de Recherche</h3>
            <ul>
                <li>Intelligence Artificielle et Machine Learning</li>
                <li>Cybersécurité et Cryptographie</li>
                <li>Cloud Computing et Big Data</li>
                <li>Réseaux et IoT</li>
                <li>Systèmes Embarqués</li>
            </ul>
        </div>
        <div class="about-image">
            <img src="assets/laboratory.jpg" alt="Laboratoire" onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%22600%22 height=%22400%22><rect fill=%22%23667eea%22 width=%22600%22 height=%22400%22/><text x=%2250%25%22 y=%2250%25%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22 font-size=%2260%22 fill=%22white%22>🔬 Laboratoire</text></svg>'">
        </div>
    </div>
</section>
<?php
    }
}
