<?php
// Bu dosya, config.php'nin zaten dahil edildiğini varsayar.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Kullanıcının giriş yapıp yapmadığını kontrol eder.
 * Giriş yapmamışsa login.php'ye yönlendirir.
 */
function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Kullanıcının rolünü kontrol eder.
 *
 * @param array $allowed_roles Bu sayfaya erişmesine izin verilen rollerin listesi.
 */
function require_role($allowed_roles = []) {
    // Önce giriş yapmış mı diye bak
    require_login();

    $user_role = $_SESSION['user_role'] ?? 'Ziyaretçi';

    if (!in_array($user_role, $allowed_roles)) {
        // Yetkisiz erişim
        echo "Bu sayfaya erişim yetkiniz bulunmamaktadır.";
        exit;
    }
}


// ==========================================================
// ===          YENİ EKLENEN CSRF FONKSİYONLARI           ===
// ==========================================================

/**
 * Formlara eklenecek gizli CSRF token input'unu yazdırır.
 */
function generate_csrf_input() {
    if (isset($_SESSION['csrf_token'])) {
        echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token']) . '">';
    }
}

/**
 * Form gönderildiğinde (POST) CSRF token'ını doğrular.
 * Başarısız olursa, programı durdurur.
 */
function validate_csrf_token() {
    // Sadece POST isteklerini kontrol et
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return; // POST değilse (örn: GET) bir şey yapma
    }
    
    // Formdan gelen token ile Session'daki token eşleşiyor mu?
    if (
        !isset($_POST['csrf_token']) || 
        !isset($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']) // Zamanlama saldırılarına karşı güvenli karşılaştırma
    ) {
        // Eşleşme Yetersiz
        die('Güvenlik hatası (CSRF Token mismatch). Lütfen formu tekrar gönderin.');
    }
}
?>