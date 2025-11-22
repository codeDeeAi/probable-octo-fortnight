<?php

declare(strict_types=1);

namespace App\Libs\v1;

use App\Enums\HttpStatusCode;
use Illuminate\Http\JsonResponse;

/**
 * Class ApiResponse
 * Handles consistent API response formatting.
 */
class ApiResponse
{
    /**
     * General method to return a JSON response.
     *
     * @param  HttpStatusCode  $statusCode  HTTP status code.
     * @param  string  $message  Response message.
     * @param  mixed  $data  Response data.
     * @param  string|null  $error  Error message, if any.
     * @param  array  $errors  Array of validation or other errors.
     * @param  array  $headers  Additional headers for the response.
     */
    public static function json(
        HttpStatusCode $statusCode,
        bool $success,
        string $message,
        mixed $data = null,
        ?string $error = null,
        array $errors = [],
        array $headers = []
    ): JsonResponse {

        // Sanitize error messages to avoid leaking sensitive info
        if ($error && self::errorContainsSensitiveInfo((string) $error)) {
            $error = 'An internal error occurred. Please contact support.';

            $errors = array_map(function ($err) {
                return 'An internal error occurred. Please contact support.';
            }, $errors);
        }

        $response = [
            'success' => $success,
            'code' => $statusCode,
            'message' => $message,
            'data' => $data,
            'error' => $error,
            'errors' => $errors,
        ];

        return response()->json($response, $statusCode->value, $headers);
    }

    /**
     * Return a successful JSON response.
     *
     * @param  string  $message  Response message.
     * @param  mixed  $data  Response data.
     * @param  array  $headers  Additional headers for the response.
     */
    public static function success(string $message, mixed $data = null, array $headers = []): JsonResponse
    {
        return self::json(HttpStatusCode::OK, $success = true, $message, $data, null, [], $headers);
    }

    /**
     * Return a created JSON response.
     *
     * @param  string  $message  Response message.
     * @param  mixed  $data  Response data.
     * @param  array  $headers  Additional headers for the response.
     */
    public static function created(string $message, mixed $data = null, array $headers = []): JsonResponse
    {
        return self::json(HttpStatusCode::CREATED, $success = true, $message, $data, null, [], $headers);
    }

    /**
     * Return a JSON response with an error status code.
     *
     * @param  string  $message  Response message.
     * @param  mixed  $data  Response data.
     * @param  string|null  $error  Error message, if any.
     * @param  array  $errors  Array of validation or other errors.
     * @param  array  $headers  Additional headers for the response.
     */
    public static function error(
        string $message,
        mixed $data = null,
        ?string $error = null,
        array $errors = [],
        array $headers = [],
        HttpStatusCode $statusCode = HttpStatusCode::INTERNAL_SERVER_ERROR
    ): JsonResponse {
        return self::json($statusCode, $success = false, $message, $data, $error, $errors, $headers);
    }

    /**
     * Return a JSON response with a 400 Bad Request status code.
     *
     * @param  string  $message  Response message.
     * @param  mixed  $data  Response data.
     * @param  string|null  $error  Error message, if any.
     * @param  array  $errors  Array of validation or other errors.
     * @param  array  $headers  Additional headers for the response.
     */
    public static function badRequest(
        string $message,
        mixed $data = null,
        ?string $error = null,
        array $errors = [],
        array $headers = []
    ): JsonResponse {
        return self::json(HttpStatusCode::BAD_REQUEST, $success = false, $message, $data, $error, $errors, $headers);
    }

    /**
     * Return a JSON response with a 422 Unauthorized status code.
     *
     * @param  string  $message  Response message.
     * @param  mixed  $data  Response data.
     * @param  string|null  $error  Error message, if any.
     * @param  array  $errors  Array of validation or other errors.
     * @param  array  $headers  Additional headers for the response.
     */
    public static function validationError(
        string $message,
        mixed $data = null,
        ?string $error = null,
        array $errors = [],
        array $headers = []
    ): JsonResponse {
        return self::json(HttpStatusCode::UNPROCESSABLE_ENTITY, $success = false, $message, $data, $error, $errors, $headers);
    }

