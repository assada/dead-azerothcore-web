<?php

use App\Enums\AccountAction;
use App\Models\Account;
use App\Notifications\ConfirmEmailChange;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

it('shows account settings and only the current accounts sessions', function () {
    $account = Account::factory()->create();
    DB::connection('acore_auth')->table('sessions')->insert([
        ['id' => 'own', 'user_id' => $account->id, 'ip_address' => '192.0.2.1', 'user_agent' => 'Mozilla/5.0 Firefox/130.0', 'last_activity' => time(), 'payload' => ''],
        ['id' => 'other', 'user_id' => 999, 'ip_address' => '192.0.2.99', 'user_agent' => '', 'last_activity' => time(), 'payload' => ''],
    ]);
    $this->actingAs($account)->get('/profile')->assertOk()->assertSee('192.0.2.1')->assertDontSee('192.0.2.99');
});

it('changes the recovery email after confirmation without changing game credentials', function () {
    Notification::fake();
    $account = Account::factory()->create();
    $username = $account->username;
    $salt = $account->salt;
    $verifier = $account->verifier;
    DB::connection('acore_auth')->table('password_reset_tokens')->insert([
        'email' => $account->email, 'token' => 'old-token', 'created_at' => now(),
    ]);
    $this->actingAs($account)->from('/profile')->patch('/profile', [
        'email' => 'New@example.com', 'current_password' => 'password',
    ])->assertSessionHasNoErrors()->assertRedirect('/profile');
    expect($account->fresh()->username)->toBe($username);
    Notification::assertSentOnDemand(ConfirmEmailChange::class, fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'NEW@EXAMPLE.COM');
    $account->refresh();
    $url = URL::temporarySignedRoute('profile.email.confirm', now()->addHour(), ['id' => $account->id, 'hash' => hash('sha256', 'NEW@EXAMPLE.COM')]);
    $this->get($url)->assertOk()->assertSee('new@example.com');
    $this->post($url, ['current_password' => 'wrong'])->assertSessionHasErrors('current_password');
    expect($account->fresh()->username)->toBe($username);
    $this->post($url, ['current_password' => 'password'])->assertRedirect('/profile');
    $account->refresh();
    expect($account->username)->toBe($username)->and($account->email)->toBe('NEW@EXAMPLE.COM')
        ->and($account->salt)->toBe($salt)->and($account->verifier)->toBe($verifier)
        ->and($account->hasVerifiedEmail())->toBeTrue()
        ->and(auth()->getProvider()->validateCredentials($account, ['password' => 'password']))->toBeTrue()
        ->and($account->operations()->where('action', AccountAction::Email)->count())->toBe(1);
    expect(DB::connection('acore_auth')->table('password_reset_tokens')->count())->toBe(0);
    $this->post($url, ['current_password' => 'password'])->assertForbidden();
});

it('requires the current password and a unique recovery email', function () {
    $account = Account::factory()->create();
    $other = Account::factory()->create();
    $this->actingAs($account)->patch('/profile', ['email' => $other->email, 'current_password' => 'password'])
        ->assertSessionHasErrorsIn('updateProfile', 'email');
    $this->patch('/profile', ['email' => 'new@example.com', 'current_password' => 'wrong'])
        ->assertSessionHasErrorsIn('updateProfile', 'current_password');
    expect($account->fresh()->profile)->toBeNull();
});

it('does not send a confirmation when the email has not changed', function () {
    Notification::fake();
    $account = Account::factory()->create();
    $this->actingAs($account)->from('/profile')->patch('/profile', [
        'email' => strtolower($account->email), 'current_password' => 'password',
    ])->assertSessionHasNoErrors()->assertRedirect('/profile');
    Notification::assertNothingSent();
    expect($account->fresh()->profile)->toBeNull();
});

