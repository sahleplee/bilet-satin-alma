<?php
include 'config.php';
include 'auth.php';
require_login(); // Silme işlemi için giriş yapmak zorunlu

$type = $_GET['type'] ?? null;
$id = intval($_GET['id'] ?? 0);

$user_role = $_SESSION['user_role'];
$user_company_id = $_SESSION['user_company_id'] ?? null;

$redirect_url = 'index.php'; // Varsayılan yönlendirme
$error_message = '';

if ($id <= 0) {
    die('Geçersiz ID.');
}

try {
    switch ($type) {
        // --- Firma Admin Yetkisi ---
        case 'trip':
            if ($user_role == 'Firma Admin') {
                $stmt = $pdo->prepare("DELETE FROM Trips WHERE id = ? AND company_id = ?");
                $stmt->execute([$id, $user_company_id]);
                $redirect_url = 'company_admin_panel.php';
            } else { $error_message = 'Yetkisiz işlem.'; }
            break;

        // --- Ortak (Admin & Firma Admin) Yetkisi ---
        case 'coupon':
            if ($user_role == 'Firma Admin') {
                $stmt = $pdo->prepare("DELETE FROM Coupons WHERE id = ? AND company_id = ?");
                $stmt->execute([$id, $user_company_id]);
                $redirect_url = 'company_admin_panel.php';
            } elseif ($user_role == 'Admin') {
                // Adminler sadece global kuponları silebilir (company_id = NULL)
                $stmt = $pdo->prepare("DELETE FROM Coupons WHERE id = ? AND company_id IS NULL");
                $stmt->execute([$id]);
                $redirect_url = 'admin_panel.php';
            } else { $error_message = 'Yetkisiz işlem.'; }
            break;

        // --- Admin Yetkisi ---
        case 'company':
            if ($user_role == 'Admin') {
                // Önce bu firmaya bağlı sefer veya admin var mı diye kontrol et (FK ihlali önlemi)
                $stmt = $pdo->prepare("SELECT COUNT(id) AS total FROM Trips WHERE company_id = ?");
                $stmt->execute([$id]);
                $trips_count = $stmt->fetchColumn();
                
                $stmt = $pdo->prepare("SELECT COUNT(id) AS total FROM Users WHERE company_id = ? AND role = 'Firma Admin'");
                $stmt->execute([$id]);
                $admins_count = $stmt->fetchColumn();
                
                if ($trips_count > 0 || $admins_count > 0) {
                    $error_message = 'Bu firmayı silemezsiniz. Önce bu firmaya bağlı seferleri (' . $trips_count . ') ve firma adminlerini (' . $admins_count . ') silmelisiniz.';
                } else {
                    $stmt = $pdo->prepare("DELETE FROM Companies WHERE id = ?");
                    $stmt->execute([$id]);
                }
                $redirect_url = 'admin_panel.php';
            } else { $error_message = 'Yetkisiz işlem.'; }
            break;
            
        case 'firma_admin':
            if ($user_role == 'Admin') {
                $stmt = $pdo->prepare("DELETE FROM Users WHERE id = ? AND role = 'Firma Admin'");
                $stmt->execute([$id]);
                $redirect_url = 'admin_panel.php';
            } else { $error_message = 'Yetkisiz işlem.'; }
            break;

        default:
            $error_message = 'Geçersiz işlem tipi.';
            break;
    }
} catch (PDOException $e) {
    $error_message = 'Silme işlemi sırasında veritabanı hatası: ' . $e->getMessage();
}

if ($error_message) {
    // Hata mesajını session'a kaydedip geri yönlendirmek daha iyi olabilir
    // Şimdilik basitçe gösterelim:
    die($error_message . ' <a href="' . $redirect_url . '">Geri dön</a>');
} else {
    // Başarılıysa ilgili panele geri dön
    header('Location: ' . $redirect_url);
    exit;
}
?>