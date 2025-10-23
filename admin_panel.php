<?php
include 'header.php'; // DB, Session, Bootstrap CSS, Navigasyon
include 'auth.php';   // CSRF fonksiyonları dahil edildi
// Bu sayfaya sadece 'Admin' rolü erişebilir
require_role(['Admin']);

$error_message = '';
$success_message = '';

// --- İŞLEM KONTROLÜ (POST) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // === YENİ EKLENEN CSRF TOKEN DOĞRULAMASI ===
    validate_csrf_token();
    // =========================================
    
    $action = $_POST['action'] ?? null;
    try {
        // (Geri kalan tüm POST mantığı aynı)
        if ($action == 'add_company') {
            $name = trim($_POST['name']);
            if (empty($name)) { $error_message = 'Firma adı boş olamaz.'; } 
            else { $stmt = $pdo->prepare("INSERT INTO Companies (name) VALUES (?)"); $stmt->execute([$name]); $success_message = 'Yeni firma başarıyla eklendi.'; }
        } elseif ($action == 'add_firma_admin') {
            $fullname = trim($_POST['fullname']); $email = trim($_POST['email']); $password = $_POST['password']; $company_id = intval($_POST['company_id']);
            if (empty($fullname) || empty($email) || empty($password) || $company_id <= 0) { $error_message = 'Tüm alanlar (ve geçerli bir firma) zorunludur.'; } 
            elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $error_message = 'Geçersiz e-posta formatı.'; } 
            else { $hashed_password = password_hash($password, PASSWORD_DEFAULT); $stmt = $pdo->prepare("INSERT INTO Users (fullname, email, password, role, company_id) VALUES (?, ?, ?, 'Firma Admin', ?)"); $stmt->execute([$fullname, $email, $hashed_password, $company_id]); $success_message = 'Yeni Firma Admin kullanıcısı başarıyla oluşturuldu.'; }
        } elseif ($action == 'add_global_coupon') {
            $code = trim($_POST['code']); $discount_rate = floatval($_POST['discount_rate']); $usage_limit = intval($_POST['usage_limit']); $expiry_date = $_POST['expiry_date'];
            if (empty($code) || $discount_rate <= 0 || $usage_limit <= 0 || empty($expiry_date)) { $error_message = 'Tüm kupon bilgileri zorunludur.'; } 
            else { $stmt = $pdo->prepare("INSERT INTO Coupons (code, discount_rate, usage_limit, expiry_date, company_id) VALUES (?, ?, ?, ?, NULL)"); $stmt->execute([$code, $discount_rate, $usage_limit, $expiry_date]); $success_message = 'Yeni global kupon başarıyla oluşturuldu.'; }
        }
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { $error_message = 'Bu e-posta, firma adı veya kupon kodu zaten mevcut.'; } 
        else { $error_message = 'İşlem sırasında bir hata oluştu: ' . $e->getMessage(); }
    }
}

// --- VERİ ÇEKME (GET) ---
$companies = $pdo->query("SELECT * FROM Companies ORDER BY name ASC")->fetchAll();
$firma_admins = $pdo->query("SELECT U.*, C.name AS company_name FROM Users AS U LEFT JOIN Companies AS C ON U.company_id = C.id WHERE U.role = 'Firma Admin'")->fetchAll();
$global_coupons = $pdo->query("SELECT * FROM Coupons WHERE company_id IS NULL ORDER BY expiry_date DESC")->fetchAll();

?>

<h2 class="mb-4">Admin Paneli</h2>
<?php if ($error_message): ?><div class="alert alert-danger"><?php echo $error_message; ?></div><?php endif; ?>
<?php if ($success_message): ?><div class="alert alert-success"><?php echo $success_message; ?></div><?php endif; ?>

