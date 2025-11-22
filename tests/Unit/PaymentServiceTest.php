<?php

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Event;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\ConfirmBookingNotification;
use App\Services\v1\PaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
    Log::spy();

    $this->user = User::factory()->customer()->create();
    $this->paymentService = new PaymentService($this->user);
});

describe('PaymentService::processPayment', function () {
    it('processes payment successfully for valid booking', function () {
        $event = Event::factory()->create();
        $ticket = Ticket::factory()->forEvent($event)->create(['price' => 100.00]);
        $booking = Booking::factory()
            ->forUser($this->user)
            ->forTicket($ticket)
            ->pending()
            ->withQuantity(2)
            ->create();

        $result = $this->paymentService->processPayment($booking->id);

        expect($result)->toBeInstanceOf(Payment::class);
        expect($result->booking_id)->toBe($booking->id);
        expect($result->amount)->toBe(200.00);
        expect($result->status)->toBe(PaymentStatus::Success->value);

        // Check booking status updated
        $booking->refresh();
        expect($booking->status)->toBe(BookingStatus::CONFIRMED->value);

        // Check notification sent
        Notification::assertSentTo($this->user, ConfirmBookingNotification::class);
    });

    it('handles payment failure when failPayment flag is true', function () {
        $event = Event::factory()->create();
        $ticket = Ticket::factory()->forEvent($event)->create(['price' => 100.00]);
        $booking = Booking::factory()
            ->forUser($this->user)
            ->forTicket($ticket)
            ->pending()
            ->create();

        expect(fn () => $this->paymentService->processPayment($booking->id, true))
            ->toThrow(Exception::class, 'Payment processing failed due to simulated error.');

        // Check booking status remains unchanged (no payment record due to rollback)
        $booking->refresh();
        expect($booking->status)->toBe(BookingStatus::PENDING->value);

        // Check error is logged
        Log::shouldHaveReceived('error')
            ->with('Payment processing failed', \Mockery::type('array'))
            ->once();
    });

    it('fails when booking does not exist', function () {
        expect(fn () => $this->paymentService->processPayment(999))
            ->toThrow(Exception::class, 'Payment processing failed due to simulated error.');

        Log::shouldHaveReceived('error')->once();
    });

    it('fails when booking is already cancelled', function () {
        $event = Event::factory()->create();
        $ticket = Ticket::factory()->forEvent($event)->create(['price' => 100.00]);
        $booking = Booking::factory()
            ->forUser($this->user)
            ->forTicket($ticket)
            ->cancelled()
            ->create();

        expect(fn () => $this->paymentService->processPayment($booking->id))
            ->toThrow(Exception::class, 'Cannot process payment for a cancelled booking.');

        Log::shouldHaveReceived('error')->once();
    });

    it('returns existing payment when booking is already confirmed', function () {
        $event = Event::factory()->create();
        $ticket = Ticket::factory()->forEvent($event)->create(['price' => 100.00]);
        $booking = Booking::factory()
            ->forUser($this->user)
            ->forTicket($ticket)
            ->confirmed()
            ->create();

        $existingPayment = Payment::factory()
            ->forBooking($booking)
            ->successful()
            ->withAmount(100.00)
            ->create();

        $result = $this->paymentService->processPayment($booking->id);

        expect($result->id)->toBe($existingPayment->id);
        expect($result->status)->toBe(PaymentStatus::Success->value);

        // Should not send another notification
        Notification::assertNothingSent();
    });

    it('calculates correct amount based on ticket price and quantity', function () {
        $event = Event::factory()->create();
        $ticket = Ticket::factory()->forEvent($event)->create(['price' => 75.50]);
        $booking = Booking::factory()
            ->forUser($this->user)
            ->forTicket($ticket)
            ->pending()
            ->withQuantity(3)
            ->create();

        $result = $this->paymentService->processPayment($booking->id);

        expect($result->amount)->toBe(226.50); // 75.50 * 3
    });

    it('handles database transactions properly', function () {
        $event = Event::factory()->create();
        $ticket = Ticket::factory()->forEvent($event)->create(['price' => 100.00]);
        $booking = Booking::factory()
            ->forUser($this->user)
            ->forTicket($ticket)
            ->pending()
            ->create();

        // Mock DB transaction failure
        DB::shouldReceive('transaction')
            ->once()
            ->andThrow(new Exception('Database transaction failed'));

        expect(fn () => $this->paymentService->processPayment($booking->id))
            ->toThrow(Exception::class, 'Database transaction failed');

        Log::shouldHaveReceived('error')->once();
    });

    it('creates or updates payment record correctly', function () {
        $event = Event::factory()->create();
        $ticket = Ticket::factory()->forEvent($event)->create(['price' => 50.00]);
        $booking = Booking::factory()
            ->forUser($this->user)
            ->forTicket($ticket)
            ->pending()
            ->create();

        // Create an existing failed payment
        Payment::factory()
            ->forBooking($booking)
            ->failed()
            ->create();

        $result = $this->paymentService->processPayment($booking->id);

        // Check that payment was updated, not duplicated
        expect(Payment::where('booking_id', $booking->id)->count())->toBe(1);
        expect($result->status)->toBe(PaymentStatus::Success->value);
    });

    it('sends confirmation notification with correct booking data', function () {
        $event = Event::factory()->create();
        $ticket = Ticket::factory()->forEvent($event)->create(['price' => 100.00]);
        $booking = Booking::factory()
            ->forUser($this->user)
            ->forTicket($ticket)
            ->pending()
            ->create();

        $this->paymentService->processPayment($booking->id);

        Notification::assertSentTo($this->user, ConfirmBookingNotification::class, function ($notification) use ($booking) {
            return $notification->booking->id === $booking->id;
        });
    });
});