it('accepts a recovery email longer than a game username', function () {
    Notification::fake();
    $account = Account::factory()->create();
    $email = 'a.long.recovery.email.address@example.com';
    $this->actingAs($account)->patch('/profile', ['email' => $email, 'current_password' => 'password'])
        ->assertSessionHasNoErrors();
    $account->refresh();
    $url = URL::temporarySignedRoute('profile.email.confirm', now()->addHour(), [
        'id' => $account->id, 'hash' => hash('sha256', strtoupper($email)),
    ]);
    $this->post($url, ['current_password' => 'password'])->assertSessionHasNoErrors()->assertRedirect('/profile');
    expect($account->fresh()->email)->toBe(strtoupper($email));
});

it('deactivates without deleting characters and allows only administrator restoration', function () {
    $account = Account::factory()->create();
    DB::connection('acore_characters')->table('characters')->insert(['guid' => 1, 'name' => 'Kept', 'account' => $account->id]);
    DB::connection('acore_auth')->table('account_banned')->insert([
        'id' => $account->id, 'bandate' => 100, 'unbandate' => 100, 'active' => 1, 'bannedby' => 'GM', 'banreason' => 'Existing restriction',
    ]);
    $this->actingAs($account)->delete('/profile', ['current_password' => 'password', 'confirmed' => 1])
        ->assertSessionHasNoErrors()->assertRedirect('/login');
    $this->assertGuest();
    expect(Account::find($account->id))->not->toBeNull();
    expect(DB::connection('acore_characters')->table('characters')->where('guid', 1)->exists())->toBeTrue();
    $this->post('/login', ['username' => $account->username, 'password' => 'password'])->assertSessionHasErrors('username');
    $this->assertGuest();
    $this->artisan('account:restore', ['id' => $account->id])->assertSuccessful();
    expect($account->fresh()->profile->deactivated_at)->toBeNull();
    expect(DB::connection('acore_auth')->table('account_banned')->where('bandate', 100)->value('active'))->toBe(1);
    expect(DB::connection('acore_auth')->table('account_banned')->where('bannedby', 'Website')->value('active'))->toBe(0);
    $this->post('/login', ['username' => $account->username, 'password' => 'password'])->assertSessionHasNoErrors();
    $this->assertAuthenticated();
});

it('refuses deactivation without confirmation or while playing', function () {
    $account = Account::factory()->create();
    $this->actingAs($account)->delete('/profile', ['current_password' => 'password'])->assertSessionHasErrorsIn('deactivateAccount', 'confirmed');
    $account->update(['online' => 1]);
    $this->delete('/profile', ['current_password' => 'password', 'confirmed' => 1])->assertSessionHasErrorsIn('deactivateAccount', 'current_password');
    expect($account->fresh()->profile)->toBeNull();
});

it('ends other web sessions and rotates remembered login tokens', function () {
    $account = Account::factory()->create();
    $account->profile()->create(['remember_token' => str_repeat('a', 60)]);
    $this->actingAs($account)->get('/profile')->assertOk();
    $current = session()->getId();
    DB::connection('acore_auth')->table('sessions')->insert([
        ['id' => $current, 'user_id' => $account->id, 'last_activity' => time(), 'payload' => ''],
        ['id' => 'other', 'user_id' => $account->id, 'last_activity' => time(), 'payload' => ''],
        ['id' => 'someone-else', 'user_id' => 999, 'last_activity' => time(), 'payload' => ''],
    ]);
    $this->withCookie(config('session.cookie'), $current)->from('/profile')->delete('/profile/sessions', ['current_password' => 'password'])->assertRedirect('/profile')->assertSessionHasNoErrors();
    expect(DB::connection('acore_auth')->table('sessions')->pluck('id')->all())->toBe([$current, 'someone-else']);
    expect($account->fresh()->getRememberToken())->not->toBe(str_repeat('a', 60));
    expect($account->operations()->where('action', AccountAction::Sessions)->first()->context['count'])->toBe(1);
});
