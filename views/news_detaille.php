<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($news['titre']); ?> - Laboratoire</title>
    <link rel="stylesheet" href="views/landingPage.css">
</head>
<body>
    <?php include 'views/components/header.php'; ?>
    
    <div class="container" style="padding: 60px 20px;">
        <a href="news.php" class="back-btn">← Retour aux actualités</a>
        
        <article class="news-detail">
            <div class="news-detail-header">
                <span class="news-type"><?php echo htmlspecialchars(ucfirst($news['type'])); ?></span>
                <h1><?php echo htmlspecialchars($news['titre']); ?></h1>
                <div class="news-meta">
                    <span>📅 <?php echo date('d F Y', strtotime($news['date_publication'])); ?></span>
                    <?php if ($news['auteur_id']): ?>
                    <span>✍️ Par l'équipe du laboratoire</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($news['image']): ?>
            <div class="news-detail-image">
                <img src="<?php echo htmlspecialchars($news['image']); ?>" alt="<?php echo htmlspecialchars($news['titre']); ?>">
            </div>
            <?php endif; ?>
            
            <div class="news-detail-content">
                <?php echo nl2br(htmlspecialchars($news['description'])); ?>
            </div>
            
            <?php if ($news['lien_detail']): ?>
            <div class="news-detail-link">
                <a href="<?php echo htmlspecialchars($news['lien_detail']); ?>" class="btn-primary" target="_blank">
                    En savoir plus →
                </a>
            </div>
            <?php endif; ?>
        </article>
    </div>
    
    <?php include 'views/components/footer.php'; ?>
</body>
</html>

<style>
.back-btn {
    display: inline-block;
    padding: 10px 20px;
    background: #667eea;
    color: white;
    text-decoration: none;
    border-radius: 8px;
    margin-bottom: 30px;
    transition: all 0.3s;
}

.back-btn:hover {
    background: #764ba2;
    transform: translateX(-5px);
}

.news-detail {
    max-width: 900px;
    margin: 0 auto;
    background: white;
    padding: 50px;
    border-radius: 20px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.1);
}

.news-detail-header {
    margin-bottom: 40px;
}

.news-detail-header h1 {
    font-size: 2.5em;
    color: #333;
    margin: 20px 0;
    line-height: 1.3;
}

.news-meta {
    display: flex;
    gap: 30px;
    color: #999;
    font-size: 1em;
}

.news-detail-image {
    margin-bottom: 40px;
    border-radius: 15px;
    overflow: hidden;
}

.news-detail-image img {
    width: 100%;
    height: auto;
    display: block;
}

.news-detail-content {
    font-size: 1.1em;
    line-height: 1.8;
    color: #444;
    margin-bottom: 40px;
}

.news-detail-link {
    text-align: center;
}
</style>