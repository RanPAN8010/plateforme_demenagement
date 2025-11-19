<?php
session_start();
/*
si client

if (isset($_GET['debug']) && $_GET['debug'] == 1) {
    $_SESSION['user_id'] = 16;
    $CURRENT_USER_ID = 16;
}
*/

/*
si demenageur
*/
if (isset($_GET['debug']) && $_GET['debug'] == 1) {
    $_SESSION['user_id'] = 17;
    $CURRENT_USER_ID = 17;
}


require_once 'auth_check.php';     
require_once 'connexion.inc.php'; 

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
}
$csrf = $_SESSION['csrf_token'];
$errors = [];
$success = "";
$annonces_affichage = [];
$propositionsByAnnonce = [];
$historique_client = [];
$historique_demenageur = [];

function e($s) {
    return htmlspecialchars(isset($s) ? $s : '', ENT_QUOTES, 'UTF-8');
}
try {
    $stmt = $pdo->prepare("SELECT id_utilisateur, nom, prenom, email, telephone, sexe, id_ville, photo_profil, etat_compte 
                           FROM utilisateur WHERE id_utilisateur = ?");
    $stmt->execute([$CURRENT_USER_ID]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        $errors[] = 'Votre compte utilisateur est introuvable.';
        header('Location: connexion.php');
        exit;
    }
} catch (PDOException $e) {
    $errors[] = 'Erreur lors de la récupération des informations utilisateur.';
    error_log("Dashboard read user error: " . $e->getMessage());
    $user = [];
}

// Détecter le rôle de l'utilisateur (admin, demenageur, client, user)
$userRole = 'user';
try {
    $stmtA = $pdo->prepare("SELECT id_admin FROM admin WHERE id_admin = ?");
    $stmtA->execute([$CURRENT_USER_ID]);
    if ($stmtA->fetch()) {
        $userRole = 'admin';
    } else {
        $stmtD = $pdo->prepare("SELECT id_demenageur FROM demenageur WHERE id_demenageur = ?");
        $stmtD->execute([$CURRENT_USER_ID]);
        if ($stmtD->fetch()) {
            $userRole = 'demenageur';
        } else {
            $stmtC = $pdo->prepare("SELECT id_client FROM client WHERE id_client = ?");
            $stmtC->execute([$CURRENT_USER_ID]);
            if ($stmtC->fetch()) $userRole = 'client';
        }
    }
} catch (PDOException $e) {
    error_log("Role detect error: " . $e->getMessage());
}

//Gestion du téléchargement d'avatar 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'telecharger_avatar') {
    if (!isset($_SESSION['csrf_token']) || !isset($_POST['csrf_token']) || $_SESSION['csrf_token'] !== $_POST['csrf_token']) {
        $errors[] = 'Requête invalide (CSRF).';
    } else {
        if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'veuillez sélectionner un fichier valide à télécharger。';
        } else {
            $file = $_FILES['avatar'];
            $maxBytes = 2 * 1024 * 1024; // 2MB maximum
            if ($file['size'] > $maxBytes) {
                $errors[] = 'Le fichier dépasse la taille maximale de 2 Mo。';
            } else {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);
                $typesAutorises = array(
                    'image/jpeg' => 'jpg',
                    'image/png'  => 'png',
                    'image/webp' => 'webp'
                );
                if (!isset($typesAutorises[$mime])) {
                    $errors[] = 'le type de fichier n\'est pas autorisé。';
                } else {
                    if (!getimagesize($file['tmp_name'])) {
                        $errors[] = "Le fichier n'est pas une image valide.";
                    } else {
                        $extension = $typesAutorises[$mime];
                        $jetonAleatoire = bin2hex(openssl_random_pseudo_bytes(8));
                        $nomFinal = 'avatar_' . $CURRENT_USER_ID . '_' . $jetonAleatoire . '.' . $extension;
                        $dossierUpload = __DIR__ . '/img/avatar/';
                        if (!is_dir($dossierUpload)) {
                            mkdir($dossierUpload, 0755, true);
                        }               
                        $cheminServeur = $dossierUpload . '/' . $nomFinal;
                        $cheminBDD = 'img/avatar/' . $nomFinal;   
                        if (!move_uploaded_file($file['tmp_name'], $cheminServeur)) {
                            $errors[] = "Erreur lors de l'enregistrement de l'image.";
                        } else {    
                            try {
                                $stmtUpd = $pdo->prepare("UPDATE utilisateur SET photo_profil = ? WHERE id_utilisateur = ?");
                                $stmtUpd->bindParam(1, $cheminBDD, PDO::PARAM_STR);
                                $stmtUpd->bindParam(2, $CURRENT_USER_ID, PDO::PARAM_INT);
                                $stmtUpd->execute();
                                $success = 'Avatar mis à jour avec succès。';
                                $user['photo_profil'] = $cheminBDD;
                            } catch (PDOException $e) {
                                $errors[] = 'Erreur lors de la mise à jour de l\'avatar en base de données。';
                                error_log("Avatar upload DB error: " . $e->getMessage());
                            }
                        }
                    }                        
                }       
            }
        }
    }
}

