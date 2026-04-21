<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Seed the application's roles.
     */
    public function run(): void
    {
        $timestamp = now();

        Role::query()->upsert([
            [
                'name' => 'Admin',
                'slug' => 'admin',
                'description' => 'System administrator with full access.',
                'is_system' => true,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'name' => 'Solver',
                'slug' => 'solver',
                'description' => 'Internal staff member handling assigned tickets.',
                'is_system' => true,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'name' => 'User',
                'slug' => 'user',
                'description' => 'Standard requester creating and tracking tickets.',
                'is_system' => true,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
        ], ['slug'], ['name', 'description', 'is_system', 'updated_at']);
    }
}
