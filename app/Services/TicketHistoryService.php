<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketHistory;
use App\Models\User;

class TicketHistoryService
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function applyUpdateWithHistory(
        Ticket $ticket,
        array $attributes,
        string $action,
        ?User $actor = null,
    ): Ticket {
        $ticket->loadMissing($this->ticketSnapshotRelations());

        $oldSnapshot = $this->captureTicketSnapshot($ticket);

        $this->ensureOriginalSnapshot($ticket, 'backfill_before_update', $actor);

        $ticket->update($attributes);

        $ticket->refresh();
        $ticket->load($this->ticketSnapshotRelations());

        $newSnapshot = $this->captureTicketSnapshot($ticket);

        $this->recordSnapshotUpdate($ticket, $oldSnapshot, $newSnapshot, $action, $actor);

        return $ticket;
    }

    /**
     * @return array<int, string>
     */
    private function ticketSnapshotRelations(): array
    {
        return [
            'status:id,name',
            'priority:id,name',
            'category:id,name',
            'requester:id,name',
            'assignee:id,name',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function captureTicketSnapshot(Ticket $ticket): array
    {
        $ticket->loadMissing($this->ticketSnapshotRelations());

        return [
            'ticket_number' => $ticket->ticket_number,
            'subject' => $ticket->subject,
            'description' => $ticket->description,
            'visibility' => $ticket->visibility,
            'status' => [
                'id' => $ticket->ticket_status_id,
                'name' => $ticket->status?->name,
            ],
            'priority' => [
                'id' => $ticket->ticket_priority_id,
                'name' => $ticket->priority?->name,
            ],
            'category' => [
                'id' => $ticket->ticket_category_id,
                'name' => $ticket->category?->name,
            ],
            'requester' => [
                'id' => $ticket->requester_id,
                'name' => $ticket->requester?->name,
            ],
            'assignee' => $ticket->assignee_id
                ? [
                    'id' => $ticket->assignee_id,
                    'name' => $ticket->assignee?->name,
                ]
                : null,
            'pinned' => Ticket::supportsPinning()
                ? [
                    'is_pinned' => (bool) $ticket->is_pinned,
                    'pinned_at' => $ticket->pinned_at?->toIso8601String(),
                ]
                : null,
            'expected_resolution_at' => $ticket->expected_resolution_at?->toIso8601String(),
            'resolved_at' => $ticket->resolved_at?->toIso8601String(),
            'auto_close_at' => $ticket->auto_close_at?->toIso8601String(),
            'closed_at' => $ticket->closed_at?->toIso8601String(),
            'created_at' => $ticket->created_at?->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, mixed>  $oldSnapshot
     * @param  array<string, mixed>  $newSnapshot
     */
    private function recordSnapshotUpdate(
        Ticket $ticket,
        array $oldSnapshot,
        array $newSnapshot,
        string $action,
        ?User $actor = null,
    ): void {
        if (! $this->snapshotHasDifferences($oldSnapshot, $newSnapshot)) {
            return;
        }

        $ticket->history()->create([
            'user_id' => $actor?->id,
            'event' => TicketHistory::EVENT_UPDATED,
            'field' => null,
            'old_value' => $oldSnapshot,
            'new_value' => $newSnapshot,
            'meta' => [
                'action' => $action,
                'changed_fields' => $this->snapshotChangedFields($oldSnapshot, $newSnapshot),
            ],
        ]);
    }

    private function ensureOriginalSnapshot(Ticket $ticket, string $source, ?User $actor = null): void
    {
        if ($this->originalSnapshotEntry($ticket) instanceof TicketHistory) {
            return;
        }

        $ticket->loadMissing($this->ticketSnapshotRelations());

        $ticket->history()->create([
            'user_id' => $actor?->id,
            'event' => $source === 'create'
                ? TicketHistory::EVENT_CREATED
                : TicketHistory::EVENT_ORIGINAL_SNAPSHOT_BACKFILLED,
            'field' => TicketHistory::FIELD_SNAPSHOT,
            'old_value' => null,
            'new_value' => $this->captureTicketSnapshot($ticket),
            'meta' => [
                'source' => $source,
            ],
        ]);
    }

    private function originalSnapshotEntry(Ticket $ticket): ?TicketHistory
    {
        return $ticket->history()
            ->where('field', TicketHistory::FIELD_SNAPSHOT)
            ->whereIn('event', [
                TicketHistory::EVENT_CREATED,
                TicketHistory::EVENT_ORIGINAL_SNAPSHOT_BACKFILLED,
            ])
            ->oldest('id')
            ->first();
    }

    /**
     * @param  array<string, mixed>  $oldSnapshot
     * @param  array<string, mixed>  $newSnapshot
     * @return array<int, string>
     */
    private function snapshotChangedFields(array $oldSnapshot, array $newSnapshot): array
    {
        $changedFields = [];

        foreach (array_unique([...array_keys($oldSnapshot), ...array_keys($newSnapshot)]) as $field) {
            if (($oldSnapshot[$field] ?? null) !== ($newSnapshot[$field] ?? null)) {
                $changedFields[] = $field;
            }
        }

        return $changedFields;
    }

    /**
     * @param  array<string, mixed>  $oldSnapshot
     * @param  array<string, mixed>  $newSnapshot
     */
    private function snapshotHasDifferences(array $oldSnapshot, array $newSnapshot): bool
    {
        return $this->snapshotChangedFields($oldSnapshot, $newSnapshot) !== [];
    }
}
