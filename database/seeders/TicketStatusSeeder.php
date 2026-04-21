<?php

namespace Database\Seeders;

use App\Models\TicketStatus;
use Illuminate\Database\Seeder;

class TicketStatusSeeder extends Seeder
{
    /**
     * Seed the application's ticket statuses.
     */
    public function run(): void
    {
        $timestamp = now();

        TicketStatus::query()->upsert([
            [
                'name' => 'New',
                'slug' => 'new',
                'description' => 'Newly created ticket waiting for triage.',
                'color' => 'slate',
                'sort_order' => 10,
                'is_default' => true,
                'is_closed' => false,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'name' => 'Assigned',
                'slug' => 'assigned',
                'description' => 'Ticket assigned to a solver.',
                'color' => 'blue',
                'sort_order' => 20,
                'is_default' => false,
                'is_closed' => false,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'name' => 'In Progress',
                'slug' => 'in_progress',
                'description' => 'Work on the ticket is actively in progress.',
                'color' => 'indigo',
                'sort_order' => 30,
                'is_default' => false,
                'is_closed' => false,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'name' => 'Waiting for User',
                'slug' => 'waiting_user',
                'description' => 'Waiting for requester feedback or confirmation.',
                'color' => 'amber',
                'sort_order' => 40,
                'is_default' => false,
                'is_closed' => false,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'name' => 'Waiting for Third Party',
                'slug' => 'waiting_third_party',
                'description' => 'Blocked by an external vendor or third party.',
                'color' => 'orange',
                'sort_order' => 50,
                'is_default' => false,
                'is_closed' => false,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'name' => 'Resolved',
                'slug' => 'resolved',
                'description' => 'Work is complete and awaiting final closure if needed.',
                'color' => 'emerald',
                'sort_order' => 60,
                'is_default' => false,
                'is_closed' => true,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'name' => 'Closed',
                'slug' => 'closed',
                'description' => 'Ticket is fully closed.',
                'color' => 'zinc',
                'sort_order' => 70,
                'is_default' => false,
                'is_closed' => true,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'name' => 'Cancelled',
                'slug' => 'cancelled',
                'description' => 'Ticket was cancelled and will not be processed.',
                'color' => 'red',
                'sort_order' => 80,
                'is_default' => false,
                'is_closed' => true,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
        ], ['slug'], ['name', 'description', 'color', 'sort_order', 'is_default', 'is_closed', 'updated_at']);
    }
}
