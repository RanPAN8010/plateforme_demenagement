<?php

require_once 'auth_check.php'; 

include 'connexion.inc.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Erreur : Aucun identifiant d'annonce spécifié. <a href='ads-list.php'>Retour</a>");
}
$id_annonce = intval($_GET['id']);

$sql = "
    SELECT 
        annonce.*, 
        v_dep.nom_ville AS ville_depart, 
        v_arr.nom_ville AS ville_arrivee,
        img.chemin_image,
        u.nom AS nom_client,
        u.prenom AS prenom_client
    FROM 
        annonce
    JOIN adresse AS adr_dep ON annonce.id_adresse_depart = adr_dep.id_adresse
    JOIN ville AS v_dep ON adr_dep.id_ville = v_dep.id_ville
    JOIN adresse AS adr_arr ON annonce.id_adresse_arrive = adr_arr.id_adresse
    JOIN ville AS v_arr ON adr_arr.id_ville = v_arr.id_ville
    LEFT JOIN image_annonce AS img ON annonce.id_annonce = img.id_annonce
    LEFT JOIN client c ON annonce.id_client = c.id_client
    LEFT JOIN utilisateur u ON c.id_client = u.id_utilisateur
    WHERE 
        annonce.id_annonce = ?
    LIMIT 1
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id_annonce]);
$annonce = $stmt->fetch();

// 如果找不到广告
if (!$annonce) {
    die("Erreur : Annonce introuvable. <a href='ads-list.php'>Retour</a>");
}

$is_mover = false;
$stmt_role = $pdo->prepare("SELECT id_demenageur FROM demenageur WHERE id_demenageur = ? LIMIT 1");
$stmt_role->execute([$CURRENT_USER_ID]); // $CURRENT_USER_ID vient de auth_check.php
if ($stmt_role->fetch()) {
    $is_mover = true; 
}


?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($annonce['titre']); ?> - Détail</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* 详情页专用简单样式 */
        .detail-container {
            background-color: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-top: 20px;
        }
        .detail-header {
            display: flex;
            gap: 30px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        .detail-img {
            flex: 1;
            max-width: 400px;
            min-width: 300px;
            height: 300px;
            object-fit: cover;
            border-radius: 15px;
            background-color: #eee;
        }
        .detail-info {
            flex: 2;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .detail-title {
            color: #6c87c4;
            font-size: 2em;
            margin: 0 0 15px 0;
        }
        .info-row {
            margin-bottom: 10px;
            font-size: 1.1em;
            color: #555;
        }
        .detail-desc {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 15px;
            line-height: 1.6;
            color: #333;
        }
        .btn-contact {
            display: inline-block;
            background-color: #e28c3f;
            color: white;
            padding: 15px 30px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: bold;
            margin-top: 20px;
            text-align: center;
            width: fit-content;
        }
        .btn-contact:hover {
            background-color: #d17b30;
        }
    </style>
</head>
<body>

    <?php include 'head.php'; ?>

    <main class="content-wrapper">
        <div class="detail-container">
            
            <div class="detail-header">
                <?php if (!empty($annonce['chemin_image'])): ?>
                    <img src="<?php echo htmlspecialchars($annonce['chemin_image']); ?>" class="detail-img" alt="Photo">
                <?php else: ?>
                    <div class="detail-img" style="display:flex;align-items:center;justify-content:center;color:#aaa;">Pas d'image</div>
                <?php endif; ?>

                <div class="detail-info">
                    <h1 class="detail-title"><?php echo htmlspecialchars($annonce['titre']); ?></h1>
                    
                    <div class="info-row">
                        <strong>Trajet :</strong> 
                        <?php echo htmlspecialchars($annonce['ville_depart']); ?> 
                        &rarr; 
                        <?php echo htmlspecialchars($annonce['ville_arrivee']); ?>
                    </div>
                    
                    <div class="info-row">
                        <strong>Date de départ :</strong> <?php echo htmlspecialchars($annonce['date_depart']); ?>
                    </div>
                    
                    <div class="info-row">
                        <strong>Déménageurs requis :</strong> <?php echo htmlspecialchars($annonce['nombre_demenageur']); ?> personne(s)
                    </div>

                    <div class="info-row">
                        <strong>Publié par :</strong> <?php echo htmlspecialchars($annonce['prenom_client'] . ' ' . $annonce['nom_client']); ?>
                    </div>

                    <a href="#" class="btn-contact">Contacter ce client</a>
                </div>
            </div>

            <h3>Description détaillée</h3>
            <div class="detail-desc">
                <?php echo nl2br(htmlspecialchars($annonce['description'])); ?>
            </div>
            
            <br>
            
            <?php if ($is_mover): ?>
                <h3 style="color:#e28c3f;">Soumettre une Proposition</h3>
                <div class="quote-form-container">
                    <form method="POST" action="submit_proposition.php">
                        <input type="hidden" name="annonce_id" value="<?php echo htmlspecialchars($annonce['id_annonce']); ?>">
                        
                        <input type="number" name="prix_propose" placeholder="Entrez votre offre en €" required min="1" step="0.01" class="quote-input">
                        <button type="submit" class="btn-quote">Confirmer l'offre</button>
                    </form>
                </div>
            <?php else: ?>
                <p style="color:#888;">*Seuls les déménageurs peuvent soumettre une proposition.</p>
            <?php endif; ?>

            <br>
            <a href="ads-list.php" style="color: #6c87c4; text-decoration: none; font-weight: bold;">&larr; Retour à la liste</a>

        </div>
    </main>

</body>
</html>