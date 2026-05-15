<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketEventNotification;

class TicketNotificationService
{
    public function __construct(
        private readonly TicketNotificationRecipients $recipients,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     */
    public function notify(Ticket $ticket, string $event, ?User $actor = null, array $context = [], bool $excludeActor = true): void
    {
        if (! config('helpdesk.notifications.mail.enabled')) {
            return;
        }

        $recipients = $this->recipients->forTicket($ticket, $event, $actor, $excludeActor);

        if ($recipients->isEmpty()) {
            return;
        }

        if ($actor instanceof User) {
            $context['actor_name'] ??= $actor->notificationName();
        }

        $notification = new TicketEventNotification($ticket, $event, $context);

        $recipients->each(fn (User $recipient) => $recipient->notify($notification));
    }
}
