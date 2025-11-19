<?php
// 1. 连接数据库
include 'connexion.inc.php';

// 检查是否是 POST 请求
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- A. 获取并清理表单数据 ---
    $dep_city = trim($_POST['departure_city']);
    $arr_city = trim($_POST['arrival_city']);
    $movers = intval($_POST['movers_count']);
    $date = $_POST['departure_date'];
    $desc = trim($_POST['description']);

    // --- B. 验证必填项 ---
    if (empty($dep_city) || empty($arr_city) || empty($movers) || empty($date) || empty($desc)) {
        die("Erreur : Tous les champs sont obligatoires. <a href='publish-ad.php'>Retour</a>");
    }

    // 验证图片上传
    if (!isset($_FILES['ad_image']) || $_FILES['ad_image']['error'] != 0) {
        die("Erreur : Veuillez télécharger une image valide. <a href='publish-ad.php'>Retour</a>");
    }

    try {
        // 开启事务 (保证所有步骤要么全成功，要么全失败)
        $pdo->beginTransaction();

        // --- C. 处理图片上传 ---
        $target_dir = "img/uploads/";
        
        // 如果文件夹不存在，自动创建
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        // 生成唯一文件名防止覆盖 (例如: ad_654df321.jpg)
        $file_ext = strtolower(pathinfo($_FILES["ad_image"]["name"], PATHINFO_EXTENSION));
        
        // 简单验证文件格式
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($file_ext, $allowed_ext)) {
             throw new Exception("Format d'image non autorisé (JPG, PNG, GIF uniquement).");
        }

        $new_file_name = "ad_" . uniqid() . "." . $file_ext;
        $target_file = $target_dir . $new_file_name;

        // 移动文件
        if (!move_uploaded_file($_FILES["ad_image"]["tmp_name"], $target_file)) {
            throw new Exception("Échec du téléchargement de l'image.");
        }

        // --- D. 处理地理位置 (严格模式：城市必须已存在) ---
        
        // 1. 验证并获取出发城市 ID
        $id_ville_dep = getVilleID($pdo, $dep_city);
        if (!$id_ville_dep) {
            // 如果返回 false，说明城市不存在，报错并停止
            die("Erreur : La ville de départ '<b>" . htmlspecialchars($dep_city) . "</b>' n'est pas disponible dans notre base de données. Veuillez en sélectionner une dans la liste déroulante. <a href='publish-ad.php'>Retour</a>");
        }
        // 城市存在，创建出发地址
        $id_adresse_dep = createAddress($pdo, $id_ville_dep);

        // 2. 验证并获取到达城市 ID
        $id_ville_arr = getVilleID($pdo, $arr_city);
        if (!$id_ville_arr) {
            die("Erreur : La ville d'arrivée '<b>" . htmlspecialchars($arr_city) . "</b>' n'est pas disponible dans notre base de données. Veuillez en sélectionner une dans la liste déroulante. <a href='publish-ad.php'>Retour</a>");
        }
        // 城市存在，创建到达地址
        $id_adresse_arr = createAddress($pdo, $id_ville_arr);

        // --- E. 插入广告 (Annonce) ---
        
        // 自动生成标题
        $titre = "Déménagement $dep_city -> $arr_city";
        // 自动生成简短描述 (如果描述超过50字则截断)
        $desc_rapide = mb_strlen($desc) > 50 ? mb_substr($desc, 0, 50) . "..." : $desc;
        
        // 假设当前用户ID为 100 (临时测试账号，后期对接登录系统)
        $id_client = 100; 

        $sql_ad = "INSERT INTO annonce 
            (titre, description, description_rapide, date_depart, heure_depart, nombre_demenageur, id_adresse_depart, id_adresse_arrive, id_client, statut) 
            VALUES 
            (?, ?, ?, ?, '09:00:00', ?, ?, ?, ?, 'publie')";
        
        $stmt = $pdo->prepare($sql_ad);
        $stmt->execute([$titre, $desc, $desc_rapide, $date, $movers, $id_adresse_dep, $id_adresse_arr, $id_client]);
        
        // 获取刚插入的广告ID
        $id_annonce = $pdo->lastInsertId();

        // --- F. 插入图片记录 (Image_Annonce) ---
        $sql_img = "INSERT INTO image_annonce (id_annonce, chemin_image) VALUES (?, ?)";
        $stmt = $pdo->prepare($sql_img);
        $stmt->execute([$id_annonce, $target_file]);

        // 提交事务 (确认保存)
        $pdo->commit();

        // --- G. 成功跳转 ---
        // 跳转回广告列表页
        header("Location: ads-list.php");
        exit();

    } catch (Exception $e) {
        // 如果出错，回滚数据库操作 (撤销之前的所有步骤)
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo "Erreur lors de la publication : " . $e->getMessage() . " <a href='publish-ad.php'>Retour</a>";
    }
}

// ========== 辅助函数 ==========

// 1. 只查找城市ID，如果不存在返回 false (严格限制：不能创建新城市)
function getVilleID($pdo, $nom_ville) {
    $nom_ville = trim($nom_ville);

    // 尝试查找 (LIMIT 1 提高效率)
    $stmt = $pdo->prepare("SELECT id_ville FROM ville WHERE nom_ville = ? LIMIT 1");
    $stmt->execute([$nom_ville]);
    $row = $stmt->fetch();

    if ($row) {
        return $row['id_ville']; // 找到了，返回 ID
    } else {
        return false; // 没找到，返回 false
    }
}

// 2. 创建一个关联到城市的地址
function createAddress($pdo, $id_ville) {
    // 因为表单只问了城市，没有问街道，我们填一个默认街道
    $rue = "Centre ville"; 
    // 默认楼层0，无电梯
    $stmt = $pdo->prepare("INSERT INTO adresse (rue, etage, ascenseur, id_ville) VALUES (?, 0, 0, ?)");
    $stmt->execute([$rue, $id_ville]);
    
    // 返回新创建的地址ID
    return $pdo->lastInsertId();
}
?>