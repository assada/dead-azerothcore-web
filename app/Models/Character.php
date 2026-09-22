<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Character extends Model
{
    protected $connection = 'acore_characters';

    protected $table = 'characters';

    protected $primaryKey = 'guid';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'guid', 'account', 'name', 'race', 'class', 'gender', 'level', 'xp', 'money', 'totaltime',
    ];

    protected function casts(): array
    {
        return array_fill_keys([
            'guid', 'race', 'class', 'gender', 'level', 'skin', 'face', 'hairStyle', 'hairColor', 'facialStyle',
            'playerFlags', 'talentGroupsCount', 'activeTalentGroup', 'online', 'at_login',
        ], 'integer');
    }

    public function stats()
    {
        return $this->hasOne(CharacterStats::class, 'guid', 'guid');
    }

    public function homebind()
    {
        return $this->hasOne(CharacterHomebind::class, 'guid', 'guid');
    }

    public function talents()
    {
        return $this->hasMany(CharacterTalent::class, 'guid', 'guid');
    }

    public function glyphs()
    {
        return $this->hasMany(CharacterGlyphs::class, 'guid', 'guid');
    }

    public function guild()
    {
        return $this->hasOne(GuildMember::class, 'guid', 'guid')->with('guild');
    }

    public function achievements()
    {
        return $this->hasMany(CharacterAchievement::class, 'guid', 'guid');
    }

    public function pets()
    {
        return $this->hasMany(CharacterPet::class, 'owner', 'guid');
    }

    public function spells()
    {
        return $this->hasMany(CharacterSpell::class, 'guid', 'guid');
    }

    public function skills()
    {
        return $this->hasMany(CharacterSkill::class, 'guid', 'guid');
    }

    public function inventoryItems()
    {
        return $this->hasMany(CharacterInventory::class, 'guid', 'guid');
    }

    public function reputations()
    {
        return $this->hasMany(CharacterReputation::class, 'guid', 'guid');
    }

    public function rewardedQuests()
    {
        return $this->hasMany(CharacterQuestRewarded::class, 'guid', 'guid');
    }

    public function dailyQuests()
    {
        return $this->hasMany(CharacterQuestDaily::class, 'guid', 'guid');
    }

    public function weeklyQuests()
    {
        return $this->hasMany(CharacterQuestWeekly::class, 'guid', 'guid');
    }

    public function arenaTeams()
    {
        return $this->belongsToMany(ArenaTeam::class, 'arena_team_member', 'guid', 'arenaTeamId')
            ->withPivot(['personalRating', 'seasonWins', 'seasonGames', 'weekWins', 'weekGames']);
    }
}
