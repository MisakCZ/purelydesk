<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketEventNotification;

class TicketNotificationService
{
    public function __construct(
        private readonly TicketNotificationRecipients $recipients,
        private readonly TicketNotificationBatchService $batches,
        private readonly TicketNotificationBatchSender $batchSender,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     */
    public function notify(Ticket $ticket, string $event, ?User $actor = null, array $context = [], bool $excludeActor = true): void
    {
        if (! config('helpdesk.notifications.mail.enabled')) {
            return;
        }

        $additionalRecipients = $context['additional_recipients'] ?? [];
        unset($context['additional_recipients']);

        $recipients = $this->recipients->forTicket(
            $ticket,
            $event,
            $actor,
            $excludeActor,
            is_array($additionalRecipients) ? $additionalRecipients : [],
        );

        if ($actor instanceof User) {
            $context['actor_name'] ??= $actor->notificationName();
        }

        foreach ($recipients as $recipient) {
            if ($this->batches->shouldBatch($ticket, $event, $actor, $recipient)) {
                $this->batches->add($ticket, $recipient, $event, $actor, $context);

                continue;
            }

            $recipient->notify(new TicketEventNotification($ticket, $event, $context));
        }

        if ($this->batches->enabled() && $this->batches->shouldFlush($ticket, $event)) {
            $this->batchSender->sendPendingForTicket($ticket);
        }
    }
}
