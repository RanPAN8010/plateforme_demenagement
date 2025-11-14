<?php
session_start();
if (isset($_GET['role'])) {
    $_SESSION['inscription_role'] = $_GET['role']; // 保存用户类型
}

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
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST'){
    $posted_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!hash_equals($_SESSION['csrf_token'], $posted_token)) {
        $errors[] = 'Invalid request (CSRF token mismatch).';
    } else {
        $email    = isset($_POST['email']) ? trim($_POST['email']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $confirm = isset($_POST['confirm']) ? $_POST['confirm'] : '';
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
                    if ($_SESSION['inscription_role'] === 'client') {
                        header("Location: inscription_2_client.php");
                        exit;
                    }
                    if ($_SESSION['inscription_role'] === 'demenageur') {
                        header("Location: inscription_2_demenageur.php");
                        exit;
                    }
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
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport">
    <link rel="stylesheet" href="css/style.css">     
    <title>Inscription - Étape 1</title>
</head>
<body>
    <?php include 'head.php'; ?>
    <main>
        <div class="content-wrapper" style="
            
            max-width:600px; 
            margin:0 auto; 
            padding:20px;
        "> 
            <h2 class="section-title" style="
                text-align:center; 
                margin-top:200px;
            ">
                Créez votre compte
            </h2>
            <?php if (!empty($errors)): ?>
                <div style="
                    background:#ffdddd; 
                    padding:12px; 
                    border-radius:12px; 
                    margin-bottom:25px; 
                    text-align:left;">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <form method="POST" style="display:flex; flex-direction:column; gap:20px">
                <input 
                    type="hidden" 
                    name="csrf_token" 
                    value="<?php echo e($csrf_token); ?>"
                >

                <!-- Email Field -->
                <div style="
                    display:flex; 
                    align-items:center; 
                    gap:10px; 
                    margin-bottom:20px;
                ">
                <div style="background:#6C87C4; 
                    color:white;
                    padding:12px 20px;
                    border-radius:25px; 
                    font-weight:bold;">
                        Adresse Email
                </div>
                <input 
                    type="text" 
                    name="email" 
                    value="<?php echo htmlspecialchars($email); ?>" 
                    placeholder="Entrez votre email" 
                    style="flex-grow:1; 
                        padding:12px; 
                        border:none; 
                        border-radius:25px; 
                        background:#E8EEF8;"
                >
                </div>

                <!-- Password Field -->
                <div style="
                    display:flex;
                     align-items:center; 
                     gap:10px; 
                     margin-bottom:20px;">
                    <div style="
                        background:#6C87C4; 
                        color:white;
                        padding:12px 20px; 
                        border-radius:25px; 
                        font-weight:bold;">
                            Mot de passe
                    </div>
                    
                    <div style="flex-grow:1; position:relative;">
                        <input 
                            type="password" 
                            id="pwdField"
                            name="password" 
                            placeholder="Entrez votre mot de passe" 
                            style="
                                flex-grow:1; 
                                padding:12px; 
                                border:none; 
                                border-radius:25px; 
                                background:#E8EEF8;"
                        >
                        <button 
                            type="button" 
                            onclick="togglePwd()" 
                                style="
                                position:absolute; 
                                right:-5px; 
                                top:5px; 
                                padding:5px 10px; 
                                border:none; 
                                border-radius:15px; 
                                background:#6C87C4; 
                                color:white;
                                cursor:pointer;">
                            Voir
                        </button>
                    </div>
                </div>

                <!-- Confirm Password Field -->
                <div style="
                    display:flex; 
                    align-items:center; 
                    gap:10px; 
                    margin-bottom:20px;
                ">
                    <div style="background:#6C87C4; 
                        color:white;
                        padding:12px 20px;
                        border-radius:25px; 
                        font-weight:bold;">
                            Confirmer
                    </div>
                    <div style="flex-grow:1; position:relative;">
                        <input 
                            type="password" 
                            id="pwd2"
                            name="confirm" 
                            required 
                            placeholder="Confirmez votre mot de passe"
                            style="
                                flex-grow:1; 
                                padding:12px; 
                                border:none; 
                                border-radius:25px;
                                background:#E8EEF8;"
                        >
                        <button 
                            type="button" 
                            onclick="togglePwd()" 
                                style="
                                position:absolute; 
                                right:-5px; 
                                top:5px; 
                                padding:5px 10px; 
                                border:none; 
                                border-radius:15px; 
                                background:#6C87C4; 
                                color:white;
                                cursor:pointer;">
                            Voir
                        </button>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit"
                    style="
                        width:70%; 
                        margin:0 auto;
                        padding:10px 0; 
                        background:#3E61A8; 
                        color:white; 
                        border:none; 
                        border-radius:20px; 
                        font-size:18px; 
                        cursor:pointer; 
                        display:block;
                    ">
                    Continuer
                </button>
            </form>
            <script >
                function togglePwd() {
                    const pwdField = document.getElementById('pwdField');
                    if (pwdField.type === 'password') {
                        pwdField.type = 'text';
                    } else {
                        pwdField.type = 'password';
                    }
                }
            </script>
<?php include 'footer.php'; ?>
</body>
</html>