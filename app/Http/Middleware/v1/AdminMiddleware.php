<?php

namespace App\Http\Middleware\v1;

use App\Enums\UserRoles;
use App\Libs\v1\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        $abort = function () {
            return ApiResponse::accessDenied(
                message: 'You are not authorized to access this resource.',
                data: null,
                error: 'Unauthorized',
                errors: [
                    'message' => 'You are not authorized to access this resource.',
                    'type' => 'Unauthorized',
                    'url' => request()->url(),
                    'method' => request()->method(),
                ]
            );
        };

        if (! $request->user()) {
            return $abort();
        }

        if ($request->user()->role !== UserRoles::Admin->value) {
            return $abort();
        }

        return $next($request);
    }
}
