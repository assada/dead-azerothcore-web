<?php

namespace App\Http\Controllers;

use App\Enums\AccountAction;
use App\Models\Account;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AccountEmailController extends Controller
{
    public function show(Request $request, int $id, string $hash): View
    {
        return view('profile.confirm-email', ['email' => $this->pendingEmail($request, $id, $hash)]);
    }

    public function update(Request $request, int $id, string $hash): RedirectResponse
    {
        $email = $this->pendingEmail($request, $id, $hash);
        $request->merge(['email' => $email]);
        $request->validate([
            'current_password' => ['required', 'string', new \App\Rules\CurrentAccountPassword],
            'email' => ['required', 'email', 'max:255', Rule::unique(Account::class, 'email')->ignore($id)],
        ]);

        $account = $request->user();
        $account->getConnection()->transaction(function () use ($account, $request, $email) {
            $locked = Account::lockForUpdate()->findOrFail($account->id);
            if (! Auth::getProvider()->validateCredentials($locked, ['password' => $request->input('current_password')])
                || $locked->profile?->pending_email !== $email) {
                throw ValidationException::withMessages(['current_password' => 'The account changed. Please try again.']);
            }
            $locked->getConnection()->table(config('auth.passwords.users.table'))->where('email', $locked->email)->delete();
            $locked->update(['email' => $email]);
            $locked->profile()->update(['pending_email' => null, 'verified_email' => $email, 'email_verified_at' => now(), 'remember_token' => Str::random(60)]);
            $locked->operations()->create(['action' => AccountAction::Email]);
        });

        return redirect()->route('profile.edit')->with('status', 'profile-updated');
    }

    private function pendingEmail(Request $request, int $id, string $hash): string
    {
        $account = $request->user();
        $email = $account->profile?->pending_email;
        abort_unless((int) $account->id === $id && $email && hash_equals(hash('sha256', $email), $hash), 403);

        return $email;
    }
}
