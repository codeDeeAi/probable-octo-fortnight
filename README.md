# Laravel Event Booking API

A RESTful API built with Laravel that handles events, tickets, bookings, and payments — with authentication, caching, and queued notifications using Laravel Sanctum & Queue Workers.

## Requirements

- Software	Version
- PHP	8.3+
- Laravel	12.x
- Composer	2.x
- PostgreSQL/MySQL (PostgreSQL Preferred)

## Installation & Setup

- Clone the Repository

- Install Dependencies with `composer install`

- Copy & Configure .env (use the .env.development as a base/reference)

- Setup Database connections & Run Migrations with `php artisan migrate`
    ```
    DB_CONNECTION=pgsql
    DB_HOST=127.0.0.1
    DB_PORT=5432
    DB_DATABASE=ticketing_assessment
    DB_USERNAME=postgres
    DB_PASSWORD=postgres
    ````
- Seed Database `php artisan db:seed`
- Run Queue Workers `php artisan queue:work --queue=default --tries=3`
- Start the Application `php artisan serve`


## Running Tests

Run all tests with `php artisan test`

## Useful Commands

- Clear cache	`php artisan cache:clear`
- Clear all	`php artisan optimize:clear`
- Run queue	`php artisan queue:work`
- Tinker	`php artisan tinker`

## API Postman Docs

- Postman Collection: [https://documenter.getpostman.com/view/50248626/2sB3dHVCwC](https://documenter.getpostman.com/view/50248626/2sB3dHVCwC)

## License

This project is open-source under the MIT License.
