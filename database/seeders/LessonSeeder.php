<?php

namespace Database\Seeders;

use App\Models\Lesson;
use Illuminate\Database\Seeder;

class LessonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $lessons = [
            ['title' => 'Hello', 'category' => 'Greetings', 'description' => 'Start a conversation with a friendly greeting.', 'sign_reference' => 'Raise your dominant hand near your forehead, palm facing outward, then move it away in a small wave.', 'practice_tip' => 'Keep your palm open and your movement relaxed.'],
            ['title' => 'Thank you', 'category' => 'Greetings', 'description' => 'Share appreciation in a clear, warm way.', 'sign_reference' => 'Touch the fingers of your dominant hand to your chin, palm angled toward you, then move the hand forward and down.', 'practice_tip' => 'Let the movement travel outward from your chin.'],
            ['title' => 'Please', 'category' => 'Everyday', 'description' => 'Make a polite request with one simple sign.', 'sign_reference' => 'Place your open dominant hand on your chest and move it in a small clockwise circle.', 'practice_tip' => 'Use a light touch and a smooth circular motion.'],
            ['title' => 'Sorry', 'category' => 'Everyday', 'description' => 'Express an apology with a steady hand shape.', 'sign_reference' => 'Make a loose fist with your dominant hand and move it in a small circle over your chest.', 'practice_tip' => 'Keep the fist relaxed rather than clenched.'],
            ['title' => 'Yes', 'category' => 'Essentials', 'description' => 'Respond positively with a compact movement.', 'sign_reference' => 'Make a fist with your dominant hand and nod it up and down at the wrist.', 'practice_tip' => 'Let the wrist create the motion.'],
            ['title' => 'No', 'category' => 'Essentials', 'description' => 'Give a clear negative response.', 'sign_reference' => 'Extend your index and middle fingers, then tap them against your thumb twice.', 'practice_tip' => 'Keep the movement small and distinct.'],
            ['title' => 'Help', 'category' => 'People', 'description' => 'Learn a useful sign for asking for assistance.', 'sign_reference' => 'Place a closed fist, palm up, on your open non-dominant palm and lift both hands together.', 'practice_tip' => 'Keep your supporting palm flat and steady.'],
            ['title' => 'Good', 'category' => 'Essentials', 'description' => 'Use a positive sign in everyday exchanges.', 'sign_reference' => 'Touch the fingers of your dominant hand to your lips, then bring the hand down to rest on your open palm.', 'practice_tip' => 'Move from your mouth toward your other hand.'],
            ['title' => 'More', 'category' => 'Everyday', 'description' => 'Ask for more of something with a repeated hand shape.', 'sign_reference' => 'Bring the fingertips of both hands together in front of your body and tap them together.', 'practice_tip' => 'Match the fingertips without pressing hard.'],
            ['title' => 'Love', 'category' => 'People', 'description' => 'Practice a familiar sign for affection.', 'sign_reference' => 'Cross both arms over your chest with your hands in fists, as if giving yourself a gentle hug.', 'practice_tip' => 'Keep your shoulders relaxed as you cross your arms.'],
        ];

        foreach ($lessons as $lesson) {
            Lesson::query()->updateOrCreate(
                ['title' => $lesson['title']],
                [
                    ...$lesson,
                    'difficulty' => fake()->randomElement(['Beginner', 'Practice']),
                    'duration_minutes' => fake()->numberBetween(3, 10),
                ],
            );
        }
    }
}
