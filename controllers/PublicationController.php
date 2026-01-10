<?php

require_once __DIR__ .  '/../models/Publications.php';

// Imports des modèles
require_once __DIR__ . '/../models/UserModel.php';
// Ajout des modèles pour le Header/Footer
require_once __DIR__ . '/../models/Settings.php';
require_once __DIR__ . '/../models/Menu.php';

class PublicationController {
    
    private $publicationModel;
    private $userModel;
    private $settingsModel;
    private $menuModel;

    public function __construct() {
        // Instanciation des modèles
        $this->publicationModel = new Publication();
        $this->userModel = new UserModel();
        $this->settingsModel = new Settings();
        $this->menuModel = new Menu();
    }

    public function index() {
       
        // 2. Logique de filtrage (Votre code)
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = 10;
        
        $filters = [
            'type' => $_GET['type'] ?? null,
            'statut' => $_GET['statut'] ?? 'en_attente', // Par défaut: publications en attente
            'domaine' => $_GET['domaine'] ?? null,
            'year' => $_GET['year'] ?? null
        ];
        
        // Supprimer les filtres vides
        $filters = array_filter($filters);
        
        // Récupérer les publications avec filtres (Côté Serveur)
        $publications = $this->publicationModel->getFiltered($page, $perPage, $filters);
        $totalPublications = $this->publicationModel->countFiltered($filters);
        $totalPages = ceil($totalPublications / $perPage);
        
        // Récupérer les statistiques pour les filtres
        $statsByType = $this->publicationModel->getStatsByType();
        $statsByDomain = $this->publicationModel->getStatsByDomain();
        
        // Vérification Admin
        $isAdmin = isset($_SESSION['isAdmin']) ;
         $user = $this->userModel->getById($_SESSION['user_id']);
        // 3. Récupération des données globales pour Header/Footer
        $config = $this->settingsModel->getAllSettings();
        $menu = $this->menuModel->getMenuTree();

        // 4. Préparation des données pour la vue
        $data = [
            'title' => 'Gestion des Publications',
            'isAdmin' => $isAdmin,
            'config' => $config,
            'menu' => $menu,
            // On passe aussi les données PHP même si le JS va probablement les recharger via API
            'publications' => $publications,
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'currentUser'=> $user,
        ];

        // 5. Chargement de la Vue Classe
        require_once __DIR__ . '/../views/public/PublicationView.php';
        $view = new PublicationView($data);
        $view->render();
    }

    /**
     * Afficher une publication spécifique
     */
    public function show($id) {
        $publication = $this->publicationModel->getById($id);
        
        if (!$publication) {
            $_SESSION['error'] = "Publication introuvable.";
            header('Location: /publications');
            exit;
        }
        
        // Charger la vue
        require_once __DIR__ . 'views/publications/show.php';
    }
    
    /**
     * Afficher le formulaire de création
     */
    public function create() {
        // Vérifier que l'utilisateur est connecté
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->store();
        }
        
