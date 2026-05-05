<?php

namespace Tests\Feature;

use App\Models\Announcement;
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

    public function test_dashboard_header_links_open_filtered_and_full_ticket_lists(): void
    {
        $user = $this->createUserWithRoles([$this->userRole]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(e(route('tickets.index', ['scope' => 'open'])), false)
            ->assertSee(e(route('tickets.index', ['reset' => 1])), false);

        $this->actingAs($user)
            ->get(route('tickets.index', ['scope' => 'open']))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('tickets.index', ['reset' => 1]))
            ->assertOk();
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

    public function test_solver_dashboard_shows_assigned_open_tickets_without_expected_resolution(): void
    {
        $solver = $this->createUserWithRoles([$this->solverRole]);
        $ticket = $this->createTicket([
            'assignee' => $solver,
            'subject' => 'Assigned dashboard ticket without deadline',
        ]);

        $this->actingAs($solver)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSeeText(__('dashboard.sections.without_expected_resolution.heading'))
            ->assertSeeText($ticket->subject)
            ->assertSee(e(route('tickets.index', [
                'scope' => 'open',
                'relation' => 'assigned',
                'due' => 'missing_expected_resolution',
            ])), false);
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

    public function test_active_announcements_are_shown_on_dashboard(): void
    {
        $user = $this->createUserWithRoles([$this->userRole]);
        $announcement = $this->createAnnouncement([
            'title' => 'Active dashboard announcement',
            'body' => 'Visible operational dashboard information.',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSeeText(__('dashboard.announcements.heading'))
            ->assertSeeText($announcement->title)
            ->assertSeeText($announcement->body);
    }

    public function test_inactive_announcements_are_not_shown_on_dashboard(): void
    {
        $user = $this->createUserWithRoles([$this->userRole]);
        $announcement = $this->createAnnouncement([
            'title' => 'Inactive dashboard announcement',
            'is_active' => false,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSeeText($announcement->title);
    }

    public function test_dashboard_announcements_respect_visibility(): void
    {
        $user = $this->createUserWithRoles([$this->userRole]);
        $internalAnnouncement = $this->createAnnouncement([
            'title' => 'Internal dashboard announcement',
            'visibility' => 'internal',
        ]);
        $publicAnnouncement = $this->createAnnouncement([
            'title' => 'Public dashboard announcement',
            'visibility' => 'public',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSeeText($publicAnnouncement->title)
            ->assertDontSeeText($internalAnnouncement->title);
    }

    public function test_pinned_announcements_have_priority_on_dashboard(): void
    {
        $user = $this->createUserWithRoles([$this->userRole]);
        $regularAnnouncement = $this->createAnnouncement([
            'title' => 'Regular outage dashboard announcement',
            'type' => Announcement::TYPE_OUTAGE,
            'starts_at' => now(),
        ]);
        $pinnedAnnouncement = $this->createAnnouncement([
            'title' => 'Pinned info dashboard announcement',
            'type' => Announcement::TYPE_INFO,
            'is_pinned' => true,
            'starts_at' => now()->subDay(),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSeeTextInOrder([
                $pinnedAnnouncement->title,
                $regularAnnouncement->title,
            ]);
    }

    public function test_dashboard_announcements_block_is_hidden_without_active_announcements(): void
    {
        $user = $this->createUserWithRoles([$this->userRole]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSeeText(__('dashboard.announcements.heading'));
    }

    public function test_dashboard_announcements_are_limited_and_link_to_all_active_announcements(): void
    {
        $user = $this->createUserWithRoles([$this->userRole]);

        foreach (range(1, 4) as $index) {
            $this->createAnnouncement([
                'title' => 'Dashboard announcement '.$index,
                'starts_at' => now()->subMinutes($index),
            ]);
        }

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSeeText('Dashboard announcement 1')
            ->assertSeeText('Dashboard announcement 2')
            ->assertSeeText('Dashboard announcement 3')
            ->assertDontSeeText('Dashboard announcement 4')
            ->assertSee(e(route('announcements.active')), false);
    }

    public function test_dashboard_shows_visible_open_pinned_tickets(): void
    {
        $user = $this->createUserWithRoles([$this->userRole]);
        $pinnedTicket = $this->createTicket([
            'requester' => $user,
            'subject' => 'Pinned dashboard ticket',
            'is_pinned' => true,
            'pinned_at' => now(),
        ]);
        $closedPinnedTicket = $this->createTicket([
            'requester' => $user,
            'subject' => 'Closed pinned dashboard ticket',
            'status' => $this->closedStatus,
            'is_pinned' => true,
            'pinned_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSeeText(__('dashboard.pinned.heading'))
            ->assertSeeText($pinnedTicket->subject)
            ->assertDontSeeText($closedPinnedTicket->subject);
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
            'is_pinned' => $overrides['is_pinned'] ?? false,
            'pinned_at' => $overrides['pinned_at'] ?? null,
            'expected_resolution_at' => $overrides['expected_resolution_at'] ?? null,
            'expected_resolution_source' => $overrides['expected_resolution_source'] ?? null,
        ]);
    }

    private function createAnnouncement(array $overrides = []): Announcement
    {
        return Announcement::query()->create([
            'title' => $overrides['title'] ?? 'Announcement '.Str::random(8),
            'body' => $overrides['body'] ?? 'Operational information.',
            'type' => $overrides['type'] ?? Announcement::TYPE_INFO,
            'visibility' => $overrides['visibility'] ?? 'public',
            'is_active' => $overrides['is_active'] ?? true,
            'is_pinned' => $overrides['is_pinned'] ?? false,
            'starts_at' => $overrides['starts_at'] ?? now()->subHour(),
            'ends_at' => $overrides['ends_at'] ?? now()->addHour(),
        ]);
    }
}
