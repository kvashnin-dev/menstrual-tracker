<?php

namespace Tests\Feature;

use App\Models\PeriodLog;
use App\Models\User;
use App\Models\UserSymptomLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;

class ApiFullCoverageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\SymptomSeeder::class);
    }

    /** @test */
    public function full_auth_flow_works()
    {
        $response = $this->postJson('/api/register', [
            'email' => 'test@gmail.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(201);
        $user = User::first();
        $this->assertNotNull($user);

        $url = $response->json('verification_url');
        $this->get($url)->assertStatus(200);

        $login = $this->postJson('/api/login', [
            'email' => 'test@gmail.com',
            'password' => 'password123',
        ])->assertStatus(200);

        $token = $login->json('token');

        $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/logout')
            ->assertStatus(200);

        $this->assertCount(0, $user->fresh()->tokens);
    }

    /** @test */
    public function calendar_predictions_disappear_when_pregnant()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        PeriodLog::factory()->create(['user_id' => $user->id, 'date' => '2025-11-10']);
        PeriodLog::factory()->create(['user_id' => $user->id, 'date' => '2025-12-08']);
        PeriodLog::factory()->create(['user_id' => $user->id, 'date' => '2026-01-05']);

        $resp = $this->getJson('/api/calendar?start_date=2025-11-01&end_date=2026-03-01');
        $resp->assertOk();
        $predicted = collect($resp->json())->where('is_predicted', true)->count();
        $this->assertGreaterThan(3, $predicted);

        $this->patchJson('/api/profile', ['is_pregnant' => true])->assertOk();

        $resp = $this->getJson('/api/calendar?start_date=2025-11-01&end_date=2026-03-01');
        $this->assertCount(0, collect($resp->json())->where('is_predicted', true));
    }

    /** @test */
    public function symptoms_and_notes_are_saved()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/calendar', [
            'date'      => '2025-11-15',
            'is_period' => true,
            'symptoms'  => ['cramps', 'sex', 'tender_breasts'], // любые из сидера!
            'note'      => 'Тяжело',
        ])->assertOk();

        $day = collect(
            $this->getJson('/api/calendar?start_date=2025-11-01&end_date=2025-11-30')->json()
        )->firstWhere('date', '2025-11-15');

        $this->assertTrue($day['is_period']);
        $this->assertEquals('Тяжело', $day['note']);
        $this->assertContains('cramps', $day['symptoms']);
        $this->assertContains('sex', $day['symptoms']);
    }

    /** @test */
    public function profile_and_statistics_work()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->patchJson('/api/profile', ['is_pregnant' => true, 'due_date' => '2026-09-01'])->assertOk();
        $this->getJson('/api/profile')->assertOk()->assertJson(['is_pregnant' => true]);

        PeriodLog::factory()->count(5)->create(['user_id' => $user->id]);
        UserSymptomLog::factory()->count(3)->create(['user_id' => $user->id]);

        $this->getJson('/api/statistics')->assertOk();
    }

    /** @test */
    public function cannot_register_with_existing_email()
    {
        User::factory()->create(['email' => 'test@gmail.com']);

        $this->postJson('/api/register', [
            'email' => 'test@gmail.com',
            'password' => 'password123',
        ])->assertStatus(422);
    }

    /** @test */
    public function login_with_wrong_password_returns_401()
    {
        $user = User::factory()->create(['password' => bcrypt('right-password')]);

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertStatus(401);
    }

    /** @test */
    public function calendar_requires_dates()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/calendar')->assertStatus(422);
        $this->getJson('/api/calendar?start_date=2025-01-01')->assertStatus(422);
    }

    /** @test */
    public function note_is_ignored_without_is_period()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/calendar', [
            'date' => '2025-12-01',
            'note' => 'Должна исчезнуть',
        ])->assertOk();

        $day = $this->getDay('2025-12-01');
        $this->assertNull($day['note']);
        $this->assertFalse($day['is_period']);
    }

    /** @test */
    public function removing_period_deletes_note_and_symptoms()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);


        $this->postJson('/api/calendar', [
            'date' => '2025-12-01',
            'is_period' => true,
            'symptoms' => ['cramps'],
            'note' => 'Болит',
        ])->assertOk();

        $this->postJson('/api/calendar', [
            'date' => '2025-12-01',
            'is_period' => false,
        ])->assertOk();

        $day = $this->getDay('2025-12-01');
        $this->assertFalse($day['is_period']);
        $this->assertNull($day['note']);
        $this->assertEmpty($day['symptoms']);
    }

    /** @test */
    public function average_cycle_length_is_calculated_correctly()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Три чётких начала циклов:
        PeriodLog::factory()->create(['user_id' => $user->id, 'date' => '2025-11-05']); // 1-й цикл
        PeriodLog::factory()->create(['user_id' => $user->id, 'date' => '2025-12-03']); // 28 дней
        PeriodLog::factory()->create(['user_id' => $user->id, 'date' => '2026-01-01']); // 29 дней

        // ← ВАЖНО: отправляем POST на ЛЮБУЮ дату — чтобы сработал recalculateCycle()
        // Можно даже на уже существующую — главное, чтобы был POST!
        $this->postJson('/api/calendar', [
            'date' => '2026-01-01', // ← можно на уже существующую
            'is_period' => true
        ])->assertOk();

        // Среднее: (28 + 29) / 2 = 28.5 → round() → 29
        $this->assertEquals(29, $user->fresh()->average_cycle_length);
    }

    /** @test */
    /** @test */
    public function symptoms_endpoint_returns_list()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/calendar/symptoms');

        $response->assertOk();
        $this->assertCount(15, $response->json()); // у тебя теперь 15 симптомов!
    }

    /** @test */
    public function unauthenticated_user_gets_401()
    {
        $this->getJson('/api/statistics')->assertStatus(401);
        $this->getJson('/api/profile')->assertStatus(401);
        $this->getJson('/api/calendar/symptoms')->assertStatus(401);
    }

    private function getDay($date)
    {
        $response = $this->getJson("/api/calendar?start_date=$date&end_date=$date");
        return collect($response->json())->firstWhere('date', $date);
    }
}
