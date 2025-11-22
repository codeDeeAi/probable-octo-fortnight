<?php

use App\Enums\HttpStatusCode;
use App\Exceptions\BadRequestException;
use App\Http\Middleware\v1\AdminMiddleware;
use App\Http\Middleware\v1\CustomerMiddleware;
use App\Http\Middleware\v1\OrganizerMiddleware;
use App\Http\Middleware\v1\PreventTicketDoubleBooking;
use App\Libs\v1\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();

        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);

        $middleware->alias([
            'is.admin' => AdminMiddleware::class,
            'is.organizer' => OrganizerMiddleware::class,
            'is.customer' => CustomerMiddleware::class,
            'ticket.booking' => PreventTicketDoubleBooking::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(function (Request $request, Throwable $e) {
            return ($request->is('api/*')) ? true : false;
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return ApiResponse::unauthorized(
                    message: 'You are not authenticated.',
                    data: null,
                    error: 'Unauthorized',
                    errors: [
                        'message' => $e->getMessage(),
                        'type' => get_class($e),
                        'url' => $request->url(),
                        'method' => $request->method(),
                    ]
                );
            }
        });

        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return ApiResponse::accessDenied(
                    message: 'You do not have permission to perform this action.',
                    data: null,
                    error: 'Access Denied',
                    errors: [
                        'message' => $e->getMessage(),
                        'type' => get_class($e),
                        'url' => $request->url(),
                        'method' => $request->method(),
                    ]
                );
            }
        });

        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {

                $firstError = collect($e->errors())->first();

                $errors = collect($e->validator->errors()->messages())
                    ->flatten()
                    ->all();

                $errorMessage = is_array($firstError) ? $firstError[0] : 'Validation error occurred';

                return ApiResponse::validationError(
                    message: $errorMessage,
                    data: null,
                    error: $errorMessage,
                    errors: $errors
                );
            }
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {

                return ApiResponse::notFound(
                    $message = $e->getMessage() ?? 'Record not found.',
                    $data = null,
                    $error = $message,
                    $errors = [
                        'message' => $message,
                        'url' => $request->url(),
                        'method' => $request->method(),
                    ]
                );
            }
        });

        $exceptions->render(function (ThrottleRequestsException $e, Request $request) {
            if ($request->is('api/*')) {

                $headers = $e->getHeaders();

                $retryAfter = $headers['Retry-After'] ?? null;

                $seconds = (int) $retryAfter;

                $timeMessage = $seconds > 0
                    ? " Please wait {$seconds} second".($seconds > 1 ? 's' : '').' before trying again.'
                    : '';

                return ApiResponse::error(
                    message: 'You’re sending requests too quickly.'.$timeMessage,
                    data: null,
                    error: 'You have exceeded the request limit. Try again later.',
                    errors: [
                        'message' => $e->getMessage(),
                        'type' => get_class($e),
                        'url' => $request->url(),
                        'method' => $request->method(),
                        'retry_after_seconds' => $retryAfter,
                    ],
                    statusCode: HttpStatusCode::TOO_MANY_REQUESTS
                );
            }
        });

        $exceptions->render(function (BadRequestException $e, Request $request) {
            if ($request->is('api/*')) {

                return ApiResponse::badRequest(
                    message: $e->getMessage() ?? 'Bad request.',
                    data: null,
                    error: $e->getMessage(),
                    errors: [
                        'message' => $e->getMessage(),
                        'method' => $request->method(),
                    ]
                );
            }
        });

        $exceptions->render(function (\App\Exceptions\AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return ApiResponse::unauthorized(
                    message: $e->getMessage() ?? 'Authentication failed.',
                    data: null,
                    error: $e->getMessage(),
                    errors: [
                        'message' => $e->getMessage(),
                        'type' => get_class($e),
                        'url' => $request->url(),
                        'method' => $request->method(),
                    ]
                );
            }
        });

        $exceptions->render(function (\Throwable $e, Request $request) {
            if ($request->is('api/*')) {

                return ApiResponse::serverError(
                    message: 'An unexpected error occurred. Please try again later.',
                    data: null,
                    error: $e->getMessage(),
                    errors: [
                        'message' => $e->getMessage(),
                        'type' => get_class($e),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTrace(),
                        'url' => $request->url(),
                        'method' => $request->method(),
                    ]
                );
            }
        });
    })->create();
