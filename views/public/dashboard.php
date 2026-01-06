<?php
// Fichier : public/profile.php

// 1. Démarrage de session
session_start();

// 2. Inclusion du contrôleur
// (Ajustez le chemin selon votre structure réelle)
require_once __DIR__ . '/../../controllers/memberController.php';

// 3. Lancement de la page
$controller = new MemberController();
$controller->dashboard();
?>