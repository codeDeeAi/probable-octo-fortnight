<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\Event\StoreEventRequest;
use App\Services\v1\EventService;
use App\Traits\v1\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

class EventController extends Controller
{
    use ApiResponseTrait;

    protected EventService $service;

    public function __construct()
    {
        $this->service = new EventService(user: request()->user());
    }

    public function index(): JsonResponse
    {

        $search = request()->query('search', null);
        $date = request()->query('date', null);
        $location = request()->query('location', null);
        $orderBy = request()->query('orderBy', 'created_at');
        $orderDirection = request()->query('orderDirection', 'desc');
        $perPage = (int) request()->query('perPage', 25);
        $page = (int) request()->query('page', 1);

        $data = $this->service->listEvents(
            search: $search,
            date: $date,
            location: $location,
            orderBy: $orderBy,
            orderDirection: $orderDirection,
            perPage: $perPage,
            page: $page,
            useCache: true,
            cacheDurationInMinutes: 10,
        );

        return $this->apiResponse::success(
            message: 'Events fetched successfully.',
            data: $data
        );
    }

    public function create(StoreEventRequest $request): JsonResponse
    {

        $data = $this->service->createEvent(
            title: $request->validated('title'),
            description: $request->validated('description'),
            location: $request->validated('location'),
            date: $request->validated('date'),
        );

        return $this->apiResponse::created(
            message: 'Event created successfully.',
            data: $data
        );
    }

    public function update(StoreEventRequest $request, int $id): JsonResponse
    {

        $data = $this->service->updateEvent(
            eventId: $id,
            title: $request->validated('title'),
            description: $request->validated('description'),
            location: $request->validated('location'),
            date: $request->validated('date'),
        );

        return $this->apiResponse::success(
            message: 'Event updated successfully.',
            data: $data
        );
    }

    public function show(int $id): JsonResponse
    {
        $data = $this->service->getEventById(
            id: $id,
            relations: ['tickets:id,event_id,type,price,quantity']
        );

        return $this->apiResponse::success(
            message: 'Event fetched successfully.',
            data: $data
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $data = $this->service->deleteEvent(
            eventId: $id
        );

        return $this->apiResponse::success(
            message: 'Event deleted successfully.',
            data: $data
        );
    }
}
