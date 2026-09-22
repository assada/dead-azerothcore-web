<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArenaTeam extends Model
{
    protected $connection = 'acore_characters';
    protected $table = 'arena_team';
    protected $primaryKey = 'arenaTeamId';
    public $incrementing = false;
    public $timestamps = false;
    protected $guarded = [];
}

