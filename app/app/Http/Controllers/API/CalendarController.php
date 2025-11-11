<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\PeriodPredictionService;
use App\Models\CalendarDay;
use App\Models\Symptom;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CalendarController extends Controller
{
    protected $predictionService;

    public function __construct(PeriodPredictionService $predictionService)
    {
        $this->predictionService = $predictionService;
    }

    /**
     * Получить календарь (всё: месячные, прогнозы, овуляция, симптомы)
     *
     * @group Calendar
     * @authenticated
     * @queryParam start_date date required Начало (Y-m-d). Example: 2025-11-01
     * @queryParam end_date date required Конец (Y-m-d). Example: 2026-02-28
     *
     * @response 200 [
     *   {
     *     "date": "2025-11-09",
     *     "is_period": true,
     *     "is_predicted": false,
     *     "is_ovulation": false,
     *     "is_fertile": false,
     *     "note": "День 1",
     *     "symptoms": ["period", "cramps", "sex"]
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
            ->map(function ($day) {
                $symptoms = $day->symptoms->pluck('key')->toArray(); // ← ИСПРАВЛЕНО!

                return [
                    'date' => $day->date->format('Y-m-d'),
                    'is_period' => $day->is_period_confirmed,
                    'is_predicted' => $day->is_period_predicted,
                    'is_ovulation' => $day->is_ovulation_predicted ?? false,
                    'is_fertile' => in_array('sex', $symptoms) || ($day->is_ovulation_predicted ?? false),
                    'note' => $day->note,
                    'symptoms' => $symptoms,
                ];
            });

        return response()->json($days);
    }

    /**
     * Обновить день → пересчитать ВЕСЬ календарь
     *
     * @group Calendar
     * @authenticated
     * @bodyParam date date required Дата. Example: 2025-11-09
     * @bodyParam is_period boolean optional Месячные. Example: true
     * @bodyParam symptoms array optional Симптомы. Example: ["sex", "cramps"]
     * @bodyParam note string optional Заметка. Example: День 1
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'is_period' => 'nullable|boolean',
            'symptoms' => 'nullable|array',
            'symptoms.*' => 'exists:symptoms,key',
            'note' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->user();
        $date = Carbon::parse($request->date);

        $day = $user->calendarDays()->updateOrCreate(
            ['date' => $date],
            [
                'is_period_confirmed' => $request->boolean('is_period', false),
                'note' => $request->note,
            ]
        );

        if ($request->filled('symptoms')) {
            $ids = Symptom::whereIn('key', $request->symptoms)->pluck('id');
            $day->symptoms()->sync($ids);
        } else {
            $day->symptoms()->detach();
        }

        // ПЕРЕСЧИТЫВАЕМ ВЕСЬ ПРОГНОЗ
        $this->predictionService->predict($user);

        return response()->json($day->load('symptoms'), 201);
    }

    protected function ensureCalendarRange($user, Carbon $start, Carbon $end): void
    {
        $existing = $user->calendarDays()
            ->whereBetween('date', [$start, $end])
            ->pluck('date')
            ->map->format('Y-m-d')
            ->toArray();

        $all = [];
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $all[] = $d->format('Y-m-d');
        }

        $missing = array_diff($all, $existing);

        if ($missing) {
            DB::table('calendar_days')->insert(
                array_map(fn($d) => [
                    'user_id' => $user->id,
                    'date' => $d,
                    'created_at' => now(),
                    'updated_at' => now(),
                ], $missing)
            );
        }
    }
}