        // Charger la vue du formulaire
        require_once __DIR__ . 'views/publications/create.php';
    }
    
    /**
     * Enregistrer une nouvelle publication
     */
    private function store() {
        // Validation des données
        $errors = $this->validatePublicationData($_POST);
        
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = $_POST;
            header('Location: /publications/create');
            exit;
        }
        
        // Gérer l'upload du fichier
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
        
        // Créer la publication
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
    
    /**
     * Afficher le formulaire de modification
     */
    public function edit($id) {
        // Vérifier que l'utilisateur est connecté
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
        
        // Vérifier que l'utilisateur peut modifier (propriétaire ou admin)
        if ($publication['soumis_par'] != $_SESSION['user_id'] && $_SESSION['role'] !== 'admin') {
            $_SESSION['error'] = "Vous n'êtes pas autorisé à modifier cette publication.";
            header('Location: /publications');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->update($id);
        }
        
        // Charger la vue du formulaire
        require_once __DIR__ . 'views/publications/edit.php';
    }
    
    /**
     * Mettre à jour une publication
     */
    private function update($id) {
        // Validation des données
        $errors = $this->validatePublicationData($_POST);
        
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = $_POST;
            header('Location: /publications/edit/' . $id);
            exit;
        }
        
        $publication = $this->publicationModel->getById($id);
        
        // Gérer l'upload d'un nouveau fichier
        $lienTelechargement = $publication['lien_telechargement'];
        if (isset($_FILES['fichier']) && $_FILES['fichier']['error'] === UPLOAD_ERR_OK) {
            $newFile = $this->uploadFile($_FILES['fichier']);
            
            if ($newFile) {
                // Supprimer l'ancien fichier si existant
                if ($lienTelechargement && file_exists($lienTelechargement)) {
                    unlink($lienTelechargement);
                }
                $lienTelechargement = $newFile;
            }
        }
        
        // Mettre à jour la publication
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
    
    /**
     * Supprimer une publication
     */
    public function delete($id) {
        // Vérifier que l'utilisateur est admin
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
        
        // Supprimer le fichier associé
        if ($publication['lien_telechargement'] && file_exists($publication['lien_telechargement'])) {
            unlink($publication['lien_telechargement']);
        }
        
        if ($this->publicationModel->delete($id)) {
            $_SESSION['success'] = "Publication supprimée avec succès.";
        } else {
            $_SESSION['error'] = "Erreur lors de la suppression de la publication.";
        }
        
        header('Location: /publications');
        exit;
    }
    
    /**
     * Valider une publication (Admin uniquement)
     */
    public function validate($id) {
        // Vérifier que l'utilisateur est admin
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
    
    /**
     * Rejeter une publication (Admin uniquement)
     */
    public function reject($id) {
        // Vérifier que l'utilisateur est admin
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
    
    /**
     * Rechercher des publications
     */
    public function search() {
        $keyword = $_GET['q'] ?? '';
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
        
        if (empty($keyword)) {
            $publications = [];
        } else {
            $publications = $this->publicationModel->search($keyword, $limit);
        }
        
        // Charger la vue
        require_once __DIR__ . 'views/publications/search.php';
    }
    
    /**
     * Afficher les publications d'un utilisateur
     */
    public function myPublications() {
        // Vérifier que l'utilisateur est connecté
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = 10;
        
        $publications = $this->publicationModel->getByUser($_SESSION['user_id']);
        
        // Charger la vue
        require_once __DIR__ . 'views/publications/my-publications.php';
    }
    
    /**
     * Panel admin: Gérer les publications en attente
     */
    public function pending() {
        // Vérifier que l'utilisateur est admin
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            $_SESSION['error'] = "Accès non autorisé.";
            header('Location: /');
            exit;
        }
        
        $publications = $this->publicationModel->getByValidationStatus('en_attente');
        
        // Charger la vue
        require_once __DIR__ . 'views/admin/publications-pending.php';
    }
    
    /**
     * Valider les données de publication
     */
    private function validatePublicationData($data) {
        $errors = [];
        
        // Validation du titre
        if (empty($data['titre'])) {
            $errors['titre'] = "Le titre est obligatoire.";
        } elseif (strlen($data['titre']) > 255) {
            $errors['titre'] = "Le titre ne peut pas dépasser 255 caractères.";
        }
        
        // Validation du type
        $typesValides = ['article', 'rapport', 'these', 'communication'];
        if (empty($data['type'])) {
            $errors['type'] = "Le type est obligatoire.";
        } elseif (!in_array($data['type'], $typesValides)) {
            $errors['type'] = "Type invalide.";
        }
        
        // Validation de la date de publication
        if (!empty($data['date_publication'])) {
            $date = DateTime::createFromFormat('Y-m-d', $data['date_publication']);
            if (!$date) {
                $errors['date_publication'] = "Format de date invalide.";
            }
        }
        
        // Validation du DOI (optionnel mais format vérifié si fourni)
        if (!empty($data['doi']) && strlen($data['doi']) > 100) {
            $errors['doi'] = "Le DOI ne peut pas dépasser 100 caractères.";
        }
        
        return $errors;
    }
    
//     /**
//      * Upload d'un fichier de publication
//      */
    private function uploadFile($file) {
        // Définir le dossier d'upload
        $uploadDir = 'uploads/publications/';
        
        // Créer le dossier s'il n'existe pas
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Vérifier le type de fichier (PDF uniquement)
        $allowedTypes = ['application/pdf'];
        if (!in_array($file['type'], $allowedTypes)) {
            return false;
        }
        
        // Vérifier la taille (max 10MB)
        $maxSize = 10 * 1024 * 1024; // 10MB
        if ($file['size'] > $maxSize) {
            return false;
        }
        
        // Générer un nom de fichier unique
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . time() . '.' . $extension;
        $destination = $uploadDir . $filename;
        
        // Déplacer le fichier
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return $destination;
        }
        
        return false;
    }
    
    /**
     * Télécharger un fichier de publication
     */
    // public function download($id) {
    //     $publication = $this->publicationModel->getById($id);
        
    //     if (!$publication || !$publication['lien_telechargement']) {
    //         $_SESSION['error'] = "Fichier introuvable.";
    //         header('Location: /publications');
    //         exit;
    //     }
        
    //     // Vérifier que le fichier existe
    //     if (!file_exists($publication['lien_telechargement'])) {
    //         $_SESSION['error'] = "Fichier introuvable sur le serveur.";
    //         header('Location: /publications/' . $id);
    //         exit;
    //     }
        
    //     // Forcer le téléchargement
    //     header('Content-Type: application/pdf');
    //     header('Content-Disposition: attachment; filename="' . basename($publication['lien_telechargement']) . '"');
    //     header('Content-Length: ' . filesize($publication['lien_telechargement']));
    //     readfile($publication['lien_telechargement']);
    //     exit;
    // }
    
    /**
     * API: Obtenir les statistiques des publications
     */
    public function stats() {
       return $stats = [
            'total' => $this->publicationModel->count(),
            'valide' => $this->publicationModel->countValidated(),
            'en_attente' => $this->publicationModel->countPending(),
            'par_type' => $this->publicationModel->getStatsByType(),
            'par_domaine' => $this->publicationModel->getStatsByDomain(),
            'par_annee' => []
        ];
        
        // Statistiques par année (5 dernières années)
        $currentYear = date('Y');
        for ($i = 0; $i < 5; $i++) {
            $year = $currentYear - $i;
            $stats['par_annee'][$year] = $this->publicationModel->countByYear($year);
        }
        
        header('Content-Type: application/json');
        echo json_encode($stats);
        exit;
    }

