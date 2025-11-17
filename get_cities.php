<?php
// get_cities.php
header('Content-Type: application/json');
include 'db.php';

try {
    // 查询所有城市，按名字排序
    // 我们把 ID, 名字, 邮编都取出来
    $stmt = $pdo->query("SELECT id_ville, nom_ville, code_postal FROM ville ORDER BY nom_ville ASC");
    $cities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($cities);
} catch (Exception $e) {
    echo json_encode([]);
}
?>