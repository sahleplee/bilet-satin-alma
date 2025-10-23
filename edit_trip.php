<?php
include 'header.php';
include 'auth.php';
require_role(['Firma Admin']);

$company_id = $_SESSION['user_company_id'];
$trip_id = $_GET['id'] ?? null;
$error_message = '';
$success_message = '';
if (!$trip_id) { header('Location: company_admin_panel.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $origin = trim($_POST['origin']); $destination = trim($_POST['destination']); $departure_time = $_POST['departure_time'];
        $arrival_time = $_POST['arrival_time']; $price = floatval($_POST['price']); $total_seats = intval($_POST['total_seats']);
        if (empty($origin) || empty($destination) || empty($departure_time) || empty($arrival_time) || $price <= 0 || $total_seats <= 0) {
            $error_message = 'Tüm sefer bilgilerini (pozitif fiyat ve koltuk sayısı) doğru girdiğinizden emin olun.';
        } else {
            $stmt = $pdo->prepare("UPDATE Trips SET origin = ?, destination = ?, departure_time = ?, arrival_time = ?, price = ?, total_seats = ? WHERE id = ? AND company_id = ?");
            $stmt->execute([$origin, $destination, $departure_time, $arrival_time, $price, $total_seats, $trip_id, $company_id]);
            $success_message = 'Sefer başarıyla güncellendi.';
        }
    } catch (PDOException $e) { $error_message = 'Güncelleme sırasında bir hata oluştu: ' . $e->getMessage(); }
}

try {
    $stmt = $pdo->prepare("SELECT * FROM Trips WHERE id = ? AND company_id = ?");
    $stmt->execute([$trip_id, $company_id]);
    $trip = $stmt->fetch();
    if (!$trip) { header('Location: company_admin_panel.php'); exit; }
} catch (PDOException $e) { die('Sefer bilgisi çekilemedi: ' . $e->getMessage()); }
?>

<a href="company_admin_panel.php" class="btn btn-secondary btn-sm mb-3">&larr; Panele Geri Dön</a>
<h2 class="mb-4">Sefer Düzenle (ID: <?php echo $trip->id; ?>)</h2>

<?php if ($error_message): ?><div class="alert alert-danger"><?php echo $error_message; ?></div><?php endif; ?>
<?php if ($success_message): ?><div class="alert alert-success"><?php echo $success_message; ?></div><?php endif; ?>

<form action="edit_trip.php?id=<?php echo $trip->id; ?>" method="POST">
    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <label for="origin" class="form-label">Kalkış Yeri:</label>
            <input type="text" id="origin" name="origin" class="form-control" value="<?php echo htmlspecialchars($trip->origin); ?>" required>
        </div>
        <div class="col-md-6">
            <label for="destination" class="form-label">Varış Yeri:</label>
            <input type="text" id="destination" name="destination" class="form-control" value="<?php echo htmlspecialchars($trip->destination); ?>" required>
        </div>
    </div>
    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <label for="departure_time" class="form-label">Kalkış Zamanı:</label>
            <input type="datetime-local" id="departure_time" name="departure_time" class="form-control" value="<?php echo date('Y-m-d\TH:i', strtotime($trip->departure_time)); ?>" required>
        </div>
        <div class="col-md-6">
            <label for="arrival_time" class="form-label">Varış Zamanı:</label>
            <input type="datetime-local" id="arrival_time" name="arrival_time" class="form-control" value="<?php echo date('Y-m-d\TH:i', strtotime($trip->arrival_time)); ?>" required>
        </div>
    </div>
    <div class="row g-3">
        <div class="col-md-6">
            <label for="price" class="form-label">Fiyat (TL):</label>
            <input type="number" id="price" name="price" class="form-control" step="0.01" min="1" value="<?php echo $trip->price; ?>" required>
        </div>
        <div class="col-md-6">
            <label for="total_seats" class="form-label">Koltuk Sayısı:</label>
            <input type="number" id="total_seats" name="total_seats" class="form-control" min="1" value="<?php echo $trip->total_seats; ?>" required>
        </div>
    </div>
    <button type="submit" class="btn btn-primary mt-3">Güncelle</button>
</form>

<?php
include 'footer.php';
?>