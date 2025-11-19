<?php
// 1. 连接数据库 (如果你的 index.php 还没包含 db.php)
include_once 'connexion.inc.php';

// 2. 查询随机的 5 条广告 (热门推荐)
// 使用 ORDER BY RAND() 实现随机
$sql_hot = "
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
    ORDER BY RAND() 
    LIMIT 5
";

$stmt_hot = $pdo->query($sql_hot);
$hot_annonces = $stmt_hot->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>搬家平台 - 首页</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <?php
        // 1. 包含你的头部文件 (head.php)
        include 'head.php'; 
    ?>

    <main>

        <section class="hero-section">
            <div class="slogan-container">
                <h2>HomeGo — On s’aide, on déménage, on avance.</h2>
            </div>
        </section>

        <section class="testimonials-section">
            <h2 class="section-title">用户口碑</h2>
            
            <div class="carousel-container">
                <button class="arrow arrow-left">&lt;</button>
                <div class="carousel-track-container">
                    <div class="carousel-track">
                        <div class="testimonial-card">
                            <div class="avatar-placeholder user-avatar"></div>
                            <h3>用户姓名</h3>
                            <div class="review-box"><p>用户评价</p></div>
                        </div>
                        <div class="testimonial-card faded">
                            <div class="avatar-placeholder user-avatar"></div>
                            <h3>用户姓名</h3>
                            <div class="review-box"><p>用户评价</p></div>
                        </div>
                        <div class="testimonial-card faded">
                            <div class="avatar-placeholder user-avatar"></div>
                            <h3>用户姓名</h3>
                            <div class="review-box"><p>用户评价</p></div>
                        </div>
                    </div>
                </div>
                <button class="arrow arrow-right">&gt;</button>
            </div>
        </section>
        
        <section class="movers-section">
            <h2 class="section-title">优秀搬家工</h2>
            
            <div class="movers-grid">
                <div class="mover-card">
                    <div class="avatar-placeholder mover-avatar"></div>
                    <h3>搬家工名字</h3>
                    <div class="mover-info-box">
                        <p>好评星数</p>
                        <p>用户评价</p>
                    </div>
                </div>
                <div class="mover-card">
                    <div class="avatar-placeholder mover-avatar"></div>
                    <h3>搬家工名字</h3>
                    <div class="mover-info-box">
                        <p>好评星数</p>
                        <p>用户评价</p>
                    </div>
                </div>
                <div class="mover-card">
                    <div class="avatar-placeholder mover-avatar"></div>
                    <h3>搬家工名字</h3>
                    <div class="mover-info-box">
                        <p>好评星数</p>
                        <p>用户评价</p>
                    </div>
                </div>
                <div class="mover-card">
                    <div class="avatar-placeholder mover-avatar"></div>
                    <h3>搬家工名字</h3>
                    <div class="mover-info-box">
                        <p>好评星数</p>
                        <p>用户评价</p>
                    </div>
                </div>
                <div class="mover-card">
                    <div class="avatar-placeholder mover-avatar"></div>
                    <h3>搬家工名字</h3>
                    <div class="mover-info-box">
                        <p>好评星数</p>
                        <p>用户评价</p>
                    </div>
                </div>
            </div>
        </section>
        
<section class="ads-carousel-section">
            <h2 class="section-title">Annonces Populaires</h2> <div class="carousel-container">
                <button class="arrow arrow-left dark-arrow">&lt;</button>
                
                <div class="carousel-track-container">
                <ul class="carousel-track" style="padding: 0; margin: 0; list-style: none; display: flex;">
                    
                    <?php if (count($hot_annonces) > 0): ?>
                        <?php foreach ($hot_annonces as $ad): ?>
                            <li class="ad-item" style="flex-shrink: 0; width: 100%; box-sizing: border-box; margin: 0;">
                                
                                <div class="ad-image-container">
                                    <?php if (!empty($ad['chemin_image'])): ?>
                                        <img src="<?php echo htmlspecialchars($ad['chemin_image']); ?>" class="ad-main-img" alt="Image">
                                    <?php else: ?>
                                        <div class="ad-main-img-placeholder">Image</div>
                                    <?php endif; ?>
                                </div>

                                <div class="ad-core-info">
                                    <a href="ad-detail.php?id=<?php echo $ad['id_annonce']; ?>" style="text-decoration:none;">
                                        <h3 class="ad-title"><?php echo htmlspecialchars($ad['titre']); ?></h3>
                                    </a>
                                    
                                    <div class="ad-capsules">
                                        <span class="capsule"><?php echo htmlspecialchars($ad['ville_depart']); ?></span>
                                        <span class="capsule"><?php echo htmlspecialchars($ad['ville_arrivee']); ?></span>
                                        <span class="capsule"><?php echo htmlspecialchars($ad['date_depart']); ?></span>
                                        <span class="capsule"><?php echo htmlspecialchars($ad['nombre_demenageur']); ?> pers.</span>
                                    </div>
                                </div>

                                <div class="ad-divider"></div>

                                <div class="ad-description-box">
                                    <p class="description-text">
                                        <?php echo htmlspecialchars($ad['description_rapide']); ?>
                                    </p>
                                </div>

                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li style="width: 100%; text-align: center; padding: 20px;">Aucune annonce populaire.</li>
                    <?php endif; ?>

                </ul>
            </div>
                
                <button class="arrow arrow-right dark-arrow">&gt;</button>
            </div>
        </section>
        
    </main>

    <?php
        // 建议你创建一个 footer.php 来封装页脚
        // include 'footer.php'; 
    ?>
    
    <?php
        // include 'footer.php'; 
    ?>

    
    <script src="js/main.js"></script>
</body>
</html>