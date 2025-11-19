<?php
session_start();
require_once '../auth_check.php';
require_once '../connexion.inc.php';

// Vérification CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Requête invalide (CSRF).");
}

$id_demenageur = $_SESSION['user_id'];
$id_annonce = intval($_POST['id_annonce']);
$prix = floatval($_POST['prix']);
$message = trim($_POST['message']);


// Lire l'ancien enregistrement
$sql_old = "SELECT * FROM proposition WHERE id_annonce = ? AND id_demenageur = ?";
$stmt_old = $pdo->prepare($sql_old);
$stmt_old->execute([$id_annonce, $id_demenageur]);
$old = $stmt_old->fetch(PDO::FETCH_ASSOC);

if (!$old) {
    die("Proposition introuvable.");
}


// -----------------------------------------------------
// Copier ancienne version dans historique_proposition
// -----------------------------------------------------
$sql_insert = "
    INSERT INTO historique_proposition
    (id_annonce, id_demenageur, prix_propose, date_proposition, message, statut, date_enregistrement)
    VALUES (?, ?, ?, ?, ?, ?, NOW())
";

$stmt_ins = $pdo->prepare($sql_insert);
$stmt_ins->execute([
    $old['id_annonce'],
    $old['id_demenageur'],
    $old['prix_propose'],
    $old['date_proposition'],
    $old['message'],
    $old['statut']
]);


// -----------------------------------------------------
// Mise à jour de la nouvelle proposition
// -----------------------------------------------------
$sql_update = "
    UPDATE proposition
    SET prix_propose = ?, message = ?, date_proposition = NOW()
    WHERE id_annonce = ? AND id_demenageur = ?
";

$stmt_update = $pdo->prepare($sql_update);
$stmt_update->execute([$prix, $message, $id_annonce, $id_demenageur]);


$_SESSION['success'] = "Proposition modifiée avec succès, l'historique a été enregistré.";
header("Location: ../dashboard.php");
exit();
?>
