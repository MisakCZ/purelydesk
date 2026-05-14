<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketComment;
use App\Models\TicketHistory;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\User;
use App\Notifications\TicketEventNotification;
use App\Services\TicketWorkflowAutomationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
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

    public function test_solver_does_not_see_private_ticket_when_not_assignee(): void
    {
        $requester = $this->createUserWithRole($this->userRole);
        $assignee = $this->createUserWithRole($this->solverRole);
        $otherSolver = $this->createUserWithRole($this->solverRole);
        $ticket = $this->createTicket([
            'requester' => $requester,
            'assignee' => $assignee,
            'subject' => 'Private ticket hidden from other solver',
            'visibility' => Ticket::VISIBILITY_PRIVATE,
        ]);

        $this->actingAs($otherSolver);

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

    public function test_solver_sees_passive_badges_in_ticket_list(): void
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
            ->assertSee('data-ticket-field="status"', false)
            ->assertSee('data-ticket-field="priority"', false)
            ->assertDontSee('<details class="list-inline-menu" data-ticket-inline-menu>', false);
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
            ->assertDontSee('<details class="list-inline-menu" data-ticket-inline-menu>', false);
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
            ->assertJsonPath('ticket.status.name', 'In progress');

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

    public function test_solver_can_update_requester_via_detail_badge_action(): void
    {
        $originalRequester = $this->createUserWithRole($this->userRole);
        $replacementRequester = $this->createUserWithRole($this->userRole);
        $solver = $this->createUserWithRole($this->solverRole);
        $ticket = $this->createTicket([
            'requester' => $originalRequester,
            'visibility' => Ticket::VISIBILITY_INTERNAL,
        ]);

        $this->actingAs($solver)
            ->patch(route('tickets.requester.update', $ticket), [
                'requester_id' => $replacementRequester->id,
            ])
            ->assertRedirect(route('tickets.show', $ticket));

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'requester_id' => $replacementRequester->id,
        ]);
    }

    public function test_regular_user_cannot_update_requester(): void
    {
        $requester = $this->createUserWithRole($this->userRole);
        $replacementRequester = $this->createUserWithRole($this->userRole);
        $ticket = $this->createTicket([
            'requester' => $requester,
            'visibility' => Ticket::VISIBILITY_PUBLIC,
        ]);

        $this->actingAs($requester)
            ->patch(route('tickets.requester.update', $ticket), [
                'requester_id' => $replacementRequester->id,
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'requester_id' => $requester->id,
        ]);
    }

    public function test_regular_user_cannot_update_status(): void
    {
        $requester = $this->createUserWithRole($this->userRole);
        $inProgressStatus = TicketStatus::query()->create([
            'name' => 'In Progress',
            'slug' => 'in_progress',
            'sort_order' => 2,
        ]);
        $ticket = $this->createTicket([
            'requester' => $requester,
            'visibility' => Ticket::VISIBILITY_PUBLIC,
        ]);

        $this->actingAs($requester)
            ->patch(route('tickets.status.update', $ticket), [
                'status_id' => $inProgressStatus->id,
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'ticket_status_id' => $this->defaultStatus->id,
        ]);
    }

    public function test_regular_user_cannot_update_assignee(): void
    {
        $requester = $this->createUserWithRole($this->userRole);
        $assignee = $this->createUserWithRole($this->solverRole);
        $ticket = $this->createTicket([
            'requester' => $requester,
            'visibility' => Ticket::VISIBILITY_PUBLIC,
        ]);

        $this->actingAs($requester)
            ->patch(route('tickets.assignee.update', $ticket), [
                'assignee_id' => $assignee->id,
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'assignee_id' => null,
        ]);
    }

    public function test_regular_user_cannot_update_visibility(): void
    {
        $requester = $this->createUserWithRole($this->userRole);
        $ticket = $this->createTicket([
            'requester' => $requester,
            'visibility' => Ticket::VISIBILITY_PUBLIC,
        ]);

        $this->actingAs($requester)
            ->patch(route('tickets.visibility.update', $ticket), [
                'visibility' => Ticket::VISIBILITY_PRIVATE,
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'visibility' => Ticket::VISIBILITY_PUBLIC,
        ]);
    }

    public function test_solver_can_update_visibility_for_visible_ticket(): void
    {
        $requester = $this->createUserWithRole($this->userRole);
        $solver = $this->createUserWithRole($this->solverRole);
        $ticket = $this->createTicket([
            'requester' => $requester,
            'visibility' => Ticket::VISIBILITY_INTERNAL,
        ]);

        $this->actingAs($solver)
            ->patch(route('tickets.visibility.update', $ticket), [
                'visibility' => Ticket::VISIBILITY_PRIVATE,
            ])
            ->assertRedirect(route('tickets.show', $ticket));

        $ticket->refresh();

        $this->assertSame(Ticket::VISIBILITY_PRIVATE, $ticket->visibility);
        $this->assertTrue(TicketHistory::query()
            ->where('ticket_id', $ticket->id)
            ->get()
            ->contains(fn (TicketHistory $entry) => ($entry->meta['action'] ?? null) === 'visibility_update'));
    }

    public function test_admin_can_update_visibility(): void
    {
        $requester = $this->createUserWithRole($this->userRole);
        $admin = $this->createUserWithRole($this->adminRole);
        $ticket = $this->createTicket([
            'requester' => $requester,
            'visibility' => Ticket::VISIBILITY_PRIVATE,
        ]);

        $this->actingAs($admin)
            ->patch(route('tickets.visibility.update', $ticket), [
                'visibility' => Ticket::VISIBILITY_PUBLIC,
            ])
            ->assertRedirect(route('tickets.show', $ticket));

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'visibility' => Ticket::VISIBILITY_PUBLIC,
        ]);
    }

    public function test_regular_user_cannot_pin_ticket(): void
    {
        $requester = $this->createUserWithRole($this->userRole);
        $ticket = $this->createTicket([
            'requester' => $requester,
            'visibility' => Ticket::VISIBILITY_PUBLIC,
        ]);

        $this->actingAs($requester)
            ->patch(route('tickets.pin.update', $ticket), [
                'pinned' => '1',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'is_pinned' => false,
        ]);
    }

    public function test_only_admin_can_archive_ticket(): void
    {
        $requester = $this->createUserWithRole($this->userRole);
        $solver = $this->createUserWithRole($this->solverRole);
        $admin = $this->createUserWithRole($this->adminRole);
        $ticket = $this->createTicket([
            'requester' => $requester,
            'visibility' => Ticket::VISIBILITY_PUBLIC,
        ]);

        $this->actingAs($requester)
            ->patch(route('tickets.archive', $ticket))
            ->assertForbidden();

        $this->actingAs($solver)
            ->patch(route('tickets.archive', $ticket))
            ->assertForbidden();

        $this->actingAs($admin)
            ->patch(route('tickets.archive', $ticket))
            ->assertRedirect(route('tickets.show', $ticket));

        $ticket->refresh();

        $this->assertNotNull($ticket->archived_at);
        $this->assertSame($admin->id, $ticket->archived_by_user_id);
        $this->assertTrue(TicketHistory::query()
            ->where('ticket_id', $ticket->id)
            ->get()
            ->contains(fn (TicketHistory $entry) => ($entry->meta['action'] ?? null) === 'ticket_archive'));
    }

    public function test_archived_ticket_is_hidden_from_regular_list_and_visible_only_to_admin_archive(): void
    {
        $requester = $this->createUserWithRole($this->userRole);
        $otherUser = $this->createUserWithRole($this->userRole);
        $solver = $this->createUserWithRole($this->solverRole);
        $admin = $this->createUserWithRole($this->adminRole);
        $ticket = $this->createTicket([
            'requester' => $requester,
            'subject' => 'Archived ticket hidden from main list',
            'visibility' => Ticket::VISIBILITY_PUBLIC,
        ]);

        $ticket->forceFill([
            'archived_at' => now(),
            'archived_by_user_id' => $admin->id,
        ])->save();

        foreach ([$requester, $otherUser, $solver, $admin] as $actor) {
            $this->actingAs($actor)
                ->get(route('tickets.index'))
                ->assertOk()
                ->assertDontSeeText($ticket->subject);
        }

        foreach ([$requester, $otherUser, $solver] as $actor) {
            $this->actingAs($actor)
                ->get(route('tickets.index', ['archive' => 'archived']))
                ->assertOk()
                ->assertDontSeeText($ticket->subject);

            $this->actingAs($actor)
                ->get(route('tickets.show', $ticket))
                ->assertForbidden();
        }

        $this->actingAs($admin)
            ->get(route('tickets.index', ['archive' => 'archived']))
            ->assertOk()
            ->assertSeeText($ticket->subject);

        $this->actingAs($admin)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSeeText($ticket->subject);
    }

    public function test_admin_can_restore_archived_ticket(): void
    {
        $requester = $this->createUserWithRole($this->userRole);
        $admin = $this->createUserWithRole($this->adminRole);
        $ticket = $this->createTicket([
            'requester' => $requester,
            'subject' => 'Restored archived ticket',
            'visibility' => Ticket::VISIBILITY_PUBLIC,
        ]);

        $ticket->forceFill([
            'archived_at' => now(),
            'archived_by_user_id' => $admin->id,
        ])->save();

        $this->actingAs($admin)
            ->patch(route('tickets.restore', $ticket))
            ->assertRedirect(route('tickets.show', $ticket));

        $ticket->refresh();

        $this->assertNull($ticket->archived_at);
        $this->assertNull($ticket->archived_by_user_id);
        $this->assertTrue(TicketHistory::query()
            ->where('ticket_id', $ticket->id)
            ->get()
            ->contains(fn (TicketHistory $entry) => ($entry->meta['action'] ?? null) === 'ticket_restore'));

        $this->actingAs($requester)
            ->get(route('tickets.index'))
            ->assertOk()
            ->assertSeeText($ticket->subject);
    }

    public function test_regular_user_cannot_view_or_create_internal_notes(): void
    {
        $requester = $this->createUserWithRole($this->userRole);
        $solver = $this->createUserWithRole($this->solverRole);
        $ticket = $this->createTicket([
            'requester' => $requester,
            'visibility' => Ticket::VISIBILITY_PUBLIC,
        ]);

        TicketComment::query()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $solver->id,
            'visibility' => 'internal',
            'body' => 'Internal note hidden from user',
        ]);

        $this->actingAs($requester);

        $this->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertDontSeeText(__('tickets.show.internal_notes.heading'))
            ->assertDontSeeText('Internal note hidden from user');

        $this->post(route('tickets.internal-notes.store', $ticket), [
            'note_body' => 'User should not create internal notes',
        ])->assertForbidden();

        $this->assertDatabaseMissing('ticket_comments', [
            'ticket_id' => $ticket->id,
            'body' => 'User should not create internal notes',
        ]);
    }

    public function test_assigning_assignee_on_new_ticket_moves_status_to_assigned(): void
    {
        $requester = $this->createUserWithRole($this->userRole);
        $solver = $this->createUserWithRole($this->solverRole);
        $assignee = $this->createUserWithRole($this->solverRole);
        $assignedStatus = TicketStatus::query()->create([
            'name' => 'Assigned',
            'slug' => 'assigned',
            'sort_order' => 2,
        ]);
        $ticket = $this->createTicket([
            'requester' => $requester,
            'status' => $this->defaultStatus,
            'visibility' => Ticket::VISIBILITY_INTERNAL,
        ]);

        $this->actingAs($solver)
            ->patch(route('tickets.assignee.update', $ticket), [
                'assignee_id' => $assignee->id,
            ])
            ->assertRedirect(route('tickets.show', $ticket));

        $ticket->refresh();

        $this->assertSame($assignee->id, $ticket->assignee_id);
        $this->assertSame($assignedStatus->id, $ticket->ticket_status_id);

        $updateEntry = TicketHistory::query()
            ->where('ticket_id', $ticket->id)
            ->where('event', TicketHistory::EVENT_UPDATED)
            ->latest('id')
            ->first();

        $this->assertNotNull($updateEntry);
        $this->assertContains('status', $updateEntry->meta['changed_fields']);
        $this->assertContains('assignee', $updateEntry->meta['changed_fields']);
    }

    public function test_assigning_assignee_on_non_new_ticket_keeps_status_unchanged(): void
    {
        $requester = $this->createUserWithRole($this->userRole);
        $solver = $this->createUserWithRole($this->solverRole);
        $assignee = $this->createUserWithRole($this->solverRole);
        $waitingUserStatus = TicketStatus::query()->create([
            'name' => 'Waiting for User',
            'slug' => 'waiting_user',
            'sort_order' => 2,
        ]);
        TicketStatus::query()->create([
            'name' => 'Assigned',
            'slug' => 'assigned',
            'sort_order' => 3,
        ]);
        $ticket = $this->createTicket([
            'requester' => $requester,
            'status' => $waitingUserStatus,
            'visibility' => Ticket::VISIBILITY_INTERNAL,
        ]);

        $this->actingAs($solver)
            ->patch(route('tickets.assignee.update', $ticket), [
                'assignee_id' => $assignee->id,
            ])
            ->assertRedirect(route('tickets.show', $ticket));

        $ticket->refresh();

        $this->assertSame($assignee->id, $ticket->assignee_id);
        $this->assertSame($waitingUserStatus->id, $ticket->ticket_status_id);
    }

    public function test_assignee_picker_lists_only_active_solvers(): void
    {
        $requester = $this->createUserWithRole($this->userRole);
        $solver = $this->createUserWithRole($this->solverRole);
        $assignableSolver = $this->createUserWithRole($this->solverRole);
        $assignableSolver->forceFill(['name' => 'Assignable Solver'])->save();
        $regularUser = $this->createUserWithRole($this->userRole);
        $regularUser->forceFill(['name' => 'Regular Non Solver'])->save();
        $inactiveSolver = $this->createUserWithRole($this->solverRole);
        $inactiveSolver->forceFill(['name' => 'Inactive Solver', 'is_active' => false])->save();
        $ticket = $this->createTicket([
            'requester' => $requester,
            'visibility' => Ticket::VISIBILITY_INTERNAL,
        ]);

        $response = $this->actingAs($solver)
            ->get(route('tickets.show', $ticket))
            ->assertOk();

        preg_match(
            sprintf('/<form class="badge-menu-form" method="post" action="[^"]*%s\\/assignee".*?<\\/form>/s', $ticket->id),
            $response->getContent(),
            $matches,
        );

        $assigneeForm = $matches[0] ?? '';

        $this->assertStringContainsString('Assignable Solver', $assigneeForm);
        $this->assertStringNotContainsString('Regular Non Solver', $assigneeForm);
        $this->assertStringNotContainsString('Inactive Solver', $assigneeForm);
    }

    public function test_assignee_update_rejects_non_solver_user(): void
    {
        $requester = $this->createUserWithRole($this->userRole);
        $solver = $this->createUserWithRole($this->solverRole);
        $regularUser = $this->createUserWithRole($this->userRole);
        $ticket = $this->createTicket([
            'requester' => $requester,
            'visibility' => Ticket::VISIBILITY_INTERNAL,
        ]);

        $this->actingAs($solver)
            ->patch(route('tickets.assignee.update', $ticket), [
                'assignee_id' => $regularUser->id,
            ])
            ->assertSessionHasErrors('assignee_id', null, 'ticketAssignee');

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'assignee_id' => null,
        ]);
    }

    public function test_requester_is_automatic_watcher_when_ticket_is_created(): void
    {
        $requester = $this->createUserWithRole($this->userRole);

        $this->actingAs($requester);

        $this->post(route('tickets.store'), [
            'subject' => 'Automatic requester watcher',
            'description' => 'Requester should watch own ticket automatically.',
            'priority_id' => $this->defaultPriority->id,
            'category_id' => $this->defaultCategory->id,
        ])->assertRedirect(route('tickets.index'));

        $ticket = Ticket::query()->where('subject', 'Automatic requester watcher')->firstOrFail();

        $this->assertDatabaseHas('ticket_watchers', [
            'ticket_id' => $ticket->id,
            'user_id' => $requester->id,
            'is_manual' => false,
            'is_auto_participant' => true,
        ]);
    }

    public function test_new_ticket_sends_notification_to_requester(): void
    {
        config()->set('helpdesk.notifications.mail.enabled', true);
        Notification::fake();
        $requester = $this->createUserWithRole($this->userRole);
        $solver = $this->createUserWithRole($this->solverRole);
        $admin = $this->createUserWithRole($this->adminRole);

        $this->actingAs($requester);

        $this->post(route('tickets.store'), [
            'subject' => 'Notification ticket',
            'description' => 'New ticket notification body.',
            'priority_id' => $this->defaultPriority->id,
            'category_id' => $this->defaultCategory->id,
        ])->assertRedirect(route('tickets.index'));

        Notification::assertSentTo(
            $requester,
            TicketEventNotification::class,
            function (TicketEventNotification $notification) use ($requester): bool {
                $mailMessage = $notification->toMail($requester);

                return $notification->event === 'created'
                    && $notification->ticket->subject === 'Notification ticket'
                    && $mailMessage->subject === '[Helpdesk #'.$notification->ticket->ticket_number.'] new ticket'
                    && in_array('Ticket description:', $mailMessage->introLines, true)
                    && in_array('New ticket notification body.', $mailMessage->introLines, true);
            },
        );
        Notification::assertSentTo($solver, TicketEventNotification::class);
        Notification::assertNotSentTo($admin, TicketEventNotification::class);
    }

    public function test_new_internal_ticket_notifies_requester_and_solvers_but_not_admins_by_default(): void
    {
        config()->set('helpdesk.notifications.mail.enabled', true);
        Notification::fake();
        $requester = $this->createUserWithRole($this->userRole);
        $solver = $this->createUserWithRole($this->solverRole);
        $admin = $this->createUserWithRole($this->adminRole);
        $ticket = $this->createTicket([
            'requester' => $requester,
            'visibility' => Ticket::VISIBILITY_INTERNAL,
            'subject' => 'Internal created notification ticket',
        ]);

        app(\App\Services\TicketNotificationService::class)->notify($ticket, 'created', $requester, excludeActor: false);

        foreach ([$requester, $solver] as $recipient) {
            Notification::assertSentTo(
                $recipient,
                TicketEventNotification::class,
                fn (TicketEventNotification $notification) => $notification->event === 'created'
                    && (int) $notification->ticket->id === (int) $ticket->id,
            );
        }

        Notification::assertNotSentTo($admin, TicketEventNotification::class);
    }

    public function test_new_ticket_does_not_notify_solvers_when_solver_queue_notifications_are_disabled(): void
    {
        config()->set('helpdesk.notifications.mail.enabled', true);
        config()->set('helpdesk.notifications.mail.notify_solvers_on_new_tickets', false);
        Notification::fake();
        $requester = $this->createUserWithRole($this->userRole);
        $solver = $this->createUserWithRole($this->solverRole);

        $this->actingAs($requester);

        $this->post(route('tickets.store'), [
            'subject' => 'No solver queue notification ticket',
            'description' => 'Solver queue notifications are disabled.',
            'priority_id' => $this->defaultPriority->id,
            'category_id' => $this->defaultCategory->id,
        ])->assertRedirect(route('tickets.index'));

        Notification::assertSentTo($requester, TicketEventNotification::class);
        Notification::assertNotSentTo($solver, TicketEventNotification::class);
    }

    public function test_created_notification_does_not_include_assignee_or_watchers_by_default(): void
    {
        config()->set('helpdesk.notifications.mail.enabled', true);
        config()->set('helpdesk.notifications.mail.notify_solvers_on_new_tickets', false);
        Notification::fake();
        $requester = $this->createUserWithRole($this->userRole);
        $assignee = $this->createUserWithRole($this->solverRole);
        $watcher = $this->createUserWithRole($this->userRole);
        $ticket = $this->createTicket([
            'requester' => $requester,
            'assignee' => $assignee,
            'visibility' => Ticket::VISIBILITY_PUBLIC,
            'subject' => 'Created event assigned watched ticket',
        ]);
        $ticket->watcherEntries()->create([
            'user_id' => $watcher->id,
            'is_manual' => true,
            'is_auto_participant' => false,
        ]);

        app(\App\Services\TicketNotificationService::class)->notify($ticket, 'created', $requester, excludeActor: false);

        Notification::assertSentTo($requester, TicketEventNotification::class);
        Notification::assertNotSentTo($assignee, TicketEventNotification::class);
        Notification::assertNotSentTo($watcher, TicketEventNotification::class);
    }

    public function test_new_ticket_recipient_deduplication_when_requester_is_solver(): void
    {
        config()->set('helpdesk.notifications.mail.enabled', true);
        Notification::fake();
        $requesterSolver = $this->createUserWithRole($this->solverRole);

        $this->actingAs($requesterSolver);

        $this->post(route('tickets.store'), [
            'subject' => 'Requester solver notification ticket',
            'description' => 'Requester is also a solver.',
            'priority_id' => $this->defaultPriority->id,
            'category_id' => $this->defaultCategory->id,
        ])->assertRedirect(route('tickets.index'));

        Notification::assertSentToTimes($requesterSolver, TicketEventNotification::class, 1);
    }

    public function test_new_private_ticket_does_not_notify_unassigned_solver_or_admin_by_default(): void
    {
        config()->set('helpdesk.notifications.mail.enabled', true);
        Notification::fake();
        $requester = $this->createUserWithRole($this->userRole);
        $solver = $this->createUserWithRole($this->solverRole);
        $admin = $this->createUserWithRole($this->adminRole);
        $ticket = $this->createTicket([
            'requester' => $requester,
            'visibility' => Ticket::VISIBILITY_PRIVATE,
            'subject' => 'Private created notification ticket',
        ]);

        app(\App\Services\TicketNotificationService::class)->notify($ticket, 'created', $requester, excludeActor: false);

        Notification::assertSentTo($requester, TicketEventNotification::class);
        Notification::assertNotSentTo($solver, TicketEventNotification::class);
        Notification::assertNotSentTo($admin, TicketEventNotification::class);
    }

    public function test_new_private_ticket_does_not_notify_assignee_just_because_assignee_exists(): void
    {
        config()->set('helpdesk.notifications.mail.enabled', true);
        config()->set('helpdesk.notifications.mail.notify_solvers_on_new_tickets', false);
        Notification::fake();
        $requester = $this->createUserWithRole($this->userRole);
        $assignee = $this->createUserWithRole($this->solverRole);
        $ticket = $this->createTicket([
            'requester' => $requester,
            'assignee' => $assignee,
            'visibility' => Ticket::VISIBILITY_PRIVATE,
            'subject' => 'Private assigned created notification ticket',
        ]);

        app(\App\Services\TicketNotificationService::class)->notify($ticket, 'created', $requester, excludeActor: false);

        Notification::assertSentTo($requester, TicketEventNotification::class);
        Notification::assertNotSentTo($assignee, TicketEventNotification::class);
    }

    public function test_new_ticket_can_notify_admins_when_enabled(): void
    {
        config()->set('helpdesk.notifications.mail.enabled', true);
        config()->set('helpdesk.notifications.mail.notify_admins_on_new_tickets', true);
        Notification::fake();
        $requester = $this->createUserWithRole($this->userRole);
        $admin = $this->createUserWithRole($this->adminRole);
        $ticket = $this->createTicket([
            'requester' => $requester,
            'visibility' => Ticket::VISIBILITY_PRIVATE,
            'subject' => 'Private admin enabled notification ticket',
        ]);

        app(\App\Services\TicketNotificationService::class)->notify($ticket, 'created', $requester, excludeActor: false);

        Notification::assertSentTo(
            $admin,
            TicketEventNotification::class,
            fn (TicketEventNotification $notification) => $notification->event === 'created'
                && (int) $notification->ticket->id === (int) $ticket->id,
        );
    }

    public function test_public_comment_notifies_authorized_requester_assignee_and_watchers(): void
    {
        config()->set('helpdesk.notifications.mail.enabled', true);
        Notification::fake();
        $requester = $this->createUserWithRole($this->userRole);
        $assignee = $this->createUserWithRole($this->solverRole);
        $watcher = $this->createUserWithRole($this->userRole);
        $commenter = $this->createUserWithRole($this->userRole);
        $ticket = $this->createTicket([
            'requester' => $requester,
            'assignee' => $assignee,
            'visibility' => Ticket::VISIBILITY_PUBLIC,
        ]);
        $ticket->watcherEntries()->create([
            'user_id' => $watcher->id,
            'is_manual' => true,
            'is_auto_participant' => false,
        ]);

        $this->actingAs($commenter)
            ->post(route('tickets.comments.store', $ticket), [
                'body' => 'A new public comment.',
            ])
            ->assertRedirect(route('tickets.show', $ticket));

        foreach ([$requester, $assignee, $watcher] as $recipient) {
            Notification::assertSentTo(
                $recipient,
                TicketEventNotification::class,
                function (TicketEventNotification $notification) use ($recipient, $ticket): bool {
                    $mailMessage = $notification->toMail($recipient);

                    return $notification->event === 'public_comment'
                        && (int) $notification->ticket->id === (int) $ticket->id
                        && in_array('Ticket description:', $mailMessage->introLines, true)
                        && in_array($ticket->description, $mailMessage->introLines, true)
                        && in_array('Comment content:', $mailMessage->introLines, true)
                        && in_array('A new public comment.', $mailMessage->introLines, true);
                },
            );
        }

        Notification::assertNotSentTo($commenter, TicketEventNotification::class);
    }

    public function test_private_ticket_comment_does_not_notify_unauthorized_watcher(): void
    {
        config()->set('helpdesk.notifications.mail.enabled', true);
        Notification::fake();
        $requester = $this->createUserWithRole($this->userRole);
        $assignee = $this->createUserWithRole($this->solverRole);
        $unauthorizedWatcher = $this->createUserWithRole($this->userRole);
        $ticket = $this->createTicket([
            'requester' => $requester,
            'assignee' => $assignee,
            'visibility' => Ticket::VISIBILITY_PRIVATE,
        ]);
        $ticket->watcherEntries()->create([
            'user_id' => $unauthorizedWatcher->id,
            'is_manual' => true,
            'is_auto_participant' => false,
        ]);

        $this->actingAs($requester)
            ->post(route('tickets.comments.store', $ticket), [
                'body' => 'Private ticket public comment.',
            ])
            ->assertRedirect(route('tickets.show', $ticket));

        Notification::assertSentTo(
            $assignee,
            TicketEventNotification::class,
            fn (TicketEventNotification $notification) => $notification->event === 'public_comment',
        );
        Notification::assertNotSentTo($unauthorizedWatcher, TicketEventNotification::class);
    }

    public function test_internal_ticket_comment_is_not_visible_or_notified_to_unauthorized_regular_user(): void
    {
        config()->set('helpdesk.notifications.mail.enabled', true);
        Notification::fake();
        $requester = $this->createUserWithRole($this->userRole);
        $assignee = $this->createUserWithRole($this->solverRole);
        $unauthorizedWatcher = $this->createUserWithRole($this->userRole);
        $ticket = $this->createTicket([
            'requester' => $requester,
            'assignee' => $assignee,
            'visibility' => Ticket::VISIBILITY_INTERNAL,
            'description' => 'Internal ticket description visible only to allowed users.',
        ]);
        $ticket->watcherEntries()->create([
            'user_id' => $unauthorizedWatcher->id,
            'is_manual' => true,
            'is_auto_participant' => false,
        ]);

        $this->actingAs($requester)
            ->post(route('tickets.comments.store', $ticket), [
                'body' => 'Internal ticket comment content.',
            ])
            ->assertRedirect(route('tickets.show', $ticket));

        Notification::assertSentTo(
            $assignee,
            TicketEventNotification::class,
            function (TicketEventNotification $notification) use ($assignee): bool {
                $mailMessage = $notification->toMail($assignee);

                return $notification->event === 'public_comment'
                    && in_array('A new comment was added to the ticket.', $mailMessage->introLines, true)
                    && ! in_array('A new public comment was added to the ticket.', $mailMessage->introLines, true)
                    && in_array('Internal ticket comment content.', $mailMessage->introLines, true);
            },
        );
        Notification::assertNotSentTo($unauthorizedWatcher, TicketEventNotification::class);

        $this->actingAs($unauthorizedWatcher)
            ->get(route('tickets.show', $ticket))
            ->assertForbidden();
    }

    public function test_internal_note_does_not_send_ticket_event_notification(): void
    {
        config()->set('helpdesk.notifications.mail.enabled', true);
        Notification::fake();
        $requester = $this->createUserWithRole($this->userRole);
        $solver = $this->createUserWithRole($this->solverRole);
        $ticket = $this->createTicket([
            'requester' => $requester,
            'visibility' => Ticket::VISIBILITY_INTERNAL,
        ]);

        $this->actingAs($solver)
            ->post(route('tickets.internal-notes.store', $ticket), [
                'note_body' => 'Internal note should stay internal.',
            ])
            ->assertRedirect(route('tickets.show', $ticket));

        Notification::assertNothingSent();
    }

    public function test_assignee_change_notifies_requester_and_new_assignee(): void
    {
        config()->set('helpdesk.notifications.mail.enabled', true);
        Notification::fake();
        $requester = $this->createUserWithRole($this->userRole);
        $solver = $this->createUserWithRole($this->solverRole);
        $newAssignee = $this->createUserWithRole($this->solverRole);
        $watcher = $this->createUserWithRole($this->userRole);
        $assignedStatus = TicketStatus::query()->create([
            'name' => 'Assigned',
            'slug' => 'assigned',
            'sort_order' => 2,
        ]);
        $ticket = $this->createTicket([
            'requester' => $requester,
            'status' => $assignedStatus,
            'visibility' => Ticket::VISIBILITY_INTERNAL,
        ]);
        $ticket->watcherEntries()->create([
            'user_id' => $watcher->id,
            'is_manual' => true,
            'is_auto_participant' => false,
        ]);

        $this->actingAs($solver)
            ->patch(route('tickets.assignee.update', $ticket), [
                'assignee_id' => $newAssignee->id,
            ])
            ->assertRedirect(route('tickets.show', $ticket));

        Notification::assertSentTo(
            $newAssignee,
            TicketEventNotification::class,
            fn (TicketEventNotification $notification) => $notification->event === 'assignee_changed'
                && (int) $notification->ticket->id === (int) $ticket->id,
        );
        Notification::assertSentTo(
            $requester,
            TicketEventNotification::class,
            fn (TicketEventNotification $notification) => $notification->event === 'assignee_changed'
                && (int) $notification->ticket->id === (int) $ticket->id,
        );
        Notification::assertNotSentTo($watcher, TicketEventNotification::class);
    }

    public function test_assignee_change_notifies_requester_but_not_actor_when_assigning_to_self(): void
    {
        config()->set('helpdesk.notifications.mail.enabled', true);
        Notification::fake();
        $requester = $this->createUserWithRole($this->userRole);
        $solver = $this->createUserWithRole($this->solverRole);
        $assignedStatus = TicketStatus::query()->create([
            'name' => 'Assigned',
            'slug' => 'assigned',
            'sort_order' => 2,
        ]);
        $ticket = $this->createTicket([
            'requester' => $requester,
            'status' => $assignedStatus,
            'visibility' => Ticket::VISIBILITY_INTERNAL,
        ]);

        $this->actingAs($solver)
            ->patch(route('tickets.assignee.update', $ticket), [
                'assignee_id' => $solver->id,
            ])
            ->assertRedirect(route('tickets.show', $ticket));

        Notification::assertSentTo(
            $requester,
            TicketEventNotification::class,
            fn (TicketEventNotification $notification) => $notification->event === 'assignee_changed'
                && (int) $notification->ticket->id === (int) $ticket->id,
        );
        Notification::assertNotSentTo($solver, TicketEventNotification::class);
    }

    public function test_disabled_mail_notifications_prevent_ticket_notification(): void
    {
        config()->set('helpdesk.notifications.mail.enabled', false);
        Notification::fake();
        $requester = $this->createUserWithRole($this->userRole);

        $this->actingAs($requester);

        $this->post(route('tickets.store'), [
            'subject' => 'Disabled notification ticket',
            'description' => 'No notification should be sent.',
            'priority_id' => $this->defaultPriority->id,
            'category_id' => $this->defaultCategory->id,
        ])->assertRedirect(route('tickets.index'));

        Notification::assertNothingSent();
    }

    public function test_ticket_notification_hides_reply_marker_and_reply_to_when_inbound_mail_is_disabled(): void
    {
        config()->set('helpdesk.inbound.mail_enabled', false);
        config()->set('helpdesk.inbound.reply_address', 'helpdesk-replies@example.org');
        $requester = $this->createUserWithRole($this->userRole, ['preferred_locale' => 'en']);
        $ticket = $this->createTicket(['requester' => $requester]);
        $notification = new TicketEventNotification($ticket, 'created');

        $mail = $notification->toMail($requester);
        $html = (string) $mail->render();

        $this->assertStringNotContainsString(__('notifications.ticket.reply_marker', [], 'en'), $html);
        $this->assertStringContainsString('Please do not reply', $html);
        $this->assertSame([], $mail->replyTo);
        $this->assertDatabaseCount('ticket_reply_tokens', 0);
    }

    public function test_ticket_notification_shows_reply_marker_and_tokenized_reply_to_when_inbound_mail_is_enabled(): void
    {
        config()->set('helpdesk.inbound.mail_enabled', true);
        config()->set('helpdesk.inbound.use_plus_addressing', true);
        config()->set('helpdesk.inbound.reply_address', 'helpdesk-replies@example.org');
        $requester = $this->createUserWithRole($this->userRole, ['preferred_locale' => 'en']);
        $ticket = $this->createTicket(['requester' => $requester]);
        $notification = new TicketEventNotification($ticket, 'created');

        $mail = $notification->toMail($requester);
        $html = (string) $mail->render();

        $this->assertStringContainsString(__('notifications.ticket.reply_marker', [], 'en'), $html);
        $this->assertStringNotContainsString('Please do not reply', $html);
        $this->assertNotSame([], $mail->replyTo);
        $this->assertStringStartsWith('helpdesk-replies+', $mail->replyTo[0][0]);
        $this->assertStringEndsWith('@example.org', $mail->replyTo[0][0]);
        $this->assertDatabaseCount('ticket_reply_tokens', 1);
    }

    public function test_assignee_update_synchronizes_automatic_watchers(): void
    {
        $requester = $this->createUserWithRole($this->userRole);
        $solver = $this->createUserWithRole($this->solverRole);
        $oldAssignee = $this->createUserWithRole($this->solverRole);
        $newAssignee = $this->createUserWithRole($this->solverRole);
        $assignedStatus = TicketStatus::query()->create([
            'name' => 'Assigned',
            'slug' => 'assigned',
            'sort_order' => 2,
        ]);
        $ticket = $this->createTicket([
            'requester' => $requester,
            'assignee' => $oldAssignee,
            'status' => $assignedStatus,
            'visibility' => Ticket::VISIBILITY_INTERNAL,
        ]);

        $ticket->watcherEntries()->create([
            'user_id' => $oldAssignee->id,
            'is_manual' => false,
            'is_auto_participant' => true,
        ]);

        $this->actingAs($solver)
            ->patch(route('tickets.assignee.update', $ticket), [
                'assignee_id' => $newAssignee->id,
            ])
            ->assertRedirect(route('tickets.show', $ticket));

        $this->assertDatabaseHas('ticket_watchers', [
            'ticket_id' => $ticket->id,
            'user_id' => $requester->id,
            'is_auto_participant' => true,
        ]);
        $this->assertDatabaseHas('ticket_watchers', [
            'ticket_id' => $ticket->id,
            'user_id' => $newAssignee->id,
            'is_manual' => false,
            'is_auto_participant' => true,
        ]);
        $this->assertDatabaseMissing('ticket_watchers', [
            'ticket_id' => $ticket->id,
            'user_id' => $oldAssignee->id,
        ]);
    }

    public function test_manual_watching_is_not_removed_when_auto_participant_changes(): void
    {
        $requester = $this->createUserWithRole($this->userRole);
        $solver = $this->createUserWithRole($this->solverRole);
        $oldAssignee = $this->createUserWithRole($this->solverRole);
        $newAssignee = $this->createUserWithRole($this->solverRole);
        $assignedStatus = TicketStatus::query()->create([
            'name' => 'Assigned',
            'slug' => 'assigned',
            'sort_order' => 2,
        ]);
        $ticket = $this->createTicket([
            'requester' => $requester,
            'assignee' => $oldAssignee,
            'status' => $assignedStatus,
            'visibility' => Ticket::VISIBILITY_INTERNAL,
        ]);

        $ticket->watcherEntries()->create([
            'user_id' => $oldAssignee->id,
            'is_manual' => true,
            'is_auto_participant' => true,
        ]);

        $this->actingAs($solver)
            ->patch(route('tickets.assignee.update', $ticket), [
                'assignee_id' => $newAssignee->id,
            ])
            ->assertRedirect(route('tickets.show', $ticket));

        $this->assertDatabaseHas('ticket_watchers', [
            'ticket_id' => $ticket->id,
            'user_id' => $oldAssignee->id,
            'is_manual' => true,
            'is_auto_participant' => false,
        ]);
    }

    public function test_watched_filter_includes_automatic_participant_watchers(): void
    {
        $requester = $this->createUserWithRole($this->userRole);
        $otherUser = $this->createUserWithRole($this->userRole);
        $watchedTicket = $this->createTicket([
            'requester' => $requester,
            'subject' => 'Automatic watched filter match',
        ]);
        $unwatchedTicket = $this->createTicket([
            'requester' => $otherUser,
            'subject' => 'Automatic watched filter miss',
        ]);

        $watchedTicket->watcherEntries()->create([
            'user_id' => $requester->id,
            'is_manual' => false,
            'is_auto_participant' => true,
        ]);

        $this->actingAs($requester);

        $this->get(route('tickets.index', ['watched' => '1']))
            ->assertOk()
            ->assertSeeText($watchedTicket->subject)
            ->assertDontSeeText($unwatchedTicket->subject);
    }

    public function test_ticket_index_relation_requester_shows_only_requested_tickets(): void
    {
        $user = $this->createUserWithRole($this->userRole);
        $otherUser = $this->createUserWithRole($this->userRole);
        $requestedTicket = $this->createTicket([
            'requester' => $user,
            'subject' => 'Relation requester match',
        ]);
        $otherTicket = $this->createTicket([
            'requester' => $otherUser,
            'subject' => 'Relation requester miss',
        ]);

        $this->actingAs($user)
            ->get(route('tickets.index', ['relation' => 'requester']))
            ->assertOk()
            ->assertSeeText($requestedTicket->subject)
            ->assertDontSeeText($otherTicket->subject);
    }

    public function test_ticket_index_relation_assigned_shows_only_assigned_tickets(): void
    {
        $solver = $this->createUserWithRole($this->solverRole);
        $otherSolver = $this->createUserWithRole($this->solverRole);
        $assignedTicket = $this->createTicket([
            'assignee' => $solver,
            'subject' => 'Relation assigned match',
        ]);
        $otherTicket = $this->createTicket([
            'assignee' => $otherSolver,
            'subject' => 'Relation assigned miss',
        ]);

        $this->actingAs($solver)
            ->get(route('tickets.index', ['relation' => 'assigned']))
            ->assertOk()
            ->assertSeeText($assignedTicket->subject)
            ->assertDontSeeText($otherTicket->subject);
    }

    public function test_ticket_index_relation_watched_shows_watched_tickets(): void
    {
        $user = $this->createUserWithRole($this->userRole);
        $watchedTicket = $this->createTicket([
            'subject' => 'Relation watched match',
        ]);
        $unwatchedTicket = $this->createTicket([
            'subject' => 'Relation watched miss',
        ]);

        $watchedTicket->watcherEntries()->create([
            'user_id' => $user->id,
            'is_manual' => true,
            'is_auto_participant' => false,
        ]);

        $this->actingAs($user)
            ->get(route('tickets.index', ['relation' => 'watched']))
            ->assertOk()
            ->assertSeeText($watchedTicket->subject)
            ->assertDontSeeText($unwatchedTicket->subject);
    }

    public function test_ticket_index_relation_unassigned_shows_unassigned_tickets(): void
    {
        $solver = $this->createUserWithRole($this->solverRole);
        $unassignedTicket = $this->createTicket([
            'subject' => 'Relation unassigned match',
        ]);
        $assignedTicket = $this->createTicket([
            'assignee' => $solver,
            'subject' => 'Relation unassigned miss',
        ]);

        $this->actingAs($solver)
            ->get(route('tickets.index', ['relation' => 'unassigned']))
            ->assertOk()
            ->assertSeeText($unassignedTicket->subject)
            ->assertDontSeeText($assignedTicket->subject);
    }

    public function test_ticket_index_relation_filters_still_respect_visibility(): void
    {
        $viewer = $this->createUserWithRole($this->userRole);
        $requester = $this->createUserWithRole($this->userRole);
        $privateTicket = $this->createTicket([
            'requester' => $requester,
            'subject' => 'Relation watched private hidden',
            'visibility' => Ticket::VISIBILITY_PRIVATE,
        ]);

        $privateTicket->watcherEntries()->create([
            'user_id' => $viewer->id,
            'is_manual' => true,
            'is_auto_participant' => false,
        ]);

        $this->actingAs($viewer)
            ->get(route('tickets.index', ['relation' => 'watched']))
            ->assertOk()
            ->assertDontSeeText($privateTicket->subject);
    }

    public function test_ticket_index_can_sort_by_visible_columns(): void
    {
        $admin = $this->createUserWithRole($this->adminRole);
        $assignedStatus = TicketStatus::query()->create([
            'name' => 'Assigned',
            'slug' => 'assigned',
            'sort_order' => 2,
        ]);
        $resolvedStatus = TicketStatus::query()->create([
            'name' => 'Resolved',
            'slug' => 'resolved',
            'sort_order' => 3,
        ]);
        $highPriority = TicketPriority::query()->create([
            'name' => 'High',
            'slug' => 'high',
            'sort_order' => 2,
        ]);
        $firstTicket = $this->createTicket([
            'subject' => 'Alpha sort ticket',
            'status' => $assignedStatus,
            'priority' => $this->defaultPriority,
        ]);
        $secondTicket = $this->createTicket([
            'subject' => 'Bravo sort ticket',
            'status' => $resolvedStatus,
            'priority' => $highPriority,
        ]);

        $firstTicket->forceFill([
            'ticket_number' => '2026-001',
            'updated_at' => Carbon::parse('2026-04-20 10:00:00'),
        ])->save();
        $secondTicket->forceFill([
            'ticket_number' => '2026-002',
            'updated_at' => Carbon::parse('2026-04-21 10:00:00'),
        ])->save();

        $this->actingAs($admin);

        foreach (['number', 'subject', 'status', 'priority'] as $sort) {
            $this->get(route('tickets.index', ['sort' => $sort, 'direction' => 'asc']))
                ->assertOk()
                ->assertSeeInOrder([$firstTicket->subject, $secondTicket->subject]);
        }

        $this->get(route('tickets.index', ['sort' => 'updated_at', 'direction' => 'asc']))
            ->assertOk()
            ->assertSeeInOrder([$firstTicket->subject, $secondTicket->subject]);
    }

    public function test_ticket_index_filters_and_sorting_persist_for_next_login(): void
    {
        $admin = $this->createUserWithRole($this->adminRole);
        $firstTicket = $this->createTicket([
            'requester' => $admin,
            'subject' => 'Alpha persisted ticket',
        ]);
        $secondTicket = $this->createTicket([
            'requester' => $admin,
            'subject' => 'Bravo persisted ticket',
        ]);

        $this->actingAs($admin);

        $this->get(route('tickets.index', [
            'relation' => 'requester',
            'sort' => 'subject',
            'direction' => 'desc',
        ]))
            ->assertOk()
            ->assertSeeInOrder([$secondTicket->subject, $firstTicket->subject]);

        $admin->refresh();

        $this->assertSame('requester', $admin->ticket_index_preferences['relation']);
        $this->assertSame('subject', $admin->ticket_index_preferences['sort']);
        $this->assertSame('desc', $admin->ticket_index_preferences['direction']);

        $this->app['session.store']->flush();
        $this->actingAs($admin);

        $this->get(route('tickets.index'))
            ->assertOk()
            ->assertSeeInOrder([$secondTicket->subject, $firstTicket->subject]);
    }

    public function test_ticket_index_scope_filter_limits_open_and_finished_tickets(): void
    {
        $admin = $this->createUserWithRole($this->adminRole);
        $closedStatus = TicketStatus::query()->create([
            'name' => 'Closed',
            'slug' => 'closed',
            'sort_order' => 9,
        ]);
        $openTicket = $this->createTicket([
            'subject' => 'Open scoped ticket',
        ]);
        $closedTicket = $this->createTicket([
            'subject' => 'Finished scoped ticket',
            'status' => $closedStatus,
        ]);

        $this->actingAs($admin);

        $this->get(route('tickets.index', ['scope' => 'open']))
            ->assertOk()
            ->assertSeeText($openTicket->subject)
            ->assertDontSeeText($closedTicket->subject);

        $this->get(route('tickets.index', ['scope' => 'finished']))
            ->assertOk()
            ->assertSeeText($closedTicket->subject)
            ->assertDontSeeText($openTicket->subject);
    }

    public function test_removing_assignee_on_assigned_ticket_moves_status_to_new(): void
    {
        $requester = $this->createUserWithRole($this->userRole);
        $solver = $this->createUserWithRole($this->solverRole);
        $assignee = $this->createUserWithRole($this->solverRole);
        $assignedStatus = TicketStatus::query()->create([
            'name' => 'Assigned',
            'slug' => 'assigned',
            'sort_order' => 2,
        ]);
        $ticket = $this->createTicket([
            'requester' => $requester,
            'assignee' => $assignee,
            'status' => $assignedStatus,
            'visibility' => Ticket::VISIBILITY_INTERNAL,
        ]);

        $this->actingAs($solver)
            ->patch(route('tickets.assignee.update', $ticket), [
                'assignee_id' => '',
            ])
            ->assertRedirect(route('tickets.show', $ticket));

        $ticket->refresh();

        $this->assertNull($ticket->assignee_id);
        $this->assertSame($this->defaultStatus->id, $ticket->ticket_status_id);
    }

    public function test_removing_assignee_on_non_assigned_ticket_keeps_status_unchanged(): void
    {
        $requester = $this->createUserWithRole($this->userRole);
        $solver = $this->createUserWithRole($this->solverRole);
        $assignee = $this->createUserWithRole($this->solverRole);
        $waitingUserStatus = TicketStatus::query()->create([
            'name' => 'Waiting for User',
            'slug' => 'waiting_user',
            'sort_order' => 2,
        ]);
        TicketStatus::query()->create([
            'name' => 'Assigned',
            'slug' => 'assigned',
            'sort_order' => 3,
        ]);
        $ticket = $this->createTicket([
            'requester' => $requester,
            'assignee' => $assignee,
            'status' => $waitingUserStatus,
            'visibility' => Ticket::VISIBILITY_INTERNAL,
        ]);

        $this->actingAs($solver)
            ->patch(route('tickets.assignee.update', $ticket), [
                'assignee_id' => '',
            ])
            ->assertRedirect(route('tickets.show', $ticket));

        $ticket->refresh();

        $this->assertNull($ticket->assignee_id);
        $this->assertSame($waitingUserStatus->id, $ticket->ticket_status_id);
    }

    public function test_requester_edit_on_waiting_user_moves_status_to_assigned(): void
    {
        $requester = $this->createUserWithRole($this->userRole);
        $waitingUserStatus = TicketStatus::query()->create([
            'name' => 'Waiting for User',
            'slug' => 'waiting_user',
            'sort_order' => 2,
        ]);
        $assignedStatus = TicketStatus::query()->create([
            'name' => 'Assigned',
            'slug' => 'assigned',
            'sort_order' => 3,
        ]);
        $ticket = $this->createTicket([
            'requester' => $requester,
            'status' => $waitingUserStatus,
            'visibility' => Ticket::VISIBILITY_PUBLIC,
        ]);

        $this->actingAs($requester)
            ->patch(route('tickets.update', $ticket), [
                'subject' => 'Requester updated subject',
                'description' => 'Requester updated description',
                'priority_id' => $this->defaultPriority->id,
                'category_id' => $this->defaultCategory->id,
            ])
            ->assertRedirect(route('tickets.show', $ticket));

        $ticket->refresh();

        $this->assertSame($assignedStatus->id, $ticket->ticket_status_id);
    }

    public function test_requester_public_comment_on_waiting_user_moves_status_to_assigned(): void
    {
        $requester = $this->createUserWithRole($this->userRole);
        $waitingUserStatus = TicketStatus::query()->create([
            'name' => 'Waiting for User',
            'slug' => 'waiting_user',
            'sort_order' => 2,
        ]);
        $assignedStatus = TicketStatus::query()->create([
            'name' => 'Assigned',
            'slug' => 'assigned',
            'sort_order' => 3,
        ]);
        $ticket = $this->createTicket([
            'requester' => $requester,
            'status' => $waitingUserStatus,
            'visibility' => Ticket::VISIBILITY_PUBLIC,
        ]);

        $this->actingAs($requester)
            ->post(route('tickets.comments.store', $ticket), [
                'body' => 'Requester provided additional details.',
            ])
            ->assertRedirect(route('tickets.show', $ticket));

        $ticket->refresh();

        $this->assertSame($assignedStatus->id, $ticket->ticket_status_id);
    }

    public function test_non_requester_action_on_waiting_user_keeps_status_unchanged(): void
    {
        $requester = $this->createUserWithRole($this->userRole);
        $solver = $this->createUserWithRole($this->solverRole);
        $waitingUserStatus = TicketStatus::query()->create([
            'name' => 'Waiting for User',
            'slug' => 'waiting_user',
            'sort_order' => 2,
        ]);
        TicketStatus::query()->create([
            'name' => 'Assigned',
            'slug' => 'assigned',
            'sort_order' => 3,
        ]);
        $ticket = $this->createTicket([
            'requester' => $requester,
            'status' => $waitingUserStatus,
            'visibility' => Ticket::VISIBILITY_PUBLIC,
        ]);

        $this->actingAs($solver)
            ->post(route('tickets.comments.store', $ticket), [
                'body' => 'Solver comment without requester action.',
            ])
            ->assertRedirect(route('tickets.show', $ticket));

        $ticket->refresh();

        $this->assertSame($waitingUserStatus->id, $ticket->ticket_status_id);
    }

    public function test_status_change_to_resolved_sets_resolution_timestamps(): void
    {
        Carbon::setTestNow('2026-04-23 15:00:00');
        config(['helpdesk.workflow.resolved_auto_close_days' => 5]);

        try {
            $requester = $this->createUserWithRole($this->userRole);
            $solver = $this->createUserWithRole($this->solverRole);
            $assignedStatus = TicketStatus::query()->create([
                'name' => 'Assigned',
                'slug' => 'assigned',
                'sort_order' => 2,
            ]);
            $resolvedStatus = TicketStatus::query()->create([
                'name' => 'Resolved',
                'slug' => 'resolved',
                'sort_order' => 3,
            ]);
            $ticket = $this->createTicket([
                'requester' => $requester,
                'status' => $assignedStatus,
                'visibility' => Ticket::VISIBILITY_INTERNAL,
            ]);

            $this->actingAs($solver)
                ->patch(route('tickets.status.update', $ticket), [
                    'status_id' => $resolvedStatus->id,
                ])
                ->assertRedirect(route('tickets.show', $ticket));

            $ticket->refresh();

            $this->assertSame($resolvedStatus->id, $ticket->ticket_status_id);
            $this->assertTrue($ticket->resolved_at?->equalTo(Carbon::now()) ?? false);
            $this->assertTrue($ticket->auto_close_at?->equalTo(Carbon::now()->addDays(5)) ?? false);
            $this->assertNull($ticket->closed_at);
            $this->assertTrue($ticket->history()
                ->get()
                ->contains(fn (TicketHistory $entry) => ($entry->meta['action'] ?? null) === 'status_update'));
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_resolved_status_change_sends_notification_when_enabled(): void
    {
        Notification::fake();
        config(['helpdesk.notifications.mail.enabled' => true]);

        $requester = $this->createUserWithRole($this->userRole, ['email' => 'requester@example.org']);
        $solver = $this->createUserWithRole($this->solverRole, ['email' => 'solver@example.org']);
        $resolvedStatus = TicketStatus::query()->create([
            'name' => 'Resolved',
            'slug' => 'resolved',
            'sort_order' => 2,
        ]);
        $ticket = $this->createTicket([
            'requester' => $requester,
            'status' => $this->defaultStatus,
            'visibility' => Ticket::VISIBILITY_PUBLIC,
        ]);

        $this->actingAs($solver)
            ->patch(route('tickets.status.update', $ticket), [
                'status_id' => $resolvedStatus->id,
            ])
            ->assertRedirect(route('tickets.show', $ticket));

        Notification::assertSentTo(
            $requester,
            TicketEventNotification::class,
            fn (TicketEventNotification $notification) => $notification->event === 'resolved',
        );
    }

    public function test_regular_user_cannot_mark_ticket_as_resolved(): void
    {
        $requester = $this->createUserWithRole($this->userRole);
        $resolvedStatus = TicketStatus::query()->create([
            'name' => 'Resolved',
            'slug' => 'resolved',
            'sort_order' => 2,
        ]);
        $ticket = $this->createTicket([
            'requester' => $requester,
            'visibility' => Ticket::VISIBILITY_PUBLIC,
        ]);

        $this->actingAs($requester)
            ->patch(route('tickets.status.update', $ticket), [
                'status_id' => $resolvedStatus->id,
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'ticket_status_id' => $this->defaultStatus->id,
        ]);
    }

    public function test_requester_sees_resolution_actions_and_other_user_is_denied(): void
    {
        $requester = $this->createUserWithRole($this->userRole);
        $otherUser = $this->createUserWithRole($this->userRole);
        $resolvedStatus = TicketStatus::query()->create([
            'name' => 'Resolved',
            'slug' => 'resolved',
            'sort_order' => 2,
        ]);
        $ticket = $this->createTicket([
            'requester' => $requester,
            'status' => $resolvedStatus,
            'visibility' => Ticket::VISIBILITY_PUBLIC,
        ]);

        $this->actingAs($requester)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSeeText(__('tickets.show.resolution.heading'))
            ->assertSeeText(__('tickets.show.metadata.confirm_resolved'))
            ->assertSeeText(__('tickets.show.metadata.problem_persists'));

        $this->actingAs($otherUser)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertDontSeeText(__('tickets.show.metadata.confirm_resolved'))
            ->assertDontSeeText(__('tickets.show.metadata.problem_persists'));

        $this->patch(route('tickets.confirm-resolution', $ticket))
            ->assertForbidden();

        $this->patch(route('tickets.report-problem-persists', $ticket))
            ->assertForbidden();
    }

    public function test_requester_confirms_resolved_ticket_and_it_becomes_closed(): void
    {
        Carbon::setTestNow('2026-04-23 16:15:00');

        try {
            $requester = $this->createUserWithRole($this->userRole);
            $resolvedStatus = TicketStatus::query()->create([
                'name' => 'Resolved',
                'slug' => 'resolved',
                'sort_order' => 2,
            ]);
            $closedStatus = TicketStatus::query()->create([
                'name' => 'Closed',
                'slug' => 'closed',
                'sort_order' => 3,
                'is_closed' => true,
            ]);
            $ticket = $this->createTicket([
                'requester' => $requester,
                'status' => $resolvedStatus,
                'visibility' => Ticket::VISIBILITY_PUBLIC,
            ]);
            $ticket->forceFill([
                'resolved_at' => Carbon::now()->subHour(),
                'auto_close_at' => Carbon::now()->addDays(5),
                'closed_at' => null,
            ])->save();

            $this->actingAs($requester)
                ->patch(route('tickets.confirm-resolution', $ticket))
                ->assertRedirect(route('tickets.show', $ticket));

            $ticket->refresh();

            $this->assertSame($closedStatus->id, $ticket->ticket_status_id);
            $this->assertNotNull($ticket->resolved_at);
            $this->assertNull($ticket->auto_close_at);
            $this->assertTrue($ticket->closed_at?->equalTo(Carbon::now()) ?? false);
            $this->assertTrue($ticket->history()
                ->get()
                ->contains(fn (TicketHistory $entry) => ($entry->meta['action'] ?? null) === 'requester_confirm_resolution'));
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_requester_confirming_resolution_notifies_assignee_and_watchers_but_not_requester(): void
    {
        config()->set('helpdesk.notifications.mail.enabled', true);
        Notification::fake();
        $requester = $this->createUserWithRole($this->userRole);
        $assignee = $this->createUserWithRole($this->solverRole);
        $watcher = $this->createUserWithRole($this->userRole);
        $resolvedStatus = TicketStatus::query()->create([
            'name' => 'Resolved',
            'slug' => 'resolved',
            'sort_order' => 2,
        ]);
        TicketStatus::query()->create([
            'name' => 'Closed',
            'slug' => 'closed',
            'sort_order' => 3,
            'is_closed' => true,
        ]);
        $ticket = $this->createTicket([
            'requester' => $requester,
            'assignee' => $assignee,
            'status' => $resolvedStatus,
            'visibility' => Ticket::VISIBILITY_PUBLIC,
        ]);
        $ticket->watcherEntries()->create([
            'user_id' => $watcher->id,
            'is_manual' => true,
            'is_auto_participant' => false,
        ]);

        $this->actingAs($requester)
            ->patch(route('tickets.confirm-resolution', $ticket))
            ->assertRedirect(route('tickets.show', $ticket));

        foreach ([$assignee, $watcher] as $recipient) {
            Notification::assertSentTo(
                $recipient,
                TicketEventNotification::class,
                fn (TicketEventNotification $notification) => $notification->event === 'closed'
                    && (int) $notification->ticket->id === (int) $ticket->id
                    && str_contains(
                        $this->notificationHtml($notification, $recipient),
                        __('notifications.ticket.descriptions.closed_by_requester', [], $recipient->preferred_locale ?: app()->getLocale()),
                    ),
            );
        }

        Notification::assertNotSentTo($requester, TicketEventNotification::class);
    }

    public function test_manual_closed_status_notification_uses_general_closed_text(): void
    {
        config()->set('helpdesk.notifications.mail.enabled', true);
        Notification::fake();
        $requester = $this->createUserWithRole($this->userRole, ['preferred_locale' => 'en']);
        $solver = $this->createUserWithRole($this->solverRole);
        $closedStatus = TicketStatus::query()->create([
            'name' => 'Closed',
            'slug' => 'closed',
            'sort_order' => 3,
            'is_closed' => true,
        ]);
        $ticket = $this->createTicket([
            'requester' => $requester,
            'visibility' => Ticket::VISIBILITY_PUBLIC,
        ]);

        $this->actingAs($solver)
            ->patch(route('tickets.status.update', $ticket), [
                'status_id' => $closedStatus->id,
            ])
            ->assertRedirect(route('tickets.show', $ticket));

        Notification::assertSentTo(
            $requester,
            TicketEventNotification::class,
            fn (TicketEventNotification $notification) => $notification->event === 'closed'
                && str_contains(
                    $this->notificationHtml($notification, $requester),
                    __('notifications.ticket.descriptions.closed', [], 'en'),
                ),
        );
    }

    public function test_admin_can_confirm_resolved_ticket(): void
    {
        $requester = $this->createUserWithRole($this->userRole);
        $admin = $this->createUserWithRole($this->adminRole);
        $resolvedStatus = TicketStatus::query()->create([
            'name' => 'Resolved',
            'slug' => 'resolved',
            'sort_order' => 2,
        ]);
        $closedStatus = TicketStatus::query()->create([
            'name' => 'Closed',
            'slug' => 'closed',
            'sort_order' => 3,
            'is_closed' => true,
        ]);
        $ticket = $this->createTicket([
            'requester' => $requester,
            'status' => $resolvedStatus,
            'visibility' => Ticket::VISIBILITY_PRIVATE,
        ]);

        $this->actingAs($admin)
            ->patch(route('tickets.confirm-resolution', $ticket))
            ->assertRedirect(route('tickets.show', $ticket));

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'ticket_status_id' => $closedStatus->id,
        ]);
    }

    public function test_requester_reports_problem_persists_and_ticket_returns_to_in_progress_when_assigned(): void
    {
        $requester = $this->createUserWithRole($this->userRole);
        $assignee = $this->createUserWithRole($this->solverRole);
        $inProgressStatus = TicketStatus::query()->create([
            'name' => 'In Progress',
            'slug' => 'in_progress',
            'sort_order' => 2,
        ]);
        $resolvedStatus = TicketStatus::query()->create([
            'name' => 'Resolved',
            'slug' => 'resolved',
            'sort_order' => 3,
        ]);
        $ticket = $this->createTicket([
            'requester' => $requester,
            'assignee' => $assignee,
            'status' => $resolvedStatus,
            'visibility' => Ticket::VISIBILITY_PUBLIC,
        ]);
        $ticket->forceFill([
            'resolved_at' => now()->subHour(),
            'auto_close_at' => now()->addDays(5),
            'closed_at' => now()->subMinutes(5),
        ])->save();

        $this->actingAs($requester)
            ->patch(route('tickets.report-problem-persists', $ticket))
            ->assertRedirect(route('tickets.show', $ticket));

        $ticket->refresh();

        $this->assertSame($inProgressStatus->id, $ticket->ticket_status_id);
        $this->assertNull($ticket->resolved_at);
        $this->assertNull($ticket->auto_close_at);
        $this->assertNull($ticket->closed_at);
        $this->assertTrue($ticket->history()
            ->get()
            ->contains(fn (TicketHistory $entry) => ($entry->meta['action'] ?? null) === 'requester_report_problem_persists'));
    }

    public function test_requester_problem_persists_notifies_assignee_and_watchers_but_not_requester(): void
    {
        config()->set('helpdesk.notifications.mail.enabled', true);
        Notification::fake();
        $requester = $this->createUserWithRole($this->userRole);
        $assignee = $this->createUserWithRole($this->solverRole);
        $watcher = $this->createUserWithRole($this->userRole);
        TicketStatus::query()->create([
            'name' => 'In Progress',
            'slug' => 'in_progress',
            'sort_order' => 2,
        ]);
        $resolvedStatus = TicketStatus::query()->create([
            'name' => 'Resolved',
            'slug' => 'resolved',
            'sort_order' => 3,
        ]);
        $ticket = $this->createTicket([
            'requester' => $requester,
            'assignee' => $assignee,
            'status' => $resolvedStatus,
            'visibility' => Ticket::VISIBILITY_PUBLIC,
        ]);
        $ticket->watcherEntries()->create([
            'user_id' => $watcher->id,
            'is_manual' => true,
            'is_auto_participant' => false,
        ]);

        $this->actingAs($requester)
            ->patch(route('tickets.report-problem-persists', $ticket))
            ->assertRedirect(route('tickets.show', $ticket));

        foreach ([$assignee, $watcher] as $recipient) {
            Notification::assertSentTo(
                $recipient,
                TicketEventNotification::class,
                fn (TicketEventNotification $notification) => $notification->event === 'problem_persists'
                    && (int) $notification->ticket->id === (int) $ticket->id,
            );
        }

        Notification::assertNotSentTo($requester, TicketEventNotification::class);
    }

    public function test_requester_reports_problem_persists_and_unassigned_ticket_returns_to_new(): void
    {
        $requester = $this->createUserWithRole($this->userRole);
        $resolvedStatus = TicketStatus::query()->create([
            'name' => 'Resolved',
            'slug' => 'resolved',
            'sort_order' => 3,
        ]);
        $ticket = $this->createTicket([
            'requester' => $requester,
            'status' => $resolvedStatus,
            'visibility' => Ticket::VISIBILITY_PUBLIC,
        ]);
        $ticket->forceFill([
            'resolved_at' => now()->subHour(),
            'auto_close_at' => now()->addDays(5),
            'closed_at' => null,
        ])->save();

        $this->actingAs($requester)
            ->patch(route('tickets.report-problem-persists', $ticket))
            ->assertRedirect(route('tickets.show', $ticket));

        $ticket->refresh();

        $this->assertSame($this->defaultStatus->id, $ticket->ticket_status_id);
        $this->assertNull($ticket->resolved_at);
        $this->assertNull($ticket->auto_close_at);
        $this->assertNull($ticket->closed_at);
    }

    public function test_auto_close_command_closes_due_resolved_tickets_and_records_history(): void
    {
        Carbon::setTestNow('2026-04-30 10:00:00');
        Notification::fake();
        config(['helpdesk.notifications.mail.enabled' => true]);
        config(['helpdesk.workflow.resolved_auto_close_days' => 5]);

        try {
            $requester = $this->createUserWithRole($this->userRole, ['email' => 'requester@example.org', 'preferred_locale' => 'en']);
            $assignee = $this->createUserWithRole($this->solverRole, ['email' => 'solver@example.org', 'preferred_locale' => 'en']);
            $resolvedStatus = TicketStatus::query()->create([
                'name' => 'Resolved',
                'slug' => 'resolved',
                'sort_order' => 2,
            ]);
            $closedStatus = TicketStatus::query()->create([
                'name' => 'Closed',
                'slug' => 'closed',
                'sort_order' => 3,
                'is_closed' => true,
            ]);
            $ticket = $this->createTicket([
                'requester' => $requester,
                'assignee' => $assignee,
                'status' => $resolvedStatus,
                'visibility' => Ticket::VISIBILITY_PUBLIC,
            ]);
            $ticket->watcherEntries()->create([
                'user_id' => $assignee->id,
                'is_manual' => false,
                'is_auto_participant' => true,
            ]);
            $ticket->forceFill([
                'resolved_at' => Carbon::now()->subDays(5),
                'auto_close_at' => Carbon::now()->subMinute(),
                'closed_at' => null,
            ])->save();

            $this->artisan('helpdesk:close-resolved-tickets')
                ->assertExitCode(0);

            $ticket->refresh();

            $this->assertSame($closedStatus->id, $ticket->ticket_status_id);
            $this->assertNull($ticket->auto_close_at);
            $this->assertTrue($ticket->closed_at?->equalTo(Carbon::now()) ?? false);
            $this->assertTrue($ticket->history()
                ->get()
                ->contains(fn (TicketHistory $entry) => ($entry->meta['action'] ?? null) === 'auto_close_resolved'));

            foreach ([$requester, $assignee] as $recipient) {
                Notification::assertSentTo(
                    $recipient,
                    TicketEventNotification::class,
                    fn (TicketEventNotification $notification) => $notification->event === 'closed'
                        && str_contains(
                            $this->notificationHtml($notification, $recipient),
                            __('notifications.ticket.descriptions.closed_automatically', ['days' => 5], 'en'),
                        ),
                );
            }
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_auto_close_command_ignores_future_and_archived_resolved_tickets(): void
    {
        Carbon::setTestNow('2026-04-30 10:00:00');

        try {
            $requester = $this->createUserWithRole($this->userRole);
            $resolvedStatus = TicketStatus::query()->create([
                'name' => 'Resolved',
                'slug' => 'resolved',
                'sort_order' => 2,
            ]);
            TicketStatus::query()->create([
                'name' => 'Closed',
                'slug' => 'closed',
                'sort_order' => 3,
                'is_closed' => true,
            ]);
            $futureTicket = $this->createTicket([
                'requester' => $requester,
                'status' => $resolvedStatus,
                'subject' => 'Future auto close ticket',
            ]);
            $futureTicket->forceFill([
                'resolved_at' => Carbon::now()->subDay(),
                'auto_close_at' => Carbon::now()->addHour(),
            ])->save();
            $archivedTicket = $this->createTicket([
                'requester' => $requester,
                'status' => $resolvedStatus,
                'subject' => 'Archived auto close ticket',
            ]);
            $archivedTicket->forceFill([
                'resolved_at' => Carbon::now()->subDays(5),
                'auto_close_at' => Carbon::now()->subMinute(),
                'archived_at' => Carbon::now()->subDay(),
            ])->save();

            $this->artisan('helpdesk:close-resolved-tickets')
                ->assertExitCode(0);

            $this->assertDatabaseHas('tickets', [
                'id' => $futureTicket->id,
                'ticket_status_id' => $resolvedStatus->id,
            ]);
            $this->assertDatabaseHas('tickets', [
                'id' => $archivedTicket->id,
                'ticket_status_id' => $resolvedStatus->id,
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_inline_json_update_respects_current_page_locale(): void
    {
        $requester = $this->createUserWithRole($this->userRole);
        $solver = $this->createUserWithRole($this->solverRole);
        $waitingUserStatus = TicketStatus::query()->create([
            'name' => 'Waiting for User',
            'slug' => 'waiting_user',
            'sort_order' => 2,
        ]);
        $criticalPriority = TicketPriority::query()->create([
            'name' => 'Critical',
            'slug' => 'critical',
            'sort_order' => 2,
        ]);
        $ticket = $this->createTicket([
            'requester' => $requester,
            'visibility' => Ticket::VISIBILITY_INTERNAL,
        ]);

        $this->actingAs($solver);

        $this->withHeaders([
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
            'X-Helpdesk-Locale' => 'cs',
        ])->post(route('tickets.status.update', $ticket), [
            '_method' => 'PATCH',
            'status_id' => $waitingUserStatus->id,
            '_locale' => 'cs',
        ])
            ->assertOk()
            ->assertJsonPath('ticket.status.id', $waitingUserStatus->id)
            ->assertJsonPath('ticket.status.name', 'Čeká na uživatele');

        $this->withHeaders([
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
            'X-Helpdesk-Locale' => 'cs',
        ])->post(route('tickets.priority.update', $ticket), [
            '_method' => 'PATCH',
            'priority_id' => $criticalPriority->id,
            '_locale' => 'cs',
        ])
            ->assertOk()
            ->assertJsonPath('ticket.priority.id', $criticalPriority->id)
            ->assertJsonPath('ticket.priority.name', 'Kritická');
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

    public function test_ticket_list_search_matches_ticket_description(): void
    {
        $requester = $this->createUserWithRole($this->userRole);
        $matchingTicket = $this->createTicket([
            'requester' => $requester,
            'subject' => 'Printer issue',
            'description' => 'VPN token synchronization failed during remote access setup.',
            'visibility' => Ticket::VISIBILITY_PUBLIC,
        ]);
        $nonMatchingTicket = $this->createTicket([
            'requester' => $requester,
            'subject' => 'Network issue',
            'description' => 'Local switch replacement request.',
            'visibility' => Ticket::VISIBILITY_PUBLIC,
        ]);

        $this->actingAs($requester)
            ->get(route('tickets.index', ['search' => 'synchronization']))
            ->assertOk()
            ->assertSeeText($matchingTicket->subject)
            ->assertDontSeeText($nonMatchingTicket->subject);
    }

    public function test_new_tickets_use_yearly_ticket_number_sequence(): void
    {
        try {
            Carbon::setTestNow('2026-04-23 09:00:00');

            $requester = $this->createUserWithRole($this->userRole);

            $this->actingAs($requester)
                ->post(route('tickets.store'), [
                    'subject' => 'First generated number',
                    'description' => 'First generated description',
                    'priority_id' => $this->defaultPriority->id,
                    'category_id' => $this->defaultCategory->id,
                ])
                ->assertRedirect(route('tickets.index'));

            $this->actingAs($requester)
                ->post(route('tickets.store'), [
                    'subject' => 'Second generated number',
                    'description' => 'Second generated description',
                    'priority_id' => $this->defaultPriority->id,
                    'category_id' => $this->defaultCategory->id,
                ])
                ->assertRedirect(route('tickets.index'));

            $tickets = Ticket::query()->orderBy('id')->get();

            $this->assertSame('2026-001', $tickets[0]->ticket_number);
            $this->assertSame('2026-002', $tickets[1]->ticket_number);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_regular_user_creates_public_ticket_without_sensitive_option(): void
    {
        $requester = $this->createUserWithRole($this->userRole);

        $this->actingAs($requester)
            ->post(route('tickets.store'), [
                'subject' => 'Public create flow ticket',
                'description' => 'Ticket should stay public.',
                'priority_id' => $this->defaultPriority->id,
                'category_id' => $this->defaultCategory->id,
            ])
            ->assertRedirect(route('tickets.index'));

        $ticket = Ticket::query()->where('subject', 'Public create flow ticket')->firstOrFail();

        $this->assertSame(Ticket::VISIBILITY_PUBLIC, $ticket->visibility);
        $this->assertSame($requester->id, $ticket->requester_id);
    }

    public function test_regular_user_creates_internal_ticket_with_sensitive_option(): void
    {
        $requester = $this->createUserWithRole($this->userRole);

        $this->actingAs($requester)
            ->post(route('tickets.store'), [
                'subject' => 'Sensitive create flow ticket',
                'description' => 'Ticket should become internal.',
                'priority_id' => $this->defaultPriority->id,
                'category_id' => $this->defaultCategory->id,
                'is_sensitive' => '1',
            ])
            ->assertRedirect(route('tickets.index'));

        $ticket = Ticket::query()->where('subject', 'Sensitive create flow ticket')->firstOrFail();

        $this->assertSame(Ticket::VISIBILITY_INTERNAL, $ticket->visibility);
        $this->assertSame($requester->id, $ticket->requester_id);
    }

    public function test_regular_user_cannot_forge_private_visibility_when_creating_ticket(): void
    {
        $requester = $this->createUserWithRole($this->userRole);

        $this->actingAs($requester)
            ->post(route('tickets.store'), [
                'subject' => 'Forged private create flow ticket',
                'description' => 'Submitted visibility must be ignored on create.',
                'priority_id' => $this->defaultPriority->id,
                'category_id' => $this->defaultCategory->id,
                'visibility' => Ticket::VISIBILITY_PRIVATE,
            ])
            ->assertRedirect(route('tickets.index'));

        $ticket = Ticket::query()->where('subject', 'Forged private create flow ticket')->firstOrFail();

        $this->assertSame(Ticket::VISIBILITY_PUBLIC, $ticket->visibility);

        $this->actingAs($requester)
            ->post(route('tickets.store'), [
                'subject' => 'Forged private sensitive create flow ticket',
                'description' => 'Submitted private visibility must be ignored even when sensitive.',
                'priority_id' => $this->defaultPriority->id,
                'category_id' => $this->defaultCategory->id,
                'is_sensitive' => '1',
                'visibility' => Ticket::VISIBILITY_PRIVATE,
            ])
            ->assertRedirect(route('tickets.index'));

        $sensitiveTicket = Ticket::query()->where('subject', 'Forged private sensitive create flow ticket')->firstOrFail();

        $this->assertSame(Ticket::VISIBILITY_INTERNAL, $sensitiveTicket->visibility);
    }

    public function test_created_internal_ticket_is_visible_to_requester_and_solver_but_not_other_regular_user(): void
    {
        $requester = $this->createUserWithRole($this->userRole);
        $solver = $this->createUserWithRole($this->solverRole);
        $otherUser = $this->createUserWithRole($this->userRole);

        $this->actingAs($requester)
            ->post(route('tickets.store'), [
                'subject' => 'Created internal access ticket',
                'description' => 'Sensitive ticket visibility should follow internal rules.',
                'priority_id' => $this->defaultPriority->id,
                'category_id' => $this->defaultCategory->id,
                'is_sensitive' => '1',
            ])
            ->assertRedirect(route('tickets.index'));

        $ticket = Ticket::query()->where('subject', 'Created internal access ticket')->firstOrFail();

        foreach ([$requester, $solver] as $authorizedUser) {
            $this->actingAs($authorizedUser)
                ->get(route('tickets.index'))
                ->assertOk()
                ->assertSeeText($ticket->subject);

            $this->actingAs($authorizedUser)
                ->get(route('tickets.show', $ticket))
                ->assertOk()
                ->assertSeeText($ticket->subject);
        }

        $this->actingAs($otherUser)
            ->get(route('tickets.index'))
            ->assertOk()
            ->assertDontSeeText($ticket->subject);

        $this->actingAs($otherUser)
            ->get(route('tickets.show', $ticket))
            ->assertForbidden();
    }

    public function test_create_form_renders_sensitive_request_checkbox(): void
    {
        $requester = $this->createUserWithRole($this->userRole);

        $this->actingAs($requester)
            ->get(route('tickets.create'))
            ->assertOk()
            ->assertSee('name="is_sensitive"', false)
            ->assertSeeText(__('tickets.form.labels.sensitive'))
            ->assertSeeText(__('tickets.form.hints.sensitive'))
            ->assertDontSee('name="visibility"', false);
    }

    public function test_create_and_edit_forms_do_not_render_pinning_controls(): void
    {
        $requester = $this->createUserWithRole($this->userRole);
        $ticket = $this->createTicket([
            'requester' => $requester,
            'visibility' => Ticket::VISIBILITY_PUBLIC,
        ]);

        $this->actingAs($requester)
            ->get(route('tickets.create'))
            ->assertOk()
            ->assertDontSee('name="pinned"', false)
            ->assertDontSeeText(__('tickets.form.labels.pinned'));

        $this->actingAs($requester)
            ->get(route('tickets.edit', $ticket))
            ->assertOk()
            ->assertDontSee('name="pinned"', false)
            ->assertDontSeeText(__('tickets.form.labels.pinned'));
    }

    public function test_create_and_edit_flow_ignore_pinned_input(): void
    {
        $requester = $this->createUserWithRole($this->userRole);

        $this->actingAs($requester)
            ->post(route('tickets.store'), [
                'subject' => 'Ticket without form pinning',
                'description' => 'Pin value should be ignored on create.',
                'priority_id' => $this->defaultPriority->id,
                'category_id' => $this->defaultCategory->id,
                'pinned' => 1,
            ])
            ->assertRedirect(route('tickets.index'));

        $createdTicket = Ticket::query()->where('subject', 'Ticket without form pinning')->firstOrFail();

        $this->assertFalse((bool) $createdTicket->is_pinned);
        $this->assertNull($createdTicket->pinned_at);

        $editableTicket = $this->createTicket([
            'requester' => $requester,
            'visibility' => Ticket::VISIBILITY_PUBLIC,
        ]);

        $this->actingAs($requester)
            ->patch(route('tickets.update', $editableTicket), [
                'subject' => 'Updated without form pinning',
                'description' => 'Pin value should be ignored on edit.',
                'priority_id' => $this->defaultPriority->id,
                'category_id' => $this->defaultCategory->id,
                'pinned' => 1,
            ])
            ->assertRedirect(route('tickets.show', $editableTicket));

        $editableTicket->refresh();

        $this->assertFalse((bool) $editableTicket->is_pinned);
        $this->assertNull($editableTicket->pinned_at);
    }

    public function test_solver_and_admin_see_expected_resolution_field_in_edit_form(): void
    {
        $requester = $this->createUserWithRole($this->userRole);
        $solver = $this->createUserWithRole($this->solverRole);
        $admin = $this->createUserWithRole($this->adminRole);
        $ticket = $this->createTicket([
            'requester' => $requester,
            'visibility' => Ticket::VISIBILITY_INTERNAL,
        ]);

        foreach ([$solver, $admin] as $actor) {
            $this->actingAs($actor)
                ->get(route('tickets.edit', $ticket))
                ->assertOk()
                ->assertSee('name="expected_resolution_at"', false);
        }
    }

    public function test_regular_user_does_not_see_expected_resolution_field_in_edit_form(): void
    {
        $requester = $this->createUserWithRole($this->userRole);
        $ticket = $this->createTicket([
            'requester' => $requester,
            'visibility' => Ticket::VISIBILITY_PUBLIC,
        ]);

        $this->actingAs($requester)
            ->get(route('tickets.edit', $ticket))
            ->assertOk()
            ->assertDontSee('name="expected_resolution_at"', false);
    }

    public function test_expected_resolution_is_visible_on_ticket_detail(): void
    {
        $requester = $this->createUserWithRole($this->userRole);
        $ticket = $this->createTicket([
            'requester' => $requester,
            'visibility' => Ticket::VISIBILITY_PUBLIC,
        ]);
        $ticket->forceFill([
            'expected_resolution_at' => Carbon::parse('2026-05-04 13:30:00'),
        ])->save();

        $this->actingAs($requester)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSeeText(__('tickets.show.content.expected_resolution_at'));
    }

    public function test_regular_user_cannot_update_expected_resolution_from_edit_flow(): void
    {
        $requester = $this->createUserWithRole($this->userRole);
        $ticket = $this->createTicket([
            'requester' => $requester,
            'visibility' => Ticket::VISIBILITY_PUBLIC,
        ]);

        $this->actingAs($requester)
            ->patch(route('tickets.update', $ticket), [
                'subject' => $ticket->subject,
                'description' => $ticket->description,
                'priority_id' => $this->defaultPriority->id,
                'category_id' => $this->defaultCategory->id,
                'expected_resolution_at' => '2026-05-04T13:30',
                'expected_resolution_source' => TicketWorkflowAutomationService::EXPECTED_RESOLUTION_SOURCE_MANUAL,
            ])
            ->assertRedirect(route('tickets.show', $ticket));

        $ticket->refresh();

        $this->assertNull($ticket->expected_resolution_at);
        $this->assertNull($ticket->expected_resolution_source);
    }

    public function test_solver_can_update_expected_resolution_and_history_records_it(): void
    {
        config()->set('helpdesk.notifications.mail.enabled', true);
        Notification::fake();

        $requester = $this->createUserWithRole($this->userRole);
        $solver = $this->createUserWithRole($this->solverRole);
        $assignee = $this->createUserWithRole($this->solverRole);
        $watcher = $this->createUserWithRole($this->userRole);
        $ticket = $this->createTicket([
            'requester' => $requester,
            'assignee' => $assignee,
            'visibility' => Ticket::VISIBILITY_PUBLIC,
        ]);
        $ticket->watcherEntries()->create([
            'user_id' => $watcher->id,
            'is_manual' => true,
            'is_auto_participant' => false,
        ]);

        $this->actingAs($solver)
            ->patch(route('tickets.update', $ticket), [
                'subject' => $ticket->subject,
                'description' => $ticket->description,
                'priority_id' => $this->defaultPriority->id,
                'category_id' => $this->defaultCategory->id,
                'expected_resolution_at' => '2026-05-04T13:30',
            ])
            ->assertRedirect(route('tickets.show', $ticket));

        $ticket->refresh();

        $this->assertTrue($ticket->expected_resolution_at?->equalTo(Carbon::parse('2026-05-04 13:30:00')) ?? false);
        $this->assertSame(TicketWorkflowAutomationService::EXPECTED_RESOLUTION_SOURCE_MANUAL, $ticket->expected_resolution_source);

        $updateEntry = TicketHistory::query()
            ->where('ticket_id', $ticket->id)
            ->where('event', TicketHistory::EVENT_UPDATED)
            ->latest('id')
            ->first();

        $this->assertNotNull($updateEntry);
        $this->assertContains('expected_resolution_at', $updateEntry->meta['changed_fields']);
        $this->assertContains('expected_resolution_source', $updateEntry->meta['changed_fields']);
        Notification::assertSentTo(
            $requester,
            TicketEventNotification::class,
            fn (TicketEventNotification $notification) => $notification->event === 'expected_resolution_changed'
        );
        Notification::assertNotSentTo($assignee, TicketEventNotification::class);
        Notification::assertNotSentTo($watcher, TicketEventNotification::class);
    }

    public function test_expected_resolution_change_does_not_notify_actor_when_actor_is_requester(): void
    {
        config()->set('helpdesk.notifications.mail.enabled', true);
        Notification::fake();

        $adminRequester = $this->createUserWithRole($this->adminRole);
        $assignee = $this->createUserWithRole($this->solverRole);
        $ticket = $this->createTicket([
            'requester' => $adminRequester,
            'assignee' => $assignee,
            'visibility' => Ticket::VISIBILITY_PRIVATE,
        ]);

        $this->actingAs($adminRequester)
            ->patch(route('tickets.update', $ticket), [
                'subject' => $ticket->subject,
                'description' => $ticket->description,
                'priority_id' => $this->defaultPriority->id,
                'category_id' => $this->defaultCategory->id,
                'expected_resolution_at' => '2026-05-04T13:30',
                'visibility' => Ticket::VISIBILITY_PRIVATE,
            ])
            ->assertRedirect(route('tickets.show', $ticket));

        Notification::assertNothingSent();
    }

    public function test_assigning_ticket_without_expected_resolution_sets_auto_deadline_from_priority(): void
    {
        $this->configureExpectedResolutionDays();
        Carbon::setTestNow(Carbon::parse('2026-05-05 09:00:00'));

        $requester = $this->createUserWithRole($this->userRole);
        $solver = $this->createUserWithRole($this->solverRole);
        $assignee = $this->createUserWithRole($this->solverRole);
        $highPriority = TicketPriority::query()->create([
            'name' => 'High',
            'slug' => 'high',
            'sort_order' => 2,
        ]);
        TicketStatus::query()->create([
            'name' => 'Assigned',
            'slug' => 'assigned',
            'sort_order' => 2,
        ]);
        $ticket = $this->createTicket([
            'requester' => $requester,
            'priority' => $highPriority,
            'visibility' => Ticket::VISIBILITY_INTERNAL,
        ]);

        $this->actingAs($solver)
            ->patch(route('tickets.assignee.update', $ticket), [
                'assignee_id' => $assignee->id,
            ])
            ->assertRedirect(route('tickets.show', $ticket));

        $ticket->refresh();

        $this->assertTrue($ticket->expected_resolution_at?->equalTo(Carbon::parse('2026-05-07 09:00:00')) ?? false);
        $this->assertSame(TicketWorkflowAutomationService::EXPECTED_RESOLUTION_SOURCE_AUTO, $ticket->expected_resolution_source);

        $history = TicketHistory::query()
            ->where('ticket_id', $ticket->id)
            ->where('event', TicketHistory::EVENT_UPDATED)
            ->latest('id')
            ->first();

        $this->assertNotNull($history);
        $this->assertContains('expected_resolution_at', $history->meta['changed_fields']);
        $this->assertContains('expected_resolution_source', $history->meta['changed_fields']);

        Carbon::setTestNow();
    }

    public function test_assignee_change_does_not_recalculate_existing_expected_resolution(): void
    {
        $this->configureExpectedResolutionDays();
        Carbon::setTestNow(Carbon::parse('2026-05-05 09:00:00'));

        $requester = $this->createUserWithRole($this->userRole);
        $solver = $this->createUserWithRole($this->solverRole);
        $oldAssignee = $this->createUserWithRole($this->solverRole);
        $newAssignee = $this->createUserWithRole($this->solverRole);
        $assignedStatus = TicketStatus::query()->create([
            'name' => 'Assigned',
            'slug' => 'assigned',
            'sort_order' => 2,
        ]);
        $existingDeadline = Carbon::parse('2026-05-06 12:00:00');
        $ticket = $this->createTicket([
            'requester' => $requester,
            'assignee' => $oldAssignee,
            'status' => $assignedStatus,
            'visibility' => Ticket::VISIBILITY_INTERNAL,
            'expected_resolution_at' => $existingDeadline,
            'expected_resolution_source' => TicketWorkflowAutomationService::EXPECTED_RESOLUTION_SOURCE_AUTO,
        ]);

        $this->actingAs($solver)
            ->patch(route('tickets.assignee.update', $ticket), [
                'assignee_id' => $newAssignee->id,
            ])
            ->assertRedirect(route('tickets.show', $ticket));

        $ticket->refresh();

        $this->assertSame($newAssignee->id, $ticket->assignee_id);
        $this->assertTrue($ticket->expected_resolution_at?->equalTo($existingDeadline) ?? false);
        $this->assertSame(TicketWorkflowAutomationService::EXPECTED_RESOLUTION_SOURCE_AUTO, $ticket->expected_resolution_source);

        Carbon::setTestNow();
    }

    public function test_removing_assignee_does_not_clear_expected_resolution(): void
    {
        $requester = $this->createUserWithRole($this->userRole);
        $solver = $this->createUserWithRole($this->solverRole);
        $assignee = $this->createUserWithRole($this->solverRole);
        $assignedStatus = TicketStatus::query()->create([
            'name' => 'Assigned',
            'slug' => 'assigned',
            'sort_order' => 2,
        ]);
        $deadline = Carbon::parse('2026-05-08 12:00:00');
        $ticket = $this->createTicket([
            'requester' => $requester,
            'assignee' => $assignee,
            'status' => $assignedStatus,
            'visibility' => Ticket::VISIBILITY_INTERNAL,
            'expected_resolution_at' => $deadline,
            'expected_resolution_source' => TicketWorkflowAutomationService::EXPECTED_RESOLUTION_SOURCE_MANUAL,
        ]);

        $this->actingAs($solver)
            ->patch(route('tickets.assignee.update', $ticket), [
                'assignee_id' => '',
            ])
            ->assertRedirect(route('tickets.show', $ticket));

        $ticket->refresh();

        $this->assertNull($ticket->assignee_id);
        $this->assertTrue($ticket->expected_resolution_at?->equalTo($deadline) ?? false);
        $this->assertSame(TicketWorkflowAutomationService::EXPECTED_RESOLUTION_SOURCE_MANUAL, $ticket->expected_resolution_source);
    }

    public function test_priority_change_without_deadline_sets_auto_expected_resolution(): void
    {
        $this->configureExpectedResolutionDays();
        Carbon::setTestNow(Carbon::parse('2026-05-05 10:00:00'));

        $requester = $this->createUserWithRole($this->userRole);
        $solver = $this->createUserWithRole($this->solverRole);
        $assignee = $this->createUserWithRole($this->solverRole);
        $highPriority = TicketPriority::query()->create([
            'name' => 'High',
            'slug' => 'high',
            'sort_order' => 2,
        ]);
        $ticket = $this->createTicket([
            'requester' => $requester,
            'assignee' => $assignee,
            'visibility' => Ticket::VISIBILITY_INTERNAL,
        ]);

        $this->actingAs($solver)
            ->patch(route('tickets.priority.update', $ticket), [
                'priority_id' => $highPriority->id,
            ])
            ->assertRedirect(route('tickets.show', $ticket));

        $ticket->refresh();

        $this->assertSame($highPriority->id, $ticket->ticket_priority_id);
        $this->assertTrue($ticket->expected_resolution_at?->equalTo(Carbon::parse('2026-05-07 10:00:00')) ?? false);
        $this->assertSame(TicketWorkflowAutomationService::EXPECTED_RESOLUTION_SOURCE_AUTO, $ticket->expected_resolution_source);

        Carbon::setTestNow();
    }

    public function test_priority_change_recalculates_auto_deadline_but_not_manual_deadline(): void
    {
        $this->configureExpectedResolutionDays();
        Carbon::setTestNow(Carbon::parse('2026-05-05 11:00:00'));

        $requester = $this->createUserWithRole($this->userRole);
        $solver = $this->createUserWithRole($this->solverRole);
        $assignee = $this->createUserWithRole($this->solverRole);
        $criticalPriority = TicketPriority::query()->create([
            'name' => 'Critical',
            'slug' => 'critical',
            'sort_order' => 2,
        ]);
        $manualDeadline = Carbon::parse('2026-05-20 12:00:00');
        $autoTicket = $this->createTicket([
            'requester' => $requester,
            'assignee' => $assignee,
            'visibility' => Ticket::VISIBILITY_INTERNAL,
            'expected_resolution_at' => Carbon::parse('2026-05-10 12:00:00'),
            'expected_resolution_source' => TicketWorkflowAutomationService::EXPECTED_RESOLUTION_SOURCE_AUTO,
        ]);
        $manualTicket = $this->createTicket([
            'requester' => $requester,
            'assignee' => $assignee,
            'visibility' => Ticket::VISIBILITY_INTERNAL,
            'expected_resolution_at' => $manualDeadline,
            'expected_resolution_source' => TicketWorkflowAutomationService::EXPECTED_RESOLUTION_SOURCE_MANUAL,
        ]);

        $this->actingAs($solver)
            ->patch(route('tickets.priority.update', $autoTicket), [
                'priority_id' => $criticalPriority->id,
            ])
            ->assertRedirect(route('tickets.show', $autoTicket));

        $this->actingAs($solver)
            ->patch(route('tickets.priority.update', $manualTicket), [
                'priority_id' => $criticalPriority->id,
            ])
            ->assertRedirect(route('tickets.show', $manualTicket));

        $autoTicket->refresh();
        $manualTicket->refresh();

        $this->assertTrue($autoTicket->expected_resolution_at?->equalTo(Carbon::parse('2026-05-06 11:00:00')) ?? false);
        $this->assertSame(TicketWorkflowAutomationService::EXPECTED_RESOLUTION_SOURCE_AUTO, $autoTicket->expected_resolution_source);
        $this->assertTrue($manualTicket->expected_resolution_at?->equalTo($manualDeadline) ?? false);
        $this->assertSame(TicketWorkflowAutomationService::EXPECTED_RESOLUTION_SOURCE_MANUAL, $manualTicket->expected_resolution_source);

        Carbon::setTestNow();
    }

    public function test_priority_update_recalculating_auto_expected_resolution_does_not_notify_requester(): void
    {
        config()->set('helpdesk.notifications.mail.enabled', true);
        $this->configureExpectedResolutionDays();
        Notification::fake();
        Carbon::setTestNow(Carbon::parse('2026-05-05 11:00:00'));

        $requester = $this->createUserWithRole($this->userRole);
        $solver = $this->createUserWithRole($this->solverRole);
        $assignee = $this->createUserWithRole($this->solverRole);
        $watcher = $this->createUserWithRole($this->userRole);
        $criticalPriority = TicketPriority::query()->create([
            'name' => 'Critical',
            'slug' => 'critical',
            'sort_order' => 2,
        ]);
        $ticket = $this->createTicket([
            'requester' => $requester,
            'assignee' => $assignee,
            'visibility' => Ticket::VISIBILITY_PUBLIC,
            'expected_resolution_at' => Carbon::parse('2026-05-10 12:00:00'),
            'expected_resolution_source' => TicketWorkflowAutomationService::EXPECTED_RESOLUTION_SOURCE_AUTO,
        ]);
        $ticket->watcherEntries()->create([
            'user_id' => $watcher->id,
            'is_manual' => true,
            'is_auto_participant' => false,
        ]);

        $this->actingAs($solver)
            ->patch(route('tickets.priority.update', $ticket), [
                'priority_id' => $criticalPriority->id,
            ])
            ->assertRedirect(route('tickets.show', $ticket));

        $ticket->refresh();

        $this->assertTrue($ticket->expected_resolution_at?->equalTo(Carbon::parse('2026-05-06 11:00:00')) ?? false);
        Notification::assertNotSentTo(
            $requester,
            TicketEventNotification::class,
            fn (TicketEventNotification $notification) => $notification->event === 'expected_resolution_changed',
        );
        Notification::assertNotSentTo($assignee, TicketEventNotification::class);
        Notification::assertNotSentTo($watcher, TicketEventNotification::class);

        Carbon::setTestNow();
    }

    public function test_priority_update_with_manual_expected_resolution_does_not_send_expected_resolution_notification(): void
    {
        config()->set('helpdesk.notifications.mail.enabled', true);
        $this->configureExpectedResolutionDays();
        Notification::fake();
        Carbon::setTestNow(Carbon::parse('2026-05-05 11:00:00'));

        $requester = $this->createUserWithRole($this->userRole);
        $solver = $this->createUserWithRole($this->solverRole);
        $assignee = $this->createUserWithRole($this->solverRole);
        $criticalPriority = TicketPriority::query()->create([
            'name' => 'Critical',
            'slug' => 'critical',
            'sort_order' => 2,
        ]);
        $manualDeadline = Carbon::parse('2026-05-20 12:00:00');
        $ticket = $this->createTicket([
            'requester' => $requester,
            'assignee' => $assignee,
            'visibility' => Ticket::VISIBILITY_PUBLIC,
            'expected_resolution_at' => $manualDeadline,
            'expected_resolution_source' => TicketWorkflowAutomationService::EXPECTED_RESOLUTION_SOURCE_MANUAL,
        ]);

        $this->actingAs($solver)
            ->patch(route('tickets.priority.update', $ticket), [
                'priority_id' => $criticalPriority->id,
            ])
            ->assertRedirect(route('tickets.show', $ticket));

        $ticket->refresh();

        $this->assertTrue($ticket->expected_resolution_at?->equalTo($manualDeadline) ?? false);
        Notification::assertNothingSent();

        Carbon::setTestNow();
    }

    public function test_priority_change_without_assignee_does_not_set_expected_resolution(): void
    {
        $this->configureExpectedResolutionDays();
        $solver = $this->createUserWithRole($this->solverRole);
        $criticalPriority = TicketPriority::query()->create([
            'name' => 'Critical',
            'slug' => 'critical',
            'sort_order' => 2,
        ]);
        $ticket = $this->createTicket([
            'visibility' => Ticket::VISIBILITY_INTERNAL,
        ]);

        $this->actingAs($solver)
            ->patch(route('tickets.priority.update', $ticket), [
                'priority_id' => $criticalPriority->id,
            ])
            ->assertRedirect(route('tickets.show', $ticket));

        $ticket->refresh();

        $this->assertNull($ticket->expected_resolution_at);
        $this->assertNull($ticket->expected_resolution_source);
    }

    public function test_auto_expected_resolution_updates_do_not_send_email_notifications(): void
    {
        config()->set('helpdesk.notifications.mail.enabled', true);
        $this->configureExpectedResolutionDays();
        Notification::fake();
        Carbon::setTestNow(Carbon::parse('2026-05-05 12:00:00'));

        $requester = $this->createUserWithRole($this->userRole);
        $solver = $this->createUserWithRole($this->solverRole);
        $assignee = $this->createUserWithRole($this->solverRole);
        TicketStatus::query()->create([
            'name' => 'Assigned',
            'slug' => 'assigned',
            'sort_order' => 2,
        ]);
        $ticket = $this->createTicket([
            'requester' => $requester,
            'visibility' => Ticket::VISIBILITY_INTERNAL,
        ]);

        $this->actingAs($solver)
            ->patch(route('tickets.assignee.update', $ticket), [
                'assignee_id' => $assignee->id,
            ])
            ->assertRedirect(route('tickets.show', $ticket));

        Notification::assertNotSentTo(
            $requester,
            TicketEventNotification::class,
            fn (TicketEventNotification $notification) => $notification->event === 'expected_resolution_changed'
        );
        Notification::assertSentTo(
            $assignee,
            TicketEventNotification::class,
            fn (TicketEventNotification $notification) => $notification->event === 'assignee_changed'
        );
        Notification::assertSentTo(
            $requester,
            TicketEventNotification::class,
            fn (TicketEventNotification $notification) => $notification->event === 'assignee_changed'
        );

        Carbon::setTestNow();
    }

    public function test_ticket_index_due_filter_can_show_assigned_open_tickets_without_expected_resolution(): void
    {
        $solver = $this->createUserWithRole($this->solverRole);
        $assignedStatus = TicketStatus::query()->create([
            'name' => 'Assigned',
            'slug' => 'assigned',
            'sort_order' => 2,
        ]);
        $missingDeadlineTicket = $this->createTicket([
            'assignee' => $solver,
            'status' => $assignedStatus,
            'visibility' => Ticket::VISIBILITY_INTERNAL,
            'subject' => 'Assigned missing expected resolution',
        ]);
        $withDeadlineTicket = $this->createTicket([
            'assignee' => $solver,
            'status' => $assignedStatus,
            'visibility' => Ticket::VISIBILITY_INTERNAL,
            'subject' => 'Assigned with expected resolution',
            'expected_resolution_at' => Carbon::parse('2026-05-10 12:00:00'),
            'expected_resolution_source' => TicketWorkflowAutomationService::EXPECTED_RESOLUTION_SOURCE_MANUAL,
        ]);

        $this->actingAs($solver)
            ->get(route('tickets.index', [
                'scope' => 'open',
                'relation' => 'assigned',
                'due' => 'missing_expected_resolution',
            ]))
            ->assertOk()
            ->assertSeeText($missingDeadlineTicket->subject)
            ->assertDontSeeText($withDeadlineTicket->subject);
    }

    public function test_ticket_list_uses_czech_translations_for_system_labels_and_values(): void
    {
        $requester = $this->createUserWithRole($this->userRole);
        $resolvedStatus = TicketStatus::query()->create([
            'name' => 'Resolved',
            'slug' => 'resolved',
            'sort_order' => 2,
            'is_closed' => true,
        ]);
        $criticalPriority = TicketPriority::query()->create([
            'name' => 'Critical',
            'slug' => 'critical',
            'sort_order' => 2,
        ]);
        $ticket = $this->createTicket([
            'requester' => $requester,
            'status' => $resolvedStatus,
            'priority' => $criticalPriority,
            'visibility' => Ticket::VISIBILITY_INTERNAL,
            'subject' => 'Prekladovy ticket',
        ]);

        config()->set('app.locale', 'cs');
        app()->setLocale('cs');

        $this->actingAs($requester);

        $this->withHeaders([
            'Accept-Language' => 'cs-CZ,cs;q=0.9,en;q=0.8',
        ])->get(route('tickets.index'))
            ->assertOk()
            ->assertSeeText('Seznam ticketů')
            ->assertSeeText('Vyřešeno')
            ->assertSeeText('Kritická')
            ->assertSeeText('Interní')
            ->assertDontSeeText('Resolved')
            ->assertDontSeeText('Critical');
    }

    public function test_ticket_list_uses_english_translations_for_system_labels_and_values(): void
    {
        $requester = $this->createUserWithRole($this->userRole);
        $resolvedStatus = TicketStatus::query()->create([
            'name' => 'Vyřešeno',
            'slug' => 'resolved',
            'sort_order' => 2,
            'is_closed' => true,
        ]);
        $criticalPriority = TicketPriority::query()->create([
            'name' => 'Kritická',
            'slug' => 'critical',
            'sort_order' => 2,
        ]);
        $ticket = $this->createTicket([
            'requester' => $requester,
            'status' => $resolvedStatus,
            'priority' => $criticalPriority,
            'visibility' => Ticket::VISIBILITY_INTERNAL,
            'subject' => 'Localized ticket',
        ]);

        config()->set('app.locale', 'en');
        app()->setLocale('en');

        $this->actingAs($requester);

        $this->withHeaders([
            'Accept-Language' => 'en-US,en;q=0.9,cs;q=0.8',
        ])->get(route('tickets.index'))
            ->assertOk()
            ->assertSeeText('Tickets')
            ->assertSeeText('Resolved')
            ->assertSeeText('Critical')
            ->assertSeeText('Internal')
            ->assertSeeText('Search')
            ->assertDontSeeText('Vyřešeno')
            ->assertDontSeeText('Kritická');
    }

    public function test_ticket_views_use_ldap_display_names_but_top_bar_uses_login(): void
    {
        $viewer = $this->createUserWithRole($this->adminRole, [
            'name' => 'viewer.login',
            'username' => 'viewer.login',
            'display_name' => 'Viewer Full Name',
        ]);
        $requester = $this->createUserWithRole($this->userRole, [
            'name' => 'requester.login',
            'username' => 'requester.login',
            'display_name' => 'Requester Full Name',
        ]);
        $assignee = $this->createUserWithRole($this->solverRole, [
            'name' => 'solver.login',
            'username' => 'solver.login',
            'display_name' => 'Solver Full Name',
        ]);
        $ticket = $this->createTicket([
            'requester' => $requester,
            'assignee' => $assignee,
            'visibility' => Ticket::VISIBILITY_PUBLIC,
            'subject' => 'Display name ticket',
        ]);
        $ticket->comments()->create([
            'user_id' => $requester->id,
            'visibility' => 'public',
            'body' => 'Comment with display name author.',
        ]);

        $this->actingAs($viewer)
            ->get(route('tickets.index'))
            ->assertOk()
            ->assertSeeText('viewer.login')
            ->assertDontSeeText('Viewer Full Name')
            ->assertSeeText('Solver Full Name')
            ->assertDontSeeText('solver.login');

        $this->actingAs($viewer)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSeeText('viewer.login')
            ->assertSeeText('Requester Full Name')
            ->assertSeeText('Solver Full Name')
            ->assertDontSeeText('requester.login')
            ->assertDontSeeText('solver.login');
    }

    public function test_solver_can_manage_announcements(): void
    {
        $solver = $this->createUserWithRole($this->solverRole);

        $this->actingAs($solver);

        $this->get(route('announcements.index'))
            ->assertOk();

        $this->post(route('announcements.store'), [
            'title' => 'Solver announcement',
            'body' => 'Announcement created by solver.',
            'type' => Announcement::TYPE_INFO,
            'is_active' => '1',
        ])->assertRedirect(route('announcements.index'));

        $this->assertDatabaseHas('announcements', [
            'title' => 'Solver announcement',
            'author_id' => $solver->id,
            'type' => Announcement::TYPE_INFO,
            'visibility' => 'public',
        ]);
    }

    public function test_announcements_support_sanitized_html_body(): void
    {
        $solver = $this->createUserWithRole($this->solverRole);

        $this->actingAs($solver);

        $this->post(route('announcements.store'), [
            'title' => 'HTML announcement',
            'body' => 'Read <a href="https://example.org/status" onclick="alert(1)" target="_blank">status page</a><script>alert(1)</script>',
            'type' => Announcement::TYPE_INFO,
            'is_active' => '1',
        ])->assertRedirect(route('announcements.index'));

        $announcement = Announcement::query()
            ->where('title', 'HTML announcement')
            ->firstOrFail();

        $this->assertStringContainsString('<a href="https://example.org/status" target="_blank" rel="noopener noreferrer">status page</a>', $announcement->body);
        $this->assertStringNotContainsString('onclick', $announcement->body);
        $this->assertStringNotContainsString('<script>', $announcement->body);

        $this->get(route('announcements.index'))
            ->assertOk()
            ->assertSee('<a href="https://example.org/status" target="_blank" rel="noopener noreferrer">status page</a>', false)
            ->assertDontSee('onclick', false)
            ->assertDontSee('alert(1)', false);
    }

    public function test_regular_user_cannot_manage_announcements(): void
    {
        $user = $this->createUserWithRole($this->userRole);
        $announcement = Announcement::query()->create([
            'author_id' => $user->id,
            'title' => 'Existing announcement',
            'body' => 'Existing body.',
            'type' => Announcement::TYPE_INFO,
            'visibility' => 'public',
            'is_active' => true,
        ]);

        $this->actingAs($user);

        $this->get(route('announcements.index'))
            ->assertForbidden();

        $this->post(route('announcements.store'), [
            'title' => 'User announcement',
            'body' => 'User should not create this.',
            'type' => Announcement::TYPE_INFO,
            'is_active' => '1',
        ])->assertForbidden();

        $this->patch(route('announcements.update', $announcement), [
            'title' => 'Updated by user',
            'body' => 'User should not update this.',
            'type' => Announcement::TYPE_WARNING,
            'is_active' => '1',
        ])->assertForbidden();

        $this->delete(route('announcements.destroy', $announcement))
            ->assertForbidden();

        $this->assertDatabaseHas('announcements', [
            'id' => $announcement->id,
            'title' => 'Existing announcement',
        ]);
        $this->assertDatabaseMissing('announcements', [
            'title' => 'User announcement',
        ]);
    }

    private function createUserWithRole(Role $role, array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
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
            'expected_resolution_at' => $overrides['expected_resolution_at'] ?? null,
            'expected_resolution_source' => $overrides['expected_resolution_source'] ?? null,
        ]);
    }

    private function configureExpectedResolutionDays(): void
    {
        config()->set('helpdesk.workflow.expected_resolution_days.low', 10);
        config()->set('helpdesk.workflow.expected_resolution_days.normal', 5);
        config()->set('helpdesk.workflow.expected_resolution_days.high', 2);
        config()->set('helpdesk.workflow.expected_resolution_days.critical', 1);
    }

    private function notificationHtml(TicketEventNotification $notification, User $recipient): string
    {
        return (string) $notification->toMail($recipient)->render();
    }
}
