<?php

declare(strict_types=1);

namespace App\Services\v1;

use App\Enums\BookingStatus;
use App\Exceptions\BadRequestException;
use App\Models\Booking;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BookingService
{
    public function __construct(protected User $user) {}

    public function listBookingsAsCustomer(
        int $page = 1,
        int $perPage = 15,
        array $relationships = []
    ): LengthAwarePaginator {
        try {

            return Booking::where('user_id', $this->user->id)
                ->with($relationships)
                ->paginate(
                    perPage: $perPage,
                    page: $page
                );

        } catch (\Throwable $th) {
            Log::error('Error listing bookings', [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            throw $th;
        }
    }

    public function createBooking(
        int $ticketId,
        int $quantity,
        BookingStatus $status = BookingStatus::PENDING,
    ): Booking {
        try {

            self::lockTicketForBooking($ticketId);

            $result = DB::transaction(function () use (
                $ticketId,
                $quantity,
                $status,
            ) {

                $ticket = Ticket::findOrFail($ticketId);

                if ($quantity > $ticket->quantity) {
                    throw new BadRequestException('Requested quantity exceeds available tickets.');
                }

                $booking = Booking::create([
                    'user_id' => $this->user->id,
                    'ticket_id' => $ticketId,
                    'quantity' => $quantity,
                    'status' => $status->value,
                ]);

                $ticket->decrement('quantity', $quantity);

                return $booking;
            });

            self::unlockTicketForBooking($ticketId);

            return $result;

        } catch (\Throwable $th) {

            self::unlockTicketForBooking($ticketId);

            Log::error('Error creating booking', [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            throw $th;
        }
    }

    public function cancelBooking(int $bookingId): Booking
    {
        try {

            $booking = Booking::where('user_id', $this->user->id)
                ->where('id', $bookingId)
                ->firstOrFail();

            if ($booking->status === BookingStatus::CANCELLED->value) {
                throw new BadRequestException('Booking is already cancelled.');
            }

            if ($booking->status === BookingStatus::CONFIRMED->value) {
                throw new BadRequestException('Cannot cancel a confirmed booking.');
            }

            $booking->update([
                'status' => BookingStatus::CANCELLED->value,
            ]);

            $booking->ticket->increment('quantity', $booking->quantity);

            return $booking;

        } catch (\Throwable $th) {
            Log::error('Error cancelling booking', [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            throw $th;
        }
    }

    public static function lockTicketForBooking(int $ticketId): bool
    {
        Cache::put(
            key: 'ticket_booking_lock_'.$ticketId,
            value: true,
            ttl: now()->addMinutes(5)
        );

        return true;
    }

    public static function unlockTicketForBooking(int $ticketId): bool
    {
        Cache::forget('ticket_booking_lock_'.$ticketId);

        return true;
    }

    public static function isTicketLockedForBooking(int $ticketId): bool
    {
        return Cache::has('ticket_booking_lock_'.$ticketId);
    }
}
