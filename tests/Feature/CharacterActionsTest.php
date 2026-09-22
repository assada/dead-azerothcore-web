<?php

use App\Enums\AccountAction;
use App\Models\Account;
use App\Models\Character;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

beforeEach(function () {
    config(['wow.realms.1.soap' => ['url' => 'http://world.test:7878', 'username' => 'service', 'password' => 'secret']]);
    $this->account = Account::factory()->create();
    DB::connection('acore_characters')->table('characters')->insert([
        ['guid' => 1, 'name' => 'Owned', 'account' => $this->account->id],
        ['guid' => 2, 'name' => 'Other', 'account' => 999],
        ['guid' => 3, 'name' => 'Alt', 'account' => $this->account->id],
    ]);
    foreach ([1, 2, 3] as $guid) {
        DB::connection('acore_characters')->table('character_homebind')->insert(['guid' => $guid]);
    }
    Http::fake(['*' => Http::response('<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns1="urn:AC"><SOAP-ENV:Body><ns1:executeCommandResponse><result>OK</result></ns1:executeCommandResponse></SOAP-ENV:Body></SOAP-ENV:Envelope>')]);
});

function characterActionRequest(string $action = 'unstuck', ?string $id = null): array
{
    return ['action' => $action, 'request_id' => $id ?? (string) Str::uuid(), 'confirmed' => 1];
}

it('dispatches approved commands and records their realm and character', function (string $action, string $command) {
    $this->actingAs($this->account)->post('/realms/1/characters/1/actions', characterActionRequest($action))->assertRedirect('/dashboard');
    $operation = $this->account->operations()->sole();
    expect($operation->status)->toBe('completed')->and($operation->realm_id)->toBe(1)->and($operation->character_guid)->toBe(1);
    Http::assertSent(fn ($request) => str_contains($request->body(), '<command>'.$command.'</command>'));
})->with([['unstuck', 'unstuck 1 inn'], ['rename', 'character rename 1'], ['customize', 'character customize 1']]);

it('rejects other accounts characters and unsupported realms before dispatch', function (string $url) {
    $this->actingAs($this->account)->post($url, characterActionRequest())->assertNotFound();
    Http::assertNothingSent();
})->with(['/realms/1/characters/2/actions', '/realms/2/characters/1/actions', '/realms/1/characters/999/actions']);

it('returns the action result to the originating page', function (string $page) {
    $this->actingAs($this->account)->from($page)
        ->post('/realms/1/characters/1/actions', characterActionRequest())
        ->assertRedirect($page)->assertSessionHas('character-operation.status', 'completed');

    $this->post('/realms/1/characters/1/actions', characterActionRequest())
        ->assertRedirect($page)->assertSessionHasErrorsIn('characterAction', 'character');
    Http::assertSentCount(1);
})->with(['/dashboard', '/characters/AzerothCore/Owned?tab=talents&spec=1']);

it('requires login confirmation and a known action', function () {
    $this->post('/realms/1/characters/1/actions', characterActionRequest())->assertRedirect('/login');
    $this->actingAs($this->account)->post('/realms/1/characters/1/actions', ['action' => 'unstuck', 'request_id' => Str::uuid()])->assertSessionHasErrorsIn('characterAction', 'confirmed');
    $this->post('/realms/1/characters/1/actions', characterActionRequest('ban'))->assertSessionHasErrorsIn('characterAction', 'action');
    Http::assertNothingSent();
});

it('replays the same request without dispatching twice and rejects reused ids', function () {
    $input = characterActionRequest();
    $this->actingAs($this->account)->post('/realms/1/characters/1/actions', $input)->assertRedirect('/dashboard');
    $this->post('/realms/1/characters/1/actions', $input)->assertRedirect('/dashboard');
    $this->post('/realms/1/characters/3/actions', $input)->assertConflict();
    Http::assertSentCount(1);
    expect($this->account->operations()->count())->toBe(1);
});

it('enforces per-character cooldowns and permits the exact boundary', function (string $action, string $requested, string $next) {
    CarbonImmutable::setTestNow($requested);
    $this->travelTo(CarbonImmutable::parse($requested));
    $this->actingAs($this->account)->post('/realms/1/characters/1/actions', characterActionRequest($action))->assertRedirect('/dashboard');
    $this->post('/realms/1/characters/1/actions', characterActionRequest($action))->assertSessionHasErrorsIn('characterAction', 'character');
    $this->post('/realms/1/characters/3/actions', characterActionRequest($action))->assertRedirect('/dashboard');
    $this->travelTo(CarbonImmutable::parse($next)->subSecond());
    $this->post('/realms/1/characters/1/actions', characterActionRequest($action))->assertSessionHasErrorsIn('characterAction', 'character');
    $this->travelTo(CarbonImmutable::parse($next));
    $this->post('/realms/1/characters/1/actions', characterActionRequest($action))->assertRedirect('/dashboard');
    Http::assertSentCount(3);
    $this->travelBack();
    CarbonImmutable::setTestNow();
})->with([
    ['unstuck', '2026-09-07 12:00:00', '2026-09-07 15:00:00'],
    ['rename', '2024-02-29 12:00:00', '2025-02-28 12:00:00'],
    ['customize', '2026-12-31 23:59:00', '2027-01-01 00:00:00'],
]);

