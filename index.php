<?php
// Header'ı dahil et (DB, Session, Bootstrap CSS ve Navigasyon)
include 'header.php'; 

// Arama yapıldı mı? (GET ile)
$origin = $_GET['origin'] ?? null;
$destination = $_GET['destination'] ?? null;
$trips = [];
$search_error = '';

if ($origin && $destination) {
    try {
        // Seferleri ve firma adını çekmek için JOIN kullanalım
        // Sadece tarihi geçmemiş (gelecek) seferleri listeleyelim
        $stmt = $pdo->prepare(
            "SELECT T.*, C.name AS company_name 
             FROM Trips AS T
             JOIN Companies AS C ON T.company_id = C.id
             WHERE T.origin LIKE ? AND T.destination LIKE ? AND T.departure_time > datetime('now', 'localtime')
             ORDER BY T.departure_time ASC"
        );
        // LIKE kullanarak kısmi eşleşmeye izin verelim (örn: "İstanbul" "İstanbul (Anadolu)" eşleşebilir)
        $stmt->execute(['%' . $origin . '%', '%' . $destination . '%']);
        $trips = $stmt->fetchAll();

        if (empty($trips)) {
            $search_error = 'Bu kriterlere uygun sefer bulunamadı.';
        }

    } catch (PDOException $e) {
        $search_error = 'Seferler aranırken bir hata oluştu: ' . $e->getMessage();
    }
}
?>

<h2 class="mb-4">Sefer Ara</h2>
<div class="card shadow-sm mb-4" style="background-color: #fcfcfc;">
    <div class="card-body">
        <form action="index.php" method="GET" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label for="origin" class="form-label fw-bold">Kalkış Yeri:</label>
                <input type="text" class="form-control form-control-lg" id="origin" name="origin" 
                       value="<?php echo htmlspecialchars($origin ?? ''); ?>" required>
            </div>
            <div class="col-md-5">
                <label for="destination" class="form-label fw-bold">Varış Yeri:</label>
                <input type="text" class="form-control form-control-lg" id="destination" name="destination" 
                       value="<?php echo htmlspecialchars($destination ?? ''); ?>" required>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-lg w-100">Sefer Bul</button>
            </div>
        </form>
    </div>
</div>

<?php if ($search_error): ?>
    <div class="alert alert-danger" role="alert">
        <?php echo $search_error; ?>
    </div>
<?php endif; ?>

<?php if (!empty($trips)): ?>
    <h3 class="mb-3">Arama Sonuçları</h3>
    
    <div class="list-group">
        <?php foreach ($trips as $trip): ?>
            <div class="list-group-item list-group-item-action mb-3 p-3 border rounded shadow-sm">
                <div class="row align-items-center">
                    
                    <div class="col-md-6">
                        <h5 class="mb-1 text-primary fw-bold"><?php echo htmlspecialchars($trip->company_name); ?></h5>
                        <p class="mb-1">
                            <strong><?php echo htmlspecialchars($trip->origin); ?></strong> &rarr; 
                            <strong><?php echo htmlspecialchars($trip->destination); ?></strong>
                        </p>
                        <small class="text-muted">
                            Kalkış: <?php echo date('d.m.Y H:i', strtotime($trip->departure_time)); ?> | 
                            Varış: <?php echo date('d.m.Y H:i', strtotime($trip->arrival_time)); ?>
                        </small>
                    </div>
                    
                    <div class="col-md-6 text-md-end mt-3 mt-md-0">
                        <h4 class="text-success fw-bold mb-3"><?php echo htmlspecialchars($trip->price); ?> TL</h4>
                        <a href="trip_details.php?trip_id=<?php echo $trip->id; ?>" class="btn btn-success btn-lg">
                            Detayları Gör / Bilet Al
                        </a>
                    </div>

                </div>
            </div>
        <?php endforeach; ?>
    </div>

<?php elseif (isset($origin)): // Arama yapıldı ama sonuç yoksa (hata değilse) ?>
    <?php if (!$search_error): ?>
        <div class="alert alert-warning" role="alert">
            Belirtilen rotada aktif sefer bulunamadı.
        </div>
    <?php endif; ?>
<?php else: // Sayfa ilk açıldığında ?>
    <div class="alert alert-info" role="alert">
        Lütfen kalkış ve varış noktası seçerek sefer arayın.
    </div>
<?php endif; ?>


<?php
// Footer'ı dahil et (Bootstrap JS)
include 'footer.php';
?>