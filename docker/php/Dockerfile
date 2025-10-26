FROM php:8.1-fpm

# Установка зависимостей
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    unzip \
    git \
    && docker-php-ext-configure pdo_mysql \
    && docker-php-ext-install pdo_mysql zip

# Установка Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Копирование исходного кода
WORKDIR /var/www/html
COPY app /var/www/html

# Установка зависимостей Composer
RUN composer install --optimize-autoloader --no-dev

# Права на файлы
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Копирование entrypoint скрипта
COPY docker/php/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Указываем entrypoint
ENTRYPOINT ["/entrypoint.sh"]