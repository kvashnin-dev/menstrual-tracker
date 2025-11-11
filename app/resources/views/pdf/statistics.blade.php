<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Менструальный отчёт</title>
    <style>
        @page { margin: 15mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1f2937; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #e11d48; padding-bottom: 10px; }
        .header h1 { color: #e11d48; margin: 0; font-size: 24px; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th { background: #e11d48; color: white; padding: 8px; font-size: 11px; }
        td { padding: 6px 8px; border-bottom: 1px solid #e5e7eb; font-size: 11px; }
        .highlight { background: #fee2e2; font-weight: bold; }
    </style>
</head>
<body>

<div class="header">
    <h1>Менструальный отчёт</h1>
</div>

<p><strong>Пользователь:</strong> {{ $stats['email'] }} (ID: {{ $stats['user_id'] }})</p>
<p><strong>Дата:</strong> {{ $stats['generated_at'] }}</p>

<table>
    <tr><th>Параметр</th><th>Значение</th></tr>
    <tr><td>Средний цикл</td><td><strong>{{ $stats['average_cycle_days'] ?? '—' }} дней</strong></td></tr>
    <tr><td>Продолжительность</td><td><strong>{{ $stats['average_period_duration'] ?? '—' }} дней</strong></td></tr>
    <tr class="{{ $stats['is_painful'] ? 'highlight' : '' }}">
        <td>Болезненные</td><td>{{ $stats['is_painful'] ? 'Да (>50%)' : 'Нет' }}</td>
    </tr>
    <tr>
        <td>Половая жизнь</td>
        <td>{{ $stats['sex_days_count'] > 0 ? 'Да (' . $stats['sex_days_count'] . ' дн.)' : 'Нет' }}</td>
    </tr>
    <tr>
        <td>Беременность</td>
        <td>
            {{ $stats['is_pregnant']
                ? 'Да' . ($stats['due_date'] ? ' (роды: ' . \Carbon\Carbon::parse($stats['due_date'])->format('d.m.Y') . ')' : '')
                : 'Нет'
            }}
        </td>
    </tr>
</table>

<h2>Симптомы</h2>
@if(empty($stats['symptom_frequency']))
    <p>Нет данных</p>
@else
    <table>
        <tr><th>Симптом</th><th>Дней</th><th>%</th></tr>
        @foreach($stats['symptom_frequency'] as $key => $data)
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
