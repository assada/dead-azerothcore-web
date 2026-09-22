<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GuildMember extends Model
{
    protected $connection = 'acore_characters';
    protected $table = 'guild_member';
    public $timestamps = false;
    protected $primaryKey = 'guid';
    public $incrementing = false;

    protected $fillable = [ 'guildid', 'guid', 'rank', 'pnote', 'offnote' ];

    public function guild()
    {
        return $this->belongsTo(Guild::class, 'guildid', 'guildid');
    }
}


