<?php

namespace App\Services\Ldap;

use App\Models\User;

class LdapAuthenticator
{
    public function __construct(
        private readonly LdapUserSynchronizer $synchronizer,
    ) {
        //
    }

    public function authenticate(string $username, string $password): User
    {
        $username = trim($username);

        if (! config('helpdesk.ldap.enabled', false) || $username === '' || $password === '') {
            throw LdapAuthenticationException::invalidCredentials();
        }

        if (! extension_loaded('ldap')) {
            report(new \RuntimeException('LDAP extension is not loaded.'));

            throw LdapAuthenticationException::unavailable();
        }

        $serviceConnection = $this->connect();
        $this->bindServiceAccount($serviceConnection);

        $entry = $this->findUserEntry($serviceConnection, $username);
        $userDn = (string) ($entry['dn'] ?? '');

        if ($userDn === '') {
            throw LdapAuthenticationException::invalidCredentials();
        }

        $userConnection = $this->connect();

        if (! @ldap_bind($userConnection, $userDn, $password)) {
            throw LdapAuthenticationException::invalidCredentials();
        }

        return $this->synchronizer->sync(new LdapUserData(
            username: $this->entryValue($entry, config('helpdesk.ldap.username_attribute', 'uid')) ?? $username,
            dn: $userDn,
            email: $this->entryValue($entry, config('helpdesk.ldap.email_attribute', 'mail')),
            displayName: $this->entryValue($entry, config('helpdesk.ldap.display_name_attribute', 'cn')),
            externalId: $this->entryValue($entry, config('helpdesk.ldap.unique_id_attribute', 'guid')),
            department: $this->entryValue($entry, config('helpdesk.ldap.department_attribute', 'department')),
            groups: $this->resolveGroups($serviceConnection, $entry),
        ));
    }

