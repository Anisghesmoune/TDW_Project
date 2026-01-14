<?php
require_once __DIR__ . 'models/News.php';

class NewsController {
    private $newsModel;
    
    public function __construct() {
        $this->newsModel = new News();
    }
 
    public function getNews() {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = isset($_GET['perPage']) ? (int)$_GET['perPage'] : 4;
        
        $news = $this->newsModel->getAll($page, $perPage);
        $total = $this->newsModel->count();
        
        header('Content-Type: application/json');
        echo json_encode([
            'news' => $news,
            'total' => $total,
            'totalPages' => ceil($total / $perPage),
            'currentPage' => $page
        ]);
        exit;
    }
    
 
    public function index() {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = 9;
        
        $news = $this->newsModel->getAll($page, $perPage);
        $total = $this->newsModel->count();
        $totalPages = ceil($total / $perPage);
        
        include 'views/news_list.php';
    }
    
  
    public function show() {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if ($id > 0) {
            $news = $this->newsModel->getById($id);
            
            if ($news) {
                include 'views/news_detail.php';
            } else {
                header('Location: news.php');
                exit;
            }
        } else {
            header('Location: news.php');
            exit;
        }
    }
}
?>
