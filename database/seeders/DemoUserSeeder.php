<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    /**
     * Seed local demo users for non-production evaluation without LDAP.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            TicketStatusSeeder::class,
            TicketPrioritySeeder::class,
            TicketCategorySeeder::class,
        ]);

        $roles = Role::query()
            ->whereIn('slug', [Role::SLUG_ADMIN, Role::SLUG_SOLVER, Role::SLUG_USER])
            ->get()
            ->keyBy('slug');

        foreach ($this->demoUsers() as $email => $attributes) {
            $user = User::query()->updateOrCreate([
                'email' => $email,
            ], [
                'username' => $attributes['username'],
                'name' => $attributes['name'],
                'display_name' => $attributes['name'],
                'password' => Hash::make('password'),
                'auth_source' => 'local-demo',
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            $role = $roles->get($attributes['role']);

            if ($role instanceof Role) {
                $user->roles()->syncWithoutDetaching([$role->id]);
            }
        }
    }

    /**
     * @return array<string, array{username: string, name: string, role: string}>
     */
    private function demoUsers(): array
    {
        return [
            'admin@example.org' => [
                'username' => 'admin',
                'name' => 'Demo Admin',
                'role' => Role::SLUG_ADMIN,
            ],
            'solver@example.org' => [
                'username' => 'solver',
                'name' => 'Demo Solver',
                'role' => Role::SLUG_SOLVER,
            ],
            'user@example.org' => [
                'username' => 'user',
                'name' => 'Demo User',
                'role' => Role::SLUG_USER,
            ],
        ];
    }
}