    /**
     * @return \LDAP\Connection
     */
    private function connect(): mixed
    {
        $host = (string) config('helpdesk.ldap.host', '');
        $port = (int) config('helpdesk.ldap.port', 389);

        if ($host === '') {
            throw LdapAuthenticationException::unavailable();
        }

        $encryption = strtolower((string) config('helpdesk.ldap.encryption', 'none'));
        $connectionHost = $encryption === 'ldaps' && ! str_starts_with($host, 'ldaps://')
            ? 'ldaps://'.$host
            : $host;

        $connection = @ldap_connect($connectionHost, $port);

        if ($connection === false) {
            throw LdapAuthenticationException::unavailable();
        }

        ldap_set_option($connection, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($connection, LDAP_OPT_REFERRALS, 0);

        $networkTimeout = (int) config('helpdesk.ldap.network_timeout', 5);
        if ($networkTimeout > 0 && defined('LDAP_OPT_NETWORK_TIMEOUT')) {
            ldap_set_option($connection, LDAP_OPT_NETWORK_TIMEOUT, $networkTimeout);
        }

        if ($encryption === 'starttls' && ! @ldap_start_tls($connection)) {
            throw LdapAuthenticationException::unavailable();
        }

        return $connection;
    }

    /**
     * @param  \LDAP\Connection  $connection
     */
    private function bindServiceAccount(mixed $connection): void
    {
        $bindDn = (string) config('helpdesk.ldap.bind_dn', '');
        $bindPassword = (string) config('helpdesk.ldap.bind_password', '');
        $bound = $bindDn !== ''
            ? @ldap_bind($connection, $bindDn, $bindPassword)
            : @ldap_bind($connection);

        if (! $bound) {
            throw LdapAuthenticationException::unavailable();
        }
    }

    /**
     * @param  \LDAP\Connection  $connection
     * @return array<string, mixed>
     */
    private function findUserEntry(mixed $connection, string $username): array
    {
        $baseDn = (string) config('helpdesk.ldap.base_dn', '');
        $filterTemplate = (string) config('helpdesk.ldap.user_filter', '(&(objectClass=person)(uid={username}))');

        if ($baseDn === '') {
            throw LdapAuthenticationException::unavailable();
        }

        $filter = str_replace('{username}', $this->escapeFilterValue($username), $filterTemplate);
        $attributes = array_values(array_unique(array_filter([
            config('helpdesk.ldap.username_attribute', 'uid'),
            config('helpdesk.ldap.email_attribute', 'mail'),
            config('helpdesk.ldap.display_name_attribute', 'cn'),
            config('helpdesk.ldap.unique_id_attribute', 'guid'),
            config('helpdesk.ldap.department_attribute', 'department'),
            ...$this->configuredList('user_group_attributes'),
        ])));

        $search = @ldap_search($connection, $baseDn, $filter, $attributes);

        if ($search === false) {
            throw LdapAuthenticationException::unavailable();
        }

        $entries = ldap_get_entries($connection, $search);

        if (($entries['count'] ?? 0) < 1) {
            throw LdapAuthenticationException::invalidCredentials();
        }

        return $entries[0];
    }

    /**
     * @param  \LDAP\Connection  $connection
     * @param  array<string, mixed>  $entry
     * @return array<int, string>
     */
    private function resolveGroups(mixed $connection, array $entry): array
    {
        if (! config('helpdesk.ldap.groups_enabled', false)) {
            return [];
        }

        $groups = $this->groupsFromUserAttributes($entry);

        return array_values(array_unique([
            ...$groups,
            ...$this->groupsFromSearch($connection, $entry),
        ]));
    }

    /**
     * @param  array<string, mixed>  $entry
     * @return array<int, string>
     */
    private function groupsFromUserAttributes(array $entry): array
    {
        $groups = [];

        foreach ($this->configuredList('user_group_attributes') as $attribute) {
            $values = $this->entryValues($entry, $attribute);

            foreach ($values as $value) {
                $groups[] = $value;
            }
        }

        return $groups;
    }

    /**
     * @param  \LDAP\Connection  $connection
     * @param  array<string, mixed>  $entry
     * @return array<int, string>
     */
    private function groupsFromSearch(mixed $connection, array $entry): array
    {
        $baseDn = (string) config('helpdesk.ldap.group_base_dn', '');

        if ($baseDn === '') {
            return [];
        }

        $userDn = (string) ($entry['dn'] ?? '');
        $memberAttribute = (string) config('helpdesk.ldap.group_member_attribute', 'member');
        $filterTemplate = (string) config('helpdesk.ldap.group_filter', '(objectClass=groupOfNames)');

        $filter = str_contains($filterTemplate, '{dn}')
            || str_contains($filterTemplate, '{username}')
            || str_contains($filterTemplate, '{external_id}')
            ? str_replace(
                ['{dn}', '{username}', '{external_id}'],
                [
                    $this->escapeFilterValue($userDn),
                    $this->escapeFilterValue($this->entryValue($entry, config('helpdesk.ldap.username_attribute', 'uid')) ?? ''),
                    $this->escapeFilterValue($this->entryValue($entry, config('helpdesk.ldap.unique_id_attribute', 'guid')) ?? ''),
                ],
                $filterTemplate,
            )
            : sprintf('(&%s(%s=%s))', $filterTemplate, $memberAttribute, $this->escapeFilterValue($userDn));

        $search = @ldap_search($connection, $baseDn, $filter, ['dn', 'cn']);

        if ($search === false) {
            return [];
        }

        $entries = ldap_get_entries($connection, $search);
        $groups = [];

        for ($index = 0; $index < (int) ($entries['count'] ?? 0); $index++) {
            if (isset($entries[$index]['dn'])) {
                $groups[] = (string) $entries[$index]['dn'];
            }

            foreach ($this->entryValues($entries[$index], 'cn') as $commonName) {
                $groups[] = $commonName;
            }
        }

        return $groups;
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    private function entryValue(array $entry, mixed $attribute): ?string
    {
        return $this->entryValues($entry, (string) $attribute)[0] ?? null;
    }

    /**
     * @param  array<string, mixed>  $entry
     * @return array<int, string>
     */
    private function entryValues(array $entry, string $attribute): array
    {
        $attribute = strtolower($attribute);

        if ($attribute === '' || ! isset($entry[$attribute])) {
            return [];
        }

        $values = [];
        $count = (int) ($entry[$attribute]['count'] ?? 0);

        for ($index = 0; $index < $count; $index++) {
            if (isset($entry[$attribute][$index]) && $entry[$attribute][$index] !== '') {
                $values[] = (string) $entry[$attribute][$index];
            }
        }

        return $values;
    }

    /**
     * @return array<int, string>
     */
    private function configuredList(string $key): array
    {
        return array_values(array_filter(array_map(
            'trim',
            preg_split('/[,;]/', (string) config('helpdesk.ldap.'.$key, '')) ?: [],
        )));
    }

    private function escapeFilterValue(string $value): string
    {
        if (function_exists('ldap_escape')) {
            return ldap_escape($value, '', LDAP_ESCAPE_FILTER);
        }

        return str_replace(
            ['\\', '*', '(', ')', "\x00"],
            ['\\5c', '\\2a', '\\28', '\\29', '\\00'],
            $value,
        );
    }
}
