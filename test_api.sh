#!/bin/bash

# === НАСТРОЙКИ ===
BASE_URL="http://localhost:8000"
EMAIL="anna@example.com"
PASSWORD="password123"

# === ФУНКЦИЯ: Получить токен ===
get_token() {
    local response=$(curl -s -X POST "$BASE_URL/api/login" \
        -H "Content-Type: application/json" \
        -d "{\"email\":\"$EMAIL\",\"password\":\"$PASSWORD\"}")

    echo "$response" | grep -o '"token":"[^"]*' | cut -d'"' -f4
}

# === Получаем токен ===
TOKEN=$(get_token)

if [ -z "$TOKEN" ]; then
    echo "Ошибка: Не удалось получить токен. Проверь логин/пароль."
    exit 1
fi

echo "Токен получен: $TOKEN"
echo "========================================"

# === 1. Календарь (ноябрь 2025 — март 2026) ===
echo "1. Календарь (2025-11-01 → 2026-03-01)"
curl -s -H "Authorization: Bearer $TOKEN" \
  "$BASE_URL/api/calendar?start_date=2025-11-01&end_date=2026-03-01" | jq '.[8:12] + [.[] | select(.is_predicted or .is_ovulation)]' | jq
echo "----------------------------------------"

# === 2. Статистика (JSON) ===
echo "2. Статистика"
curl -s -H "Authorization: Bearer $TOKEN" \
  "$BASE_URL/api/statistics" | jq
echo "----------------------------------------"

# === 3. PDF-отчёт ===
echo "3. PDF-отчёт"
PDF_RESPONSE=$(curl -s -H "Authorization: Bearer $TOKEN" \
  "$BASE_URL/api/statistics?format=pdf")

echo "$PDF_RESPONSE" | jq
DOWNLOAD_URL=$(echo "$PDF_RESPONSE" | jq -r '.download_url')
echo "PDF доступен по ссылке: $DOWNLOAD_URL"
echo "----------------------------------------"

# === 4. Профиль ===
echo "4. Профиль"
curl -s -H "Authorization: Bearer $TOKEN" \
  "$BASE_URL/api/profile" | jq
echo "----------------------------------------"

# === 5. Обновить день (пример) ===
echo "5. Добавляем секс на 2025-11-15"
curl -s -X POST "$BASE_URL/api/calendar" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "date": "2025-11-15",
    "is_period": false,
    "symptoms": ["sex"],
    "note": "Романтика"
  }' | jq
echo "----------------------------------------"

echo "ГОТОВО! Все запросы выполнены."
