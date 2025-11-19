<?php
session_start();
require_once 'connexion.inc.php';
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
function e($s) {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
if (empty($_SESSION['register_email']) || empty($_SESSION['register_password'])) {
    header('Location: inscription_1.php');
    exit;
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = isset($_POST['nom']) ? trim($_POST['nom']) : '';
    $prenom = isset($_POST['prenom']) ? trim($_POST['prenom']) : '';
    $sexe = isset($_POST['sexe']) ? $_POST['sexe'] : '';
    $telephone = isset($_POST['telephone']) ? trim($_POST['telephone']) : '';
    $id_ville = isset($_POST['ville']) ? $_POST['ville'] : '';
    $possede_voiture = isset($_POST['possede_voiture']) ? $_POST['possede_voiture'] : '';

    if ($nom === '') {
        $errors[] = 'Le nom est requis.';
    }
    if ($prenom === '') {
        $errors[] = 'Le prénom est requis.';
    }
    if ($sexe !== 'M' && $sexe !== 'F') {
        $errors[] = 'Le sexe est invalide.';
    }
    if ($telephone === '') {
        $errors[] = 'Le téléphone est requis.';
    }
    if ($id_ville === '' || !ctype_digit($id_ville)) {
        $errors[] = 'La ville est invalide.';
    }
    if ($possede_voiture !== 'oui' && $possede_voiture !== 'non') {
        $errors[] = 'L\'information sur la possession de voiture est invalide.';
    }

    if (empty($errors)) {
        try {

            $password_hash = $_SESSION['register_password'];
            $pdo->beginTransaction();
            $sql = "INSERT INTO utilisateur (nom, prenom, email, mot_de_passe, telephone, sexe, id_ville, etat_compte)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 1)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $nom,
                $prenom,
                $_SESSION['register_email'],
                $password_hash,
                $telephone,
                $sexe,
                $id_ville
            ]);
            $id_user = $pdo->lastInsertId();
            $sql2 = "INSERT INTO demenageur (id_demenageur, possede_voiture)
                     VALUES (?, ?)";
            $stmt2 = $pdo->prepare($sql2);
            $stmt2->execute([
                $id_user,
                ($possede_voiture === 'oui' ? 1 : 0)
            ]);
            $pdo->commit();
            $success = 'Inscription réussie ! Vous pouvez maintenant vous connecter.';
            header('Location: connexion.php');
            exit;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = 'Erreur serveur. Veuillez réessayer plus tard.';
            error_log('Register Step 2 DB error: ' . $e->getMessage());
        }
    }
}
$villes = $pdo->query("SELECT id_ville, nom_ville, code_postal FROM ville ORDER BY nom_ville")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Inscription - demenageur</title>
    <link rel="stylesheet" href="css/style.css">
    <meta name="viewport">
