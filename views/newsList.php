<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actualités - Laboratoire</title>
    <link rel="stylesheet" href="views/landingPage.css">
</head>
<body>
    <?php include 'views/components/header.php'; ?>
    
    <div class="page-header">
        <div class="container">
            <h1>📰 Toutes les Actualités</h1>
            <p>Restez informé des dernières nouvelles du laboratoire</p>
        </div>
    </div>
    
    <div class="container" style="padding: 60px 20px;">
        <div class="news-grid">
            <?php foreach ($news as $item): ?>
            <div class="news-card">
                <div class="news-image" style="background-image: url('<?php echo htmlspecialchars($item['image'] ?? 'assets/default-news.jpg'); ?>');">
                    <div class="news-badge">
                        <?php 
                        $icons = ['projet' => '🤖', 'publication' => '📄', 'événement' => '📅', 'soutenance' => '🎓', 'autre' => '📰'];
                        echo $icons[$item['type']] ?? '📰';
                        ?>
                    </div>
                </div>
                <div class="news-card-content">
                    <span class="news-type"><?php echo htmlspecialchars(ucfirst($item['type'])); ?></span>
                    <h3><?php echo htmlspecialchars($item['titre']); ?></h3>
                    <p class="news-date">📅 <?php echo date('d F Y', strtotime($item['date_publication'])); ?></p>
                    <p class="news-description">
                        <?php echo htmlspecialchars(substr($item['description'], 0, 120)) . '...'; ?>
                    </p>
                    <a href="news_detail.php?id=<?php echo $item['id']; ?>" class="news-link">Lire la suite →</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
            <a href="?page=<?php echo $page - 1; ?>" class="page-btn">← Précédent</a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?page=<?php echo $i; ?>" class="page-num <?php echo $i === $page ? 'active' : ''; ?>">
                <?php echo $i; ?>
            </a>
            <?php endfor; ?>
            
            <?php if ($page < $totalPages): ?>
            <a href="?page=<?php echo $page + 1; ?>" class="page-btn">Suivant →</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <?php include 'views/components/footer.php'; ?>
</body>
</html>

<style>
.page-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 80px 0;
    text-align: center;
}

.page-header h1 {
    font-size: 3em;
    margin-bottom: 15px;
}

.page-header p {
    font-size: 1.3em;
    opacity: 0.9;
}

.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
    margin-top: 60px;
}

.page-btn,
.page-num {
    padding: 10px 20px;
    background: white;
    color: #667eea;
    text-decoration: none;
    border-radius: 8px;
    border: 2px solid #667eea;
    transition: all 0.3s;
}

.page-btn:hover,
.page-num:hover {
    background: #667eea;
    color: white;
}

.page-num.active {
    background: #667eea;
    color: white;
    font-weight: bold;
}
</style>
