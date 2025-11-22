<?php

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Event;
use App\Models\Ticket;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->baseUrl = '/api';
    Notification::fake();
});

describe('Payment Processing', function () {
    it('can process payment for valid booking', function () {
        $customer = $this->authenticateAsCustomer();
        $event = Event::factory()->create();
        $ticket = Ticket::factory()->forEvent($event)->create(['price' => 100.00]);
        $booking = Booking::factory()
            ->forUser($customer)
            ->forTicket($ticket)
            ->pending()
            ->withQuantity(2)
            ->create();

        $response = $this->postJson(($this->baseUrl)."/bookings/{$booking->id}/payment");

        $response->assertStatus(201);

        $response->assertJsonStructure([
            'data' => ['id', 'booking_id', 'amount', 'status'],
        ]);

        $this->assertDatabaseHas('payments', [
            'booking_id' => $booking->id,
            'amount' => 200.00,
            'status' => PaymentStatus::Success->value,
        ]);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => BookingStatus::CONFIRMED->value,
        ]);
    });

    it('can simulate payment failure', function () {
        $customer = $this->authenticateAsCustomer();
        $event = Event::factory()->create();
        $ticket = Ticket::factory()->forEvent($event)->create(['price' => 100.00]);
        $booking = Booking::factory()
            ->forUser($customer)
            ->forTicket($ticket)
            ->create();

        $booking->update(['status' => BookingStatus::CANCELLED->value]);

        $response = $this->postJson(($this->baseUrl)."/bookings/{$booking->id}/payment");

        $response->assertStatus(500);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => BookingStatus::CANCELLED->value,
        ]);
    });
});
