<?php

namespace App\Http\Controllers\v1;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\v1\Booking\BookTicketRequest;
use App\Services\v1\BookingService;
use App\Traits\v1\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

class BookingController extends Controller
{
    use ApiResponseTrait;

    protected BookingService $service;

    public function __construct()
    {
        $this->service = new BookingService(user: request()->user());
    }

    public function index(): JsonResponse
    {

        $data = $this->service->listBookingsAsCustomer(
            relationships: [
                'ticket:id,event_id,type,price,quantity',
                'ticket.event:id,title,description,location,date',
                'payment:id,booking_id,amount,status',
            ],
        );

        return $this->apiResponse::success(
            message: 'Bookings retrieved successfully.',
            data: $data
        );
    }

    public function create(BookTicketRequest $request, int $ticketId): JsonResponse
    {

        $data = $this->service->createBooking(
            ticketId: $ticketId,
            quantity: $request->validated('quantity'),
            status: BookingStatus::PENDING
        );

        return $this->apiResponse::created(
            message: 'Booking created successfully.',
            data: $data
        );
    }

    public function cancelBooking(int $bookingId): JsonResponse
    {

        $data = $this->service->cancelBooking(
            bookingId: $bookingId,
        );

        return $this->apiResponse::success(
            message: 'Booking cancelled successfully.',
            data: $data
        );
    }
}
