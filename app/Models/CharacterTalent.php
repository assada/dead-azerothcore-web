<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CharacterTalent extends Model
{
    protected $connection = 'acore_characters';

    protected $table = 'character_talent';

    public $timestamps = false;
}
