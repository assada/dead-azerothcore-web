<?php

use App\Models\Account;

test('password can be updated', function () {
    $user = Account::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->put('/password', [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $user->refresh();
    expect(auth()->getProvider()->validateCredentials($user, ['password' => 'new-password']))->toBeTrue();
});

test('correct password must be provided to update password', function () {
    $user = Account::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->put('/password', [
            'current_password' => 'wrong-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

    $response
        ->assertSessionHasErrorsIn('updatePassword', 'current_password')
        ->assertRedirect('/profile');
});

it('does not overwrite credentials changed by a concurrent request', function () {
    $account = Account::factory()->create();
    $stale = $account->fresh();
    $account->changePassword('first-password', \App\Enums\AccountAction::Password);
    expect(fn () => $stale->changePassword('second-password', \App\Enums\AccountAction::Password))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
    expect(auth()->getProvider()->validateCredentials($account->fresh(), ['password' => 'first-password']))->toBeTrue();
});
