<?php

namespace Database\Seeders;

use App\Models\Symptom;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SymptomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $symptoms = [
            ['key' => 'period', 'name_ru' => 'Месячные', 'icon' => 'drop', 'color' => '#ef4444'],
            ['key' => 'headache', 'name_ru' => 'Голова болит', 'icon' => 'head', 'color' => '#f59e0b'],
            ['key' => 'cramps', 'name_ru' => 'Спазмы', 'icon' => 'pain', 'color' => '#ec4899'],
            ['key' => 'happy', 'name_ru' => 'Весело', 'icon' => 'smile', 'color' => '#10b981'],
            ['key' => 'sad', 'name_ru' => 'Грустно', 'icon' => 'frown', 'color' => '#6366f1'],
        ];

        foreach ($symptoms as $s) {
            Symptom::create($s);
        }
    }
}
