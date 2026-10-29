<?php

namespace App\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

class LegacyUserProvider extends EloquentUserProvider
{
    public function validateCredentials(Authenticatable $user, #[\SensitiveParameter] array $credentials): bool
    {
        $hash = $user->getAuthPassword();
        $password = $credentials['password'] ?? null;

        if (is_string($hash) && preg_match('/\A[0-9a-f]{40}\z/', $hash) === 1) {
            return is_string($password) && hash_equals($hash, sha1($password));
        }

        return parent::validateCredentials($user, $credentials);
    }
}
