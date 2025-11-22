<?php

namespace Database\Factories;

use App\Enums\TicketTypes;
use App\Models\Event;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ticket>
 */
class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'type' => fake()->randomElement([TicketTypes::VIP->value, TicketTypes::Standard->value, TicketTypes::Economy->value]),
            'price' => fake()->randomFloat(2, 10, 1000),
            'quantity' => fake()->numberBetween(1, 100),
        ];
    }

    /**
     * Create a ticket for a specific event.
     */
    public function forEvent(Event $event): static
    {
        return $this->state(fn (array $attributes) => [
            'event_id' => $event->id,
        ]);
    }

    /**
     * Create a standard ticket.
     */
    public function standard(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => TicketTypes::Standard->value,
            'price' => fake()->randomFloat(2, 50, 200),
        ]);
    }

    /**
     * Create a VIP ticket.
     */
    public function vip(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => TicketTypes::VIP->value,
            'price' => fake()->randomFloat(2, 200, 1000),
        ]);
    }

    /**
     * Create an economy ticket.
     */
    public function economy(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => TicketTypes::Economy->value,
            'price' => fake()->randomFloat(2, 30, 150),
        ]);
    }

    /**
     * Create a ticket with limited quantity.
     */
    public function limited(int $quantity = 5): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => $quantity,
        ]);
    }

    /**
     * Create a sold out ticket (quantity = 0).
     */
    public function soldOut(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => 0,
        ]);
    }
}
