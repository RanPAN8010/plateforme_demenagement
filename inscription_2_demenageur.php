<?php
session_start();
require_once 'connexion.inc.php';
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
function e($s) {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
if (empty($_SESSION['register_email']) || empty($_SESSION['register_password'])) {
    header('Location: inscription_1.php');
    exit;
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $sexe = $_POST['sexe'] ?? '';
    $telephone = trim($_POST['telephone'] ?? '');
    $id_ville = $_POST['ville'] ?? '';
    $possede_voiture = $_POST['possede_voiture'] ?? '';

    if ($nom === '') {
        $errors[] = 'Le nom est requis.';
    }
    if ($prenom === '') {
        $errors[] = 'Le prénom est requis.';
    }
    if ($sexe !== 'M' && $sexe !== 'F') {
        $errors[] = 'Le sexe est invalide.';
    }
    if ($telephone === '') {
        $errors[] = 'Le téléphone est requis.';
    }
    if ($id_ville === '' || !ctype_digit($id_ville)) {
        $errors[] = 'La ville est invalide.';
    }
    if ($possede_voiture !== 'oui' && $possede_voiture !== 'non') {
        $errors[] = 'L\'information sur la possession de voiture est invalide.';
    }

    if (empty($errors)) {
        try {

            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $pdo->beginTransaction();
            $sql = "INSERT INTO utilisateur (nom, prenom, email, mot_de_passe, telephone, sexe, id_ville, etat_compte)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ACTIVE)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $nom,
                $prenom,
                $_SESSION['register_email'],
                $_SESSION['register_password'],
                $telephone,
                $sexe,
                $id_ville
                $possede_voiture_bit = ($possede_voiture === 'oui') ? 1 : 0;
            ]);
            $id_user = $pdo->lastInsertId();
            $pdo->commit();
            $success = 'Inscription réussie ! Vous pouvez maintenant vous connecter.';
            header('Location: connexion.php');
            exit;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = 'Erreur serveur. Veuillez réessayer plus tard.';
            error_log('Register Step 2 DB error: ' . $e->getMessage());
        }
    }
}
$villes = $pdo->query("SELECT id_ville, nom_ville FROM ville ORDER BY nom_ville")->fetchAll(PDO::FETCH_ASSOC);
?>