// Récupérer les 5 dernières annonces du client
if ($userRole === 'client') {
    try{
        $sql = "
        SELECT 
            a.id_annonce,
            a.titre,
            a.description_rapide,
            a.date_depart,
            a.nombre_demenageur,
            vdep.nom_ville AS ville_depart,
            varr.nom_ville AS ville_arrivee
        FROM annonce a
        LEFT JOIN adresse ad_dep ON ad_dep.id_adresse = a.id_adresse_depart
        LEFT JOIN ville vdep ON vdep.id_ville = ad_dep.id_ville
        LEFT JOIN adresse ad_arr ON ad_arr.id_adresse = a.id_adresse_arrive
        LEFT JOIN ville varr ON varr.id_ville = ad_arr.id_ville
        WHERE a.id_client = ?
        ORDER BY a.date_depart DESC
        LIMIT 5
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$CURRENT_USER_ID]);
        $annonces_affichage = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $errors[] = 'Erreur lors de la récupération des annonces。';
        error_log("Load annonces error: " . $e->getMessage());
    }
    try {

        if (!empty($annonces_affichage)) {

            $ids = array_column($annonces_affichage, 'id_annonce');
            $placeholders = implode(",", array_fill(0, count($ids), "?"));

            $sql_props = "
                SELECT 
                    p.id_annonce,
                    p.id_demenageur,
                    p.prix_propose,
                    p.message,
                    p.date_proposition,
                    p.statut,
                    u.nom AS nom_demenageur,
                    u.prenom AS prenom_demenageur,
                    u.photo_profil AS avatar
                FROM proposition p
                JOIN utilisateur u ON u.id_utilisateur = p.id_demenageur
                WHERE p.id_annonce IN ($placeholders)
                ORDER BY p.date_proposition DESC
            ";

            $stmtP = $pdo->prepare($sql_props);
            $stmtP->execute($ids);

            $props = $stmtP->fetchAll(PDO::FETCH_ASSOC);

            foreach ($props as $row) {
                $annonce_id = $row['id_annonce'];

                if (!isset($propositionsByAnnonce[$annonce_id])) {
                    $propositionsByAnnonce[$annonce_id] = [];
                }

                $propositionsByAnnonce[$annonce_id][] = [
                    "prix"     => $row["prix_propose"],
                    "message"          => $row["message"],
                    "date_proposition" => $row["date_proposition"],
                    "statut"           =>$row["statut"],
                    "avatar"           => $row["avatar"] ?: "img/default_avatar.png",
                    "nom_demenageur"   => $row["prenom_demenageur"] . " " . $row["nom_demenageur"],
                    "id_demenageur"    => $row["id_demenageur"],
                ];
            }
        }

    } catch (PDOException $e) {
        $errors[] = 'Erreur lors de la récupération des propositions.';
        error_log("Load propositions error: " . $e->getMessage());
    }

}
//historique des demenagements de client
if ($userRole === 'client') {
    try{
        $sql = "
            SELECT 
                c.id_case,
                c.date_depart,
                c.statut,
                a.titre,
                v_dep.nom_ville AS ville_depart,
                v_arr.nom_ville AS ville_arrive
            FROM `case` c
            JOIN annonce a ON c.id_annonce = a.id_annonce
            JOIN adresse adr_dep ON adr_dep.id_adresse = c.id_adresse_depart
            JOIN ville v_dep ON v_dep.id_ville = adr_dep.id_ville
            JOIN adresse adr_arr ON adr_arr.id_adresse = c.id_adresse_arrive
            JOIN ville v_arr ON v_arr.id_ville = adr_arr.id_ville
            WHERE c.id_client = ?
            AND c.statut IN ('terminee', 'annulee', 'cloturee_admin')
            ORDER BY c.date_depart DESC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$CURRENT_USER_ID]);
        $historique = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $errors[] = 'Erreur lors de la récupération des historiques。';
        error_log("Load historique client error: " . $e->getMessage());
    }
}
//proposition de demenageur
if($userRole ==='demenageur'){
    try{
        $sql = "
            SELECT 
                p.id_annonce,
                p.prix_propose,
                p.date_proposition,
                p.message,
                p.statut,
                a.titre,
                a.nombre_demenageur,
                vdep.nom_ville AS ville_depart,
                varr.nom_ville AS ville_arrive,
                a.date_depart,
                img.chemin_image AS img
            FROM proposition p
            JOIN annonce a ON a.id_annonce = p.id_annonce
            LEFT JOIN adresse adr_dep ON adr_dep.id_adresse = a.id_adresse_depart
            LEFT JOIN ville vdep ON vdep.id_ville = adr_dep.id_ville
            LEFT JOIN adresse adr_arr ON adr_arr.id_adresse = a.id_adresse_arrive
            LEFT JOIN ville varr ON varr.id_ville = adr_arr.id_ville
            LEFT JOIN image_annonce img ON img.id_annonce = a.id_annonce
            WHERE p.id_demenageur = ?
            ORDER BY p.date_proposition DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$CURRENT_USER_ID]);
        $propositions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $errors[] = 'Erreur lors de la récupération des propositions。';
        error_log("Load propositions demenageur error: " . $e->getMessage());
    }
}
//historique des demenagements de demenageur
if ($userRole === 'demenageur') {
    try{
        $sql = "
            SELECT 
                c.id_case,
                c.date_depart,
                c.statut,
                a.titre,
                v_dep.nom_ville AS ville_depart,
                v_arr.nom_ville AS ville_arrive
            FROM participation p
            JOIN `case` c ON p.id_case = c.id_case
            JOIN annonce a ON c.id_annonce = a.id_annonce
            JOIN adresse adr_dep ON adr_dep.id_adresse = c.id_adresse_depart
            JOIN ville v_dep ON v_dep.id_ville = adr_dep.id_ville
            JOIN adresse adr_arr ON adr_arr.id_adresse = c.id_adresse_arrive
            JOIN ville v_arr ON v_arr.id_ville = adr_arr.id_ville
            WHERE p.id_demenageur = ?
            AND c.statut IN ('terminee', 'annulee', 'cloturee_admin')
            ORDER BY c.date_depart DESC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$CURRENT_USER_ID]);
        $historique = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $errors[] = 'Erreur lors de la récupération des historiques。';
        error_log("Load historique demenageur error: " . $e->getMessage());
    }
}    

