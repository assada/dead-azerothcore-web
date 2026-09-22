<?php

use App\Models\Account;
use App\Support\GameData;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    config([
        'app.key' => str_repeat('x', 32), 'cache.default' => 'array',
        'database.connections.acore_characters' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
        'database.connections.acore_auth' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
        'wow.world_database' => 'main',
    ]);
    DB::purge('acore_characters');
    DB::purge('acore_auth');
    $tables = [
        'characters' => ['guid', 'account', 'name:string', 'race', 'class', 'gender', 'level', 'skin', 'face', 'hairStyle', 'hairColor', 'facialStyle', 'playerFlags', 'talentGroupsCount', 'activeTalentGroup', 'online', 'at_login', 'money', 'totaltime', 'logout_time', 'totalKills', 'todayKills', 'yesterdayKills', 'totalHonorPoints', 'arenaPoints'],
        'character_homebind' => ['guid', 'zoneId'],
        'guild_member' => ['guid', 'guildid'], 'guild' => ['guildid', 'name:string'],
        'character_achievement' => ['guid', 'achievement', 'date'],
        'character_pet' => ['id', 'owner'], 'character_spell' => ['guid', 'spell', 'specMask'],
        'character_inventory' => ['guid', 'item', 'bag', 'slot'],
        'character_queststatus_rewarded' => ['guid', 'quest', 'active'],
        'character_skills' => ['guid', 'skill', 'value', 'max'],
        'character_reputation' => ['guid', 'faction', 'standing', 'flags'],
        'character_talent' => ['guid', 'spell', 'specMask'],
        'character_glyphs' => ['guid', 'talentGroup', 'glyph1', 'glyph2', 'glyph3', 'glyph4', 'glyph5', 'glyph6'],
        'character_arena_stats' => ['guid', 'slot', 'matchMakerRating'],
        'arena_team' => ['arenaTeamId', 'name:string', 'type', 'rating', 'seasonWins', 'seasonGames'],
        'arena_team_member' => ['guid', 'arenaTeamId', 'personalRating', 'seasonWins', 'seasonGames', 'weekWins', 'weekGames'],
        'item_instance' => ['guid', 'itemEntry', 'enchantments:string', 'randomPropertyId'],
        'item_template' => ['entry', 'name:string', 'Quality', 'ItemLevel', 'class', 'subclass', 'InventoryType', 'displayid', 'socketBonus'],
        'character_stats' => ['guid', 'maxhealth', 'maxpower1', 'strength', 'agility', 'stamina', 'intellect', 'spirit', 'armor', 'resHoly', 'resFire', 'resNature', 'resFrost', 'resShadow', 'resArcane', 'blockPct', 'dodgePct', 'parryPct', 'critPct', 'rangedCritPct', 'spellCritPct', 'attackPower', 'rangedAttackPower', 'spellPower', 'resilience'],
    ];
    foreach ($tables as $name => $columns) {
        Schema::connection('acore_characters')->create($name, function (Blueprint $table) use ($columns) {
            foreach ($columns as $column) {
                [$name, $type] = array_pad(explode(':', $column), 2, 'integer');
                $table->$type($name)->default($type === 'string' ? '' : 0);
            }
        });
    }
    Schema::connection('acore_auth')->create('account_access', function (Blueprint $table) {
        $table->integer('id');
        $table->integer('RealmID');
        $table->integer('gmlevel');
    });
    DB::connection('acore_characters')->table('characters')->insert([
        'guid' => 1, 'name' => 'Testorc', 'race' => 2, 'class' => 1, 'level' => 80, 'talentGroupsCount' => 2, 'money' => 12345,
    ]);
    $this->withoutVite();
});

function characterUser(): Account
{
    $account = new Account;
    $account->setRelation('profile', null);
    $account->id = 999;
    $account->username = 'TEST';

    return $account;
}

it('requires authentication for every character tab', function () {
    foreach (['equipment', 'talents', 'mounts', 'achievements', 'pvp'] as $tab) {
        $this->get('/characters/AzerothCore/Testorc?tab='.$tab)->assertRedirect('/login');
    }
});

it('renders every tab with empty collections and no iframe', function (string $tab) {
    $this->actingAs(characterUser())->get('/characters/AzerothCore/Testorc?tab='.$tab)
        ->assertOk()->assertSee('Testorc')->assertDontSee('<iframe', false)
        ->assertDontSee('Actions for Testorc')->assertViewHas('actions', []);
})->with(['equipment', 'talents', 'mounts', 'achievements', 'pvp']);

it('shows shared actions on every tab of an owned character', function (string $tab) {
    (require database_path('migrations/2026_09_07_160000_create_account_operations_and_sessions.php'))->up();
    DB::connection('acore_characters')->table('characters')->where('guid', 1)->update(['account' => 999, 'at_login' => 1]);
    config(['wow.realms.1.soap' => ['url' => 'http://world.test:7878', 'username' => 'service', 'password' => 'test-password']]);
    DB::connection('acore_characters')->table('character_homebind')->insert(['guid' => 1, 'zoneId' => 12]);

    $this->actingAs(characterUser())->get('/characters/AzerothCore/Testorc?tab='.$tab)->assertOk()
        ->assertSee('Actions for Testorc')->assertSee('character-action-form')
        ->assertSee('Already requested. Finish this change in the game client.')
        ->assertViewHas('actions', fn ($actions) => ! $actions['unstuck']['disabled'] && $actions['rename']['disabled'] && ! $actions['customize']['disabled']);
})->with(['equipment', 'talents', 'mounts', 'achievements', 'pvp']);

