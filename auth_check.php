<?php
// auth_check.php
// pour protéger les pages nécessitant une authentification
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Vérifier si l'utilisateur est connecté
if (empty($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit;
}
// Récupérer l'ID de l'utilisateur connecté
$CURRENT_USER_ID = $_SESSION['user_id'];
