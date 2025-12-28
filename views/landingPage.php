<?php
class LandingPageView {
    private $data;

    public function __construct($data) {
        $this->data = $data ?? [];
    }
   private function getDefaultMenu() {
        return [
            ['title' => 'Accueil', 'url' => 'index.php'],
            ['title' => 'Projets', 'url' => 'projects.php'],
            ['title' => 'Publications', 'url' => 'publications.php'],
            ['title' => 'Équipements', 'url' => 'equipment.php'],
            ['title' => 'Membres', 'url' => 'members.php'],
            ['title' => 'Contact', 'url' => 'contact.php']
        ];
    }
    

    public function render() {
        ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Laboratoire d'Informatique - ESI</title>
            <link rel="stylesheet" href="views/landingPage.css">
            <link rel="stylesheet" href="views/organigramme.css">
            <link rel="stylesheet" href="views/diaporama.css">

        </head>
        <body>
            <?php 
            $this->renderHeader();
            
            $this->renderDiaporama();
            ?>
            
            <div class="container">
                <?php 
                $this->renderNewsSection();
                $this->renderAboutSection();
                $this->renderEventsSection();
                $this->renderOrganigrammeSection();
                $this->renderPartnersSection();
                ?>
            </div>
            
            <?php $this->renderFooter(); ?>
            
            <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
            <script src="views/landingPage.js"></script>
        </body>
        </html>
        <?php
    }


private function renderHeader() {
    require_once 'views/header.php';
    $menu = $this->data['menu'] ?? $this->getDefaultMenu();
    $header = new HeaderView($menu);
    $header->render();
}

// La méthode renderNav() n'est plus nécessaire car la navigation est intégrée dans renderHeader()

    private function renderDiaporama() {
        require_once 'views/diaporama.php';
        $slides = $this->data['slides'] ?? [];
        $diaporama = new DiaporamaView($slides);
        $diaporama->render();
    }

    private function renderNewsSection() {
        require_once 'views/newsSection.php';
        $news = $this->data['news'] ?? [];
        $newsSection = new NewsSectionView($news);
        $newsSection->render();
    }

    private function renderAboutSection() {
        require_once 'views/aboutsection.php';
        $aboutSection = new AboutSectionView();
        $aboutSection->render();
    }

    private function renderEventsSection() {
        require_once 'views/eventSection.php';
        $events = $this->data['events'] ?? [];
        $eventCount = count($events);
        $eventSection = new EventSectionView($events, $eventCount);
        $eventSection->render();
    }

    private function renderOrganigrammeSection() {
        require_once 'views/organigramme.php';
        $org = $this->data['organigramme'] ?? [];
        $orgSection = new OrganigrammeSectionView(
            $org['director'] ?? null,
            $org['hierarchyTree'] ?? [],
            $org['stats'] ?? null
        );
        $orgSection->render();
    }

    private function renderPartnersSection() {
        require_once 'views/partnerSection.php';
        $partners = $this->data['partners'] ?? [];
        $partnerSection = new PartnerSectionView($partners);
        $partnerSection->render();
    }

    private function renderFooter() {
        require_once 'views/footer.php';
        $footer = new FooterView();
        $footer->render();
    }
}
?>
