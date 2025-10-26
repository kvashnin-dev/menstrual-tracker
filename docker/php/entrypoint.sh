#!/bin/bash

# Проверяем, существует ли .env файл
if [ ! -f /var/www/html/.env ]; then
    echo "Копирую .env.example в .env"
    cp /var/www/html/.env.example /var/www/html/.env
fi

# Генерируем ключ приложения, если он еще не сгенерирован
if ! grep -q "APP_KEY=.\+" /var/www/html/.env; then
    echo "Генерирую APP_KEY"
    php artisan key:generate
fi

# Запускаем миграции
echo "Запускаю миграции"
php artisan migrate --force

# Запускаем PHP-FPM
exec php-fpm