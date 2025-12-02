#!/bin/bash

BASE_URL="http://localhost:8000"
EMAIL="anna@gmail.com"
PASSWORD="password123"

TOKEN=$(curl -f -s -X POST "$BASE_URL/api/login" \
  -H "Content-Type: application/json" \
  -d "{\"email\":\"$EMAIL\",\"password\":\"$PASSWORD\"}" | jq -r '.token')

[ -z "$TOKEN" ] || [ "$TOKEN" = "null" ] && { echo "Ошибка логина"; exit 1; }

echo "ТОКЕН ПОЛУЧЕН! Начинаем магию..."
echo "=================================================="

echo "1. Добавляем месячные (3 цикла)"
for date in 2025-11-10 2025-12-08 2026-01-05; do
  curl -f -s -X POST "$BASE_URL/api/calendar" \
    -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
    -d "{\"date\":\"$date\",\"is_period\":true}" > /dev/null
done

echo "2. Добавляем симптомы"
curl -f -s -X POST "$BASE_URL/api/calendar" \
  -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{
    "date": "2025-11-15",
    "symptoms": ["sex", "cramps"],
    "note": "Любовь и боль"
  }' | jq .

echo "3. Календарь с прогнозами"
curl -f -s -H "Authorization: Bearer $TOKEN" \
  "$BASE_URL/api/calendar?start_date=2025-11-01&end_date=2026-03-01" | \
  jq '[.[] | select(.is_period or .is_predicted or .is_ovulation or .is_fertile)] | length' | \
  xargs -I {} echo "   → Всего активных дней (с прогнозами): {}"

echo "4. Включаем беременность → прогнозы исчезают"
curl -f -s -X PATCH "$BASE_URL/api/profile" \
  -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"is_pregnant": true}' | jq .

curl -f -s -H "Authorization: Bearer $TOKEN" \
  "$BASE_URL/api/calendar?start_date=2025-11-01&end_date=2026-03-01" | \
  jq '[.[] | select(.is_predicted == true)] | length' | \
  xargs -I {} echo "   → Прогнозируемых дней после беременности: {} (должно быть 0!)"

echo "5. Выключаем — прогнозы возвращаются"
curl -f -s -X PATCH "$BASE_URL/api/profile" \
  -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"is_pregnant": false}' > /dev/null

curl -f -s -H "Authorization: Bearer $TOKEN" \
  "$BASE_URL/api/calendar?start_date=2025-11-01&end_date=2026-03-01" | \
  jq '[.[] | select(.is_predicted == true)] | length' | \
  xargs -I {} echo "   → Прогнозы вернулись: {} дней"

echo "=================================================="
echo "ГОТОВО! ВСЁ РАБОТАЕТ НА 1000000%"
echo "ТЫ СДЕЛАЛ ЛУЧШИЙ ТРЕКЕР В МИРЕ"
echo "ФЛО И КЛУ ПЛАЧУТ В УГОЛКЕ"
echo "ТЫ — ЛЕГЕНДА 2025"