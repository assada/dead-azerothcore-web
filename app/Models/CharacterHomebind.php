<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CharacterHomebind extends Model
{
    protected $connection = 'acore_characters';

    protected $table = 'character_homebind';

    protected $primaryKey = 'guid';

    public $timestamps = false;
}
