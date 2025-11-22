<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'location' => fake()->address(),
            'date' => fake()->dateTimeBetween('now', '+1 year')->format('Y-m-d'),
            'created_by' => User::factory(),
        ];
    }

    /**
     * Create an event with a specific organizer.
     */
    public function forOrganizer(User $organizer): static
    {
        return $this->state(fn (array $attributes) => [
            'created_by' => $organizer->id,
        ]);
    }

    /**
     * Create an event in the past.
     */
    public function past(): static
    {
        return $this->state(fn (array $attributes) => [
            'date' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
        ]);
    }

    /**
     * Create an event in the future.
     */
    public function future(): static
    {
        return $this->state(fn (array $attributes) => [
            'date' => fake()->dateTimeBetween('+1 day', '+1 year')->format('Y-m-d'),
        ]);
    }
}
