FROM php:8.2-apache

# ติดตั้งส่วนเสริม PDO MySQL สำหรับเชื่อมต่อฐานข้อมูล
RUN docker-php-ext-install pdo pdo_mysql

# เปิดใช้งาน Apache mod_rewrite สำหรับ Clean URL Routing (.htaccess)
RUN a2enmod rewrite

# อนุญาตให้ .htaccess ทำงานใน Apache
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# คัดลอกซอร์สโค้ดโปรเจกต์ทั้งหมดเข้าสู่ Container
COPY . /var/www/html/

# ตั้งค่าสิทธิ์ไฟล์
RUN chown -R www-data:www-data /var/www/html

# รองรับพอร์ตไดนามิกของ Railway ($PORT)
CMD sed -i "s/80/$PORT/g" /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf && apache2-foreground
