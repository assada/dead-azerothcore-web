<?php

use App\Models\Account;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    config([
        'app.key' => str_repeat('x', 32),
        'database.connections.acore_characters' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
        'database.connections.acore_auth' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
        'wow.world_database' => 'main',
        'wow.auction.shared' => false,
    ]);
    DB::purge('acore_characters');
    DB::purge('acore_auth');
    $schema = Schema::connection('acore_characters');
    $schema->create('auctionhouse', function (Blueprint $table) {
        $table->integer('id')->primary();
        foreach (['houseid', 'itemguid', 'itemowner', 'buyoutprice', 'startbid', 'lastbid', 'time'] as $column) {
            $table->integer($column)->default(0);
        }
    });
    $schema->create('item_instance', function (Blueprint $table) {
        $table->integer('guid')->primary();
        $table->integer('itemEntry');
        $table->integer('count')->default(1);
        $table->integer('randomPropertyId')->default(0);
        $table->text('enchantments')->default('');
    });
    $schema->create('item_template', function (Blueprint $table) {
        $table->integer('entry')->primary();
        $table->string('name');
        foreach (['Quality', 'RequiredLevel', 'ItemLevel', 'displayid', 'class', 'subclass'] as $column) {
            $table->integer($column)->default(0);
        }
    });
    $schema->create('characters', function (Blueprint $table) {
        $table->integer('guid')->primary();
        $table->string('name');
    });
    Schema::connection('acore_auth')->create('account_access', function (Blueprint $table) {
        $table->integer('id');
        $table->integer('RealmID');
        $table->integer('gmlevel');
    });
    DB::connection('acore_characters')->table('characters')->insert(['guid' => 1, 'name' => '<script>Seller</script>']);
    $this->withoutVite();
    $this->travelTo(now()->startOfMinute());
});

function addAuction(int $id, array $auction = [], array $item = [], array $instance = []): void
{
    $db = DB::connection('acore_characters');
    $db->table('item_template')->insert(array_replace([
        'entry' => $id, 'name' => 'Linen Cloth '.$id, 'Quality' => 1,
        'class' => 7, 'subclass' => 5, 'RequiredLevel' => 10, 'ItemLevel' => 10, 'displayid' => 220,
    ], $item));
    $db->table('item_instance')->insert(array_replace(['guid' => $id, 'itemEntry' => $id, 'count' => 20], $instance));
    $db->table('auctionhouse')->insert(array_replace([
        'id' => $id, 'houseid' => 2, 'itemguid' => $id, 'itemowner' => 1,
        'buyoutprice' => 12345, 'startbid' => 5000, 'lastbid' => 0, 'time' => now()->timestamp + 3600,
    ], $auction));
}

function auctionUser(): Account
{
    $user = new Account;
    $user->setRelation('profile', null);
    $user->id = 999;
    $user->username = 'TEST';

    return $user;
}

it('requires login for every auction request', function () {
    $this->get('/auctions?market=horde&q=cloth')->assertRedirect('/login');
    $this->getJson('/auctions')->assertUnauthorized();
});

it('shows active auctions from the selected market and escapes player text', function () {
    addAuction(1);
    addAuction(2, ['houseid' => 6], ['name' => 'Horde only']);
    addAuction(3, ['time' => now()->timestamp], ['name' => 'Expired auction']);
    addAuction(4, [], ['name' => '<img src=x onerror=alert(1)>']);

    $this->actingAs(auctionUser())->get('/auctions?market=alliance')->assertOk()
        ->assertSee('Linen Cloth 1')->assertDontSee('Horde only')->assertDontSee('Expired auction')
        ->assertSee('&lt;script&gt;Seller&lt;/script&gt;', false)
        ->assertDontSee('<img src=x onerror=alert(1)>', false)
        ->assertSee('1 gold, 23 silver, 45 copper')
        ->assertHeader('Cache-Control', 'no-store, private');
    $this->get('/auctions?market=horde')->assertSee('Horde only')->assertDontSee('Linen Cloth 1');
});

it('shows one shared market when cross-faction auctions are enabled', function () {
    config(['wow.auction.shared' => true]);
    addAuction(1, ['houseid' => 7]);
    $this->actingAs(auctionUser())->get('/auctions')->assertOk()->assertSee('Shared Auction')->assertSee('Linen Cloth 1');
});

it('combines search, category, subclass, quality and level filters', function () {
    addAuction(1);
    addAuction(2, [], ['name' => 'Silk Cloth', 'RequiredLevel' => 20]);
    addAuction(3, [], ['Quality' => 2]);
    addAuction(4, [], ['subclass' => 9]);
    $response = $this->actingAs(auctionUser())->get('/auctions?category=7&subclass=5&quality=1&min_level=5&max_level=15&q=linen');
    $response->assertOk()->assertViewHas('auctions', fn ($rows) => $rows->pluck('id')->all() === [1]);
});

it('treats wildcard characters as literal search text', function () {
    addAuction(1, [], ['name' => '100% Cloth']);
    addAuction(2);
    $this->actingAs(auctionUser())->get('/auctions?q=%25')->assertOk()
        ->assertViewHas('auctions', fn ($rows) => $rows->pluck('id')->all() === [1]);
});

it('keeps auctions without a buyout last and uses the current bid', function () {
    addAuction(1, ['buyoutprice' => 0, 'lastbid' => 9999]);
    addAuction(2, ['buyoutprice' => 200]);
    addAuction(3, ['buyoutprice' => 100]);
    $this->actingAs(auctionUser())->get('/auctions?sort=buyout')->assertOk()
        ->assertViewHas('auctions', fn ($rows) => $rows->pluck('id')->all() === [3, 2, 1] && $rows->last()->bid === 9999);
});

it('paginates deterministically and preserves filters', function () {
    foreach (range(1, 27) as $id) {
        addAuction($id);
    }
    $this->actingAs(auctionUser())->get('/auctions?category=7&sort=time&page=2')->assertOk()
        ->assertViewHas('auctions', fn ($rows) => $rows->count() === 2 && $rows->total() === 27)
        ->assertSee('category=7', false)->assertSee('sort=time', false);
});

it('rejects malformed filters', function (string $query) {
    $this->actingAs(auctionUser())->getJson('/auctions?'.$query)->assertUnprocessable();
})->with(['category[]=7', 'market=unknown', 'sort=drop+table', 'direction=invalid', 'category=7&subclass=99', 'quality=9', 'min_level=81', 'page=-1']);

it('provides the existing AoWoW tooltip script with instance properties', function () {
    addAuction(1, [], [], ['randomPropertyId' => -42, 'enchantments' => '123 0 0']);
    $this->actingAs(auctionUser())->get('/auctions')->assertOk()
        ->assertSee('/static/widgets/power.js', false)
        ->assertSee('rand=-42&amp;ench=123', false)
        ->assertSee('inv_robe_02.jpg', false);
    $this->post('/auctions')->assertStatus(405);
});
