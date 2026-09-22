<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CharacterGlyphs extends Model
{
    protected $connection = 'acore_characters';

    protected $table = 'character_glyphs';

    public $timestamps = false;
}
