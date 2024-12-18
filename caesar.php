<?php
session_start();

// Connexion à la base de données
$conn = new mysqli("localhost", "root", "", "cryptage_db");
if ($conn->connect_error) {
    die("Erreur de connexion à la base de données : " . $conn->connect_error);
}

// Fonction pour récupérer la clé de cryptage depuis la base de données
function getEncryptionKey($conn) {
    $sql = "SELECT secret_value FROM key_vault WHERE key_name = 'cesar_key' LIMIT 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row["secret_value"];
    }
    return null;
}

// Fonction pour chiffrer le texte avec l'algorithme de César
function encryptText($plaintext, $encryptionKey) {
    $shift = intval($encryptionKey);
    $encryptedText = "";

    for ($i = 0; $i < strlen($plaintext); $i++) {
        $char = $plaintext[$i];

        if (ctype_upper($char)) {
            $encryptedText .= chr((ord($char) + $shift - 65) % 26 + 65);
        } elseif (ctype_lower($char)) {
            $encryptedText .= chr((ord($char) + $shift - 97) % 26 + 97);
        } else {
            $encryptedText .= $char;
        }
    }

    return $encryptedText;
}

// Fonction pour décrypter le texte avec l'algorithme de César
function decryptText($ciphertext, $encryptionKey) {
    $shift = intval($encryptionKey);
    $decryptedText = "";

    for ($i = 0; $i < strlen($ciphertext); $i++) {
        $char = $ciphertext[$i];

        if (ctype_upper($char)) {
            $decryptedText .= chr((ord($char) - $shift - 65 + 26) % 26 + 65);
        } elseif (ctype_lower($char)) {
            $decryptedText .= chr((ord($char) - $shift - 97 + 26) % 26 + 97);
        } else {
            $decryptedText .= $char;
        }
    }

    return $decryptedText;
}

$message = '';
$error = '';
$encrypted_text = '';
$decrypted_text = '';

// Ajouter un texte à crypter
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST["encrypt_text"])) {
        $text_to_encrypt = $_POST["text_to_encrypt"];

        if (empty($text_to_encrypt)) {
            $error = "Veuillez entrer un texte à crypter.";
        } else {
            $encryptionKey = getEncryptionKey($conn);

            if ($encryptionKey === null) {
                $error = "Clé de cryptage introuvable dans la base de données.";
            } else {
                $encrypted_text = encryptText($text_to_encrypt, $encryptionKey);
                $message = "Le texte a été crypté avec succès.";
            }
        }
    }

    if (isset($_POST["decrypt_text"])) {
        $text_to_decrypt = $_POST["text_to_decrypt"];

        if (empty($text_to_decrypt)) {
            $error = "Veuillez entrer un texte à décrypter.";
        } else {
            $encryptionKey = getEncryptionKey($conn);

            if ($encryptionKey === null) {
                $error = "Clé de cryptage introuvable dans la base de données.";
            } else {
                $decrypted_text = decryptText($text_to_decrypt, $encryptionKey);
                $message = "Le texte a été décrypté avec succès.";
            }
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Caesar Encryption</title>
    <link rel="stylesheet" href="./styles/style.css">
</head>
<body>
    <div class="container">
        <h2>Bienvenue, <?php echo htmlspecialchars($_SESSION["username"]); ?>!</h2>
        <p>Cryptage et Décryptage</p>

        <!-- Affichage des messages -->
        <?php if ($message): ?><p class="success"><?php echo $message; ?></p><?php endif; ?>
        <?php if ($error): ?><p class="error"><?php echo $error; ?></p><?php endif; ?>

        <!-- Formulaire pour entrer un texte à crypter -->
        <form method="POST" action="caesar.php">
            <textarea name="text_to_encrypt" placeholder="Entrez le texte à crypter" required></textarea>
            <button type="submit" name="encrypt_text">Crypter</button>
        </form>

        <!-- Affichage du texte crypté -->
        <?php if ($encrypted_text): ?>
            <h3>Texte crypté :</h3>
            <p><?php echo htmlspecialchars($encrypted_text); ?></p>
        <?php endif; ?>

        <!-- Formulaire pour entrer un texte à décrypter -->
        <form method="POST" action="caesar.php">
            <textarea name="text_to_decrypt" placeholder="Entrez le texte à décrypter" required></textarea>
            <button type="submit" name="decrypt_text">Décrypter</button>
        </form>

        <!-- Affichage du texte décrypté -->
        <?php if ($decrypted_text): ?>
            <h3>Texte décrypté :</h3>
            <p><?php echo htmlspecialchars($decrypted_text); ?></p>
        <?php endif; ?>

        <!-- Bouton de déconnexion -->
        <form method="POST" action="logout.php">
            <button type="submit">Déconnexion</button>
        </form>
    </div>
</body>
</html>
