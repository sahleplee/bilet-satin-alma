<?php
include 'header.php';
include 'auth.php'; // CSRF fonksiyonları dahil
require_role(['Admin', 'Firma Admin']); 

$coupon_id = intval($_GET['id'] ?? 0);
$user_role = $_SESSION['user_role'];
$user_company_id = $_SESSION['user_company_id'] ?? null;
$error_message = ''; $success_message = ''; $coupon = null;
if ($coupon_id <= 0) { header('Location: index.php'); exit; }

try {
    $sql = "SELECT * FROM Coupons WHERE id = ?"; $params = [$coupon_id];
    if ($user_role == 'Firma Admin') { $sql .= " AND company_id = ?"; $params[] = $user_company_id; } 
    elseif ($user_role == 'Admin') { $sql .= " AND company_id IS NULL"; } // Admin sadece global kuponları düzenler
    $stmt = $pdo->prepare($sql); $stmt->execute($params); $coupon = $stmt->fetch();
    if (!$coupon) { die('Kupon bulunamadı veya bu kuponu düzenleme yetkiniz yok.'); }
} catch (PDOException $e) { die('Veri çekme hatası: ' . $e->getMessage()); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
     // CSRF Token Doğrulama
     validate_csrf_token();
     
     try {
        $code = trim($_POST['code']); $discount_rate = floatval($_POST['discount_rate']);
        $usage_limit = intval($_POST['usage_limit']); $expiry_date = $_POST['expiry_date'];
        $sql = "UPDATE Coupons SET code = ?, discount_rate = ?, usage_limit = ?, expiry_date = ? WHERE id = ?";
        $params = [$code, $discount_rate, $usage_limit, $expiry_date, $coupon_id];
        if ($user_role == 'Firma Admin') { $sql .= " AND company_id = ?"; $params[] = $user_company_id; } 
        elseif ($user_role == 'Admin') { $sql .= " AND company_id IS NULL"; }
        $stmt = $pdo->prepare($sql); $stmt->execute($params); $success_message = 'Kupon başarıyla güncellendi.';
        // Güncelleme sonrası veriyi tekrar çek
        $stmt_refetch = $pdo->prepare("SELECT * FROM Coupons WHERE id = ?"); $stmt_refetch->execute([$coupon_id]); $coupon = $stmt_refetch->fetch();
     } catch (PDOException $e) { $error_message = 'Güncelleme hatası: ' . $e->getMessage(); }
}
?>

<?php if ($user_role == 'Firma Admin'): ?>
    <a href="company_admin_panel.php" class="btn btn-secondary btn-sm mb-3">&larr; Firma Paneline Geri Dön</a>
<?php else: // Admin ise ?>
    <a href="admin_panel.php" class="btn btn-secondary btn-sm mb-3">&larr; Admin Paneline Geri Dön</a>
<?php endif; ?>

<h2 class="mb-4">Kupon Düzenle (ID: <?php echo $coupon->id; ?>)</h2>
<?php if ($error_message): ?><div class="alert alert-danger"><?php echo $error_message; ?></div><?php endif; ?>
<?php if ($success_message): ?><div class="alert alert-success"><?php echo $success_message; ?></div><?php endif; ?>

<form action="edit_coupon.php?id=<?php echo $coupon->id; ?>" method="POST">
    <?php generate_csrf_input(); ?>
    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <label for="code" class="form-label">Kupon Kodu:</label>
            <input type="text" id="code" name="code" class="form-control" value="<?php echo htmlspecialchars($coupon->code); ?>" required>
        </div>
        <div class="col-md-6">
            <label for="discount_rate" class="form-label">İndirim Oranı (%):</label>
            <input type="number" id="discount_rate" name="discount_rate" class="form-control" step="0.1" min="1" max="100" value="<?php echo $coupon->discount_rate; ?>" required>
        </div>
    </div>
    <div class="row g-3">
        <div class="col-md-6">
            <label for="usage_limit" class="form-label">Kullanım Limiti:</label>
            <input type="number" id="usage_limit" name="usage_limit" class="form-control" min="1" value="<?php echo $coupon->usage_limit; ?>" required>
        </div>
        <div class="col-md-6">
            <label for="expiry_date" class="form-label">Son Kullanma Tarihi:</label>
            <input type="date" id="expiry_date" name="expiry_date" class="form-control" value="<?php echo date('Y-m-d', strtotime($coupon->expiry_date)); ?>" required>
        </div>
    </div>
    <button type="submit" class="btn btn-primary mt-3">Güncelle</button>
</form>

<?php include 'footer.php'; ?>