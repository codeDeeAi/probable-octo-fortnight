<?php

use App\Enums\BookingStatus;
use App\Models\Event;
use App\Models\Ticket;

beforeEach(function () {
    $this->baseUrl = '/api';
});

describe('Ticket Booking', function () {
    it('can book tickets as customer', function () {
        $customer = $this->authenticateAsCustomer();
        $event = Event::factory()->create();
        $ticket = Ticket::factory()->forEvent($event)->create([
            'quantity' => 10,
            'price' => 50.00,
        ]);

        $bookingData = [
            'quantity' => 2,
        ];

        $response = $this->postJson($this->baseUrl."/tickets/{$ticket->id}/bookings", $bookingData);

        $this->assertSuccessfulApiResponse($response, 201);

        $response->assertJsonStructure([
            'data' => ['id', 'user_id', 'ticket_id', 'quantity', 'status'],
        ]);

        $this->assertDatabaseHas('bookings', [
            'user_id' => $customer->id,
            'ticket_id' => $ticket->id,
            'quantity' => 2,
            'status' => BookingStatus::PENDING->value,
        ]);
    });
});
