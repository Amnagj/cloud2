<?php
session_start();

// Vérifier si l'utilisateur est connecté et est un super utilisateur
// Ajouter ici votre logique de vérification du rôle de super utilisateur si nécessaire

// Connexion à la base de données
$conn = new mysqli("localhost", "root", "", "cryptage_db");
if ($conn->connect_error) {
    die("Erreur de connexion à la base de données : " . $conn->connect_error);
}

// Variables pour les messages
$message = '';
$error = '';

// Ajouter un administrateur
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_admin"])) {
    $new_username = $_POST["new_username"];
    $new_password = $_POST["new_password"];

    if (empty($new_username) || empty($new_password)) {
        $error = "Veuillez remplir tous les champs.";
    } else {
        // Hachage du mot de passe
        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

        // Insérer un nouvel administrateur
        $sql = "INSERT INTO users (username, password, role) VALUES (?, ?, 'admin')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $new_username, $hashed_password);

        if ($stmt->execute()) {
            $message = "Administrateur ajouté avec succès.";
        } else {
            $error = "Erreur lors de l'ajout de l'administrateur : " . $conn->error;
        }

        $stmt->close();
    }
}

// Ajouter une clé dans le Key Vault
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_key"])) {
    $key_name = $_POST["key_name"];
    $secret_value = $_POST["secret_value"];

    if (empty($key_name) || empty($secret_value)) {
        $error = "Veuillez remplir tous les champs pour ajouter une clé.";
    } else {
        // Insérer la clé dans le Key Vault sans chiffrement
        $sql = "INSERT INTO key_vault (key_name, secret_value) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $key_name, $secret_value);

        if ($stmt->execute()) {
            $message = "Clé ajoutée avec succès au Key Vault.";
        } else {
            $error = "Erreur lors de l'ajout de la clé : " . $conn->error;
        }

        $stmt->close();
    }
}

// Afficher toutes les clés
$keys = [];
$sql = "SELECT key_name, secret_value FROM key_vault";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $keys[] = [
            'key_name' => $row["key_name"],
            'secret_value' => $row["secret_value"]
        ];
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super User Dashboard</title>
    <link rel="stylesheet" href="styles/style.css">
</head>
<body>
    <div class="container">
        <h2>Bienvenue, Super User <?php echo htmlspecialchars($_SESSION["username"]); ?>!</h2>
        <p>Vous avez un accès privilégié pour gérer les administrateurs et les clés.</p>

        <!-- Affichage des messages -->
        <?php if ($message): ?><p class="success"><?php echo $message; ?></p><?php endif; ?>
        <?php if ($error): ?><p class="error"><?php echo $error; ?></p><?php endif; ?>

        <!-- Formulaire pour ajouter un administrateur -->
        <form method="POST" action="superuser.php">
            <h3>Ajouter un administrateur</h3>
            <input type="text" name="new_username" placeholder="Nom d'utilisateur" required>
            <input type="password" name="new_password" placeholder="Mot de passe" required>
            <button type="submit" name="add_admin">Ajouter l'administrateur</button>
        </form>

        <!-- Formulaire pour ajouter une clé -->
        <form method="POST" action="superuser.php">
            <h3>Ajouter une clé dans le Key Vault</h3>
            <input type="text" name="key_name" placeholder="Nom de la clé" required>
            <input type="text" name="secret_value" placeholder="Valeur de la clé" required>
            <button type="submit" name="add_key">Ajouter la clé</button>
        </form>

        <!-- Affichage des clés enregistrées -->
        <h3>Clés dans le Key Vault</h3>
        <?php if (count($keys) > 0): ?>
            <ul>
                <?php foreach ($keys as $key): ?>
                    <li><strong><?php echo htmlspecialchars($key['key_name']); ?>:</strong> <?php echo htmlspecialchars($key['secret_value']); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>Aucune clé dans le Key Vault.</p>
        <?php endif; ?>

        <!-- Bouton de déconnexion -->
        <form method="POST" action="logout.php">
            <button type="submit">Déconnexion</button>
        </form>
    </div>
</body>
</html>
