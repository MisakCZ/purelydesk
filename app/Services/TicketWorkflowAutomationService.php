<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketStatus;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Schema;

class TicketWorkflowAutomationService
{
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

        if (! $ticket->hasStatusSlug('new')) {
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
                    : $now->addDays(5),
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

        return array_replace($attributes, [
            'resolved_at' => null,
            'auto_close_at' => null,
            'closed_at' => null,
        ]);
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
