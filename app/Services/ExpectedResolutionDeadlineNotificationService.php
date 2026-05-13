<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class ExpectedResolutionDeadlineNotificationService
{
    public function __construct(
        private readonly TicketNotificationRecipients $recipients,
        private readonly TicketNotificationService $notifications,
    ) {}

    /**
     * @return array{due_soon: int, overdue: int}
     */
    public function notifyDueDeadlines(): array
    {
        if (! config('helpdesk.notifications.mail.enabled')
            || ! config('helpdesk.notifications.mail.expected_resolution_deadline_notifications_enabled')
            || ! Ticket::supportsExpectedResolution()
        ) {
            return ['due_soon' => 0, 'overdue' => 0];
        }

        return [
            'due_soon' => $this->notifyDueSoon(),
            'overdue' => $this->notifyOverdue(),
        ];
    }

    private function notifyDueSoon(): int
    {
        $notified = 0;
        $now = CarbonImmutable::now();
        $dueSoonHours = max(1, (int) config('helpdesk.notifications.mail.expected_resolution_due_soon_hours', 24));

        $this->baseQuery()
            ->whereNull('expected_resolution_due_soon_notified_at')
            ->where('expected_resolution_at', '>', $now)
            ->where('expected_resolution_at', '<=', $now->addHours($dueSoonHours))
            ->orderBy('expected_resolution_at')
            ->each(function (Ticket $ticket) use (&$notified, $now): void {
                if ($this->notifyTicket($ticket, 'expected_resolution_due_soon')) {
                    $ticket->forceFill([
                        'expected_resolution_due_soon_notified_at' => $now,
                    ])->saveQuietly();
                    $notified++;
                }
            });

        return $notified;
    }

    private function notifyOverdue(): int
    {
        $notified = 0;
        $now = CarbonImmutable::now();
        $repeatHours = max(1, (int) config('helpdesk.notifications.mail.expected_resolution_overdue_repeat_hours', 24));

        $this->baseQuery()
            ->where('expected_resolution_at', '<=', $now)
            ->where(function (Builder $query) use ($now, $repeatHours): void {
                $query
                    ->whereNull('expected_resolution_overdue_notified_at')
                    ->orWhere('expected_resolution_overdue_notified_at', '<=', $now->subHours($repeatHours));
            })
            ->orderBy('expected_resolution_at')
            ->each(function (Ticket $ticket) use (&$notified, $now): void {
                if ($this->notifyTicket($ticket, 'expected_resolution_overdue')) {
                    $ticket->forceFill([
                        'expected_resolution_overdue_notified_at' => $now,
                    ])->saveQuietly();
                    $notified++;
                }
            });

        return $notified;
    }

    private function baseQuery(): Builder
    {
        $query = Ticket::query()
            ->whereNotNull('assignee_id')
            ->whereNotNull('expected_resolution_at')
            ->with([
                'assignee:id,name,display_name,username,email,preferred_locale',
                'requester:id,name,display_name,username,email,preferred_locale',
                'status:id,name,slug',
                'watchers:id,name,display_name,username,email,preferred_locale',
            ])
            ->whereDoesntHave('status', function (Builder $query): void {
                $query->whereIn('slug', ['resolved', 'closed', 'cancelled']);
            });

        if (Ticket::supportsArchiving()) {
            $query->whereNull('archived_at');
        }

        return $query;
    }

    private function notifyTicket(Ticket $ticket, string $event): bool
    {
        if (! $ticket->assignee instanceof User || blank($ticket->assignee->email)) {
            return false;
        }

        if ($this->recipients->forTicket($ticket, $event, null)->isEmpty()) {
            return false;
        }

        $this->notifications->notify($ticket, $event, null);

        return true;
    }
}
