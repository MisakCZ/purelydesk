<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private Role $userRole;

    private Role $solverRole;

    private Role $adminRole;

    private TicketStatus $newStatus;

    private TicketStatus $waitingUserStatus;

    private TicketStatus $resolvedStatus;

    private TicketStatus $closedStatus;

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

        $this->newStatus = $this->createStatus('New', 'new', 1);
        $this->waitingUserStatus = $this->createStatus('Waiting User', 'waiting_user', 2);
        $this->resolvedStatus = $this->createStatus('Resolved', 'resolved', 3);
        $this->closedStatus = $this->createStatus('Closed', 'closed', 4, true);
        $this->createStatus('Cancelled', 'cancelled', 5, true);

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

    public function test_dashboard_route_requires_authentication(): void
    {
        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_user_sees_own_tickets_on_dashboard(): void
    {
        $user = $this->createUserWithRoles([$this->userRole]);
        $ticket = $this->createTicket([
            'requester' => $user,
            'subject' => 'My visible dashboard ticket',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSeeText(__('dashboard.sections.my_open_tickets.heading'))
            ->assertSeeText($ticket->subject)
            ->assertSee(e(route('tickets.index', ['scope' => 'open', 'relation' => 'requester'])), false);
    }

    public function test_user_does_not_see_other_private_or_internal_ticket(): void
    {
        $viewer = $this->createUserWithRoles([$this->userRole]);
        $otherUser = $this->createUserWithRoles([$this->userRole]);
        $internalTicket = $this->createTicket([
            'requester' => $otherUser,
            'subject' => 'Other internal dashboard ticket',
            'visibility' => Ticket::VISIBILITY_INTERNAL,
        ]);
        $privateTicket = $this->createTicket([
            'requester' => $otherUser,
            'subject' => 'Other private dashboard ticket',
            'visibility' => Ticket::VISIBILITY_PRIVATE,
        ]);

        $this->actingAs($viewer)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSeeText($internalTicket->subject)
            ->assertDontSeeText($privateTicket->subject);
    }

    public function test_solver_sees_new_unassigned_public_and_internal_tickets(): void
    {
        $solver = $this->createUserWithRoles([$this->solverRole]);
        $requester = $this->createUserWithRoles([$this->userRole]);
        $publicTicket = $this->createTicket([
            'requester' => $requester,
            'subject' => 'New public unassigned dashboard ticket',
            'visibility' => Ticket::VISIBILITY_PUBLIC,
        ]);
        $internalTicket = $this->createTicket([
            'requester' => $requester,
            'subject' => 'New internal unassigned dashboard ticket',
            'visibility' => Ticket::VISIBILITY_INTERNAL,
        ]);

        $this->actingAs($solver)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSeeText(__('dashboard.sections.new_unassigned_tickets.heading'))
            ->assertSee(e(route('tickets.index', ['status' => 'new', 'relation' => 'unassigned'])), false)
            ->assertSeeText($publicTicket->subject)
            ->assertSeeText($internalTicket->subject);
    }

    public function test_user_confirmation_link_uses_requester_relation_and_resolved_status(): void
    {
        $user = $this->createUserWithRoles([$this->userRole]);

        $this->createTicket([
            'requester' => $user,
            'subject' => 'Resolved waiting for requester link',
            'status' => $this->resolvedStatus,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(e(route('tickets.index', ['status' => 'resolved', 'relation' => 'requester'])), false);
    }

    public function test_solver_does_not_see_private_ticket_when_not_assignee(): void
    {
        $solver = $this->createUserWithRoles([$this->solverRole]);
        $requester = $this->createUserWithRoles([$this->userRole]);
        $ticket = $this->createTicket([
            'requester' => $requester,
            'subject' => 'Private unassigned hidden from solver dashboard',
            'visibility' => Ticket::VISIBILITY_PRIVATE,
        ]);

        $this->actingAs($solver)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSeeText($ticket->subject);
    }

    public function test_admin_without_solver_role_does_not_get_solver_dashboard(): void
    {
        $admin = $this->createUserWithRoles([$this->adminRole]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSeeText(__('dashboard.admin.heading'))
            ->assertDontSeeText(__('dashboard.sections.new_unassigned_tickets.heading'));
    }

    public function test_admin_with_solver_role_gets_solver_dashboard(): void
    {
        $adminSolver = $this->createUserWithRoles([$this->adminRole, $this->solverRole]);

        $this->actingAs($adminSolver)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSeeText(__('dashboard.admin.heading'))
            ->assertSeeText(__('dashboard.sections.new_unassigned_tickets.heading'))
            ->assertSee(e(route('tickets.index', ['scope' => 'open', 'relation' => 'assigned'])), false);
    }

    public function test_archived_tickets_are_not_shown_on_regular_dashboard(): void
    {
        $user = $this->createUserWithRoles([$this->userRole]);
        $ticket = $this->createTicket([
            'requester' => $user,
            'subject' => 'Archived dashboard ticket',
            'archived_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSeeText($ticket->subject);
    }

    private function createStatus(string $name, string $slug, int $sortOrder, bool $isClosed = false): TicketStatus
    {
        return TicketStatus::query()->create([
            'name' => $name,
            'slug' => $slug,
            'sort_order' => $sortOrder,
            'is_closed' => $isClosed,
        ]);
    }

    /**
     * @param  array<int, Role>  $roles
     */
    private function createUserWithRoles(array $roles, array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->roles()->attach(collect($roles)->pluck('id')->all());

        return $user;
    }

    private function createTicket(array $overrides = []): Ticket
    {
        $requester = $overrides['requester'] ?? $this->createUserWithRoles([$this->userRole]);
        $assignee = $overrides['assignee'] ?? null;

        return Ticket::query()->create([
            'ticket_number' => 'T-TEST-'.Str::upper(Str::random(8)),
            'subject' => $overrides['subject'] ?? 'Ticket '.Str::random(8),
            'description' => $overrides['description'] ?? 'Test description',
            'visibility' => $overrides['visibility'] ?? Ticket::VISIBILITY_PUBLIC,
            'requester_id' => $requester->id,
            'assignee_id' => $assignee?->id,
            'ticket_status_id' => ($overrides['status'] ?? $this->newStatus)->id,
            'ticket_priority_id' => ($overrides['priority'] ?? $this->defaultPriority)->id,
            'ticket_category_id' => ($overrides['category'] ?? $this->defaultCategory)->id,
            'archived_at' => $overrides['archived_at'] ?? null,
        ]);
    }
}
