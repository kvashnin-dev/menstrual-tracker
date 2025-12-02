<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $e): Response|JsonResponse|\Symfony\Component\HttpFoundation\Response
    {
        // Все API-запросы — только JSON
        if ($request->expectsJson() || $request->is('api/*')) {

            // 1. Валидация — уже красиво
            if ($e instanceof ValidationException) {
                return response()->json([
                    'message' => 'Проверьте введённые данные',
                    'errors'  => $e->errors(),
                ], 422);
            }

            // 2. Не авторизован
            if ($e instanceof AuthenticationException) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            // 3. Нет доступа (например, email не подтверждён)
            if ($e instanceof AuthorizationException) {
                return response()->json(['message' => 'Forbidden.'], 403);
            }

            // 4. HTTP-ошибки (404, 429, 500 и т.д.)
            if ($e instanceof HttpException) {
                return response()->json(['message' => $e->getMessage() ?: 'Server error'], $e->getStatusCode());
            }

            // 5. Любая другая непойманная ошибка — в продакшене скрываем детали
            $status = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
            $message = app()->environment('production')
                ? 'Внутренняя ошибка сервера'
                : $e->getMessage();

            return response()->json([
                'message' => $message,
                // 'file' => $e->getFile(),    // убирай в продакшене
                // 'line' => $e->getLine(),
                // 'trace' => $e->getTraceAsString(),
            ], $status);
        }

        return parent::render($request, $e);
    }
}
