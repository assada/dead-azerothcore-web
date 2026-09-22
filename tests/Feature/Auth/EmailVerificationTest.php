<?php

use App\Models\Account;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

test('accounts without an email add it before requesting verification', function () {
    Notification::fake();
    $user = Account::factory()->create(['email' => '']);

    $this->actingAs($user)->get('/profile')->assertDontSee('Send verification email');
    $this->actingAs($user)->post('/email/verification-notification')->assertRedirect(route('profile.edit'));

    Notification::assertNothingSent();
});

test('email verification screen can be rendered', function () {
    $user = Account::factory()->create();

    $response = $this->actingAs($user)->get('/verify-email');

    $response->assertStatus(200);
});

test('email can be verified', function () {
    $user = Account::factory()->create();

    Event::fake();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    $response = $this->actingAs($user)->get($verificationUrl);

    Event::assertDispatched(Verified::class);
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
});

test('signed email links only trust the configured HTTPS proxy', function (string $proxy, bool $trusted) {
    config(['trustedproxy.proxies' => '192.0.2.10']);
    $user = Account::factory()->create();
    URL::forceRootUrl('https://realm.example.com');
    URL::forceScheme('https');
    $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
        'id' => $user->id, 'hash' => sha1($user->email),
    ]);

    $response = $this->actingAs($user)->call('GET', str_replace('https://', 'http://', $url), server: [
        'REMOTE_ADDR' => $proxy,
        'HTTP_X_FORWARDED_PROTO' => 'https',
        'HTTP_X_FORWARDED_FOR' => '203.0.113.10',
    ]);

    $response->assertStatus($trusted ? 302 : 403);
    expect($user->fresh()->hasVerifiedEmail())->toBe($trusted);
})->with([
    'Caddy' => ['192.0.2.10', true],
    'untrusted proxy' => ['192.0.2.11', false],
]);

test('email is not verified with invalid hash', function () {
    $user = Account::factory()->create();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1('wrong-email')]
    );

    $this->actingAs($user)->get($verificationUrl);

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});
