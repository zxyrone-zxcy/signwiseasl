<?php

use App\Models\User;
use App\Notifications\PasswordResetConfirmation;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

test('reset password link screen can be rendered', function () {
    $response = $this->get('/forgot-password');

    $response->assertStatus(200);
});

test('reset password link can be requested', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', [
        'email' => $user->email,
        'destination_email' => 'attacker@example.test',
    ]);

    Notification::assertSentTo($user, ResetPasswordNotification::class);
    expect($user->routeNotificationFor('mail'))->toBe($user->email);
});

test('reset link response does not reveal whether an account exists', function () {
    Notification::fake();

    $user = User::factory()->create();
    $message = 'If an account exists for that email address, a password reset link has been sent.';

    $knownResponse = $this->from('/forgot-password')->post('/forgot-password', ['email' => $user->email]);
    $unknownResponse = $this->from('/forgot-password')->post('/forgot-password', ['email' => 'unknown@example.test']);

    $knownResponse->assertRedirect('/forgot-password')->assertSessionHas('status', $message);
    $unknownResponse->assertRedirect('/forgot-password')->assertSessionHas('status', $message);
    Notification::assertSentTo($user, ResetPasswordNotification::class);
});

test('reset links are not sent to unverified accounts', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    $response = $this->from('/forgot-password')->post('/forgot-password', ['email' => $user->email]);

    $response->assertRedirect('/forgot-password')
        ->assertSessionHas('status', 'If an account exists for that email address, a password reset link has been sent.');
    Notification::assertNothingSent();
});

test('reset password screen can be rendered from a token-only link', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) {
        $response = $this->get('/reset-password/'.$notification->token);

        $response->assertStatus(200);

        return true;
    });
});

test('reset tokens contain 32 random bytes and only their digest is stored', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) use ($user) {
        $decodedToken = base64_decode(strtr($notification->token, '-_', '+/').'=', true);
        $storedToken = DB::table('password_reset_tokens')->where('email', $user->email)->value('token');

        expect(strlen($decodedToken))->toBe(32)
            ->and($storedToken)->toBe(hash('sha256', $notification->token))
            ->and($storedToken)->not->toBe($notification->token);

        return true;
    });
});

test('password reset is single use and revokes sessions and sends confirmation', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    $token = null;
    Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) use (&$token) {
        $token = $notification->token;

        return true;
    });

    $originalRememberToken = $user->remember_token;

    DB::table('sessions')->insert([
        [
            'id' => 'session-one',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test',
            'payload' => 'session-one',
            'last_activity' => now()->timestamp,
        ],
        [
            'id' => 'session-two',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test',
            'payload' => 'session-two',
            'last_activity' => now()->timestamp,
        ],
    ]);

    $resetData = [
        'token' => $token,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ];

    $this->post('/reset-password', $resetData)
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('login'));

    expect(Hash::check('new-password', $user->fresh()->password))->toBeTrue()
        ->and($user->fresh()->remember_token)->not->toBe($originalRememberToken)
        ->and(DB::table('sessions')->where('user_id', $user->id)->count())->toBe(0)
        ->and(DB::table('password_reset_tokens')->where('email', $user->email)->exists())->toBeFalse();

    Notification::assertSentTo($user, PasswordResetConfirmation::class);

    $this->from('/reset-password/'.$token)
        ->post('/reset-password', $resetData)
        ->assertSessionHasErrors('token');
});

test('reset tokens expire after 30 minutes', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    $token = null;
    Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) use (&$token) {
        $token = $notification->token;

        return true;
    });

    $this->travel(30)->minutes();

    $this->post('/reset-password', [
        'token' => $token,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->assertSessionHasErrors('token');
});

test('password reset requests are rate limited', function () {
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->post('/forgot-password', ['email' => "unknown{$attempt}@example.test"])
            ->assertRedirect('/');
    }

    $this->post('/forgot-password', ['email' => 'another-unknown@example.test'])
        ->assertTooManyRequests();
});

test('password reset token generation is throttled per account', function () {
    Notification::fake();

    $user = User::factory()->create();
    $notificationCount = 0;

    $this->post('/forgot-password', ['email' => $user->email]);
    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPasswordNotification::class, function () use (&$notificationCount) {
        $notificationCount++;

        return true;
    });

    expect($notificationCount)->toBe(1);
});
