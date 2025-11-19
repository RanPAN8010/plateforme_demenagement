<?php

header('Content-Type: application/json');
include 'connexion.inc.php';

try {

    $stmt = $pdo->query("SELECT id_ville, nom_ville, code_postal FROM ville ORDER BY nom_ville ASC");
    $cities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($cities);
} catch (Exception $e) {
    echo json_encode([]);
}
?>