<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    /**
     * GET /api/profile
     * Получить данные профиля
     *
     * @group Profile
     * @authenticated
     *
     * @response 200 {
     *   "email": "test@test.com",
     *   "is_pregnant": false,
     *   "due_date": null
     * }
     * @response 401 {"message":"Unauthenticated."}
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'email'       => $user->email,
            'is_pregnant' => $user->is_pregnant,
            'due_date'    => $user->due_date ? $user->due_date->format('Y-m-d') : null,
        ]);
    }

    /**
     * PATCH /api/profile
     * Обновить статус беременности
     *
     * @group Profile
     * @authenticated
     *
     * @bodyParam is_pregnant boolean optional true — беременна, false — нет
     * @bodyParam due_date date optional Ожидаемая дата родов (Y-m-d), только если is_pregnant=true Example: 2026-08-15
     *
     * @response 200 {
     *   "message": "Profile updated",
     *   "is_pregnant": true,
     *   "due_date": "2026-08-15"
     * }
     * @response 401 {"message":"Unauthenticated."}
     * @response 422 scenario=invalid_date {"errors":{"due_date":["The due date field must be a valid date."]}}
     * @response 422 scenario=date_in_past {"errors":{"due_date":["The due date must be a date after today."]}}
     */
    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'is_pregnant' => 'nullable|boolean',
            'due_date'    => 'nullable|date|after:today',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->user();

        $user->update([
            'is_pregnant' => $request->boolean('is_pregnant', $user->is_pregnant),
            'due_date'    => $request->filled('due_date') ? $request->date('due_date') : ($request->boolean('is_pregnant') === false ? null : $user->due_date),
        ]);

        return response()->json([
            'message'     => 'Profile updated',
            'is_pregnant' => $user->is_pregnant,
            'due_date'    => $user->due_date?->format('Y-m-d'),
        ]);
    }
}
