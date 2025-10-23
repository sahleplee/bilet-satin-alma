<?php
include 'header.php'; // DB, Session, Bootstrap CSS, Navigasyon
include 'auth.php';   // CSRF fonksiyonları (generate_csrf_input, validate_csrf_token) dahil edildi

$trip_id = intval($_GET['trip_id'] ?? 0);
$error_message = '';
$success_message = '';
$user_balance = 0;
$trip = null;
$sold_seats = []; 
if ($trip_id <= 0) die('Geçersiz sefer ID\'si.');

// --- SATIN ALMA İŞLEMİ (POST) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // 1. CSRF TOKEN DOĞRULAMASI (YENİ EKLENDİ)
    validate_csrf_token(); 
    
    // 2. Yetki Kontrolü (Daha önce de vardı)
    require_role(['User']); 
    
    $seat_number = intval($_POST['seat_number'] ?? 0);
    $coupon_code = trim($_POST['coupon_code'] ?? '');
    $user_id = $_SESSION['user_id'];
    
    if ($seat_number <= 0) $error_message = 'Lütfen geçerli bir koltuk numarası seçin.';
    else {
        $pdo->beginTransaction();
        try {
            // (Geri kalan tüm bilet alma, bakiye düşme, kupon vb. işlemleri aynı)
            $stmt_trip = $pdo->prepare("SELECT T.* FROM Trips AS T WHERE T.id = ?");
            $stmt_trip->execute([$trip_id]);
            $trip_data = $stmt_trip->fetch();
            if (!$trip_data) throw new Exception("Sefer bulunamadı.");
            $stmt_seat = $pdo->prepare("SELECT id FROM Tickets WHERE trip_id = ? AND seat_number = ? AND status = 'Active'");
            $stmt_seat->execute([$trip_id, $seat_number]);
            if ($stmt_seat->fetch()) throw new Exception("Seçtiğiniz $seat_number numaralı koltuk az önce satın alındı.");
            $final_price = $trip_data->price;
            $coupon_id_to_update = null;
            if (!empty($coupon_code)) {
                $stmt_coupon = $pdo->prepare("SELECT * FROM Coupons WHERE code = ? AND expiry_date >= date('now', 'localtime') AND usage_count < usage_limit AND (company_id IS NULL OR company_id = ?)");
                $stmt_coupon->execute([$coupon_code, $trip_data->company_id]);
                $coupon = $stmt_coupon->fetch();
                if ($coupon) { $final_price = $final_price - ($final_price * ($coupon->discount_rate / 100)); $coupon_id_to_update = $coupon->id; } 
                else { throw new Exception("AJAX tarafından doğrulanmasına rağmen sunucuda geçersiz kupon hatası."); }
            }
            $stmt_user = $pdo->prepare("SELECT balance FROM Users WHERE id = ?"); $stmt_user->execute([$user_id]);
            $current_balance = $stmt_user->fetchColumn();
            if ($current_balance < $final_price) throw new Exception("Yetersiz bakiye. (Gereken: $final_price TL, Mevcut: $current_balance TL)");
            $stmt_update_balance = $pdo->prepare("UPDATE Users SET balance = balance - ? WHERE id = ?");
            $stmt_update_balance->execute([$final_price, $user_id]);
            $stmt_insert_ticket = $pdo->prepare("INSERT INTO Tickets (user_id, trip_id, seat_number, status, purchase_price) VALUES (?, ?, ?, 'Active', ?)");
            $stmt_insert_ticket->execute([$user_id, $trip_id, $seat_number, $final_price]);
            if ($coupon_id_to_update) { $stmt_update_coupon = $pdo->prepare("UPDATE Coupons SET usage_count = usage_count + 1 WHERE id = ?"); $stmt_update_coupon->execute([$coupon_id_to_update]); }
            $pdo->commit();
            header("Location: my_tickets.php?buy_success=1");
            exit;
        } catch (Exception $e) { $pdo->rollBack(); $error_message = 'Bilet alımı başarısız: ' . $e->getMessage(); }
    }
}
// --- POST SONU ---

