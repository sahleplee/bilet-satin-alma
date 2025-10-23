# Resmi PHP 8.1 Apache imajını temel alıyoruz
FROM php:8.1-apache 

# === YENİ EKLENEN ADIM: Gerekli kütüphaneleri yükle ===
# Önce paket listesini güncelle, sonra SQLite3 geliştirme kütüphanelerini (-dev) yükle.
# Ayrıca, gd eklentisi için libpng-dev ve libjpeg-dev gerekebilir.
RUN apt-get update && apt-get install -y \
        libsqlite3-dev \
        libpng-dev \
        libjpeg-dev \
    && rm -rf /var/lib/apt/lists/* # =======================================================

# Şimdi PHP eklentilerini etkinleştir (Artık kütüphaneler olduğu için pdo_sqlite başarılı olacak)
RUN docker-php-ext-install pdo pdo_sqlite gd && docker-php-ext-enable gd

# Apache yapılandırmasını mod_rewrite etkinleştirecek şekilde ayarla
RUN a2enmod rewrite

# Proje dosyalarını Apache'nin web kök dizinine kopyala
COPY . /var/www/html/

# (Opsiyonel) Font klasörü için izinler
# RUN chown -R www-data:www-data /var/www/html/font

# Çalışma dizinini ayarla
WORKDIR /var/www/html

# Apache'nin çalıştığı 80 portunu dışarıya aç
EXPOSE 80

# Container başladığında Apache'yi ön planda başlat
CMD ["apache2-foreground"]