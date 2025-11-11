<?php

namespace App\Services;

use App\Models\CalendarDay;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PeriodPredictionService
{
    public function predict(User $user): void
    {
        $this->clearFuturePredictions($user);

        $cycles = $this->getCycleData($user);

        if ($cycles->count() < 2) {
            return; // Нужен минимум 1 полный цикл
        }

        $avgCycle = $cycles->avg('cycle_length');
        $avgDuration = $cycles->avg('period_duration');
        $stdDev = $cycles->stdDev('cycle_length');

        $lastPeriodStart = $cycles->last()['start'];

        // Прогнозируем следующие 6 циклов
        for ($i = 1; $i <= 6; $i++) {
            $predictedStart = $lastPeriodStart->copy()->addDays($avgCycle * $i);

            // Учитываем разброс
            if ($stdDev > 3) {
                $offset = rand(-2, 2);
                $predictedStart->addDays($offset);
            }

            $this->markPeriod($user, $predictedStart, $avgDuration);
            $this->markOvulation($user, $predictedStart, $avgCycle);
        }
    }

    private function getCycleData($user): Collection
    {
        $days = $user->calendarDays()
            ->where('is_period_confirmed', true)
            ->orderBy('date')
            ->get();

        $cycles = collect();
        $start = null;
        $inPeriod = false;
        $periodDays = 0;

        foreach ($days as $day) {
            if (!$inPeriod) {
                if ($start) {
                    $cycles->push([
                        'start' => $start,
                        'cycle_length' => $start->diffInDays($day->date),
                        'period_duration' => $periodDays,
                    ]);
                }
                $start = $day->date;
                $inPeriod = true;
                $periodDays = 1;
            } else {
                $periodDays++;
                if ($day->date->diffInDays($start) > 7) {
                    $inPeriod = false;
                }
            }
        }

        if ($inPeriod && $periodDays > 1) {
            $cycles->push([
                'start' => $start,
                'cycle_length' => null,
                'period_duration' => $periodDays,
            ]);
        }

        return $cycles->filter(fn($c) => $c['cycle_length']);
    }

    private function markPeriod($user, Carbon $start, $duration): void
    {
        for ($i = 0; $i < $duration; $i++) {
            $date = $start->copy()->addDays($i);
            if ($date->isFuture()) {
                $user->calendarDays()->updateOrCreate(
                    ['date' => $date],
                    ['is_period_predicted' => true]
                );
            }
        }
    }

    private function markOvulation($user, Carbon $start, $avgCycle): void
    {
        $ovulation = $start->copy()->addDays($avgCycle - 14); // Лютеиновая фаза ~14 дней
        if ($ovulation->isFuture()) {
            $user->calendarDays()->updateOrCreate(
                ['date' => $ovulation],
                ['is_ovulation_predicted' => true]
            );
        }
    }

    private function clearFuturePredictions($user): void
    {
        $user->calendarDays()
            ->where('date', '>', now())
            ->where(function ($q) {
                $q->where('is_period_predicted', true)
                    ->orWhere('is_ovulation_predicted', true);
            })
            ->update([
                'is_period_predicted' => false,
                'is_ovulation_predicted' => false,
            ]);
    }
}
