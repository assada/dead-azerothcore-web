<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Guild extends Model
{
    protected $connection = 'acore_characters';

    protected $table = 'guild';

    public $timestamps = false;

    protected $primaryKey = 'guildid';

    public $incrementing = false;

    protected $fillable = ['guildid', 'name', 'leaderguid'];

    public function leader()
    {
        return $this->belongsTo(Character::class, 'leaderguid', 'guid');
    }

    public function members()
    {
        return $this->hasMany(GuildMember::class, 'guildid', 'guildid');
    }
}
