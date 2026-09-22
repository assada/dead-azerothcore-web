<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountProfile extends Model
{
    protected $connection = 'acore_auth';

    protected $primaryKey = 'account_id';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];

    protected $hidden = ['remember_token'];

    protected function casts(): array
    {
        return ['email_verified_at' => 'immutable_datetime', 'deactivated_at' => 'immutable_datetime'];
    }
}
