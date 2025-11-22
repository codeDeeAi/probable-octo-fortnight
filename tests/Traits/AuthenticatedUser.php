<?php

namespace Tests\Traits;

use App\Enums\UserRoles;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

trait AuthenticatedUser
{
    /**
     * Create and authenticate a user with the given role.
     */
    protected function authenticateAs(UserRoles $role = UserRoles::Customer): User
    {
        $user = User::factory()->{$role->value}()->create();
        Sanctum::actingAs($user);

        return $user;
    }

    /**
     * Create and authenticate an admin user.
     */
    protected function authenticateAsAdmin(): User
    {
        return $this->authenticateAs(UserRoles::Admin);
    }

    /**
     * Create and authenticate an organizer user.
     */
    protected function authenticateAsOrganizer(): User
    {
        return $this->authenticateAs(UserRoles::Organizer);
    }

    /**
     * Create and authenticate a customer user.
     */
    protected function authenticateAsCustomer(): User
    {
        return $this->authenticateAs(UserRoles::Customer);
    }

    /**
     * Create a user without authentication.
     */
    protected function createUser(UserRoles $role = UserRoles::Customer): User
    {
        return User::factory()->{$role->value}()->create();
    }

    /**
     * Get authentication headers for API requests.
     */
    protected function getAuthHeaders(?User $user = null): array
    {
        if ($user) {
            $token = $user->createToken('test-token')->plainTextToken;

            return [
                'Authorization' => 'Bearer '.$token,
                'Accept' => 'application/json',
            ];
        }

        return [
            'Accept' => 'application/json',
        ];
    }
}