// // ============================================
// // MÉTHODES API À AJOUTER AU PublicationController
// // ============================================

// /**
//  * API: Obtenir toutes les publications avec pagination et filtres
//  */
public function apiGetPublications() {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $perPage = isset($_GET['perPage']) ? (int)$_GET['perPage'] : 10;
    
    $filters = [
        'type' => $_GET['type'] ?? null,
        'statut' => $_GET['statut'] ?? null,
        'domaine' => $_GET['domaine'] ?? null,
        'year' => $_GET['year'] ?? null
    ];
    
    // Supprimer les filtres vides
    $filters = array_filter($filters);
    
    // Recherche par mot-clé
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

/**
 * API: Obtenir une publication par ID
 */
public function apiGetPublicationById($id) {
    $publication = $this->publicationModel->getById($id);
    
    if ($publication) {
        return ['success' => true, 'data' => $publication];
    } else {
        return ['success' => false, 'message' => 'Publication introuvable'];
    }
}

/**
 * API: Créer une nouvelle publication
 */
/**
     * API : Créer une publication
     */
    public function apiCreatePublication($postData, $filesData) {
        // 1. Vérification connexion
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user_id'])) {
            return ['success' => false, 'message' => 'Vous devez être connecté.'];
        }
        
        $currentUserId = $_SESSION['user_id'];

        // 2. Validation basique
        $errors = [];
        if (empty($postData['titre'])) $errors[] = 'Le titre est obligatoire';
        if (empty($postData['type'])) $errors[] = 'Le type est obligatoire';
        
        if (!empty($errors)) return ['success' => false, 'errors' => $errors];
        
        // 3. Upload du Fichier PDF
        $lienTelechargement = null;
        if (isset($filesData['fichier']) && $filesData['fichier']['error'] === UPLOAD_ERR_OK) {
            $lienTelechargement = $this->uploadFile($filesData['fichier']);
        }
        
        // 4. Préparation des données pour le Modèle
        $this->publicationModel->titre = $postData['titre'];
        $this->publicationModel->resume = $postData['resume'] ?? null;
        $this->publicationModel->type = $postData['type'];
        $this->publicationModel->date_publication = $postData['date_publication'] ?? date('Y-m-d'); // Date du jour par défaut
        $this->publicationModel->doi = $postData['doi'] ?? null;
        $this->publicationModel->lien_telechargement = $lienTelechargement;
        $this->publicationModel->domaine = $postData['domaine'] ?? null;
        $this->publicationModel->statut_validation = 'en_attente'; // Toujours en attente au début
        
        // --- POINT CLÉ : LE CRÉATEUR EST L'UTILISATEUR CONNECTÉ ---
        $this->publicationModel->soumis_par = $currentUserId;
        
        // 5. Insertion en base
        $newId = $this->publicationModel->create();
        
        if ($newId) {
            
            return ['success' => true, 'message' => 'Publication soumise avec succès ! En attente de validation.'];
        }
        
        return ['success' => false, 'message' => 'Erreur SQL lors de l\'enregistrement.'];
    }

