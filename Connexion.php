<?php
session_start();                      
require_once 'connexion.inc.php';    

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$errors = []; 
$email = '';
$remember = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $posted_token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $posted_token)) {
        $errors[] = 'Requête invalide (CSRF).';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']) && $_POST['remember'] === '1';
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Veuillez saisir un email valide.';
        }
        if ($password === '') {
            $errors[] = 'Veuillez saisir le mot de passe.';
        }
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare('SELECT id_utlisateur, mot_de_passe, etat_compte FROM utilisateur WHERE email = ?');
                $stmt->execute([$email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$user) {
                    $errors[] = 'Email ou mot de passe incorrect.';
                } else {
                    if ((string)$user['etat_compte'] !== '1') {
                        $errors[] = "Votre compte n'est pas activé. Contactez l'administrateur.";
                    } else {
                        if (!password_verify($password, $user['mot_de_passe'])) {
                            $errors[] = 'Email ou mot de passe incorrect.';
                        } else {
                            session_regenerate_id(true);
                            $_SESSION['user_id'] = $user['id_utlisateur'];
                            $_SESSION['user_email'] = $email;
                            $stmtAdmin = $pdo->prepare('SELECT id_admin FROM admin WHERE id_admin = ?');
                            $stmtAdmin->execute([$user['id_utlisateur']]);
                            if ($stmtAdmin->fetch()) {
                                $_SESSION['user_role'] = 'admin';
                            } else {
                                $stmtDem = $pdo->prepare('SELECT id_demenageur FROM demenageur WHERE id_demenageur = ?');
                                $stmtDem->execute([$user['id_utlisateur']]);
                                if ($stmtDem->fetch()) {
                                    $_SESSION['user_role'] = 'demenageur';
                                } else {
                                    $stmtCli = $pdo->prepare('SELECT id_client FROM client WHERE id_client = ?');
                                    $stmtCli->execute([$user['id_utlisateur']]);
                                    if ($stmtCli->fetch()) {
                                        $_SESSION['user_role'] = 'client';
                                    } else {
                                        $_SESSION['user_role'] = 'user';
                                    }
                                }
                            }
                            if ($remember) {
                                $token = bin2hex(random_bytes(32));
                                setcookie('remember_token', $token, [
                                    'expires' => time() + 60*60*24*30, //30jours
                                    'path' => '/',
                                    'httponly' => true,
                                    'samesite' => 'Lax'
                                ]);
                            }
                            header('Location: index.php');
                            exit;
                        } // end password_verify
                    } // end etat_compte check
                } // end user found
            } catch (PDOException $e) {
                $errors[] = "Erreur serveur. Veuillez réessayer plus tard.";
                error_log('Login DB error: ' . $e->getMessage());
            }
        } // end if no validation errors
    } // end CSRF ok
} // end POST
function e($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
?>
