<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArenaTeamMember extends Model
{
    protected $connection = 'acore_characters';
    protected $table = 'arena_team_member';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = null;
    protected $guarded = [];
}

