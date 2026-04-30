<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Services\Ldap\LdapAuthenticator;
use App\Services\Ldap\LdapRoleMapper;
use App\Services\Ldap\LdapUserData;
use App\Services\Ldap\LdapUserSynchronizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LdapAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            Role::SLUG_USER => 'User',
            Role::SLUG_SOLVER => 'Solver',
            Role::SLUG_ADMIN => 'Admin',
        ] as $slug => $name) {
            Role::query()->create([
                'name' => $name,
                'slug' => $slug,
                'is_system' => true,
            ]);
        }
    }

    public function test_login_page_renders(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSeeText(__('auth.login.title'))
            ->assertSeeText(__('auth.login.username'));
    }

    public function test_helpdesk_pages_require_authentication(): void
    {
        $this->get(route('tickets.index'))
            ->assertRedirect(route('login'));
    }

    public function test_explicit_dev_fallback_can_allow_helpdesk_access_without_session(): void
    {
        config(['helpdesk.auth.allow_temporary_user_fallback' => true]);

        User::query()->create([
            'name' => 'Dev User',
            'email' => 'dev@example.com',
            'password' => 'not-used',
            'is_active' => true,
        ]);

        $this->get(route('tickets.index'))
            ->assertOk();
    }

    public function test_login_uses_ldap_and_starts_laravel_session(): void
    {
        $user = User::query()->create([
            'name' => 'LDAP User',
            'email' => 'ldap@example.com',
            'password' => 'not-used-for-ldap',
            'auth_source' => 'ldap',
            'is_active' => true,
        ]);

        $this->mock(LdapAuthenticator::class, function ($mock) use ($user): void {
            $mock->shouldReceive('authenticate')
                ->once()
                ->with('ldapuser', 'secret')
                ->andReturn($user);
        });

        $this->post(route('login.store'), [
            'username' => 'ldapuser',
            'password' => 'secret',
        ])
            ->assertRedirect(route('tickets.index'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_role_mapper_prioritizes_admin_then_solver_then_user(): void
    {
        config([
            'helpdesk.ldap.role_user_groups' => 'cn=helpdesk-users,o=example',
            'helpdesk.ldap.role_solver_groups' => 'cn=helpdesk-solvers,o=example',
            'helpdesk.ldap.role_admin_groups' => 'cn=helpdesk-admins,o=example',
            'helpdesk.ldap.allow_default_user_role' => true,
        ]);

        $mapper = new LdapRoleMapper();

        $this->assertSame([Role::SLUG_ADMIN], $mapper->roleSlugsForGroups([
            'cn=helpdesk-solvers,o=example',
            'cn=helpdesk-admins,o=example',
        ]));

        $this->assertSame([Role::SLUG_SOLVER], $mapper->roleSlugsForGroups([
            'cn=helpdesk-solvers,o=example',
        ]));

        $this->assertSame([Role::SLUG_USER], $mapper->roleSlugsForGroups([]));
    }

    public function test_role_mapper_can_reject_users_without_allowed_group(): void
    {
        config([
            'helpdesk.ldap.role_admin_groups' => '',
            'helpdesk.ldap.role_solver_groups' => '',
            'helpdesk.ldap.role_user_groups' => '',
            'helpdesk.ldap.allow_default_user_role' => false,
        ]);

        $this->assertSame([], (new LdapRoleMapper())->roleSlugsForGroups([]));
    }

    public function test_ldap_binary_unique_id_is_normalized_before_user_sync(): void
    {
        config([
            'helpdesk.ldap.allow_default_user_role' => true,
        ]);

        $binaryGuid = "\xA1\x00\xB2\xC3\xD4\xE5\xF6\x07\x88\x99\xAA\xBB\xCC\xDD\xEE\xFF";

        $user = app(LdapUserSynchronizer::class)->sync(new LdapUserData(
            username: 'binary.guid',
            dn: 'cn=binary.guid,o=example',
            email: 'binary.guid@example.com',
            displayName: 'Binary GUID',
            externalId: $binaryGuid,
            department: null,
            groups: [],
        ));

        $this->assertSame('base64:'.base64_encode($binaryGuid), $user->external_id);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'external_id' => 'base64:'.base64_encode($binaryGuid),
        ]);
    }

    public function test_ldap_text_unique_id_stays_readable(): void
    {
        config([
            'helpdesk.ldap.allow_default_user_role' => true,
        ]);

        $user = app(LdapUserSynchronizer::class)->sync(new LdapUserData(
            username: 'text.guid',
            dn: 'cn=text.guid,o=example',
            email: 'text.guid@example.com',
            displayName: 'Text GUID',
            externalId: '2f4b0ed6-43d1-43a2-80d1-6cb011d0f58c',
            department: null,
            groups: [],
        ));

        $this->assertSame('2f4b0ed6-43d1-43a2-80d1-6cb011d0f58c', $user->external_id);
    }

    public function test_ldap_display_name_prefers_full_name_over_login_like_common_name(): void
    {
        config([
            'helpdesk.ldap.display_name_attribute' => 'cn',
            'helpdesk.ldap.display_name_attributes' => 'displayName,fullName,cn',
        ]);

        $method = new \ReflectionMethod(LdapAuthenticator::class, 'entryDisplayName');
        $method->setAccessible(true);

        $displayName = $method->invoke(app(LdapAuthenticator::class), [
            'cn' => [
                'count' => 1,
                0 => 'michal',
            ],
            'fullname' => [
                'count' => 1,
                0 => 'Michal Hradecký',
            ],
        ], 'michal');

        $this->assertSame('Michal Hradecký', $displayName);
    }
}
