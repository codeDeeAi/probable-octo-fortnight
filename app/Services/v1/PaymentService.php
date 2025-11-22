<?php

declare(strict_types=1);

namespace App\Services\v1;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\ConfirmBookingNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    public function __construct(protected User $user) {}

    public function processPayment(int $bookingId, bool $failPayment = false)
    {
        try {
            return DB::transaction(function () use ($bookingId, $failPayment) {
                $booking = Booking::with('ticket:id,price')->find($bookingId);

                if ($failPayment || ! $booking) {

                    if ($failPayment && $booking) {
                        $amount = (float) $booking->ticket->price * $booking->quantity;

                        Payment::updateOrCreate([
                            'booking_id' => $bookingId,
                        ], [
                            'amount' => $amount,
                            'status' => PaymentStatus::Failed->value,
                        ]);
                    }

                    throw new \Exception('Payment processing failed due to simulated error.');
                }

                $amount = (float) $booking->ticket->price * $booking->quantity;

                if ($booking->status === BookingStatus::CANCELLED->value) {
                    Payment::updateOrCreate([
                        'booking_id' => $bookingId,
                    ], [
                        'amount' => $amount,
                        'status' => PaymentStatus::Failed->value,
                    ]);

                    throw new \Exception('Cannot process payment for a cancelled booking.');
                }

                if ($booking->status === BookingStatus::CONFIRMED->value) {
                    return Payment::where('booking_id', $bookingId)->first();
                }

                $payment = Payment::updateOrCreate([
                    'booking_id' => $bookingId,
                ], [
                    'amount' => $amount,
                    'status' => PaymentStatus::Success->value,
                ]);

                $booking->update([
                    'status' => BookingStatus::CONFIRMED->value,
                ]);

                $this->user->notify(new ConfirmBookingNotification($booking));

                return $payment;
            });
        } catch (\Throwable $th) {

            Log::error('Payment processing failed', [
                'booking_id' => $bookingId,
                'error' => $th->getMessage(),
            ]);

            throw $th;
        }
    }

    public function getPaymentDetails(int $paymentId): ?Payment
    {
        return Payment::with('booking')->findOrFail($paymentId);
    }
}
