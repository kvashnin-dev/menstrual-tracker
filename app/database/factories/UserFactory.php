<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            // 'name' — УДАЛЕНО НАВСЕГДА
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),

            // Твои кастомные поля — ОБЯЗАТЕЛЬНО
            'is_pregnant' => false,
            'due_date' => null,
            'average_cycle_length' => 28,
            'average_period_length' => 5,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function pregnant(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_pregnant' => true,
            'due_date' => now()->addMonths(6),
        ]);
    }
}
