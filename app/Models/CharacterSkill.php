<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CharacterSkill extends Model
{
    protected $connection = 'acore_characters';
    protected $table = 'character_skills';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = null;
    protected $guarded = [];
}

