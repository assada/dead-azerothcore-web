<?php

namespace App\Http\Controllers\Auth;

use App\Enums\AccountAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'string', new \App\Rules\CurrentAccountPassword],
            'password' => ['required', 'string', Password::defaults(), 'max:16', 'confirmed'],
        ]);

        $request->user()->changePassword($validated['password'], AccountAction::Password);

        return back()->with('status', 'password-updated');
    }
}
