<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 如果已登录
$logged_in = isset($_SESSION['user_id']);

// 获取头像路径
$avatar = 'img/avatar/default_avatar.png';  

if ($logged_in) {
    require_once 'connexion.inc.php';
    $stmt = $pdo->prepare("SELECT photo_profil FROM utilisateur WHERE id_utilisateur = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && !empty($row['photo_profil'])) {
        $avatar = $row['photo_profil']; 
    }
}
?>

<header class="navbar">
    <div class="logo">
        <a href="index.php">
        <img src="img/logo.png" alt="Logo" class="logo-img">
    </a></div>
    
    <nav>
        <ul class="nav-links">
            <li><a href="index.php">Accueil</a></li>
            <li><a href="ads-list.php">Liste des annonces</a></li>
            <li><a href="inscription_1.php?role=demenageur">Inscription déménageur</a></li>
            <li><a href="publish-ad.php">Publier une annonce</a></li>
            <?php if (!$logged_in): ?>
                <li><a href="Connexion.php">Connexion</a></li>
            <?php endif; ?>
        </ul>
    </nav>
    <!-- 右侧头像区域 -->
    <div class="nav-user">
        <?php if ($logged_in): ?>
            <a href="dashboard.php" class="nav-avatar-link">
                <img src="<?php echo htmlspecialchars($avatar); ?>" class="nav-avatar">
            </a>
        <?php endif; ?>
    </div>
</header>