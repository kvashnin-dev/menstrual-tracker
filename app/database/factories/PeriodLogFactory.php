<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class PeriodLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'date' => Carbon::today()->subDays(rand(0, 100)),
        ];
    }
}
