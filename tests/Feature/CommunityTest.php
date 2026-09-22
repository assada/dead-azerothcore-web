<?php

use App\Models\Account;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    config([
        'app.key' => str_repeat('x', 32), 'cache.default' => 'array',
        'database.connections.acore_characters' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
        'database.connections.acore_auth' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
    ]);
    DB::purge('acore_characters');
    DB::purge('acore_auth');
    $schema = Schema::connection('acore_characters');
    $schema->create('characters', function (Blueprint $table) {
        $table->integer('guid')->primary();
        $table->string('name');
        foreach (['account', 'race', 'class', 'level', 'money', 'totaltime', 'totalHonorPoints', 'online', 'at_login'] as $column) {
            $table->integer($column)->default(0);
        }
    });
    $schema->create('guild', function (Blueprint $table) {
        $table->integer('guildid')->primary();
        $table->string('name');
        $table->integer('leaderguid');
    });
    $schema->create('guild_member', function (Blueprint $table) {
        $table->integer('guid')->primary();
        $table->integer('guildid');
        $table->integer('rank');
        $table->string('pnote');
        $table->string('offnote');
    });
    $schema->create('guild_rank', function (Blueprint $table) {
        $table->integer('guildid');
        $table->integer('rid');
        $table->string('rname');
        $table->primary(['guildid', 'rid']);
    });
    Schema::connection('acore_auth')->create('account_access', function (Blueprint $table) {
        $table->integer('id');
        $table->integer('gmlevel');
        $table->integer('RealmID');
    });
    (require database_path('migrations/2026_09_07_160000_create_account_operations_and_sessions.php'))->up();
    $schema->create('character_homebind', function (Blueprint $table) {
        $table->integer('guid')->primary();
        $table->integer('zoneId');
    });
    $this->withoutVite();
});

function communityUser(): Account
{
    $user = new Account;
    $user->setRelation('profile', null);
    $user->id = 999;
    $user->username = 'TEST';

    return $user;
}

function communityFixtures(): void
{
    $db = DB::connection('acore_characters');
    foreach (range(1, 60) as $id) {
        $db->table('characters')->insert([
            'guid' => $id, 'name' => 'Player'.str_pad($id, 3, '0', STR_PAD_LEFT),
            'account' => 999, 'race' => 2, 'class' => $id % 2 ? 1 : 8,
            'level' => $id, 'money' => $id * 10000, 'totaltime' => (61 - $id) * 3600,
            'totalHonorPoints' => $id * 10, 'online' => $id === 1 ? 1 : 0,
        ]);
        $db->table('guild_member')->insert([
            'guid' => $id, 'guildid' => 1, 'rank' => $id === 1 ? 0 : 1,
            'pnote' => 'PRIVATE NOTE', 'offnote' => 'OFFICER SECRET',
        ]);
        $db->table('guild')->insert(['guildid' => $id, 'name' => 'Guild'.str_pad($id, 3, '0', STR_PAD_LEFT), 'leaderguid' => $id]);
    }
    $db->table('guild_rank')->insert([
        ['guildid' => 1, 'rid' => 0, 'rname' => 'Guild Master'],
        ['guildid' => 1, 'rid' => 1, 'rname' => 'Officer'],
        ['guildid' => 2, 'rid' => 1, 'rname' => 'Wrong guild rank'],
    ]);
}

it('requires authentication for community data', function ($url) {
    $this->getJson($url)->assertUnauthorized();
})->with(['/characters/AzerothCore', '/guilds', '/guilds/1']);

it('paginates every directory on the server', function ($url) {
    communityFixtures();
    $this->actingAs(communityUser())->getJson($url.'?draw=4&start=25&length=25')
        ->assertOk()->assertJsonPath('draw', 4)->assertJsonPath('recordsTotal', 60)
        ->assertJsonPath('recordsFiltered', 60)->assertJsonCount(25, 'data');
    $this->getJson($url.'?start=50&length=25')->assertOk()->assertJsonCount(10, 'data');
    $this->getJson($url.'?start=100&length=25')->assertOk()->assertJsonCount(0, 'data');
})->with(['/characters/AzerothCore', '/guilds', '/guilds/1']);

it('combines character filters with literal name search', function () {
    communityFixtures();
    $response = $this->actingAs(communityUser())->getJson('/characters/AzerothCore?'.http_build_query([
        'search' => ['value' => 'Player0'], 'guild' => 'Guild001', 'class' => 8,
        'level_min' => 10, 'level_max' => 20,
    ]))->assertOk()->assertJsonPath('recordsFiltered', 6)->assertJsonCount(6, 'data');
    expect($response->json('data.0.character'))->toContain('Player020');
    $this->getJson('/characters/AzerothCore?guild=DoesNotExist')->assertOk()->assertJsonCount(0, 'data');
    $this->getJson('/characters/AzerothCore?guild=Guild002')->assertOk()->assertJsonCount(0, 'data');
});

