<?php

namespace Database\Seeders;

use App\Models\Lesson;
use App\Models\User;
use App\Models\UserProgress;
use Illuminate\Database\Seeder;

class UserProgressSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::query()->firstOrCreate(
            ['email' => 'learner@signwise.test'],
            ['name' => 'Demo Learner', 'password' => 'password'],
        );

        foreach (Lesson::query()->orderBy('id')->get() as $index => $lesson) {
            UserProgress::query()->updateOrCreate(
                ['user_id' => $user->id, 'lesson_id' => $lesson->id],
                [
                    'is_completed' => $index < 3,
                    'quiz_score' => fake()->numberBetween(70, 100),
                    'last_practiced_at' => now()->subDays(fake()->numberBetween(0, 12)),
                ],
            );
        }
    }
}
