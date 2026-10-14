<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\PasswordResetConfirmation;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     */
    public function create(Request $request): View
    {
        return view('auth.reset-password', ['request' => $request]);
    }

    /**
     * Handle an incoming new password request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = DB::transaction(function () use ($validated): ?User {
            $record = DB::table('password_reset_tokens')
                ->where('token', hash('sha256', $validated['token']))
                ->lockForUpdate()
                ->first();

            if ($record === null || $record->created_at === null) {
                return null;
            }

            $expiresAt = Carbon::parse($record->created_at)
                ->addMinutes((int) config('auth.passwords.users.expire', 30));

            if ($expiresAt->lte(now())) {
                return null;
            }

            $user = User::query()
                ->where('email', $record->email)
                ->whereNotNull('email_verified_at')
                ->lockForUpdate()
                ->first();

            if ($user === null) {
                return null;
            }

            $user->forceFill([
                'password' => Hash::make($validated['password']),
                'remember_token' => Str::random(60),
            ])->save();

            DB::table('password_reset_tokens')->where('email', $user->email)->delete();
            DB::table(config('session.table', 'sessions'))->where('user_id', $user->getKey())->delete();

            return $user;
        });

        if ($user === null) {
            return back()->withErrors(['token' => __('This password reset token is invalid or has expired.')]);
        }

        event(new PasswordReset($user));
        $user->notify(new PasswordResetConfirmation);

        return redirect()->route('login')->with('status', __('Your password has been reset.'));
    }
}