</head>
<body>
    <?php include 'head.php';?>
    <main>
        <div class="content-wrapper" style="
            max-width:600px; 
            margin:0 auto; 
            padding:20px;
        ">
            <h2 class="section-title" style="
                margin-top:200px;
                text-align:center;
            ">
                Informations personnelles
            </h2>
            <?php if (!empty($errors)): ?>
                <div style="
                    background:#ffdddd; 
                    padding:12px; 
                    border-radius:12px; 
                    margin-bottom:25px; 
                    text-align:left;">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <form method="POST" action="inscription_2_demenageur.php" style="
                display:flex; 
                flex-direction:column;
                gap:20px;
            ">
                 <input 
                    type="hidden" 
                    name="csrf_token" 
                    value="<?php echo e($csrf_token); ?>"
                >
                
                <!-- Nom Field -->
                <div class="form-row">
                    <div class="form-label">
                            Nom
                    </div>
                    <input 
                        type="text" 
                        name="nom" 
                        placeholder="Entrez votre Nom" 
                        class="input-field"                                                             
                    >
                </div>
                
                <!-- Prénom Field -->
                <div class="form-row">
                    <div class="form-label">
                            Prénom
                    </div>
                    <input 
                        type="text" 
                        name="prenom" 
                        placeholder="Entrez votre Prénom" 
                        class="input-field"
                    >
                </div>

                <!-- Sexe Field -->
                <div class="form-row">
                    <div class="form-label">
                            Sexe
                    </div>
                    <div style="flex-grow:1; 
                        display:flex;
                        align-items:center;
                        gap:25px;
                        padding:12px;
                    ">
                        <label style="display:flex; align-items:center; gap:8px;cursor:pointer;">
                            <input type="radio" name="sexe" value="M" required
                            style="width:18px; height:18px; cursor:pointer;">
                            <span> M </span>
                        </label>
                        <label style="display:flex; align-items:center; gap:8px;cursor:pointer;">
                            <input type="radio" name="sexe" value="F" required
                                style="width:18px; height:18px; cursor:pointer;">
                            <span> F </span>
                        </label>
                    </div>
                </div>

                <!-- Téléphone Field -->
                <div class="form-row">
                    <div class="form-label">
                            Téléphone
                    </div>
                    <input
                        type="text" 
                        name="telephone" 
                        placeholder="Entrez votre numéro de téléphone" 
                        required
                        maxlength="10"
                        pattern="[0-9]{10}"
                        class="input-field"
                        oninput="this.value = this.value.replace(/[^0-9]/g, '');"
                    >
                </div>

                <!-- Ville Field -->
                <div class="form-row">
                    <div class="form-label">
                            Ville
                    </div>
                    <div style="
                        flex-grow:1;
                        position:relative;
                    ">
                        <input
                            type="text"
                            id="ville-search"
                            placeholder="Rechercher une ville. un code postal..."
                            autocomplete="off"
                            class="input-field"
                            style="width:100%;"
                        >
                        <div id="villeList" class="ville-list"></div>
                        <input type="hidden" name="ville" id="idVille">
                    </div>
                </div>

                <!-- Possède une voiture Field -->
                <div class="form-row">
                    <div class="form-label">
                            Possédez-vous une voiture ?
                    </div>
                    <div style="flex-grow:1; 
                        display:flex;
                        align-items:center;
                        gap:25px;
                        padding:12px;
                    ">
                        <label style="display:flex; align-items:center; gap:8px;cursor:pointer;">
                            <input type="radio" name="possede_voiture" value="oui" required
                            style="width:18px; height:18px; cursor:pointer;">
                            <span> Oui </span>
                        </label>
                        <label style="display:flex; align-items:center; gap:8px;cursor:pointer;">
                            <input type="radio" name="possede_voiture" value="non" required
                                style="width:18px; height:18px; cursor:pointer;">
                            <span> Non </span>
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn-primary">
                    S'inscrire
                </button>
            </form>
        </div>
    </main>
<script>
    var villes = [
        <?php foreach ($villes as $v): ?>
        {
            id: <?php echo json_encode($v['id_ville']); ?>,
            name: <?php echo json_encode($v['nom_ville']); ?>,
            postal: <?php echo json_encode($v['code_postal']); ?>
        },
        <?php endforeach; ?>
    ];

    var villeInput = document.getElementById('ville-search');
    var villeList = document.getElementById('villeList');
    var idVilleInput = document.getElementById('idVille');

    villeInput.addEventListener("input", function () {
        var text = this.value.toLowerCase();
        villeList.innerHTML = "";

        if (text.length === 0) {
            villeList.style.display = "none";
            idVilleInput.value = "";
            return;
        }

        var matches = villes.filter(function (v) {
            return (
                v.name.toLowerCase().includes(text) ||
                v.postal.toString().includes(text)
            );
        });

        matches.forEach(function (v) {
            var item = document.createElement("div");
            item.className = "ville-item";
            item.textContent = v.name + " (" + v.postal + ")";
            item.onmouseover = function () { this.style.background = "#eef"; };
            item.onmouseout  = function () { this.style.background = "white"; };

            item.onclick = function () {
                villeInput.value = v.name;
                idVilleInput.value = v.id;
                villeList.style.display = "none";
            };

            villeList.appendChild(item);
        });

        villeList.style.display = matches.length ? "block" : "none";
    });

    document.addEventListener("click", function (e) {
        if (!villeList.contains(e.target) && e.target !== villeInput) {
            villeList.style.display = "none";
        }
    });
</script>


<?php include 'footer.php';?>
</body>
</html>
    