it('sorts character columns in both directions with stable ties', function (int $column, string $direction, string $name) {
    communityFixtures();
    $response = $this->actingAs(communityUser())->getJson('/characters/AzerothCore?'.http_build_query([
        'order' => [['column' => $column, 'dir' => $direction]], 'length' => 1,
    ]))->assertOk();
    expect($response->json('data.0.character'))->toContain($name);
})->with([
    [0, 'asc', 'Player001'], [0, 'desc', 'Player060'],
    [2, 'asc', 'Player001'], [2, 'desc', 'Player060'],
    [3, 'asc', 'Player060'], [3, 'desc', 'Player001'],
    [4, 'asc', 'Player001'], [4, 'desc', 'Player060'],
    [5, 'asc', 'Player001'], [5, 'desc', 'Player060'],
]);

it('searches guilds and sorts by member count', function () {
    communityFixtures();
    $response = $this->actingAs(communityUser())->getJson('/guilds?'.http_build_query(['order' => [['column' => 2, 'dir' => 'desc']]]))->assertOk();
    expect($response->json('data.0.guild'))->toContain('Guild001');
    expect($response->json('data.0.members'))->toBe('60');
    $response = $this->getJson('/guilds?search[value]=Guild050')->assertOk()->assertJsonCount(1, 'data');
    expect($response->json('data.0.leader'))->toContain('Player050');
});

it('loads named guild ranks without exposing member notes or other guild ranks', function () {
    communityFixtures();
    $response = $this->actingAs(communityUser())->getJson('/guilds/1')->assertOk();
    expect($response->json('data.0.rank'))->toBe('Guild Master');
    expect($response->json('data.1.rank'))->toBe('Officer');
    expect($response->getContent())->not->toContain('PRIVATE NOTE', 'OFFICER SECRET', 'Wrong guild rank');
    $this->getJson('/guilds/1?search[value]=Player060')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('recordsFiltered', 1);
    $this->getJson('/guilds/2')->assertOk()->assertJsonCount(0, 'data');
    $this->getJson('/guilds/999')->assertNotFound();
});

it('escapes names and search wildcards in every directory', function () {
    communityFixtures();
    $db = DB::connection('acore_characters');
    $db->table('characters')->where('guid', 1)->update(['name' => '<b>_%!</b>']);
    $db->table('guild')->where('guildid', 1)->update(['name' => '<b>_%!</b>']);
    $db->table('guild_rank')->where('guildid', 1)->where('rid', 0)->update(['rname' => '<img src=x>']);
    $this->actingAs(communityUser());
    foreach (['/characters/AzerothCore', '/guilds', '/guilds/1'] as $url) {
        $response = $this->getJson($url.'?'.http_build_query(['search' => ['value' => '_%!']]))
            ->assertOk()->assertJsonPath('recordsFiltered', 1)->assertJsonCount(1, 'data');
        expect($response->getContent())->toContain('&lt;b&gt;')->not->toContain('<b>', '<img src=x>');
    }
    expect($response->json('data.0.rank'))->toBe('&lt;img src=x&gt;');
});

it('renders the shared navigation and guild header', function () {
    communityFixtures();
    $this->actingAs(communityUser());
    foreach (['/dashboard', '/characters/AzerothCore', '/guilds', '/guilds/1'] as $url) {
        $this->get($url)->assertOk()->assertSee('Main navigation')->assertSee('data-table', false)
            ->assertDontSee('OFFICER SECRET')->assertDontSee('Rank 0');
    }
    $this->get('/guilds/1')->assertSee('Guild001')->assertSee('Player001')->assertSee('Guild Master');
});

it('rejects malformed pagination and character filters', function (array $input) {
    $this->actingAs(communityUser())->getJson('/characters/AzerothCore?'.http_build_query($input))->assertUnprocessable();
})->with([
    [['length' => 101]], [['length' => -1]], [['start' => -1]], [['search' => ['value' => ['x']]]],
    [['class' => 10]], [['class' => [1]]], [['guild' => ['x']]], [['level_min' => 0]], [['level_max' => 81]],
    [['level_min' => 50, 'level_max' => 10]], [['order' => [['column' => 1, 'dir' => 'asc']]]],
    [['order' => [['column' => 2, 'dir' => 'drop']]]],
]);
