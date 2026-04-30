<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class DashboardDataService
{
    private const LIMIT = 5;

    /**
     * @return array<string, mixed>
     */
    public function forUser(User $user): array
    {
        return [
            'userSections' => [
                'my_open_tickets' => $this->myOpenTickets($user),
                'waiting_for_confirmation' => $this->waitingForConfirmation($user),
            ],
            'personalRequesterCount' => $user->isSolver()
                ? $this->myOpenTicketsQuery($user)->count()
                : 0,
            'solverSections' => $user->isSolver()
                ? [
                    'new_unassigned_tickets' => $this->newUnassignedTickets($user),
                    'my_assigned_tickets' => $this->myAssignedTickets($user),
                    'waiting_for_user' => $this->waitingForUser($user),
                    'resolved_waiting_confirmation' => $this->resolvedWaitingConfirmation($user),
                    'due_soon_or_overdue' => Ticket::supportsExpectedResolution()
                        ? $this->dueSoonOrOverdue($user)
                        : collect(),
                ]
                : [],
            'solverCounts' => $user->isSolver()
                ? [
                    'new_unassigned_tickets' => $this->newUnassignedTicketsQuery($user)->count(),
                    'my_assigned_tickets' => $this->myAssignedTicketsQuery($user)->count(),
                    'waiting_for_user' => $this->waitingForUserQuery($user)->count(),
                    'due_soon_or_overdue' => Ticket::supportsExpectedResolution()
                        ? $this->dueSoonOrOverdueQuery($user)->count()
                        : 0,
                ]
                : [],
            'showExpectedResolutionSection' => $user->isSolver() && Ticket::supportsExpectedResolution(),
            'adminLinks' => $user->isAdmin()
                ? $this->adminLinks()
                : [],
            'isSolverDashboard' => $user->isSolver(),
            'isAdminDashboard' => $user->isAdmin(),
        ];
    }

    /**
     * @return Collection<int, Ticket>
     */
    private function myOpenTickets(User $user): Collection
    {
        return $this->myOpenTicketsQuery($user)
            ->orderByDesc('updated_at')
            ->limit(self::LIMIT)
            ->get();
    }

    /**
     * @return Collection<int, Ticket>
     */
    private function waitingForConfirmation(User $user): Collection
    {
        return $this->waitingForConfirmationQuery($user)
            ->orderByDesc('updated_at')
            ->limit(self::LIMIT)
            ->get();
    }

    private function myOpenTicketsQuery(User $user): Builder
    {
        return $this->baseTicketQuery($user)
            ->where('requester_id', $user->id)
            ->tap(fn (Builder $query) => $this->whereNotFinal($query));
    }

    private function waitingForConfirmationQuery(User $user): Builder
    {
        return $this->baseTicketQuery($user)
            ->where('requester_id', $user->id)
            ->tap(fn (Builder $query) => $this->whereStatusIdentifiers($query, ['resolved']));
    }

    /**
     * @return Collection<int, Ticket>
     */
    private function newUnassignedTickets(User $user): Collection
    {
        return $this->newUnassignedTicketsQuery($user)
            ->orderByDesc('updated_at')
            ->limit(self::LIMIT)
            ->get();
    }

    /**
     * @return Collection<int, Ticket>
     */
    private function myAssignedTickets(User $user): Collection
    {
        return $this->myAssignedTicketsQuery($user)
            ->orderByDesc('updated_at')
            ->limit(self::LIMIT)
            ->get();
    }

    /**
     * @return Collection<int, Ticket>
     */
    private function waitingForUser(User $user): Collection
    {
        return $this->waitingForUserQuery($user)
            ->orderByDesc('updated_at')
            ->limit(self::LIMIT)
            ->get();
    }

    /**
     * @return Collection<int, Ticket>
     */
    private function resolvedWaitingConfirmation(User $user): Collection
    {
        return $this->baseTicketQuery($user)
            ->tap(fn (Builder $query) => $this->whereStatusIdentifiers($query, ['resolved']))
            ->orderByDesc('updated_at')
            ->limit(self::LIMIT)
            ->get();
    }

    /**
     * @return Collection<int, Ticket>
     */
    private function dueSoonOrOverdue(User $user): Collection
    {
        return $this->dueSoonOrOverdueQuery($user)
            ->orderBy('expected_resolution_at')
            ->limit(self::LIMIT)
            ->get();
    }

    private function newUnassignedTicketsQuery(User $user): Builder
    {
        return $this->baseTicketQuery($user)
            ->whereNull('assignee_id')
            ->tap(fn (Builder $query) => $this->whereStatusIdentifiers($query, ['new']))
            ->tap(fn (Builder $query) => $this->whereNotFinal($query));
    }

    private function myAssignedTicketsQuery(User $user): Builder
    {
        return $this->baseTicketQuery($user)
            ->where('assignee_id', $user->id)
            ->tap(fn (Builder $query) => $this->whereNotFinal($query));
    }

    private function waitingForUserQuery(User $user): Builder
    {
        return $this->baseTicketQuery($user)
            ->tap(fn (Builder $query) => $this->whereStatusIdentifiers($query, ['waiting_user']));
    }

    private function dueSoonOrOverdueQuery(User $user): Builder
    {
        return $this->baseTicketQuery($user)
            ->whereNotNull('expected_resolution_at')
            ->where('expected_resolution_at', '<=', Carbon::now()->addDays(3))
            ->tap(fn (Builder $query) => $this->whereNotFinal($query));
    }

    private function baseTicketQuery(User $user): Builder
    {
        $query = Ticket::query()
            ->visibleTo($user)
            ->with([
                'status:id,name,slug',
                'priority:id,name,slug',
            ]);

        if (Ticket::supportsArchiving()) {
            $query->whereNull('archived_at');
        }

        return $query;
    }

    private function whereNotFinal(Builder $query): void
    {
        $query->whereDoesntHave('status', fn (Builder $query) => $this->whereStatusSlugIn($query, ['closed', 'cancelled']));
    }

    private function whereStatusIdentifiers(Builder $query, array $identifiers): void
    {
        $query->whereHas('status', fn (Builder $query) => $this->whereStatusSlugIn($query, $identifiers));
    }

    private function whereStatusSlugIn(Builder $query, array $identifiers): void
    {
        $query->where(function (Builder $query) use ($identifiers): void {
            $query->whereIn('slug', $identifiers);

            if (Schema::hasColumn('ticket_statuses', 'code')) {
                $query->orWhereIn('code', $identifiers);
            }
        });
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function adminLinks(): array
    {
        $links = [
            [
                'label' => __('dashboard.admin.links.tickets'),
                'url' => route('tickets.index'),
            ],
            [
                'label' => __('dashboard.admin.links.announcements'),
                'url' => route('announcements.index'),
            ],
        ];

        if (Ticket::supportsArchiving()) {
            $links[] = [
                'label' => __('dashboard.admin.links.archive'),
                'url' => route('tickets.index', ['archive' => 'archived']),
            ];
        }

        return $links;
    }
}