describe('PaymentService::getPaymentDetails', function () {
    it('returns payment with booking relationship', function () {
        $event = Event::factory()->create();
        $ticket = Ticket::factory()->forEvent($event)->create();
        $booking = Booking::factory()
            ->forUser($this->user)
            ->forTicket($ticket)
            ->create();

        $payment = Payment::factory()
            ->forBooking($booking)
            ->successful()
            ->create();

        $result = $this->paymentService->getPaymentDetails($payment->id);

        expect($result)->toBeInstanceOf(Payment::class);
        expect($result->id)->toBe($payment->id);
        expect($result->booking)->not->toBeNull();
        expect($result->booking->id)->toBe($booking->id);
    });

    it('throws exception when payment not found', function () {
        expect(fn () => $this->paymentService->getPaymentDetails(999))
            ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
    });

    it('loads booking relationship eagerly', function () {
        $event = Event::factory()->create();
        $ticket = Ticket::factory()->forEvent($event)->create();
        $booking = Booking::factory()
            ->forUser($this->user)
            ->forTicket($ticket)
            ->create();

        $payment = Payment::factory()
            ->forBooking($booking)
            ->create();

        $result = $this->paymentService->getPaymentDetails($payment->id);

        // Check that booking is loaded (no additional query needed)
        expect($result->relationLoaded('booking'))->toBeTrue();
    });

    it('returns payment with correct attributes', function () {
        $event = Event::factory()->create();
        $ticket = Ticket::factory()->forEvent($event)->create();
        $booking = Booking::factory()
            ->forUser($this->user)
            ->forTicket($ticket)
            ->create();

        $payment = Payment::factory()
            ->forBooking($booking)
            ->successful()
            ->withAmount(150.75)
            ->create();

        $result = $this->paymentService->getPaymentDetails($payment->id);

        expect($result->id)->toBe($payment->id);
        expect($result->booking_id)->toBe($booking->id);
        expect($result->amount)->toBe(150.75);
        expect($result->status)->toBe(PaymentStatus::Success->value);
    });
});
