<?php
// Bu dosya, AJAX isteklerine JSON formatında cevap verecek.
header('Content-Type: application/json');
$response = ['userExists' => false, 'message' => '']; // Varsayılan yanıt

try {
    include 'config.php'; // Veritabanı bağlantısını ($pdo) getirir

    // config.php'nin (AJAX uyumlu) $pdo'yu getirip getirmediğini kontrol et
    if (!isset($pdo) || $pdo === null) {
        throw new Exception("Veritabanı bağlantısı kurulamadı.");
    }

    $email = $_POST['email'] ?? null;

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // E-posta gelmediyse veya geçersizse bir şey yapma (JS tarafı halleder)
        echo json_encode($response);
        exit;
    }

    // E-postanın veritabanında olup olmadığını kontrol et
    $stmt = $pdo->prepare("SELECT id FROM Users WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        // Kullanıcı bulundu
        $response['userExists'] = true;
    } else {
        // Kullanıcı bulunamadı
        $response['userExists'] = false;
    }

    echo json_encode($response);

} catch (Throwable $e) { 
    // Hata durumunda (DB çökmesi vb.)
    http_response_code(500); 
    $response = ['userExists' => false, 'message' => 'Sunucu hatası: ' . $e->getMessage()];
    echo json_encode($response);
}
?>