<?php

include_once 'connexion.inc.php';


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

        include 'head.php'; 
    ?>

    <main>

        <section class="hero-section">
            <div class="slogan-container">
                <h2>HomeGo — On s’aide, on déménage, on avance.</h2>
            </div>
        </section>

        <section class="testimonials-section">
            <h2 class="section-title">Avis des utilisateurs</h2>
            
            <div class="carousel-container">
                <button class="arrow arrow-left">&lt;</button>
<div class="carousel-track-container">
                    <ul class="carousel-track" style="padding: 0; margin: 0; list-style: none; display: flex;">
                        
                        <li class="testimonial-card">
                            <img src="https://i.pravatar.cc/150?img=5" alt="Sophie Martin" class="testimonial-avatar">
                            <h3>Sophie Martin</h3>
                            <div class="review-box">
                                <p>Service ultra rapide ! J'ai trouvé des déménageurs qualifiés en moins d'une heure. Je recommande.</p>
                            </div>
                        </li>

                        <li class="testimonial-card">
                            <img src="https://i.pravatar.cc/150?img=11" alt="Thomas Dubois" class="testimonial-avatar">
                            <h3>Thomas Dubois</h3>
                            <div class="review-box">
                                <p>L'interface est très intuitive. J'ai pu comparer les prix et économiser 200€ sur mon déménagement.</p>
                            </div>
                        </li>

                        <li class="testimonial-card">
                            <img src="https://i.pravatar.cc/150?img=9" alt="Camille Laurent" class="testimonial-avatar">
                            <h3>Camille Laurent</h3>
                            <div class="review-box">
                                <p>Les déménageurs étaient ponctuels et très soigneux avec mes meubles fragiles. Merci HomeGo !</p>
                            </div>
                        </li>

                        <li class="testimonial-card">
                            <img src="https://i.pravatar.cc/150?img=33" alt="Lucas Bernard" class="testimonial-avatar">
                            <h3>Lucas Bernard</h3>
                            <div class="review-box">
                                <p>Une excellente expérience. Le support client a répondu à toutes mes questions très rapidement.</p>
                            </div>
                        </li>

                        <li class="testimonial-card">
                            <img src="https://i.pravatar.cc/150?img=44" alt="Emma Petit" class="testimonial-avatar">
                            <h3>Emma Petit</h3>
                            <div class="review-box">
                                <p>Simple, efficace et sécurisé. Tout s'est déroulé comme prévu du début à la fin.</p>
                            </div>
                        </li>

                    </ul>
                </div>
                <button class="arrow arrow-right">&gt;</button>
            </div>
        </section>
        
        <section class="movers-section">
            <h2 class="section-title">Excellent déménageur</h2>
            
            <div class="movers-grid">
                <div class="mover-card">
                    <div class="avatar-placeholder mover-avatar"></div>
                    <h3>Marc Chevalier</h3>
                    <div class="mover-info-box">
                        <p>☆☆☆☆☆</p>
                        <p>“Ponctualité exemplaire et grande force ! Il a su monter mon armoire sans problème au 4ème étage.”</p>
                    </div>
                </div>
                <div class="mover-card">
                    <div class="avatar-placeholder mover-avatar"></div>
                    <h3>Éloïse Mercier</h3>
                    <div class="mover-info-box">
                        <p>☆☆☆☆</p>
                        <p>“Très professionnelle et organisée. Éloïse a géré l'emballage et le transport avec une grande efficacité.”</p>
                    </div>
                </div>
                <div class="mover-card">
                    <div class="avatar-placeholder mover-avatar"></div>
                    <h3>Antoine Leroy</h3>
                    <div class="mover-info-box">
                        <p>☆☆☆☆☆</p>
                        <p>“Excellent ! Prix juste et service client très sympathique. Je le rappellerai pour mon prochain déménagement.”</p>
                    </div>
                </div>
                <div class="mover-card">
                    <div class="avatar-placeholder mover-avatar"></div>
                    <h3>Léa Morel</h3>
                    <div class="mover-info-box">
                        <p>☆☆☆☆☆</p>
                        <p>“Léa était très rassurante et a pris grand soin de ma collection de disques fragiles. Je recommande pour les objets de valeur.”</p>
                    </div>
                </div>
                <div class="mover-card">
                    <div class="avatar-placeholder mover-avatar"></div>
                    <h3>Yann Dubois</h3>
                    <div class="mover-info-box">
                        <p>☆☆☆☆☆</p>
                        <p>“Rapide et efficace. Le travail était terminé bien plus tôt que prévu. Un vrai pro !”</p>
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

    ?>
    
    <?php

    ?>

    
    <script src="js/main.js"></script>
</body>
</html>