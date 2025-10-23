<?php
include 'header.php'; // DB, Session, Bootstrap CSS, Navigasyon
include 'auth.php';   // CSRF fonksiyonları dahil edildi
// Bu sayfaya sadece 'Firma Admin' rolü erişebilir
require_role(['Firma Admin']);

$company_id = $_SESSION['user_company_id'];
$error_message = '';
$success_message = '';

// --- İŞLEM KONTROLÜ (POST) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // === YENİ EKLENEN CSRF TOKEN DOĞRULAMASI ===
    validate_csrf_token();
    // =========================================
    
    $action = $_POST['action'] ?? null;
    try {
        if ($action == 'add_trip') {
            // (Sefer ekleme mantığı - değişiklik yok)
            $origin = trim($_POST['origin']); $destination = trim($_POST['destination']); $departure_time = $_POST['departure_time'];
            $arrival_time = $_POST['arrival_time']; $price = floatval($_POST['price']); $total_seats = intval($_POST['total_seats']);
            if (empty($origin) || empty($destination) || empty($departure_time) || empty($arrival_time) || $price <= 0 || $total_seats <= 0) {
                $error_message = 'Tüm sefer bilgilerini (pozitif fiyat ve koltuk sayısı) doğru girdiğinizden emin olun.';
            } else {
                $stmt = $pdo->prepare("INSERT INTO Trips (company_id, origin, destination, departure_time, arrival_time, price, total_seats) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$company_id, $origin, $destination, $departure_time, $arrival_time, $price, $total_seats]);
                $success_message = 'Yeni sefer başarıyla eklendi.';
            }
        } elseif ($action == 'add_coupon') {
            // (Kupon ekleme mantığı - değişiklik yok)
            $code = trim($_POST['code']); $discount_rate = floatval($_POST['discount_rate']);
            $usage_limit = intval($_POST['usage_limit']); $expiry_date = $_POST['expiry_date'];
            if (empty($code) || $discount_rate <= 0 || $discount_rate > 100 || $usage_limit <= 0 || empty($expiry_date)) {
                $error_message = 'Kupon bilgilerini (İndirim Oranı %1-100 arası olmalı) kontrol edin.';
            } else {
                $stmt = $pdo->prepare("INSERT INTO Coupons (code, discount_rate, usage_limit, expiry_date, company_id) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$code, $discount_rate, $usage_limit, $expiry_date, $company_id]);
                $success_message = 'Yeni kupon başarıyla oluşturuldu.';
            }
        }
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { $error_message = 'Bu kod (sefer veya kupon) zaten mevcut.'; } 
        else { $error_message = 'İşlem sırasında bir hata oluştu: ' . $e->getMessage(); }
    }
}

// --- VERİ ÇEKME (GET) ---
$stmt_trips = $pdo->prepare("SELECT * FROM Trips WHERE company_id = ? ORDER BY departure_time DESC");
$stmt_trips->execute([$company_id]);
$trips = $stmt_trips->fetchAll();
$stmt_coupons = $pdo->prepare("SELECT * FROM Coupons WHERE company_id = ? ORDER BY expiry_date DESC");
$stmt_coupons->execute([$company_id]);
$coupons = $stmt_coupons->fetchAll();

// (HTML kısmı header.php'de başlar)
?>

<h2 class="mb-4">Firma Admin Paneli (<?php echo htmlspecialchars($_SESSION['user_fullname']); ?>)</h2>
<?php if ($error_message): ?><div class="alert alert-danger"><?php echo $error_message; ?></div><?php endif; ?>
<?php if ($success_message): ?><div class="alert alert-success"><?php echo $success_message; ?></div><?php endif; ?>

<div class="card shadow-sm mb-4">
    <div class="card-header"><h4 class="mb-0">Yeni Sefer Ekle</h4></div>
    <div class="card-body">
        <form action="company_admin_panel.php" method="POST">
            <?php generate_csrf_input(); ?>
            
            <input type="hidden" name="action" value="add_trip">
            <div class="row g-3 mb-3">
                <div class="col-md-6"><label for="origin" class="form-label">Kalkış Yeri:</label><input type="text" id="origin" name="origin" class="form-control" required></div>
                <div class="col-md-6"><label for="destination" class="form-label">Varış Yeri:</label><input type="text" id="destination" name="destination" class="form-control" required></div>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-6"><label for="departure_time" class="form-label">Kalkış Zamanı:</label><input type="datetime-local" id="departure_time" name="departure_time" class="form-control" required></div>
                <div class="col-md-6"><label for="arrival_time" class="form-label">Varış Zamanı:</label><input type="datetime-local" id="arrival_time" name="arrival_time" class="form-control" required></div>
            </div>
            <div class="row g-3">
                <div class="col-md-6"><label for="price" class="form-label">Fiyat (TL):</label><input type="number" id="price" name="price" class="form-control" step="0.01" min="1" required></div>
                <div class="col-md-6"><label for="total_seats" class="form-label">Koltuk Sayısı:</label><input type="number" id="total_seats" name="total_seats" class="form-control" min="1" required></div>
            </div>
            <button type="submit" class="btn btn-primary mt-3">Seferi Ekle</button>
        </form>
    </div>
</div>

<h3 class="mt-5">Mevcut Seferler</h3>
<div class="table-responsive">
    <table class="table table-striped table-hover align-middle border">
        <thead class="table-light"><tr><th>Kalkış</th><th>Varış</th><th>Kalkış Zamanı</th><th>Fiyat</th><th>Koltuk</th><th>İşlemler</th></tr></thead>
        <tbody>
            <?php if (empty($trips)): ?><tr><td colspan="6" class="text-center p-4">Henüz eklenmiş seferiniz bulunmamaktadır.</td></tr>
            <?php else: foreach ($trips as $trip): ?>
                <tr>
                    <td><?php echo htmlspecialchars($trip->origin); ?></td><td><?php echo htmlspecialchars($trip->destination); ?></td>
                    <td><?php echo date('d.m.Y H:i', strtotime($trip->departure_time)); ?></td><td><?php echo $trip->price; ?> TL</td><td><?php echo $trip->total_seats; ?></td>
                    <td>
                        <a href="edit_trip.php?id=<?php echo $trip->id; ?>" class="btn btn-outline-primary btn-sm">Düzenle</a>
                        <a href="delete_handler.php?type=trip&id=<?php echo $trip->id; ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Bu seferi silmek istediğinizden emin misiniz?');">Sil</a>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<div class="card shadow-sm mt-5 mb-4">
    <div class="card-header"><h4 class="mb-0">Yeni İndirim Kuponu Ekle (Firmaya Özel)</h4></div>
    <div class="card-body">
        <form action="company_admin_panel.php" method="POST">
            <?php generate_csrf_input(); ?>
            
            <input type="hidden" name="action" value="add_coupon">
            <div class="row g-3">
                <div class="col-md-6"><label for="code" class="form-label">Kupon Kodu:</label><input type="text" id="code" name="code" class="form-control" required></div>
                <div class="col-md-6"><label for="discount_rate" class="form-label">İndirim Oranı (%):</label><input type="number" id="discount_rate" name="discount_rate" class="form-control" step="0.1" min="1" max="100" required></div>
            </div>
            <div class="row g-3 mt-1">
                <div class="col-md-6"><label for="usage_limit" class="form-label">Kullanım Limiti:</label><input type="number" id="usage_limit" name="usage_limit" class="form-control" min="1" required></div>
                <div class="col-md-6"><label for="expiry_date" class="form-label">Son Kullanma Tarihi:</label><input type="date" id="expiry_date" name="expiry_date" class="form-control" required></div>
            </div>
            <button type="submit" class="btn btn-primary mt-3">Kuponu Ekle</button>
        </form>
    </div>
</div>

<h3 class="mt-5">Mevcut Kuponlar (Firmaya Özel)</h3>
<div class="table-responsive">
    <table class="table table-striped table-hover align-middle border">
        <thead class="table-light"><tr><th>Kod</th><th>Oran (%)</th><th>Limit / Kullanılan</th><th>Son Tarih</th><th>İşlemler</th></tr></thead>
        <tbody>
            <?php if (empty($coupons)): ?><tr><td colspan="5" class="text-center p-4">Henüz eklenmiş kuponunuz bulunmamaktadır.</td></tr>
            <?php else: foreach ($coupons as $coupon): ?>
                <tr>
                    <td><?php echo htmlspecialchars($coupon->code); ?></td><td>% <?php echo $coupon->discount_rate; ?></td>
                    <td><?php echo $coupon->usage_limit; ?> / <?php echo $coupon->usage_count; ?></td><td><?php echo date('d.m.Y', strtotime($coupon->expiry_date)); ?></td>
                    <td>
                         <a href="edit_coupon.php?id=<?php echo $coupon->id; ?>" class="btn btn-outline-primary btn-sm">Düzenle</a>
                         <a href="delete_handler.php?type=coupon&id=<?php echo $coupon->id; ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Bu kuponu silmek istediğinizden emin misiniz?');">Sil</a>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<?php
include 'footer.php'; // Sayfa sonu
?>