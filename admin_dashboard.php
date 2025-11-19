<?php
// admin_dashboard.php
// Inclusion de la connexion BDD et protection d'accès
include 'db.php'; 
// (Optionnel) Ici, il faudrait inclure un fichier admin_auth_check.php pour sécuriser la page

// Récupération du module (par défaut: gestion des annonces)
$module = $_GET['module'] ?? 'ads'; 

// Déclaration des variables de données
$data = [];
$title = "";

try {
    // --- Logique de récupération des données ---
    if ($module === 'ads') {
        $title = "Gestion des Annonces";
        // Requête complexe pour obtenir les noms de ville et le statut des annonces
        $sql_ads = "
            SELECT a.id_annonce, a.titre, a.date_depart, a.statut, a.nombre_demenageur,
                   v_dep.nom_ville AS depart, 
                   v_arr.nom_ville AS arrivee
            FROM annonce a
            JOIN adresse adr_dep ON a.id_adresse_depart = adr_dep.id_adresse
            JOIN ville v_dep ON adr_dep.id_ville = v_dep.id_ville
            JOIN adresse adr_arr ON a.id_adresse_arrive = adr_arr.id_adresse
            JOIN ville v_arr ON adr_arr.id_ville = v_arr.id_ville
            ORDER BY a.date_depart DESC";
        $stmt = $pdo->query($sql_ads);
        $data = $stmt->fetchAll();

    } elseif ($module === 'users') {
        $title = "Gestion des Comptes et Utilisateurs";
        // Requête pour obtenir tous les utilisateurs et identifier leur rôle (Client, Déménageur)
        $sql_users = "
            SELECT u.id_utilisateur, u.nom, u.prenom, u.email, u.etat_compte,
                   c.id_client IS NOT NULL AS is_client, 
                   d.id_demenageur IS NOT NULL AS is_demenageur
            FROM utilisateur u
            LEFT JOIN client c ON u.id_utilisateur = c.id_client
            LEFT JOIN demenageur d ON u.id_utilisateur = d.id_demenageur
            ORDER BY u.nom ASC";
        $stmt = $pdo->query($sql_users);
        $data = $stmt->fetchAll();

    } elseif ($module === 'orders') {
        $title = "Gestion des Transactions et Historique";
        // Requête pour obtenir les transactions/cases (simplifiée)
        $sql_orders = "
            SELECT id_case, id_client, date_depart, statut
            FROM `case` 
            ORDER BY date_depart DESC";
        $stmt = $pdo->query($sql_orders);
        $data = $stmt->fetchAll();
    }
} catch (\PDOException $e) {
    // Gestion des erreurs BDD
    $title = "Erreur de connexion";
    $data = [];
    $error_message = "Impossible de charger les données : " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Administration - <?php echo htmlspecialchars($title); ?></title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .admin-table th, .admin-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        .admin-table th {
            background-color: #f2f2f2;
        }
        .status-publie { background-color: #d4edda; color: #155724; }
        .status-attente { background-color: #fff3cd; color: #856404; }
        .status-termine { background-color: #cce5ff; color: #004085; }
    </style>
</head>
<body>
    
    <?php include 'head.php'; // En-tête public réutilisé ?>

    <main class="content-wrapper admin-container"> 
        
        <nav class="admin-sidebar">
            <h3>Menu d'Administration</h3>
            <ul class="sidebar-menu">
                <li><a href="admin_dashboard.php?module=ads" class="<?php echo $module === 'ads' ? 'active' : ''; ?>">📦 Gestion des Annonces</a></li>
                <li><a href="admin_dashboard.php?module=users" class="<?php echo $module === 'users' ? 'active' : ''; ?>">👤 Comptes et Utilisateurs</a></li>
                <li><a href="admin_dashboard.php?module=orders" class="<?php echo $module === 'orders' ? 'active' : ''; ?>">💰 Transactions et Historique</a></li>
            </ul>
        </nav>

        <div class="admin-main-content">
            <h2 style="color:<?php echo $module === 'ads' ? '#e28c3f' : '#4a6aa0'; ?>;"><?php echo htmlspecialchars($title); ?></h2>
            
            <?php if (isset($error_message)): ?>
                <p style="color:red;"><?php echo $error_message; ?></p>
            <?php elseif (empty($data)): ?>
                <p>Aucune donnée trouvée pour ce module. Votre base de données est vide ou la requête a échoué.</p>
            <?php else: ?>

                <table class="admin-table">
                    <thead>
                        <tr>
                            <?php 
                            $headers = array_keys($data[0]);
                            foreach ($headers as $header): ?>
                                <th><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $header))); ?></th>
                            <?php endforeach; ?>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data as $row): ?>
                            <tr>
                                <?php foreach ($row as $key => $value): ?>
                                    <td class="<?php if ($key === 'statut') echo 'status-' . strtolower($value); ?>">
                                        <?php echo htmlspecialchars($value); ?>
                                    </td>
                                <?php endforeach; ?>
                                <td>
                                    <a href="#">Éditer</a> | <a href="#">Supprimer</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
    </main>
</body>
</html>