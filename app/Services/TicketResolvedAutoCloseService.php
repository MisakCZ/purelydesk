<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class TicketResolvedAutoCloseService
{
    public function __construct(
        private readonly TicketHistoryService $history,
        private readonly TicketNotificationService $notifications,
        private readonly TicketWorkflowAutomationService $workflow,
    ) {}

    public function closeDueTickets(): int
    {
        $closedStatus = $this->statusByIdentifier('closed');

        if (! $closedStatus instanceof TicketStatus) {
            return 0;
        }

        $closedCount = 0;

        $this->resolvedDueQuery()
            ->with(['status:id,name,slug', 'requester:id,name,display_name,username,email,preferred_locale', 'assignee:id,name,display_name,username,email,preferred_locale'])
            ->chunkById(100, function ($tickets) use ($closedStatus, &$closedCount): void {
                foreach ($tickets as $ticket) {
                    $updatedTicket = $this->history->applyUpdateWithHistory(
                        $ticket,
                        $this->workflow->attributesForStatusTransition($ticket, $closedStatus),
                        'auto_close_resolved',
                    );

                    $this->notifications->notify($updatedTicket, 'closed', null, excludeActor: false);
                    $closedCount++;
                }
            });

        return $closedCount;
    }

    private function resolvedDueQuery(): Builder
    {
        return Ticket::query()
            ->whereNotNull('auto_close_at')
            ->where('auto_close_at', '<=', Carbon::now())
            ->whereHas('status', fn (Builder $query) => $this->whereStatusIdentifiers($query, ['resolved']))
            ->when(Ticket::supportsArchiving(), fn (Builder $query) => $query->whereNull('archived_at'));
    }

    private function statusByIdentifier(string $identifier): ?TicketStatus
    {
        return TicketStatus::query()
            ->where(function (Builder $query) use ($identifier): void {
                $query->where('slug', $identifier);

                if (Schema::hasColumn('ticket_statuses', 'code')) {
                    $query->orWhere('code', $identifier);
                }
            })
            ->first();
    }

    /**
     * @param  array<int, string>  $identifiers
     */
    private function whereStatusIdentifiers(Builder $query, array $identifiers): void
    {
        $query->whereIn('slug', $identifiers);

        if (Schema::hasColumn('ticket_statuses', 'code')) {
            $query->orWhereIn('code', $identifiers);
        }
    }
}
