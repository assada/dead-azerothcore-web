<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CharacterStats extends Model
{
    protected $connection = 'acore_characters';

    protected $table = 'character_stats';

    protected $primaryKey = 'guid';

    public $timestamps = false;
}
