<?php
include 'header.php';
include 'auth.php'; // CSRF fonksiyonları dahil
require_role(['Admin']); 

$admin_id = intval($_GET['id'] ?? 0);
if ($admin_id <= 0) { header('Location: admin_panel.php'); exit; }

$stmt = $pdo->prepare("SELECT * FROM Users WHERE id = ? AND role = 'Firma Admin'");
$stmt->execute([$admin_id]); $admin_user = $stmt->fetch();
if (!$admin_user) { header('Location: admin_panel.php'); exit; }

$companies = $pdo->query("SELECT * FROM Companies ORDER BY name ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // CSRF Token Doğrulama
    validate_csrf_token();
    
    $fullname = trim($_POST['fullname']); $email = trim($_POST['email']);
    $company_id = intval($_POST['company_id']); $password = $_POST['password']; 
    try {
        if (empty($fullname) || empty($email) || $company_id <= 0) { $error_message = 'Ad Soyad, E-posta ve Atanan Firma zorunludur.'; } 
        else {
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE Users SET fullname = ?, email = ?, company_id = ?, password = ? WHERE id = ?");
                $stmt->execute([$fullname, $email, $company_id, $hashed_password, $admin_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE Users SET fullname = ?, email = ?, company_id = ? WHERE id = ?");
                $stmt->execute([$fullname, $email, $company_id, $admin_id]);
            }
            header('Location: admin_panel.php?update_success=1'); 
            exit;
        }
    } catch (PDOException $e) { $error_message = 'Güncelleme hatası (E-posta zaten kullanılıyor olabilir): ' . $e->getMessage(); }
}
?>

<a href="admin_panel.php" class="btn btn-secondary btn-sm mb-3">&larr; Admin Paneline Geri Dön</a>
<h2 class="mb-4">Firma Admin Düzenle</h2>

<?php if (isset($error_message)): ?><div class="alert alert-danger"><?php echo $error_message; ?></div><?php endif; ?>
<?php if (isset($_GET['update_success'])): ?><div class="alert alert-success">Firma Admin başarıyla güncellendi.</div><?php endif; ?>

<form action="edit_firma_admin.php?id=<?php echo $admin_user->id; ?>" method="POST">
    <?php generate_csrf_input(); ?>
    <div class="mb-3">
        <label for="fullname" class="form-label">Ad Soyad:</label>
        <input type="text" id="fullname" name="fullname" class="form-control" value="<?php echo htmlspecialchars($admin_user->fullname); ?>" required>
    </div>
    <div class="mb-3">
        <label for="email" class="form-label">E-posta:</label>
        <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($admin_user->email); ?>" required>
    </div>
    <div class="mb-3">
        <label for="password" class="form-label">Şifre (Değiştirmek istemiyorsanız boş bırakın):</label>
        <input type="password" id="password" name="password" class="form-control">
    </div>
    <div class="mb-3">
        <label for="company_id" class="form-label">Atanan Firma:</label>
        <select name="company_id" id="company_id" class="form-select" required>
            <?php foreach ($companies as $company): ?>
            <option value="<?php echo $company->id; ?>" <?php if ($company->id == $admin_user->company_id) echo 'selected'; ?>>
                <?php echo htmlspecialchars($company->name); ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <button type="submit" class="btn btn-primary">Güncelle</button>
</form>

<?php include 'footer.php'; ?>