<?php
include 'header.php'; // DB, Session, Bootstrap CSS, Navigasyon
include 'auth.php';   // CSRF fonksiyonları (generate_csrf_input, validate_csrf_token) dahil edildi
// Bu sayfaya sadece 'User' (Yolcu) rolü erişebilir
require_role(['User']);

$user_id = $_SESSION['user_id'];
$error_message = '';
$success_message = '';

// Adım 7'den gelen başarılı satın alma mesajı
if (isset($_GET['buy_success'])) {
    $success_message = 'Biletiniz başarıyla satın alındı!';
}

// --- POST İŞLEMLERİ ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    
    // === YENİ EKLENEN CSRF TOKEN DOĞRULAMASI ===
    // Sayfadaki TÜM post işlemleri için önce token'ı doğrula
    validate_csrf_token();
    // =========================================

    // --- 1. BİLET İPTAL İŞLEMİ ---
    if ($_POST['action'] == 'cancel_ticket') {
        $ticket_id_to_cancel = intval($_POST['ticket_id'] ?? 0);
        if ($ticket_id_to_cancel > 0) {
            $pdo->beginTransaction();
            try {
                // (Bilet iptal mantığı - değişiklik yok)
                $stmt = $pdo->prepare("SELECT T.*, TR.departure_time FROM Tickets AS T JOIN Trips AS TR ON T.trip_id = TR.id WHERE T.id = ? AND T.user_id = ? AND T.status = 'Active'");
                $stmt->execute([$ticket_id_to_cancel, $user_id]);
                $ticket = $stmt->fetch();
                if (!$ticket) { throw new Exception("İptal edilecek bilet bulunamadı veya bu bilet size ait değil."); }
                $departure_timestamp = strtotime($ticket->departure_time);
                $current_timestamp = time();
                $one_hour_in_seconds = 3600;
                if (($departure_timestamp - $current_timestamp) < $one_hour_in_seconds) {
                    throw new Exception("Kalkışa 1 saatten az bir süre kaldığı için bilet iptal edilemez.");
                }
                $stmt_cancel = $pdo->prepare("UPDATE Tickets SET status = 'Canceled' WHERE id = ?");
                $stmt_cancel->execute([$ticket_id_to_cancel]);
                $refund_amount = $ticket->purchase_price;
                $stmt_refund = $pdo->prepare("UPDATE Users SET balance = balance + ? WHERE id = ?");
                $stmt_refund->execute([$refund_amount, $user_id]);
                $pdo->commit();
                $success_message = "Bilet (ID: $ticket_id_to_cancel) başarıyla iptal edildi. $refund_amount TL bakiyenize iade edildi.";
            } catch (Exception $e) {
                $pdo->rollBack();
                $error_message = 'İptal işlemi başarısız: ' . $e->getMessage();
            }
        }
    }
    
    // --- 2. BAKİYE YÜKLEME İŞLEMİ ---
    elseif ($_POST['action'] == 'add_balance') {
        $amount_to_add = floatval($_POST['amount'] ?? 0);
        
        if ($amount_to_add > 0) {
            try {
                // (Bakiye yükleme mantığı - değişiklik yok)
                $stmt = $pdo->prepare("UPDATE Users SET balance = balance + ? WHERE id = ?");
                $stmt->execute([$amount_to_add, $user_id]);
                $success_message = number_format($amount_to_add, 2) . " TL (Test Bakiye) başarıyla hesabınıza eklendi.";
            } catch (PDOException $e) {
                $error_message = "Bakiye yüklenirken bir veritabanı hatası oluştu: " . $e->getMessage();
            }
        } else {
            $error_message = "Lütfen 0'dan büyük geçerli bir tutar girin.";
        }
    }
}


// --- VERİ ÇEKME (GET) ---
// (POST işlemleri bittikten sonra en güncel bakiyeyi çekiyoruz)
$stmt_user = $pdo->prepare("SELECT balance FROM Users WHERE id = ?");
$stmt_user->execute([$user_id]);
$user_balance = $stmt_user->fetchColumn();

// Kullanıcının tüm biletlerini çek
$stmt_tickets = $pdo->prepare(
    "SELECT T.*, TR.origin, TR.destination, TR.departure_time, C.name AS company_name
     FROM Tickets AS T
     JOIN Trips AS TR ON T.trip_id = TR.id
     JOIN Companies AS C ON TR.company_id = C.id
     WHERE T.user_id = ? ORDER BY TR.departure_time DESC"
);
$stmt_tickets->execute([$user_id]);
$tickets = $stmt_tickets->fetchAll();


// (HTML KISMI BAŞLIYOR)
?>

<h2 class="mb-4">Hesabım / Biletlerim</h2>