/**
 * API: Mettre à jour une publication
 */
public function apiUpdatePublication($id, $postData, $filesData) {
    $publication = $this->publicationModel->getById($id);
    if (!$publication) {
        return ['success' => false, 'message' => 'Publication introuvable'];
    }
    
    // Vérifier les permissions
    if ($publication['soumis_par'] != $_SESSION['user_id'] && $_SESSION['role'] !== 'admin') {
        return ['success' => false, 'message' => 'Non autorisé à modifier cette publication'];
    }
    
    // Validation
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
    
    // Gérer l'upload d'un nouveau fichier
    $lienTelechargement = $publication['lien_telechargement'];
    if (isset($filesData['fichier']) && $filesData['fichier']['error'] === UPLOAD_ERR_OK) {
        $newFile = $this->uploadFile($filesData['fichier']);
        
        if ($newFile) {
            // Supprimer l'ancien fichier
            if ($lienTelechargement && file_exists($lienTelechargement)) {
                unlink($lienTelechargement);
            }
            $lienTelechargement = $newFile;
        }
    }
    
    // Mettre à jour
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

/**
 * API: Valider une publication
 */
public function apiValidatePublication($id) {
    if ($this->publicationModel->validate($id)) {
        return ['success' => true, 'message' => 'Publication validée avec succès'];
    } else {
        return ['success' => false, 'message' => 'Erreur lors de la validation'];
    }
}

/**
 * API: Rejeter une publication
 */
public function apiRejectPublication($id) {
    if ($this->publicationModel->reject($id)) {
        return ['success' => true, 'message' => 'Publication rejetée'];
    } else {
        return ['success' => false, 'message' => 'Erreur lors du rejet'];
    }
}

/**
 * API: Supprimer une publication
 */
public function apiDeletePublication($id) {
    $publication = $this->publicationModel->getById($id);
    if (!$publication) {
        return ['success' => false, 'message' => 'Publication introuvable'];
    }
    
    // Supprimer le fichier associé
    if ($publication['lien_telechargement'] && file_exists($publication['lien_telechargement'])) {
        unlink($publication['lien_telechargement']);
    }
    
    if ($this->publicationModel->delete($id)) {
        return ['success' => true, 'message' => 'Publication supprimée avec succès'];
    } else {
        return ['success' => false, 'message' => 'Erreur lors de la suppression'];
    }
}


/**
 * API: Obtenir les statistiques (DÉJÀ EXISTANTE - Version corrigée)
 */


/**
 * API: Rechercher des publications
 */
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

/**
 * API: Obtenir les domaines disponibles
 */
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

/**
 * API: Obtenir les années disponibles
 */
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

/**
 * API: Obtenir les publications de l'utilisateur connecté
 */
public function apiGetMyPublications($userId) {
    $publications = $this->publicationModel->getByUser($userId);
    
    return [
        'success' => true,
        'data' => $publications,
        'total' => count($publications)
    ];
}

/**
 * API: Obtenir les publications récentes
 */
public function apiGetRecentPublications($limit = 5) {
    $publications = $this->publicationModel->getRecent($limit);
    
    return [
        'success' => true,
        'data' => $publications,
        'total' => count($publications)
    ];
}

/**
 * API: Obtenir les publications en attente
 */
public function apiGetPendingPublications() {
    $publications = $this->publicationModel->getByValidationStatus('en_attente');
    
    return [
        'success' => true,
        'data' => $publications,
        'total' => count($publications)
    ];
}

/**
 * API: Obtenir les publications par type
 */
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

        // 2. Récupération des données via le Modèle
        $pub = $this->publicationModel->getByIdWithDetails($id);

        if (!$pub) {
            // Gestion erreur 404 ou redirection
            header('Location: index.php?route=publications');
            exit;
        }

        // 3. Récupération des données globales pour Header/Footer
        $config = $this->settingsModel->getAllSettings();
        $menu = $this->menuModel->getMenuTree();

        // 4. Préparation des données pour la vue
        $data = [
            'publication' => $pub,
            'config' => $config,
            'menu' => $menu,
            'title' => $pub['titre'] // Pour le <title> HTML
        ];

        // 5. Chargement de la Vue
        require_once __DIR__ . '/../views/public/publication-detailsView.php';
        $view = new PublicationDetailsView($data);
        $view->render();
    }

    /**
     * Action : Télécharger le PDF
     */
    public function download($id) {
        $pub = $this->publicationModel->getById($id);
        
        if ($pub && !empty($pub['lien_telechargement']) && file_exists($pub['lien_telechargement'])) {
            // Nettoyage du buffer de sortie pour éviter la corruption du PDF
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
        require_once __DIR__ . '/../libs/PDFReport.php';

        $year = $_GET['year'] ?? null;
        $domaine = $_GET['domaine'] ?? null;
        
        $filters = array_filter(['year' => $year, 'domaine' => $domaine]);
        $publications = $this->publicationModel->getAllForReport($filters);

        $pdf = new PDFReport();
        $pdf->AliasNbPages();
        $pdf->AddPage();
        
        $titre = "Rapport Bibliographique";
        if ($year) $titre .= " - Année $year";
        $pdf->setReportTitle($titre);
        $pdf->setFilterInfo("Généré le " . date('d/m/Y'));

        if (empty($publications)) {
            $pdf->SetFont('Arial', 'I', 12);
            $pdf->Cell(0, 10, $pdf->convert("Aucune publication trouvée pour ces critères."), 0, 1, 'C');
            $pdf->Output('D', 'Rapport_Vide.pdf');
            exit;
        }

        // Groupement par Type
        $grouped = [];
        foreach ($publications as $pub) {
            $grouped[ucfirst($pub['type'])][] = $pub;
        }

        foreach ($grouped as $type => $pubs) {
            $pdf->Ln(5);
            
            // En-tête de catégorie
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->SetFillColor(230, 230, 230);
            $pdf->Cell(0, 8, $pdf->convert(strtoupper($type)), 0, 1, 'L', true);
            $pdf->Ln(2);

            // En-têtes Tableau
            $header = ['Titre', 'Auteurs', 'Date', 'Projet'];
            $w = [80, 50, 25, 35]; // Total 190
            
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->SetFillColor(78, 115, 223);
            $pdf->SetTextColor(255);
            for($i=0; $i<count($header); $i++) 
                $pdf->Cell($w[$i], 7, $pdf->convert($header[$i]), 1, 0, 'C', true);
            $pdf->Ln();

            // Données
            $pdf->SetFont('Arial', '', 8);
            $pdf->SetTextColor(0);
            $pdf->SetFillColor(245, 245, 245);
            $fill = false;

            foreach ($pubs as $row) {
                // 'auteurs_noms' vient du GROUP_CONCAT dans le modèle
                $titre = substr($row['titre'], 0, 45) . (strlen($row['titre']) > 45 ? '...' : '');
                $auteurs = substr($row['auteurs_noms'] ?? 'N/A', 0, 30) . '...';
                $projet = substr($row['projet_titre'] ?? '-', 0, 18);

                $pdf->Cell($w[0], 7, $pdf->convert($titre), 1, 0, 'L', $fill);
                $pdf->Cell($w[1], 7, $pdf->convert($auteurs), 1, 0, 'L', $fill);
                $pdf->Cell($w[2], 7, $row['date_publication'], 1, 0, 'C', $fill);
                $pdf->Cell($w[3], 7, $pdf->convert($projet), 1, 0, 'C', $fill);
                $pdf->Ln();
                $fill = !$fill;
            }
        }

        $pdf->Output('D', 'Rapport_Publications.pdf');
        exit;
    }
    public function indexAdmin() {
        // 1. Vérification Session & Admin
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?route=login');
            exit;
        }

        // Vérification Rôle
        // $isAdmin = isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'directeur');
        // if (!$isAdmin) {
        //     header('Location: index.php?route=dashboard-user'); 
        //     exit;
        // }

        try {
            // 2. Récupération des données globales (Header/Footer)
            $config = $this->settingsModel->getAllSettings();
            $menu = $this->menuModel->getMenuTree();

            $stats = $this->publicationModel->getAll(); // Supposons que cette méthode existe

            // 4. Préparation des données pour la vue
            $data = [
                'title' => 'Gestion des Publications',
                'config' => $config,
                'menu' => $menu,
                'stats' => $stats
            ];

            // 5. Chargement de la Vue Classe
            require_once __DIR__ . '/../views/publication_management.php';
            $view = new PublicationAdminView($data);
            $view->render();

        } catch (Exception $e) {
            die("Erreur lors du chargement des publications : " . $e->getMessage());
        }
    }

}