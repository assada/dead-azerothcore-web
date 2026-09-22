<?php

namespace App\Support;

use App\Enums\AccountAction;
use App\Models\Account;
use App\Models\Character;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AccountDeactivation
{
    public function deactivate(Account $account): void
    {
        $account->getConnection()->transaction(function () use ($account) {
            $account = Account::lockForUpdate()->findOrFail($account->id);
            if ($account->profile?->deactivated_at) {
                return;
            }
            if ($account->online || Character::where('account', $account->id)->where('online', 1)->exists()) {
                throw ValidationException::withMessages(['current_password' => 'Log out of the game before deactivating your account.'])->errorBag('deactivateAccount');
            }

            $bans = $account->getConnection()->table('account_banned');
            $date = now()->timestamp;
            while ((clone $bans)->where('id', $account->id)->where('bandate', $date)->exists()) {
                $date++;
            }
            $bans->insert([
                'id' => $account->id, 'bandate' => $date, 'unbandate' => $date,
                'active' => 1, 'bannedby' => 'Website', 'banreason' => 'Account deactivated by owner',
            ]);
            $account->profile()->updateOrCreate([], [
                'deactivated_at' => now(), 'deactivation_ban_date' => $date,
                'pending_email' => null, 'remember_token' => Str::random(60),
            ]);
            DB::connection(config('session.connection'))->table(config('session.table'))->where('user_id', $account->id)->delete();
            $account->operations()->create(['action' => AccountAction::Deactivate]);
        });
    }

    public function restore(Account $account): bool
    {
        return $account->getConnection()->transaction(function () use ($account) {
            $account = Account::lockForUpdate()->findOrFail($account->id);
            if (! $account->profile?->deactivated_at) {
                return false;
            }
            $account->getConnection()->table('account_banned')->where('id', $account->id)
                ->where('bandate', $account->profile->deactivation_ban_date)
                ->where('bannedby', 'Website')->where('banreason', 'Account deactivated by owner')->update(['active' => 0]);
            $account->profile()->update(['deactivated_at' => null, 'deactivation_ban_date' => null]);
            $account->operations()->create(['action' => AccountAction::Restore]);

            return true;
        });
    }
}
