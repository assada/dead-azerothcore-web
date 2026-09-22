<?php

namespace App\Auth;

use App\Support\Srp6;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

class Srp6UserProvider extends EloquentUserProvider
{
    public function retrieveByCredentials(array $credentials)
    {
        foreach (['username', 'email'] as $field) {
            if (isset($credentials[$field])) {
                $credentials[$field] = strtoupper($credentials[$field]);
            }
        }

        $user = parent::retrieveByCredentials($credentials);

        return $user?->profile?->deactivated_at ? null : $user;
    }

    public function validateCredentials(Authenticatable $user, #[\SensitiveParameter] array $credentials)
    {
        $password = $credentials['password'] ?? null;
        if (! is_string($password) || ! is_string($user->salt) || ! is_string($user->verifier)) {
            return false;
        }

        return hash_equals($user->verifier, Srp6::computeVerifier($user->username, $password, $user->salt));
    }

    public function rehashPasswordIfRequired(Authenticatable $user, #[\SensitiveParameter] array $credentials, bool $force = false)
    {
        // AzerothCore owns the SRP6 format; Laravel must not replace it with bcrypt.
    }

    public function updateRememberToken(Authenticatable $user, #[\SensitiveParameter] $token)
    {
        $user->setRememberToken($token);
        $user->profile()->save($user->profile);
    }
}
