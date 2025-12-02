<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PeriodLog;
use App\Models\Symptom;
use App\Models\UserSymptomLog;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CalendarController extends Controller
{
    /**
     * GET /api/calendar?start_date=YYYY-MM-DD&end_date=YYYY-MM-DD
     * Получить календарь за указанный период
     *
     * @group Calendar
     * @authenticated
     * @queryParam start_date required Дата начала (Y-m-d) Example: 2025-12-01
     * @queryParam end_date required Дата окончания (Y-m-d) Example: 2025-12-31
     *
     * @response 200 array
     * @response 401 {"message":"Unauthenticated."}
     * @response 422 {"message":"The start_date field is required.", ...}
     *
     * @responseExample 200
     * [
     *   {
     *     "date": "2025-12-05",
     *     "is_period": true,
     *     "is_predicted": false,
     *     "is_ovulation": false,
     *     "is_fertile": false,
     *     "note": "Болит всё",
     *     "symptoms": ["cramps", "headache"]
     *   },
     *   {
     *     "date": "2025-12-06",
     *     "is_period": false,
     *     "is_predicted": true,
     *     "is_ovulation": true,
     *     "is_fertile": true,
     *     "note": null,
     *     "symptoms": []
     *   }
     * ]
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date_format:Y-m-d',
            'end_date'   => 'required|date_format:Y-m-d|after_or_equal:start_date',
        ]);

        $user = $request->user();
        $start = $request->query('start_date');
        $end = $request->query('end_date');

        // 1. Месячные (с заметкой)
        $periods = $user->periodLogs()
            ->whereBetween('date', [$start, $end])
            ->get()
            ->keyBy(fn($log) => $log->date->format('Y-m-d'));

        // 2. Симптомы + заметки
        $symptoms = $user->symptomLogs()
            ->whereBetween('date', [$start, $end])
            ->get()
            ->groupBy(fn($log) => $log->date->format('Y-m-d'));

        // 3. Прогнозы
        $predictions = $this->predictCycle($user, $start, $end);

        $result = [];
        $current = Carbon::parse($start);

        while ($current->lte($end)) {
            $date = $current->format('Y-m-d');

            $periodLog = $periods->get($date);
            $symptomLog = $symptoms->get($date);

            $result[] = [
                'date'         => $date,
                'is_period'    => (bool) $periodLog,
                'is_predicted' => in_array($date, $predictions['periods']),
                'is_ovulation' => in_array($date, $predictions['ovulation']),
                'is_fertile'   => in_array($date, $predictions['fertile']),
                'note' => $periodLog?->note,
                'symptoms'     => $symptomLog?->pluck('symptom_key')->unique()->values()->toArray() ?? [],
            ];

            $current->addDay();
        }

        return response()->json($result);
    }

    /**
     * POST /api/calendar
     * Сохранить/обновить день
     *
     * @group Calendar
     * @authenticated
     * @bodyParam date date required Дата в формате Y-m-d Example: 2025-12-05
     * @bodyParam is_period boolean optional true — есть месячные, false — убрать месячные Example: true
     * @bodyParam symptoms array optional Массив ключей симптомов (должны существовать в таблице symptoms) Example: ["cramps","headache","sex"]
     * @bodyParam note string optional Заметка (сохраняется только если is_period=true) Example: "Болит всё"
     *
     * @response 200 {"message":"Day updated successfully"}
     * @response 401 {"message":"Unauthenticated."}
     * @response 422 scenario=date_invalid {"errors":{"date":["The date field must be a valid date."]}}
     * @response 422 scenario=symptom_not_exists {"errors":{"symptoms.0":["The selected symptoms.0 is invalid."]}}
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'date'       => 'required|date_format:Y-m-d',
            'is_period'  => 'nullable|boolean',
            'symptoms'   => 'nullable|array',
            'symptoms.*' => 'exists:symptoms,key',
            'note'       => 'nullable|string|max:1000',
        ]);

        $user = $request->user();
        $date = $request->date('date');

        DB::transaction(function () use ($user, $date, $request) {
            // 1. Месячные
            if ($request->boolean('is_period')) {
                PeriodLog::updateOrCreate(
                    ['user_id' => $user->id, 'date' => $date],
                    ['note' => $request->note]
                );
            } else {
                PeriodLog::where('user_id', $user->id)
                    ->where('date', $date)
                    ->delete();
            }

            UserSymptomLog::where('user_id', $user->id)
                ->where('date', $date)
                ->delete();

            if ($request->filled('symptoms')) {
                $insert = [];
                foreach ($request->symptoms as $key) {
                    $insert[] = [
                        'user_id'     => $user->id,
                        'date'        => $date,
                        'symptom_key' => $key,
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ];
                }
                UserSymptomLog::insert($insert);
            }

            // 3. Пересчёт цикла
            $this->recalculateCycle($user);
        });

        return response()->json(['message' => 'Day updated successfully']);
    }

    private function predictCycle($user, string $start, string $end)
    {
        if ($user->is_pregnant || !$user->average_cycle_length) {
            return ['periods' => [], 'ovulation' => [], 'fertile' => []];
        }

        $cycleLength = $user->average_cycle_length;
        $periodLength = $user->average_period_length ?? 5;

        $lastPeriod = $user->periodLogs()->latest('date')->first();
        if (!$lastPeriod) return ['periods' => [], 'ovulation' => [], 'fertile' => []];

        $periods = [];
        $ovulations = [];
        $fertiles = [];

        $next = $lastPeriod->date->copy()->addDays($cycleLength);

        while ($next->lte($end)) {
            if ($next->gte($start)) {
                for ($i = 0; $i < $periodLength; $i++) {
                    $d = $next->copy()->addDays($i);
                    if ($d->between($start, $end)) {
                        $periods[] = $d->format('Y-m-d');
                    }
                }

                $ovulation = $next->copy()->subDays(14);
                if ($ovulation->between($start, $end)) {
                    $ovulations[] = $ovulation->format('Y-m-d');
                    for ($i = -5; $i <= 1; $i++) {
                        $f = $ovulation->copy()->addDays($i);
                        if ($f->between($start, $end)) {
                            $fertiles[] = $f->format('Y-m-d');
                        }
                    }
                }
            }
            $next->addDays($cycleLength);
        }

        return [
            'periods'   => array_unique($periods),
            'ovulation' => array_unique($ovulations),
            'fertile'   => array_unique($fertiles),
        ];
    }

    private function recalculateCycle($user): void
    {
        // Все даты с месячными, отсортированные, без дублей
        $dates = $user->periodLogs()
            ->orderBy('date')
            ->pluck('date')
            ->map(fn($d) => $d->startOfDay())
            ->unique()
            ->values();

        if ($dates->count() < 2) {
            $user->update([
                'average_cycle_length' => null,
                'average_period_length' => null,
            ]);
            return;
        }

        // Определяем НАЧАЛО каждого цикла (первый день после разрыва >= 10 дней)
        $cycleStarts = [];
        $prev = null;
        foreach ($dates as $date) {
            if ($prev === null || $date->diffInDays($prev) >= 10) {
                $cycleStarts[] = $date;
            }
            $prev = $date;
        }

        // Длина циклов — разница между началами
        $cycleLengths = [];
        for ($i = 1; $i < count($cycleStarts); $i++) {
            $cycleLengths[] = $cycleStarts[$i]->diffInDays($cycleStarts[$i - 1]);
        }

        // Длина месячных — цепочки подряд идущих дней
        $periodLengths = [];
        $streak = 1;
        for ($i = 1; $i < $dates->count(); $i++) {
            if ($dates[$i]->diffInDays($dates[$i - 1]) === 1) {
                $streak++;
            } else {
                if ($streak >= 2) $periodLengths[] = $streak;
                $streak = 1;
            }
        }
        if ($streak >= 2) $periodLengths[] = $streak;

        $user->update([
            'average_cycle_length'  => $cycleLengths ? (int) round(array_sum($cycleLengths) / count($cycleLengths)) : null,
            'average_period_length' => $periodLengths ? (int) round(array_sum($periodLengths) / count($periodLengths)) : 5,
        ]);
    }

    /**
     * GET /api/calendar/symptoms
     * Получить список всех доступных симптомов
     *
     * @group Calendar
     * @authenticated
     *
     * @response 200 array
     * @response 401 {"message":"Unauthenticated."}
     *
     * @responseExample 200
     * [
     *   {
     *     "key": "cramps",
     *     "name_ru": "Спазмы",
     *     "name_en": "Cramps",
     *     "icon": "wave-sine",
     *     "color": "#ef4444"
     *   },
     *   {
     *     "key": "sex",
     *     "name_ru": "Секс",
     *     "name_en": "Sex",
     *     "icon": "heart",
     *     "color": "#ec4899"
     *   }
     * ]
     */
    public function symptoms(): JsonResponse
    {
        return response()->json(Symptom::select('key', 'name_ru', 'name_en', 'icon', 'color')->get());
    }
}
