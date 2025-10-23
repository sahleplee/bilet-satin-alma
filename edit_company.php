<?php
include 'header.php';
include 'auth.php'; // CSRF fonksiyonları dahil
require_role(['Admin']); 

$company_id = intval($_GET['id'] ?? 0);
if ($company_id <= 0) { header('Location: admin_panel.php'); exit; }

$stmt = $pdo->prepare("SELECT * FROM Companies WHERE id = ?"); $stmt->execute([$company_id]);
$company = $stmt->fetch();
if (!$company) { header('Location: admin_panel.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // CSRF Token Doğrulama
    validate_csrf_token();
    
    $name = trim($_POST['name']);
    if (empty($name)) { $error_message = 'Firma adı boş olamaz.'; } 
    else {
        $stmt = $pdo->prepare("UPDATE Companies SET name = ? WHERE id = ?");
        $stmt->execute([$name, $company_id]);
        header('Location: admin_panel.php?update_success=1'); 
        exit;
    }
}
?>

<a href="admin_panel.php" class="btn btn-secondary btn-sm mb-3">&larr; Admin Paneline Geri Dön</a>
<h2 class="mb-4">Firma Düzenle</h2>

<?php if (isset($error_message)): ?><div class="alert alert-danger"><?php echo $error_message; ?></div><?php endif; ?>
<?php if (isset($_GET['update_success'])): ?><div class="alert alert-success">Firma başarıyla güncellendi.</div><?php endif; ?>

<form action="edit_company.php?id=<?php echo $company->id; ?>" method="POST">
    <?php generate_csrf_input(); ?>
    <div class="mb-3">
        <label for="name" class="form-label">Firma Adı:</label>
        <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($company->name); ?>" required>
    </div>
    <button type="submit" class="btn btn-primary">Güncelle</button>
</form>

<?php include 'footer.php'; ?>