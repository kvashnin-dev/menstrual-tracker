<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CalendarDay;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class StatisticsController extends Controller
{
    /**
     * Получить статистику менструального цикла
     *
     * @group Statistics
     * @authenticated
     *
     * @queryParam format string optional Формат ответа: `json` (по умолчанию) или `pdf`. Example: pdf
     *
     * @response 200 scenario="JSON" {
     *     "user_id": 1,
     *     "email": "user@example.com",
     *     "average_cycle_days": 28.5,
     *     "average_period_duration": 5.2,
     *     "is_painful": true,
     *     "is_sexually_active": true,
     *     "is_pregnant": false,
     *     "total_confirmed_period_days": 24,
     *     "symptom_frequency": {
     *         "cramps": { "count": 12, "percentage": 50 },
     *         "headache": { "count": 8, "percentage": 33 }
     *     },
     *     "generated_at": "2025-11-11 03:52:00"
     * }
     *
     * @response 200 scenario="PDF" {
     *     "message": "PDF report generated successfully",
     *     "download_url": "http://localhost:8000/storage/statistics_user_1_2025-11-11.pdf",
     *     "generated_at": "2025-11-11 03:52:00"
     * }
     *
     * @response 401 {
     *     "message": "Unauthenticated"
     * }
     *
     * @responseFile storage/app/public/statistics_user_1_2025-11-11.pdf
     */
    public function index(): JsonResponse
    {
        $user = request()->user();
        $format = request()->query('format', 'json');
        $stats = $this->calculateStats($user);

        if ($format === 'pdf') {
            return $this->generatePdf($stats);
        }

        return response()->json($stats);
    }

    /**
     * Рассчитать статистику на основе подтверждённых дней
     */
    private function calculateStats($user): array
    {
        $confirmedDays = $user->calendarDays()
            ->where('is_period_confirmed', true)
            ->with('symptoms')
            ->orderBy('date')
            ->get();

        $cycleLengths = [];
        $periodDurations = [];
        $symptomFrequency = [];
        $periodStart = null;
        $inPeriod = false;
        $periodDays = 0;

        foreach ($confirmedDays as $day) {
            // === Циклы ===
            if (!$inPeriod) {
                if ($periodStart) {
                    $cycleLengths[] = $periodStart->diffInDays($day->date);
                }
                $periodStart = $day->date;
                $inPeriod = true;
                $periodDays = 1;
            } else {
                $periodDays++;
                if ($day->date->diffInDays($periodStart) > 7) {
                    $periodDurations[] = $periodDays - 1;
                    $inPeriod = false;
                }
            }

            // === Симптомы ===
            foreach ($day->symptoms as $s) {
                $symptomFrequency[$s->key] = ($symptomFrequency[$s->key] ?? 0) + 1;
            }
        }

        if ($inPeriod && $periodDays > 1) {
            $periodDurations[] = $periodDays;
        }

        $avgCycle = $cycleLengths ? round(array_sum($cycleLengths) / count($cycleLengths), 1) : null;
        $avgDuration = $periodDurations ? round(array_sum($periodDurations) / count($periodDurations), 1) : null;

        $totalPeriodDays = $confirmedDays->count();
        $painfulDays = $confirmedDays->filter(fn($d) =>
        $d->symptoms->whereIn('key', ['cramps', 'headache', 'nausea', 'backache'])->count()
        )->count();
        $isPainful = $totalPeriodDays > 0 && ($painfulDays / $totalPeriodDays) > 0.5;

        return [
            'user_id' => $user->id,
            'email' => $user->email,
            'average_cycle_days' => $avgCycle,
            'average_period_duration' => $avgDuration,
            'is_painful' => $isPainful,
            'is_sexually_active' => $user->is_sexually_active,
            'is_pregnant' => $user->is_pregnant,
            'total_confirmed_period_days' => $totalPeriodDays,
            'symptom_frequency' => collect($symptomFrequency)->map(fn($count) => [
                'count' => $count,
                'percentage' => $totalPeriodDays > 0 ? round(($count / $totalPeriodDays) * 100) : 0
            ])->toArray(),
            'generated_at' => now()->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Сгенерировать PDF-отчёт
     */
    private function generatePdf(array $stats): JsonResponse
    {
        $pdf = Pdf::loadView('pdf.statistics', $stats);
        $filename = "statistics_user_{$stats['user_id']}_" . now()->format('Y-m-d') . '.pdf';
        $path = 'public/' . $filename;
        Storage::put($path, $pdf->output());

        return response()->json([
            'message' => 'PDF report generated successfully',
            'download_url' => url('storage/' . $filename),
            'generated_at' => now()->toDateTimeString(),
        ]);
    }
}
