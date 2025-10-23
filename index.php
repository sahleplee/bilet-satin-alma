<?php
include 'header.php'; // Temel header'ı (ve style.css bağlantısını) dahil et

// Sefer arama mantığı
$origin = $_GET['origin'] ?? null;
$destination = $_GET['destination'] ?? null;
$trips = [];
$search_error = '';

// Arama yapıldı mı? (Bu değişkeni JS'de kullanacağız)
$searchPerformed = ($origin && $destination); 

if ($searchPerformed) {
    try {
        if (!isset($pdo) || $pdo === null) { throw new Exception("Veritabanı bağlantısı kurulamadı."); }
        $stmt = $pdo->prepare("SELECT T.*, C.name AS company_name FROM Trips AS T JOIN Companies AS C ON T.company_id = C.id WHERE T.origin LIKE ? AND T.destination LIKE ? AND T.departure_time > datetime('now', 'localtime') ORDER BY T.departure_time ASC");
        $stmt->execute(['%' . $origin . '%', '%' . $destination . '%']);
        $trips = $stmt->fetchAll();
        if (empty($trips)) { $search_error = 'Bu kriterlere uygun sefer bulunamadı.'; }
    } catch (Exception $e) { $search_error = 'Seferler aranırken bir hata oluştu: ' . $e->getMessage(); }
}
?>

<div class="hero-section text-center text-white d-flex align-items-center justify-content-center mb-5 fade-in-slow">
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <h1 class="display-4 fw-bold">Türkiye'nin Dört Bir Yanına Ulaşın</h1>
        <p class="lead fs-4">Kolayca otobüs biletinizi bulun ve yolculuğa başlayın!</p>
    </div>
</div>
<div class="main-content-card fade-in">

    <div class="motif-border">
        <h2 class="mb-4 text-center" style="color: var(--color-turquoise);">Sefer Ara</h2>
        <form action="index.php" method="GET" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label for="origin" class="form-label fw-bold">Kalkış Yeri:</label>
                <input type="text" class="form-control form-control-lg" id="origin" name="origin" value="<?php echo htmlspecialchars($origin ?? ''); ?>" required>
            </div>
            <div class="col-md-5">
                <label for="destination" class="form-label fw-bold">Varış Yeri:</label>
                <input type="text" class="form-control form-control-lg" id="destination" name="destination" value="<?php echo htmlspecialchars($destination ?? ''); ?>" required>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-lg w-100">Sefer Bul</button>
            </div>
        </form>
    </div>

    <?php if ($search_error): ?>
        <div class="alert alert-danger mt-4"><?php echo $search_error; ?></div>
    <?php endif; ?>

    <?php if (!empty($trips)): ?>
        <h3 class="mb-3 mt-4" id="searchResultsSection">Arama Sonuçları</h3>
        <div class="list-group">
            <?php foreach ($trips as $trip): ?>
                <div class="list-group-item list-group-item-action mb-3 p-3 border-0 rounded shadow-sm card-hover" style="border-radius: var(--border-radius-base) !important;">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h5 class="mb-1 fw-bold" style="color: var(--color-red);"><?php echo htmlspecialchars($trip->company_name); ?></h5>
                            <p class="mb-1"><strong><?php echo htmlspecialchars($trip->origin); ?></strong> &rarr; <strong><?php echo htmlspecialchars($trip->destination); ?></strong></p>
                            <small class="text-muted">Kalkış: <?php echo date('d.m.Y H:i', strtotime($trip->departure_time)); ?> | Varış: <?php echo date('d.m.Y H:i', strtotime($trip->arrival_time)); ?></small>
                        </div>
                        <div class="col-md-6 text-md-end mt-3 mt-md-0">
                            <h4 class="fw-bold mb-3" style="color: var(--color-turquoise);"><?php echo htmlspecialchars($trip->price); ?> TL</h4>
                            <a href="trip_details.php?trip_id=<?php echo $trip->id; ?>" class="btn btn-success btn-lg">Detayları Gör / Bilet Al</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php elseif (isset($origin) && !$search_error): ?>
        <div class="alert alert-warning mt-4">Belirtilen rotada aktif sefer bulunamadı.</div>
    <?php elseif (!isset($origin)): ?>
        <div class="alert alert-info mt-4">Lütfen kalkış ve varış noktası seçerek sefer arayın.</div>
    <?php endif; ?>

</div> <script>
    // Sayfa yüklendiğinde çalışacak kod
    document.addEventListener('DOMContentLoaded', () => {
        // PHP tarafından belirlenen $searchPerformed değişkenini kontrol et
        const wasSearchPerformed = <?php echo json_encode($searchPerformed); ?>;

        if (wasSearchPerformed) {
            // Arama sonuçları başlığını bul
            const resultsSection = document.getElementById('searchResultsSection');
            
            // Eğer başlık bulunduysa (yani sonuç varsa)
            if (resultsSection) {
                // Oraya doğru yumuşakça kaydır
                resultsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }
    });
</script>
<?php
include 'footer.php'; // Footer'ı dahil et (Bootstrap JS dahil)
?>