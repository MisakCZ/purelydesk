<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DemoAuthenticator
{
    public function enabled(): bool
    {
        return ! config('helpdesk.ldap.enabled', false)
            && config('helpdesk.demo.login_enabled', false)
            && in_array(config('app.env'), ['local', 'testing'], true);
    }

    public function authenticate(string $identifier, string $password): ?User
    {
        if (! $this->enabled() || trim($identifier) === '' || $password === '') {
            return null;
        }

        $user = User::query()
            ->where('auth_source', 'local-demo')
            ->where(function ($query) use ($identifier): void {
                $query
                    ->where('username', $identifier)
                    ->orWhere('email', $identifier);
            })
            ->where('is_active', true)
            ->first();

        if (! $user instanceof User || ! is_string($user->password) || ! Hash::check($password, $user->password)) {
            return null;
        }

        $user->forceFill(['last_login_at' => now()])->save();

        return $user;
    }
}
