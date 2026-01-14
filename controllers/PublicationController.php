<?php

require_once __DIR__ .  '/../models/Publications.php';

require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/Settings.php';
require_once __DIR__ . '/../models/Menu.php';

class PublicationController {
    
    private $publicationModel;
    private $userModel;
    private $settingsModel;
    private $menuModel;

    public function __construct() {
        $this->publicationModel = new Publication();
        $this->userModel = new UserModel();
        $this->settingsModel = new Settings();
        $this->menuModel = new Menu();
    }

    public function index() {
       
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = 10;
        
        $filters = [
            'type' => $_GET['type'] ?? null,
            'statut' => $_GET['statut'] ?? 'en_attente', 
            'domaine' => $_GET['domaine'] ?? null,
            'year' => $_GET['year'] ?? null
        ];
        
        $filters = array_filter($filters);
        
        $publications = $this->publicationModel->getFiltered($page, $perPage, $filters);
        $totalPublications = $this->publicationModel->countFiltered($filters);
        $totalPages = ceil($totalPublications / $perPage);
        
        $statsByType = $this->publicationModel->getStatsByType();
        $statsByDomain = $this->publicationModel->getStatsByDomain();
        
        $isAdmin = isset($_SESSION['isAdmin']) ;
         $user = $this->userModel->getById($_SESSION['user_id']);
        $config = $this->settingsModel->getAllSettings();
        $menu = $this->menuModel->getMenuTree();

        $data = [
            'title' => 'Gestion des Publications',
            'isAdmin' => $isAdmin,
            'config' => $config,
            'menu' => $menu,
            'publications' => $publications,
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'currentUser'=> $user,
        ];

        require_once __DIR__ . '/../views/public/PublicationView.php';
        $view = new PublicationView($data);
        $view->render();
    }

 
    public function show($id) {
        $publication = $this->publicationModel->getById($id);
        
        if (!$publication) {
            $_SESSION['error'] = "Publication introuvable.";
            header('Location: /publications');
            exit;
        }
        
      
    }
    
    
    public function create() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->store();
        }
        
    }
    
   
    private function store() {
        $errors = $this->validatePublicationData($_POST);
        
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = $_POST;
            header('Location: /publications/create');
            exit;
        }
        
        $lienTelechargement = null;
        if (isset($_FILES['fichier']) && $_FILES['fichier']['error'] === UPLOAD_ERR_OK) {
            $lienTelechargement = $this->uploadFile($_FILES['fichier']);
            
            if (!$lienTelechargement) {
                $_SESSION['error'] = "Erreur lors de l'upload du fichier.";
                $_SESSION['old'] = $_POST;
                header('Location: /publications/create');
                exit;
            }
        }
        
        $this->publicationModel->titre = $_POST['titre'];
        $this->publicationModel->resume = $_POST['resume'] ?? null;
        $this->publicationModel->type = $_POST['type'];
        $this->publicationModel->date_publication = $_POST['date_publication'] ?? null;
        $this->publicationModel->doi = $_POST['doi'] ?? null;
        $this->publicationModel->lien_telechargement = $lienTelechargement;
        $this->publicationModel->domaine = $_POST['domaine'] ?? null;
        $this->publicationModel->statut_validation = 'en_attente';
        $this->publicationModel->soumis_par = $_SESSION['user_id'];
        
        if ($this->publicationModel->create()) {
            $_SESSION['success'] = "Publication soumise avec succès. Elle sera validée par un administrateur.";
            
            exit;
        } else {
            $_SESSION['error'] = "Erreur lors de la création de la publication.";
            $_SESSION['old'] = $_POST;
           
            exit;
        }
    }
    
   
    public function edit($id) {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        
        $publication = $this->publicationModel->getById($id);
        
        if (!$publication) {
            $_SESSION['error'] = "Publication introuvable.";
            header('Location: /publications');
            exit;
        }
        
        if ($publication['soumis_par'] != $_SESSION['user_id'] && $_SESSION['role'] !== 'admin') {
            $_SESSION['error'] = "Vous n'êtes pas autorisé à modifier cette publication.";
            header('Location: /publications');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->update($id);
        }
        
    }
    
   
    private function update($id) {
        $errors = $this->validatePublicationData($_POST);
        
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = $_POST;
            exit;
        }
        
        $publication = $this->publicationModel->getById($id);
        
        $lienTelechargement = $publication['lien_telechargement'];
        if (isset($_FILES['fichier']) && $_FILES['fichier']['error'] === UPLOAD_ERR_OK) {
            $newFile = $this->uploadFile($_FILES['fichier']);
            
            if ($newFile) {
                if ($lienTelechargement && file_exists($lienTelechargement)) {
                    unlink($lienTelechargement);
                }
                $lienTelechargement = $newFile;
            }
        }
        
        $this->publicationModel->id = $id;
        $this->publicationModel->titre = $_POST['titre'];
        $this->publicationModel->resume = $_POST['resume'] ?? null;
        $this->publicationModel->type = $_POST['type'];
        $this->publicationModel->date_publication = $_POST['date_publication'] ?? null;
        $this->publicationModel->doi = $_POST['doi'] ?? null;
        $this->publicationModel->lien_telechargement = $lienTelechargement;
        $this->publicationModel->domaine = $_POST['domaine'] ?? null;
        $this->publicationModel->statut_validation = $publication['statut_validation']; // Garder le statut actuel
        
        if ($this->publicationModel->update()) {
            $_SESSION['success'] = "Publication mise à jour avec succès.";
            header('Location: /publications/' . $id);
            exit;
        } else {
            $_SESSION['error'] = "Erreur lors de la mise à jour de la publication.";
            $_SESSION['old'] = $_POST;
            header('Location: /publications/edit/' . $id);
            exit;
        }
    }
    
   
    public function delete($id) {
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            $_SESSION['error'] = "Accès non autorisé.";
            header('Location: /publications');
            exit;
        }
        
        $publication = $this->publicationModel->getById($id);
        
        if (!$publication) {
            $_SESSION['error'] = "Publication introuvable.";
            header('Location: /publications');
            exit;
        }
        
        if ($publication['lien_telechargement'] && file_exists($publication['lien_telechargement'])) {
            unlink($publication['lien_telechargement']);
        }
        
        if ($this->publicationModel->delete($id)) {
            $_SESSION['success'] = "Publication supprimée avec succès.";
        } else {
            $_SESSION['error'] = "Erreur lors de la suppression de la publication.";
        }
        
        exit;
    }
   
    public function validate($id) {
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
            exit;
        }
        
        if ($this->publicationModel->validate($id)) {
            $_SESSION['success'] = "Publication validée avec succès.";
            echo json_encode(['success' => true, 'message' => 'Publication validée']);
        } else {
            $_SESSION['error'] = "Erreur lors de la validation.";
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la validation']);
        }
        exit;
    }
    
   
    public function reject($id) {
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
            exit;
        }
        
        if ($this->publicationModel->reject($id)) {
            $_SESSION['success'] = "Publication rejetée.";
            echo json_encode(['success' => true, 'message' => 'Publication rejetée']);
        } else {
            $_SESSION['error'] = "Erreur lors du rejet.";
            echo json_encode(['success' => false, 'message' => 'Erreur lors du rejet']);
        }
        exit;
    }
    
   
    public function search() {
        $keyword = $_GET['q'] ?? '';
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
        
        if (empty($keyword)) {
            $publications = [];
        } else {
            $publications = $this->publicationModel->search($keyword, $limit);
        }
        
    }
    
   
    public function myPublications() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = 10;
        
        $publications = $this->publicationModel->getByUser($_SESSION['user_id']);
        
        require_once __DIR__ . 'views/publications/my-publications.php';
    }
    
  
    public function pending() {
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            $_SESSION['error'] = "Accès non autorisé.";
            header('Location: /');
            exit;
        }
        
        $publications = $this->publicationModel->getByValidationStatus('en_attente');
        
        require_once __DIR__ . 'views/admin/publications-pending.php';
    }
    
   
    private function validatePublicationData($data) {
        $errors = [];
        
        if (empty($data['titre'])) {
            $errors['titre'] = "Le titre est obligatoire.";
        } elseif (strlen($data['titre']) > 255) {
            $errors['titre'] = "Le titre ne peut pas dépasser 255 caractères.";
        }
        
        $typesValides = ['article', 'rapport', 'these', 'communication'];
        if (empty($data['type'])) {
            $errors['type'] = "Le type est obligatoire.";
        } elseif (!in_array($data['type'], $typesValides)) {
            $errors['type'] = "Type invalide.";
        }
        
        if (!empty($data['date_publication'])) {
            $date = DateTime::createFromFormat('Y-m-d', $data['date_publication']);
            if (!$date) {
                $errors['date_publication'] = "Format de date invalide.";
            }
        }
        
        if (!empty($data['doi']) && strlen($data['doi']) > 100) {
            $errors['doi'] = "Le DOI ne peut pas dépasser 100 caractères.";
        }
        
        return $errors;
    }
    

    private function uploadFile($file) {
        $uploadDir = 'uploads/publications/';
        
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $allowedTypes = ['application/pdf'];
        if (!in_array($file['type'], $allowedTypes)) {
            return false;
        }
        
        $maxSize = 10 * 1024 * 1024; 
        if ($file['size'] > $maxSize) {
            return false;
        }
        
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . time() . '.' . $extension;
        $destination = $uploadDir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return $destination;
        }
        
        return false;
    }
    
   
   
    public function stats() {
       return $stats = [
            'total' => $this->publicationModel->count(),
            'valide' => $this->publicationModel->countValidated(),
            'en_attente' => $this->publicationModel->countPending(),
            'par_type' => $this->publicationModel->getStatsByType(),
            'par_domaine' => $this->publicationModel->getStatsByDomain(),
            'par_annee' => []
        ];
        
        $currentYear = date('Y');
        for ($i = 0; $i < 5; $i++) {
            $year = $currentYear - $i;
            $stats['par_annee'][$year] = $this->publicationModel->countByYear($year);
        }
        
        header('Content-Type: application/json');
        echo json_encode($stats);
        exit;
    }


