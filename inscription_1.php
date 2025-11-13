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

$errors = []; 
$success = ''; 
if ($_SERVER['REQUEST_METHOD'] === 'POST'){
    $posted_token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $posted_token)) {
        $errors[] = 'Invalid request (CSRF token mismatch).';
    } else {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm'] ?? '';
        }
        if ($email === '') {
            $errors[] = 'L\'email est requis.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Format d\'email invalide.';
        }
        if (strlen($password) < 8) {
            $errors[] = 'Le mot de passe doit contenir au moins 8 caractères.';
        }
        if ($password !== $confirm) {
            $errors[] = 'Les mots de passe ne correspondent pas.';
        }
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare('SELECT id_utlisateur FROM utilisateur WHERE email = ?');
                $stmt->execute([$email]);
                $existing = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($existing) {
                    $errors[] = 'Cet email est déjà utilisé. Si vous avez déjà un compte, connectez-vous.';
                } else {
                    $_SESSION['register_email'] = $email;
                    $_SESSION['register_password'] = password_hash($password, PASSWORD_DEFAULT);
                    header('Location: register_step2.php');
                    exit;
                }

            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $errors[] = 'Erreur serveur. Veuillez réessayer plus tard.';
                error_log('Register DB error: ' . $e->getMessage());
            }
        }
    } 
}
?>