<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class TicketVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private Role $userRole;

    private Role $solverRole;

    private Role $adminRole;

    private TicketStatus $defaultStatus;

    private TicketPriority $defaultPriority;

    private TicketCategory $defaultCategory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRole = Role::query()->create([
            'name' => 'User',
            'slug' => Role::SLUG_USER,
            'is_system' => true,
        ]);

        $this->solverRole = Role::query()->create([
            'name' => 'Solver',
            'slug' => Role::SLUG_SOLVER,
            'is_system' => true,
        ]);

        $this->adminRole = Role::query()->create([
            'name' => 'Admin',
            'slug' => Role::SLUG_ADMIN,
            'is_system' => true,
        ]);

        $this->defaultStatus = TicketStatus::query()->create([
            'name' => 'New',
            'slug' => 'new',
            'sort_order' => 1,
            'is_default' => true,
        ]);

        $this->defaultPriority = TicketPriority::query()->create([
            'name' => 'Normal',
            'slug' => 'normal',
            'sort_order' => 1,
            'is_default' => true,
        ]);

        $this->defaultCategory = TicketCategory::query()->create([
            'name' => 'Obecné',
            'slug' => 'general',
            'is_active' => true,
        ]);
    }

    public function test_public_ticket_is_visible_to_authenticated_user_in_list_and_detail(): void
    {
        $requester = $this->createUserWithRole($this->userRole);
        $viewer = $this->createUserWithRole($this->userRole);
        $ticket = $this->createTicket([
            'requester' => $requester,
            'subject' => 'Public visibility ticket',
            'visibility' => Ticket::VISIBILITY_PUBLIC,
        ]);

        $this->actingAs($viewer);

        $this->get(route('tickets.index'))
            ->assertOk()
            ->assertSeeText($ticket->subject);

        $this->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSeeText($ticket->subject);
    }

    public function test_internal_ticket_is_visible_to_solver(): void
    {
        $requester = $this->createUserWithRole($this->userRole);
        $solver = $this->createUserWithRole($this->solverRole);
        $ticket = $this->createTicket([
            'requester' => $requester,
            'subject' => 'Internal visibility ticket',
            'visibility' => Ticket::VISIBILITY_INTERNAL,
        ]);

        $this->actingAs($solver);

        $this->get(route('tickets.index'))
            ->assertOk()
            ->assertSeeText($ticket->subject);

        $this->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSeeText($ticket->subject);
    }

    public function test_private_ticket_is_visible_only_to_requester_assignee_and_admin(): void
    {
        $requester = $this->createUserWithRole($this->userRole);
        $assignee = $this->createUserWithRole($this->solverRole);
        $admin = $this->createUserWithRole($this->adminRole);
        $unrelatedUser = $this->createUserWithRole($this->userRole);
        $ticket = $this->createTicket([
            'requester' => $requester,
            'assignee' => $assignee,
            'subject' => 'Private visibility ticket',
            'visibility' => Ticket::VISIBILITY_PRIVATE,
        ]);

        foreach ([$requester, $assignee, $admin] as $authorizedUser) {
            $this->actingAs($authorizedUser);

            $this->get(route('tickets.index'))
                ->assertOk()
                ->assertSeeText($ticket->subject);

            $this->get(route('tickets.show', $ticket))
                ->assertOk()
                ->assertSeeText($ticket->subject);
        }

        $this->actingAs($unrelatedUser);

        $this->get(route('tickets.index'))
            ->assertOk()
            ->assertDontSeeText($ticket->subject);

        $this->get(route('tickets.show', $ticket))
            ->assertForbidden();
    }

    public function test_watcher_does_not_gain_access_to_private_ticket(): void
    {
        $requester = $this->createUserWithRole($this->userRole);
        $watcher = $this->createUserWithRole($this->userRole);
        $ticket = $this->createTicket([
            'requester' => $requester,
            'subject' => 'Watcher access ticket',
            'visibility' => Ticket::VISIBILITY_PRIVATE,
        ]);

        $ticket->watchers()->attach($watcher->id);

        $this->actingAs($watcher);

        $this->get(route('tickets.index'))
            ->assertOk()
            ->assertDontSeeText($ticket->subject);

        $this->get(route('tickets.show', $ticket))
            ->assertForbidden();
    }

    public function test_legacy_restricted_ticket_is_treated_as_private_for_admin_visibility(): void
    {
        $requester = $this->createUserWithRole($this->userRole);
        $admin = $this->createUserWithRole($this->adminRole);
        $unrelatedUser = $this->createUserWithRole($this->userRole);
        $ticket = $this->createTicket([
            'requester' => $requester,
            'subject' => 'Legacy restricted ticket',
            'visibility' => Ticket::LEGACY_VISIBILITY_RESTRICTED,
        ]);

        $this->actingAs($admin);

        $this->get(route('tickets.index'))
            ->assertOk()
            ->assertSeeText($ticket->subject);

        $this->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSeeText($ticket->subject);

        $this->actingAs($unrelatedUser);

        $this->get(route('tickets.index'))
            ->assertOk()
            ->assertDontSeeText($ticket->subject);

        $this->get(route('tickets.show', $ticket))
            ->assertForbidden();
    }

    public function test_solver_sees_inline_list_edit_controls(): void
    {
        $requester = $this->createUserWithRole($this->userRole);
        $solver = $this->createUserWithRole($this->solverRole);
        $ticket = $this->createTicket([
            'requester' => $requester,
            'subject' => 'Inline editable ticket',
            'visibility' => Ticket::VISIBILITY_INTERNAL,
        ]);

        $this->actingAs($solver);

        $this->get(route('tickets.index'))
            ->assertOk()
            ->assertSeeText($ticket->subject)
            ->assertSee('data-inline-mode="status"', false)
            ->assertSee('data-inline-mode="priority"', false);
    }

    public function test_regular_user_sees_passive_badges_in_ticket_list(): void
    {
        $requester = $this->createUserWithRole($this->userRole);
        $ticket = $this->createTicket([
            'requester' => $requester,
            'subject' => 'Passive badge ticket',
            'visibility' => Ticket::VISIBILITY_PUBLIC,
        ]);

        $this->actingAs($requester);

        $this->get(route('tickets.index'))
            ->assertOk()
            ->assertSeeText($ticket->subject)
            ->assertDontSee('data-inline-mode="status"', false)
            ->assertDontSee('data-inline-mode="priority"', false);
    }

    public function test_solver_can_update_status_via_json_patch(): void
    {
        $requester = $this->createUserWithRole($this->userRole);
        $solver = $this->createUserWithRole($this->solverRole);
        $inProgressStatus = TicketStatus::query()->create([
            'name' => 'In Progress',
            'slug' => 'in_progress',
            'sort_order' => 2,
        ]);
        $ticket = $this->createTicket([
            'requester' => $requester,
            'visibility' => Ticket::VISIBILITY_INTERNAL,
        ]);

        $this->actingAs($solver);

        $this->patchJson(route('tickets.status.update', $ticket), [
            'status_id' => $inProgressStatus->id,
        ])
            ->assertOk()
            ->assertJsonPath('ticket.status.id', $inProgressStatus->id)
            ->assertJsonPath('ticket.status.name', $inProgressStatus->name);

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'ticket_status_id' => $inProgressStatus->id,
        ]);
    }

    public function test_solver_can_update_priority_via_json_patch(): void
    {
        $requester = $this->createUserWithRole($this->userRole);
        $solver = $this->createUserWithRole($this->solverRole);
        $highPriority = TicketPriority::query()->create([
            'name' => 'High',
            'slug' => 'high',
            'sort_order' => 2,
        ]);
        $ticket = $this->createTicket([
            'requester' => $requester,
            'visibility' => Ticket::VISIBILITY_INTERNAL,
        ]);

        $this->actingAs($solver);

        $this->patchJson(route('tickets.priority.update', $ticket), [
            'priority_id' => $highPriority->id,
        ])
            ->assertOk()
            ->assertJsonPath('ticket.priority.id', $highPriority->id)
            ->assertJsonPath('ticket.priority.name', $highPriority->name);

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'ticket_priority_id' => $highPriority->id,
        ]);
    }

    public function test_public_comment_updates_ticket_updated_at_timestamp(): void
    {
        try {
            Carbon::setTestNow('2026-04-23 10:00:00');

            $requester = $this->createUserWithRole($this->userRole);
            $ticket = $this->createTicket([
                'requester' => $requester,
                'visibility' => Ticket::VISIBILITY_PUBLIC,
            ]);

            $ticket->forceFill([
                'updated_at' => Carbon::now()->subDay(),
            ])->saveQuietly();

            Carbon::setTestNow('2026-04-23 12:15:00');

            $this->actingAs($requester)
                ->post(route('tickets.comments.store', $ticket), [
                    'body' => 'Nový komentář k ticketu.',
                ])
                ->assertRedirect(route('tickets.show', $ticket));

            $ticket->refresh();

            $this->assertTrue($ticket->updated_at->equalTo(Carbon::now()));
            $this->assertTrue($ticket->last_activity_at->equalTo(Carbon::now()));
        } finally {
            Carbon::setTestNow();
        }
    }

    private function createUserWithRole(Role $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach($role->id);

        return $user;
    }

    private function createTicket(array $overrides = []): Ticket
    {
        $requester = $overrides['requester'] ?? $this->createUserWithRole($this->userRole);
        $assignee = $overrides['assignee'] ?? null;

        return Ticket::query()->create([
            'ticket_number' => 'T-TEST-'.Str::upper(Str::random(8)),
            'subject' => $overrides['subject'] ?? 'Ticket '.Str::random(8),
            'description' => $overrides['description'] ?? 'Test description',
            'visibility' => $overrides['visibility'] ?? Ticket::VISIBILITY_PUBLIC,
            'requester_id' => $requester->id,
            'assignee_id' => $assignee?->id,
            'ticket_status_id' => ($overrides['status'] ?? $this->defaultStatus)->id,
            'ticket_priority_id' => ($overrides['priority'] ?? $this->defaultPriority)->id,
            'ticket_category_id' => ($overrides['category'] ?? $this->defaultCategory)->id,
        ]);
    }
}
