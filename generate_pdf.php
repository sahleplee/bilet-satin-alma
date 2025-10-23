<?php
// FPDF kütüphanesini dahil et
require('fpdf.php'); 

include 'config.php';
include 'auth.php';
// Bu sayfaya sadece 'User' (Yolcu) rolü erişebilir
require_role(['User']);

$ticket_id = intval($_GET['ticket_id'] ?? 0);
$user_id = $_SESSION['user_id'];
$ticket = null;

if ($ticket_id <= 0) {
    die('Geçersiz bilet ID\'si.');
}

// --- PDF BİLGİLERİNİ ÇEK ---
try {
    // PDF için gerekli tüm bilgileri (Yolcu Adı, Firma Adı, Sefer Detayları) çek
    $stmt = $pdo->prepare(
        "SELECT 
            T.*, 
            TR.origin, TR.destination, TR.departure_time, TR.arrival_time,
            C.name AS company_name,
            U.fullname AS user_fullname
         FROM Tickets AS T
         JOIN Trips AS TR ON T.trip_id = TR.id
         JOIN Companies AS C ON TR.company_id = C.id
         JOIN Users AS U ON T.user_id = U.id
         WHERE T.id = ? AND T.user_id = ?" // GÜVENLİK: Sadece kendi biletini indirebilsin
    );
    $stmt->execute([$ticket_id, $user_id]);
    $ticket = $stmt->fetch();

    if (!$ticket) {
        die('Bilet bulunamadı veya bu bileti görüntüleme yetkiniz yok.');
    }
} catch (PDOException $e) {
    die('Veritabanı hatası: ' . $e->getMessage());
}


// --- PDF OLUŞTURMA BAŞLANGICI ---

/**
 * DÜZELTİLMİŞ TÜRKÇE KARAKTER FONKSİYONU
 * FPDF'nin anlayacağı Latin-1 (ISO-8859-1) formatına dönüştürür.
 * TRANSLIT, 'İ', 'ş', 'ğ' gibi karakterleri 'I', 's', 'g' gibi en yakın benzerlerine dönüştürür.
 */
function tr_converter($text) {
    return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text);
}


// PDF'i oluşturalım (P: Dikey, mm: milimetre, A4: Sayfa boyutu)
$pdf = new FPDF('P', 'mm', 'A4');
$pdf->AddPage();

// --- BİLET BAŞLIĞI ---
$pdf->SetFont('Arial', 'B', 20); // Font: Arial, Kalın, 20px
$pdf->SetFillColor(230, 230, 230); // Arka plan için açık gri
$pdf->Cell(0, 15, tr_converter('OTOBÜS BİLETİ'), 0, 1, 'C', true); // 0: tüm genişlik, 15: yükseklik, 0: kenarlık yok, 1: satır atla, C: ortalı, true: dolgu
$pdf->Ln(5); // 5mm boşluk

// --- FİRMA ADI ---
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 12, tr_converter(htmlspecialchars($ticket->company_name)), 0, 1, 'C');
$pdf->Ln(5);

// --- BİLET ÇERÇEVESİ ---
$current_y_start = $pdf->GetY(); // Çerçevenin Y başlangıcı
$pdf->SetLineWidth(0.5); // Çizgi kalınlığı
$pdf->SetDrawColor(150, 150, 150); // Çizgi rengi (gri)
// (x, y, genişlik, yükseklik)
$pdf->Rect(10, $current_y_start, 190, 95); // Biletin etrafına büyük bir dikdörtgen çiz

$pdf->Ln(5); // Çerçeveden sonra 5mm boşluk
$current_y = $pdf->GetY(); // İçeriğin Y başlangıcı


// --- DÜZELTİLMİŞ 2 SÜTUNLU YAPI ---
$left_col_x = 15;   // Sol kenar boşluğu
$right_col_x = 105; // Sağ sütun başlangıcı
$line_height = 7;   // Satır yüksekliği

// --- Satır 1: Başlıklar ---
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetXY($left_col_x, $current_y);
$pdf->Cell(90, $line_height, tr_converter('YOLCU BİLGİLERİ'), 0, 0, 'L');
$pdf->SetXY($right_col_x, $current_y);
$pdf->Cell(90, $line_height, tr_converter('SEFER BİLGİLERİ'), 0, 1, 'L'); // 1: Satır atla
$current_y += $line_height; // Y pozisyonunu manuel olarak artır

