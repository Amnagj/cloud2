<?php
$conn = new mysqli("localhost", "root", "", "cryptage_db");

if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}
?>