<?php if ($error_message): ?>
    <div class="alert alert-danger"><?php echo $error_message; ?></div>
<?php endif; ?>
<?php if ($success_message): ?>
    <div class="alert alert-success"><?php echo $success_message; ?></div>
<?php endif; ?>


<div class="row">
    <div class="col-lg-7 mb-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-dark text-white"><h4 class="mb-0">Hesap Bilgileri</h4></div>
            <div class="card-body d-flex flex-column justify-content-between">
                <div>
                    <p class="fs-5"><strong>Ad Soyad:</strong> <?php echo htmlspecialchars($_SESSION['user_fullname']); ?></p>
                    <p class="fs-5"><strong>E-posta:</strong> <?php echo htmlspecialchars($_SESSION['user_email']); ?></p>
                </div>
                <div class="text-md-end mt-3">
                    <p class="fs-5 text-muted mb-1">Mevcut Bakiyeniz:</p>
                    <p class="fs-1 fw-bold text-success"><?php echo number_format($user_balance, 2); ?> TL</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-5 mb-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-primary text-white"><h4 class="mb-0">Test Bakiye Yükle</h4></div>
            <div class="card-body">
                <p class="text-muted">Bu bir test platformudur. Gerçek bir ödeme alınmaz. Hesabınıza sanal bakiye ekleyebilirsiniz.</p>
                
                <form action="my_tickets.php" method="POST">
                    <?php generate_csrf_input(); ?>
                
                    <input type="hidden" name="action" value="add_balance">
                    <div class="mb-3">
                        <label for="amount" class="form-label fw-bold">Yüklenecek Tutar (TL):</label>
                        <input type="number" class="form-control form-control-lg" id="amount" name="amount" min="1" step="0.01" placeholder="örn: 500" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg w-100">Bakiyeyi Ekle</button>
                </form>
            </div>
        </div>
    </div>
</div>


<h3 class="mt-4">Biletlerim</h3>
<div class="table-responsive">
    <table class="table table-striped table-hover align-middle mt-3 border">
        <thead class="table-light">
            <tr>
                <th scope="col">Bilet ID</th>
                <th scope="col">Firma</th>
                <th scope="col">Güzergah</th>
                <th scope="col">Kalkış Zamanı</th>
                <th scope="col">Koltuk</th>
                <th scope="col">Durum</th>
                <th scope="col">İşlemler</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($tickets)): ?>
                <tr><td colspan="7" class="text-center p-4">Henüz satın alınmış biletiniz bulunmamaktadır.</td></tr>
            <?php else: ?>
                <?php 
                $current_time_for_check = time();
                $one_hour = 3600;
                ?>
                <?php foreach ($tickets as $ticket): ?>
                    <tr>
                        <td><strong>#<?php echo $ticket->id; ?></strong></td>
                        <td><?php echo htmlspecialchars($ticket->company_name); ?></td>
                        <td><?php echo htmlspecialchars($ticket->origin); ?> &rarr; <?php echo htmlspecialchars($ticket->destination); ?></td>
                        <td><?php echo date('d.m.Y H:i', strtotime($ticket->departure_time)); ?></td>
                        <td><?php echo $ticket->seat_number; ?></td>
                        <td>
                            <?php if ($ticket->status == 'Active'): ?><span class="badge bg-success">Aktif</span><?php else: ?><span class="badge bg-danger">İptal Edildi</span><?php endif; ?>
                        </td>
                        <td>
                            <a href="generate_pdf.php?ticket_id=<?php echo $ticket->id; ?>" target="_blank" class="btn btn-info btn-sm">PDF İndir</a>
                            
                            <?php 
                            // İptal Et Butonu (Sadece Aktifse VE 1 saatten fazla varsa)
                            $can_cancel = false;
                            if ($ticket->status == 'Active') {
                                $departure_time = strtotime($ticket->departure_time);
                                if (($departure_time - $current_time_for_check) > $one_hour) {
                                    $can_cancel = true;
                                }
                            }
                            
                            if ($can_cancel): 
                            ?>
                                <form action="my_tickets.php" method="POST" style="display: inline-block; margin-left: 5px;">
                                    <?php generate_csrf_input(); ?>
                                
                                    <input type="hidden" name="action" value="cancel_ticket">
                                    <input type="hidden" name="ticket_id" value="<?php echo $ticket->id; ?>">
                                    <button type="submit" 
                                            class="btn btn-danger btn-sm"
                                            onclick="return confirm('Bu bileti iptal etmek istediğinizden emin misiniz? <?php echo $ticket->purchase_price; ?> TL bakiyenize iade edilecektir.');">
                                        İptal Et
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
include 'footer.php'; // Bootstrap JS
?>