<?php
session_start();

// Connexion à la base de données
$conn = new mysqli("localhost", "root", "", "cryptage_db");
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

$message = '';
$error = '';
$current_user_id = $_SESSION["user_id"];

// Ajouter un nouvel utilisateur
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_user"])) {
    $new_username = $_POST["new_username"];
    $new_email = $_POST["new_email"];
    $new_password = $_POST["new_password"];
    $new_role = $_POST["new_role"];

    if (empty($new_username) || empty($new_email) || empty($new_password) || empty($new_role)) {
        $error = "Veuillez remplir tous les champs.";
    } else {
        // Insérer l'utilisateur dans la base de données
        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
        $sql = "INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $new_username, $hashed_password, $new_email, $new_role);

        if ($stmt->execute()) {
            $message = "Utilisateur ajouté avec succès.";

            // Envoyer un e-mail de bienvenue
            $to = $new_email;
            $subject = "Bienvenue dans notre plateforme";
            $body = "Bonjour $new_username,\n\nVotre compte a été créé avec succès.\nVotre rôle : $new_role.\n\nCordialement,\nEquipe Admin.";
            $headers = "From: admin@example.com\r\n";
            $headers .= "Reply-To: admin@example.com\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

            if (mail($to, $subject, $body, $headers)) {
                $message .= " Un e-mail a été envoyé à $new_email.";
            } else {
                $error = "Erreur lors de l'envoi de l'e-mail.";
            }
        } else {
            $error = "Erreur : " . $conn->error;
        }

        $stmt->close();
    }
}

// Supprimer un utilisateur uniquement si son rôle est "user"
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_user"])) {
    $user_id_to_delete = $_POST["user_id"];

    $sql = "DELETE FROM users WHERE id = ? AND role = 'user'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id_to_delete);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $message = "Utilisateur supprimé avec succès.";
    } else {
        $error = "Erreur : Impossible de supprimer cet utilisateur.";
    }

    $stmt->close();
}

// Récupérer la liste des utilisateurs ayant le rôle "user"
$sql = "SELECT id, username, email FROM users WHERE role = 'user'";
$result = $conn->query($sql);

$conn->close();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panneau d'administration</title>
    <link rel="stylesheet" href="./styles/style.css">
</head>
<body>
    <div class="container">
        <h2>Panneau d'administration</h2>

        <!-- Messages de succès ou d'erreur -->
        <?php if ($message): ?><p class="success"><?php echo $message; ?></p><?php endif; ?>
        <?php if ($error): ?><p class="error"><?php echo $error; ?></p><?php endif; ?>

        <!-- Formulaire pour ajouter un utilisateur -->
        <form method="POST" action="admin.php">
            <h3>Ajouter un utilisateur</h3>
            <input type="text" name="new_username" placeholder="Nom d'utilisateur" required>
            <input type="email" name="new_email" placeholder="Email" required>
            <input type="password" name="new_password" placeholder="Mot de passe" required>
            <select name="new_role" required>
                <option value="user">Utilisateur</option>
            </select>
            <button type="submit" name="add_user">Ajouter</button>
        </form>

        <!-- Liste des utilisateurs -->
        <h3>Liste des utilisateurs (rôle : Utilisateur)</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nom d'utilisateur</th>
                    <th>Email</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row["id"]; ?></td>
                            <td><?php echo $row["username"]; ?></td>
                            <td><?php echo $row["email"]; ?></td>
                            <td>
                                <!-- Bouton pour supprimer un utilisateur -->
                                <form method="POST" action="admin.php" style="display:inline;">
                                    <input type="hidden" name="user_id" value="<?php echo $row["id"]; ?>">
                                    <button type="submit" name="delete_user" onclick="return confirm('Voulez-vous vraiment supprimer cet utilisateur ?');">Supprimer</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4">Aucun utilisateur trouvé.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Bouton de déconnexion -->
        <form method="POST" action="logout.php">
            <button type="submit">Déconnexion</button>
        </form>
    </div>
</body>
</html>
