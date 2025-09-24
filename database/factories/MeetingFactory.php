<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Meeting>
 */
class MeetingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'created_by' => \App\Models\User::factory(),
            'status' => fake()->randomElement(['active', 'completed', 'archived']),
            'language' => fake()->randomElement(['ja-JP', 'en-US', 'en-GB']),
        ];
    }
}
