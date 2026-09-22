<?php

use App\Models\Account;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    config([
        'app.key' => str_repeat('x', 32), 'cache.default' => 'array',
        'database.connections.acore_characters' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
    ]);
    DB::purge('acore_characters');
    Schema::connection('acore_characters')->create('characters', function (Blueprint $table) {
        $table->integer('guid')->primary();
        $table->string('name');
        foreach (['account', 'race', 'class', 'level', 'money', 'totaltime', 'totalHonorPoints', 'arenaPoints'] as $column) {
            $table->integer($column)->default(0);
        }
    });
    $this->withoutVite();
});

function rankingsUser(): Account
{
    $account = new Account;
    $account->setRelation('profile', null);
    $account->id = 999;
    $account->username = 'TEST';

    return $account;
}

function rankingCharacters(int $count = 60): void
{
    DB::connection('acore_characters')->table('characters')->insert(
        collect(range(1, $count))->map(fn ($id) => [
            'guid' => $id, 'name' => 'Player'.$id, 'race' => 2, 'class' => 1, 'level' => 80,
            'money' => ($count - $id) * 10000, 'totaltime' => $id * 3601,
            'totalHonorPoints' => ($count - $id) * 10, 'arenaPoints' => $id,
        ])->all()
    );
}

it('requires authentication', function () {
    $this->getJson('/leaderboard')->assertUnauthorized();
});

it('returns only the requested page with global positions', function () {
    rankingCharacters(1000);
    $this->actingAs(rankingsUser())->getJson('/leaderboard?draw=7&start=25&length=25')
        ->assertOk()->assertJsonPath('draw', 7)->assertJsonPath('recordsTotal', 1000)
        ->assertJsonPath('recordsFiltered', 1000)->assertJsonCount(25, 'data')
        ->assertJsonPath('data.0.rank', 26)->assertJsonPath('data.24.rank', 50);
});

it('sorts all four metrics numerically in either direction', function (int $column, string $direction, string $name) {
    rankingCharacters(12);
    $response = $this->actingAs(rankingsUser())->getJson('/leaderboard?'.http_build_query([
        'order' => [['column' => $column, 'dir' => $direction]], 'length' => 1,
    ]))->assertOk()->assertJsonCount(1, 'data');
    expect($response->json('data.0.character'))->toContain('>'.$name.'</strong>');
    expect($response->json('data.0.rank'))->toBe($direction === 'desc' ? 1 : 12);
})->with([
    [2, 'desc', 'Player1'], [2, 'asc', 'Player12'],
    [3, 'desc', 'Player12'], [3, 'asc', 'Player1'],
    [4, 'desc', 'Player1'], [4, 'asc', 'Player12'],
    [5, 'desc', 'Player12'], [5, 'asc', 'Player1'],
]);

it('keeps positions when searching and breaks metric ties consistently', function () {
    rankingCharacters(4);
    DB::connection('acore_characters')->table('characters')->whereIn('guid', [2, 3])->update(['money' => 20000]);
    $response = $this->actingAs(rankingsUser())->getJson('/leaderboard?search[value]=Player3')
        ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('recordsTotal', 4)
        ->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.rank', 3);
    expect($response->json('data.0.gold'))->toContain('2 gold, 0 silver, 0 copper');
});

it('treats search wildcards literally and escapes character names', function () {
    rankingCharacters(3);
    DB::connection('acore_characters')->table('characters')->where('guid', 2)->update(['name' => '<b>_%!</b>']);
    $response = $this->actingAs(rankingsUser())->getJson('/leaderboard?'.http_build_query(['search' => ['value' => '_%!']]))
        ->assertOk()->assertJsonPath('recordsFiltered', 1)->assertJsonCount(1, 'data');
    expect($response->json('data.0.character'))->toContain('&lt;b&gt;_%!&lt;/b&gt;')->not->toContain('<b>');
});

it('rejects unlimited downloads and invalid ordering', function (array $params) {
    $this->actingAs(rankingsUser())->getJson('/leaderboard?'.http_build_query($params))->assertUnprocessable();
})->with([
    [['length' => -1]], [['length' => 100000]], [['start' => -1]], [['draw' => '<script>']],
    [['order' => [['column' => 'money DESC', 'dir' => 'desc']]]],
    [['order' => [['column' => 1, 'dir' => 'desc']]]],
    [['order' => [['column' => 2, 'dir' => 'invalid']]]],
]);

it('handles empty results and excludes deleted character names', function () {
    DB::connection('acore_characters')->table('characters')->insert(['guid' => 1, 'name' => '']);
    $this->actingAs(rankingsUser())->getJson('/leaderboard')->assertOk()
        ->assertJsonPath('recordsTotal', 0)->assertJsonPath('data', []);
    DB::connection('acore_characters')->table('characters')->where('guid', 1)->update(['name' => 'Player']);
    $this->getJson('/leaderboard?search[value]=Nobody')->assertOk()
        ->assertJsonPath('recordsTotal', 1)->assertJsonPath('recordsFiltered', 0)->assertJsonPath('data', []);
});
