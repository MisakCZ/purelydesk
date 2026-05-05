<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Schema;

class TicketWorkflowAutomationService
{
    public const EXPECTED_RESOLUTION_SOURCE_AUTO = 'auto';
    public const EXPECTED_RESOLUTION_SOURCE_MANUAL = 'manual';

    /**
     * @var array<string, int|null>
     */
    private array $statusIdCache = [];

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function attributesForAssigneeUpdate(Ticket $ticket, ?int $newAssigneeId, array $attributes = []): array
    {
        $currentAssigneeId = $ticket->assignee_id !== null
            ? (int) $ticket->assignee_id
            : null;

        $attributes['assignee_id'] = $newAssigneeId;

        if ($currentAssigneeId === $newAssigneeId) {
            return $attributes;
        }

        if ($newAssigneeId === null) {
            if (! $ticket->hasStatusSlug('assigned')) {
                return $attributes;
            }

            return array_replace(
                $attributes,
                $this->attributesForStatusIdentifier($ticket, 'new'),
            );
        }

        if ($ticket->hasStatusSlug('new')) {
            $attributes = array_replace(
                $attributes,
                $this->attributesForStatusIdentifier($ticket, 'assigned'),
            );
        }

        return $this->applyMissingExpectedResolution($ticket, $attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function attributesForRequesterActivity(Ticket $ticket, ?User $actor, array $attributes = []): array
    {
        if (! $actor instanceof User || (int) $ticket->requester_id !== (int) $actor->id) {
            return $attributes;
        }

        if (! $ticket->hasStatusSlug('waiting_user')) {
            return $attributes;
        }

        return array_replace(
            $attributes,
            $this->attributesForStatusIdentifier($ticket, 'assigned'),
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function attributesForTicketUpdate(Ticket $ticket, ?User $actor, array $attributes = []): array
    {
        $attributes = $this->attributesForPriorityChange($ticket, (int) ($attributes['ticket_priority_id'] ?? $ticket->ticket_priority_id), $attributes);

        return $this->attributesForRequesterActivity($ticket, $actor, $attributes);
    }

    /**
     * @return array<string, mixed>
     */
    public function attributesForPriorityUpdate(Ticket $ticket, int $priorityId): array
    {
        return $this->attributesForPriorityChange($ticket, $priorityId, [
            'ticket_priority_id' => $priorityId,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function attributesForStatusIdentifier(Ticket $ticket, string $identifier): array
    {
        $status = $this->statusByIdentifier($identifier);

        if (! $status instanceof TicketStatus) {
            return [];
        }

        return $this->attributesForStatusTransition($ticket, $status);
    }

    /**
     * @return array<string, mixed>
     */
    public function attributesForStatusTransition(Ticket $ticket, TicketStatus $targetStatus): array
    {
        $attributes = [
            'ticket_status_id' => $targetStatus->id,
        ];

        $targetSlug = (string) $targetStatus->slug;
        $now = CarbonImmutable::now();

        if ($targetSlug === 'resolved') {
            return array_replace($attributes, [
                'resolved_at' => $ticket->hasStatusSlug('resolved')
                    ? $ticket->resolved_at
                    : $now,
                'auto_close_at' => $ticket->hasStatusSlug('resolved')
                    ? $ticket->auto_close_at
                    : $now->addDays(max(1, (int) config('helpdesk.workflow.resolved_auto_close_days', 5))),
                'closed_at' => null,
            ]);
        }

        if ($targetSlug === 'closed') {
            return array_replace($attributes, [
                'auto_close_at' => null,
                'closed_at' => $ticket->hasStatusSlug('closed')
                    ? $ticket->closed_at
                    : ($ticket->closed_at ?? $now),
            ]);
        }

        $attributes = array_replace($attributes, [
            'resolved_at' => null,
            'auto_close_at' => null,
            'closed_at' => null,
        ]);

        if (in_array($targetSlug, ['assigned', 'in_progress'], true)) {
            return $this->applyMissingExpectedResolution($ticket, $attributes);
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function attributesForPriorityChange(Ticket $ticket, int $priorityId, array $attributes): array
    {
        if ($priorityId <= 0 || (int) $ticket->ticket_priority_id === $priorityId) {
            return $attributes;
        }

        if (($attributes['assignee_id'] ?? $ticket->assignee_id) === null) {
            return $attributes;
        }

        if (array_key_exists('expected_resolution_at', $attributes)) {
            return $attributes;
        }

        if ($ticket->expected_resolution_at === null) {
            return $this->withAutomaticExpectedResolution($attributes, $priorityId);
        }

        if (Ticket::supportsExpectedResolutionSource() && $ticket->expected_resolution_source === self::EXPECTED_RESOLUTION_SOURCE_AUTO) {
            return $this->withAutomaticExpectedResolution($attributes, $priorityId);
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function applyMissingExpectedResolution(Ticket $ticket, array $attributes): array
    {
        if (! Ticket::supportsExpectedResolution() || array_key_exists('expected_resolution_at', $attributes)) {
            return $attributes;
        }

        if (($attributes['assignee_id'] ?? $ticket->assignee_id) === null || $ticket->expected_resolution_at !== null) {
            return $attributes;
        }

        return $this->withAutomaticExpectedResolution($attributes, (int) ($attributes['ticket_priority_id'] ?? $ticket->ticket_priority_id));
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function withAutomaticExpectedResolution(array $attributes, int $priorityId): array
    {
        if (! Ticket::supportsExpectedResolution()) {
            return $attributes;
        }

        $attributes['expected_resolution_at'] = CarbonImmutable::now()->addDays($this->defaultDaysForPriorityId($priorityId));

        if (Ticket::supportsExpectedResolutionSource()) {
            $attributes['expected_resolution_source'] = self::EXPECTED_RESOLUTION_SOURCE_AUTO;
        }

        return $attributes;
    }

    private function defaultDaysForPriorityId(int $priorityId): int
    {
        $slug = TicketPriority::query()->whereKey($priorityId)->value('slug') ?: 'normal';
        $days = (int) config("helpdesk.workflow.expected_resolution_days.{$slug}", config('helpdesk.workflow.expected_resolution_days.normal', 5));

        return max(1, $days);
    }

    private function statusByIdentifier(string $identifier): ?TicketStatus
    {
        if (array_key_exists($identifier, $this->statusIdCache)) {
            $statusId = $this->statusIdCache[$identifier];

            return $statusId !== null
                ? TicketStatus::query()->find($statusId)
                : null;
        }

        $status = TicketStatus::query()
            ->where(function ($query) use ($identifier): void {
                $query->where('slug', $identifier);

                if (Schema::hasColumn('ticket_statuses', 'code')) {
                    $query->orWhere('code', $identifier);
                }
            })
            ->first();

        $this->statusIdCache[$identifier] = $status?->id !== null
            ? (int) $status->id
            : null;

        return $status;
    }
}
