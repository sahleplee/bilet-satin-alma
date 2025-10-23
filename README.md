# TUlaşım - Otobüs Bileti Satın Alma Platformu

Bu proje, PHP, SQLite ve Docker kullanılarak geliştirilmiş bir otobüs bileti satış platformudur. Görev dökümanı gereksinimlerini karşılamak üzere oluşturulmuştur.

**Geliştiren:** [sahleplee](https://github.com/sahleplee)
**Teslim Tarihi:** 24.10.2025

## Kullanılan Teknolojiler

* **Programlama Dili:** PHP 8.1
* **Veritabanı:** SQLite
* **Arayüz:** HTML, CSS, Bootstrap 5
* **PDF Kütüphanesi:** FPDF
* **Paketleme:** Docker

## Kurulum ve Çalıştırma (Docker ile)

Bu proje, Docker kullanılarak kolayca çalıştırılabilir. Bilgisayarınızda Docker Desktop'ın kurulu ve çalışır durumda olduğundan emin olun.

1.  **Projeyi Klonlayın veya İndirin:**
    Bu GitHub deposunu bilgisayarınıza klonlayın veya ZIP olarak indirin.
    ```bash
    git clone https://github.com/sahleplee/bilet-satin-alma.git
    cd bilet-satin-alma 
    ```

2.  **Docker Image'ını Oluşturun:**
    Proje ana dizininde komut satırını açın ve aşağıdaki komutu çalıştırın:
    ```bash
    docker build -t tulasim-bilet .
    ```

3.  **Docker Container'ını Çalıştırın:**
    Aşağıdaki komut ile container'ı başlatın:
    ```bash
    docker run -d -p 8080:80 --name tulasim-app tulasim-bilet
    ```
    * `-p 8080:80`: Bilgisayarınızın 8080 portunu container'ın 80 portuna bağlar. Eğer 8080 portu doluysa, başka bir port deneyin (örn: `-p 8888:80`).

4.  **Uygulamaya Erişin:**
    Web tarayıcınızı açın ve `http://localhost:8080` adresine gidin.

## Veritabanı Kurulumu (Container İçinde)

Container ilk kez başlatıldığında veritabanı boş olacaktır. Uygulamanın çalışması için aşağıdaki adımları **tarayıcınız üzerinden** gerçekleştirin:

1.  **Tabloları Oluşturun:**
    `http://localhost:8080/setup_db.php` adresine gidin. "Tüm tablolar başarıyla oluşturuldu" mesajını görmelisiniz.

2.  **Test Verilerini Yükleyin:**
    `http://localhost:8080/seed_data.php` adresine gidin. "VERİTABANI BAŞARIYLA DOLDURULDU" mesajını görmelisiniz. Bu adım, test için gerekli kullanıcıları, firmaları, seferleri ve kuponları ekler.

## Test Kullanıcıları

`seed_data.php` betiği aşağıdaki test hesaplarını oluşturur:

* **Admin (Tüm Yetkiler):**
    * E-posta: `admin@mail.com`
    * Şifre: `admin123`
* **Firma Admin (Kamil Koç):**
    * E-posta: `kamil@mail.com`
    * Şifre: `kamil123`
* **Firma Admin (Metro Turizm):**
    * E-posta: `metro@mail.com`
    * Şifre: `metro123`
* **Firma Admin (Lüks Artvin):**
    * E-posta: `luks@mail.com`
    * Şifre: `luks123`
* **Yolcu (User):**
    * E-posta: `yolcu@mail.com`
    * Şifre: `yolcu123`
    * (Başlangıç Bakiyesi: 5000 TL)
      
## Eklenen Test Güzergahları

`seed_data.php` betiği, aşağıdaki güzergahlar için test seferleri oluşturur:

* İstanbul - Ankara 
* Trabzon - İstanbul
* Ankara - İzmir
* Antalya - Bursa
* Artvin - Trabzon
## Güvenlik Notu

Test işlemleriniz bittikten sonra, güvenlik amacıyla `setup_db.php` ve `seed_data.php` dosyalarını sunucudan (veya Docker image'ından) silmeniz önerilir.

---
