<?php
class DiaporamaView {
    private $slides;
    
    public function __construct($slides = []) {
        $this->slides = is_array($slides) ? $slides : [];
    }
    
    public function render() {
?>
<div class="hero-slider" id="accueil">
    <div class="slider-container">
        <?php 
        if (!empty($this->slides) && count($this->slides) > 0) {
            foreach ($this->slides as $index => $slide): 
                $activeClass = $index === 0 ? 'active' : '';
                $imageUrl = htmlspecialchars($slide['image'] ?? 'assets/default-slide.jpg');
        ?>
        <div class="slide <?php echo $activeClass; ?>" 
             style="background: linear-gradient(rgba(102,126,234,0.7), rgba(118,75,162,0.7)), 
                    url('<?php echo $imageUrl; ?>'); 
                    background-size: cover; background-position: center;">
            <div class="slide-content">
                <h2 class="slide-title"><?php echo htmlspecialchars($slide['titre']); ?></h2>
                <p class="slide-description"><?php echo htmlspecialchars(substr($slide['description'], 0, 150)); ?></p>
                <a href="<?php echo htmlspecialchars($slide['lien_detail'] ?? '#'); ?>" class="slide-btn">
                    En savoir plus →
                </a>
            </div>
        </div>
        <?php 
            endforeach;
        } else {
            $this->renderDefaultSlides();
        }
        ?>
    </div>
    
    <!-- Contrôles du slider -->
    <button class="slider-prev" onclick="diaporama.changeSlide(-1)" aria-label="Diapositive précédente">
        <span>❮</span>
    </button>
    <button class="slider-next" onclick="diaporama.changeSlide(1)" aria-label="Diapositive suivante">
        <span>❯</span>
    </button>
    
    <!-- Indicateurs -->
    <div class="slider-dots">
        <?php 
        $slideCount = !empty($this->slides) && count($this->slides) > 0 ? count($this->slides) : 3;
        for ($i = 0; $i < $slideCount; $i++): 
        ?>
        <span class="dot <?php echo $i === 0 ? 'active' : ''; ?>" 
              onclick="diaporama.goToSlide(<?php echo $i; ?>)" 
              aria-label="Aller à la diapositive <?php echo $i + 1; ?>"></span>
        <?php endfor; ?>
    </div>
    
    <!-- Bouton Play/Pause -->
    <button class="slider-play-pause" onclick="diaporama.toggleAutoplay()" aria-label="Lecture/Pause">
        <span class="play-icon">⏸️</span>
    </button>
</div>



<script>
// Gestionnaire du diaporama
const diaporama = {
    currentSlide: 0,
    slides: null,
    dots: null,
    autoplayInterval: null,
    isPlaying: true,
    autoplayDelay: 5000, // 5 secondes
    
    init() {
        this.slides = document.querySelectorAll('.slide');
        this.dots = document.querySelectorAll('.dot');
        
        if (this.slides.length > 0) {
            this.startAutoplay();
        }
    },
    
    changeSlide(direction) {
        this.goToSlide(this.currentSlide + direction);
    },
    
    goToSlide(n) {
        // Arrêter temporairement l'autoplay quand on navigue manuellement
        this.stopAutoplay();
        
        // Calculer le nouvel index
        if (n >= this.slides.length) {
            this.currentSlide = 0;
        } else if (n < 0) {
            this.currentSlide = this.slides.length - 1;
        } else {
            this.currentSlide = n;
        }
        
        // Mettre à jour l'affichage
        this.updateSlides();
        
        // Redémarrer l'autoplay après 2 secondes
        if (this.isPlaying) {
            setTimeout(() => {
                this.startAutoplay();
            }, 2000);
        }
    },
    
    updateSlides() {
        // Retirer la classe active de tous les slides
        this.slides.forEach(slide => {
            slide.classList.remove('active');
        });
        
        // Retirer la classe active de tous les dots
        this.dots.forEach(dot => {
            dot.classList.remove('active');
        });
        
        // Ajouter la classe active au slide et dot actuels
        this.slides[this.currentSlide].classList.add('active');
        this.dots[this.currentSlide].classList.add('active');
    },
    
    startAutoplay() {
        this.stopAutoplay(); // Nettoyer l'ancien interval
        this.autoplayInterval = setInterval(() => {
            this.currentSlide = (this.currentSlide + 1) % this.slides.length;
            this.updateSlides();
        }, this.autoplayDelay);
    },
    
    stopAutoplay() {
        if (this.autoplayInterval) {
            clearInterval(this.autoplayInterval);
            this.autoplayInterval = null;
        }
    },
    
    toggleAutoplay() {
        const playIcon = document.querySelector('.play-icon');
        
        if (this.isPlaying) {
            this.stopAutoplay();
            this.isPlaying = false;
            playIcon.textContent = '▶️';
        } else {
            this.startAutoplay();
            this.isPlaying = true;
            playIcon.textContent = '⏸️';
        }
    }
};

// Initialiser le diaporama quand la page est chargée
document.addEventListener('DOMContentLoaded', function() {
    diaporama.init();
});

// Gérer la navigation au clavier
document.addEventListener('keydown', function(e) {
    if (e.key === 'ArrowLeft') {
        diaporama.changeSlide(-1);
    } else if (e.key === 'ArrowRight') {
        diaporama.changeSlide(1);
    } else if (e.key === ' ') {
        e.preventDefault();
        diaporama.toggleAutoplay();
    }
});

// Mettre en pause quand l'utilisateur change d'onglet
document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        diaporama.stopAutoplay();
    } else if (diaporama.isPlaying) {
        diaporama.startAutoplay();
    }
});
</script>
<?php
    }
    
    private function renderDefaultSlides() {
?>
<div class="slide active" style="background: linear-gradient(rgba(102,126,234,0.7), rgba(118,75,162,0.7)), url('data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%221920%22 height=%22500%22><rect fill=%22%23667eea%22 width=%221920%22 height=%22500%22/><text x=%2250%25%22 y=%2250%25%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22 font-size=%2260%22 fill=%22white%22>🔬 Recherche Scientifique</text></svg>');">
    <div class="slide-content">
        <h2 class="slide-title">Nouveau Projet de Recherche en IA</h2>
        <p class="slide-description">Découvrez notre dernier projet sur l'intelligence artificielle appliquée à la santé</p>
        <a href="#projets" class="slide-btn">En savoir plus →</a>
    </div>
</div>

<div class="slide" style="background: linear-gradient(rgba(102,126,234,0.7), rgba(118,75,162,0.7)), url('data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%221920%22 height=%22500%22><rect fill=%22%23764ba2%22 width=%221920%22 height=%22500%22/><text x=%2250%25%22 y=%2250%25%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22 font-size=%2260%22 fill=%22white%22>📚 Publications</text></svg>');">
    <div class="slide-content">
        <h2 class="slide-title">Publication dans une revue internationale</h2>
        <p class="slide-description">Notre équipe publie dans IEEE Transactions on Computing</p>
        <a href="#publications" class="slide-btn">Lire l'article →</a>
    </div>
</div>

<div class="slide" style="background: linear-gradient(rgba(102,126,234,0.7), rgba(118,75,162,0.7)), url('data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%221920%22 height=%22500%22><rect fill=%22%23667eea%22 width=%221920%22 height=%22500%22/><text x=%2250%25%22 y=%2250%25%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22 font-size=%2260%22 fill=%22white%22>🎓 Soutenance</text></svg>');">
    <div class="slide-content">
        <h2 class="slide-title">Soutenance de Thèse - Cybersécurité</h2>
        <p class="slide-description">Soutenance publique le 20 Janvier 2026 à 14h00</p>
        <a href="#events" class="slide-btn">S'inscrire →</a>
    </div>
</div>
<?php
    }
}
?>