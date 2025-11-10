<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\LogEmailVerificationNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Регистрация нового пользователя
     *
     * @group Authentication
     * @bodyParam email string required Email пользователя. Example: test@example.com
     * @bodyParam password string required Пароль (мин. 8 символов). Example: password123
     * @response 201 {
     *     "message": "User registered. Please verify your email.",
     *     "verification_url": "http://localhost:8000/api/email/verify/1/abc123?expires=...&signature=...",
     *     "expires_in": "24 hours"
     * }
     * @response 422 {
     *     "errors": {
     *         "email": ["The email has already been taken."],
     *         "password": ["The password must be at least 8 characters."]
     *     }
     * }
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email:rfc,dns|unique:users,email',
            'password' => 'required|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Генерируем подписанную ссылку
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addHours(24),
            ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())]
        );

        return response()->json([
            'message' => 'User registered. Please verify your email.',
            'verification_url' => $verificationUrl,
            'expires_in' => '24 hours'
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
     *         "email_verified_at": "2025-11-09T10:00:00.000000Z"
     *     },
     *     "token": "2|random-token-string"
     * }
     * @response 401 {
     *     "message": "Invalid credentials"
     * }
     * @response 403 {
     *     "message": "Please verify your email before logging in."
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

        if (!$user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Please verify your email before logging in.',
            ], 403);
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