public function apiGetPublications() {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $perPage = isset($_GET['perPage']) ? (int)$_GET['perPage'] : 10;
    
    $filters = [
        'type' => $_GET['type'] ?? null,
        'statut' => $_GET['statut'] ?? null,
        'domaine' => $_GET['domaine'] ?? null,
        'year' => $_GET['year'] ?? null
    ];
    
    $filters = array_filter($filters);
    
    if (!empty($_GET['q'])) {
        $publications = $this->publicationModel->search($_GET['q'], 1000);
        $total = count($publications);
    } else {
        $publications = $this->publicationModel->getFiltered($page, $perPage, $filters);
        $total = $this->publicationModel->countFiltered($filters);
    }
    
    $totalPages = ceil($total / $perPage);
    
    return [
        'success' => true,
        'data' => $publications,
        'currentPage' => $page,
        'totalPages' => $totalPages,
        'total' => $total
    ];
}


public function apiGetPublicationById($id) {
    $publication = $this->publicationModel->getById($id);
    
    if ($publication) {
        return ['success' => true, 'data' => $publication];
    } else {
        return ['success' => false, 'message' => 'Publication introuvable'];
    }
}



    public function apiCreatePublication($postData, $filesData) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user_id'])) {
            return ['success' => false, 'message' => 'Vous devez être connecté.'];
        }
        
        $currentUserId = $_SESSION['user_id'];

        $errors = [];
        if (empty($postData['titre'])) $errors[] = 'Le titre est obligatoire';
        if (empty($postData['type'])) $errors[] = 'Le type est obligatoire';
        
        if (!empty($errors)) return ['success' => false, 'errors' => $errors];
        
        $lienTelechargement = null;
        if (isset($filesData['fichier']) && $filesData['fichier']['error'] === UPLOAD_ERR_OK) {
            $lienTelechargement = $this->uploadFile($filesData['fichier']);
        }
        
        $this->publicationModel->titre = $postData['titre'];
        $this->publicationModel->resume = $postData['resume'] ?? null;
        $this->publicationModel->type = $postData['type'];
        $this->publicationModel->date_publication = $postData['date_publication'] ?? date('Y-m-d'); // Date du jour par défaut
        $this->publicationModel->doi = $postData['doi'] ?? null;
        $this->publicationModel->lien_telechargement = $lienTelechargement;
        $this->publicationModel->domaine = $postData['domaine'] ?? null;
        $this->publicationModel->statut_validation = 'en_attente'; // Toujours en attente au début
        
        $this->publicationModel->soumis_par = $currentUserId;
        
        $newId = $this->publicationModel->create();
        
        if ($newId) {
            
            return ['success' => true, 'message' => 'Publication soumise avec succès ! En attente de validation.'];
        }
        
        return ['success' => false, 'message' => 'Erreur SQL lors de l\'enregistrement.'];
    }


