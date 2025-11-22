<?php

use Carbon\Carbon;

beforeEach(function () {
    $this->baseUrl = '/api/events';
});

describe('Event Creation', function () {
    it('can create an event as organizer', function () {
        $organizer = $this->authenticateAsOrganizer();

        $eventData = [
            'title' => fake()->sentence(1),
            'description' => fake()->sentence(3),
            'location' => fake()->address(),
            'date' => Carbon::tomorrow()->format('Y-m-d'),
        ];

        $response = $this->postJson(($this->baseUrl), $eventData);

        $this->assertSuccessfulApiResponse($response, 201);

        $response->assertJsonStructure([
            'data' => ['id', 'title', 'description', 'location', 'date', 'created_by'],
        ]);

        $this->assertDatabaseHas('events', [
            'title' => $eventData['title'],
            'created_by' => $organizer->id,
        ]);
    });

    it('cannot create event as customer', function () {
        $this->authenticateAsCustomer();

        $eventData = [
            'title' => 'Tech Conference 2024',
            'description' => 'Annual technology conference',
            'location' => 'San Francisco Convention Center',
            'date' => Carbon::tomorrow()->format('Y-m-d'),
        ];

        $response = $this->postJson($this->baseUrl, $eventData);

        $this->assertForbidden($response);
    });

});
