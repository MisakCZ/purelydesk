<?php

namespace App\Services\Ldap;

class LdapUserData
{
    /**
     * @param  array<int, string>  $groups
     */
    public function __construct(
        public readonly string $username,
        public readonly string $dn,
        public readonly ?string $email,
        public readonly ?string $displayName,
        public readonly ?string $externalId,
        public readonly ?string $department,
        public readonly array $groups,
    ) {
        //
    }
}
