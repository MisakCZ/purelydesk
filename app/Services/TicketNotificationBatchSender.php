<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketNotificationBatch;
use App\Models\User;
use App\Notifications\TicketNotificationBatchNotification;
use App\Policies\TicketPolicy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class TicketNotificationBatchSender
{
    public function __construct(
        private readonly TicketPolicy $ticketPolicy,
    ) {}

    /**
     * @return array{sent: int, suppressed: int, failed: int}
     */
    public function sendDue(int $chunkSize = 50): array
    {
        $counts = ['sent' => 0, 'suppressed' => 0, 'failed' => 0];

        if (! config('helpdesk.notifications.mail.enabled')
            || ! config('helpdesk.notifications.mail.batch.enabled', true)
        ) {
            return $counts;
        }

        $this->recoverStaleClaims();

        TicketNotificationBatch::query()
            ->where('active_marker', true)
            ->whereIn('status', [
                TicketNotificationBatch::STATUS_PENDING,
                TicketNotificationBatch::STATUS_FAILED,
            ])
            ->where('send_after', '<=', now())
            ->orderBy('id')
            ->select('id')
            ->chunkById(max(1, min(500, $chunkSize)), function ($batches) use (&$counts): void {
                foreach ($batches as $batch) {
                    $result = $this->send((int) $batch->id);

                    if (array_key_exists($result, $counts)) {
                        $counts[$result]++;
                    }
                }
            });

        return $counts;
    }

    /**
     * @return array{sent: int, suppressed: int, failed: int}
     */
    public function sendPendingForTicket(Ticket $ticket): array
    {
        $counts = ['sent' => 0, 'suppressed' => 0, 'failed' => 0];

        if (! config('helpdesk.notifications.mail.enabled')
            || ! config('helpdesk.notifications.mail.batch.enabled', true)
        ) {
            return $counts;
        }

        $ids = TicketNotificationBatch::query()
            ->where('ticket_id', $ticket->id)
            ->where('active_marker', true)
            ->whereIn('status', [
                TicketNotificationBatch::STATUS_PENDING,
                TicketNotificationBatch::STATUS_FAILED,
            ])
            ->orderBy('id')
            ->pluck('id');

        foreach ($ids as $id) {
            $result = $this->send((int) $id);

            if (array_key_exists($result, $counts)) {
                $counts[$result]++;
            }
        }

        return $counts;
    }

    public function send(int $batchId): string
    {
        $claimed = DB::transaction(function () use ($batchId): bool {
            $batch = TicketNotificationBatch::query()->lockForUpdate()->find($batchId);

            if (! $batch instanceof TicketNotificationBatch
                || ! $batch->active_marker
                || ! in_array($batch->status, [
                    TicketNotificationBatch::STATUS_PENDING,
                    TicketNotificationBatch::STATUS_FAILED,
                ], true)
            ) {
                return false;
            }

            $batch->forceFill(['status' => TicketNotificationBatch::STATUS_SENDING])->save();

            return true;
        }, 3);

        if (! $claimed) {
            return 'skipped';
        }

        $batch = TicketNotificationBatch::query()
            ->with([
                'ticket.status:id,name,slug',
                'recipient.roles:id,slug',
                'items' => fn ($query) => $query
                    ->with('actor:id,name,display_name,username')
                    ->orderBy('created_at')
                    ->orderBy('id'),
            ])
            ->find($batchId);

        if (! $batch instanceof TicketNotificationBatch
            || ! $batch->ticket instanceof Ticket
            || ! $this->eligibleRecipient($batch->recipient, $batch->ticket)
            || $batch->items->isEmpty()
        ) {
            $this->finish($batchId, TicketNotificationBatch::STATUS_SUPPRESSED);

            return 'suppressed';
        }

        try {
            $batch->recipient->notify(new TicketNotificationBatchNotification($batch));
            $this->finish($batchId, TicketNotificationBatch::STATUS_SENT);

            return 'sent';
        } catch (Throwable $exception) {
            TicketNotificationBatch::query()->whereKey($batchId)->update([
                'status' => TicketNotificationBatch::STATUS_FAILED,
                'failed_at' => now(),
                'last_error' => Str::limit($exception->getMessage(), 2000, ''),
                'send_after' => now()->addMinutes(5),
                'updated_at' => now(),
            ]);

            report($exception);

            return 'failed';
        }
    }

    private function eligibleRecipient(?User $recipient, Ticket $ticket): bool
    {
        return $recipient instanceof User
            && $recipient->is_active !== false
            && filled($recipient->email)
            && filter_var($recipient->email, FILTER_VALIDATE_EMAIL) !== false
            && $this->ticketPolicy->view($recipient, $ticket);
    }

    private function finish(int $batchId, string $status): void
    {
        TicketNotificationBatch::query()->whereKey($batchId)->update([
            'status' => $status,
            'active_marker' => null,
            'sent_at' => $status === TicketNotificationBatch::STATUS_SENT ? now() : null,
            'last_error' => null,
            'updated_at' => now(),
        ]);
    }

    private function recoverStaleClaims(): void
    {
        TicketNotificationBatch::query()
            ->where('active_marker', true)
            ->where('status', TicketNotificationBatch::STATUS_SENDING)
            ->where('updated_at', '<=', now()->subMinutes(15))
            ->update([
                'status' => TicketNotificationBatch::STATUS_FAILED,
                'failed_at' => now(),
                'last_error' => 'Recovered stale sending claim.',
                'send_after' => now(),
                'updated_at' => now(),
            ]);
    }
}
