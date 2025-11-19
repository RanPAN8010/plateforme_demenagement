<?php if (empty($_SESSION['user_id'])): ?>
        
        <a href="quick_login.php" style="
            position: fixed; top: 150px; right: 20px; z-index: 9999; 
            background-color: #e74c3c; color: white; padding: 15px 20px; 
            border-radius: 10px; text-decoration: none; font-weight: bold; 
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);">
            🚀 一键登录
        </a>

    <?php else: ?>

        <a href="logout.php" style="
            position: fixed; top: 150px; right: 20px; z-index: 9999; 
            background-color: #2c3e50; color: white; padding: 15px 20px; 
            border-radius: 10px; text-decoration: none; font-weight: bold; 
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);">
            👋 退出登录 (ID: <?php echo $_SESSION['user_id']; ?>)
        </a>

    <?php endif; ?>
    ```


<?php
// 1. 引入数据库连接文件
include 'connexion.inc.php'; 

// 2. 编写 SQL 查询语句
// 这个查询稍微有点长，因为它需要把 annonce 表和 ville(城市)、image(图片) 表连起来
$sql = "
    SELECT 
        annonce.id_annonce,
        annonce.titre,
        annonce.description_rapide,
        annonce.date_depart,
        annonce.nombre_demenageur,
        
        v_dep.nom_ville AS ville_depart,
        
        v_arr.nom_ville AS ville_arrivee,
        
        img.chemin_image
    FROM 
        annonce
    
    JOIN adresse AS adr_dep ON annonce.id_adresse_depart = adr_dep.id_adresse
    JOIN ville AS v_dep ON adr_dep.id_ville = v_dep.id_ville
    
    JOIN adresse AS adr_arr ON annonce.id_adresse_arrive = adr_arr.id_adresse
    JOIN ville AS v_arr ON adr_arr.id_ville = v_arr.id_ville
    
    LEFT JOIN image_annonce AS img ON annonce.id_annonce = img.id_annonce
    
    GROUP BY annonce.id_annonce
    
    ORDER BY annonce.date_depart DESC
";

// 3. 执行查询
$stmt = $pdo->query($sql);
$annonces = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>广告列表 - 搬家平台</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <?php
        // 包含导航栏
        include 'head.php'; 
    ?>

    <main class="content-wrapper">
        
        <section class="filter-section">
            <input type="text" class="filter-input" placeholder="Ville de départ (Ville/CP)">
            <input type="text" class="filter-input" placeholder="Ville d'arrivée (Ville/CP)">
            <input type="text" class="filter-input" placeholder="Date de départ" onfocus="this.type='date'" onblur="if(!this.value) { this.type='text'; }">
            <input type="text" class="filter-input" placeholder="Date d'arrivée" onfocus="this.type='date'" onblur="if(!this.value) { this.type='text'; }">
        </section>
        
        <hr class="separator">
        
        <section class="ad-list-container">

            <?php if (count($annonces) > 0): ?>
                
<?php foreach ($annonces as $annonce): ?>
                    <a href="ad-detail.php?id=<?php echo $annonce['id_annonce']; ?>" class="ad-item-link">
                        <article class="ad-item">
                            
                            <div class="ad-image-container">
                                <?php if (!empty($annonce['chemin_image'])): ?>
                                    <img src="<?php echo htmlspecialchars($annonce['chemin_image']); ?>" class="ad-main-img">
                                <?php else: ?>
                                    <div class="ad-main-img-placeholder"></div>
                                <?php endif; ?>
                            </div>

                            <div class="ad-core-info">
                                <h3 class="ad-title"><?php echo htmlspecialchars($annonce['titre']); ?></h3>
                                
                                <div class="ad-capsules">
                                    <span class="capsule"><?php echo htmlspecialchars($annonce['ville_depart']); ?></span>
                                    <span class="capsule"><?php echo htmlspecialchars($annonce['ville_arrivee']); ?></span>
                                    <span class="capsule"><?php echo htmlspecialchars($annonce['date_depart']); ?></span>
                                    <span class="capsule"><?php echo htmlspecialchars($annonce['nombre_demenageur']); ?> pers.</span>
                                </div>
                            </div>

                            <div class="ad-divider"></div>

                            <div class="ad-description-box">
                                <p class="description-text">
                                    <?php echo htmlspecialchars($annonce['description_rapide']); ?>
                                </p>
                            </div>

                        </article>
                    </a>
                <?php endforeach; ?>

            <?php else: ?>
                <p style="text-align: center; color: #666;">暂时没有正在进行的搬家广告。</p>
            <?php endif; ?>
            
        </section>
        
    </main>

</body>
</html>