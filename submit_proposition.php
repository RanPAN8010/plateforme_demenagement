<?php



require_once 'auth_check.php'; 
include 'db.php';              

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ads-list.php");
    exit();
}

$annonce_id = intval($_POST['annonce_id'] ?? 0);
$prix_propose = floatval($_POST['prix_propose'] ?? 0);
$demenageur_id = $CURRENT_USER_ID; 


if ($annonce_id <= 0 || $prix_propose <= 0) {
    die("Erreur de validation : L'offre ou l'ID de l'annonce est invalide.");
}

try {
    
    $stmt_role = $pdo->prepare("SELECT id_demenageur FROM demenageur WHERE id_demenageur = ? LIMIT 1");
    $stmt_role->execute([$demenageur_id]);
    if (!$stmt_role->fetch()) {
        die("Accès refusé : Seuls les déménageurs enregistrés peuvent soumettre une offre.");
    }
    
    
    $sql = "INSERT INTO proposition 
            (id_annonce, id_demenageur, prix_propose, date_proposition, statut, message) 
            VALUES 
            (?, ?, ?, NOW(), ?, ?)"; 

    $stmt = $pdo->prepare($sql);
    $statut = 'en_attente'; 
    $message = "Offre soumise automatiquement."; 

    $stmt->execute([
        $annonce_id,
        $demenageur_id,
        $prix_propose,
        $statut,
        $message
    ]);

    
    header("Location: ad-detail.php?id=$annonce_id&status=success");
    exit();

} catch (\PDOException $e) {
   
    $error_msg = "Erreur BDD : Votre offre n'a pas pu être enregistrée.";
    if ($e->getCode() == 23000) { 
        $error_msg = "Vous avez déjà soumis une offre pour cette annonce.";
    }
    header("Location: ad-detail.php?id=$annonce_id&status=error&msg=" . urlencode($error_msg));
    exit();
}
?>