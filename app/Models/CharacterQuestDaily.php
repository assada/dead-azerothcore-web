<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CharacterQuestDaily extends Model
{
    protected $connection = 'acore_characters';
    protected $table = 'character_queststatus_daily';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = null;
    protected $guarded = [];
}