$my_annonces = [];
$my_propositions = [];
try {
    if ($userRole === 'client') {
        $stmt = $pdo->prepare("SELECT * FROM annonce WHERE id_client = ? ORDER BY date_depart DESC");
        $stmt->execute([$CURRENT_USER_ID]);
        $my_annonces = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($userRole === 'demenageur') {
        $stmt = $pdo->prepare("SELECT p.*, a.titre FROM proposition p JOIN annonce a ON p.id_annonce = a.id_annonce WHERE p.id_demenageur = ? ORDER BY p.date_proposition DESC");
        $stmt->execute([$CURRENT_USER_ID]);
        $my_propositions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log("Load user related data error: " . $e->getMessage());
}

// Charger la liste des villes
$villes = [];
try {
    $stmtV = $pdo->query("SELECT id_ville, nom_ville FROM ville ORDER BY nom_ville");
    $villes = $stmtV->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Load villes error: " . $e->getMessage());
    $villes = [];
}


?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport">
    <title>Tableau de bord</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
    <?php include 'head.php'; ?>
    <main>
        <div class="content-wrapper" style="max-width:800px; margin0 auto; padding:20px;">
            <!-- Titre -->
            <h2 class="section-title" style="text-align:center; margin-top:150px;">Profile</h2>
            <!-- Affichage des messages de succès ou d'erreur -->
            <?php if (!empty($errors)): ?>
                <div class="error-message-box">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <!-- conteneur du profil -->
            <div style="
                display:flex;
                justify-content:space-between;
                align-items:center;
                gap:25px;
                margin-top:40px;
                flex-wrap:wrap;
            ">
                <div style="flex:1; min-width:300px;">
                    <!--affichage d'email-->
                    <div class="form-row" style="margin-bottom:18px;">
                        <div class="form-label">Email</div>
                        <div class="form-info"><?php echo e($user['email']); ?></div>
                    </div>
                    <!--affichage de Nom et prenom-->
                    <div class="form-row" style="margin-bottom:18px;">
                        <div class="form-label">Nom</div>
                        <div class="form-info"><?php echo e($user['nom']); ?></div>
                        <div class="form-label">Prénom</div>
                        <div class="form-info"><?php echo e($user['prenom']); ?></div>
                    </div>
                    <!--affichage de téléphone-->
                    <div class="form-row" style="margin-bottom:18px;">
                        <div class="form-label">Téléphone</div>
                        <div class="form-info"><?php echo e($user['telephone']); ?></div>
                    </div>
                    <!--affichage de sexe et ville-->
                    <div class="form-row" style="margin-bottom:18px;">
                        <div class="form-label">Sexe</div>
                        <div class="form-info">
                            <?php 
                            echo e($user['sexe'] === 'M' ? 'Masculin' :($user['sexe'] === 'F' ? 'Féminin' : 'Autre')); 
                            ?>
                        </div>
                        <div class="form-label">Ville</div>
                        <div class="form-info">
                            <?php
                                $villeName = '';
                                foreach ($villes as $ville) {
                                    if ($ville['id_ville'] == $user['id_ville']) {
                                        $villeName = $ville['nom_ville'];
                                        break;
                                    }
                                }
                                echo e($villeName);
                            ?>
                        </div>
                    </div>
                </div>

                <!-- Affichage + mise a jour de la photo de profil -->
                <div style="flex-shrink:0; text-align:center;">
                    <form id="formAvatar" method="POST" enctype="multipart/form-data" style="display:none;">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="action" value="telecharger_avatar">
                        <input type="file" 
                               name="avatar" 
                               id="inputAvatar" 
                               accept="image/*" 
                               style="display:none;" 
                               onchange="document.getElementById('formAvatar').submit();">
                    </form>
                    <?php if(!empty($user['photo_profil'])): ?>
                        <img src="<?php echo htmlspecialchars($user['photo_profil']); ?>" 
                             alt="Avatar" 
                             style="width:150px; height:150px; object-fit:cover; border-radius:50%; border:2px solid #ccc;"
                             onclick="document.getElementById('inputAvatar').click();">
                    <?php else: ?>
                        <div 
                            style="
                                width:150px;
                                height:150px;
                                border-radius:50%;
                                background-color:#6c87c4;
                                cursor:pointer;"
                            onclick="document.getElementById('inputAvatar').click();">
                        </div>
                    <?php endif; ?>
                    <p style="font-size:14px; color:#666; margin-top:8px;">Cliquez sur l'image pour changer</p>
                </div>
                <div style="
                    margin-top:30px; 
                    display:flex; 
                    justify-content:space-between; 
                    align-items:center; 
                    width:100%;">
                    <button 
                        onclick="window.location.href='modifier_profile.php'" 
                        class="btn-primary" 
                        style="
                            font-size:18px;
                            width:auto; 
                            padding:12px 30px;
                            border:none;
                        ">
                        modifier le profil
                    </button>
                    <button 
                        onclick="window.location.href='logout.php'" 
                        class="btn-primary" 
                        style="
                            background-color:#d9534f;                                 
                            width:auto; 
                            padding:12px 30px;
                            font-size:18px;
                            border:none;
                        ">
                        Déconnexion
                    </button> 
                </div>
            </div>

            <div class="separator"></div>

            <?php if ($userRole === 'client'): ?>
            <!-- -->
                <!-- Section de mes annonces -->
                <h2 class="section-title" style="text-align:center; margin-top:50px;">Mes annonces</h2>
                <div style="margin-top:25px">
                <?php if (empty($annonces_affichage)): ?>
                    <p style="text-align:center; color:#666;">Vous n'avez pas encore publié d'annonce.</p>
                <?php else: ?>
                    <?php foreach ($annonces_affichage as $a): ?>
                        <div class="annonce-accordion-header"
                            onclick="toggleAnnonceAccordion(<?php echo $a['id_annonce']; ?>)">
                            <div class="annonce-accordion-image">
                                <?php if (!empty($a['img'])): ?>
                                    <img src="<?php echo e($a['img']); ?>">
                                <?php else: ?>
                                    <div class="annonce-accordion-placeholder"></div>
                                <?php endif; ?>
                            </div>
                                <div class="annonce-accordion-info">
                                    <div class="annonce-accordion-title">
                                        <?php echo e($a['titre']); ?>
                                    </div>

                                    <div class="annonce-accordion-tags">
                                        <span class="annonce-tag"><?php echo e($a['date_depart']); ?></span>
                                        <span class="annonce-tag"><?php echo e($a['ville_depart']); ?></span>
                                        <span class="annonce-tag"><?php echo e($a['ville_arrivee']); ?></span>
                                        <span class="annonce-tag"><?php echo e($a['nombre_demenageur']); ?> pers</span>
                                    </div>

                                    <div style="margin-top:6px; color:#666;">
                                        <?php echo e($a['description_rapide']); ?>
                                    </div>
                                </div>

                            <div id="arrow-<?php echo $a['id_annonce']; ?>" 
                                class="annonce-accordion-arrow">▼</div>
                        </div>
                        <div id="annonce-body-<?php echo $a['id_annonce']; ?>" 
                            class="annonce-accordion-body">

                            <h3 style="margin-bottom:15px;">Offres reçues</h3>

                            <?php if (!empty($propositionsByAnnonce[$a['id_annonce']])): ?>
                                <?php foreach ($propositionsByAnnonce[$a['id_annonce']] as $p): ?>
                                    <div class="prop-card-client">
                                        <div class="prop-card-client-left">
                                            <div class="prop-card-client-avatar">
                                                <img src="<?php echo htmlspecialchars($p['avatar']); ?>">
                                            </div>
                                            <div class="prop-card-client-name">
                                                <?php echo htmlspecialchars($p['nom_demenageur']); ?>
                                            </div>
                                        </div>

                                        <div class="prop-card-client-middle">
                                            <div class="prop-card-client-price">
                                                prix proposé : <?php echo number_format($p['prix'], 2); ?> €
                                            </div>
                                            <div class="prop-card-client-message">
                                                <?php echo nl2br(htmlspecialchars($p['message'])); ?>
                                            </div>
                                        </div>

                                        <div class="prop-card-client-right">

                                        <?php if ($p['statut'] === 'accepte'): ?>
                                            <span style="color:green; font-weight:bold;">✔ Accepté</span>
                                        <?php elseif ($p['statut'] === 'refuse'): ?>
                                            <span style="color:#d33; font-weight:bold;">✖ Refusé</span>
                                        <?php else: ?>
                                            <form action="client_action/proposition_action/accept.php" method="POST" style="display:inline;">
                                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                <input type="hidden" name="id_annonce" value="<?php echo $a['id_annonce']; ?>">
                                                <input type="hidden" name="id_demenageur" value="<?php echo $p['id_demenageur']; ?>">
                                                <button class="prop-card-client-btn prop-card-client-accept">Accepter</button>
                                            </form>

                                            <form action="client_action/proposition_action/refuse.php" method="POST" style="display:inline;">
                                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                <input type="hidden" name="id_annonce" value="<?php echo $a['id_annonce']; ?>">
                                                <input type="hidden" name="id_demenageur" value="<?php echo $p['id_demenageur']; ?>">
                                                <button class="prop-card-client-btn prop-card-client-refuse">Refuser</button>
                                            </form>
                                        <?php endif; ?>
                                        </div>

                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p style="color:#666;">Aucune proposition reçue。</p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <div class="separator"></div>

                <!--section des historiques-->
                <h2 class="section-title" style="text-align:center; margin-top:50px;">Déménagements Historiques</h2>
                <div style="margin-top:25px">
                <?php if (empty($historique)): ?>
                    <p style="text-align:center; color:#666;">Aucun déménagement historique trouvé.</p> 
                <?php else: ?>
                    <?php foreach ($historique as $h): ?>
                        <div class="historique-item">
                            <div class="hist-main">
                                <div class="hist-ville">
                                    <?= htmlspecialchars($h['ville_depart'] . " → " . $h['ville_arrive']) ?>
                                </div>
                                <div class="hist-titre">
                                    <?= htmlspecialchars($h['titre']) ?>
                                </div>
                            </div>
                            <div class="hist-side">
                                <div class="hist-date">
                                    <?= htmlspecialchars($h['date_depart']) ?>
                                </div>
                                <div class="hist-statut">
                                    <?= htmlspecialchars(ucfirst($h['statut'])) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endif; ?>
            <?php if ($userRole === 'demenageur'): ?>
                <!--section de mes propositions -->
                <h2 class="section-title" style="text-align:center; margin-top:50px;">Mes propositions</h2>
                <div style="margin-top:25px">
                <?php if (empty($propositions)): ?>
                    <p style="text-align:center; color:#666;">Vous n'avez pas encore fait de propositions.</p>
                <?php else: ?>
                    <?php foreach ($propositions as $p): ?>
                        <div class="prop-item">
                            <div class="prop-img">
                                <img src="<?php echo htmlspecialchars($p['img'] ?: 'img/default_annonce.png'); ?>">
                            </div>
                            <div class="prop-main">
                                <div class="prop-title">
                                    <?php echo htmlspecialchars($p['titre']); ?>
                                </div>

                                <div class="prop-row">
                                    <div class="prop-badge"><?php echo htmlspecialchars($p['ville_depart']); ?></div>
                                    <div class="prop-badge"><?php echo htmlspecialchars($p['ville_arrive']); ?></div>
                                </div>

                                <div class="prop-row">
                                    <div class="prop-badge"><?php echo htmlspecialchars($p['date_depart']); ?></div>
                                    <div class="prop-badge"><?php echo htmlspecialchars($p['nombre_demenageur']); ?> pers requises</div>
                                </div>
                            </div>
                            <div class="prop-side">
                                <div class="prop-top">
                                    <div class="prop-price">
                                        prix propose :
                                        <strong><?php echo number_format($p['prix_propose'], 2); ?> €</strong>
                                    </div>
                                    <button class="prop-modifier"
                                            onclick="openModal(
                                                <?= $p['id_annonce'] ?>,
                                                <?= $p['prix_propose'] ?>,
                                                `<?= htmlspecialchars($p['message']) ?>`
                                            )">
                                        modifier
                                    </button>
                                </div>
                                <div class="prop-message">
                                    <?php echo nl2br(htmlspecialchars($p['message'])); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                <!--section des historiques-->
                <h2 class="section-title" style="text-align:center; margin-top:50px;">Déménagements Historiques</h2>
                <div style="margin-top:25px">
                <?php if (empty($historique)): ?>
                    <p style="text-align:center; color:#666;">Aucun déménagement historique trouvé.</p> 
                <?php else: ?>
                    <?php foreach ($historique as $h): ?>
                        <div class="historique-item">
                            <div class="hist-main">
                                <div class="hist-ville">
                                    <?php echo htmlspecialchars($h['ville_depart'] . " → " . $h['ville_arrive']); ?>
                                </div>
                                <div class="hist-titre">
                                    <?php echo htmlspecialchars($h['titre']); ?>
                                </div>
                            </div>
                            <div class="hist-side">
                                <div class="hist-date">
                                    <?php echo htmlspecialchars($h['date_depart']); ?>
                                </div>
                                <div class="hist-statut">
                                    <?php echo htmlspecialchars(ucfirst($h['statut'])); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <!-- Modal background -->
        <div id="editModal" class="modal-overlay">
            <div class="modal-box">

                <h2 class="modal-title">Modifier votre proposition</h2>

                <form id="editForm">

                    <input type="hidden" name="id_annonce" id="modal_id_annonce">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                    <label>Prix proposé (€) :</label>
                    <input type="number" step="0.01" id="modal_prix" name="prix_propose" required>

                    <label>Message :</label>
                    <textarea id="modal_message" name="message" rows="5" required></textarea>

                    <div class="modal-buttons">
                        <button type="submit" class="btn-submit">Enregistrer</button>
                        <button type="button" class="btn-cancel" onclick="closeModal()">Annuler</button>
                    </div>
                </form>

            </div>
        </div>
    </main>
<script>
function toggleAnnonceAccordion(id) {
    const body = document.getElementById("annonce-body-" + id);
    const arrow = document.getElementById("arrow-" + id);

    if (body.style.display === "block") {
        body.style.display = "none";
        arrow.textContent = "▼";
    } else {
        body.style.display = "block";
        arrow.textContent = "▲";
    }
}
function openModal(id_annonce, prix, message) {
    document.getElementById("modal_id_annonce").value = id_annonce;
    document.getElementById("modal_prix").value = prix;
    document.getElementById("modal_message").value = message;

    document.getElementById("editModal").style.display = "flex";
}

function closeModal() {
    document.getElementById("editModal").style.display = "none";
}

// AJAX submit
document.getElementById("editForm").addEventListener("submit", function(e){
    e.preventDefault();

    const formData = new FormData(this);

    fetch("demenageur_action/proposition_update.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.text())
    .then(result => {
        closeModal();
        location.reload(); // 刷新卡片
    })
    .catch(err => alert("Erreur AJAX"));
});
</script>    
</body>
</html>
