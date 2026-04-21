<?php

namespace Database\Seeders;

use App\Models\TicketPriority;
use Illuminate\Database\Seeder;

class TicketPrioritySeeder extends Seeder
{
    /**
     * Seed the application's ticket priorities.
     */
    public function run(): void
    {
        $timestamp = now();

        TicketPriority::query()->upsert([
            [
                'name' => 'Low',
                'slug' => 'low',
                'description' => 'Minor issue with low urgency.',
                'color' => 'slate',
                'sort_order' => 10,
                'is_default' => false,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'name' => 'Normal',
                'slug' => 'normal',
                'description' => 'Standard ticket priority.',
                'color' => 'blue',
                'sort_order' => 20,
                'is_default' => true,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'name' => 'High',
                'slug' => 'high',
                'description' => 'Important issue needing faster handling.',
                'color' => 'orange',
                'sort_order' => 30,
                'is_default' => false,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'name' => 'Critical',
                'slug' => 'critical',
                'description' => 'Critical outage or severe business impact.',
                'color' => 'red',
                'sort_order' => 40,
                'is_default' => false,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
        ], ['slug'], ['name', 'description', 'color', 'sort_order', 'is_default', 'updated_at']);
    }
}
