FROM php:8.3-fpm

# Install ekstensi sistem & GD dengan dependensi yang bersih
RUN apt-get update && apt-get install -y \
    nginx \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql pdo_sqlite

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy semua file project
COPY . .

# Jalankan composer install
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Siapkan file .env dan generate key
RUN cp .env.example .env || echo "APP_KEY=" > .env
RUN php artisan key:generate

# Buat file database SQLite kosong
RUN mkdir -p /var/www/html/database && touch /var/www/html/database/database.sqlite

# Set permission folder storage, cache, dan database
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database

# Konfigurasi Nginx untuk Laravel
RUN echo 'server {\n\
    listen 8080;\n\
    index index.php index.html;\n\
    root /var/www/html/public;\n\
    location / {\n\
        try_files $uri $uri/ /index.php?$query_string;\n\
    }\n\
    location ~ \\.php$ {\n\
        include fastcgi_params;\n\
        fastcgi_pass 127.0.0.1:9000;\n\
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;\n\
    }\n\
}' > /etc/nginx/sites-available/default

EXPOSE 8080

# Jalankan PHP-FPM dan Nginx secara bersamaan
CMD service nginx start && php-fpm