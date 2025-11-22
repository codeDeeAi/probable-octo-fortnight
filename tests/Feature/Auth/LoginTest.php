<?php

use App\Enums\UserRoles;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->baseUrl = '/api';
});

describe('User Login', function () {
    it('can login with valid credentials', function () {

        $email = fake()->unique()->safeEmail();

        $user = User::factory()->customer()->create([
            'email' => $email,
            'password' => Hash::make('password'),
        ]);

        $loginData = [
            'email' => $email,
            'password' => 'password',
        ];

        $response = $this->postJson(($this->baseUrl).'/login', $loginData);

        $this->assertSuccessfulApiResponse($response);

        $response->assertJsonStructure([
            'data' => [
                'user' => ['id', 'name', 'email', 'phone', 'role'],
                'token',
            ],
        ]);

        expect($response->json('data.user.id'))->toBe($user->id);
        expect($response->json('data.token'))->not->toBeNull();
    });

    it('returns error for invalid email', function () {
        $loginData = [
            'email' => 'nonexistent@example.com',
            'password' => 'password',
        ];

        $response = $this->postJson(($this->baseUrl).'/login', $loginData);

        $response->assertStatus(401);
    });

    it('can login different user roles', function () {
        $organizer = User::factory()->organizer()->create([
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
        ]);

        $loginData = [
            'email' => $organizer->email,
            'password' => 'password',
        ];

        $response = $this->postJson(($this->baseUrl).'/login', $loginData);

        $this->assertSuccessfulApiResponse($response);

        $response->assertJsonStructure([
            'data' => [
                'user' => ['id', 'name', 'email', 'phone', 'role'],
                'token',
            ],
        ]);

        expect($response->json('data.user.id'))->toBe($organizer->id);
        expect($response->json('data.user.role'))->toBe(UserRoles::Organizer->value);
        expect($response->json('data.token'))->not->toBeNull();
    });
});

describe('User Logout', function () {
    it('can logout successfully', function () {
        $user = $this->authenticateAsCustomer();

        $response = $this->postJson(($this->baseUrl).'/logout');

        $response->assertStatus(200);
    });

    it('requires authentication', function () {
        $response = $this->postJson(($this->baseUrl).'/logout');

        $this->assertUnauthorized($response);
    });
});

describe('User Profile', function () {
    it('can fetch user profile', function () {
        $user = $this->authenticateAsCustomer();

        $response = $this->getJson($this->baseUrl.'/me');

        $this->assertSuccessfulApiResponse($response);

        $response->assertJsonStructure([
            'data' => ['id', 'name', 'email', 'phone', 'role'],
        ]);

        expect($response->json('data.id'))->toBe($user->id);
        expect($response->json('data.email'))->toBe($user->email);
    });

    it('requires authentication', function () {
        $response = $this->getJson($this->baseUrl.'/me');

        $this->assertUnauthorized($response);
    });
});
