<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketNotificationBatch;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\User;
use App\Notifications\TicketEventNotification;
use App\Notifications\TicketNotificationBatchNotification;
use App\Services\InboundEmailReplyService;
use App\Services\TicketNotificationBatchSender;
use App\Services\TicketNotificationService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Notifications\Dispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class TicketNotificationBatchTest extends TestCase
{
    use RefreshDatabase;

    private Role $userRole;

    private Role $solverRole;

    private TicketStatus $assignedStatus;

    private TicketStatus $inProgressStatus;

    private TicketStatus $waitingUserStatus;

    private TicketStatus $resolvedStatus;

    private TicketStatus $closedStatus;

    private TicketPriority $priority;

    private TicketCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRole = $this->createRole('User', Role::SLUG_USER);
        $this->solverRole = $this->createRole('Solver', Role::SLUG_SOLVER);
        $this->assignedStatus = $this->createStatus('Assigned', 'assigned');
        $this->inProgressStatus = $this->createStatus('In progress', 'in_progress');
        $this->waitingUserStatus = $this->createStatus('Waiting for user', 'waiting_user');
        $this->resolvedStatus = $this->createStatus('Resolved', 'resolved');
        $this->closedStatus = $this->createStatus('Closed', 'closed', true);
        $this->priority = TicketPriority::query()->create([
            'name' => 'Normal',
            'slug' => 'normal',
            'sort_order' => 1,
            'is_default' => true,
        ]);
        $this->category = TicketCategory::query()->create([
            'name' => 'General',
            'slug' => 'general',
            'is_active' => true,
        ]);

        config()->set('helpdesk.notifications.mail.enabled', true);
        config()->set('helpdesk.notifications.mail.batch.enabled', true);
        config()->set('helpdesk.notifications.mail.batch.quiet_minutes', 10);
        config()->set('helpdesk.notifications.mail.batch.max_minutes', 30);
        config()->set('helpdesk.notifications.mail.notify_solvers_on_new_tickets', false);
        config()->set('helpdesk.notifications.mail.notify_admins_on_new_tickets', false);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_solver_changes_are_combined_and_waiting_user_flushes_the_batch(): void
    {
        Notification::fake();
        CarbonImmutable::setTestNow('2026-07-21 10:00:00');
        $requester = $this->createUser($this->userRole, ['preferred_locale' => 'en']);
        $solver = $this->createUser($this->solverRole, ['display_name' => 'Jane Solver']);
        $ticket = $this->createTicket($requester);

        $ticket->update(['assignee_id' => $solver->id]);
        $this->notifications()->notify($ticket->refresh(), 'assignee_changed', $solver, ['assignee' => $solver->displayName()]);
        Notification::assertNothingSent();

        CarbonImmutable::setTestNow('2026-07-21 10:05:00');
        $ticket->update(['ticket_status_id' => $this->inProgressStatus->id]);
        $this->notifications()->notify($ticket->refresh(), 'status_changed', $solver);
        Notification::assertNothingSent();

        CarbonImmutable::setTestNow('2026-07-21 10:10:00');
        $this->notifications()->notify($ticket->refresh(), 'public_comment', $solver, [
            'comment_body' => 'Please verify the new configuration.',
        ]);
        Notification::assertNothingSent();

        CarbonImmutable::setTestNow('2026-07-21 10:15:00');
        $ticket->update(['ticket_status_id' => $this->waitingUserStatus->id]);
        $this->notifications()->notify($ticket->refresh(), 'status_changed', $solver);

        Notification::assertSentToTimes($requester, TicketNotificationBatchNotification::class, 1);
        Notification::assertNotSentTo($solver, TicketNotificationBatchNotification::class);
        Notification::assertNotSentTo($requester, TicketEventNotification::class);

        $batch = TicketNotificationBatch::query()->with('items')->firstOrFail();
        $this->assertSame(TicketNotificationBatch::STATUS_SENT, $batch->status);
        $this->assertSame([
            'assignee_changed',
            'status_changed',
            'public_comment',
            'status_changed',
        ], $batch->items->pluck('event')->all());

        $mail = (new TicketNotificationBatchNotification($batch->load(['ticket.status', 'items.actor'])))->toMail($requester);
        $this->assertStringContainsString('We are waiting for your reply', $mail->subject);
        $this->assertContains('Please verify the new configuration.', $mail->introLines);
        $this->assertContains('We are currently waiting for your reply.', $mail->introLines);
        $this->assertContains('Current ticket status: Waiting for user', $mail->introLines);
    }

    public function test_quiet_period_moves_after_each_event_and_command_sends_when_due(): void
    {
        Notification::fake();
        CarbonImmutable::setTestNow('2026-07-21 10:00:00');
        [$ticket, $requester, $solver] = $this->ticketParticipants();

        $this->notifications()->notify($ticket, 'ticket_updated', $solver);
        $this->assertSame('2026-07-21 10:10:00', TicketNotificationBatch::query()->firstOrFail()->send_after->format('Y-m-d H:i:s'));

        CarbonImmutable::setTestNow('2026-07-21 10:08:00');
        $this->notifications()->notify($ticket, 'public_comment', $solver, ['comment_body' => 'Second event']);
        $this->assertSame('2026-07-21 10:18:00', TicketNotificationBatch::query()->firstOrFail()->send_after->format('Y-m-d H:i:s'));

        CarbonImmutable::setTestNow('2026-07-21 10:17:00');
        $this->artisan('helpdesk:send-pending-notification-batches')->assertSuccessful();
        Notification::assertNothingSent();

        CarbonImmutable::setTestNow('2026-07-21 10:18:00');
        $this->artisan('helpdesk:send-pending-notification-batches')->assertSuccessful();
        Notification::assertSentToTimes($requester, TicketNotificationBatchNotification::class, 1);
    }

    public function test_maximum_period_caps_further_postponement(): void
    {
        Notification::fake();
        CarbonImmutable::setTestNow('2026-07-21 10:00:00');
        [$ticket, , $solver] = $this->ticketParticipants();

        $this->notifications()->notify($ticket, 'ticket_updated', $solver);
        CarbonImmutable::setTestNow('2026-07-21 10:25:00');
        $this->notifications()->notify($ticket, 'public_comment', $solver, ['comment_body' => 'Late event']);

        $this->assertSame('2026-07-21 10:30:00', TicketNotificationBatch::query()->firstOrFail()->send_after->format('Y-m-d H:i:s'));
    }

    public function test_created_ticket_notification_remains_immediate(): void
    {
        Notification::fake();
        $requester = $this->createUser($this->userRole);
        $ticket = $this->createTicket($requester);

        $this->notifications()->notify($ticket, 'created', $requester, excludeActor: false);

        Notification::assertSentTo($requester, TicketEventNotification::class);
        $this->assertDatabaseCount('ticket_notification_batches', 0);
    }

    public function test_assignee_change_is_immediate_for_new_solver_and_batched_for_requester(): void
    {
        Notification::fake();
        $requester = $this->createUser($this->userRole);
        $actor = $this->createUser($this->solverRole);
        $newAssignee = $this->createUser($this->solverRole);
        $ticket = $this->createTicket($requester, $newAssignee);

        $this->notifications()->notify($ticket, 'assignee_changed', $actor, ['assignee' => $newAssignee->displayName()]);

        Notification::assertSentTo($newAssignee, TicketEventNotification::class);
        Notification::assertNotSentTo($requester, TicketEventNotification::class);
        $this->assertDatabaseHas('ticket_notification_batches', [
            'ticket_id' => $ticket->id,
            'recipient_id' => $requester->id,
            'status' => TicketNotificationBatch::STATUS_PENDING,
        ]);
    }

    public function test_requester_public_comment_is_sent_to_solver_immediately(): void
    {
        Notification::fake();
        [$ticket, $requester, $solver] = $this->ticketParticipants();

        $this->notifications()->notify($ticket, 'public_comment', $requester, ['comment_body' => 'Requester response']);

        Notification::assertSentTo($solver, TicketEventNotification::class);
        $this->assertDatabaseCount('ticket_notification_batches', 0);
    }

    public function test_inbound_requester_comment_remains_top_level_and_notifies_solver_immediately(): void
    {
        Notification::fake();
        [$ticket, $requester, $solver] = $this->ticketParticipants();

        $comment = app(InboundEmailReplyService::class)->processReply(
            $ticket,
            $requester,
            (string) $requester->email,
            '<batch-inbound@example.org>',
            'Inbound requester reply',
        );

        $this->assertNull($comment->parent_id);
        Notification::assertSentTo($solver, TicketEventNotification::class);
        $this->assertDatabaseCount('ticket_notification_batches', 0);
    }

    public function test_resolved_and_closed_events_flush_pending_batches(): void
    {
        Notification::fake();

        foreach ([['resolved', $this->resolvedStatus], ['closed', $this->closedStatus]] as [$event, $status]) {
            [$ticket, $requester, $solver] = $this->ticketParticipants();
            $this->notifications()->notify($ticket, 'ticket_updated', $solver);
            $ticket->update(['ticket_status_id' => $status->id]);
            $this->notifications()->notify($ticket->refresh(), $event, $solver);

            $batch = TicketNotificationBatch::query()->where('ticket_id', $ticket->id)->with('items')->firstOrFail();
            $this->assertSame(TicketNotificationBatch::STATUS_SENT, $batch->status);
            $this->assertSame(['ticket_updated', $event], $batch->items->pluck('event')->all());
            Notification::assertSentToTimes($requester, TicketNotificationBatchNotification::class, 1);
        }
    }

    public function test_problem_persists_is_immediate_for_solver_and_flushes_existing_requester_batch(): void
    {
        Notification::fake();
        [$ticket, $requester, $solver] = $this->ticketParticipants();
        $this->notifications()->notify($ticket, 'ticket_updated', $solver);

        $this->notifications()->notify($ticket, 'problem_persists', $requester);

        Notification::assertSentTo($solver, TicketEventNotification::class);
        Notification::assertSentTo($requester, TicketNotificationBatchNotification::class);
        $this->assertSame(TicketNotificationBatch::STATUS_SENT, TicketNotificationBatch::query()->firstOrFail()->status);
    }

    public function test_terminal_event_flushes_existing_batch_even_without_a_current_recipient(): void
    {
        Notification::fake();
        [$ticket, $requester, $solver] = $this->ticketParticipants();
        $this->notifications()->notify($ticket, 'ticket_updated', $solver);
        $ticket->update(['assignee_id' => null]);

        $this->notifications()->notify($ticket->refresh(), 'problem_persists', $requester);

        Notification::assertSentTo($requester, TicketNotificationBatchNotification::class);
        $this->assertSame(TicketNotificationBatch::STATUS_SENT, TicketNotificationBatch::query()->firstOrFail()->status);
    }

    public function test_internal_note_does_not_create_mail_or_batch_item(): void
    {
        Notification::fake();
        [$ticket, , $solver] = $this->ticketParticipants();

        $this->actingAs($solver)->post(route('tickets.internal-notes.store', $ticket), [
            'note_body' => 'Internal only',
        ])->assertRedirect();

        Notification::assertNothingSent();
        $this->assertDatabaseCount('ticket_notification_batch_items', 0);
    }

    public function test_private_watcher_without_view_permission_is_not_notified_or_batched(): void
    {
        Notification::fake();
        $requester = $this->createUser($this->userRole);
        $solver = $this->createUser($this->solverRole);
        $watcher = $this->createUser($this->userRole);
        $ticket = $this->createTicket($requester, $solver, Ticket::VISIBILITY_PRIVATE);
        $ticket->watcherEntries()->create(['user_id' => $watcher->id, 'is_manual' => true, 'is_auto_participant' => false]);

        $this->notifications()->notify($ticket, 'public_comment', $solver, ['comment_body' => 'Solver update']);

        Notification::assertNotSentTo($watcher, TicketEventNotification::class);
        $this->assertDatabaseMissing('ticket_notification_batches', ['recipient_id' => $watcher->id]);
    }

    public function test_batch_is_suppressed_when_recipient_loses_ticket_access(): void
    {
        Notification::fake();
        [$ticket, $requester, $solver] = $this->ticketParticipants();
        $this->notifications()->notify($ticket, 'ticket_updated', $solver);
        $replacementRequester = $this->createUser($this->userRole);
        $ticket->update([
            'requester_id' => $replacementRequester->id,
            'visibility' => Ticket::VISIBILITY_PRIVATE,
        ]);

        $result = app(TicketNotificationBatchSender::class)->send((int) TicketNotificationBatch::query()->value('id'));

        $this->assertSame('suppressed', $result);
        Notification::assertNotSentTo($requester, TicketNotificationBatchNotification::class);
        $this->assertSame(TicketNotificationBatch::STATUS_SUPPRESSED, TicketNotificationBatch::query()->firstOrFail()->status);
    }

    public function test_inactive_or_addressless_recipient_does_not_get_a_batch(): void
    {
        Notification::fake();
        $solver = $this->createUser($this->solverRole);

        foreach ([
            ['is_active' => false],
            ['email' => ''],
        ] as $attributes) {
            $requester = $this->createUser($this->userRole, $attributes);
            $ticket = $this->createTicket($requester, $solver);
            $this->notifications()->notify($ticket, 'ticket_updated', $solver);
        }

        $this->assertDatabaseCount('ticket_notification_batches', 0);
        Notification::assertNothingSent();
    }

    public function test_sender_claim_is_idempotent_and_does_not_send_twice(): void
    {
        Notification::fake();
        [$ticket, $requester, $solver] = $this->ticketParticipants();
        $this->notifications()->notify($ticket, 'ticket_updated', $solver);
        $batchId = (int) TicketNotificationBatch::query()->value('id');
        $sender = app(TicketNotificationBatchSender::class);

        $this->assertSame('sent', $sender->send($batchId));
        $this->assertSame('skipped', $sender->send($batchId));

        Notification::assertSentToTimes($requester, TicketNotificationBatchNotification::class, 1);
    }

    public function test_failed_delivery_is_retained_for_retry(): void
    {
        [$ticket, , $solver] = $this->ticketParticipants();
        Notification::fake();
        $this->notifications()->notify($ticket, 'ticket_updated', $solver);
        $batchId = (int) TicketNotificationBatch::query()->value('id');

        $dispatcher = Mockery::mock(Dispatcher::class);
        $dispatcher->shouldReceive('send')->once()->andThrow(new RuntimeException('Temporary SMTP failure'));
        $this->app->instance(Dispatcher::class, $dispatcher);

        $this->assertSame('failed', app(TicketNotificationBatchSender::class)->send($batchId));
        $batch = TicketNotificationBatch::query()->findOrFail($batchId);
        $this->assertSame(TicketNotificationBatch::STATUS_FAILED, $batch->status);
        $this->assertTrue((bool) $batch->active_marker);
        $this->assertStringContainsString('Temporary SMTP failure', (string) $batch->last_error);
    }

    public function test_disabled_batching_preserves_immediate_behavior(): void
    {
        Notification::fake();
        config()->set('helpdesk.notifications.mail.batch.enabled', false);
        [$ticket, $requester, $solver] = $this->ticketParticipants();

        $this->notifications()->notify($ticket, 'ticket_updated', $solver);

        Notification::assertSentTo($requester, TicketEventNotification::class);
        $this->assertDatabaseCount('ticket_notification_batches', 0);
    }

    public function test_one_assignee_operation_creates_one_batch_item_despite_automatic_fields(): void
    {
        Notification::fake();
        [$ticket, , $solver] = $this->ticketParticipants();

        $this->notifications()->notify($ticket, 'assignee_changed', $solver, [
            'assignee' => $solver->displayName(),
            'old_expected_resolution_at' => null,
        ]);

        $this->assertDatabaseCount('ticket_notification_batch_items', 1);
        $this->assertDatabaseHas('ticket_notification_batch_items', ['event' => 'assignee_changed']);
    }

    public function test_summary_uses_recipient_locale_and_tokenized_reply_to(): void
    {
        Notification::fake();
        config()->set('helpdesk.inbound.mail_enabled', true);
        config()->set('helpdesk.inbound.reply_address', 'helpdesk-replies@example.org');
        $requester = $this->createUser($this->userRole, ['preferred_locale' => 'cs']);
        $solver = $this->createUser($this->solverRole, ['display_name' => 'Jan Řešitel']);
        $ticket = $this->createTicket($requester, $solver);
        $this->notifications()->notify($ticket, 'ticket_updated', $solver);
        $batch = TicketNotificationBatch::query()->with(['ticket.status', 'items.actor'])->firstOrFail();

        $mail = (new TicketNotificationBatchNotification($batch))->toMail($requester);

        $this->assertStringContainsString('Souhrn 1 změn ticketu', $mail->subject);
        $this->assertNotSame([], $mail->replyTo);
        $this->assertStringStartsWith('helpdesk-replies+', $mail->replyTo[0][0]);
        $this->assertStringEndsWith('@example.org', $mail->replyTo[0][0]);
    }

    private function notifications(): TicketNotificationService
    {
        return app(TicketNotificationService::class);
    }

    /**
     * @return array{Ticket, User, User}
     */
    private function ticketParticipants(): array
    {
        $requester = $this->createUser($this->userRole);
        $solver = $this->createUser($this->solverRole);

        return [$this->createTicket($requester, $solver), $requester, $solver];
    }

    private function createRole(string $name, string $slug): Role
    {
        return Role::query()->create(['name' => $name, 'slug' => $slug, 'is_system' => true]);
    }

    private function createStatus(string $name, string $slug, bool $closed = false): TicketStatus
    {
        return TicketStatus::query()->create([
            'name' => $name,
            'slug' => $slug,
            'sort_order' => TicketStatus::query()->count() + 1,
            'is_closed' => $closed,
        ]);
    }

    private function createUser(Role $role, array $attributes = []): User
    {
        $user = User::query()->create(array_merge([
            'name' => 'User '.str()->random(8),
            'display_name' => null,
            'email' => str()->random(12).'@example.org',
            'password' => 'unused',
            'preferred_locale' => 'en',
            'is_active' => true,
        ], $attributes));
        $user->roles()->attach($role);

        return $user;
    }

    private function createTicket(
        User $requester,
        ?User $assignee = null,
        string $visibility = Ticket::VISIBILITY_INTERNAL,
    ): Ticket {
        return Ticket::query()->create([
            'ticket_number' => '2026-'.str_pad((string) (Ticket::query()->count() + 1), 3, '0', STR_PAD_LEFT),
            'subject' => 'Notification batching ticket',
            'description' => 'Ticket description',
            'visibility' => $visibility,
            'requester_id' => $requester->id,
            'assignee_id' => $assignee?->id,
            'ticket_status_id' => $this->assignedStatus->id,
            'ticket_priority_id' => $this->priority->id,
            'ticket_category_id' => $this->category->id,
        ]);
    }
}
