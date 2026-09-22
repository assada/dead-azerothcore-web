<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CharacterAchievement extends Model
{
    protected $connection = 'acore_characters';
    protected $table = 'character_achievement';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = null;
    protected $guarded = [];
}

