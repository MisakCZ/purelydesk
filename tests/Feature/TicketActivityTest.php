<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Ticket;
use App\Models\TicketActivity;
use App\Models\TicketCategory;
use App\Models\TicketComment;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\User;
use App\Services\TicketActivityService;
use App\Services\TicketWatcherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TicketActivityTest extends TestCase
{
    use RefreshDatabase;

    private Role $userRole;

    private Role $solverRole;

    private Role $adminRole;

    private TicketStatus $newStatus;

    private TicketStatus $assignedStatus;

    private TicketStatus $resolvedStatus;

    private TicketPriority $normalPriority;

    private TicketPriority $highPriority;

    private TicketCategory $defaultCategory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRole = Role::query()->create(['name' => 'User', 'slug' => Role::SLUG_USER, 'is_system' => true]);
        $this->solverRole = Role::query()->create(['name' => 'Solver', 'slug' => Role::SLUG_SOLVER, 'is_system' => true]);
        $this->adminRole = Role::query()->create(['name' => 'Admin', 'slug' => Role::SLUG_ADMIN, 'is_system' => true]);
        $this->newStatus = TicketStatus::query()->create(['name' => 'New', 'slug' => 'new', 'sort_order' => 1, 'is_default' => true]);
        $this->assignedStatus = TicketStatus::query()->create(['name' => 'Assigned', 'slug' => 'assigned', 'sort_order' => 2]);
        $this->resolvedStatus = TicketStatus::query()->create(['name' => 'Resolved', 'slug' => 'resolved', 'sort_order' => 3]);
        $this->normalPriority = TicketPriority::query()->create(['name' => 'Normal', 'slug' => 'normal', 'sort_order' => 1, 'is_default' => true]);
        $this->highPriority = TicketPriority::query()->create(['name' => 'High', 'slug' => 'high', 'sort_order' => 2]);
        $this->defaultCategory = TicketCategory::query()->create(['name' => 'General', 'slug' => 'general', 'is_active' => true]);
    }

    public function test_public_comment_creates_activity_and_is_unread_for_requester_assignee_and_watcher_not_author(): void
    {
        $requester = $this->createUser($this->userRole);
        $assignee = $this->createUser($this->solverRole);
        $watcher = $this->createUser($this->userRole);
        $author = $this->createUser($this->userRole);
        $ticket = $this->createTicket(['requester' => $requester, 'assignee' => $assignee]);
        $ticket->watchers()->attach($watcher->id);

        $this->actingAs($author)
            ->post(route('tickets.comments.store', $ticket), ['body' => 'New public comment'])
            ->assertRedirect(route('tickets.show', $ticket));

        $activity = TicketActivity::query()->first();

        $this->assertSame(TicketActivity::TYPE_PUBLIC_COMMENT, $activity?->type);
        $this->assertSame(TicketActivity::VISIBILITY_PUBLIC, $activity?->visibility);
        $this->assertSame(0, $this->activities()->unreadTicketCountForUser($author));
        $this->assertSame(1, $this->activities()->unreadTicketCountForUser($requester));
        $this->assertSame(1, $this->activities()->unreadTicketCountForUser($assignee));
        $this->assertSame(1, $this->activities()->unreadTicketCountForUser($watcher));
    }

    public function test_internal_note_is_hidden_from_requester_but_visible_to_solver(): void
    {
        $requester = $this->createUser($this->userRole);
        $assignee = $this->createUser($this->solverRole);
        $author = $this->createUser($this->solverRole);
        $ticket = $this->createTicket(['requester' => $requester, 'assignee' => $assignee]);

        $this->actingAs($author)
            ->post(route('tickets.internal-notes.store', $ticket), ['note_body' => 'Internal note'])
            ->assertRedirect(route('tickets.show', $ticket));

        $activity = TicketActivity::query()->first();

        $this->assertSame(TicketActivity::TYPE_INTERNAL_NOTE, $activity?->type);
        $this->assertSame(TicketActivity::VISIBILITY_INTERNAL, $activity?->visibility);
        $this->assertSame(0, $this->activities()->unreadTicketCountForUser($requester));
        $this->assertSame(1, $this->activities()->unreadTicketCountForUser($assignee));
    }

    public function test_private_ticket_activity_does_not_leak_to_unauthorized_watcher(): void
    {
        $requester = $this->createUser($this->userRole);
        $watcher = $this->createUser($this->userRole);
        $ticket = $this->createTicket([
            'requester' => $requester,
            'visibility' => Ticket::VISIBILITY_PRIVATE,
        ]);
        $ticket->watchers()->attach($watcher->id);
        $comment = TicketComment::query()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $requester->id,
            'visibility' => 'public',
            'body' => 'Private comment',
        ]);

        $this->activities()->recordPublicComment($ticket, $comment, $requester);

        $this->assertSame(0, $this->activities()->unreadTicketCountForUser($watcher));
    }

    public function test_ticket_detail_marks_visible_activities_as_read_after_rendering_notice(): void
    {
        $requester = $this->createUser($this->userRole);
        $assignee = $this->createUser($this->solverRole);
        $ticket = $this->createTicket(['requester' => $requester, 'assignee' => $assignee]);
        $comment = TicketComment::query()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $assignee->id,
            'visibility' => 'public',
            'body' => 'Visible unread comment',
        ]);
        $this->activities()->recordPublicComment($ticket, $comment, $assignee);

        $this->actingAs($requester)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSeeText(trans_choice('activities.ticket_notice', 1, ['count' => 1]));

        $this->assertSame(0, $this->activities()->unreadTicketCountForUser($requester));
    }

    public function test_header_count_counts_tickets_not_individual_activities(): void
    {
        $requester = $this->createUser($this->userRole);
        $assignee = $this->createUser($this->solverRole);
        $ticket = $this->createTicket(['requester' => $requester, 'assignee' => $assignee]);

        foreach (['First', 'Second'] as $body) {
            $comment = TicketComment::query()->create([
                'ticket_id' => $ticket->id,
                'user_id' => $assignee->id,
                'visibility' => 'public',
                'body' => $body,
            ]);
            $this->activities()->recordPublicComment($ticket, $comment, $assignee);
        }

        $this->actingAs($requester)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('activity-inbox-count', false)
            ->assertSee('>1</span>', false);
    }

    public function test_mark_all_read_marks_only_visible_activities(): void
    {
        $viewer = $this->createUser($this->userRole);
        $author = $this->createUser($this->solverRole);
        $visibleTicket = $this->createTicket(['requester' => $viewer, 'assignee' => $author]);
        $hiddenTicket = $this->createTicket([
            'requester' => $author,
            'visibility' => Ticket::VISIBILITY_PRIVATE,
        ]);
        $hiddenTicket->watchers()->attach($viewer->id);

        foreach ([$visibleTicket, $hiddenTicket] as $ticket) {
            $comment = TicketComment::query()->create([
                'ticket_id' => $ticket->id,
                'user_id' => $author->id,
                'visibility' => 'public',
                'body' => 'Activity',
            ]);
            $this->activities()->recordPublicComment($ticket, $comment, $author);
        }

        $this->actingAs($viewer)
            ->post(route('activities.mark-all-read'))
            ->assertRedirect(route('activities.index'));

        $this->assertSame(0, $this->activities()->unreadTicketCountForUser($viewer));
        $this->assertSame(1, TicketActivity::query()->where('ticket_id', $hiddenTicket->id)->count());
    }

    public function test_status_priority_and_assignee_changes_create_activities(): void
    {
        $requester = $this->createUser($this->userRole);
        $solver = $this->createUser($this->solverRole);
        $newAssignee = $this->createUser($this->solverRole);
        $ticket = $this->createTicket(['requester' => $requester]);

        $this->actingAs($solver)
            ->patch(route('tickets.status.update', $ticket), ['status_id' => $this->resolvedStatus->id])
            ->assertRedirect(route('tickets.show', $ticket));
        $this->actingAs($solver)
            ->patch(route('tickets.priority.update', $ticket), ['priority_id' => $this->highPriority->id])
            ->assertRedirect(route('tickets.show', $ticket));
        $this->actingAs($solver)
            ->patch(route('tickets.assignee.update', $ticket), ['assignee_id' => $newAssignee->id])
            ->assertRedirect(route('tickets.show', $ticket));

        $this->assertDatabaseHas('ticket_activities', ['ticket_id' => $ticket->id, 'type' => TicketActivity::TYPE_RESOLVED]);
        $this->assertDatabaseHas('ticket_activities', ['ticket_id' => $ticket->id, 'type' => TicketActivity::TYPE_PRIORITY_CHANGED]);
        $this->assertDatabaseHas('ticket_activities', ['ticket_id' => $ticket->id, 'type' => TicketActivity::TYPE_ASSIGNEE_CHANGED]);
    }

    public function test_new_watcher_and_new_assignee_do_not_receive_old_history_as_unread(): void
    {
        $requester = $this->createUser($this->userRole);
        $solver = $this->createUser($this->solverRole);
        $newAssignee = $this->createUser($this->solverRole);
        $watcher = $this->createUser($this->userRole);
        $ticket = $this->createTicket(['requester' => $requester]);
        $oldComment = TicketComment::query()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $requester->id,
            'visibility' => 'public',
            'body' => 'Old comment',
        ]);
        $this->activities()->recordPublicComment($ticket, $oldComment, $requester);

        app(TicketWatcherService::class)->startManualWatching($ticket, $watcher->id);

        $this->assertSame(0, $this->activities()->unreadTicketCountForUser($watcher));

        $this->actingAs($solver)
            ->patch(route('tickets.assignee.update', $ticket), ['assignee_id' => $newAssignee->id])
            ->assertRedirect(route('tickets.show', $ticket));

        $unreadForAssignee = $this->activities()->unreadActivitiesForTicket($newAssignee, $ticket);

        $this->assertCount(1, $unreadForAssignee);
        $this->assertSame(TicketActivity::TYPE_ASSIGNEE_CHANGED, $unreadForAssignee->first()->type);
    }

    private function createUser(Role $role, array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->roles()->attach($role->id);

        return $user;
    }

    private function createTicket(array $overrides = []): Ticket
    {
        $requester = $overrides['requester'] ?? $this->createUser($this->userRole);
        $assignee = $overrides['assignee'] ?? null;

        return Ticket::query()->create([
            'ticket_number' => 'T-TEST-'.Str::upper(Str::random(8)),
            'subject' => $overrides['subject'] ?? 'Ticket '.Str::random(8),
            'description' => $overrides['description'] ?? 'Test description',
            'visibility' => $overrides['visibility'] ?? Ticket::VISIBILITY_PUBLIC,
            'requester_id' => $requester->id,
            'assignee_id' => $assignee?->id ?? ($overrides['assignee_id'] ?? null),
            'ticket_status_id' => ($overrides['status'] ?? $this->newStatus)->id,
            'ticket_priority_id' => ($overrides['priority'] ?? $this->normalPriority)->id,
            'ticket_category_id' => ($overrides['category'] ?? $this->defaultCategory)->id,
        ]);
    }

    private function activities(): TicketActivityService
    {
        return app(TicketActivityService::class);
    }
}
