<?php

namespace App\Http\Controllers\v1;

use App\Enums\TicketTypes;
use App\Http\Controllers\Controller;
use App\Http\Requests\v1\Tickets\StoreTicketRequest;
use App\Services\v1\TicketService;
use App\Traits\v1\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

class TicketController extends Controller
{
    use ApiResponseTrait;

    protected TicketService $service;

    public function __construct()
    {
        $this->service = new TicketService(user: request()->user());
    }

    public function create(StoreTicketRequest $request, int $eventId): JsonResponse
    {

        $data = $this->service->createTicket(
            eventId: $eventId,
            type: TicketTypes::from($request->validated('type')),
            price: $request->validated('price'),
            quantity: $request->validated('quantity'),
        );

        return $this->apiResponse::created(
            message: 'Ticket created successfully.',
            data: $data
        );
    }

    public function update(StoreTicketRequest $request, int $id): JsonResponse
    {

        $data = $this->service->updateTicket(
            ticketId: $id,
            type: TicketTypes::from($request->validated('type')),
            price: $request->validated('price'),
            quantity: $request->validated('quantity'),
        );

        return $this->apiResponse::success(
            message: 'Ticket updated successfully.',
            data: $data
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $data = $this->service->deleteTicket(
            ticketId: $id
        );

        return $this->apiResponse::success(
            message: 'Ticket deleted successfully.',
            data: $data
        );
    }
}
