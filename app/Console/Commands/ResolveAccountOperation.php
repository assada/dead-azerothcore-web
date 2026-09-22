<?php

namespace App\Console\Commands;

use App\Models\AccountOperation;
use App\Support\CharacterActions;
use Illuminate\Console\Command;

class ResolveAccountOperation extends Command
{
    protected $signature = 'account:resolve-operation {id} {status : completed or failed} {--reason= : Explanation shown in the account history}';

    protected $description = 'Resolve an uncertain character action after checking the game server';

    public function handle(): int
    {
        $status = $this->argument('status');
        $reason = trim((string) $this->option('reason'));
        if (! in_array($status, ['completed', 'failed'], true) || $reason === '' || mb_strlen($reason) > 500) {
            $this->error('Use completed or failed and provide a reason of up to 500 characters.');

            return self::FAILURE;
        }

        $resolved = (new AccountOperation)->getConnection()->transaction(function () use ($status, $reason) {
            $operation = AccountOperation::lockForUpdate()->find($this->argument('id'));
            if (! $operation || ! in_array($operation->status, ['pending', 'unknown'], true)
                || ! in_array($operation->action, CharacterActions::ACTIONS, true)) {
                return false;
            }
            $operation->update([
                'status' => $status,
                'context' => [...($operation->context ?? []), 'message' => $reason, 'resolved_by' => 'administrator'],
            ]);

            return true;
        });

        if (! $resolved) {
            $this->error('Unresolved character operation not found.');

            return self::FAILURE;
        }
        $this->info('Operation resolved. No command was sent to the game server.');

        return self::SUCCESS;
    }
}
