<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    /**
     * Получить профиль пользователя
     *
     * @group Profile
     * @authenticated
     * @response 200 {
     *     "email": "user@example.com",
     *     "is_pregnant": true,
     *     "due_date": "2026-06-15"
     * }
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'email' => $user->email,
            'is_pregnant' => $user->is_pregnant,
            'due_date' => $user->due_date,
        ]);
    }

    /**
     * Обновить профиль (беременность)
     *
     * @group Profile
     * @authenticated
     * @bodyParam is_pregnant boolean optional Беременна. Example: true
     * @bodyParam due_date date optional Ожидаемая дата родов (Y-m-d). Example: 2026-06-15
     * @response 200 {
     *     "message": "Profile updated",
     *     "is_pregnant": true,
     *     "due_date": "2026-06-15"
     * }
     * @response 422 {
     *     "errors": { "due_date": ["The due date field must be a valid date."] }
     * }
     */
    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'is_pregnant' => 'nullable|boolean',
            'due_date' => 'nullable|date|after:today',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->user();

        $user->update([
            'is_pregnant' => $request->boolean('is_pregnant', $user->is_pregnant),
            'due_date' => $request->filled('due_date') ? $request->date('due_date') : $user->due_date,
        ]);

        if ($request->boolean('is_pregnant') && !$user->wasChanged('is_pregnant')) {
            $user->calendarDays()
                ->where('is_period_predicted', true)
                ->where('date', '>', now())
                ->delete();
        }

        return response()->json([
            'message' => 'Profile updated',
            'is_pregnant' => $user->is_pregnant,
            'due_date' => $user->due_date?->format('Y-m-d'),
        ]);
    }
}
