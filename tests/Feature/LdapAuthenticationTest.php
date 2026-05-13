<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Services\Ldap\LdapAuthenticator;
use App\Services\Ldap\LdapRoleMapper;
use App\Services\Ldap\LdapUserData;
use App\Services\Ldap\LdapUserSynchronizer;
use Database\Seeders\DemoUserSeeder;
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
        config(['helpdesk.ldap.enabled' => true]);

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
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_local_demo_login_can_authenticate_by_email_when_safe_and_enabled(): void
    {
        config([
            'app.env' => 'local',
            'helpdesk.ldap.enabled' => false,
            'helpdesk.demo.login_enabled' => true,
        ]);

        $user = User::query()->create([
            'username' => 'demo.user',
            'name' => 'Demo User',
            'email' => 'demo@example.org',
            'password' => 'password',
            'auth_source' => 'local-demo',
            'is_active' => true,
        ]);

        $this->post(route('login.store'), [
            'username' => 'demo@example.org',
            'password' => 'password',
        ])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_local_demo_login_can_authenticate_by_username_when_safe_and_enabled(): void
    {
        config([
            'app.env' => 'testing',
            'helpdesk.ldap.enabled' => false,
            'helpdesk.demo.login_enabled' => true,
        ]);

        $user = User::query()->create([
            'username' => 'demo.user',
            'name' => 'Demo User',
            'email' => 'demo@example.org',
            'password' => 'password',
            'auth_source' => 'local-demo',
            'is_active' => true,
        ]);

        $this->post(route('login.store'), [
            'username' => 'demo.user',
            'password' => 'password',
        ])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_local_demo_login_is_rejected_in_production_even_when_enabled(): void
    {
        config([
            'app.env' => 'production',
            'helpdesk.ldap.enabled' => false,
            'helpdesk.demo.login_enabled' => true,
        ]);

        User::query()->create([
            'username' => 'demo.user',
            'name' => 'Demo User',
            'email' => 'demo@example.org',
            'password' => 'password',
            'auth_source' => 'local-demo',
            'is_active' => true,
        ]);

        $this->post(route('login.store'), [
            'username' => 'demo@example.org',
            'password' => 'password',
        ])
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_login_page_shows_demo_accounts_only_when_demo_login_is_active(): void
    {
        config([
            'app.env' => 'local',
            'helpdesk.ldap.enabled' => false,
            'helpdesk.demo.login_enabled' => true,
        ]);

        $this->get(route('login'))
            ->assertOk()
            ->assertSeeText('admin@example.org / password');

        config(['app.env' => 'production']);

        $this->get(route('login'))
            ->assertOk()
            ->assertDontSeeText('admin@example.org / password');
    }

    public function test_demo_user_seeder_creates_local_demo_users_with_roles(): void
    {
        $this->seed(DemoUserSeeder::class);

        $admin = User::query()->where('email', 'admin@example.org')->firstOrFail();
        $solver = User::query()->where('email', 'solver@example.org')->firstOrFail();
        $user = User::query()->where('email', 'user@example.org')->firstOrFail();

        $this->assertSame('local-demo', $admin->auth_source);
        $this->assertSame('local-demo', $solver->auth_source);
        $this->assertSame('local-demo', $user->auth_source);
        $this->assertTrue($admin->hasRole(Role::SLUG_ADMIN));
        $this->assertTrue($solver->hasRole(Role::SLUG_SOLVER));
        $this->assertTrue($user->hasRole(Role::SLUG_USER));
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
