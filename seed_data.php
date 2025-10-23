<?php
include 'config.php';

// SQL komutlarında hata olursa işlemi durdur ve hatayı göster
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "<h1>Test Verisi Oluşturma (Seeding) Başlatıldı...</h1>";

try {
    // Veritabanı tutarlılığı için tüm işlemleri bir transaction içine alalım
    $pdo->beginTransaction();

    // 1. FİRMALAR (Companies) [cite: 165, 205]
    $pdo->exec("INSERT OR IGNORE INTO Companies (id, name) VALUES (1, 'Kamil Koç')");
    $pdo->exec("INSERT OR IGNORE INTO Companies (id, name) VALUES (2, 'Metro Turizm')");
    $pdo->exec("INSERT OR IGNORE INTO Companies (id, name) VALUES (3, 'Pamukkale')");
    $pdo->exec("INSERT OR IGNORE INTO Companies (id, name) VALUES (4, 'Lüks Artvin')");
    echo "<p>+ 4 adet firma (Kamil Koç, Metro, Pamukkale, Lüks Artvin) eklendi/kontrol edildi.</p>";

    // 2. KULLANICILAR (Users) 
    // (Şifreler 'seed_data.php'de yeniden hashleniyor, 'admin123', 'kamil123' vb.)
    $pass_admin = password_hash('admin123', PASSWORD_DEFAULT);
    $pass_kamil_admin = password_hash('kamil123', PASSWORD_DEFAULT);
    $pass_metro_admin = password_hash('metro123', PASSWORD_DEFAULT);
    $pass_luks_admin = password_hash('luks123', PASSWORD_DEFAULT);
    $pass_yolcu = password_hash('yolcu123', PASSWORD_DEFAULT);

    // Admin (Rol: Admin)
    $pdo->exec("INSERT OR IGNORE INTO Users (id, fullname, email, password, role, balance, company_id) 
                VALUES (1, 'Admin Kullanıcı', 'admin@mail.com', '$pass_admin', 'Admin', 0.0, NULL)");
    // Kamil Koç Admin (ID: 1)
    $pdo->exec("INSERT OR IGNORE INTO Users (id, fullname, email, password, role, balance, company_id) 
                VALUES (2, 'Kamil Destek', 'kamil@mail.com', '$pass_kamil_admin', 'Firma Admin', 0.0, 1)");
    // Metro Admin (ID: 2)
    $pdo->exec("INSERT OR IGNORE INTO Users (id, fullname, email, password, role, balance, company_id) 
                VALUES (3, 'Metro Destek', 'metro@mail.com', '$pass_metro_admin', 'Firma Admin', 0.0, 2)");
    // Yolcu (ID: 4)
    $pdo->exec("INSERT OR IGNORE INTO Users (id, fullname, email, password, role, balance, company_id) 
                VALUES (4, 'Ali Veli', 'yolcu@mail.com', '$pass_yolcu', 'User', 5000.0, NULL)");
    // Lüks Artvin Admin (ID: 5)
    $pdo->exec("INSERT OR IGNORE INTO Users (id, fullname, email, password, role, balance, company_id) 
                VALUES (5, 'Lüks Admin', 'luks@mail.com', '$pass_luks_admin', 'Firma Admin', 0.0, 4)");

    echo "<p>+ 5 adet test kullanıcısı (Admin, 3x Firma Admin, 1x Yolcu) eklendi/kontrol edildi.</p>";

    // 3. SEFERLER (Trips) 
    // (Fiyatlar dosyadaki bozuk verilerden tahmin edilmiştir)
    $pdo->exec("INSERT OR IGNORE INTO Trips (id, company_id, origin, destination, departure_time, arrival_time, price, total_seats)
                VALUES (1, 1, 'İstanbul (Esenler)', 'Ankara (AŞTİ)', '2025-10-25 10:00:00', '2025-10-25 16:00:00', 500.0, 40)");
    $pdo->exec("INSERT OR IGNORE INTO Trips (id, company_id, origin, destination, departure_time, arrival_time, price, total_seats)
                VALUES (2, 1, 'Trabzon', 'İstanbul (Esenler)', '2025-10-26 21:00:00', '2025-10-27 15:00:00', 850.0, 40)");
    $pdo->exec("INSERT OR IGNORE INTO Trips (id, company_id, origin, destination, departure_time, arrival_time, price, total_seats)
                VALUES (3, 2, 'Ankara (AŞTİ)', 'İzmir', '2025-10-25 12:00:00', '2025-10-25 20:00:00', 600.0, 42)");
    $pdo->exec("INSERT OR IGNORE INTO Trips (id, company_id, origin, destination, departure_time, arrival_time, price, total_seats)
                VALUES (4, 3, 'Antalya', 'Bursa', '2025-10-27 09:00:00', '2025-10-27 17:00:00', 700.0, 38)");
    $pdo->exec("INSERT OR IGNORE INTO Trips (id, company_id, origin, destination, departure_time, arrival_time, price, total_seats)
                VALUES (5, 4, 'Artvin', 'Trabzon', '2025-10-30 17:00:00', '2025-10-30 21:00:00', 300.0, 33)");
    
    echo "<p>+ 5 adet test seferi (2x Kamil Koç, 1x Metro, 1x Pamukkale, 1x Lüks Artvin) eklendi/kontrol edildi.</p>";

    // 4. KUPONLAR (Coupons) 
    $pdo->exec("INSERT OR IGNORE INTO Coupons (id, code, discount_rate, usage_limit, expiry_date, company_id)
                VALUES (1, 'GLOBAL10', 10.0, 100, '2025-12-31', NULL)");
    $pdo->exec("INSERT OR IGNORE INTO Coupons (id, code, discount_rate, usage_limit, expiry_date, company_id)
                VALUES (2, 'KAMILOZEL', 20.0, 50, '2025-11-30', 1)"); // Kamil Koç'a (ID:1) bağlı
    $pdo->exec("INSERT OR IGNORE INTO Coupons (id, code, discount_rate, usage_limit, expiry_date, company_id)
                VALUES (3, 'KAMILBABA', 15.0, 10, '2025-11-30', 1)"); // Kamil Koç'a (ID:1) bağlı
    $pdo->exec("INSERT OR IGNORE INTO Coupons (id, code, discount_rate, usage_limit, expiry_date, company_id)
                VALUES (4, 'TULAŞIM10', 10.0, 100, '2026-01-01', NULL)"); // Global
    $pdo->exec("INSERT OR IGNORE INTO Coupons (id, code, discount_rate, usage_limit, expiry_date, company_id)
                VALUES (5, 'LUKSEDIT', 10.0, 50, '2025-10-30', 4)"); // Lüks Artvin'e (ID:4) bağlı

    echo "<p>+ 5 adet test kuponu (2x Global, 2x Kamil Koç, 1x Lüks Artvin) eklendi/kontrol edildi.</p>";

    // 5. BİLETLER (Tickets) [cite: 282, 283]
    // Dosyadaki bozuk veriler yerine, bu verilere uygun örnek biletler:
    
    // 1. Ali Veli (User ID:4) -> İstanbul-Ankara (Trip ID:1) -> Koltuk 8, Status: Canceled
    $pdo->exec("INSERT OR IGNORE INTO Tickets (id, user_id, trip_id, seat_number, status, purchase_price)
                VALUES (1, 4, 1, 8, 'Canceled', 500.0)");
    
    // 2. Ali Veli (User ID:4) -> İstanbul-Ankara (Trip ID:1) -> Koltuk 9, Status: Active
    $pdo->exec("INSERT OR IGNORE INTO Tickets (id, user_id, trip_id, seat_number, status, purchase_price)
                VALUES (2, 4, 1, 9, 'Active', 500.0)");
    
    // 3. Ali Veli (User ID:4) -> Ankara-İzmir (Trip ID:3) -> Koltuk 38, Status: Active (GLOBAL10 kuponuyla 600->540)
    $pdo->exec("INSERT OR IGNORE INTO Tickets (id, user_id, trip_id, seat_number, status, purchase_price)
                VALUES (3, 4, 3, 38, 'Active', 540.0)");

    echo "<p>+ 3 adet test bileti (2 Aktif, 1 İptal) eklendi/kontrol edildi.</p>";
    
    // Tabloların ID sayaçlarını güncelle
    $pdo->exec("INSERT OR IGNORE INTO sqlite_sequence (name, seq) VALUES ('Companies', 4)");
    $pdo->exec("INSERT OR IGNORE INTO sqlite_sequence (name, seq) VALUES ('Users', 5)");
    $pdo->exec("INSERT OR IGNORE INTO sqlite_sequence (name, seq) VALUES ('Trips', 5)");
    $pdo->exec("INSERT OR IGNORE INTO sqlite_sequence (name, seq) VALUES ('Coupons', 5)");
    $pdo->exec("INSERT OR IGNORE INTO sqlite_sequence (name, seq) VALUES ('Tickets', 3)");

    // Tüm işlemleri onayla
    $pdo->commit();
    
    echo "<hr><h2>VERİTABANI BAŞARIYLA DOLDURULDU (Sizin Verilerinizle)!</h2>";
    echo "<p>Artık <a href='index.php'>ANA SAYFAYA GİT</a> ve test et.</p>";
    echo "<p style='color:red;'><b>GÜVENLİK UYARISI:</b> Testi tamamladıktan sonra 'setup_db.php' ve 'seed_data.php' dosyalarını silin.</p>";


} catch (Exception $e) {
    // Hata olursa tüm işlemleri geri al
    $pdo->rollBack();
    echo "<h1>HATA!</h1>";
    echo "<p>Veritabanı doldurulurken bir hata oluştu: " . $e->getMessage() . "</p>";
}
?>