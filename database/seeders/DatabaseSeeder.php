<?php

namespace Database\Seeders;

use App\Enums\BookingStatus;
use App\Enums\TicketTypes;
use App\Enums\UserRoles;
use App\Models\Booking;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $admin_count = 2;
        $organizers_count = 3;
        $customers_count = 10;
        $events_count = 5;
        $tickets_count = 15;
        $bookings_count = 20;

        DB::transaction(function () use ($admin_count, $organizers_count, $customers_count, $events_count, $tickets_count, $bookings_count) {
            // Seed Admin Users
            for ($i = 0; $i < $admin_count; $i++) {
                User::create([
                    'name' => fake()->name(),
                    'email' => fake()->unique()->safeEmail(),
                    'password' => bcrypt('password'),
                    'phone' => fake()->phoneNumber(),
                    'role' => UserRoles::Admin->value,
                ]);
            }

            // Seed Organizers
            for ($i = 0; $i < $organizers_count; $i++) {
                User::create([
                    'name' => fake()->name(),
                    'email' => fake()->unique()->safeEmail(),
                    'password' => bcrypt('password'),
                    'phone' => fake()->phoneNumber(),
                    'role' => UserRoles::Organizer->value,
                ]);
            }

            // Seed Customers
            for ($i = 0; $i < $customers_count; $i++) {
                User::create([
                    'name' => fake()->name(),
                    'email' => fake()->unique()->safeEmail(),
                    'password' => bcrypt('password'),
                    'phone' => fake()->phoneNumber(),
                    'role' => UserRoles::Customer->value,
                ]);
            }

            // Seed Events
            for ($i = 0; $i < $events_count; $i++) {
                Event::create([
                    'title' => fake()->sentence(3),
                    'description' => fake()->paragraph(),
                    'date' => fake()->dateTimeBetween('now', '+1 year'),
                    'location' => fake()->address(),
                    'created_by' => User::where('role', UserRoles::Organizer->value)->inRandomOrder()->first()->id,
                ]);
            }

            // Seed Tickets
            for ($i = 0; $i < $tickets_count; $i++) {
                $event = Event::inRandomOrder()->first();

                Ticket::create([
                    'event_id' => $event->id,
                    'type' => fake()->randomElement(TicketTypes::values()),
                    'price' => fake()->randomFloat(2, 10, 500),
                    'quantity' => fake()->numberBetween(50, 200),
                ]);
            }

            // Seed Bookings
            for ($i = 0; $i < $bookings_count; $i++) {
                $ticket = Ticket::inRandomOrder()->first();
                $customer = User::where('role', UserRoles::Customer->value)->inRandomOrder()->first();

                Booking::create([
                    'user_id' => $customer->id,
                    'ticket_id' => $ticket->id,
                    'quantity' => 1,
                    'status' => BookingStatus::PENDING->value,
                ]);

            }

        });
    }
}
