<?php

namespace App\Models;

use App\Enums\AccountAction;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AccountOperation extends Model
{
    use HasUuids;

    protected $connection = 'acore_auth';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['action' => AccountAction::class, 'context' => 'array'];
    }

    public function result(): string
    {
        return match ($this->status) {
            'pending' => 'Processing',
            'unknown' => 'Result not confirmed',
            'failed' => 'Failed',
            default => $this->action->loginFlag() ? 'Requested for next login' : 'Completed',
        };
    }
}
