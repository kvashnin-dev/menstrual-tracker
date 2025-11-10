<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class EmailVerificationController extends Controller
{
    /**
     * Подтверждение email
     *
     * После регистрации ссылка приходит в ответе API.
     * Открывается в браузере или WebView.
     *
     * @group Authentication
     * @urlParam id required ID пользователя.
     * @urlParam hash required SHA1-хеш email.
     * @queryParam expires required Время жизни (timestamp).
     * @queryParam signature required Подпись URL.
     * @response 200 {
     *     "message": "Email verified successfully! You can now log in.",
     *     "user_id": 1
     * }
     * @response 400 {
     *     "message": "Invalid or expired verification link"
     * }
     * @response 400 {
     *     "message": "Invalid verification hash"
     * }
     * @response 200 {
     *     "message": "Email already verified"
     * }
     */
    public function verify(Request $request, $id, $hash): JsonResponse
    {
        $user = User::findOrFail($id);

        if (!URL::hasValidSignature($request)) {
            return response()->json(['message' => 'Invalid or expired verification link'], 400);
        }

        if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return response()->json(['message' => 'Invalid verification hash'], 400);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified'], 200);
        }

        $user->markEmailAsVerified();
        event(new Verified($user));

        return response()->json([
            'message' => 'Email verified successfully! You can now log in.',
            'user_id' => $user->id
        ], 200);
    }

    /**
     * Повторная отправка ссылки на верификацию
     *
     * @group Authentication
     * @authenticated
     * @response 200 {
     *     "message": "Verification link generated",
     *     "verification_url": "http://localhost:8000/api/email/verify/1/abc123?expires=...&signature=...",
     *     "expires_in": "24 hours"
     * }
     * @response 200 {
     *     "message": "Email already verified"
     * }
     * @response 429 {
     *     "message": "Too Many Attempts"
     * }
     */
    public function resend(Request $request): JsonResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified'], 200);
        }

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addHours(24),
            ['id' => $request->user()->id, 'hash' => sha1($request->user()->getEmailForVerification())]
        );

        return response()->json([
            'message' => 'Verification link generated',
            'verification_url' => $verificationUrl,
            'expires_in' => '24 hours'
        ], 200);
    }
}
