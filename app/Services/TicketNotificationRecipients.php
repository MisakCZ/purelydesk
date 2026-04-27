<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use App\Policies\TicketPolicy;
use Illuminate\Support\Collection;

class TicketNotificationRecipients
{
    public function __construct(
        private readonly TicketPolicy $ticketPolicy,
    ) {}

    /**
     * @return Collection<int, User>
     */
    public function forTicket(Ticket $ticket, ?User $actor = null, bool $excludeActor = true): Collection
    {
        $ticket->loadMissing([
            'requester:id,name,email,preferred_locale',
            'assignee:id,name,email,preferred_locale',
            'watchers:id,name,email,preferred_locale',
        ]);

        return collect([
            $ticket->requester,
            $ticket->assignee,
            ...$ticket->watchers,
        ])
            ->filter(fn ($user) => $user instanceof User)
            ->when(
                $excludeActor && $actor instanceof User,
                fn (Collection $users) => $users->reject(fn (User $user) => (int) $user->id === (int) $actor->id),
            )
            ->filter(fn (User $user) => filled($user->email))
            ->filter(fn (User $user) => $this->ticketPolicy->view($user, $ticket))
            ->unique(fn (User $user) => $this->deduplicationKey($user))
            ->values();
    }

    private function deduplicationKey(User $user): string
    {
        return $user->email !== null && $user->email !== ''
            ? 'email:'.mb_strtolower($user->email)
            : 'id:'.$user->id;
    }
}
