FROM php:8.2-apache

# ป้องกันปัญหาความขัดแย้งของ MPM บน Apache Container
RUN a2dismod mpm_event mpm_worker 2>/dev/null || true && a2enmod mpm_prefork

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

# ตั้งค่า entrypoint script สำหรับกำหนด PORT ไดนามิกของ Railway
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
