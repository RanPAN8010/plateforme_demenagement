<?php
session_start();
require_once "connexion.inc.php";
require_once "auth_check.php"; 

$CURRENT_USER_ID = $_SESSION['user_id'];
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

$errors = array();
$success = "";
try {
    $stmt = $pdo->prepare("
        SELECT nom, prenom, email, telephone, id_ville, mot_de_passe
        FROM utilisateur 
        WHERE id_utilisateur = ?
    ");
    $stmt->execute(array($CURRENT_USER_ID));
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur de chargement utilisateur.");
}
//Traitement de la mise à jour du profil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {

    $csrf_post = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if ($csrf_post !== $csrf) {
        $errors[] = "Requête invalide (CSRF).";
    } else {

        $nom       = isset($_POST['nom']) ? trim($_POST['nom']) : '';
        $prenom    = isset($_POST['prenom']) ? trim($_POST['prenom']) : '';
        $telephone = isset($_POST['telephone']) ? trim($_POST['telephone']) : '';
        $id_ville  = isset($_POST['id_ville']) ? $_POST['id_ville'] : '';

        if ($nom === '' || $prenom === '') $errors[] = 'Nom et prénom sont obligatoires.';
        if (!preg_match('/^[0-9]{8,15}$/', $telephone)) $errors[] = 'Le téléphone doit contenir 8-15 chiffres.';
        if ($id_ville === '') $errors[] = 'Veuillez choisir une ville.';

        if (empty($errors)) {
            try {
                $stmtUp = $pdo->prepare("
                    UPDATE utilisateur 
                    SET nom=?, prenom=?, telephone=?, id_ville=? 
                    WHERE id_utilisateur=?
                ");
                $stmtUp->execute(array($nom, $prenom, $telephone, $id_ville, $CURRENT_USER_ID));

                $success = "Profil mis à jour avec succès.";

                $user['nom'] = $nom;
                $user['prenom'] = $prenom;
                $user['telephone'] = $telephone;
                $user['id_ville'] = $id_ville;

            } catch (PDOException $e) {
                $errors[] = "Erreur lors de la mise à jour du profil.";
            }
        }
    }
}
//Traitement du changement de mot de passe 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {

    $csrf_post = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if ($csrf_post !== $csrf) {
        $errors[] = "Requête invalide (CSRF).";
    } else {

        $current = isset($_POST['current_password']) ? $_POST['current_password'] : '';
        $new     = isset($_POST['new_password']) ? $_POST['new_password'] : '';
        $confirm = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

        if ($current === '' || $new === '' || $confirm === '') {
            $errors[] = "Tous les champs de mot de passe sont obligatoires.";
        }

        if ($new !== $confirm) {
            $errors[] = "Les mots de passe ne correspondent pas.";
        }

        if (strlen($new) < 8) {
            $errors[] = "Le nouveau mot de passe doit contenir au moins 8 caractères.";
        }

        if (empty($errors)) {
            try {
                $stmtPass = $pdo->prepare("SELECT mot_de_passe FROM utilisateur WHERE id_utilisateur=?");
                $stmtPass->execute(array($CURRENT_USER_ID));
                $row = $stmtPass->fetch(PDO::FETCH_ASSOC);

                if (!$row || !password_verify($current, $row['mot_de_passe'])) {
                    $errors[] = "Le mot de passe actuel est incorrect.";
                } else {
                    $newHash = password_hash($new, PASSWORD_DEFAULT);
                    $stmtUpd = $pdo->prepare("UPDATE utilisateur SET mot_de_passe=? WHERE id_utilisateur=?");
                    $stmtUpd->execute(array($newHash, $CURRENT_USER_ID));

                    $success = "Mot de passe changé avec succès.";
                }
            } catch (PDOException $e) {
                $errors[] = "Erreur lors du changement de mot de passe.";
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
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Modification du profil</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'head.php'; ?>
    <main>
        <div class="content-wrapper" style="margin-top:200px; max-width:600px; margin:0 auto; padding:20px;">
            <h2 class="section-title" style="text-align:center; margin-top:150px;">
                changement de profil</h2>
            <?php if (!empty($errors)): ?>
                <div class="error-message-box">
                    <?php foreach ($errors as $e) echo "<p>$e</p>"; ?>
                </div>
            <?php endif; ?>    
            <?php if (!empty($success)): ?>
                <div style="background:#ddffdd; padding:15px; border-radius:12px; margin-bottom:25px;">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?> 
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                <input type="hidden" name="action" value="update_profile">

                <!-- Email（不可修改） -->
                <div class="form-row">
                    <div class="form-label">Email</div>
                    <div class="form-info"><?php echo htmlspecialchars($user['email']); ?></div>
                </div>
                <!-- Nom -->
                <div class="form-row">
                    <div class="form-label">Nom</div>
                    <input type="text" name="nom" class="input-field" value="<?php echo htmlspecialchars($user['nom']); ?>">
                </div>
                <!-- Prénom -->
                <div class="form-row">
                    <div class="form-label">Prenom</div>
                    <input type="text" name="prenom" class="input-field" value="<?php echo htmlspecialchars($user['prenom']); ?>">
                </div>            
                <!-- Téléphone -->
                <div class="form-row">
                    <div class="form-label">telephone</div>
                    <input type="text" name="telephone" class="input-field" value="<?php echo htmlspecialchars($user['telephone']); ?>">
                </div>
                <!-- Ville -->
                <div class="form-row">
                    <div class="form-label">ville</div>
                    <select name="id_ville" class="input-field">
                        <option value="">-- Choisir une ville --</option>
                        <?php foreach ($villes as $v): ?>
                            <option value="<?php echo $v['id_ville']; ?>"
                                <?php if ($v['id_ville'] == $user['id_ville']) echo "selected"; ?>>
                                <?php echo htmlspecialchars($v['nom_ville']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="text-align:center; margin-top:30px;">
                    <button type="submit" class="btn-primary">Confirmer</button>
                </div>
            </form>
            <br><br><hr><br>
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                <input type="hidden" name="action" value="change_password">
                <h3>Changer le mot de passe</h3>
                <!-- Mot de passe actuel -->
                <div class="form-row">
                    <div class="form-label">Mot de passe actuel</div>
                    <input type="password" name="current_password" class="input-field">
                </div>
                <!-- Nouveau mot de passe -->
                <div class="form-row">
                    <div class="form-label">Nouveau mot de passe</div>
                    <input type="password" name="new_password" class="input-field">
                </div>
                <!-- Confirmer le nouveau mot de passe -->
                <div class="form-row">
                    <div class="form-label">Confirmer le nouveau mot de passe</div>
                    <input type="password" name="confirm_password" class="input-field">
                </div>
                <div style="text-align:center; margin-top:30px;">
                    <button type="submit" class="btn-primary"> Confirmer</button>
                </div>
            </form>
        </div>
    </main>
</body>
</html>