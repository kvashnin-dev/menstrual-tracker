<?php

namespace Database\Seeders;

use App\Models\Symptom;
use Illuminate\Database\Seeder;

class SymptomSeeder extends Seeder
{
    public function run(): void
    {
        $symptoms = [
            // Боль и дискомфорт
            ['key' => 'cramps',         'name_ru' => 'Спазмы',          'name_en' => 'Cramps',         'icon' => 'wave-sine',   'color' => '#ef4444'],
            ['key' => 'headache',       'name_ru' => 'Головная боль',   'name_en' => 'Headache',       'icon' => 'head',        'color' => '#f59e0b'],
            ['key' => 'backache',       'name_ru' => 'Боль в спине',    'name_en' => 'Back pain',      'icon' => 'back',        'color' => '#dc2626'],
            ['key' => 'nausea',         'name_ru' => 'Тошнота',         'name_en' => 'Nausea',         'icon' => 'stomach',     'color' => '#84cc16'],

            // Настроение
            ['key' => 'mood_sad',      'name_ru' => 'Грустно',         'name_en' => 'Sad',            'icon' => 'face-sad',    'color' => '#6366f1'],
            ['key' => 'mood_happy',     'name_ru' => 'Весело',          'name_en' => 'Happy',          'icon' => 'face-happy',  'color' => '#10b981'],
            ['key' => 'mood_anxious',   'name_ru' => 'Тревожно',        'name_en' => 'Anxious',        'icon' => 'alert',       'color' => '#f97316'],
            ['key' => 'mood_irritable', 'name_ru' => 'Раздражена',      'name_en' => 'Irritable',      'icon' => 'fire',        'color' => '#ef4444'],

            // Выделения и тело
            ['key' => 'spotting',       'name_ru' => 'Мажущие',         'name_en' => 'Spotting',       'icon' => 'drop',        'color' => '#f43f5e'],
            ['key' => 'heavy_flow',     'name_ru' => 'Сильные',         'name_en' => 'Heavy flow',     'icon' => 'drop-half',   'color' => '#dc2626'],
            ['key' => 'bloating',       'name_ru' => 'Вздутие',         'name_en' => 'Bloating',       'icon' => 'belly',       'color' => '#3b82f6'],
            ['key' => 'tender_breasts', 'name_ru' => 'Грудь болит',     'name_en' => 'Tender breasts', 'icon' => 'heart',       'color' => '#ec4899'],

            // Секс и энергия
            ['key' => 'sex',            'name_ru' => 'Секс',            'name_en' => 'Sex',            'icon' => 'heart',       'color' => '#ec4899'],
            ['key' => 'high_energy',    'name_ru' => 'Много энергии',   'name_en' => 'High energy',    'icon' => 'zap',         'color' => '#8b5cf6'],
            ['key' => 'low_energy',     'name_ru' => 'Нет сил',         'name_en' => 'Low energy',     'icon' => 'sleep',       'color' => '#6b7280'],
        ];

        foreach ($symptoms as $s) {
            Symptom::updateOrCreate(['key' => $s['key']], $s);
        }
    }
}
