<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketWatcher;
use Illuminate\Support\Facades\Schema;

class TicketWatcherService
{
    public function syncAutomaticParticipants(Ticket $ticket): void
    {
        if (! $this->supportsWatcherFlags()) {
            return;
        }

        $participantIds = collect([
            $ticket->requester_id,
            $ticket->assignee_id,
        ])
            ->filter()
            ->map(fn ($userId) => (int) $userId)
            ->unique()
            ->values();

        foreach ($participantIds as $userId) {
            $watcher = TicketWatcher::query()->firstOrNew([
                'ticket_id' => $ticket->id,
                'user_id' => $userId,
            ]);

            if (! $watcher->exists) {
                $watcher->is_manual = false;
            }

            $watcher->is_auto_participant = true;
            $watcher->save();
        }

        TicketWatcher::query()
            ->where('ticket_id', $ticket->id)
            ->where('is_auto_participant', true)
            ->when($participantIds->isNotEmpty(), fn ($query) => $query->whereNotIn('user_id', $participantIds))
            ->when($participantIds->isEmpty(), fn ($query) => $query)
            ->update(['is_auto_participant' => false]);

        $this->deleteInactiveWatchers($ticket);
    }

    public function startManualWatching(Ticket $ticket, int $userId): void
    {
        if (! $this->supportsWatcherFlags()) {
            $ticket->watchers()->syncWithoutDetaching([$userId]);

            return;
        }

        $watcher = TicketWatcher::query()->firstOrNew([
            'ticket_id' => $ticket->id,
            'user_id' => $userId,
        ]);

        $watcher->is_manual = true;
        $watcher->is_auto_participant = (bool) ($watcher->is_auto_participant ?? false);
        $watcher->save();
    }

    public function stopManualWatching(Ticket $ticket, int $userId): void
    {
        if (! $this->supportsWatcherFlags()) {
            $ticket->watchers()->detach($userId);

            return;
        }

        TicketWatcher::query()
            ->where('ticket_id', $ticket->id)
            ->where('user_id', $userId)
            ->update(['is_manual' => false]);

        $this->deleteInactiveWatchers($ticket);
    }

    private function deleteInactiveWatchers(Ticket $ticket): void
    {
        TicketWatcher::query()
            ->where('ticket_id', $ticket->id)
            ->where('is_manual', false)
            ->where('is_auto_participant', false)
            ->delete();
    }

    private function supportsWatcherFlags(): bool
    {
        static $supportsWatcherFlags;

        if ($supportsWatcherFlags === null) {
            $supportsWatcherFlags = Schema::hasColumn('ticket_watchers', 'is_manual')
                && Schema::hasColumn('ticket_watchers', 'is_auto_participant');
        }

        return $supportsWatcherFlags;
    }
}
