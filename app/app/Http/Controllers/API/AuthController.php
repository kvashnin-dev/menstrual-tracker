<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    /**
     * Регистрация нового пользователя
     *
     * @group Authentication
     * @bodyParam email string required Email пользователя. Example: test@example.com
     * @bodyParam password string required Пароль (мин. 8 символов). Example: password123
     * @response 201 {
     *     "user": {
     *         "id": 1,
     *         "email": "test@example.com",
     *         "created_at": "2025-10-26T21:05:59.000000Z",
     *         "updated_at": "2025-10-26T21:05:59.000000Z"
     *     },
     *     "token": "1|random-token-string"
     * }
     * @response 422 {
     *     "errors": {
     *         "email": ["The email has already been taken."],
     *         "password": ["The password must be at least 8 characters."]
     *     }
     * }
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    /**
     * Аутентификация пользователя
     *
     * @group Authentication
     * @bodyParam email string required Email пользователя. Example: test@example.com
     * @bodyParam password string required Пароль. Example: password123
     * @response 200 {
     *     "user": {
     *         "id": 1,
     *         "email": "test@example.com",
     *         "created_at": "2025-10-26T21:05:59.000000Z",
     *         "updated_at": "2025-10-26T21:05:59.000000Z"
     *     },
     *     "token": "2|random-token-string"
     * }
     * @response 401 {
     *     "message": "Invalid credentials"
     * }
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials',
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    /**
     * Выход из системы
     *
     * @group Authentication
     * @authenticated
     * @response 200 {
     *     "message": "Logged out successfully"
     * }
     * @response 401 {
     *     "message": "Unauthenticated"
     * }
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }
}
