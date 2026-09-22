<?php

namespace App\Http\Controllers;

use App\Support\AccountDeactivation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccountDeactivationController extends Controller
{
    public function destroy(Request $request, AccountDeactivation $deactivation): RedirectResponse
    {
        $request->validateWithBag('deactivateAccount', [
            'current_password' => ['required', 'string', new \App\Rules\CurrentAccountPassword],
            'confirmed' => ['accepted'],
        ]);
        $deactivation->deactivate($request->user());
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Your account is deactivated. Contact an administrator to restore it.');
    }
}
