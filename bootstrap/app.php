<?php

use App\Http\Responses\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\UnauthorizedException;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: env('API_PATH', 'api/v1'),
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Handle validation exceptions:
        $exceptions->render(function(ValidationException $exception): JsonResponse {
            $translatedMessage = __($exception->getMessage());
            $translatedErrors = collect($exception->errors())
                ->mapWithKeys(
                    fn($messages, $field): array => 
                        [
                            $field => __($messages[0] ?? 'http-statuses.0')
                        ]
                )->toArray();

            return ApiResponse::error(
                status: Response::HTTP_UNPROCESSABLE_ENTITY,
                message: $translatedMessage,
                errors: $translatedErrors
            );
        });

        // Handle unauthorized exceptions:
        $exceptions->render(function(UnauthorizedException $exception): JsonResponse {
            $translatedMessage = __($exception->getMessage() ?: 'http-statuses.403');

            return ApiResponse::error(
                status: Response::HTTP_FORBIDDEN,
                message: $translatedMessage
            );
        });

        // Handle unauthenticated exceptions:
        $exceptions->render(function(AuthenticationException $exception): JsonResponse {            
            $translatedMessage = __($exception->getMessage() ?: 'http-statuses.401');
            
            return ApiResponse::error(
                status: Response::HTTP_UNAUTHORIZED,
                message: $translatedMessage
            );
        });

        // Handle NotFoundHttpException:
        $exceptions->render(function(NotFoundHttpException $exception): JsonResponse {
            $message = $exception->getMessage();
            if (str_starts_with($message, 'No query results for model')) {
                preg_match('/\[([^\]]+)\]/', $message, $matches);
                $attribute = $matches[1] ?? $message;
                $message = __('messages.common.not_found', ['item' => "[$attribute]"]);
            }

            return ApiResponse::error(
                status: Response::HTTP_NOT_FOUND,
                message: $message
            );
        });

        // Handle unsupported method exceptions:
        $exceptions->render(function(MethodNotAllowedHttpException $exception): JsonResponse {
            $message = $exception->getMessage();
            if (str_starts_with($message, 'Method not allowed for route')) {
                preg_match('/\[([^\]]+)\]/', $message, $matches);
                $method = $matches[1] ?? $message;
                $message = __('http-statuses.405', ['method' => $method]);
            }
            return ApiResponse::error(
                status: Response::HTTP_METHOD_NOT_ALLOWED,
                message: $message
            );
        });

        // Handle any other exceptions:
        $exceptions->render(function(\Throwable $exception): JsonResponse {
            $statusCode = $exception instanceof HttpExceptionInterface
                ? $exception->getStatusCode()
                : Response::HTTP_INTERNAL_SERVER_ERROR;
            $translatedMessage = __($exception->getMessage() ?: 'http-statuses.500');

            return ApiResponse::error(
                status: $statusCode,
                message: $translatedMessage
            );
        });
    })->create();