<div class="card shadow-sm mb-4">
    <div class="card-header"><h4 class="mb-0">1. Otobüs Firmaları Yönetimi</h4></div>
    <div class="card-body">
        <h5 class="card-title">Yeni Firma Ekle</h5>
        <form action="admin_panel.php" method="POST" class="row g-3">
            <?php generate_csrf_input(); ?>
            <input type="hidden" name="action" value="add_company">
            <div class="col-md-8">
                <label for="name" class="form-label">Firma Adı:</label>
                <input type="text" id="name" name="name" class="form-control" required>
            </div>
            <div class="col-md-4 align-self-end">
                <button type="submit" class="btn btn-primary w-100">Firma Ekle</button>
            </div>
        </form>
        
        <hr class="my-4">
        <h5 class="card-title">Mevcut Firmalar</h5>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle border">
                <thead class="table-light"><tr><th>ID</th><th>Firma Adı</th><th>İşlemler</th></tr></thead>
                <tbody>
                    <?php foreach ($companies as $company): ?>
                    <tr>
                        <td><?php echo $company->id; ?></td><td><?php echo htmlspecialchars($company->name); ?></td>
                        <td>
                            <a href="edit_company.php?id=<?php echo $company->id; ?>" class="btn btn-outline-primary btn-sm">Düzenle</a>
                            <a href="delete_handler.php?type=company&id=<?php echo $company->id; ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Bu firmayı silmek istediğinizden emin misiniz? (Bağlı seferler ve adminler varsa silinemez.)');">Sil</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header"><h4 class="mb-0">2. Firma Admin Kullanıcıları Yönetimi</h4></div>
    <div class="card-body">
        <h5 class="card-title">Yeni Firma Admin Ekle</h5>
        <form action="admin_panel.php" method="POST">
            <?php generate_csrf_input(); ?>
            <input type="hidden" name="action" value="add_firma_admin">
            <div class="row g-3 mb-3">
                <div class="col-md-6"><label for="fullname" class="form-label">Ad Soyad:</label> <input type="text" id="fullname" name="fullname" class="form-control" required></div>
                <div class="col-md-6"><label for="email" class="form-label">E-posta:</label> <input type="email" id="email" name="email" class="form-control" required></div>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-6"><label for="password" class="form-label">Şifre (Geçici):</label> <input type="password" id="password" name="password" class="form-control" required></div>
                <div class="col-md-6">
                    <label for="company_id" class="form-label">Atanan Firma:</label>
                    <select name="company_id" id="company_id" class="form-select" required>
                        <option value="">-- Firma Seçin --</option>
                        <?php foreach ($companies as $company): ?>
                        <option value="<?php echo $company->id; ?>"><?php echo htmlspecialchars($company->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Firma Admin Ekle</button>
        </form>

        <hr class="my-4">
        <h5 class="card-title">Mevcut Firma Adminleri</h5>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle border">
                <thead class="table-light"><tr><th>ID</th><th>Ad Soyad</th><th>E-posta</th><th>Atalı Firma</th><th>İşlemler</th></tr></thead>
                <tbody>
                    <?php foreach ($firma_admins as $admin): ?>
                    <tr>
                        <td><?php echo $admin->id; ?></td><td><?php echo htmlspecialchars($admin->fullname); ?></td><td><?php echo htmlspecialchars($admin->email); ?></td>
                        <td>(ID: <?php echo $admin->company_id; ?>) <?php echo htmlspecialchars($admin->company_name); ?></td>
                        <td>
                            <a href="edit_firma_admin.php?id=<?php echo $admin->id; ?>" class="btn btn-outline-primary btn-sm">Düzenle</a>
                            <a href="delete_handler.php?type=firma_admin&id=<?php echo $admin->id; ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Bu kullanıcıyı silmek istediğinizden emin misiniz?');">Sil</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header"><h4 class="mb-0">3. Global İndirim Kuponları Yönetimi</h4></div>
    <div class="card-body">
        <h5 class="card-title">Yeni Global Kupon Ekle</h5>
        <form action="admin_panel.php" method="POST">
            <?php generate_csrf_input(); ?>
            <input type="hidden" name="action" value="add_global_coupon">
            <div class="row g-3 mb-3">
                <div class="col-md-6"><label for="g_code" class="form-label">Kod:</label> <input type="text" id="g_code" name="code" class="form-control" required></div>
                <div class="col-md-6"><label for="g_rate" class="form-label">Oran (%):</label> <input type="number" id="g_rate" name="discount_rate" class="form-control" step="0.1" min="1" max="100" required></div>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-6"><label for="g_limit" class="form-label">Limit:</label> <input type="number" id="g_limit" name="usage_limit" class="form-control" min="1" required></div>
                <div class="col-md-6"><label for="g_expiry" class="form-label">Son Tarih:</label> <input type="date" id="g_expiry" name="expiry_date" class="form-control" required></div>
            </div>
            <button type="submit" class="btn btn-primary">Global Kupon Ekle</button>
        </form>

        <hr class="my-4">
        <h5 class="card-title">Mevcut Global Kuponlar</h5>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle border">
                <thead class="table-light"><tr><th>Kod</th><th>Oran</th><th>Limit/Kullanılan</th><th>Son Tarih</th><th>İşlemler</th></tr></thead>
                <tbody>
                    <?php foreach ($global_coupons as $coupon): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($coupon->code); ?></td><td>% <?php echo $coupon->discount_rate; ?></td>
                        <td><?php echo $coupon->usage_limit; ?> / <?php echo $coupon->usage_count; ?></td><td><?php echo date('d.m.Y', strtotime($coupon->expiry_date)); ?></td>
                        <td>
                            <a href="edit_coupon.php?id=<?php echo $coupon->id; ?>" class="btn btn-outline-primary btn-sm">Düzenle</a>
                            <a href="delete_handler.php?type=coupon&id=<?php echo $coupon->id; ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Bu kuponu silmek istediğinizden emin misiniz?');">Sil</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
include 'footer.php';
?>