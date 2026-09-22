<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CharacterPet extends Model
{
    protected $connection = 'acore_characters';
    protected $table = 'character_pet';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = 'id';
    protected $guarded = [];
}

