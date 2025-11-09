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
     * Verify email address
     * После регистрации строка с url нужным для подтверждения падает в storage/logs/laravel.log
     *
     * @group Authentication
     * @urlParam id required The ID of the user.
     * @urlParam hash required The verification hash.
     * @queryParam expires required The expiration timestamp.
     * @queryParam signature required The URL signature.
     * @response 200 {"message": "Email verified successfully"}
     * @response 400 {"message": "Invalid verification link"}
     * @response 200 {"message": "Email already verified"}
     */
    public function verify(Request $request, $id, $hash): JsonResponse
    {
        $user = User::findOrFail($id);

        // Проверка подписи (защита от подделки)
        if (!URL::hasValidSignature($request)) {
            return response()->json(['message' => 'Invalid or expired verification link'], 400);
        }

        // Проверка хеша
        if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return response()->json(['message' => 'Invalid verification hash'], 400);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified'], 200);
        }

        $user->markEmailAsVerified();
        event(new Verified($user));

        return response()->json(['message' => 'Email verified successfully! You can now log in.'], 200);
    }

    /**
     * Resend verification email
     *
     * @group Authentication
     * @authenticated
     * @response 200 {"message": "Verification link sent to logs"}
     * @response 429 {"message": "Too Many Attempts"}
     */
    public function resend(Request $request): JsonResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified'], 200);
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json(['message' => 'Verification link sent to logs'], 200);
    }
}
