<?php

namespace Database\Seeders;

use App\Models\TicketCategory;
use Illuminate\Database\Seeder;

class TicketCategorySeeder extends Seeder
{
    /**
     * Seed the application's ticket categories.
     */
    public function run(): void
    {
        $timestamp = now();

        TicketCategory::query()->upsert([
            [
                'department_id' => null,
                'name' => 'Obecné',
                'slug' => 'obecne',
                'description' => 'Obecné helpdesk požadavky a dotazy.',
                'is_active' => true,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'department_id' => null,
                'name' => 'HW',
                'slug' => 'hw',
                'description' => 'Hardware incidents and requests.',
                'is_active' => true,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'department_id' => null,
                'name' => 'SW',
                'slug' => 'sw',
                'description' => 'Software incidents and requests.',
                'is_active' => true,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'department_id' => null,
                'name' => 'Tisk',
                'slug' => 'tisk',
                'description' => 'Printer issues, toner and print setup requests.',
                'is_active' => true,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'department_id' => null,
                'name' => 'Síť',
                'slug' => 'sit',
                'description' => 'Network connectivity and infrastructure issues.',
                'is_active' => true,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'department_id' => null,
                'name' => 'Přístupy',
                'slug' => 'pristupy',
                'description' => 'Access requests, permissions and account issues.',
                'is_active' => true,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
        ], ['slug'], ['department_id', 'name', 'description', 'is_active', 'updated_at']);
    }
}
