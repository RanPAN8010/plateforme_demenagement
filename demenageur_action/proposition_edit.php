<?php
session_start();
require_once '../auth_check.php';
require_once '../connexion.inc.php';

if (!isset($_GET['id_annonce']) || !is_numeric($_GET['id_annonce'])) {
    die("Annonce invalide.");
}

$id_annonce = intval($_GET['id_annonce']);
$id_demenageur = $_SESSION['user_id'];


// 读取当前报价
$sql = "
    SELECT prix_propose, message 
    FROM proposition
    WHERE id_annonce = ? AND id_demenageur = ?
    LIMIT 1
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_annonce, $id_demenageur]);
$prop = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$prop) {
    die("Aucune proposition trouvée.");
}


// CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier Proposition</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body style="padding:40px;">

<h2 style="margin-bottom:20px;">Modifier votre proposition</h2>

<form action="proposition_edit_action.php" method="POST" style="max-width:500px;">
    
    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
    <input type="hidden" name="id_annonce" value="<?= $id_annonce ?>">

    <label>Prix proposé (€):</label>
    <input type="number" step="0.01" name="prix" value="<?= htmlspecialchars($prop['prix_propose']) ?>"
           required style="width:100%; padding:10px; margin-bottom:20px;">

    <label>Message :</label>
    <textarea name="message" required
              style="width:100%; height:120px; padding:10px; margin-bottom:20px;"
    ><?= htmlspecialchars($prop['message']) ?></textarea>

    <button type="submit" class="btn-primary" style="padding:10px 25px;">Enregistrer</button>
    <button type="button"  class="btn-cancel" onclick="window.location.href='../dashboard.php'" >
        Annuler
    </button>
</form>

</body>
</html>
