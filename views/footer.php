<?php
class FooterView {
    public function render() {
?>
<footer>
    <div class="footer-content">
        <div class="footer-section">
            <h3>Contact</h3>
            <p>📍 École Supérieure d'Informatique<br>
            BP 68M, Oued Smar, Alger 16309</p>
            <p>📧 contact@labo-esi.dz<br>
            📞 +213 (0)21 43 91 23</p>
        </div>
        
        <div class="footer-section">
            <h3>Navigation</h3>
            <ul>
                <li><a href="#accueil">Accueil</a></li>
                <li><a href="#projets">Projets</a></li>
                <li><a href="#publications">Publications</a></li>
                <li><a href="#equipements">Équipements</a></li>
                <li><a href="#membres">Membres</a></li>
                <li><a href="#contact">Contact</a></li>
            </ul>
        </div>
        
        <div class="footer-section">
            <h3>Liens Utiles</h3>
            <ul>
                <li><a href="https://www.esi.dz" target="_blank">Site ESI</a></li>
                <li><a href="publications.php">Plateforme de Publication</a></li>
                <li><a href="equipment.php">Réservation d'Équipements</a></li>
                <li><a href="opportunities.php">Offres et Opportunités</a></li>
            </ul>
        </div>
        
        <div class="footer-section">
            <h3>🎓 ESI</h3>
            <p>École Supérieure d'Informatique - Leader en formation et recherche informatique en Algérie</p>
            <div class="social-footer">
                <a href="#" title="Facebook">📘</a>
                <a href="#" title="Twitter">🐦</a>
                <a href="#" title="LinkedIn">💼</a>
            </div>
        </div>
    </div>
    
    <div class="footer-bottom">
        <p>&copy; 2025 Laboratoire d'Informatique ESI. Tous droits réservés.</p>
    </div>
</footer>
<?php
    }
}