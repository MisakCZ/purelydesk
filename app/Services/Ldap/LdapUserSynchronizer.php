<?php

namespace App\Services\Ldap;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Str;

class LdapUserSynchronizer
{
    public function __construct(
        private readonly LdapRoleMapper $roleMapper,
    ) {
        //
    }

    public function sync(LdapUserData $ldapUser): User
    {
        $roleSlugs = $this->roleMapper->roleSlugsForGroups($ldapUser->groups);

        if ($roleSlugs === []) {
            throw LdapAuthenticationException::notAuthorized();
        }

        $user = $this->resolveUser($ldapUser);
        $displayName = $ldapUser->displayName ?: $ldapUser->username;
        $email = $ldapUser->email ?: $this->fallbackEmail($ldapUser->username);

        $user->forceFill([
            'username' => $ldapUser->username,
            'name' => $displayName,
            'display_name' => $displayName,
            'email' => $email,
            'ldap_dn' => $ldapUser->dn,
            'external_id' => $ldapUser->externalId,
            'department' => $ldapUser->department,
            'auth_source' => 'ldap',
            'is_active' => true,
            'last_login_at' => now(),
        ]);

        if (! $user->exists || empty($user->password)) {
            $user->password = Str::random(64);
        }

        $user->save();

        $roleIds = Role::query()
            ->whereIn('slug', $roleSlugs)
            ->pluck('id')
            ->all();

        $user->roles()->sync($roleIds);

        return $user->refresh();
    }

    private function resolveUser(LdapUserData $ldapUser): User
    {
        if ($ldapUser->externalId !== null && $ldapUser->externalId !== '') {
            $user = User::query()->where('external_id', $ldapUser->externalId)->first();

            if ($user instanceof User) {
                return $user;
            }
        }

        $user = User::query()->where('username', $ldapUser->username)->first();

        if ($user instanceof User) {
            return $user;
        }

        if ($ldapUser->email !== null && $ldapUser->email !== '') {
            $user = User::query()->where('email', $ldapUser->email)->first();

            if ($user instanceof User) {
                return $user;
            }
        }

        return new User();
    }

    private function fallbackEmail(string $username): string
    {
        $safeUsername = Str::of($username)
            ->lower()
            ->replaceMatches('/[^a-z0-9._-]+/', '.')
            ->trim('.')
            ->value();

        return ($safeUsername !== '' ? $safeUsername : 'ldap-user').'.ldap.local';
    }
}