// --- Satır 2: Etiketler ---
$pdf->SetFont('Arial', '', 11);
$pdf->SetXY($left_col_x, $current_y);
$pdf->Cell(90, $line_height, tr_converter('Adı Soyadı:'), 0, 0, 'L');
$pdf->SetXY($right_col_x, $current_y);
$pdf->Cell(90, $line_height, tr_converter('Güzergah:'), 0, 1, 'L');
$current_y += $line_height;

// --- Satır 3: Değerler ---
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetXY($left_col_x, $current_y);
$pdf->Cell(90, $line_height, tr_converter(htmlspecialchars($ticket->user_fullname)), 0, 0, 'L');
$pdf->SetXY($right_col_x, $current_y);
// Güzergahın taşmaması için 90mm genişlik veriyoruz
$pdf->Cell(90, $line_height, tr_converter(htmlspecialchars($ticket->origin) . ' -> ' . htmlspecialchars($ticket->destination)), 0, 1, 'L');
$current_y += $line_height;

// --- DÜZELTİLMİŞ DETAY SATIRI (Kalkış, Koltuk, Fiyat) ---
$pdf->SetY($current_y + 8); // Sütunlardan sonra 8mm boşluk bırak
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(60, 8, tr_converter('KALKIŞ ZAMANI'), 0, 0, 'L'); // 0: Satır atlama
$pdf->Cell(60, 8, tr_converter('KOLTUK NO'), 0, 0, 'C'); // 0: Satır atlama
$pdf->Cell(60, 8, tr_converter('ÖDENEN TUTAR'), 0, 1, 'R'); // 1: Satır atla

$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(60, 10, date('d.m.Y H:i', strtotime($ticket->departure_time)), 0, 0, 'L');
$pdf->Cell(60, 10, $ticket->seat_number, 0, 0, 'C');
$pdf->Cell(60, 10, $ticket->purchase_price . ' TL', 0, 1, 'R');

// --- Çizgi Ayırıcı ---
$pdf->Line(15, $pdf->GetY() + 5, 195, $pdf->GetY() + 5);
$pdf->Ln(10);


// --- BİLET DURUMU (Aktif / İptal) ---
$pdf->SetXY(15, $pdf->GetY());
$pdf->SetFont('Arial', 'B', 18);

if ($ticket->status == 'Active') {
    $pdf->SetTextColor(0, 120, 0); // Koyu Yeşil
    $pdf->Cell(180, 10, tr_converter('BİLET AKTİF'), 0, 1, 'C');
} else {
    $pdf->SetTextColor(200, 0, 0); // Kırmızı
    $pdf->Cell(180, 10, tr_converter('BİLET İPTAL EDİLDİ'), 0, 1, 'C');
}
$pdf->SetTextColor(0, 0, 0); // Rengi sıfırla

$pdf->Ln(10); // Alt boşluk

// --- ALT MESAJ ---
$pdf->SetFont('Arial', 'I', 12);
$pdf->Cell(0, 10, tr_converter('İyi yolculuklar dileriz.'), 0, 1, 'C');


// ==========================================================
// ===          YENİ EKLENEN LOGO ALANI                   ===
// ==========================================================
$pdf->Ln(50); // "İyi yolculuklar" yazısından sonra büyük bir boşluk bırak

// Logo genişliği 80mm (8cm)
$logo_width = 80;
// A4 genişliği 210mm'dir. Ortalamak için: (210 - 80) / 2 = 65
$x_pos = (210 - $logo_width) / 2;
// Y pozisyonunu manuel olarak sayfanın altına yakın ayarlayalım
// (A4 yüksekliği 297mm'dir. 230mm iyi bir alt pozisyondur)
$y_pos = $pdf->GetY(); // Mevcut Y pozisyonunu al

// PNG dosyasını (şeffaflığı koruyarak) yerleştir
$pdf->Image('Tulaşım.png', $x_pos, $y_pos, $logo_width, 0, 'PNG');
// ==========================================================


// PDF'i tarayıcıya 'D' (Download) parametresi ile gönder
$pdf->Output('D', 'Bilet_ID_' . $ticket->id . '.pdf');

?>