public function apiUpdatePublication($id, $postData, $filesData) {
    $publication = $this->publicationModel->getById($id);
    if (!$publication) {
        return ['success' => false, 'message' => 'Publication introuvable'];
    }
    
    if ($publication['soumis_par'] != $_SESSION['user_id'] && $_SESSION['role'] !== 'admin') {
        return ['success' => false, 'message' => 'Non autorisé à modifier cette publication'];
    }
    
    $errors = [];
    
    if (empty($postData['titre'])) {
        $errors[] = 'Le titre est obligatoire';
    } elseif (strlen($postData['titre']) > 255) {
        $errors[] = 'Le titre ne peut pas dépasser 255 caractères';
    }
    
    $typesValides = ['article', 'rapport', 'these', 'communication'];
    if (empty($postData['type'])) {
        $errors[] = 'Le type est obligatoire';
    } elseif (!in_array($postData['type'], $typesValides)) {
        $errors[] = 'Type invalide';
    }
    
    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }
    
    $lienTelechargement = $publication['lien_telechargement'];
    if (isset($filesData['fichier']) && $filesData['fichier']['error'] === UPLOAD_ERR_OK) {
        $newFile = $this->uploadFile($filesData['fichier']);
        
        if ($newFile) {
            if ($lienTelechargement && file_exists($lienTelechargement)) {
                unlink($lienTelechargement);
            }
            $lienTelechargement = $newFile;
        }
    }
    
    $this->publicationModel->id = $id;
    $this->publicationModel->titre = $postData['titre'];
    $this->publicationModel->resume = $postData['resume'] ?? null;
    $this->publicationModel->type = $postData['type'];
    $this->publicationModel->date_publication = $postData['date_publication'] ?? null;
    $this->publicationModel->doi = $postData['doi'] ?? null;
    $this->publicationModel->lien_telechargement = $lienTelechargement;
    $this->publicationModel->domaine = $postData['domaine'] ?? null;
    $this->publicationModel->statut_validation = $publication['statut_validation'];
    
    if ($this->publicationModel->update()) {
        return ['success' => true, 'message' => 'Publication mise à jour avec succès'];
    } else {
        return ['success' => false, 'message' => 'Erreur lors de la mise à jour'];
    }
}


