<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CharacterSpell extends Model
{
    protected $connection = 'acore_characters';
    protected $table = 'character_spell';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = null;
    protected $guarded = [];
}

