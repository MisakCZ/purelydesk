<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            TicketStatusSeeder::class,
            TicketPrioritySeeder::class,
            TicketCategorySeeder::class,
        ]);

        $testUser = User::query()->firstOrCreate([
            'email' => 'test@example.com',
        ], [
            'name' => 'Test User',
            'email_verified_at' => now(),
            'password' => 'password',
            'remember_token' => Str::random(10),
        ]);

        $adminRole = Role::query()
            ->where('slug', Role::SLUG_ADMIN)
            ->first();

        if ($adminRole instanceof Role) {
            $testUser->roles()->syncWithoutDetaching([$adminRole->id]);
        }
    }
}