public function apiValidatePublication($id) {
    if ($this->publicationModel->validate($id)) {
        return ['success' => true, 'message' => 'Publication validée avec succès'];
    } else {
        return ['success' => false, 'message' => 'Erreur lors de la validation'];
    }
}


public function apiRejectPublication($id) {
    if ($this->publicationModel->reject($id)) {
        return ['success' => true, 'message' => 'Publication rejetée'];
    } else {
        return ['success' => false, 'message' => 'Erreur lors du rejet'];
    }
}


public function apiDeletePublication($id) {
    $publication = $this->publicationModel->getById($id);
    if (!$publication) {
        return ['success' => false, 'message' => 'Publication introuvable'];
    }
    
  
    if ($publication['lien_telechargement'] && file_exists($publication['lien_telechargement'])) {
        unlink($publication['lien_telechargement']);
    }
    
    if ($this->publicationModel->delete($id)) {
        return ['success' => true, 'message' => 'Publication supprimée avec succès'];
    } else {
        return ['success' => false, 'message' => 'Erreur lors de la suppression'];
    }
}






public function apiSearchPublications($keyword, $limit = 20) {
    if (empty($keyword)) {
        return ['success' => false, 'message' => 'Le mot-clé de recherche est requis'];
    }
    
    $publications = $this->publicationModel->search($keyword, $limit);
    
    return [
        'success' => true,
        'data' => $publications,
        'total' => count($publications)
    ];
}


