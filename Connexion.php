<?php
session_start();                      
require_once 'connexion.inc.php';    
require_once 'password_compat.php';
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$errors = []; 
$email = '';
$remember = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $posted_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!hash_equals($_SESSION['csrf_token'], $posted_token)) {
        $errors[] = 'Requête invalide (CSRF).';
    } else {
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $remember = isset($_POST['remember']) && $_POST['remember'] === '1';
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Veuillez saisir un email valide.';
        }
        if ($password === '') {
            $errors[] = 'Veuillez saisir le mot de passe.';
        }
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare('SELECT id_utilisateur, mot_de_passe, etat_compte FROM utilisateur WHERE email = ?');
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
                            $_SESSION['user_id'] = $user['id_utilisateur'];
                            $_SESSION['user_email'] = $email;
                            $stmtAdmin = $pdo->prepare('SELECT id_admin FROM admin WHERE id_admin = ?');
                            $stmtAdmin->execute([$user['id_utilisateur']]);
                            if ($stmtAdmin->fetch()) {
                                $_SESSION['user_role'] = 'admin';
                            } else {
                                $stmtDem = $pdo->prepare('SELECT id_demenageur FROM demenageur WHERE id_demenageur = ?');
                                $stmtDem->execute([$user['id_utilisateur']]);
                                if ($stmtDem->fetch()) {
                                    $_SESSION['user_role'] = 'demenageur';
                                } else {
                                    $stmtCli = $pdo->prepare('SELECT id_client FROM client WHERE id_client = ?');
                                    $stmtCli->execute([$user['id_utilisateur']]);
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
                        } 
                    } 
                } 
            } catch (PDOException $e) {
                $errors[] = "Erreur serveur. Veuillez réessayer plus tard.";
                error_log('Login DB error: ' . $e->getMessage());
            }
        }
    }
} 
function e($s) { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" >
    <link rel="stylesheet" href="css/style.css">
    <title>Connexion</title>
</head>
<body>
    <?php include 'head.php'; ?>
    <main>
        <div class="content-wrapper" style="margin-top:200px; max-width:600px; margin:0 auto; padding:20px;">
            <h2 class="section-title" style="
                text-align:center; 
                margin-top:200px;">
                Connectez-vous à votre compte
            </h2>
            <?php if (!empty($errors)): ?>
                <div class="error-message-box">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <form method="POST" style="display:flex; flex-direction:column; gap:20px">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                <!-- Email Field -->
                <div class="form-row">
                    <div class="form-label">
                            Adresse Email
                    </div>

                <input 
                    type="text" 
                    name="email" 
                    value="<?php echo htmlspecialchars($email); ?>" 
                    placeholder="Entrez votre email" 
                    class="input-field"
                >
                </div>

                <!-- Password Field -->
                <div class="form-row">
                    <div class="form-label">
                            Mot de passe
                    </div>
                    
                    <div style="flex-grow:1; position:relative;width:100%;">
                        <input 
                            type="password" 
                            id="pwdField"
                            name="password" 
                            placeholder="Entrez votre mot de passe" 
                            class="input-field"
                        >
                        <button 
                            type="button" 
                            onclick="togglePwd()" 
                                class="btn-toggle-voir">
                            Voir
                        </button>
                    </div>
                </div>
                <!-- Remember Me Checkbox -->
                <div style="display:flex; align-items:center; margin-bottom:20px;">
                    <input type="checkbox" name="remember" value="1" id="remember"
                        style="width:18px; height:18px; margin-right:10px; cursor:pointer;">
                    <label for="remember" style="cursor:pointer;">Se souvenir de moi</label>
                </div>

                <button type="submit" 
                    class="btn-primary">Connexion</button>
            </form>
            <div class="separator"></div>

            <div style="display:flex; justify-content:space-between;">
                <a href="inscription_1.php?role=client"
                    class="btn-secondary-inscrip">Créer un compte client</a>
                <a href="inscription_1.php?role=demenageur" 
                    class="btn-secondary-inscrip">Créer un compte déménageur</a>
            </div>
    </main>

    <script>
        function togglePwd() {
            var f = document.getElementById('pwdField');
            f.type = (f.type === 'password') ? 'text' : 'password';
        }
    </script>
</body>
</html>