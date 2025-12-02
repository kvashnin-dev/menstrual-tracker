<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserSymptomLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'     => User::factory(),
            'date'        => fake()->dateTimeBetween('-3 months', '+1 month')->format('Y-m-d'),
            'symptom_key' => fake()->randomElement(['cramps', 'sex', 'headache', 'mood', 'bloating']),
        ];
    }
}
