<?php
require_once __DIR__ . '/Component.php';

class UISlider extends Component {
    
    public function render() {
        if (empty($this->data)) return '';

        $slidesHtml = '';
        $dotsHtml = '';
        
        // Génération des slides
        foreach ($this->data as $index => $slide) {
            $activeClass = ($index === 0) ? 'active' : '';
            // Gestion image par défaut si manquante
            $img = !empty($slide['image']) ? $slide['image'] : 'assets/default-slide.jpg';
            
            $slidesHtml .= "
            <div class='slide $activeClass' style='background-image: url(\"$img\")'>
                <div class='slide-content'>
                    <h2 class='slide-title'>".htmlspecialchars($slide['titre'])."</h2>
                    <p class='slide-description'>".htmlspecialchars(substr($slide['description'] ?? '', 0, 150))."...</p>
                    <a href='".($slide['lien_detail'] ?? '#')."' class='slide-btn'>En savoir plus →</a>
                </div>
            </div>";

            // Génération des points (dots)
            $dotsHtml .= "<span class='dot $activeClass' onclick='currentSlide($index)'></span>";
        }

        // Retourne le HTML complet + le Script JS encapsulé
        return <<<HTML
        <div class="hero-slider">
            <div class="slider-container">
                $slidesHtml
            </div>
            
            <!-- Contrôles -->
            <button class="slider-prev" onclick="changeSlide(-1)">❮</button>
            <button class="slider-next" onclick="changeSlide(1)">❯</button>
            
            <div class="slider-dots">
                $dotsHtml
            </div>
        </div>

        <script>
            let slideIndex = 0;
            const slides = document.querySelectorAll('.slide');
            const dots = document.querySelectorAll('.dot');
            let timer;

            function showSlide(n) {
                if (n >= slides.length) slideIndex = 0;
                if (n < 0) slideIndex = slides.length - 1;

                // Reset
                slides.forEach(s => s.classList.remove('active'));
                dots.forEach(d => d.classList.remove('active'));

                // Active
                slides[slideIndex].classList.add('active');
                if(dots[slideIndex]) dots[slideIndex].classList.add('active');
            }

            function changeSlide(n) {
                clearInterval(timer); // Reset timer on manual click
                showSlide(slideIndex += n);
                startTimer();
            }

            function currentSlide(n) {
                clearInterval(timer);
                slideIndex = n;
                showSlide(slideIndex);
                startTimer();
            }

            function startTimer() {
                timer = setInterval(() => {
                    slideIndex++;
                    showSlide(slideIndex);
                }, 5000);
            }

            // Démarrage automatique si des slides existent
            if(slides.length > 0) startTimer();
        </script>
HTML;
    }
}
?>