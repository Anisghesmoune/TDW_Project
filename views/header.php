<?php
class HeaderView {
    private $menu;

    public function __construct($menu = []) {
        $this->menu = $menu;
    }

    public function render() {
        ?>
        <header>
            <div class="top-bar">
                <div class="logo">
                    <img src="assets/logos/logo_esi.png" alt="Logo ESI" style="height: 40px; vertical-align: middle; margin-right: 10px;">
                    
                </div>

                <!-- Menu centré -->
                <?php if (!empty($this->menu)): ?>
                <nav class="main-nav">
                    <ul>
                        <?php foreach ($this->menu as $item): ?>
                            <li>
                                <a href="<?php echo htmlspecialchars($item['url']); ?>">
                                    <?php echo htmlspecialchars($item['title']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </nav>
                <?php endif; ?>

                <div class="social-links">
                    <!-- <a href="#" title="Facebook">📘</a>
                    <a href="#" title="Twitter">🐦</a>
                    <a href="#" title="LinkedIn">💼</a>
                    <a href="https://www.esi.dz" title="Site ESI" target="_blank">🌐</a> -->
                </div>
            </div>
        </header>
        <?php
    }
}
