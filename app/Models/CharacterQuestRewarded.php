<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CharacterQuestRewarded extends Model
{
    protected $connection = 'acore_characters';
    protected $table = 'character_queststatus_rewarded';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = null;
    protected $guarded = [];
}

