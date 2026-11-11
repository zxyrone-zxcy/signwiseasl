<?php

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

test('email verification screen can be rendered', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->get('/verify-email');

    $response->assertStatus(200);
});

test('email can be verified', function () {
    $user = User::factory()->unverified()->create();

    Event::fake();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    $response = $this->actingAs($user)->get($verificationUrl);

    Event::assertDispatched(Verified::class);
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
});

test('email is not verified with invalid hash', function () {
    $user = User::factory()->unverified()->create();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1('wrong-email')]
    );

    $this->actingAs($user)->get($verificationUrl);

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('registering sends the verification email', function () {
    Notification::fake();

    $this->post('/register', [
        'name' => 'Test Learner',
        'email' => 'learner@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(route('verification.notice', absolute: false));

    Notification::assertSentTo(User::query()->where('email', 'learner@example.com')->firstOrFail(), VerifyEmail::class);
});

test('registering still works when the mail server is unreachable', function () {
    config([
        'mail.default' => 'smtp',
        'mail.mailers.smtp.host' => '127.0.0.1',
        'mail.mailers.smtp.port' => 1,
    ]);

    $this->post('/register', [
        'name' => 'Test Learner',
        'email' => 'learner@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(route('verification.notice', absolute: false))
        ->assertSessionHas('status', 'verification-link-failed');

    $this->assertDatabaseHas('users', ['email' => 'learner@example.com']);
    $this->assertAuthenticated();
});

test('unverified learners can resend the email and see a reminder', function () {
    Notification::fake();
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->get(route('home'))->assertSee('Please verify your email address');

    $this->post(route('verification.send'))->assertSessionHas('status', 'verification-link-sent');
    Notification::assertSentTo($user, VerifyEmail::class);

    $verified = User::factory()->create();
    $this->actingAs($verified)->get(route('home'))->assertDontSee('Please verify your email address');
});
