<?php

namespace Tests\Traits;

use Illuminate\Testing\TestResponse;

trait ApiTestHelpers
{
    /**
     * Assert that the response has a successful API structure.
     */
    protected function assertSuccessfulApiResponse(TestResponse $response, int $status = 200): TestResponse
    {
        return $response->assertStatus($status)
            ->assertJsonStructure([
                'success',
                'code',
                'message',
                'data',
                'error',
                'errors',
            ])
            ->assertJson(['success' => true]);
    }

    /**
     * Assert that the response has an error API structure.
     */
    protected function assertErrorApiResponse(TestResponse $response, int $status = 400): TestResponse
    {
        return $response->assertStatus($status)
            ->assertJsonStructure([
                'success',
                'code',
                'message',
                'data',
                'error',
                'errors',
            ])
            ->assertJson(['success' => false]);
    }

    /**
     * Assert validation errors in API response.
     */
    protected function assertValidationErrors(TestResponse $response, array $fields): TestResponse
    {
        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors' => $fields,
            ])
            ->assertJson(['success' => false]);

        foreach ($fields as $field) {
            $response->assertJsonValidationErrors($field);
        }

        return $response;
    }

    /**
     * Assert unauthorized response.
     */
    protected function assertUnauthorized(TestResponse $response): TestResponse
    {
        return $response->assertStatus(401)
            ->assertJson(['success' => false]);
    }

    /**
     * Assert forbidden response.
     */
    protected function assertForbidden(TestResponse $response): TestResponse
    {
        return $response->assertStatus(403)
            ->assertJson(['success' => false]);
    }

    /**
     * Get default API headers.
     */
    protected function getApiHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }
}
