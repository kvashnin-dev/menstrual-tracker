<?php

namespace Database\Seeders;

use App\Models\Symptom;
use Illuminate\Database\Seeder;

class SymptomSeeder extends Seeder
{
    public function run(): void
    {
        $symptoms = [
            ['key' => 'cramps',     'name_ru' => 'Спазмы',      'name_en' => 'Cramps',     'icon' => 'droplet', 'color' => '#ef4444'],
            ['key' => 'headache',   'name_ru' => 'Головная боль', 'name_en' => 'Headache',   'icon' => 'head',    'color' => '#f59e0b'],
            ['key' => 'sex',        'name_ru' => 'Секс',        'name_en' => 'Sex',        'icon' => 'heart',   'color' => '#ec4899'],
            ['key' => 'nausea',     'name_ru' => 'Тошнота',     'name_en' => 'Nausea',     'icon' => 'stomach', 'color' => '#10b981'],
            ['key' => 'backache',   'name_ru' => 'Боль в спине','name_en' => 'Backache',   'icon' => 'back',    'color' => '#8b5cf6'],
        ];

        foreach ($symptoms as $s) {
            Symptom::firstOrCreate(['key' => $s['key']], $s);
        }
    }
}