public function apiGetDomains() {
    $result = $this->publicationModel->getDistinctDomains();
    
    if ($result['success']) {
        return [
            'success' => true,
            'data' => $result['data']
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Erreur lors de la récupération des domaines'
        ];
    }
}


public function apiGetYears() {
    $result = $this->publicationModel->getDistinctYears();
    
    if ($result['success']) {
        return [
            'success' => true,
            'data' => $result['data']
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Erreur lors de la récupération des années'
        ];
    }
}


public function apiGetMyPublications($userId) {
    $publications = $this->publicationModel->getByUser($userId);
    
    return [
        'success' => true,
        'data' => $publications,
        'total' => count($publications)
    ];
}


public function apiGetRecentPublications($limit = 5) {
    $publications = $this->publicationModel->getRecent($limit);
    
    return [
        'success' => true,
        'data' => $publications,
        'total' => count($publications)
    ];
}


public function apiGetPendingPublications() {
    $publications = $this->publicationModel->getByValidationStatus('en_attente');
    
    return [
        'success' => true,
        'data' => $publications,
        'total' => count($publications)
    ];
}


public function apiGetPublicationsByType($type, $limit = null) {
    $typesValides = ['article', 'rapport', 'these', 'communication'];
    
    if (!in_array($type, $typesValides)) {
        return ['success' => false, 'message' => 'Type invalide'];
    }
    
    $publications = $this->publicationModel->getByType($type, $limit);
    
    return [
        'success' => true,
        'data' => $publications,
        'total' => count($publications)
    ];
}

    
    public function apiGetPublicationDetails($id) {
      $id = (int)$id;
        if ($id <= 0) {
            header('Location: index.php?route=publications');
            exit;
        }

        $pub = $this->publicationModel->getByIdWithDetails($id);

        if (!$pub) {
            header('Location: index.php?route=publications');
            exit;
        }

        $config = $this->settingsModel->getAllSettings();
        $menu = $this->menuModel->getMenuTree();

        $data = [
            'publication' => $pub,
            'config' => $config,
            'menu' => $menu,
            'title' => $pub['titre'] // Pour le <title> HTML
        ];

        require_once __DIR__ . '/../views/public/publication-detailsView.php';
        $view = new PublicationDetailsView($data);
        $view->render();
    }

    public function download($id) {
        $pub = $this->publicationModel->getById($id);
        
        if ($pub && !empty($pub['lien_telechargement']) && file_exists($pub['lien_telechargement'])) {
            if (ob_get_length()) ob_clean();
            
            header('Content-Description: File Transfer');
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="'.basename($pub['lien_telechargement']).'"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($pub['lien_telechargement']));
            readfile($pub['lien_telechargement']);
            exit;
        }
        die("Erreur : Le fichier n'existe pas sur le serveur.");
    }
   

 
    public function generateReport() {
        if (ob_get_length()) ob_clean();

        require_once __DIR__ . '/../libs/PDFReport.php';

        $year = $_REQUEST['year'] ?? null;
        $domaine = $_REQUEST['domaine'] ?? null;
        $typeFiltre = $_REQUEST['type'] ?? null;
        $format = $_REQUEST['format'] ?? 'pdf';

        if ($format !== 'pdf') {
            die("Format non supporté.");
        }

        $filters = array_filter([
            'year' => $year,
            'domaine' => $domaine,
            'type' => ($typeFiltre === 'type') ? null : $typeFiltre
        ]);

        $publications = $this->publicationModel->getAllForReport($filters);

        $pdf = new PDFReport();
        $pdf->AliasNbPages();
        $pdf->AddPage();
        
        $titre = "Rapport Bibliographique";
        if ($year) $titre .= " - Année $year";
        $pdf->setReportTitle(iconv('UTF-8', 'windows-1252', $titre));
        
        $dateInfo = "Généré le " . date('d/m/Y H:i');
        $pdf->setFilterInfo(iconv('UTF-8', 'windows-1252', $dateInfo));

        if (empty($publications)) {
            $pdf->SetFont('Arial', 'I', 12);
            $msg = "Aucune publication trouvée pour ces critères.";
            $pdf->Cell(0, 10, iconv('UTF-8', 'windows-1252', $msg), 0, 1, 'C');
            $pdf->Output('D', 'Rapport_Vide.pdf');
            exit;
        }

        $grouped = [];
        foreach ($publications as $pub) {
            $typeName = ucfirst($pub['type'] ?? 'Autre');
            $grouped[$typeName][] = $pub;
        }

        foreach ($grouped as $type => $pubs) {
            $pdf->Ln(5);
            
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->SetFillColor(230, 230, 230);
            
            $typeText = strtoupper($type);
            $pdf->Cell(0, 8, iconv('UTF-8', 'windows-1252', $typeText), 0, 1, 'L', true);
            $pdf->Ln(2);

            $header = ['Titre', 'Auteurs', 'Date', 'Projet'];
            $w = [80, 50, 25, 35];
            
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->SetFillColor(78, 115, 223);
            $pdf->SetTextColor(255);
            
            foreach($header as $i => $col) {
                $pdf->Cell($w[$i], 7, iconv('UTF-8', 'windows-1252', $col), 1, 0, 'C', true);
            }
            $pdf->Ln();

            $pdf->SetFont('Arial', '', 8);
            $pdf->SetTextColor(0);
            $pdf->SetFillColor(245, 245, 245);
            $fill = false;

            foreach ($pubs as $row) {
               $titreRaw = substr($row['titre'], 0, 50) . (strlen($row['titre']) > 50 ? '...' : '');
                $auteursRaw = substr($row['auteurs_noms'] ?? 'N/A', 0, 35) . '...';
                $dateRaw = $row['date_publication'] ? date('d/m/Y', strtotime($row['date_publication'])) : '-';
                $projetRaw = substr($row['projet_titre'] ?? '-', 0, 20);

               $titre = iconv('UTF-8', 'windows-1252//TRANSLIT', $titreRaw);
                $auteurs = iconv('UTF-8', 'windows-1252//TRANSLIT', $auteursRaw);
                $date = iconv('UTF-8', 'windows-1252//TRANSLIT', $dateRaw);
                $projet = iconv('UTF-8', 'windows-1252//TRANSLIT', $projetRaw);

                $pdf->Cell($w[0], 7, $titre, 1, 0, 'L', $fill);
                $pdf->Cell($w[1], 7, $auteurs, 1, 0, 'L', $fill);
                $pdf->Cell($w[2], 7, $date, 1, 0, 'C', $fill);
                $pdf->Cell($w[3], 7, $projet, 1, 0, 'C', $fill);
                $pdf->Ln();
                $fill = !$fill;
            }
        }

        $filename = 'Rapport_Publications_' . date('Y-m-d') . '.pdf';
        $pdf->Output('D', $filename);
        exit;
    }
    public function indexAdmin() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?route=login');
            exit;
        }

   

        try {
            // 2. Récupération des données globales (Header/Footer)
            $config = $this->settingsModel->getAllSettings();
            $menu = $this->menuModel->getMenuTree();

            $stats = $this->publicationModel->getAll(); 

            $data = [
                'title' => 'Gestion des Publications',
                'config' => $config,
                'menu' => $menu,
                'stats' => $stats
            ];

            require_once __DIR__ . '/../views/publication_management.php';
            $view = new PublicationAdminView($data);
            $view->render();

        } catch (Exception $e) {
            die("Erreur lors du chargement des publications : " . $e->getMessage());
        }
    }

}