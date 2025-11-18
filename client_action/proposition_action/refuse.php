<?php
session_start();
require_once '../../auth_check.php';
require_once '../../connexion.inc.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../dashboard.php");
    exit();
}

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = "Requête invalide (CSRF).";
    header("Location: ../../dashboard.php");
    exit();
}

$id_annonce     = intval($_POST['id_annonce']);
$id_demenageur  = intval($_POST['id_demenageur']);
$id_client      = $_SESSION['user_id'];

// Vérifier annonce appartient au client
$sql = "SELECT id_client FROM annonce WHERE id_annonce = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_annonce]);
$annonce = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$annonce || $annonce['id_client'] != $id_client) {
    $_SESSION['error'] = "Vous n'avez pas la permission de refuser cette proposition.";
    header("Location: ../../dashboard.php");
    exit();
}

// Refuser uniquement si en_attente
$sql = "UPDATE proposition
        SET statut = 'refuse'
        WHERE id_annonce = ?
        AND id_demenageur = ?
        AND statut = 'en_attente'";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_annonce, $id_demenageur]);

if ($stmt->rowCount() > 0) {
    $_SESSION['success'] = "Proposition refusée.";
} else {
    $_SESSION['error'] = "Impossible de refuser cette proposition (déjà acceptée ou inexistante).";
}

header("Location: ../../dashboard.php");
exit();
