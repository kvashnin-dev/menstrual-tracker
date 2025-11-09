<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable; // Это обязательно!
use Illuminate\Queue\InteractsWithQueue;
use Carbon\Carbon;

class GeneratePeriodPredictions implements ShouldQueue
{
    use Dispatchable, Queueable, InteractsWithQueue; // Dispatchable добавлен

    public function __construct(protected User $user) {}

    public function handle()
    {
        $periodDays = $this->user->calendarDays()
            ->where('is_period_confirmed', true)
            ->orderBy('date')
            ->pluck('date');

        if ($periodDays->count() < 2) {
            return;
        }

        $intervals = [];
        for ($i = 1; $i < $periodDays->count(); $i++) {
            $intervals[] = $periodDays[$i]->diffInDays($periodDays[$i - 1]);
        }

        $avgCycle = (int) round(array_sum($intervals) / count($intervals));
        $lastPeriod = $periodDays->last();

        $this->user->calendarDays()
            ->where('is_period_predicted', true)
            ->update(['is_period_predicted' => false]);

        $predictUntil = now()->addMonths(3);
        $next = $lastPeriod->copy()->addDays($avgCycle);

        while ($next->lte($predictUntil)) {
            $this->user->calendarDays()
                ->updateOrCreate(
                    ['date' => $next->format('Y-m-d')],
                    ['is_period_predicted' => true]
                );
            $next->addDays($avgCycle);
        }
    }
}
