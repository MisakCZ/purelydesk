<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\User;
use App\Notifications\TicketEventNotification;
use App\Services\ExpectedResolutionDeadlineNotificationService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ExpectedResolutionDeadlineNotificationTest extends TestCase
{
    use RefreshDatabase;

    private Role $userRole;

    private Role $solverRole;

    private Role $adminRole;

    private TicketStatus $assignedStatus;

    private TicketStatus $resolvedStatus;

    private TicketStatus $closedStatus;

    private TicketStatus $cancelledStatus;

    private TicketPriority $priority;

    private TicketCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRole = Role::query()->create(['name' => 'User', 'slug' => Role::SLUG_USER, 'is_system' => true]);
        $this->solverRole = Role::query()->create(['name' => 'Solver', 'slug' => Role::SLUG_SOLVER, 'is_system' => true]);
        $this->adminRole = Role::query()->create(['name' => 'Admin', 'slug' => Role::SLUG_ADMIN, 'is_system' => true]);

        $this->assignedStatus = $this->createStatus('Assigned', 'assigned');
        $this->resolvedStatus = $this->createStatus('Resolved', 'resolved');
        $this->closedStatus = $this->createStatus('Closed', 'closed');
        $this->cancelledStatus = $this->createStatus('Cancelled', 'cancelled');

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
        config()->set('helpdesk.notifications.mail.expected_resolution_deadline_notifications_enabled', true);
        config()->set('helpdesk.notifications.mail.expected_resolution_due_soon_hours', 24);
        config()->set('helpdesk.notifications.mail.expected_resolution_overdue_repeat_hours', 24);
    }

    public function test_due_soon_notification_is_sent_once_to_assignee(): void
    {
        Notification::fake();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-05-13 10:00:00'));
        $requester = $this->createUserWithRole($this->userRole);
        $assignee = $this->createUserWithRole($this->solverRole);
        $admin = $this->createUserWithRole($this->adminRole);
        $watcher = $this->createUserWithRole($this->userRole);
        $ticket = $this->createTicket([
            'requester_id' => $requester->id,
            'assignee_id' => $assignee->id,
            'expected_resolution_at' => CarbonImmutable::now()->addHours(23),
        ]);
        $ticket->watcherEntries()->create(['user_id' => $watcher->id, 'is_manual' => true, 'is_auto_participant' => false]);

        $counts = $this->service()->notifyDueDeadlines();

        $this->assertSame(['due_soon' => 1, 'overdue' => 0], $counts);
        Notification::assertSentTo($assignee, TicketEventNotification::class, fn (TicketEventNotification $notification): bool => $notification->event === 'expected_resolution_due_soon');
        Notification::assertNotSentTo($requester, TicketEventNotification::class);
        Notification::assertNotSentTo($admin, TicketEventNotification::class);
        Notification::assertNotSentTo($watcher, TicketEventNotification::class);
        $this->assertNotNull($ticket->refresh()->expected_resolution_due_soon_notified_at);

        Notification::fake();
        $counts = $this->service()->notifyDueDeadlines();

        $this->assertSame(['due_soon' => 0, 'overdue' => 0], $counts);
        Notification::assertNothingSent();
        CarbonImmutable::setTestNow();
    }

    public function test_overdue_notification_repeats_only_after_configured_interval(): void
    {
        Notification::fake();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-05-13 10:00:00'));
        $assignee = $this->createUserWithRole($this->solverRole);
        $ticket = $this->createTicket([
            'assignee_id' => $assignee->id,
            'expected_resolution_at' => CarbonImmutable::now()->subHour(),
        ]);

        $counts = $this->service()->notifyDueDeadlines();

        $this->assertSame(['due_soon' => 0, 'overdue' => 1], $counts);
        Notification::assertSentTo($assignee, TicketEventNotification::class, fn (TicketEventNotification $notification): bool => $notification->event === 'expected_resolution_overdue');

        Notification::fake();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-05-14 09:00:00'));
        $counts = $this->service()->notifyDueDeadlines();

        $this->assertSame(['due_soon' => 0, 'overdue' => 0], $counts);
        Notification::assertNothingSent();

        Notification::fake();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-05-14 11:00:00'));
        $counts = $this->service()->notifyDueDeadlines();

        $this->assertSame(['due_soon' => 0, 'overdue' => 1], $counts);
        Notification::assertSentTo($assignee, TicketEventNotification::class, fn (TicketEventNotification $notification): bool => $notification->event === 'expected_resolution_overdue');
        $this->assertTrue($ticket->refresh()->expected_resolution_overdue_notified_at->equalTo(CarbonImmutable::now()));
        CarbonImmutable::setTestNow();
    }

    public function test_deadline_notifications_skip_ineligible_tickets_and_disabled_mail(): void
    {
        Notification::fake();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-05-13 10:00:00'));
        $assignee = $this->createUserWithRole($this->solverRole);

        $this->createTicket(['assignee_id' => null, 'expected_resolution_at' => CarbonImmutable::now()->subHour()]);
        $this->createTicket(['assignee_id' => $assignee->id, 'expected_resolution_at' => null]);
        $this->createTicket(['assignee_id' => $assignee->id, 'ticket_status_id' => $this->resolvedStatus->id, 'expected_resolution_at' => CarbonImmutable::now()->subHour()]);
        $this->createTicket(['assignee_id' => $assignee->id, 'ticket_status_id' => $this->closedStatus->id, 'expected_resolution_at' => CarbonImmutable::now()->subHour()]);
        $this->createTicket(['assignee_id' => $assignee->id, 'ticket_status_id' => $this->cancelledStatus->id, 'expected_resolution_at' => CarbonImmutable::now()->subHour()]);
        $this->createTicket(['assignee_id' => $assignee->id, 'expected_resolution_at' => CarbonImmutable::now()->subHour(), 'archived_at' => CarbonImmutable::now()]);

        $counts = $this->service()->notifyDueDeadlines();

        $this->assertSame(['due_soon' => 0, 'overdue' => 0], $counts);
        Notification::assertNothingSent();

        config()->set('helpdesk.notifications.mail.enabled', false);
        $this->createTicket(['assignee_id' => $assignee->id, 'expected_resolution_at' => CarbonImmutable::now()->subHour()]);

        $counts = $this->service()->notifyDueDeadlines();

        $this->assertSame(['due_soon' => 0, 'overdue' => 0], $counts);
        Notification::assertNothingSent();
        CarbonImmutable::setTestNow();
    }

    public function test_deadline_notification_timestamps_reset_when_expected_resolution_changes(): void
    {
        Notification::fake();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-05-13 10:00:00'));
        $solver = $this->createUserWithRole($this->solverRole);
        $ticket = $this->createTicket([
            'requester_id' => $this->createUserWithRole($this->userRole)->id,
            'assignee_id' => $solver->id,
            'expected_resolution_at' => CarbonImmutable::now()->addDay(),
            'expected_resolution_due_soon_notified_at' => CarbonImmutable::now(),
            'expected_resolution_overdue_notified_at' => CarbonImmutable::now(),
        ]);

        $this->actingAs($solver)
            ->patch(route('tickets.update', $ticket), [
                'subject' => $ticket->subject,
                'description' => $ticket->description,
                'priority_id' => $this->priority->id,
                'category_id' => $this->category->id,
                'expected_resolution_at' => CarbonImmutable::now()->addDays(2)->format('Y-m-d\TH:i'),
            ])
            ->assertRedirect(route('tickets.show', $ticket));

        $ticket->refresh();

        $this->assertNull($ticket->expected_resolution_due_soon_notified_at);
        $this->assertNull($ticket->expected_resolution_overdue_notified_at);
        CarbonImmutable::setTestNow();
    }

    public function test_priority_recalculation_does_not_notify_requester_about_expected_resolution_change(): void
    {
        Notification::fake();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-05-13 10:00:00'));
        config()->set('helpdesk.workflow.expected_resolution_days.high', 2);
        $requester = $this->createUserWithRole($this->userRole);
        $solver = $this->createUserWithRole($this->solverRole);
        $highPriority = TicketPriority::query()->create([
            'name' => 'High',
            'slug' => 'high',
            'sort_order' => 2,
            'is_default' => false,
        ]);
        $ticket = $this->createTicket([
            'requester_id' => $requester->id,
            'assignee_id' => $solver->id,
            'expected_resolution_at' => CarbonImmutable::now()->addDays(5),
            'expected_resolution_source' => 'auto',
        ]);

        $this->actingAs($solver)
            ->patch(route('tickets.priority.update', $ticket), ['priority_id' => $highPriority->id])
            ->assertRedirect(route('tickets.show', $ticket));

        Notification::assertNotSentTo($requester, TicketEventNotification::class, fn (TicketEventNotification $notification): bool => $notification->event === 'expected_resolution_changed');
        CarbonImmutable::setTestNow();
    }

    private function service(): ExpectedResolutionDeadlineNotificationService
    {
        return app(ExpectedResolutionDeadlineNotificationService::class);
    }

    private function createUserWithRole(Role $role, array $attributes = []): User
    {
        $user = User::query()->create(array_merge([
            'name' => 'Test User '.$role->slug.' '.str()->random(6),
            'email' => $role->slug.str()->random(6).'@example.org',
            'password' => 'unused',
            'is_active' => true,
        ], $attributes));

        $user->roles()->attach($role);

        return $user;
    }

    private function createStatus(string $name, string $slug): TicketStatus
    {
        return TicketStatus::query()->create([
            'name' => $name,
            'slug' => $slug,
            'sort_order' => TicketStatus::query()->count() + 1,
        ]);
    }

    private function createTicket(array $attributes = []): Ticket
    {
        return Ticket::query()->create(array_merge([
            'ticket_number' => '2026-'.str_pad((string) (Ticket::query()->count() + 1), 3, '0', STR_PAD_LEFT),
            'subject' => 'Deadline reminder ticket',
            'description' => 'Ticket body',
            'visibility' => Ticket::VISIBILITY_INTERNAL,
            'requester_id' => $this->createUserWithRole($this->userRole)->id,
            'assignee_id' => null,
            'ticket_status_id' => $this->assignedStatus->id,
            'ticket_priority_id' => $this->priority->id,
            'ticket_category_id' => $this->category->id,
        ], $attributes));
    }
}