it('returns 404 for a missing character', function () {
    $this->actingAs(characterUser())->get('/characters/AzerothCore/Nobody')->assertNotFound();
});

it('counts only learned mount spells and searches the collection', function () {
    $mount = collect(app(GameData::class)->table('mounts'))->first();
    DB::connection('acore_characters')->table('character_spell')->insert([
        ['guid' => 1, 'spell' => $mount['spell'], 'specMask' => 3],
        ['guid' => 1, 'spell' => 23162, 'specMask' => 3],
    ]);
    $this->actingAs(characterUser())->get('/characters/AzerothCore/Testorc?tab=mounts')
        ->assertOk()->assertViewHas('mountCount', 1)->assertSee($mount['name']);
    $this->get('/characters/AzerothCore/Testorc?tab=mounts&q=nonexistent')->assertOk()->assertSee('No mounts match');
});

it('loads both talent masks including shared talents and the selected glyph set', function () {
    $trees = app(GameData::class)->table('talents')[1];
    $talent = $trees[0]['talents'][0];
    $glyph = collect(app(GameData::class)->table('glyphs'))->first();
    $glyphId = array_key_first(app(GameData::class)->table('glyphs'));
    DB::connection('acore_characters')->table('character_talent')->insert(['guid' => 1, 'spell' => $talent['ranks'][0], 'specMask' => 3]);
    DB::connection('acore_characters')->table('character_glyphs')->insert(['guid' => 1, 'talentGroup' => 1, 'glyph1' => $glyphId]);
    $this->actingAs(characterUser())->get('/characters/AzerothCore/Testorc?tab=talents&spec=1')
        ->assertOk()->assertSee($glyph['name'])->assertViewHas('specs', fn ($specs) => $specs[0]['trees'][0]['points'] === 1 && $specs[1]['trees'][0]['points'] === 1);
});

it('uses saved character stats and shared money markup', function () {
    DB::connection('acore_characters')->table('character_stats')->insert(['guid' => 1, 'maxhealth' => 31961, 'strength' => 1643, 'armor' => 17664]);
    $this->actingAs(characterUser())->get('/characters/AzerothCore/Testorc')->assertOk()
        ->assertSee('31,961')->assertSee('1,643')->assertSee('17,664')->assertSee('1 gold, 23 silver, 45 copper');
});

it('applies base reputation and puts Wrath before older expansions', function () {
    DB::connection('acore_characters')->table('character_reputation')->insert([
        ['guid' => 1, 'faction' => 76, 'standing' => 0, 'flags' => 1],
        ['guid' => 1, 'faction' => 1098, 'standing' => 9000, 'flags' => 1],
        ['guid' => 1, 'faction' => 1119, 'standing' => 84999, 'flags' => 3],
        ['guid' => 1, 'faction' => 1097, 'standing' => 42000, 'flags' => 1],
        ['guid' => 1, 'faction' => 72, 'standing' => 42000, 'flags' => 5],
    ]);
    $this->actingAs(characterUser())->get('/characters/AzerothCore/Testorc')->assertOk()
        ->assertSeeInOrder(['Wrath of the Lich King', 'Knights of the Ebon Blade', 'The Sons of Hodir', 'Classic', 'Orgrimmar'])
        ->assertDontSee('Stormwind')->assertViewHas('reputations', fn ($groups) => $groups['Classic'][0]['standing'] === 'Friendly');
});

it('filters earned achievements and preserves the category when paging', function () {
    DB::connection('acore_characters')->table('character_achievement')->insert(['guid' => 1, 'achievement' => 6, 'date' => 1700000000]);
    $this->actingAs(characterUser())->get('/characters/AzerothCore/Testorc?tab=achievements')
        ->assertOk()->assertSee('Level 10')->assertViewHas('achievementPoints', 10);
    $this->get('/characters/AzerothCore/Testorc?tab=achievements&status=missing&q=Level%2010')
        ->assertOk()->assertSee('No achievements match');
    $this->get('/characters/AzerothCore/Testorc?tab=achievements&status=all&category=92')
        ->assertOk()->assertViewHas('achievements', fn ($rows) => str_contains($rows->nextPageUrl() ?? '', 'category=92'));
});

it('excludes statistic counters from achievement searches', function () {
    $this->actingAs(characterUser())->get('/characters/AzerothCore/Testorc?tab=achievements&status=all')
        ->assertOk()->assertDontSee('Statistics');
    $this->get('/characters/AzerothCore/Testorc?tab=achievements&status=all&q=Alterac%20Valley%20victories')
        ->assertOk()->assertSee('No achievements match');
});
