<?php
session_start();
require_once "db.php";
require_once "encryption.php";
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AES Cryptage/Décryptage</title>
    <link rel="stylesheet" href="./styles/style.css">
</head>
<body>
    <div class="container">
        <h2>Crypter/Décrypter avec AES</h2>
        <?php if (isset($error)) { echo "<p class='error'>$error</p>"; } ?>

        <!-- Formulaire pour crypter ou décrypter un message -->
        <form method="POST">
            <textarea name="text" placeholder="Entrez votre texte ici" required></textarea><br>
            <select name="key_size" required>
                <option value="128">AES-128</option>
                <option value="256">AES-256</option>
                <option value="512">AES-512</option>
            </select><br>
            <button type="submit" name="action" value="encrypt">Crypter</button>
            <button type="submit" name="action" value="decrypt">Décrypter</button>
        </form>

        <!-- Affichage des résultats -->
        <?php
        if (isset($_POST['action'])) {
            $result = '';
            if ($_POST['action'] == 'encrypt' && isset($_POST['text'])) {
                // Récupérer le message à crypter
                $message = $_POST['text'];
                $key_size = $_POST['key_size'];

                // Choisir la méthode AES en fonction de la taille de clé
                $method = "aes-" . $key_size . "-cbc";
                $key = openssl_random_pseudo_bytes($key_size / 8);  // Générer une clé aléatoire en fonction de la taille
                $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($method));  // Générer un IV

                // Crypter le message
                $encrypted = openssl_encrypt($message, $method, $key, 0, $iv);
                $encrypted = base64_encode($encrypted . "::" . base64_encode($key) . "::" . base64_encode($iv));  // Encodage base64 pour transmettre en toute sécurité

                $result = "<h3>Résultats :</h3>";
                $result .= "<div class='result'><strong>Message crypté :</strong><br>" . htmlspecialchars($encrypted) . "</div>";
            } elseif ($_POST['action'] == 'decrypt' && isset($_POST['text'])) {
                // Récupérer le message crypté et le décoder
                $encrypted_message = $_POST['text'];
                $decoded_data = base64_decode($encrypted_message);

                // Vérifier si la chaîne décodée contient 3 parties
                $parts = explode("::", $decoded_data);

                if (count($parts) == 3) {
                    list($encrypted, $encoded_key, $encoded_iv) = $parts;  // Extraire l'IV, la clé et le message crypté

                    // Décodez la clé et l'IV
                    $key = base64_decode($encoded_key);
                    $iv = base64_decode($encoded_iv);

                    // Choisir la méthode AES en fonction de la taille de clé
                    $key_size = $_POST['key_size']; // Utiliser la taille de clé choisie pour le décryptage
                    $method = "aes-" . $key_size . "-cbc"; // Méthode correspondante

                    // Décrypter le message
                    $decrypted = openssl_decrypt($encrypted, $method, $key, 0, $iv);

                    $result = "<h3>Résultats :</h3>";
                    if ($decrypted === false) {
                        $result .= "<div class='result'><strong>Erreur lors du décryptage.</strong></div>";
                    } else {
                        $result .= "<div class='result'><strong>Message décrypté :</strong><br>" . htmlspecialchars($decrypted) . "</div>";
                    }
                } else {
                    $result = "<h3>Erreur :</h3><div class='result'><strong>Le message crypté semble mal formaté.</strong></div>";
                }
            }

            echo $result;
        }
        ?>

        <a href="user.php">Retour</a> | <a href="logout.php">Déconnexion</a>
    </div>
</body>
</html>
