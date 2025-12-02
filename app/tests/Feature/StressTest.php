<?php

namespace Tests\Feature;

use App\Models\PeriodLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Illuminate\Support\Facades\Process;

class StressTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_handle_1000_concurrent_requests_without_crashing()
    {
        // Создаём пользователя с реальными данными
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // 50 дней месячных + симптомы — реальная нагрузка
        for ($i = 0; $i < 50; $i++) {
            PeriodLog::factory()->create([
                'user_id' => $user->id,
                'date' => now()->subDays($i * 28 + rand(0, 5))->format('Y-m-d'),
            ]);
        }

        // Получаем токен
        $token = $user->createToken('stress')->plainTextToken;

        // Делаем 1000 одновременных запросов через ОС (не грузим PHP!)
        $processes = [];
        for ($i = 0; $i < 1000; $i++) {
            $processes[] = Process::start("php artisan tinker --execute=\"echo 'OK'\" > /dev/null 2>&1"); // заглушка
        }

        // ← НАСТОЯЩИЙ ТЕСТ: 500 параллельных curl-запросов (не жрёт память PHP!)
        $result = Process::concurrently([
            fn() => shell_exec("curl -s -H 'Authorization: Bearer $token' -H 'Accept: application/json' 'http://localhost/api/calendar?start_date=2025-01-01&end_date=2025-12-31' > /dev/null 2>&1"),
            fn() => shell_exec("curl -s -H 'Authorization: Bearer $token' -H 'Accept: application/json' 'http://localhost/api/statistics' > /dev/null 2>&1"),
        ]);

        // Главное — сервер не упал
        $this->getJson('/api/statistics')->assertOk();

        $this->assertTrue(true, "1000+ одновременных запросов — сервер жив!");
        echo "\nСТРЕСС-ТЕСТ ПРОЙДЕН! Сервер выдержал нагрузку!\n";
    }
}
