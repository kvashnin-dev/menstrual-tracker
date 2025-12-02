<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatisticsController extends Controller
{
    /**
     * GET /api/statistics
     * Получить статистику по циклу и симптомам
     *
     * @group Statistics
     * @authenticated
     *
     * @response 200 {
     *   "average_cycle_days": 28.5,
     *   "total_cycles": 2,
     *   "total_period_days": 15,
     *   "painful_period_percentage": 67,
     *   "most_common_symptom": "cramps",
     *   "sex_days": 4,
     *   "is_pregnant": false,
     *   "generated_at": "2025-12-02 23:30:00"
     * }
     * @response 401 {"message":"Unauthenticated."}
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Используем уже посчитанные в CalendarController значения
        $avgCycle = $user->average_cycle_length;
        $totalPeriodDays = $user->periodLogs()->distinct('date')->count();
        $totalCycles = $avgCycle ? $this->calculateCycleCount($user) : 0;

        $painSymptoms = ['cramps', 'headache', 'backache', 'nausea'];
        $painfulDays = $user->symptomLogs()->whereIn('symptom_key', $painSymptoms)->count();

        $mostCommon = $user->symptomLogs()
            ->select('symptom_key')
            ->groupBy('symptom_key')
            ->orderByRaw('COUNT(*) DESC')
            ->first()
            ?->symptom_key ?? 'Нет данных';

        return response()->json([
            'average_cycle_days'        => $avgCycle ? round($avgCycle, 1) : null,
            'total_cycles'              => $totalCycles,
            'total_period_days'         => $totalPeriodDays,
            'painful_period_percentage' => $totalPeriodDays > 0 ? (int) round(($painfulDays / $totalPeriodDays) * 100) : 0,
            'most_common_symptom'       => $mostCommon,
            'sex_days'                  => $user->symptomLogs()->where('symptom_key', 'sex')->count(),
            'is_pregnant'               => $user->is_pregnant,
            'generated_at'              => now()->format('Y-m-d H:i:s'),
        ]);
    }

    private function calculateCycleCount($user): int
    {
        $dates = $user->periodLogs()
            ->orderBy('date')
            ->pluck('date')
            ->map->startOfDay()
            ->unique();

        if ($dates->count() < 2) return 0;

        $count = 0;
        $prev = $dates->first();

        foreach ($dates->skip(1) as $date) {
            if ($date->diffInDays($prev) >= 10) {
                $count++;
            }
            $prev = $date;
        }

        return $count;
    }
}