    /**
     * Return a JSON response with a 404 Not Found status code.
     *
     * @param  string  $message  Response message.
     * @param  mixed  $data  Response data.
     * @param  string|null  $error  Error message, if any.
     * @param  array  $errors  Array of validation or other errors.
     * @param  array  $headers  Additional headers for the response.
     */
    public static function notFound(
        string $message,
        mixed $data = null,
        ?string $error = null,
        array $errors = [],
        array $headers = []
    ): JsonResponse {
        return self::json(HttpStatusCode::NOT_FOUND, $success = false, $message, $data, $error, $errors, $headers);
    }

    /**
     * Return a JSON response with a 500 Internal Server Error status code.
     *
     * @param  string  $message  Response message.
     * @param  mixed  $data  Response data.
     * @param  string|null  $error  Error message, if any.
     * @param  array  $errors  Array of validation or other errors.
     * @param  array  $headers  Additional headers for the response.
     */
    public static function serverError(
        string $message,
        mixed $data = null,
        ?string $error = null,
        array $errors = [],
        array $headers = []
    ): JsonResponse {
        return self::json(HttpStatusCode::INTERNAL_SERVER_ERROR, $success = false, $message, $data, $error, $errors, $headers);
    }

    /**
     * Return a JSON response with a 403 Forbidden status code (Access Denied).
     *
     * @param  string  $message  Response message.
     * @param  mixed  $data  Response data.
     * @param  string|null  $error  Error message, if any.
     * @param  array  $errors  Array of validation or other errors.
     * @param  array  $headers  Additional headers for the response.
     */
    public static function accessDenied(
        string $message,
        mixed $data = null,
        ?string $error = null,
        array $errors = [],
        array $headers = []
    ): JsonResponse {
        return self::json(HttpStatusCode::FORBIDDEN, $success = false, $message, $data, $error, $errors, $headers);
    }

    /**
     * Return a JSON response with a 401 Forbidden status code (Unauthenticated).
     *
     * @param  string  $message  Response message.
     * @param  mixed  $data  Response data.
     * @param  string|null  $error  Error message, if any.
     * @param  array  $errors  Array of validation or other errors.
     * @param  array  $headers  Additional headers for the response.
     */
    public static function unauthorized(
        string $message,
        mixed $data = null,
        ?string $error = null,
        array $errors = [],
        array $headers = []
    ): JsonResponse {
        return self::json(HttpStatusCode::UNAUTHORIZED, $success = false, $message, $data, $error, $errors, $headers);
    }

    /**
     * Detect if the error message or trace contains sensitive details.
     */
    private static function errorContainsSensitiveInfo(string $error): bool
    {
        $isProduction = app()->environment('production') || ! config('app.debug');

        if (! $isProduction) {
            return false;
        }

        $patterns = [
            // Database-related
            '/SQLSTATE/i',
            '/invalid\s+input\s+syntax\s+for\s+type\s+uuid/i',
            '/\bselect\s+.*\s+from\b/i',
            '/\binsert\s+into\b/i',
            '/\bupdate\s+.*\s+set\b/i',
            '/\bdelete\s+from\b/i',

            // Framework & app paths
            '/app\/Http/i',
            '/vendor\/laravel/i',
            '/vendor\/symfony/i',
            '/vendor\/doctrine/i',

            // Environment & config leaks
            '/\.env/i',
            '/AKIA[0-9A-Z]{16}/i',
            '/AIza[0-9A-Za-z\-_]{35}/',
            '/sk_live_[0-9a-zA-Z]+/',

            // PHP & execution details
            '/PHP\/[0-9]+\.[0-9]+\.[0-9]+/',
            '/eval\(/i',
            '/shell_exec/i',
            '/base64_decode/i',
        ];

        $haystack = $error;

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $haystack)) {
                return true;
            }
        }

        return false;
    }
}
