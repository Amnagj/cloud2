<?php
session_start();
require_once "db.php";
require_once "encryption.php";



if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $encryptionKey = $_POST["encryption_key"];
    $userId = $_SESSION["user_id"];
    saveDEK($encryptionKey, $userId, $conn); // Enregistrer la clé avec cryptage
    $message = "Clé enregistrée avec succès.";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Utilisateur</title>
    <link rel="stylesheet" href="styles/style.css">
</head>
<body>
    <div class="container">
        <h2>Bienvenue, <?php echo $_SESSION["username"]; ?></h2>
        
        <?php if (isset($message)) echo "<p>$message</p>"; ?>
        <a href="aes.php">AES Cryptage</a> 
        <a href="caesar.php">César Cryptage</a> 
        <a href="logout.php">Déconnexion</a>
    </div>
</body>
</html>
