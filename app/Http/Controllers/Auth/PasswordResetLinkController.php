<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::query()
            ->where('email', $validated['email'])
            ->whereNotNull('email_verified_at')
            ->first();

        if ($user !== null) {
            $throttleSeconds = (int) config('auth.passwords.users.throttle', 60);

            $token = DB::transaction(function () use ($user, $throttleSeconds): ?string {
                $record = DB::table('password_reset_tokens')
                    ->where('email', $user->email)
                    ->lockForUpdate()
                    ->first();

                if ($record !== null && Carbon::parse($record->created_at)->gt(now()->subSeconds($throttleSeconds))) {
                    return null;
                }

                $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');

                DB::table('password_reset_tokens')->updateOrInsert(
                    ['email' => $user->email],
                    [
                        'token' => hash('sha256', $token),
                        'created_at' => now(),
                    ],
                );

                return $token;
            });

            if ($token !== null) {
                $user->notify(new ResetPasswordNotification($token));
            }
        }

        return back()->with('status', __('If an account exists for that email address, a password reset link has been sent.'));
    }
}
