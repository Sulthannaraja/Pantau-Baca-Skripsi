<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\ReadingLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReadingLog>
 */
class ReadingLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'book_id' => Book::factory(),
            'duration' => fake()->numberBetween(5, 120),
            'progress' => fake()->numberBetween(10, 100),
            'last_page' => fake()->numberBetween(1, 100),
            'started_at' => now()->subMinutes(fake()->numberBetween(5, 180)),
        ];
    }
}