it('blocks online characters existing requests and missing home locations', function (array $values, string $action, bool $removeHome) {
    Character::where('guid', 1)->update($values);
    if ($removeHome) {
        DB::connection('acore_characters')->table('character_homebind')->where('guid', 1)->delete();
    }
    $this->actingAs($this->account)->post('/realms/1/characters/1/actions', characterActionRequest($action))->assertSessionHasErrorsIn('characterAction', 'character');
    Http::assertNothingSent();
})->with([
    [['online' => 1], 'unstuck', false], [['at_login' => 1], 'rename', false],
    [['at_login' => 8], 'customize', false], [['online' => 0], 'unstuck', true],
]);

it('records explicit failures without consuming cooldown and blocks ambiguous retries', function (string $response, int $status, string $expected) {
    Http::swap(new \Illuminate\Http\Client\Factory);
    Http::fake(['*' => Http::response($response, $status)]);
    $this->actingAs($this->account)->post('/realms/1/characters/1/actions', characterActionRequest())->assertRedirect('/dashboard');
    expect($this->account->operations()->sole()->status)->toBe($expected);
    $this->post('/realms/1/characters/1/actions', characterActionRequest());
    Http::assertSentCount($expected === 'failed' ? 2 : 1);
})->with([
    ['<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/"><s:Body><s:Fault><faultstring>secret internal error</faultstring></s:Fault></s:Body></s:Envelope>', 500, 'failed'],
    ['', 401, 'failed'], ['', 200, 'unknown'], ['invalid xml', 503, 'unknown'],
    ['<!DOCTYPE x [<!ENTITY e SYSTEM "file:///etc/passwd">]><x>&e;</x>', 200, 'unknown'],
]);

it('serializes concurrent requests for the same character', function () {
    $lock = Cache::lock('character-action:1:1', 15);
    $lock->get();
    $this->actingAs($this->account)->post('/realms/1/characters/1/actions', characterActionRequest())->assertSessionHasErrorsIn('characterAction', 'character');
    $lock->release();
    Http::assertNothingSent();
});

it('shows only the current accounts paginated history', function () {
    foreach (range(1, 22) as $i) {
        $this->account->operations()->create(['action' => AccountAction::Rename, 'character_name' => 'Own'.$i, 'realm_id' => 1]);
    }
    Account::factory()->create()->operations()->create(['action' => AccountAction::Rename, 'character_name' => 'PRIVATE']);
    $this->actingAs($this->account)->get('/profile/history')->assertOk()->assertDontSee('PRIVATE')->assertSee('Next');
    $this->get('/profile/history?page=2')->assertOk()->assertDontSee('PRIVATE');
});

it('never retries a timed out command whose outcome is unknown', function () {
    Http::swap(new \Illuminate\Http\Client\Factory);
    Http::fake(['*' => Http::failedConnection()]);
    $this->actingAs($this->account)->post('/realms/1/characters/1/actions', characterActionRequest())->assertRedirect('/dashboard');
    expect($this->account->operations()->sole()->status)->toBe('unknown');
    $this->post('/realms/1/characters/1/actions', characterActionRequest())->assertSessionHasErrorsIn('characterAction', 'character');
    expect($this->account->operations()->count())->toBe(1);
});

it('requires CSRF protection on character actions', function () {
    $this->app->instance('env', 'local');
    $this->actingAs($this->account)->post('/realms/1/characters/1/actions', characterActionRequest())->assertStatus(419);
    Http::assertNothingSent();
});

it('allows an administrator to resolve uncertain operations without dispatching again', function () {
    $operation = $this->account->operations()->create([
        'action' => AccountAction::Unstuck, 'realm_id' => 1, 'character_guid' => 1, 'status' => 'unknown',
    ]);
    $this->artisan('account:resolve-operation', ['id' => $operation->id, 'status' => 'failed', '--reason' => 'No change was applied.'])
        ->assertSuccessful();
    expect($operation->fresh()->status)->toBe('failed');
    $this->artisan('account:resolve-operation', ['id' => $operation->id, 'status' => 'completed', '--reason' => 'Changed later.'])
        ->assertFailed();
    Http::assertNothingSent();
});
