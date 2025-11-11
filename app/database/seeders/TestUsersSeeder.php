<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\CalendarDay;
use App\Models\Symptom;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestUsersSeeder extends Seeder
{
    public function run(): void
    {
        // === ПОЛЬЗОВАТЕЛЬ 1: Активная, 3 цикла ===
        $user1 = User::create([
            'email' => 'anna@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
            'is_pregnant' => false,
            'due_date' => null,
        ]);

        $this->createCycles($user1, [
            // Цикл 1: 8–12 ноября
            ['2025-11-08', ['cramps', 'headache']],
            ['2025-11-09', ['sex', 'cramps']],
            ['2025-11-10', ['headache']],
            ['2025-11-11', []],
            ['2025-11-12', []],

            // Цикл 2: 6–10 декабря
            ['2025-12-06', ['cramps']],
            ['2025-12-07', ['sex']],
            ['2025-12-08', []],
            ['2025-12-09', []],
            ['2025-12-10', []],

            // Цикл 3: 3–7 января 2026
            ['2026-01-03', ['cramps']],
            ['2026-01-04', ['headache']],
            ['2026-01-05', []],
            ['2026-01-06', []],
            ['2026-01-07', []],
        ]);

        // === ПОЛЬЗОВАТЕЛЬ 2: Беременная ===
        $user2 = User::create([
            'email' => 'maria@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
            'is_pregnant' => true,
            'due_date' => '2026-06-15',
        ]);

        $this->createCycles($user2, [
            ['2025-10-01', ['cramps']],
            ['2025-10-02', []],
            ['2025-10-03', []],
        ]);
    }

    private function createCycles(User $user, array $days): void
    {
        foreach ($days as [$date, $symptomKeys]) {
            $day = CalendarDay::updateOrCreate(
                ['user_id' => $user->id, 'date' => $date],
                [
                    'is_period_confirmed' => true,
                    'note' => 'День ' . (Carbon::parse($date)->day),
                ]
            );

            if (!empty($symptomKeys)) {
                $symptomIds = Symptom::whereIn('key', $symptomKeys)->pluck('id');
                $day->symptoms()->sync($symptomIds);
            }
        }

        // Пересчитываем прогнозы
        app(\App\Services\PeriodPredictionService::class)->predict($user);
    }
}
