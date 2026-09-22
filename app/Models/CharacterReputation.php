<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CharacterReputation extends Model
{
    protected $connection = 'acore_characters';
    protected $table = 'character_reputation';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = null;
    protected $guarded = [];
}