// --- GET (Sayfa Yükleme) ---
if ($is_logged_in && $user_role == 'User') { $stmt_balance = $pdo->prepare("SELECT balance FROM Users WHERE id = ?"); $stmt_balance->execute([$_SESSION['user_id']]); $user_balance = $stmt_balance->fetchColumn(); }
try {
    $stmt = $pdo->prepare("SELECT T.*, C.name AS company_name FROM Trips AS T JOIN Companies AS C ON T.company_id = C.id WHERE T.id = ?");
    $stmt->execute([$trip_id]); $trip = $stmt->fetch();
    if (!$trip) die('Sefer bulunamadı.');
    $stmt_seats = $pdo->prepare("SELECT seat_number FROM Tickets WHERE trip_id = ? AND status = 'Active'");
    $stmt_seats->execute([$trip_id]); $sold_seats_data = $stmt_seats->fetchAll(PDO::FETCH_COLUMN);
    $sold_seats = array_flip($sold_seats_data);
} catch (PDOException $e) { die('Veri çekme hatası: '. $e->getMessage()); }
// --- GET SONU ---
?>

<h2 class="mb-3">Sefer Detayları ve Bilet Alma</h2>
<?php if ($error_message): ?><div class="alert alert-danger"><?php echo $error_message; ?></div><?php endif; ?>
<?php if ($success_message): ?><div class="alert alert-success"><?php echo $success_message; ?></div><?php endif; ?>
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-primary text-white p-3"><h3 class="mb-0"><?php echo htmlspecialchars($trip->company_name); ?></h3></div>
    <div class="card-body p-4"><div class="row"><div class="col-md-6"><p class="fs-5"><strong>Kalkış:</strong> <?php echo htmlspecialchars($trip->origin); ?> <br> <strong>Varış:</strong> <?php echo htmlspecialchars($trip->destination); ?></p></div><div class="col-md-6"><p class="fs-5"><strong>Kalkış Zamanı:</strong> <?php echo date('d.m.Y H:i', strtotime($trip->departure_time)); ?> <br> <strong>Fiyat:</strong> <span class="fw-bold text-success fs-4"><?php echo $trip->price; ?> TL</span></p></div></div></div>
</div>
<hr class="my-4">
<?php if ($is_logged_in && $user_role == 'User'): ?><div class="alert alert-info">Mevcut Bakiyeniz: <strong><?php echo number_format($user_balance, 2); ?> TL</strong></div><?php endif; ?>
<h4 class="mb-3">Koltuk Seçimi (Toplam Koltuk: <?php echo $trip->total_seats; ?>)</h4>
<form action="trip_details.php?trip_id=<?php echo $trip->id; ?>" method="POST" class="purchase-form">
    
    <?php generate_csrf_input(); ?>
    
    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <h5 class="card-title">Lütfen Koltuk Seçiniz</h5>
            <p class="card-text text-muted">Kırmızı koltuklar doludur.</p>
            <div class="seat-map mb-3">
                <?php for ($i = 1; $i <= $trip->total_seats; $i++): $is_disabled = isset($sold_seats[$i]); ?>
                    <div class="seat">
                        <input type="radio" name="seat_number" id="seat-<?php echo $i; ?>" value="<?php echo $i; ?>" <?php if ($is_disabled) echo 'disabled'; ?> required>
                        <label for="seat-<?php echo $i; ?>"><?php echo $i; ?></label>
                    </div>
                    <?php if ($i % 2 == 0 && $i % 4 != 0): ?><div class="seat corridor"></div><?php endif; ?>
                <?php endfor; ?>
            </div>
            <hr class="my-4">
            <div class="row g-3 align-items-end">
                <div class="col-md-6">
                    <label for="coupon_code" class="form-label">İndirim Kuponu:</label>
                    <input type="text" class="form-control form-control-lg" id="coupon_code" name="coupon_code">
                    <div id="coupon-feedback" class="mt-2" style="min-height: 25px;"></div>
                </div>
                <div class="col-md-6">
                    <div id="price-breakdown" class="mb-3 fs-5"></div>
                    <button type="submit" class="btn btn-success btn-lg w-100" id="btn-buy" disabled>Satın Al</button>
                </div>
            </div>
            <?php if (!$is_logged_in): ?><p class="text-muted text-center mt-3 small">(Bilet almak için 'Satın Al' butonuna tıkladığınızda giriş yapmanız istenecektir.)</p><?php endif; ?>
        </div>
    </div>
