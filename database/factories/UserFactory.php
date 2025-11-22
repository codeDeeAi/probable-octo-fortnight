<?php

namespace Database\Factories;

use App\Enums\UserRoles;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'phone' => '+'.fake()->numerify('############'),
            'role' => fake()->randomElement([UserRoles::Customer->value, UserRoles::Organizer->value]),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Create a user with admin role.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRoles::Admin->value,
        ]);
    }

    /**
     * Create a user with organizer role.
     */
    public function organizer(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRoles::Organizer->value,
        ]);
    }

    /**
     * Create a user with customer role.
     */
    public function customer(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRoles::Customer->value,
        ]);
    }
}
