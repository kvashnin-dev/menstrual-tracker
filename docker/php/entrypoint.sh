#!/bin/bash

# Проверяем, существует ли .env файл
if [ ! -f /var/www/html/.env ]; then
    echo "Копирую .env.example в .env"
    cp /var/www/html/.env.example /var/www/html/.env
fi

# Устанавливаем зависимости Composer, если папка vendor отсутствует
if [ ! -d /var/www/html/vendor ]; then
    echo "Устанавливаю зависимости Composer"
    composer install --optimize-autoloader
fi

# Генерируем ключ приложения, если он еще не сгенерирован
if ! grep -q "APP_KEY=.\+" /var/www/html/.env; then
    echo "Генерирую APP_KEY"
    php artisan key:generate
fi

# Запускаем миграции
echo "Запускаю миграции"
php artisan migrate --force

# Генерируем документацию Scribe
echo "Генерирую документацию API"
php artisan scribe:generate

# Запускаем PHP-FPM
exec php-fpm