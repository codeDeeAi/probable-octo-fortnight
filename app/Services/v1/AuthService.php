<?php

declare(strict_types=1);

namespace App\Services\v1;

use App\Enums\UserRoles;
use App\Exceptions\AuthenticationException;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthService
{
    public function registerUser(
        string $name,
        string $email,
        string $password,
        string $phone,
        UserRoles $role
    ): array {
        try {

            $l_email = strtolower($email);

            $existingUser = User::where('email', $l_email)->first();

            if ($existingUser) {
                throw new \Exception('User already exists, pls try logging in instead.');
            }

            $user = User::create([
                'name' => $name,
                'email' => $l_email,
                'password' => Hash::make($password),
                'phone' => $phone,
                'role' => $role->value,
            ]);

            return $user->only(['id', 'name', 'role']);
        } catch (\Throwable $th) {

            Log::error('Error registering user', [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            throw $th;
        }
    }

    public function loginUser(string $email, string $password, int $expiresInHrs = 24): array
    {
        try {

            $l_email = strtolower($email);

            $user = User::where('email', $l_email)->first();

            if (! $user || ! Hash::check($password, $user->password)) {
                throw new AuthenticationException('Invalid credentials provided.');
            }

            $token = $user->createToken(
                name: 'user-token',
                abilities: [
                    'role:'.$user->role,
                ],
                expiresAt: now()->addHours($expiresInHrs))->plainTextToken;

            return [
                'user' => $this->userProfile(user: $user),
                'token' => $token,
                'token_type' => 'Bearer',
            ];
        } catch (\Throwable $th) {

            Log::error('Error logging in user', [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            throw $th;
        }
    }

    public function logoutUser(User $user, bool $all_tokens = false): bool
    {
        if ($all_tokens) {
            $user->tokens()->delete();
        } else {
            $user->currentToken()->delete();
        }

        return true;
    }

    public function userProfile(User $user): array
    {
        return $user->only(['id', 'name', 'email', 'phone', 'role']);
    }
}
