<?php
// Vérification de l'état de la session et démarrage si nécessaire
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Connexion à la base de données
$conn = new mysqli("localhost", "root", "", "cryptage_db");
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

$error = '';
$captchaError = '';

// Fonction pour enregistrer les logs
function writeLog($message) {
    $logFile = './logs/login_log.txt'; // Chemin du fichier log
    $currentTime = date("Y-m-d H:i:s"); // Date et heure actuelles
    $ipAddress = $_SERVER['REMOTE_ADDR']; // Adresse IP de l'utilisateur
    $logMessage = "[$currentTime] [IP: $ipAddress] $message" . PHP_EOL;

    // Création du dossier "logs" s'il n'existe pas
    if (!is_dir('./logs')) {
        mkdir('./logs', 0777, true);
    }

    // Écriture dans le fichier log
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST["username"];
    $password = $_POST["password"];
    $captchaResponse = $_POST['g-recaptcha-response'];

    // Vérifier si les champs username et password sont remplis
    if (empty($username) || empty($password)) {
        $error = "Veuillez remplir tous les champs.";
        writeLog("Tentative échouée : champs vides.");
    } else {
        // Vérification du CAPTCHA
        $secretKey = "6LfKJY8qAAAAANx-uDPkVrJExB_vrQVt2vyp9jhd"; // Remplacez par votre clé secrète
        $response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$secretKey&response=$captchaResponse");
        $responseKeys = json_decode($response, true);

        if (!$responseKeys['success']) {
            $captchaError = "La vérification CAPTCHA a échoué. Essayez à nouveau.";
            writeLog("Tentative échouée : échec du CAPTCHA pour l'utilisateur $username.");
        } else {
            // Connexion à la base de données
            $sql = "SELECT * FROM users WHERE username = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();

                // Comparaison du mot de passe haché
                if (password_verify($password, $user['password'])) {
                    $_SESSION["username"] = $user["username"];
                    $_SESSION["role"] = $user["role"];
                    $_SESSION["user_id"] = $user["id"];

                    // Enregistrer le login réussi dans le fichier log
                    writeLog("Connexion réussie pour l'utilisateur $username avec le rôle " . $user["role"] . ".");

                    // Redirection selon le rôle de l'utilisateur
                    if ($user["role"] === "superuser") {
                        header("Location: superuser.php");
                        exit();
                    } elseif ($user["role"] === "admin") {
                        header("Location: admin.php");
                        exit();
                    } elseif ($user["role"] === "user") {
                        header("Location: user.php");
                        exit();
                    } else {
                        $error = "Rôle non valide.";
                        writeLog("Échec : rôle non valide pour l'utilisateur $username.");
                    }
                } else {
                    $error = "Mot de passe incorrect.";
                    writeLog("Échec : mot de passe incorrect pour l'utilisateur $username.");
                }
            } else {
                $error = "Nom d'utilisateur incorrect.";
                writeLog("Échec : nom d'utilisateur incorrect ($username).");
            }

            $stmt->close();
        }
    }
}

$conn->close();
?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion</title>
    <!-- Importation de l'API reCAPTCHA -->
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <!-- Lien vers votre fichier CSS -->
    <link rel="stylesheet" href="styles/style.css">
</head>
<body>
    <div class="container">
        <h2>Connexion</h2>
        <!-- Affichage des erreurs -->
        <?php if ($error): ?><p class="error"><?php echo $error; ?></p><?php endif; ?>
        <?php if ($captchaError): ?><p class="error"><?php echo $captchaError; ?></p><?php endif; ?>
        
        <!-- Formulaire de connexion -->
        <form method="POST" action="login.php">
            <input type="text" name="username" placeholder="Nom d'utilisateur" required>
            <input type="password" name="password" placeholder="Mot de passe" required>
            <!-- reCAPTCHA v2 -->
            <div class="g-recaptcha" data-sitekey="6LfKJY8qAAAAANjaC2BWBUTXWOr2KS5nUl3zlAuA"></div>
            <button type="submit">Se connecter</button>
        </form>
    </div>
</body>
</html>

