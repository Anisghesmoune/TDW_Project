<?php
class NavView {
    private $menuItems;
    
    public function __construct($menuItems) {
        $this->menuItems = $menuItems;
    }
    
    public function render() {
?>
<nav>
    <ul>
        <?php foreach($this->menuItems as $item): ?>
        <li>
            <a href="<?php echo htmlspecialchars($item['url']); ?>">
                <?php echo htmlspecialchars($item['title']); ?>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>
</nav>
<?php
    }
}