<?php

namespace App\Services\Ldap;

use App\Models\Role;

class LdapRoleMapper
{
    /**
     * @param  array<int, string>  $groups
     * @return array<int, string>
     */
    public function roleSlugsForGroups(array $groups): array
    {
        $normalizedGroups = array_map($this->normalize(...), $groups);

        if ($this->matchesAnyConfiguredGroup($normalizedGroups, 'role_admin_groups')) {
            return [Role::SLUG_ADMIN];
        }

        if ($this->matchesAnyConfiguredGroup($normalizedGroups, 'role_solver_groups')) {
            return [Role::SLUG_SOLVER];
        }

        if ($this->matchesAnyConfiguredGroup($normalizedGroups, 'role_user_groups')) {
            return [Role::SLUG_USER];
        }

        if (config('helpdesk.ldap.allow_default_user_role', true)) {
            return [Role::SLUG_USER];
        }

        return [];
    }

    /**
     * @param  array<int, string>  $normalizedGroups
     */
    private function matchesAnyConfiguredGroup(array $normalizedGroups, string $configKey): bool
    {
        foreach ($this->configuredGroups($configKey) as $configuredGroup) {
            if (in_array($configuredGroup, $normalizedGroups, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    private function configuredGroups(string $configKey): array
    {
        $value = (string) config('helpdesk.ldap.'.$configKey, '');

        $groups = array_map(
            fn (string $group) => $this->normalize($group),
            str_contains($value, ';') ? explode(';', $value) : explode(',', $value),
        );

        // Keep a full DN value valid even when it contains commas.
        $groups[] = $this->normalize($value);

        return array_values(array_unique(array_filter($groups)));
    }

    private function normalize(string $value): string
    {
        return mb_strtolower(trim($value));
    }
}
