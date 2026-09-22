<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Support\AccountDeactivation;
use Illuminate\Console\Command;

class RestoreAccount extends Command
{
    protected $signature = 'account:restore {id : Account ID}';

    protected $description = 'Restore an account deactivated by its owner';

    public function handle(AccountDeactivation $deactivation): int
    {
        $account = Account::find($this->argument('id'));
        if (! $account) {
            $this->error('Account not found.');

            return self::FAILURE;
        }
        $this->info($deactivation->restore($account) ? 'Account restored. Other bans are unchanged.' : 'Account is already active.');

        return self::SUCCESS;
    }
}
