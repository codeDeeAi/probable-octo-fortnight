<?php

use App\Enums\UserRoles;

beforeEach(function () {
    $this->baseUrl = '/api';

    $this->generateUser = function (UserRoles $role) {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'SecureP@ssw0rd!',
            'password_confirmation' => 'SecureP@ssw0rd!',
            'phone' => fake()->unique()->numerify('+###########'),
            'role' => $role->value,
        ];
    };
});

describe('User Registration', function () {
    it('can register a customer user successfully', function () {
        $userData = ($this->generateUser)(UserRoles::Customer);

        $response = $this->postJson(($this->baseUrl).'/register', $userData);

        $this->assertSuccessfulApiResponse($response, 201);

        $response->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'role',
            ],
        ]);

        $this->assertDatabaseHas('users', [
            'name' => $userData['name'],
            'email' => $userData['email'],
            'role' => $userData['role'],
        ]);
    });

    it('can register an organizer user successfully', function () {
        $userData = ($this->generateUser)(UserRoles::Organizer);

        $response = $this->postJson(($this->baseUrl).'/register', $userData);

        $this->assertSuccessfulApiResponse($response, 201);

        $response->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'role',
            ],
        ]);

        $this->assertDatabaseHas('users', [
            'name' => $userData['name'],
            'email' => $userData['email'],
            'role' => $userData['role'],
        ]);
    });

    it('can register an admin user successfully', function () {
        $userData = ($this->generateUser)(UserRoles::Admin);

        $response = $this->postJson(($this->baseUrl).'/register', $userData);

        $this->assertSuccessfulApiResponse($response, 201);

        $response->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'role',
            ],
        ]);

        $this->assertDatabaseHas('users', [
            'name' => $userData['name'],
            'email' => $userData['email'],
            'role' => $userData['role'],
        ]);
    });

    it('validates required fields', function () {
        $response = $this->postJson(($this->baseUrl).'/register', []);

        $response->assertStatus(422);
    });
});
