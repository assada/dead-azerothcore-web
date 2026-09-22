<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CharacterInventory extends Model
{
    protected $connection = 'acore_characters';
    protected $table = 'character_inventory';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = null;
    protected $guarded = [];
}

