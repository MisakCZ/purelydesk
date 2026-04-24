<?php

namespace App\Services\Ldap;

use RuntimeException;

class LdapAuthenticationException extends RuntimeException
{
    public static function invalidCredentials(): self
    {
        return new self(__('auth.failed'));
    }

    public static function unavailable(): self
    {
        return new self(__('auth.ldap_unavailable'));
    }

    public static function notAuthorized(): self
    {
        return new self(__('auth.ldap_not_authorized'));
    }
}
