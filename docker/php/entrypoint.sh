#!/bin/bash

# Проверяем, существует ли .env, если нет — копируем из .env.example
if [ ! -f app/.env ]; then
    echo "Копирую .env.example в .env..."
    cp app/.env.example app/.env
fi

# Запускаем Docker-контейнеры
echo "Запускаю Docker-контейнеры..."
docker-compose up -d --build

# Устанавливаем зависимости Composer
echo "Устанавливаю зависимости Composer..."
docker-compose exec -T app composer install

# Генерируем ключ приложения
echo "Генерирую APP_KEY..."
docker-compose exec -T app php artisan key:generate

# Выполняем миграции
echo "Выполняю миграции..."
docker-compose exec -T app php artisan migrate --force

# Генерируем документацию API с помощью Scribe
echo "Генерирую документацию API..."
docker-compose exec -T app php artisan scribe:generate

echo "Проект успешно настроен! Документация API: http://localhost:8000/docs"
