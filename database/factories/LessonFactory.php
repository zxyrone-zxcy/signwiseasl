<?php

namespace Database\Factories;

use App\Models\Lesson;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lesson>
 */
class LessonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->unique()->words(2, true),
            'category' => fake()->randomElement(['Everyday', 'People', 'Essentials']),
            'difficulty' => fake()->randomElement(['Beginner', 'Beginner', 'Practice']),
            'duration_minutes' => fake()->numberBetween(3, 12),
            'description' => fake()->sentence(),
            'sign_reference' => fake()->paragraph(),
            'practice_tip' => fake()->sentence(),
        ];
    }
}
