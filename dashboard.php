<?php
session_start();
require_once 'auth_check.php';     
require_once 'connexion.inc.php'; 

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];
$errors = [];
$success = "";

function e($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
//Récupérer les informations sur l'utilisateur actuel 
try {
    $stmt = $pdo->prepare("SELECT id_utlisateur, nom, prenom, email, telephone, sexe, id_ville, photo_profil, etat_compte 
                           FROM utilisateur WHERE id_utlisateur = ?");
    $stmt->execute([$CURRENT_USER_ID]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        $errors[] = 'Votre compte utilisateur est introuvable.';
        header('Location: connexion.php');
        exit;
    }
} catch (PDOException $e) {
    $errors[] = 'Erreur lors de la récupération des informations utilisateur.';
    error_log("Dashboard read user error: " . $e->getMessage());
    $ = [];
}
//Gestion du téléchargement d'avatar 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_avatar') {
    if (!hash_equals($csrf, $_POST['csrf_token'] ?? '')) {
        $errors[] = 'Requête invalide (CSRF).';
    } else {
        if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'veuillez sélectionner un fichier valide à télécharger。';
        } else {
            $file = $_FILES['avatar'];
            $maxBytes = 2 * 1024 * 1024; // 2MB maximum
            if ($file['size'] > $maxBytes) {
                $errors[] = 'Le fichier dépasse la taille maximale de 2 Mo。';
            } else {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($file['tmp_name']);
                $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
                if (!array_key_exists($mime, $allowed)) {
                    $errors[] = 'le type de fichier n\'est pas autorisé。';
                } else {
                    $ext = $allowed[$mime];
                    $newName = 'avatar_' . $CURRENT_USER_ID . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
                    $uploadDir = __DIR__ . '/images/uploads';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                    $dest = $uploadDir . '/' . $newName;
                    if (!move_uploaded_file($file['tmp_name'], $dest)) {
                        $errors[] = 'echec de l\'enregistrement du fichier.';
                    } else {
                        $dbPath = 'images/uploads/' . $newName;
                        try {
                            $stmtUpd = $pdo->prepare("UPDATE utilisateur SET photo_profil = ? WHERE id_utlisateur = ?");
                            $stmtUpd->execute([$dbPath, $CURRENT_USER_ID]);
                            $success = 'photo de profil mise à jour avec succès。';
                            $user['photo_profil'] = $dbPath;
                        } catch (PDOException $e) {
                            $errors[] = 'Erreur lors de la mise à jour de la photo de profil en base de données。'; 
                            error_log("Avatar DB update error: " . $e->getMessage());
                        }
                    }
                }
            }
        }
    }
}

//Traitement de la mise à jour du profil (action = update_profile)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    if (!hash_equals($csrf, $_POST['csrf_token'] ?? '')) {
        $errors[] = 'Requête invalide (CSRF).';
    } else {
        $nom = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $telephone = trim($_POST['telephone'] ?? '');
        $id_ville = $_POST['id_ville'] ?? '';

        if ($nom === '' || $prenom === '') $errors[] = 'Nom et prénom sont obligatoires.';
        if (!preg_match('/^[0-9]{8,15}$/', $telephone)) $errors[] = 'Le téléphone doit contenir 8-15 chiffres.';
        if ($id_ville === '') $errors[] = 'Veuillez choisir une ville.';

        if (empty($errors)) {
            try {
                $stmtUp = $pdo->prepare("UPDATE utilisateur SET nom = ?, prenom = ?, telephone = ?, id_ville = ? WHERE id_utlisateur = ?");
                $stmtUp->execute([$nom, $prenom, $telephone, $id_ville, $CURRENT_USER_ID]);
                $success = 'Profil mis à jour avec succès。';
                $user['nom'] = $nom;
                $user['prenom'] = $prenom;
                $user['telephone'] = $telephone;
                $user['id_ville'] = $id_ville;
            } catch (PDOException $e) {
                $errors[] = 'Erreur lors de la mise à jour du profil。';
                error_log("Profile update error: " . $e->getMessage());
            }
        }
    }
}

//Traitement du changement de mot de passe (action = change_password)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    if (!hash_equals($csrf, $_POST['csrf_token'] ?? '')) {
        $errors[] = 'Requête invalide (CSRF).';
    } else {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if ($current === '' || $new === '' || $confirm === '') $errors[] = 'les champs de mot de passe sont obligatoires。';
        if ($new !== $confirm) $errors[] = 'les mots de passe ne correspondent pas。';
        if (strlen($new) < 8) $errors[] = 'le nouveau mot de passe doit contenir au moins 8 caractères。';

        if (empty($errors)) {
            try {
                $stmtPass = $pdo->prepare("SELECT mot_de_passe FROM utilisateur WHERE id_utlisateur = ?");
                $stmtPass->execute([$CURRENT_USER_ID]);
                $row = $stmtPass->fetch(PDO::FETCH_ASSOC);
                if (!$row || !password_verify($current, $row['mot_de_passe'])) {
                    $errors[] = 'le mot de passe actuel est incorrect。';
                } else {
                    $newHash = password_hash($new, PASSWORD_DEFAULT);
                    $stmtUpd = $pdo->prepare("UPDATE utilisateur SET mot_de_passe = ? WHERE id_utlisateur = ?");
                    $stmtUpd->execute([$newHash, $CURRENT_USER_ID]);
                    $success = 'le mot de passe a été changé avec succès。';
                }
            } catch (PDOException $e) {
                $errors[] = 'Erreur lors du changement de mot de passe。';
                error_log("Password change error: " . $e->getMessage());
            }
        }
    }
}

// Charger la liste des villes pour les sélections
try {
    $villes = $pdo->query("SELECT id_ville, nom_ville FROM ville ORDER BY nom_ville")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $villes = [];
    error_log("Load villes error: " . $e->getMessage());
}

// Détecter le rôle de l'utilisateur (admin, demenageur, client, user)
$userRole = 'user';
try {
    $stmtA = $pdo->prepare("SELECT id_admin FROM admin WHERE id_admin = ?");
    $stmtA->execute([$CURRENT_USER_ID]);
    if ($stmtA->fetch()) {
        $userRole = 'admin';
    } else {
        $stmtD = $pdo->prepare("SELECT id_demenageur FROM demenageur WHERE id_demenageur = ?");
        $stmtD->execute([$CURRENT_USER_ID]);
        if ($stmtD->fetch()) {
            $userRole = 'demenageur';
        } else {
            $stmtC = $pdo->prepare("SELECT id_client FROM client WHERE id_client = ?");
            $stmtC->execute([$CURRENT_USER_ID]);
            if ($stmtC->fetch()) $userRole = 'client';
        }
    }
} catch (PDOException $e) {
    error_log("Role detect error: " . $e->getMessage());
}
$my_annonces = [];
$my_propositions = [];
try {
    if ($userRole === 'client') {
        $stmt = $pdo->prepare("SELECT * FROM annonce WHERE id_client = ? ORDER BY date_depart DESC");
        $stmt->execute([$CURRENT_USER_ID]);
        $my_annonces = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($userRole === 'demenageur') {
        $stmt = $pdo->prepare("SELECT p.*, a.titre FROM proposition p JOIN annonce a ON p.id_annonce = a.id_annonce WHERE p.id_demenageur = ? ORDER BY p.date_proposition DESC");
        $stmt->execute([$CURRENT_USER_ID]);
        $my_propositions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log("Load user related data error: " . $e->getMessage());
}

?>