<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountAccess extends Model
{
    protected $connection = 'acore_auth';
    protected $table = 'account_access';
    public $timestamps = false;
    public $incrementing = false;

    protected $primaryKey = null; // composite (id, RealmID)

    protected $fillable = [
        'id',
        'gmlevel',
        'RealmID',
        'comment',
    ];
}


