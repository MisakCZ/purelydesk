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
