<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class DashboardDataService
{
    private const LIMIT = 5;

    private const ANNOUNCEMENT_LIMIT = 3;

    /**
     * @return array<string, mixed>
     */
    public function forUser(User $user): array
    {
        return [
            'announcements' => $this->activeAnnouncements($user),
            'pinnedTickets' => $this->pinnedTickets($user),
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
                    'without_expected_resolution' => Ticket::supportsExpectedResolution()
                        ? $this->withoutExpectedResolution($user)
                        : collect(),
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
                    'without_expected_resolution' => Ticket::supportsExpectedResolution()
                        ? $this->withoutExpectedResolutionQuery($user)->count()
                        : 0,
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
     * @return array{items: Collection<int, Announcement>, hasMore: bool}
     */
    private function activeAnnouncements(User $user): array
    {
        $announcements = $this->activeAnnouncementsQuery()
            ->limit(self::ANNOUNCEMENT_LIMIT + 1)
            ->get();

        return [
            'items' => $announcements->take(self::ANNOUNCEMENT_LIMIT)->values(),
            'hasMore' => $announcements->count() > self::ANNOUNCEMENT_LIMIT,
        ];
    }

    private function activeAnnouncementsQuery(): Builder
    {
        $query = Announcement::query()
            ->active()
            ->publicVisible()
            ->select([
                'id',
                'title',
                'body',
                'type',
                'visibility',
                'starts_at',
                'ends_at',
                'updated_at',
                'created_at',
            ]);

        if (Announcement::supportsPinning()) {
            $query
                ->addSelect('is_pinned')
                ->orderByDesc('is_pinned');
        }

        return $query
            ->orderByRaw("case type when 'outage' then 1 when 'warning' then 2 when 'maintenance' then 3 when 'info' then 4 else 5 end")
            ->orderByDesc('starts_at')
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at');
    }

    /**
     * @return Collection<int, Ticket>
     */
    private function pinnedTickets(User $user): Collection
    {
        if (! Ticket::supportsPinning()) {
            return collect();
        }

        return $this->baseTicketQuery($user)
            ->where('is_pinned', true)
            ->tap(fn (Builder $query) => $this->whereNotFinal($query))
            ->orderByDesc('pinned_at')
            ->orderByDesc('updated_at')
            ->limit(self::LIMIT)
            ->get();
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

    /**
     * @return Collection<int, Ticket>
     */
    private function withoutExpectedResolution(User $user): Collection
    {
        return $this->withoutExpectedResolutionQuery($user)
            ->orderByDesc('updated_at')
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

    private function withoutExpectedResolutionQuery(User $user): Builder
    {
        return $this->baseTicketQuery($user)
            ->where('assignee_id', $user->id)
            ->whereNull('expected_resolution_at')
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
