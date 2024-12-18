<?php
function generateKEK($conn) {
    $kek = bin2hex(random_bytes(32)); // 256 bits
    $encryptedKEK = password_hash($kek, PASSWORD_DEFAULT); // Crypter KEK
    $sql = "INSERT INTO kek (kek_value) VALUES (?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $encryptedKEK);
    $stmt->execute();
    return $kek;
}

function encryptDEK($userProvidedKey, $kek) {
    return openssl_encrypt($userProvidedKey, "AES-256-CBC", $kek, 0, str_repeat("0", 16));
}

function decryptDEK($encryptedDEK, $kek) {
    return openssl_decrypt($encryptedDEK, "AES-256-CBC", $kek, 0, str_repeat("0", 16));
}

function saveDEK($userProvidedKey, $userId, $conn) {
    $kekRow = $conn->query("SELECT * FROM kek LIMIT 1")->fetch_assoc();
    if (!$kekRow) {
        $kek = generateKEK($conn); // Générer une KEK si non disponible
    } else {
        $kek = $kekRow['kek_value'];
    }

    $encryptedDEK = encryptDEK($userProvidedKey, $kek);
    $sql = "INSERT INTO encryption_keys (user_id, encrypted_dek, kek_id) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isi", $userId, $encryptedDEK, $kekRow['id']);
    $stmt->execute();
}

function getDEK($userId, $conn) {
    $sql = "SELECT ek.encrypted_dek, k.kek_value 
            FROM encryption_keys ek 
            JOIN kek k ON ek.kek_id = k.id 
            WHERE ek.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if ($result) {
        return decryptDEK($result['encrypted_dek'], $result['kek_value']);
    }
    return null;
}
?>
