<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketStatus;
use App\Models\User;
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
        $attributes['assignee_id'] = $newAssigneeId;

        if ($newAssigneeId === null || (int) $ticket->assignee_id === $newAssigneeId) {
            return $attributes;
        }

        if (! $ticket->hasStatusSlug('new')) {
            return $attributes;
        }

        $assignedStatusId = $this->statusIdByIdentifier('assigned');

        if ($assignedStatusId !== null && (int) $ticket->ticket_status_id !== $assignedStatusId) {
            $attributes['ticket_status_id'] = $assignedStatusId;
        }

        return $attributes;
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

        $assignedStatusId = $this->statusIdByIdentifier('assigned');

        if ($assignedStatusId !== null && (int) $ticket->ticket_status_id !== $assignedStatusId) {
            $attributes['ticket_status_id'] = $assignedStatusId;
        }

        return $attributes;
    }

    private function statusIdByIdentifier(string $identifier): ?int
    {
        if (array_key_exists($identifier, $this->statusIdCache)) {
            return $this->statusIdCache[$identifier];
        }

        $statusId = TicketStatus::query()
            ->where(function ($query) use ($identifier): void {
                $query->where('slug', $identifier);

                if (Schema::hasColumn('ticket_statuses', 'code')) {
                    $query->orWhere('code', $identifier);
                }
            })
            ->value('id');

        return $this->statusIdCache[$identifier] = $statusId !== null
            ? (int) $statusId
            : null;
    }
}
