<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SymptomFactory extends Factory
{
    protected $model = \App\Models\Symptom::class;

    private static $symptoms = [
        ['key' => 'cramps',        'name_ru' => 'Спазмы',           'name_en' => 'Cramps',         'icon' => 'wave-sine',   'color' => '#ef4444'],
        ['key' => 'sex',           'name_ru' => 'Секс',             'name_en' => 'Sex',            'icon' => 'heart',       'color' => '#ec4899'],
        ['key' => 'headache',      'name_ru' => 'Головная боль',    'name_en' => 'Headache',       'icon' => 'head-side',   'color' => '#f97316'],
        ['key' => 'backache',      'name_ru' => 'Боль в спине',     'name_en' => 'Backache',       'icon' => 'spine',       'color' => '#eab308'],
        ['key' => 'nausea',        'name_ru' => 'Тошнота',          'name_en' => 'Nausea',         'icon' => 'face-nauseated', 'color' => '#84cc16'],
        ['key' => 'bloating',      'name_ru' => 'Вздутие',          'name_en' => 'Bloating',       'icon' => 'circle-dot',  'color' => '#06b6d4'],
        ['key' => 'mood_swing',    'name_ru' => 'Перепады настроения','name_en' => 'Mood swings',   'icon' => 'face-meh',    'color' => '#8b5cf6'],
        ['key' => 'low_energy',    'name_ru' => 'Слабость',         'name_en' => 'Low energy',     'icon' => 'battery-low', 'color' => '#94a3b8'],
    ];

    public function definition(): array
    {
        // Берём случайный из списка — без unique()
        return $this->faker->randomElement(self::$symptoms);
    }

    // Если хочешь создать ВСЕ симптомы сразу
    public function all(): static
    {
        return $this->state(fn() => $this->faker->randomElement(self::$symptoms));
    }
}
