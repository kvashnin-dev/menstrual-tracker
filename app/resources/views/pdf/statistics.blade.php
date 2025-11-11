<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Менструальная статистика</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; padding: 20px; }
        h1 { color: #e11d48; text-align: center; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #e11d48; color: white; }
        .highlight { background: #fee2e2; font-weight: bold; }
    </style>
</head>
<body>
<h1>Менструальная статистика</h1>
<p><strong>Пользователь:</strong> {{ $email }} (ID: {{ $user_id }})</p>
<p><strong>Дата:</strong> {{ $generated_at }}</p>

<table>
    <tr><th>Параметр</th><th>Значение</th></tr>
    <tr><td>Средний цикл</td><td>{{ $average_cycle_days ?? 'Нет данных' }} дней</td></tr>
    <tr><td>Продолжительность</td><td>{{ $average_period_duration ?? 'Нет данных' }} дней</td></tr>
    <tr class="{{ $is_painful ? 'highlight' : '' }}">
        <td>Болезненные</td><td>{{ $is_painful ? 'Да' : 'Нет' }}</td>
    </tr>
    <tr><td>Половая жизнь</td><td>{{ $is_sexually_active ? 'Да' : 'Нет' }}</td></tr>
    <tr><td>Беременность</td><td>{{ $is_pregnant ? 'Да' : 'Нет' }}</td></tr>
</table>

<h2>Симптомы</h2>
@if(empty($symptom_frequency))
    <p>Нет данных</p>
@else
    <table>
        <tr><th>Симптом</th><th>Дней</th><th>%</th></tr>
        @foreach($symptom_frequency as $key => $data)
            <tr>
                <td>{{ ucfirst($key) }}</td>
                <td>{{ $data['count'] }}</td>
                <td>{{ $data['percentage'] }}%</td>
            </tr>
        @endforeach
    </table>
@endif
</body>
</html>
