<?php

declare(strict_types=1);

namespace App\Services\v1;

use App\Models\Event;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EventService
{
    public function __construct(protected User $user) {}

    public function listEvents(
        ?string $search = null,
        ?string $date = null,
        ?string $location = null,
        string $orderBy = 'created_at',
        string $orderDirection = 'desc',
        array $relations = [],
        int $perPage = 15,
        int $page = 1,
        array $columns = ['*'],
        ?string $createdBy = null,
        bool $useCache = true,
        int $cacheDurationInMinutes = 10,
    ): LengthAwarePaginator {
        try {

            $res = function () use (
                $search,
                $date,
                $location,
                $orderBy,
                $orderDirection,
                $relations,
                $perPage,
                $page,
                $columns,
                $createdBy,
            ) {

                return Event::query()
                    ->when($createdBy, fn ($q) => $q->where('created_by', $createdBy))
                    ->when($search, fn ($q) => $q = $q->searchByTitle($search))
                    ->when($location, function ($q) use ($location) {

                        $operator = $q->getConnection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';

                        return $q->where('location', $operator, "%{$location}%");
                    })
                    ->when($date, fn ($q) => $q->filterByDate($date, 'date'))
                    ->select($columns)
                    ->with($relations)
                    ->orderBy($orderBy, $orderDirection)
                    ->paginate(
                        perPage: $perPage,
                        page: $page
                    );
            };

            $cacheKey = 'LIST::events_'.md5(json_encode(func_get_args()));

            return ($useCache) ?
                Cache::remember(
                    key: $cacheKey,
                    ttl: now()->addMinutes($cacheDurationInMinutes),
                    callback: $res
                ) : $res();
        } catch (\Throwable $th) {

            Log::error('Error listing events', [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
                'user_id' => $this->user->id,
                'params' => [
                    'search' => $search,
                    'date' => $date,
                    'location' => $location,
                    'orderBy' => $orderBy,
                    'orderDirection' => $orderDirection,
                    'relations' => $relations,
                    'perPage' => $perPage,
                    'page' => $page,
                    'columns' => $columns,
                    'createdBy' => $createdBy,
                ],
            ]);

            throw $th;
        }
    }

    public function getEventById(int $id, array $relations = [], array $columns = ['*'], bool $useCache = true, int $cacheDurationInMinutes = 60): Event
    {
        try {

            $func = function () use ($id, $relations, $columns) {
                return Event::query()
                    ->where('id', $id)
                    ->select($columns)
                    ->with($relations)
                    ->firstOrFail();
            };

            $cacheKey = 'GET::event_'.$id;

            return ($useCache) ?
                Cache::remember(
                    key: $cacheKey,
                    ttl: now()->addMinutes($cacheDurationInMinutes),
                    callback: $func
                ) : $func();
        } catch (\Throwable $th) {

            Log::error('Error fetching event by ID', [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
                'user_id' => $this->user->id,
                'event_id' => $id,
                'relations' => $relations,
                'columns' => $columns,
            ]);

            throw $th;
        }
    }

    public function createEvent(
        string $title,
        string $description,
        string $location,
        Carbon|string $date
    ): Event {
        try {

            $date = Carbon::parse($date)->toDateString();

            $event = Event::create([
                'title' => $title,
                'description' => $description,
                'location' => $location,
                'date' => $date,
                'created_by' => $this->user->id,
            ]);

            return $event;
        } catch (\Throwable $th) {

            Log::error('Error creating event', [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
                'user_id' => $this->user->id,
                'data' => [
                    'title' => $title,
                    'description' => $description,
                    'location' => $location,
                    'date' => $date,
                ],
            ]);

            throw $th;
        }
    }

    public function updateEvent(
        string $eventId,
        ?string $title = null,
        ?string $description = null,
        ?string $location = null,
        Carbon|string|null $date = null
    ): Event {
        try {

            $event = Event::where('id', $eventId)
                ->where('created_by', $this->user->id)
                ->firstOrFail();

            $dataToUpdate = [];

            if ($title !== null) {
                $dataToUpdate['title'] = $title;
            }

            if ($description !== null) {
                $dataToUpdate['description'] = $description;
            }

            if ($location !== null) {
                $dataToUpdate['location'] = $location;
            }

            if ($date !== null) {
                $dataToUpdate['date'] = Carbon::parse($date)->toDateString();
            }

            $event->update($dataToUpdate);

            $cacheKey = 'GET::event_'.$eventId;

            Cache::forget($cacheKey);

            return $event;
        } catch (\Throwable $th) {

            Log::error('Error updating event', [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
                'user_id' => $this->user->id,
                'event_id' => $event->id,
                'data' => [
                    'title' => $title,
                    'description' => $description,
                    'location' => $location,
                    'date' => $date,
                ],
            ]);

            throw $th;
        }
    }

    public function deleteEvent(string $eventId): bool
    {
        try {

            return DB::transaction(function () use ($eventId) {
                $event = Event::where('id', $eventId)
                    ->where('created_by', $this->user->id)
                    ->with('tickets.bookings.payment')
                    ->firstOrFail();

                foreach ($event->tickets as $ticket) {
                    foreach ($ticket->bookings as $booking) {
                        if ($booking->payment) {
                            $booking->payment()->delete();
                        }
                    }

                    $ticket->bookings()->delete();
                }

                $event->tickets()->delete();

                $event->delete();

                $cacheKey = 'GET::event_'.$eventId;

                Cache::forget($cacheKey);

                return true;
            });
        } catch (\Throwable $th) {

            Log::error('Error deleting event', [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
                'user_id' => $this->user->id,
                'event_id' => $eventId,
            ]);

            throw $th;
        }
    }
}
