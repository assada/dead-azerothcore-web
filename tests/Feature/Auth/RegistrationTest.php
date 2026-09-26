<?php

use App\Models\Account;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users register with a separate username and recovery email', function () {
    Notification::fake();
    $response = $this->post('/register', [
        'username' => ' TestPlayer ',
        'email' => 'test.player.with.a.long.address@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('verification.notice', absolute: false));

    $account = Account::sole();
    expect($account->username)->toBe('TESTPLAYER')
        ->and($account->email)->toBe('TEST.PLAYER.WITH.A.LONG.ADDRESS@EXAMPLE.COM')
        ->and($account->reg_mail)->toBe($account->email)
        ->and(auth()->getProvider()->validateCredentials($account, ['password' => 'password']))->toBeTrue();
    Notification::assertSentTo($account, VerifyEmail::class);
    $this->post('/logout');
    $this->post('/login', ['username' => 'testplayer', 'password' => 'password'])
        ->assertSessionHasNoErrors()->assertRedirect('/dashboard');
    $this->assertAuthenticatedAs($account);
});

test('registration rejects invalid game usernames', function ($username) {
    Notification::fake();
    $this->post('/register', [
        'username' => $username,
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors('username');

    expect(Account::count())->toBe(0);
    Notification::assertNothingSent();
})->with(['', 'test@example.com', 'player name', 'гравець', str_repeat('a', 18)]);

test('registration accepts a 17 character username', function () {
    Notification::fake();
    $this->post('/register', [
        'username' => str_repeat('a', 17),
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasNoErrors();
    expect(Account::sole()->username)->toBe(str_repeat('A', 17));
});

test('usernames and recovery emails must each be unique regardless of case', function () {
    Notification::fake();
    $account = Account::factory()->create();
    $this->post('/register', [
        'username' => strtolower($account->username),
        'email' => 'other@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors('username');
    $this->post('/register', [
        'username' => 'otherplayer',
        'email' => strtolower($account->email),
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors('email');
    expect(Account::count())->toBe(1);
});
