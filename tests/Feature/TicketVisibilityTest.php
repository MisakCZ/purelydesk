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
            ->assertSee('<details class="list-inline-menu" data-ticket-inline-menu>', false)
            ->assertSee('data-ticket-field="status"', false)
            ->assertSee('data-ticket-field="priority"', false);
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
        $this->assertSame(['status', 'assignee'], $updateEntry->meta['changed_fields']);
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
        } finally {
            Carbon::setTestNow();
        }
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
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_requester_reports_problem_persists_and_ticket_returns_to_assigned(): void
    {
        $requester = $this->createUserWithRole($this->userRole);
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

        $this->assertSame($assignedStatus->id, $ticket->ticket_status_id);
        $this->assertNull($ticket->resolved_at);
        $this->assertNull($ticket->auto_close_at);
        $this->assertNull($ticket->closed_at);
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
            ])
            ->assertRedirect(route('tickets.show', $ticket));

        $ticket->refresh();

        $this->assertNull($ticket->expected_resolution_at);
    }

    public function test_solver_can_update_expected_resolution_and_history_records_it(): void
    {
        $requester = $this->createUserWithRole($this->userRole);
        $solver = $this->createUserWithRole($this->solverRole);
        $ticket = $this->createTicket([
            'requester' => $requester,
            'visibility' => Ticket::VISIBILITY_INTERNAL,
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

        $updateEntry = TicketHistory::query()
            ->where('ticket_id', $ticket->id)
            ->where('event', TicketHistory::EVENT_UPDATED)
            ->latest('id')
            ->first();

        $this->assertNotNull($updateEntry);
        $this->assertContains('expected_resolution_at', $updateEntry->meta['changed_fields']);
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
            ->assertSeeText('Search subject')
            ->assertDontSeeText('Vyřešeno')
            ->assertDontSeeText('Kritická');
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
