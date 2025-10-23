<?php
// Veritabanı dosyası
$dbFile = 'database.sqlite';

try {
    // 1. SQLite Veritabanı Bağlantısı (Dosya yoksa oluşturulur)
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Veritabanı bağlantısı başarılı ('$dbFile' oluşturuldu).<br>";

    // 2. Tabloları oluşturacak SQL komutları
    $sqlCommands = [
        // Kullanıcılar Tablosu
        "CREATE TABLE IF NOT EXISTS Users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            fullname TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL,
            role TEXT NOT NULL CHECK(role IN ('User', 'Firma Admin', 'Admin')),
            balance REAL DEFAULT 0.0,
            company_id INTEGER NULL,
            FOREIGN KEY (company_id) REFERENCES Companies(id)
        );",

        // Firmalar Tablosu
        "CREATE TABLE IF NOT EXISTS Companies (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE
        );",

        // Seferler Tablosu
        "CREATE TABLE IF NOT EXISTS Trips (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            company_id INTEGER NOT NULL,
            origin TEXT NOT NULL,
            destination TEXT NOT NULL,
            departure_time DATETIME NOT NULL,
            arrival_time DATETIME NOT NULL,
            price REAL NOT NULL,
            total_seats INTEGER NOT NULL,
            FOREIGN KEY (company_id) REFERENCES Companies(id)
        );",

        // Biletler Tablosu (GÜNCELLENDİ)
        "CREATE TABLE IF NOT EXISTS Tickets (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            trip_id INTEGER NOT NULL,
            seat_number INTEGER NOT NULL,
            status TEXT NOT NULL DEFAULT 'Active' CHECK(status IN ('Active', 'Canceled')),
            purchase_price REAL NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            
            -- Hatalı olan 'UNIQUE(trip_id, seat_number),' kuralı buradan kaldırıldı --
            
            FOREIGN KEY (user_id) REFERENCES Users(id),
            FOREIGN KEY (trip_id) REFERENCES Trips(id)
        );",

        // Kuponlar Tablosu
        "CREATE TABLE IF NOT EXISTS Coupons (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            code TEXT NOT NULL UNIQUE,
            discount_rate REAL NOT NULL,
            usage_limit INTEGER NOT NULL,
            usage_count INTEGER DEFAULT 0,
            expiry_date DATE NOT NULL,
            company_id INTEGER NULL,
            FOREIGN KEY (company_id) REFERENCES Companies(id)
        );"
    ];

    // 3. SQL komutlarını çalıştır
    foreach ($sqlCommands as $command) {
        $pdo->exec($command);
    }

    echo "Tüm tablolar (Hatasız 'Tickets' tablosu dahil) başarıyla oluşturuldu.<br>";
    echo "<b>Adım 2: Veritabanı Kurulumu tamamlandı.</b><br>";
    echo "<p>Şimdi 'seed_data.php' betiğini çalıştırarak verilerinizi yükleyebilirsiniz.</p>";

} catch (PDOException $e) {
    // Hata durumunda
    echo "Veritabanı hatası: " . $e->getMessage();
}
?>