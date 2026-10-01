<?php

use App\Models\Lesson;
use App\Models\User;
use App\Models\UserProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows the public SignWise landing page and lesson catalog', function () {
    Lesson::factory()->count(3)->create();

    $this->get(route('home'))->assertOk()->assertSee('Learn sign language');
    $this->get(route('lessons.index'))->assertOk()->assertSee('Lessons');
});

it('requires a learner account to view progress and manage lessons', function () {
    $lesson = Lesson::factory()->create();

    $this->get(route('dashboard'))->assertRedirect(route('login'));
    $this->get(route('lessons.create'))->assertRedirect(route('login'));
    $this->post(route('lessons.progress.store', $lesson))->assertRedirect(route('login'));
});

it('records a learner lesson completion', function () {
    $user = User::factory()->create();
    $lesson = Lesson::factory()->create();

    $this->actingAs($user)
        ->post(route('lessons.progress.store', $lesson))
        ->assertRedirect(route('lessons.show', $lesson));

    $this->assertDatabaseHas('user_progress', [
        'user_id' => $user->id,
        'lesson_id' => $lesson->id,
        'is_completed' => true,
    ]);
});

it('seeds ten lessons and ten sample progress records', function () {
    $this->seed();

    expect(Lesson::query()->count())->toBe(10)
        ->and(UserProgress::query()->count())->toBe(10);
});

it('scores a vocabulary quiz and saves the result to learner progress', function () {
    $user = User::factory()->create();
    Lesson::factory()->count(10)->create();

    $this->actingAs($user)->get(route('quiz'))->assertOk();
    $answerKey = session('quiz.answer_key');

    $this->post(route('quiz.submit'), ['answers' => $answerKey])
        ->assertRedirect(route('dashboard'));

    $this->assertDatabaseHas('user_progress', [
        'user_id' => $user->id,
        'quiz_score' => 100,
    ]);
});

it('creates, updates, and deletes a lesson through the authenticated catalog', function () {
    $user = User::factory()->create();
    $lessonData = [
        'title' => 'New sign',
        'category' => 'Essentials',
        'difficulty' => 'Beginner',
        'duration_minutes' => 5,
        'description' => 'A useful sign to practice.',
        'sign_reference' => 'Move both hands clearly in front of you.',
        'practice_tip' => 'Keep your hands in frame.',
    ];

    $this->actingAs($user)->post(route('lessons.store'), $lessonData)->assertRedirect();
    $lesson = Lesson::query()->where('title', 'New sign')->firstOrFail();

    $this->patch(route('lessons.update', $lesson), [...$lessonData, 'title' => 'Updated sign'])
        ->assertRedirect(route('lessons.show', $lesson));
    $this->assertDatabaseHas('lessons', ['id' => $lesson->id, 'title' => 'Updated sign']);

    $this->delete(route('lessons.destroy', $lesson))->assertRedirect(route('lessons.index'));
    $this->assertDatabaseMissing('lessons', ['id' => $lesson->id]);
});
