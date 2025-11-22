<?php

namespace App\Http\Controllers\v1;

use App\Enums\UserRoles;
use App\Http\Controllers\Controller;
use App\Http\Requests\v1\Auth\LoginRequest;
use App\Http\Requests\v1\Auth\RegisterRequest;
use App\Services\v1\AuthService;
use App\Traits\v1\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected AuthService $service) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $this->service->registerUser(
            name: $request->validated('name'),
            email: $request->validated('email'),
            password: $request->validated('password'),
            phone: $request->validated('phone'),
            role: UserRoles::from($request->validated('role')),
        );

        return $this->apiResponse::created(
            message: 'User registered successfully.',
            data: $data
        );
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $data = $this->service->loginUser(
            email: $request->validated('email'),
            password: $request->validated('password'),
            expiresInHrs: 24,
        );

        return $this->apiResponse::success(
            message: 'User logged in successfully.',
            data: $data
        );
    }

    public function logout(): JsonResponse
    {
        $user = request()->user();

        $this->service->logoutUser(
            user: $user,
            all_tokens: true
        );

        return $this->apiResponse::success(
            message: 'User logged out successfully.',
            data: null
        );
    }

    public function me(): JsonResponse
    {
        $user = request()->user();

        $data = $this->service->userProfile(
            user: $user
        );

        return $this->apiResponse::success(
            message: 'User profile fetched successfully.',
            data: $data
        );
    }
}
