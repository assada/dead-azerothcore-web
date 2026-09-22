<?php

use App\Enums\AccountAction;
use App\Models\Account;
use App\Support\CharacterActions;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

it('uses the installation branding and realmlist without removing homepage content', function () {
    config([
        'app.name' => 'Example Realm',
        'site.description' => 'A community server.',
        'wow.realmlist' => 'set realmlist logon.example.test',
    ]);

    $this->get('/')->assertOk()->assertSee('Example Realm')
        ->assertSee('set realmlist logon.example.test')
        ->assertSee('Latest News')->assertSee('Server Maintenance Complete')
        ->assertSee('Server Status');

    $mail = (new \Illuminate\Auth\Notifications\VerifyEmail)->toMail(Account::factory()->create());
    expect($mail->subject)->toBe('Verify your Example Realm email');
});

it('blocks disabled registration on both endpoints and removes its links', function () {
    config(['wow.registration_enabled' => false]);

    $this->get('/register')->assertNotFound();
    $this->post('/register', [])->assertNotFound();
    $this->get('/')->assertOk()->assertDontSee('href="'.route('register').'"', false);
    expect(Account::count())->toBe(0);
});

it('blocks a disabled character action before sending a command or recording an operation', function () {
    config(['wow.actions.rename.enabled' => false]);
    Http::fake();
    $account = Account::factory()->create();

    $this->actingAs($account)->post('/realms/1/characters/1/actions', [
        'action' => 'rename', 'request_id' => (string) \Illuminate\Support\Str::uuid(), 'confirmed' => 1,
    ])->assertForbidden();

    Http::assertNothingSent();
    expect($account->operations()->count())->toBe(0);
});

it('disables character actions when SOAP is not configured', function () {
    $account = Account::factory()->create();
    DB::connection('acore_characters')->table('characters')->insert(['guid' => 1, 'name' => 'Owned', 'account' => $account->id]);
    $character = \App\Models\Character::with('homebind')->find(1);
    $availability = app(CharacterActions::class)->availability($account, 1, collect([$character]));

    expect($availability[1]['rename']['disabled'])->toBeTrue()
        ->and($availability[1]['rename']['reason'])->toBe('Character services are unavailable.');
});

it('applies configured cooldowns with month boundaries and local quarter resets', function (string $cooldown, string $requested, string $next, string $timezone) {
    config(['wow.actions.rename.cooldown' => $cooldown, 'app.timezone' => $timezone]);

    expect(AccountAction::Rename->availableAfter(CarbonImmutable::parse($requested))->toIso8601String())->toBe($next);
})->with([
    ['P2D', '2026-09-01T12:00:00+00:00', '2026-09-03T12:00:00+00:00', 'UTC'],
    ['P1M', '2026-01-31T12:00:00+00:00', '2026-02-28T12:00:00+00:00', 'UTC'],
    ['P1Y', '2024-02-29T12:00:00+00:00', '2025-02-28T12:00:00+00:00', 'UTC'],
    ['P0D', '2026-09-01T12:00:00+00:00', '2026-09-01T12:00:00+00:00', 'UTC'],
    ['calendar_quarter', '2026-09-30T23:00:00+00:00', '2027-01-01T00:00:00+01:00', 'Europe/Warsaw'],
]);
