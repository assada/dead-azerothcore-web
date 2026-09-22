<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Account;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        abort_unless(config('wow.registration_enabled'), 404);

        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        abort_unless(config('wow.registration_enabled'), 404);

        $request->merge(['email' => strtoupper(trim((string) $request->input('email')))]);
        $request->validate([
            'email' => ['required', 'string', 'email', 'max:32', 'unique:acore_auth.account,email', 'unique:acore_auth.account,username'],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults(), 'max:16'],
        ]);

        $emailValue = strtoupper($request->string('email'));

        $salt = random_bytes(32);
        $verifier = \App\Support\Srp6::computeVerifier($emailValue, (string) $request->string('password'), $salt);

        $account = Account::create([
            'username' => $emailValue,
            'salt' => $salt,
            'verifier' => $verifier,
            'email' => $emailValue,
            'reg_mail' => $emailValue,
            'joindate' => now(),
            'last_ip' => $request->ip(),
            'last_attempt_ip' => $request->ip(),
            'expansion' => 2,
            'locale' => 0,
            'online' => 0,
        ]);

        event(new Registered($account));

        Auth::login($account);

        return redirect(route('verification.notice', absolute: false));
    }
}
