<?php

use App\Http\Responses\ApiResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: env('API_BASE', '/api/v1'),
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Handle validation exceptions:
        $exceptions->render(function(ValidationException $exception): JsonResponse {
            $translatedMessage = __($exception->getMessage());
            $translatedErrors = collect($exception->errors())
                ->mapWithKeys(
                    fn($messages, $field): array => 
                        [
                            $field => array_map('__', $messages)
                        ]
                )->toArray();

            return ApiResponse::error(
                status: Response::HTTP_UNPROCESSABLE_ENTITY,
                message: $translatedMessage,
                errors: $translatedErrors
            );
        });
    })->create();