</form>

<script>
    // PHP'den JS'e değişken aktarma
    const tripId = <?php echo $trip->id; ?>;
    const originalPrice = <?php echo $trip->price; ?>;
    // PHP'de oluşturulan CSRF token'ı JavaScript'e aktar (YENİ EKLENDİ)
    const csrfToken = "<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>";

    // Elementleri seçme
    const couponInput = document.getElementById('coupon_code');
    const buyButton = document.getElementById('btn-buy');
    const feedbackDiv = document.getElementById('coupon-feedback');
    const priceDiv = document.getElementById('price-breakdown');
    
    let isSeatSelected = false;
    let isCouponValid = true; 

    function updateButtonState() {
        if (isSeatSelected && isCouponValid) { buyButton.disabled = false; } 
        else { buyButton.disabled = true; }
    }

    document.querySelectorAll('input[name="seat_number"]').forEach(radio => {
        radio.addEventListener('change', () => {
            isSeatSelected = true;
            updateButtonState(); 
        });
    });

    couponInput.addEventListener('blur', async () => {
        const code = couponInput.value.trim();
        feedbackDiv.innerHTML = '';
        priceDiv.innerHTML = '';
        if (code === "") { isCouponValid = true; updateButtonState(); return; }

        isCouponValid = false;
        buyButton.disabled = true;
        feedbackDiv.innerHTML = '<span class="text-muted">Kupon doğrulanıyor...</span>';

        // AJAX isteği için veri hazırla
        const formData = new FormData();
        formData.append('coupon_code', code);
        formData.append('trip_id', tripId);
        // AJAX isteğine CSRF token'ı ekle (YENİ EKLENDİ)
        // (Not: formun name'i 'csrf_token' olmalı, auth.php'deki 'validate_csrf_token' ile eşleşmeli)
        formData.append('csrf_token', csrfToken); 

        try {
            const response = await fetch('validate_coupon.php', {
                method: 'POST',
                body: formData
            });

            if (!response.ok) { throw new Error(`HTTP hatası! Durum: ${response.status}`); }
            const data = await response.json();

            if (data.success) {
                isCouponValid = true;
                feedbackDiv.innerHTML = `<span class="text-success fw-bold">${data.message} (%${data.discountRate} indirim)</span>`;
                priceDiv.innerHTML = 
                    `<div>Bilet Fiyatı: <span class="text-decoration-line-through">${data.originalPrice} TL</span></div>` +
                    `<div>İndirim: <span class="text-danger">-${data.discountAmount} TL</span></div>` +
                    `<div><strong>Son Tutar:</strong> <strong class="text-success fs-4">${data.finalPrice} TL</strong></div>`;
            } else {
                isCouponValid = false;
                feedbackDiv.innerHTML = `<span class="text-danger fw-bold">${data.message}</span>`;
            }
        } catch (error) {
            isCouponValid = false;
            feedbackDiv.innerHTML = '<span class="text-danger fw-bold">Doğrulama sırasında hata oluştu.</span>';
            console.error('AJAX Doğrulama Hatası:', error);
        }
        updateButtonState();
    });
    updateButtonState();
</script>
<?php
include 'footer.php';
?>