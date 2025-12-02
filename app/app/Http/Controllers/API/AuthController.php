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
     * @bodyParam email string required Валидный email (реальный домен)
     * @bodyParam password string required Минимум 8 символов
     * @bodyParam is_pregnant boolean optional default: false
     * @bodyParam due_date date optional Y-m-d, только если is_pregnant=true
     *
     * @response 201 scenario=success
     * @response 422 scenario=validation_failed {
     *   "errors": {
     *     "email": ["The email has already been taken."],
     *     "password": ["The password must be at least 8 characters."]
     *   }
     * }
     * @response 422 scenario=invalid_email {
     *   "errors": { "email": ["The email field must be a valid email address."] }
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
            'is_pregnant' => $request->boolean('is_pregnant', false),
            'due_date' => $request->date('due_date'),
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
     * Вход в систему
     *
     * @group Authentication
     * @bodyParam email string required
     * @bodyParam password string required
     *
     * @response 200 scenario=success
     * @response 401 scenario=wrong_credentials {
     *   "message": "Invalid credentials"
     * }
     * @response 403 scenario=not_verified {
     *   "message": "Please verify your email before logging in."
     * }
     * @response 422 scenario=validation_failed {
     *   "errors": { "email": ["The email field is required."] }
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
     *
     * @response 200 scenario=success {
     *   "message": "Logged out successfully"
     * }
     * @response 401 scenario=no_token {
     *   "message": "Unauthenticated."
     * }
     * @response 401 scenario=invalid_token {
     *   "message": "Unauthenticated."
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
