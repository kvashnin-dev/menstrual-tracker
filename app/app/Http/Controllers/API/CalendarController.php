<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Jobs\GeneratePeriodPredictions;
use App\Models\CalendarDay;
use App\Models\Symptom;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CalendarController extends Controller
{
    /**
     * Получить календарь за диапазон дат
     *
     * @group Calendar
     * @authenticated
     * @queryParam start_date date required Начальная дата (Y-m-d). Example: 2025-11-01
     * @queryParam end_date date required Конечная дата (Y-m-d). Example: 2025-11-30
     * @response 200 [
     *   {
     *     "date": "2025-11-09",
     *     "is_period": true,
     *     "predicted": false,
     *     "note": "День 1",
     *     "symptoms": [
     *       {
     *         "id": 1,
     *         "key": "period",
     *         "name": "Месячные",
     *         "icon": "drop",
     *         "color": "#ef4444"
     *       }
     *     ]
     *   }
     * ]
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->query(), [
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->user();
        $start = Carbon::parse($request->query('start_date'));
        $end = Carbon::parse($request->query('end_date'));

        $this->ensureCalendarRange($user, $start, $end);

        $days = $user->calendarDays()
            ->with('symptoms')
            ->whereBetween('date', [$start, $end])
            ->orderBy('date')
            ->get()
            ->map(fn($day) => [
                'date' => $day->date->format('Y-m-d'),
                'is_period' => $day->is_period_confirmed,
                'predicted' => $day->is_period_predicted,
                'note' => $day->note,
                'symptoms' => $day->symptoms->map(fn($s) => [
                    'id' => $s->id,
                    'key' => $s->key,
                    'name' => $s->name_ru,
                    'icon' => $s->icon,
                    'color' => $s->color,
                ])->toArray(),
            ]);

        return response()->json($days);
    }

    /**
     * Добавить или обновить день
     *
     * @group Calendar
     * @authenticated
     * @bodyParam date date required Дата (Y-m-d). Example: 2025-11-09
     * @bodyParam symptoms array optional Массив ключей симптомов. Example: ["period", "cramps"]
     * @bodyParam note string optional Заметка дня. Example: День 1
     * @bodyParam is_period boolean optional Подтверждённые месячные. Example: true
     * @response 201 { ...день с симптомами... }
     * @response 422 { "errors": { "date": ["The date field is required."] } }
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'symptoms' => 'nullable|array',
            'symptoms.*' => 'exists:symptoms,key',
            'note' => 'nullable|string|max:1000',
            'is_period' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->user();
        $date = $request->date;

        $day = $user->calendarDays()->updateOrCreate(
            ['date' => $date],
            [
                'note' => $request->note,
                'is_period_confirmed' => $request->boolean('is_period', false),
            ]
        );

        if ($request->filled('symptoms')) {
            $symptomIds = Symptom::whereIn('key', $request->symptoms)->pluck('id');
            $day->symptoms()->sync($symptomIds);
        } else {
            $day->symptoms()->detach();
        }

        // Пересчитываем прогнозы
        GeneratePeriodPredictions::dispatch($user);

        return response()->json($day->load('symptoms'), 201);
    }

    /**
     * Получить прогнозы месячных
     *
     * @group Calendar
     * @authenticated
     * @response 200 [
     *   "2025-12-07",
     *   "2026-01-04"
     * ]
     */
    public function predictions(Request $request): JsonResponse
    {
        $predictions = $request->user()
            ->calendarDays()
            ->where('is_period_predicted', true)
            ->where('date', '>=', now())
            ->orderBy('date')
            ->take(3)
            ->pluck('date')
            ->map(fn($d) => $d->format('Y-m-d'));

        return response()->json($predictions);
    }

    // Вспомогательные методы
    protected function ensureCalendarRange($user, Carbon $start, Carbon $end): void
    {
        $existing = $user->calendarDays()
            ->whereBetween('date', [$start, $end])
            ->pluck('date')
            ->map->format('Y-m-d')
            ->toArray();

        $all = [];
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $all[] = $date->format('Y-m-d');
        }

        $missing = array_diff($all, $existing);

        if ($missing) {
            $insert = array_map(fn($d) => [
                'user_id' => $user->id,
                'date' => $d,
                'created_at' => now(),
                'updated_at' => now(),
            ], $missing);

            DB::table('calendar_days')->insert($insert);
        }
    }
}
