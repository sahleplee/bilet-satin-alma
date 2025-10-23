<?php
header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'Bilinmeyen sunucu hatası.']; 

try {
    include 'config.php'; // $pdo'yu getirir
    include 'auth.php';   // CSRF fonksiyonlarını getirir

    // === YENİ EKLENEN CSRF DOĞRULAMASI ===
    // (JavaScript 'fetch' isteğinden gelen token'ı doğrular)
    validate_csrf_token();
    // ===================================
    
    if (!isset($pdo) || $pdo === null) {
        throw new Exception("Veritabanı bağlantısı kurulamadı.");
    }

    // (Geri kalan tüm kupon doğrulama mantığı aynı)
    $coupon_code = $_POST['coupon_code'] ?? null;
    $trip_id = intval($_POST['trip_id'] ?? 0);

    if (empty($coupon_code) || $trip_id <= 0) {
        $response['message'] = 'Kupon kodu veya Sefer ID eksik.';
        echo json_encode($response);
        exit;
    }

    $stmt_trip = $pdo->prepare("SELECT price, company_id FROM Trips WHERE id = ?");
    $stmt_trip->execute([$trip_id]);
    $trip = $stmt_trip->fetch();
    if (!$trip) { $response['message'] = 'Sefer bulunamadı.'; echo json_encode($response); exit; }

    $original_price = $trip->price;
    $stmt_coupon = $pdo->prepare("SELECT * FROM Coupons WHERE code = ? AND expiry_date >= date('now', 'localtime') AND usage_count < usage_limit AND (company_id IS NULL OR company_id = ?)");
    $stmt_coupon->execute([$coupon_code, $trip->company_id]);
    $coupon = $stmt_coupon->fetch();

    if ($coupon) {
        $discount_rate = $coupon->discount_rate;
        $discount_amount = ($original_price * ($discount_rate / 100));
        $final_price = $original_price - $discount_amount;
        $response = [
            'success'       => true,
            'message'       => 'Kupon başarıyla uygulandı!',
            'originalPrice' => number_format($original_price, 2),
            'discountAmount' => number_format($discount_amount, 2),
            'finalPrice'    => number_format($final_price, 2),
            'discountRate'  => $discount_rate
        ];
    } else {
        $response['message'] = 'Geçersiz veya süresi dolmuş kupon kodu.';
    }
    echo json_encode($response);

} catch (Throwable $e) { 
    http_response_code(500); 
    $response['message'] = 'Sunucu Hatası: ' . $e->getMessage();
    // Hatanın CSRF hatası olup olmadığını kontrol et
    if (str_contains($e->getMessage(), "CSRF Token mismatch")) {
         $response['message'] = 'Güvenlik anahtarı hatası. Lütfen sayfayı yenileyin.';
    }
    echo json_encode($response);
}
?>