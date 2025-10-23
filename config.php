<?php
// Session yönetimini başlat
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// === YENİ CSRF TOKEN ALANI ===
// Eğer kullanıcı için bir CSRF token'ı henüz üretilmemişse, üret.
if (empty($_SESSION['csrf_token'])) {
    try {
        // Kriptografik olarak güvenli, rastgele 32 byte'lık bir anahtar üret
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } catch (Exception $e) {
        // random_bytes hatası (nadir)
        die("Güvenlik anahtarı üretilemedi.");
    }
}
// =============================

// Veritabanı dosyası
$dbFile = 'database.sqlite';
$pdo = null; 

try {
    // SQLite Veritabanı Bağlantısı
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ); 
} catch (PDOException $e) {
    // $pdo null olarak kalacak (AJAX uyumluluğu)
}
?>