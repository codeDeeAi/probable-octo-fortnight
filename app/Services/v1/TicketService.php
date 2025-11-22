<?php

declare(strict_types=1);

namespace App\Services\v1;

use App\Enums\TicketTypes;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TicketService
{
    public function __construct(protected User $user) {}

    public function createTicket(
        int $eventId,
        TicketTypes $type,
        int|float $price,
        int $quantity
    ): Ticket {
        try {

            $event = Event::where([
                'id' => $eventId,
                'created_by' => $this->user->id,
            ])->firstOrFail();

            $ticket = Ticket::create([
                'event_id' => $event->id,
                'type' => $type->value,
                'price' => $price,
                'quantity' => $quantity,
            ]);

            return $ticket;
        } catch (\Throwable $th) {
            Log::error('Error creating ticket', [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            throw $th;
        }
    }

    public function updateTicket(
        int $ticketId,
        ?TicketTypes $type = null,
        int|float|null $price = null,
        ?int $quantity = null
    ): Ticket {
        try {

            $ticket = Ticket::whereHas('event', function ($query) {
                $query->where('created_by', $this->user->id);
            })->where('id', $ticketId)->firstOrFail();

            $dataToUpdate = [];

            if ($type !== null) {
                $dataToUpdate['type'] = $type->value;
            }
            if ($price !== null) {
                $dataToUpdate['price'] = $price;
            }
            if ($quantity !== null) {
                $dataToUpdate['quantity'] = $quantity;
            }

            $ticket->update($dataToUpdate);

            return $ticket;
        } catch (\Throwable $th) {
            Log::error('Error updating ticket', [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            throw $th;
        }
    }

    public function deleteTicket(int $ticketId): bool
    {
        try {

            return DB::transaction(function () use ($ticketId) {
                $ticket = Ticket::whereHas('event', function ($query) {
                    $query->where('created_by', $this->user->id);
                })->where('id', $ticketId)->with('bookings.payment')->firstOrFail();

                $ticket->bookings->each(function ($booking) {
                    if ($booking->payment) {
                        $booking->payment->delete();
                    }

                    $booking->delete();
                });

                $ticket->delete();

                return true;
            });
        } catch (\Throwable $th) {
            Log::error('Error deleting ticket', [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            throw $th;
        }
    }
}
