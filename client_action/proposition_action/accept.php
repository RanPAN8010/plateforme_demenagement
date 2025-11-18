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

$id_annonce      = intval($_POST['id_annonce']);
$id_demenageur   = intval($_POST['id_demenageur']);
$id_client       = $_SESSION['user_id'];

// 1. Charger l'annonce
$sql = "SELECT id_client, nombre_demenageur, date_depart, heure_depart,
               id_adresse_depart, id_adresse_arrive, volume_estime, poids_estime
        FROM annonce
        WHERE id_annonce = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_annonce]);
$annonce = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$annonce || $annonce['id_client'] != $id_client) {
    $_SESSION['error'] = "Vous n'avez pas la permission d'accepter cette proposition.";
    header("Location: ../../dashboard.php");
    exit();
}

$maxDemenageurs = intval($annonce['nombre_demenageur']);

// 2. Vérifier nombre déjà accepté
$sql = "SELECT COUNT(*) FROM proposition
        WHERE id_annonce = ? AND statut = 'accepte'";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_annonce]);
$accepted_count = intval($stmt->fetchColumn());

if ($accepted_count >= $maxDemenageurs) {
    $_SESSION['error'] = "Le nombre maximum de déménageurs est déjà atteint.";
    header("Location: ../../dashboard.php");
    exit();
}

$pdo->beginTransaction();

try {

    // 3. Accepter la proposition
    $sql = "UPDATE proposition
            SET statut = 'accepte'
            WHERE id_annonce = ? AND id_demenageur = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_annonce, $id_demenageur]);

    // 4. Récupérer ou créer case
    $sql = "SELECT id_case FROM `case`
            WHERE id_annonce = ? AND id_client = ?
            LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_annonce, $id_client]);
    $case = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($case) {
        $id_case = $case['id_case'];
    } else {
        $sql = "INSERT INTO `case`
                (id_client, id_annonce, date_depart, heure_depart,
                 id_adresse_depart, id_adresse_arrive, volume, poids, statut)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'en_attente')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $id_client,
            $id_annonce,
            $annonce['date_depart'],
            $annonce['heure_depart'] ?? "00:00:00",
            $annonce['id_adresse_depart'],
            $annonce['id_adresse_arrive'],
            $annonce['volume_estime'] ?? 0,
            $annonce['poids_estime'] ?? 0
        ]);

        $id_case = $pdo->lastInsertId();
    }

    // 5. Ajouter participation
    $sql = "SELECT 1 FROM participation
            WHERE id_case = ? AND id_demenageur = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_case, $id_demenageur]);

    if (!$stmt->fetch()) {
        $sql = "INSERT INTO participation (id_case, id_demenageur)
                VALUES (?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id_case, $id_demenageur]);
    }

    // 6. Si limite atteinte => refuser les autres
    if ($accepted_count + 1 >= $maxDemenageurs) {
        $sql = "UPDATE proposition
                SET statut = 'refuse'
                WHERE id_annonce = ? AND statut = 'en_attente'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id_annonce]);
    }

    $pdo->commit();
    $_SESSION['success'] = "Proposition acceptée avec succès !";

} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = "Erreur lors du traitement : " . $e->getMessage();
}

header("Location: ../../dashboard.php");
exit();
