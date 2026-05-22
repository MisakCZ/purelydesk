<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketActivity;
use App\Models\TicketComment;
use App\Models\TicketHistory;
use App\Models\TicketReadState;
use App\Models\User;
use App\Policies\TicketPolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class TicketActivityService
{
    public function recordPublicComment(Ticket $ticket, TicketComment $comment, ?User $actor): TicketActivity
    {
        return $this->createActivity($ticket, [
            'actor_id' => $actor?->id,
            'type' => TicketActivity::TYPE_PUBLIC_COMMENT,
            'visibility' => TicketActivity::VISIBILITY_PUBLIC,
            'subject_type' => TicketComment::class,
            'subject_id' => $comment->id,
            'summary_key' => $comment->parent_id
                ? 'activities.summaries.public_reply'
                : 'activities.summaries.public_comment',
            'meta' => [
                'is_reply' => $comment->parent_id !== null,
                'comment_id' => $comment->id,
                'parent_id' => $comment->parent_id,
            ],
        ]);
    }

    public function recordInternalNote(Ticket $ticket, TicketComment $comment, ?User $actor): TicketActivity
    {
        return $this->createActivity($ticket, [
            'actor_id' => $actor?->id,
            'type' => TicketActivity::TYPE_INTERNAL_NOTE,
            'visibility' => TicketActivity::VISIBILITY_INTERNAL,
            'subject_type' => TicketComment::class,
            'subject_id' => $comment->id,
            'summary_key' => 'activities.summaries.internal_note',
            'meta' => [
                'comment_id' => $comment->id,
            ],
        ]);
    }

    public function recordTicketUpdate(Ticket $ticket, TicketHistory $history, string $action, ?User $actor): TicketActivity
    {
        $type = $this->activityTypeForHistory($ticket, $history, $action);

        return $this->createActivity($ticket, [
            'actor_id' => $actor?->id,
            'type' => $type,
            'visibility' => $this->activityVisibilityForType($type),
            'subject_type' => TicketHistory::class,
            'subject_id' => $history->id,
            'summary_key' => 'activities.summaries.'.$type,
            'meta' => [
                'action' => $action,
                'changed_fields' => $history->meta['changed_fields'] ?? [],
            ],
        ]);
    }

    public function unreadTicketCountForUser(User $user): int
    {
        return (int) $this->unreadActivitiesQuery($user)
            ->distinct('ticket_activities.ticket_id')
            ->count('ticket_activities.ticket_id');
    }

    public function unreadActivityCountForUser(User $user): int
    {
        return (int) $this->unreadActivitiesQuery($user)->count();
    }

    /**
     * @return Collection<int, TicketActivity>
     */
    public function unreadActivitiesForUser(User $user, int $limit = 50): Collection
    {
        return $this->unreadActivitiesQuery($user)
            ->with([
                'actor:id,name,display_name,username',
                'ticket:id,ticket_number,subject,visibility,requester_id,assignee_id',
            ])
            ->latest('ticket_activities.id')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, TicketActivity>
     */
    public function unreadActivitiesForTicket(User $user, Ticket $ticket): Collection
    {
        if (! $this->canUserReceiveTicketActivities($user, $ticket)) {
            return collect();
        }

        return $this->unreadActivitiesQuery($user)
            ->where('ticket_activities.ticket_id', $ticket->id)
            ->with('actor:id,name,display_name,username')
            ->oldest('ticket_activities.id')
            ->get();
    }

    /**
     * @param  Collection<int, Ticket>  $tickets
     * @return array<int, array{count: int, comments: int, internal: int, changes: int}>
     */
    public function unreadSummaryForTickets(User $user, Collection $tickets): array
    {
        $ticketIds = $tickets
            ->pluck('id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($ticketIds->isEmpty()) {
            return [];
        }

        $activities = $this->unreadActivitiesQuery($user)
            ->whereIn('ticket_activities.ticket_id', $ticketIds)
            ->get(['ticket_activities.ticket_id', 'ticket_activities.type', 'ticket_activities.visibility']);

        return $activities
            ->groupBy('ticket_id')
            ->map(function (Collection $group): array {
                return [
                    'count' => $group->count(),
                    'comments' => $group->where('type', TicketActivity::TYPE_PUBLIC_COMMENT)->count(),
                    'internal' => $group->where('type', TicketActivity::TYPE_INTERNAL_NOTE)->count(),
                    'changes' => $group
                        ->reject(fn (TicketActivity $activity) => in_array($activity->type, [
                            TicketActivity::TYPE_PUBLIC_COMMENT,
                            TicketActivity::TYPE_INTERNAL_NOTE,
                        ], true))
                        ->count(),
                ];
            })
            ->all();
    }

    public function markTicketRead(User $user, Ticket $ticket): void
    {
        if (! app(TicketPolicy::class)->view($user, $ticket)) {
            return;
        }

        $lastVisibleActivity = $this->visibleActivitiesForTicketQuery($user, $ticket)
            ->latest('id')
            ->first(['id']);

        if (! $lastVisibleActivity instanceof TicketActivity) {
            return;
        }

        $this->storeReadState($ticket, $user, $lastVisibleActivity->id);
    }

    public function markAllVisibleRead(User $user): int
    {
        $latestByTicket = $this->unreadActivitiesQuery($user)
            ->selectRaw('ticket_activities.ticket_id, max(ticket_activities.id) as last_activity_id')
            ->groupBy('ticket_activities.ticket_id')
            ->get();

        foreach ($latestByTicket as $row) {
            TicketReadState::query()->updateOrCreate([
                'ticket_id' => (int) $row->ticket_id,
                'user_id' => $user->id,
            ], [
                'last_read_activity_id' => (int) $row->last_activity_id,
                'last_read_at' => now(),
            ]);
        }

        return $latestByTicket->count();
    }

    public function ensureReadStateAtCurrentActivity(Ticket $ticket, User $user): void
    {
        $lastActivityId = $ticket->activities()
            ->latest('id')
            ->value('id');

        if ($lastActivityId === null) {
            return;
        }

        $this->storeReadState($ticket, $user, (int) $lastActivityId);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createActivity(Ticket $ticket, array $attributes): TicketActivity
    {
        return $ticket->activities()->create([
            ...$attributes,
            'created_at' => now(),
        ]);
    }

    private function unreadActivitiesQuery(User $user): Builder
    {
        return TicketActivity::query()
            ->select('ticket_activities.*')
            ->leftJoin('ticket_read_states', function ($join) use ($user): void {
                $join
                    ->on('ticket_read_states.ticket_id', '=', 'ticket_activities.ticket_id')
                    ->where('ticket_read_states.user_id', '=', $user->id);
            })
            ->where(function (Builder $query) use ($user): void {
                $query->whereIn('ticket_activities.ticket_id', $this->visibleParticipantTicketQuery($user));

                if ($user->isAdmin() || $user->isSolver()) {
                    $query->orWhere(function (Builder $query) use ($user): void {
                        $query
                            ->where('ticket_activities.type', TicketActivity::TYPE_INTERNAL_NOTE)
                            ->whereIn('ticket_activities.ticket_id', $this->visibleInternalNoteTicketQuery($user));
                    });
                }
            })
            ->where(function (Builder $query) use ($user): void {
                $query
                    ->whereNull('ticket_activities.actor_id')
                    ->orWhere('ticket_activities.actor_id', '!=', $user->id);
            })
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('ticket_read_states.last_read_activity_id')
                    ->orWhereColumn('ticket_activities.id', '>', 'ticket_read_states.last_read_activity_id');
            })
            ->tap(fn (Builder $query) => $this->applyActivityVisibility($query, $user));
    }

    private function visibleActivitiesForTicketQuery(User $user, Ticket $ticket): Builder
    {
        return $ticket->activities()
            ->getQuery()
            ->tap(fn (Builder $query) => $this->applyActivityVisibility($query, $user));
    }

    private function visibleParticipantTicketQuery(User $user): Builder
    {
        return Ticket::query()
            ->visibleTo($user)
            ->select('tickets.id')
            ->when(Ticket::supportsArchiving() && ! $user->isAdmin(), fn (Builder $query) => $query->whereNull('archived_at'))
            ->where(function (Builder $query) use ($user): void {
                $query
                    ->where('requester_id', $user->id)
                    ->orWhere('assignee_id', $user->id)
                    ->orWhereHas('watchers', fn (Builder $watcherQuery) => $watcherQuery->whereKey($user->id));
            });
    }

    private function visibleInternalNoteTicketQuery(User $user): Builder
    {
        return Ticket::query()
            ->visibleTo($user)
            ->select('tickets.id')
            ->when(Ticket::supportsArchiving() && ! $user->isAdmin(), fn (Builder $query) => $query->whereNull('archived_at'));
    }

    private function applyActivityVisibility(Builder $query, User $user): void
    {
        if ($user->isAdmin() || $user->isSolver()) {
            return;
        }

        $query->where('ticket_activities.visibility', TicketActivity::VISIBILITY_PUBLIC);
    }

    private function canUserReceiveTicketActivities(User $user, Ticket $ticket): bool
    {
        if (! app(TicketPolicy::class)->view($user, $ticket)) {
            return false;
        }

        if (app(TicketPolicy::class)->viewInternalNotes($user, $ticket)) {
            return true;
        }

        if ((int) $ticket->requester_id === (int) $user->id || (int) $ticket->assignee_id === (int) $user->id) {
            return true;
        }

        return $ticket->watchers()
            ->whereKey($user->id)
            ->exists();
    }

    private function storeReadState(Ticket $ticket, User $user, int $activityId): void
    {
        TicketReadState::query()->updateOrCreate([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
        ], [
            'last_read_activity_id' => $activityId,
            'last_read_at' => now(),
        ]);
    }

    private function activityTypeForHistory(Ticket $ticket, TicketHistory $history, string $action): string
    {
        $changedFields = $history->meta['changed_fields'] ?? [];

        return match ($action) {
            'assignee_update' => TicketActivity::TYPE_ASSIGNEE_CHANGED,
            'priority_update' => TicketActivity::TYPE_PRIORITY_CHANGED,
            'category_update' => TicketActivity::TYPE_CATEGORY_CHANGED,
            'visibility_update' => TicketActivity::TYPE_VISIBILITY_CHANGED,
            'requester_update' => TicketActivity::TYPE_REQUESTER_CHANGED,
            'requester_confirm_resolution' => TicketActivity::TYPE_CLOSED,
            'requester_report_problem_persists' => TicketActivity::TYPE_PROBLEM_PERSISTS,
            'pin_update' => $ticket->is_pinned ? TicketActivity::TYPE_PINNED : TicketActivity::TYPE_UNPINNED,
            'ticket_archive' => TicketActivity::TYPE_ARCHIVED,
            'ticket_restore' => TicketActivity::TYPE_RESTORED,
            'status_update' => match ($ticket->statusSlug()) {
                'resolved' => TicketActivity::TYPE_RESOLVED,
                'closed' => TicketActivity::TYPE_CLOSED,
                default => TicketActivity::TYPE_STATUS_CHANGED,
            },
            'ticket_update' => in_array('expected_resolution_at', $changedFields, true)
                ? TicketActivity::TYPE_EXPECTED_RESOLUTION_CHANGED
                : TicketActivity::TYPE_TICKET_UPDATED,
            default => TicketActivity::TYPE_TICKET_UPDATED,
        };
    }

    private function activityVisibilityForType(string $type): string
    {
        return $type === TicketActivity::TYPE_INTERNAL_NOTE
            ? TicketActivity::VISIBILITY_INTERNAL
            : TicketActivity::VISIBILITY_PUBLIC;
    }
}
