<?php

use App\Models\Account;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('users can authenticate using the login screen', function () {
    $user = Account::factory()->create();

    $response = $this->post('/login', [
        'username' => $user->username,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('users can not authenticate with invalid password', function () {
    $user = Account::factory()->create();

    $this->post('/login', [
        'username' => $user->username,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = Account::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});

it('keeps existing AzerothCore credentials unchanged after website login', function () {
    $account = Account::factory()->create();
    $salt = $account->salt;
    $verifier = $account->verifier;
    $this->post('/login', ['username' => strtolower($account->username), 'password' => 'password', 'remember' => 1])
        ->assertRedirect('/dashboard');
    $account->refresh();
    expect($account->salt)->toBe($salt)->and($account->verifier)->toBe($verifier)
        ->and($account->getRememberToken())->not->toBeEmpty();
});

it('rejects sessions carrying the password fingerprint from before a password change', function () {
    $account = Account::factory()->create();
    $oldHash = $account->getAuthPassword();
    $account->changePassword('new-password', \App\Enums\AccountAction::Password);
    $this->actingAs($account)->withSession(['password_hash_web' => $oldHash])->get('/profile')->assertRedirect('/login');
    $this->assertGuest();
});

it('rejects expired remembered login tokens', function () {
    $account = Account::factory()->create();
    auth()->getProvider()->updateRememberToken($account, str_repeat('b', 60));
    expect(auth()->getProvider()->retrieveByToken($account->id, str_repeat('a', 60)))->toBeNull();
    expect(auth()->getProvider()->retrieveByToken($account->id, str_repeat('b', 60))->id)->toBe($account->id);
});
