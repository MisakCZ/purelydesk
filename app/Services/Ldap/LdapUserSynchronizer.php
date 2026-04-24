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

        $externalId = $this->normalizeExternalId($ldapUser->externalId);
        $user = $this->resolveUser($ldapUser, $externalId);
        $displayName = $ldapUser->displayName ?: $ldapUser->username;
        $email = $ldapUser->email ?: $this->fallbackEmail($ldapUser->username);

        $user->forceFill([
            'username' => $ldapUser->username,
            'name' => $displayName,
            'display_name' => $displayName,
            'email' => $email,
            'ldap_dn' => $ldapUser->dn,
            'external_id' => $externalId,
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

    private function resolveUser(LdapUserData $ldapUser, ?string $externalId): User
    {
        if ($externalId !== null && $externalId !== '') {
            $user = User::query()->where('external_id', $externalId)->first();

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

    private function normalizeExternalId(?string $externalId): ?string
    {
        if ($externalId === null || $externalId === '') {
            return $externalId;
        }

        // LDAP unique identifiers can be binary attributes, such as directory GUIDs.
        // Normalize them before storing in a text column to avoid invalid UTF-8 writes.
        if (! mb_check_encoding($externalId, 'UTF-8') || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $externalId)) {
            return 'base64:'.base64_encode($externalId);
        }

        return $externalId;
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
