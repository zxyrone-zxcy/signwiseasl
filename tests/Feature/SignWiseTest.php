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

    $this->post(route('quiz.submit'), ['results' => array_map(fn () => 1, $answerKey)])
        ->assertRedirect(route('quiz.results'));

    $this->get(route('dashboard'))->assertOk();

    $this->get(route('quiz.results'))
        ->assertOk()
        ->assertSee('100%')
        ->assertSee('5 of 5 correct');

    $this->assertDatabaseHas('user_progress', [
        'user_id' => $user->id,
        'quiz_score' => 100,
    ]);
});

it('creates, updates, and deletes a lesson through the authenticated catalog', function () {
    $user = User::factory()->admin()->create();
    $lessonData = [
        'title' => 'New sign',
        'category' => 'Essentials',
        'difficulty' => 'Beginner',
        'duration_minutes' => 5,
        'description' => 'A useful sign to practice.',
        'sign_reference' => 'Move both hands clearly in front of you.',
        'practice_tip' => 'Keep your hands in frame.',
    ];

    $createdLesson = Lesson::query()->where('title', 'New sign')->first();
    expect($createdLesson)->toBeNull();

    $this->actingAs($user)
        ->post(route('lessons.store'), $lessonData)
        ->assertRedirect(route('lessons.show', Lesson::query()->where('title', 'New sign')->firstOrFail()))
        ->assertSessionHas('status');

    $lesson = Lesson::query()->where('title', 'New sign')->firstOrFail();

    $this->patch(route('lessons.update', $lesson), [...$lessonData, 'title' => 'Updated sign'])
        ->assertRedirect(route('lessons.show', $lesson));
    $this->assertDatabaseHas('lessons', ['id' => $lesson->id, 'title' => 'Updated sign']);

    $this->delete(route('lessons.destroy', $lesson))->assertRedirect(route('lessons.index'));
    $this->assertDatabaseMissing('lessons', ['id' => $lesson->id]);
});

it('forbids non-admin learners from managing lessons', function () {
    $user = User::factory()->create();
    $lesson = Lesson::factory()->create();

    $this->actingAs($user)->get(route('lessons.create'))->assertForbidden();
    $this->actingAs($user)->get(route('lessons.edit', $lesson))->assertForbidden();
    $this->actingAs($user)->post(route('lessons.store'), [
        'title' => 'Not allowed',
        'category' => 'Essentials',
        'difficulty' => 'Beginner',
        'duration_minutes' => 5,
        'description' => 'Should not be created.',
        'sign_reference' => 'No reference.',
        'practice_tip' => 'No tip.',
    ])->assertForbidden();
    $this->actingAs($user)->patch(route('lessons.update', $lesson), ['title' => 'Nope'])->assertForbidden();
    $this->actingAs($user)->delete(route('lessons.destroy', $lesson))->assertForbidden();

    $this->assertDatabaseMissing('lessons', ['title' => 'Not allowed']);
});

it('logs each day of practice and builds a streak from consecutive days', function () {
    $user = User::factory()->create();
    $lesson = Lesson::factory()->create();

    $this->actingAs($user)->post(route('lessons.progress.store', $lesson));
    $this->post(route('lessons.progress.store', $lesson));

    $this->assertDatabaseHas('daily_activities', [
        'user_id' => $user->id,
        'activity_date' => today()->toDateString(),
        'lessons_practiced' => 2,
    ]);

    $user->dailyActivities()->create(['activity_date' => today()->subDay()->toDateString()]);
    $user->dailyActivities()->create(['activity_date' => today()->subDays(3)->toDateString()]);

    $this->get(route('dashboard'))->assertOk()->assertSee('2 days');
});

it('keeps yesterday’s streak alive until today ends', function () {
    $user = User::factory()->create();
    $user->dailyActivities()->create(['activity_date' => today()->subDay()->toDateString()]);

    $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertSee('1 day')->assertSee('No practice yet today');
});

it('saves gesture accuracy per lesson and keeps the personal best', function () {
    $user = User::factory()->create();
    $lesson = Lesson::factory()->create();

    $this->actingAs($user)->postJson(route('lessons.accuracy.store', $lesson), ['accuracy' => 82])
        ->assertOk()->assertJson(['best_accuracy' => 82, 'last_accuracy' => 82]);
    $this->postJson(route('lessons.accuracy.store', $lesson), ['accuracy' => 55])
        ->assertOk()->assertJson(['best_accuracy' => 82, 'last_accuracy' => 55]);

    $this->postJson(route('lessons.accuracy.store', $lesson), ['accuracy' => 101])->assertUnprocessable();

    $this->get(route('dashboard'))->assertOk()->assertSee('82%');
});

it('requires a learner account to save gesture accuracy', function () {
    $lesson = Lesson::factory()->create();

    $this->postJson(route('lessons.accuracy.store', $lesson), ['accuracy' => 70])->assertUnauthorized();
});

it('stores per-question accuracy submitted with the camera quiz', function () {
    $user = User::factory()->create();
    $lesson = Lesson::factory()->create();

    $this->actingAs($user)->withSession(['quiz.answer_key' => [$lesson->id => $lesson->id]])
        ->post(route('quiz.submit'), ['results' => [$lesson->id => 1], 'accuracy' => [$lesson->id => 91]])
        ->assertRedirect(route('quiz.results'));

    $this->assertDatabaseHas('user_progress', ['user_id' => $user->id, 'lesson_id' => $lesson->id, 'quiz_score' => 100, 'best_accuracy' => 91]);
});

it('links each lesson to its previous and next lesson and highlights next once finished', function () {
    $user = User::factory()->create();
    [$first, $second, $third] = Lesson::factory()->count(3)->create()->all();

    $this->actingAs($user)->get(route('lessons.show', $second))
        ->assertOk()
        ->assertSee(route('lessons.show', $first), false)
        ->assertSee(route('lessons.show', $third), false)
        ->assertSee('Up next</small>', false)
        ->assertDontSee('Nice work! Continue with</small>', false);

    $this->post(route('lessons.progress.store', $second));

    $this->get(route('lessons.show', $second))->assertSee('Nice work! Continue with</small>', false);
    $this->get(route('lessons.show', $third))->assertSee('Last lesson</small>', false);
});

it('shows an animated visual demo alongside the written sign reference', function () {
    $lesson = Lesson::factory()->create();

    $this->get(route('lessons.show', $lesson))
        ->assertOk()
        ->assertSee('data-sign-demo', false)
        ->assertSee('Watch the sign')
        ->assertSee($lesson->sign_reference);
});
