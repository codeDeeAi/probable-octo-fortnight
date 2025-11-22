<?php

use App\Http\Controllers\v1\BookingController;
use App\Http\Controllers\v1\EventController;
use App\Http\Controllers\v1\PaymentController;
use App\Http\Controllers\v1\TicketController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// • Ticket APIs:
// • POST /api/events/{event_id}/tickets (organizer only)
// • PUT /api/tickets/{id} (organizer only)
// • DELETE /api/tickets/{id} (organizer only)
// • Booking APIs:
// • POST /api/tickets/{id}/bookings (customer)
// • GET /api/bookings (customer’s bookings)
// • PUT /api/bookings/{id}/cancel (customer)
// • Payment APIs:
// • POST /api/bookings/{id}/payment (mock payment)
// • GET /api/payments/{id}

// Authentication Routes
Route::group(['controller' => \App\Http\Controllers\v1\AuthController::class], function () {
    Route::post('/register', 'register');
    Route::post('/login', 'login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', 'logout');
        Route::get('/me', 'me');
    });
});

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {

    // Event Routes
    Route::group(['prefix' => 'events', 'controller' => EventController::class], function () {
        Route::get('/', 'index');
        Route::get('/{id}', 'show');

        Route::middleware('is.organizer')->group(function () {
            Route::post('/', 'create');
            Route::put('/{id}', 'update');
            Route::delete('/{id}', 'destroy');
        });
    });

    // Ticket Routes
    Route::group([
        'middleware' => 'is.organizer',
        'controller' => TicketController::class,
    ], function () {
        Route::post('/events/{eventId}/tickets', 'create');
        Route::put('/tickets/{id}', 'update');
        Route::delete('/tickets/{id}', 'destroy');
    });

    // Booking Routes
    Route::group([
        'middleware' => 'is.customer',
        'controller' => BookingController::class,
    ], function () {
        Route::post('/tickets/{ticketId}/bookings', 'create');
        Route::get('/bookings', 'index');
        Route::put('/bookings/{bookingId}/cancel', 'cancelBooking');
    });

    // Payment Routes
    Route::group(['controller' => PaymentController::class], function () {
        Route::post('/bookings/{bookingId}/payment', 'makePayment');
        Route::get('/payments/{paymentId}', 'show');
    });

});
